<?php
namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Groq — OpenAI-compatible, very fast, cheap. Handles both text (chat/intent)
 * and vision (invoice OCR) through one API. The key + models are injected
 * per-tenant from ai_settings; falls back to env config for CLI/queue use.
 */
class GroqDriver implements AiDriverInterface
{
    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    private string $apiKey;
    private string $textModel;
    private string $visionModel;
    private int $maxTokens;

    public function __construct(array $config = [])
    {
        // Use ?? on every key — this ctor is also called with an empty config
        // in the env-fallback path, so the keys may be absent (not just null).
        $this->apiKey      = ($config['api_key']      ?? null) ?: (string) config('services.ai.groq_key', '');
        $this->textModel   = ($config['text_model']   ?? null) ?: config('services.ai.groq_text_model', 'llama-3.3-70b-versatile');
        $this->visionModel = ($config['vision_model'] ?? null) ?: config('services.ai.groq_vision_model', 'meta-llama/llama-4-scout-17b-16e-instruct');
        $this->maxTokens   = (int) config('services.ai.max_tokens', 2048);
    }

    public function extractInvoice(string $text, ?string $imageBase64 = null): array
    {
        if ($imageBase64) {
            $content = [
                ['type' => 'text', 'text' => $this->invoiceExtractionPrompt('(see attached image)')],
                ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imageBase64}"]],
            ];
            return $this->call($content, vision: true);
        }

        return $this->call([['type' => 'text', 'text' => $this->invoiceExtractionPrompt($text)]]);
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
        return ! empty($this->apiKey);
    }

    private function call(array $content, bool $vision = false): array
    {
        try {
            $body = [
                'model'       => $vision ? $this->visionModel : $this->textModel,
                'messages'    => [['role' => 'user', 'content' => $content]],
                'max_tokens'  => $this->maxTokens,
                'temperature' => (float) config('services.ai.temperature', 0.1),
            ];
            if (! $vision) {
                // JSON mode keeps text responses strictly parseable.
                $body['response_format'] = ['type' => 'json_object'];
            }

            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post(self::ENDPOINT, $body);

            if (! $response->successful()) {
                Log::warning('Groq request failed', ['status' => $response->status(), 'body' => $response->body()]);
                return ['error' => 'Groq API error: ' . $response->status()];
            }

            return $this->parseJsonResponse($response->json('choices.0.message.content', ''));
        } catch (\Throwable $e) {
            Log::error('Groq driver error', ['error' => $e->getMessage()]);
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
        return "You are an expert Indian GST invoice reader. Extract EVERY detail as JSON from the invoice below — miss nothing.\n\n{$text}\n\n"
            . "Return ONLY JSON with fields: invoice_number, invoice_date (YYYY-MM-DD), due_date (YYYY-MM-DD or null), "
            . "party_name, party_gstin, party_pan, party_address, party_email, party_phone, "
            . "lines (array of {description, hsn_sac, quantity, unit, rate, discount_percent, amount, gst_rate}), "
            . "subtotal, cgst_amount, sgst_amount, igst_amount, cess_amount, round_off, total_amount, currency, "
            . "place_of_supply, reverse_charge (true/false), payment_terms, notes, "
            . "confidence (0-1 overall), field_confidence (object mapping each top-level field name to 0-1). "
            . "Use null for any field you cannot read. Never invent values.";
    }

    private function nlQueryPrompt(string $query): string
    {
        return "Parse this accounting query into JSON intent. Query: {$query}\n"
            . "Return JSON: {intent, period, date_from, date_to, filters, description}. "
            . "Valid intents: sales_total, expense_total, outstanding_ar, outstanding_ap, profit_loss, gst_liability, bank_balance, invoice_count, top_customers, top_vendors. "
            . "Valid periods: today, this_week, this_month, last_month, this_quarter, this_year, last_year.";
    }

    private function accountCodingPrompt(string $description, float $amount, string $vendor): string
    {
        return "Suggest an Indian Chart-of-Accounts ledger for: description={$description}, amount=Rs " . number_format($amount, 2) . ", vendor={$vendor}. "
            . "Return JSON: {account_name, account_type (expense/income/asset/liability), confidence (0-1), reason, alternatives (array)}.";
    }

    private function anomalyPrompt(array $data, float $avg): string
    {
        $j = json_encode($data);
        return "Check this journal for fraud/anomalies: {$j}. Average for account: Rs " . number_format($avg, 2) . ". "
            . "Return JSON: {is_anomaly (bool), severity (low/medium/high/critical), flags (array), recommendation, confidence (0-1)}.";
    }
}
