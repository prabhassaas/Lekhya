<?php
namespace App\Services\AI;

use App\Services\AI\Contracts\AiDriverInterface;
use App\Services\AI\Drivers\AnthropicDriver;
use App\Services\AI\Drivers\MockDriver;
use App\Services\AI\Drivers\OllamaDriver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class AiService
{
    private AiDriverInterface $driver;

    public function __construct()
    {
        $this->driver = $this->resolveDriver();
    }

    private function resolveDriver(): AiDriverInterface
    {
        $configured = config('services.ai.driver', 'ollama');

        $driver = match ($configured) {
            'anthropic' => new AnthropicDriver(),
            'mock'      => new MockDriver(),
            default     => new OllamaDriver(),
        };

        // Auto-fallback chain: if primary unavailable, try next available
        if (!$driver->isAvailable()) {
            Log::info("AI driver [{$configured}] unavailable, trying fallback chain");

            if ($configured !== 'anthropic' && (new AnthropicDriver())->isAvailable()) {
                return new AnthropicDriver();
            }
            return new MockDriver();
        }

        return $driver;
    }

    public function getDriverName(): string
    {
        return match (true) {
            $this->driver instanceof OllamaDriver    => 'ollama',
            $this->driver instanceof AnthropicDriver => 'anthropic',
            default                                  => 'mock',
        };
    }

    public function isAvailable(): bool
    {
        return $this->driver->isAvailable();
    }

    /**
     * Extract structured invoice data from a PDF or image file.
     */
    public function extractFromFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $fullPath  = $file->getRealPath();

        $text         = '';
        $imageBase64  = null;

        if ($extension === 'pdf') {
            $text = $this->pdfToText($fullPath);
        } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'webp'])) {
            $text        = "Image invoice file: {$file->getClientOriginalName()}";
            $imageBase64 = base64_encode(file_get_contents($fullPath));
        } else {
            $text = file_get_contents($fullPath) ?: '';
        }

        if (empty(trim($text)) && !$imageBase64) {
            return ['error' => 'Could not extract text from file. Ensure it is a readable PDF or image.'];
        }

        return $this->driver->extractInvoice($text, $imageBase64);
    }

    /**
     * Parse a natural language accounting query into a structured intent.
     * Then execute the safe pre-defined query for that intent.
     */
    public function runNlQuery(string $query, int $tenantId): array
    {
        $intent = $this->driver->parseNlQueryIntent($query);

        if (isset($intent['error'])) {
            return $intent;
        }

        // Execute the safe parameterized query based on intent
        return $this->executeIntent($intent, $tenantId);
    }

    /**
     * Suggest a ledger account for a transaction description.
     */
    public function suggestAccount(string $description, float $amount, string $vendor = ''): array
    {
        return $this->driver->suggestAccount($description, $amount, $vendor);
    }

    /**
     * Run anomaly detection on a journal entry.
     */
    public function detectAnomaly(array $journalData): array
    {
        // Compute the average amount for this account over last 90 days
        $averageAmount = $this->computeAccountAverage(
            $journalData['account_id'] ?? 0,
            $journalData['tenant_id']  ?? 0
        );
        return $this->driver->detectAnomaly($journalData, $averageAmount);
    }

    // Internal helpers

    private function pdfToText(string $path): string
    {
        // pdftotext from poppler-utils — available on most Linux servers
        if ($this->commandExists('pdftotext')) {
            $escaped = escapeshellarg($path);
            $text    = shell_exec("pdftotext {$escaped} - 2>/dev/null");
            if ($text && strlen(trim($text)) > 10) {
                return $text;
            }
        }

        // php-pdfparser via composer (if installed)
        if (class_exists('\Smalot\PdfParser\Parser')) {
            try {
                $parser   = new \Smalot\PdfParser\Parser();
                $pdf      = $parser->parseFile($path);
                return $pdf->getText();
            } catch (\Throwable) {}
        }

        // Last resort: read raw bytes and try to find text snippets
        $raw = file_get_contents($path);
        preg_match_all('/\(([^\)]{3,})\)/', $raw ?? '', $m);
        return implode(' ', $m[1] ?? []);
    }

    private function commandExists(string $cmd): bool
    {
        if (function_exists('shell_exec') && !str_contains((string) ini_get('disable_functions'), 'shell_exec')) {
            return (bool) shell_exec("command -v {$cmd} 2>/dev/null");
        }
        return false;
    }

    private function computeAccountAverage(int $accountId, int $tenantId): float
    {
        if (!$accountId || !$tenantId) {
            return 0.0;
        }
        return (float) \App\Models\JournalLine::where('tenant_id', $tenantId)
            ->where('account_id', $accountId)
            ->where('created_at', '>=', now()->subDays(90))
            ->avg('debit') ?: 0.0;
    }

    private function executeIntent(array $intent, int $tenantId): array
    {
        $period = $intent['period'] ?? 'this_month';
        [$from, $to] = $this->periodDates($period, $intent);

        $base = fn(string $model) => \App\Models\Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$from, $to]);

        $result = match ($intent['intent'] ?? '') {
            'sales_total'    => ['label' => 'Total Sales',        'value' => (clone $base('invoice'))->where('type', 'sales')->sum('total_amount'),    'format' => 'currency'],
            'expense_total'  => ['label' => 'Total Purchases',    'value' => (clone $base('invoice'))->where('type', 'purchase')->sum('total_amount'),  'format' => 'currency'],
            'outstanding_ar' => ['label' => 'Outstanding AR',     'value' => (clone $base('invoice'))->where('type', 'sales')->whereIn('status', ['posted','partially_paid'])->sum('balance_amount'), 'format' => 'currency'],
            'outstanding_ap' => ['label' => 'Outstanding AP',     'value' => (clone $base('invoice'))->where('type', 'purchase')->whereIn('status', ['posted','partially_paid'])->sum('balance_amount'), 'format' => 'currency'],
            'invoice_count'  => ['label' => 'Invoice Count',      'value' => (clone $base('invoice'))->count(),                                        'format' => 'number'],
            'gst_liability'  => ['label' => 'GST Payable (Est.)', 'value' => (clone $base('invoice'))->where('type', 'sales')->sum('igst_amount') + (clone $base('invoice'))->where('type', 'sales')->sum('cgst_amount') + (clone $base('invoice'))->where('type', 'sales')->sum('sgst_amount'), 'format' => 'currency'],
            default          => ['label' => 'Query Result',       'value' => null, 'format' => 'text', 'note' => 'Query intent not yet supported. Try: total sales this month, outstanding receivables, GST liability.'],
        };

        return array_merge($result, [
            'period_label' => ucwords(str_replace('_', ' ', $period)),
            'date_from'    => $from,
            'date_to'      => $to,
            'intent'       => $intent['intent'] ?? '',
            'description'  => $intent['description'] ?? '',
        ]);
    }

    private function periodDates(string $period, array $intent): array
    {
        return match ($period) {
            'today'          => [now()->toDateString(), now()->toDateString()],
            'this_week'      => [now()->startOfWeek()->toDateString(), now()->toDateString()],
            'this_month'     => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'last_month'     => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'this_quarter'   => [now()->startOfQuarter()->toDateString(), now()->toDateString()],
            'last_quarter'   => [now()->subQuarter()->startOfQuarter()->toDateString(), now()->subQuarter()->endOfQuarter()->toDateString()],
            'this_year'      => [now()->startOfYear()->toDateString(), now()->toDateString()],
            'last_year'      => [now()->subYear()->startOfYear()->toDateString(), now()->subYear()->endOfYear()->toDateString()],
            'custom'         => [$intent['date_from'] ?? now()->startOfMonth()->toDateString(), $intent['date_to'] ?? now()->toDateString()],
            default          => [now()->startOfMonth()->toDateString(), now()->toDateString()],
        };
    }
}
