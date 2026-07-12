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
        // For vision, try each candidate model in turn — Groq deprecates vision
        // models often, so if one is decommissioned we fall through to the next.
        $models = $vision ? $this->visionModelChain() : [$this->textModel];
        $last   = ['error' => 'Groq API error'];

        foreach ($models as $model) {
            $res = $this->callModel($content, $model, $vision);
            if (! isset($res['error'])) {
                return $res;
            }
            $last = $res;
            if (empty($res['_retry_next_model'])) {
                break; // a non-model error (size, key, etc.) — no point trying others
            }
        }

        unset($last['_retry_next_model']);
        return $last;
    }

    private function callModel(array $content, string $model, bool $vision): array
    {
        try {
            $body = [
                'model'       => $model,
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
                $detail = $this->errorDetail($response->json());
                Log::warning('Groq request failed', ['model' => $model, 'status' => $response->status(), 'body' => $response->body()]);
                return [
                    'error'             => 'Groq API error ' . $response->status() . ($detail ? ': ' . $detail : ''),
                    '_retry_next_model' => $vision && $this->isModelUnavailable($response->status(), $detail),
                ];
            }

            return $this->parseJsonResponse($response->json('choices.0.message.content', ''));
        } catch (\Throwable $e) {
            Log::error('Groq driver error', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /** Candidate vision models, env-configured one first, newest fallbacks after. */
    private function visionModelChain(): array
    {
        return array_values(array_unique(array_filter([
            $this->visionModel,
            'meta-llama/llama-4-maverick-17b-128e-instruct',
            'meta-llama/llama-4-scout-17b-16e-instruct',
        ])));
    }

    private function errorDetail(mixed $body): ?string
    {
        if (is_array($body)) {
            $err = $body['error'] ?? null;
            if (is_array($err)) {
                return $err['message'] ?? ($err['code'] ?? null);
            }
            if (is_string($err) && $err !== '') {
                return $err;
            }
            if (! empty($body['message']) && is_string($body['message'])) {
                return $body['message'];
            }
        }
        return null;
    }

    /** True when a 400/404 means the model itself is gone — so we try the next. */
    private function isModelUnavailable(int $status, ?string $detail): bool
    {
        if (! in_array($status, [400, 404], true) || ! $detail) {
            return false;
        }
        $d = strtolower($detail);
        foreach (['decommission', 'deprecat', 'not found', 'does not exist', 'no longer', 'unknown model', 'model_not_found'] as $needle) {
            if (str_contains($d, $needle)) {
                return true;
            }
        }
        return false;
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
            . "A GST tax invoice always has TWO DIFFERENT parties — keep them strictly separate, never merge or swap them:\n"
            . "• SELLER (a.k.a. supplier / vendor): the party that ISSUED and RAISED this invoice. Find them on the letterhead at the top, in the 'For <company>' line above the signature, next to 'Authorised Signatory', and in the bank / UPI details given for payment — those belong to the SELLER.\n"
            . "• BUYER (a.k.a. recipient / customer): the party the invoice is billed TO — under headings like 'Bill To', 'Billed To', 'Buyer', \"Buyer's Details\", 'Bill To Party', 'Party', 'Customer', or 'Consignee'.\n"
            . "The two are never the same entity. A GSTIN printed next to 'Bill To' / 'Buyer' is the BUYER's, NOT the seller's — do not report it as the seller.\n\n"
            . "Return ONLY JSON with fields: invoice_number, invoice_date (YYYY-MM-DD), due_date (YYYY-MM-DD or null), "
            . "seller_name, seller_gstin, seller_pan, seller_address, seller_email, seller_phone, "
            . "buyer_name, buyer_gstin, buyer_pan, buyer_address, buyer_email, buyer_phone, "
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
