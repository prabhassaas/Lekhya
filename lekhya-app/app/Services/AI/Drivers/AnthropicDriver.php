<?php
namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicDriver implements AiDriverInterface
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;

    public function __construct()
    {
        // config value is env('ANTHROPIC_API_KEY') which is null when unset —
        // cast so the typed string property never receives null.
        $this->apiKey    = (string) config('services.ai.anthropic_key', '');
        $this->model     = 'claude-haiku-4-5-20251001'; // fast + cheap for ERP tasks
        $this->maxTokens = (int) config('services.ai.max_tokens', 2048);
    }

    public function extractInvoice(string $text, ?string $imageBase64 = null): array
    {
        $content = [['type' => 'text', 'text' => $this->invoiceExtractionPrompt($text)]];

        if ($imageBase64) {
            $content = [
                ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $imageBase64]],
                ['type' => 'text',  'text' => 'Extract all invoice fields from this image as JSON.'],
            ];
        }

        return $this->call($content);
    }

    public function parseNlQueryIntent(string $query): array
    {
        return $this->call([['type' => 'text', 'text' => $this->nlQueryPrompt($query)]]);
    }

    public function suggestAccount(string $description, float $amount, string $vendor): array
    {
        return $this->call([['type' => 'text', 'text' => $this->accountCodingPrompt($description, $amount, $vendor)]]);
    }

    public function detectAnomaly(array $journalData, float $averageAmount): array
    {
        return $this->call([['type' => 'text', 'text' => $this->anomalyPrompt($journalData, $averageAmount)]]);
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    private function call(array $content): array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key'         => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => $this->model,
                    'max_tokens' => $this->maxTokens,
                    'messages'   => [['role' => 'user', 'content' => $content]],
                ]);

            if (!$response->successful()) {
                Log::warning('Anthropic request failed', ['status' => $response->status()]);
                return ['error' => 'Anthropic API error: ' . $response->status()];
            }

            $raw = $response->json('content.0.text', '');
            return $this->parseJsonResponse($raw);
        } catch (\Throwable $e) {
            Log::error('Anthropic driver error', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    private function parseJsonResponse(string $raw): array
    {
        if (preg_match('/```(?:json)?\s*([\s\S]+?)\s*```/', $raw, $m)) {
            $raw = $m[1];
        }
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start !== false && $end !== false) {
            $raw = substr($raw, $start, $end - $start + 1);
        }
        $decoded = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : ['error' => 'JSON parse failed', 'raw' => substr($raw, 0, 200)];
    }

    private function invoiceExtractionPrompt(string $text): string
    {
        return "Extract invoice data as JSON from:\n\n{$text}\n\nReturn ONLY JSON with fields: invoice_number, invoice_date (YYYY-MM-DD), due_date, party_name, party_gstin, party_address, lines (array of description/hsn_sac/quantity/rate/amount/gst_rate), subtotal, cgst_amount, sgst_amount, igst_amount, total_amount, currency, payment_terms, confidence (0-1).";
    }

    private function nlQueryPrompt(string $query): string
    {
        return "Parse this accounting query into JSON intent. Query: {$query}\nReturn JSON: {intent, period, date_from, date_to, filters, description}. Valid intents: sales_total, expense_total, outstanding_ar, outstanding_ap, profit_loss, gst_liability, bank_balance, invoice_count, top_customers, top_vendors. Valid periods: today, this_week, this_month, last_month, this_quarter, this_year, last_year.";
    }

    private function accountCodingPrompt(string $description, float $amount, string $vendor): string
    {
        return "Suggest Indian CoA account for: description={$description}, amount=Rs " . number_format($amount, 2) . ", vendor={$vendor}. Return JSON: {account_name, account_type (expense/income/asset/liability), confidence (0-1), reason, alternatives (array)}.";
    }

    private function anomalyPrompt(array $data, float $avg): string
    {
        $j = json_encode($data);
        return "Check this journal for fraud/anomalies: {$j}. Average for account: Rs " . number_format($avg, 2) . ". Return JSON: {is_anomaly (bool), severity (low/medium/high/critical), flags (array), recommendation, confidence (0-1)}.";
    }
}
