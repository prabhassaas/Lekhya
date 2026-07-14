<?php
namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Primary Lekhya AI engine — OpenAI-compatible, fast. Handles text (chat/intent)
 * and vision (invoice OCR) through one API. The key + models are injected
 * per-tenant from ai_settings; falls back to env config for CLI/queue use.
 */
class LekhyaAiDriver implements AiDriverInterface
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
        $this->apiKey      = ($config['api_key']      ?? null) ?: (string) config('services.ai.primary_key', '');
        $this->textModel   = ($config['text_model']   ?? null) ?: config('services.ai.text_model', 'llama-3.3-70b-versatile');
        $this->visionModel = ($config['vision_model'] ?? null) ?: config('services.ai.vision_model', 'meta-llama/llama-4-scout-17b-16e-instruct');
        // Big enough that a full invoice's JSON (lines + field_confidence) isn't
        // truncated — a cut-off body triggers the engine's "failed to generate JSON" 400.
        $this->maxTokens   = (int) config('services.ai.max_tokens', 4096);
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

    /** Plain conversational completion (no JSON mode) — powers the in-app assistant. */
    public function chat(string $system, string $user): string
    {
        // The configured text model can be silently decommissioned by the provider,
        // which 400s every call. Fall through a chain that ends in the vision models
        // — those are known-good in this deployment (invoice scanning uses them) and
        // also serve plain-text chat — so the assistant keeps working regardless.
        $lastStatus = null;
        $lastBody   = null;

        foreach ($this->chatModelChain() as $model) {
            try {
                $response = Http::timeout(45)->withToken($this->apiKey)->acceptJson()->post(self::ENDPOINT, [
                    'model'       => $model,
                    'messages'    => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'max_tokens'  => 600,
                    'temperature' => 0.3,
                ]);

                if ($response->successful()) {
                    $answer = trim((string) $response->json('choices.0.message.content', ''));
                    if ($answer !== '') {
                        return $answer;
                    }
                } else {
                    $lastStatus = $response->status();
                    $lastBody   = substr($response->body(), 0, 300);
                }
            } catch (\Throwable $e) {
                Log::error('AI chat error', ['model' => $model, 'error' => $e->getMessage()]);
            }
        }

        Log::warning('AI chat failed on all models', ['models' => $this->chatModelChain(), 'last_status' => $lastStatus, 'last_body' => $lastBody]);
        return '';
    }

    /** Chat model candidates: configured text model first, then the multimodal models
     *  (scanning proves they are live here) as insurance against a stale text model. */
    private function chatModelChain(): array
    {
        return array_values(array_unique(array_filter([
            $this->textModel,
            'llama-3.3-70b-versatile',
            $this->visionModel,
            'meta-llama/llama-4-maverick-17b-128e-instruct',
            'meta-llama/llama-4-scout-17b-16e-instruct',
        ])));
    }

    private function call(array $content, bool $vision = false): array
    {
        // For vision, try each candidate model in turn — the provider deprecates vision
        // models often, so if one is decommissioned we fall through to the next.
        $models = $vision ? $this->visionModelChain() : [$this->textModel];
        $last   = ['error' => 'AI engine error'];

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

    private function callModel(array $content, string $model, bool $vision, bool $jsonMode = true): array
    {
        try {
            $body = [
                'model'       => $model,
                'messages'    => [['role' => 'user', 'content' => $content]],
                'max_tokens'  => $this->maxTokens,
                'temperature' => (float) config('services.ai.temperature', 0.1),
            ];
            if (! $vision && $jsonMode) {
                // JSON mode keeps text responses strictly parseable.
                $body['response_format'] = ['type' => 'json_object'];
            }

            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post(self::ENDPOINT, $body);

            if ($response->successful()) {
                return $this->parseJsonResponse($response->json('choices.0.message.content', ''));
            }

            $json = $response->json();

            // The engine's JSON mode is strict: it 400s with json_validate_failed and
            // tucks the model's actual output into error.failed_generation, which
            // is usually valid JSON. Salvage that before giving up.
            $failed = data_get($json, 'error.failed_generation');
            if (is_string($failed) && trim($failed) !== '') {
                $parsed = $this->parseJsonResponse($failed);
                if (! isset($parsed['error'])) {
                    return $parsed;
                }
            }

            // Some models reject strict JSON mode outright — retry once in plain
            // mode and pull the JSON out of the text ourselves.
            if (! $vision && $jsonMode) {
                return $this->callModel($content, $model, $vision, jsonMode: false);
            }

            $detail = $this->errorDetail($json);
            Log::warning('AI request failed', ['model' => $model, 'status' => $response->status(), 'body' => $response->body()]);
            return [
                'error'             => 'AI engine error ' . $response->status() . ($detail ? ': ' . $detail : ''),
                '_retry_next_model' => $vision && $this->isModelUnavailable($response->status(), $detail),
            ];
        } catch (\Throwable $e) {
            Log::error('AI driver error', ['error' => $e->getMessage()]);
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
            . "LINE ITEMS — read the item table ROW BY ROW and keep each row's values on the same line. These tax fields are critical, so read them carefully:\n"
            . "• hsn_sac: the HSN code (goods) or SAC code (services) for that row — a 4, 6, or 8-digit number, usually in an 'HSN/SAC' or 'HSN' column. If a number is printed for the row, capture it; only use null when nothing is printed.\n"
            . "• gst_rate: that row's GST percentage (typically 0, 5, 12, 18 or 28). If the bill shows CGST% and SGST% in separate columns, gst_rate is their SUM (e.g. 9% + 9% = 18). If only a combined rate or IGST% is shown, use that.\n"
            . "• Also capture quantity, unit, rate (price per unit), discount_percent, and amount (the row's taxable value).\n"
            . "• CAPTURE EVERY ROW — never stop early or summarise. If a bill has 25 line rows, return 25 line objects. Long bills (courier/delivery, e-commerce, freight) list many rows — include them all.\n"
            . "• meta: put any OTHER printed per-row attributes here as a small object — e.g. {\"dimension\":\"12x8x2 cm\",\"weight\":\"1.2 kg\",\"origin_country\":\"India\",\"order_id\":\"...\",\"awb\":\"...\",\"service\":\"Surface\",\"batch\":\"...\"}. Never drop a column just because there is no dedicated field for it — park it in meta.\n"
            . "Never guess an HSN or a rate — but never leave one blank if it is legibly printed.\n\n"
            . "GST INCLUSIVE OR EXCLUSIVE — decide carefully and set gst_inclusive (true/false): true when the line rate/amount ALREADY contains GST (look for 'inclusive of GST', 'incl. tax', 'MRP', or when the line amounts add up to the grand total with no separate tax added); false when GST is added on top of the taxable value (a separate CGST/SGST/IGST amount is shown). When unsure, use false.\n\n"
            . "SELLER TYPE — set seller_type to one of: 'vendor' or 'supplier' (they mainly supply GOODS), or 'service_provider' (they supply SERVICES — e.g. courier/delivery, freight, consulting, professional/legal/audit fees, commission, rent, software, job-work). Base it on the SAC vs HSN codes and the line descriptions. When services dominate, use 'service_provider'.\n\n"
            . "SELLER BANK DETAILS — invoices usually print the supplier's bank for payment (look for 'Bank Details', 'Bank Name', 'A/c No', 'Account Number', 'IFSC', 'IFS Code', 'Beneficiary Name', or a UPI id / UPI QR). These are the SELLER's collection account — capture them as seller_bank_*. Never use the buyer's bank.\n\n"
            . "ANY FORMAT — bills come in every layout (tax invoice, courier manifest, e-commerce, hand-made, thermal receipt). Adapt: find the same meaning under different labels and extract completely. If a value is genuinely absent, use null (or 0 for a numeric that the bill implies is zero) — never fail, never invent.\n\n"
            . "Return ONLY JSON with fields: invoice_number, invoice_date (YYYY-MM-DD), due_date (YYYY-MM-DD or null), "
            . "seller_name, seller_gstin, seller_pan, seller_address, seller_email, seller_phone, seller_type, "
            . "seller_bank_name, seller_bank_account, seller_bank_ifsc, seller_account_holder, seller_upi, "
            . "buyer_name, buyer_gstin, buyer_pan, buyer_address, buyer_email, buyer_phone, "
            . "lines (array of {description, hsn_sac, quantity, unit, rate, discount_percent, amount, gst_rate, meta}), "
            . "subtotal, cgst_amount, sgst_amount, igst_amount, cess_amount, round_off, total_amount, currency, gst_inclusive (true/false), "
            . "place_of_supply, reverse_charge (true/false), tds_rate, payment_terms, notes, "
            . "confidence (0-1 overall), field_confidence (object mapping each top-level field name to 0-1). "
            . "Example line: {\"description\":\"Cotton yarn\",\"hsn_sac\":\"5205\",\"quantity\":10,\"unit\":\"kg\",\"rate\":250,\"discount_percent\":0,\"amount\":2500,\"gst_rate\":5,\"meta\":{\"dimension\":null}}. "
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
