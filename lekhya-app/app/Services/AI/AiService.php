<?php
namespace App\Services\AI;

use App\Models\AiSetting;
use App\Services\AI\Contracts\AiDriverInterface;
use App\Services\AI\Drivers\AnthropicDriver;
use App\Services\AI\Drivers\GroqDriver;
use App\Services\AI\Drivers\MockDriver;
use App\Services\AI\Drivers\OllamaDriver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class AiService
{
    // Keep the prompt well under Groq's request-size ceiling; a big PDF's text
    // otherwise returns HTTP 400.
    private const MAX_PROMPT_CHARS = 24000;

    private AiDriverInterface $driver;

    public function __construct()
    {
        $this->driver = $this->resolveDriver();
    }

    private function resolveDriver(): AiDriverInterface
    {
        // 1. Per-tenant key from ai_settings takes priority (this is where a
        //    user's own Groq key lives — encrypted, never in env or git).
        $setting = $this->tenantSetting();
        if ($setting && $setting->is_active && $setting->hasKey()) {
            $driver = $this->build($setting->provider, [
                'api_key'      => $setting->api_key,
                'text_model'   => $setting->text_model,
                'vision_model' => $setting->vision_model,
            ]);
            if ($driver && $driver->isAvailable()) {
                return $driver;
            }
        }

        // 2. Central Prabhas SaaS key (env/secret) — auto-enabled for any
        //    tenant on an active subscription or trial. No per-user keys.
        if ($this->aiEntitled()) {
            $configured = config('services.ai.driver', 'groq');
            foreach ([$configured, 'groq', 'anthropic', 'ollama'] as $provider) {
                $d = $this->build($provider);
                if ($d && $d->isAvailable()) {
                    return $d;
                }
            }
        }

        // 3. Not entitled, or no key configured yet → offline mock.
        return new MockDriver();
    }

    /** AI runs on the central key only for subscribed/trial tenants (CLI/queue exempt). */
    private function aiEntitled(): bool
    {
        if (! auth()->check()) {
            return true; // CLI / queue jobs
        }
        return (bool) auth()->user()->tenant?->aiEnabled();
    }

    private function build(string $provider, array $config = []): ?AiDriverInterface
    {
        return match ($provider) {
            'groq'      => new GroqDriver($config),
            'anthropic' => new AnthropicDriver(),
            'mock'      => new MockDriver(),
            'ollama'    => new OllamaDriver(),
            default     => null,
        };
    }

    private function tenantSetting(): ?AiSetting
    {
        // Only available inside an authenticated web request; safe (null) on CLI/queue.
        return auth()->check() ? auth()->user()->tenant?->aiSetting : null;
    }

    public function getDriverName(): string
    {
        return match (true) {
            $this->driver instanceof GroqDriver      => 'groq',
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
            $text = "Image invoice file: {$file->getClientOriginalName()}";
            // Downscale first — a phone photo of a bill easily exceeds Groq's
            // vision limits (4MB base64 / 33 megapixels) and returns HTTP 400.
            $imageBase64 = base64_encode($this->downscaleImage($fullPath) ?: (file_get_contents($fullPath) ?: ''));
        } else {
            $text = file_get_contents($fullPath) ?: '';
        }

        if (empty(trim($text)) && !$imageBase64) {
            return ['error' => 'Could not extract text from file. Ensure it is a readable PDF or image.'];
        }

        // Scrub before the call: raw-byte PDF text can carry invalid UTF-8, which
        // makes Guzzle's json_encode of the request body throw "Malformed UTF-8".
        // Cap the length too — an over-long prompt trips Groq's request-size 400.
        $text = mb_substr($this->sanitizeUtf8($text), 0, self::MAX_PROMPT_CHARS);

        // Scrub the response too, so Eloquent's JSON cast can persist it safely.
        return $this->scrubUtf8($this->driver->extractInvoice($text, $imageBase64));
    }

    /**
     * Shrink an uploaded image so its base64 stays under Groq's vision limits
     * (4MB base64, 33 megapixels). Returns JPEG bytes, or null if GD can't read
     * it (caller then falls back to the original bytes).
     */
    private function downscaleImage(string $path): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            return null;
        }

        // Step down dimensions/quality until the base64 is safely under 4MB.
        // Start large — HSN codes and GST rates are small print, so more pixels
        // means the model can actually read them (Groq allows up to 33 MP / 4MB).
        foreach ([[2400, 82], [1800, 78], [1200, 70]] as [$dim, $quality]) {
            $w = imagesx($img);
            $h = imagesy($img);
            $scale  = min(1.0, $dim / max($w, $h));
            $canvas = $img;
            if ($scale < 1.0) {
                $scaled = imagescale($img, (int) round($w * $scale), (int) round($h * $scale));
                if ($scaled !== false) {
                    $canvas = $scaled;
                }
            }

            ob_start();
            imagejpeg($canvas, null, $quality);
            $jpeg = (string) ob_get_clean();
            if ($canvas !== $img) {
                imagedestroy($canvas);
            }

            if ($jpeg !== '' && strlen($jpeg) * 4 / 3 < 3_800_000) { // base64 inflates ~33%
                imagedestroy($img);
                return $jpeg;
            }
        }

        imagedestroy($img);
        return null;
    }

    /**
     * Force a string to valid UTF-8. Both the AI HTTP call (Guzzle) and the
     * Eloquent JSON cast of the stored suggestion run json_encode, which rejects
     * malformed byte sequences with "Malformed UTF-8 characters". Drop them.
     */
    private function sanitizeUtf8(string $s): string
    {
        if ($s === '' || preg_match('//u', $s) === 1) {
            return $s; // already valid UTF-8 — fast path
        }
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        return $clean !== false ? $clean : mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    }

    /** Recursively scrub every string in an extraction array to valid UTF-8. */
    private function scrubUtf8(array $data): array
    {
        array_walk_recursive($data, function (&$v) {
            if (is_string($v)) {
                $v = $this->sanitizeUtf8($v);
            }
        });
        return $data;
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
