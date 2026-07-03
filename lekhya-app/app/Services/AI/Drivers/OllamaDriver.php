<?php
namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaDriver implements AiDriverInterface
{
    private string $endpoint;
    private string $model;
    private int $maxTokens;
    private float $temperature;
    private bool $useVision;

    public function __construct()
    {
        $this->endpoint    = rtrim(config('services.ai.endpoint', 'http://localhost:11434'), '/');
        $this->model       = config('services.ai.model', 'llama3.2');
        $this->maxTokens   = (int) config('services.ai.max_tokens', 2048);
        $this->temperature = (float) config('services.ai.temperature', 0.1);
        $this->useVision   = (bool) config('services.ai.use_vision', false);
    }

    public function extractInvoice(string $text, ?string $imageBase64 = null): array
    {
        $prompt = $this->invoiceExtractionPrompt($text);

        $payload = [
            'model'  => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => $this->temperature,
                'num_predict' => $this->maxTokens,
            ],
        ];

        // If vision model and image provided, attach image
        if ($imageBase64 && $this->useVision) {
            $payload['images'] = [$imageBase64];
        }

        $raw = $this->generate($payload);
        return $this->parseJsonResponse($raw, 'extraction');
    }

    public function parseNlQueryIntent(string $query): array
    {
        $prompt = $this->nlQueryPrompt($query);
        $raw    = $this->generate(['model' => $this->model, 'prompt' => $prompt, 'stream' => false,
                                   'options' => ['temperature' => 0.0, 'num_predict' => 512]]);
        return $this->parseJsonResponse($raw, 'nl_query');
    }

    public function suggestAccount(string $description, float $amount, string $vendor): array
    {
        $prompt = $this->accountCodingPrompt($description, $amount, $vendor);
        $raw    = $this->generate(['model' => $this->model, 'prompt' => $prompt, 'stream' => false,
                                   'options' => ['temperature' => 0.05, 'num_predict' => 512]]);
        return $this->parseJsonResponse($raw, 'account_coding');
    }

    public function detectAnomaly(array $journalData, float $averageAmount): array
    {
        $prompt = $this->anomalyPrompt($journalData, $averageAmount);
        $raw    = $this->generate(['model' => $this->model, 'prompt' => $prompt, 'stream' => false,
                                   'options' => ['temperature' => 0.0, 'num_predict' => 512]]);
        return $this->parseJsonResponse($raw, 'anomaly');
    }

    public function isAvailable(): bool
    {
        try {
            $base = str_replace('/api/generate', '', $this->endpoint);
            $response = Http::timeout(3)->get("{$base}/api/tags");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function generate(array $payload): ?string
    {
        try {
            // Use /api/generate endpoint
            $url = str_contains($this->endpoint, '/api/') ? $this->endpoint : $this->endpoint . '/api/generate';
            $response = Http::timeout(120)
                ->withOptions(['verify' => false])
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['response'] ?? null;
            }

            Log::warning('Ollama request failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Ollama connection error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function parseJsonResponse(?string $raw, string $context): array
    {
        if (!$raw) {
            return ['error' => 'No response from AI model', 'context' => $context];
        }

        // Extract JSON from response (model may wrap it in markdown code fences)
        if (preg_match('/```(?:json)?\s*([\s\S]+?)\s*```/', $raw, $m)) {
            $raw = $m[1];
        }

        // Find first { and last }
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start !== false && $end !== false) {
            $raw = substr($raw, $start, $end - $start + 1);
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Ollama JSON parse failed', ['raw' => $raw, 'context' => $context]);
            return ['error' => 'Could not parse AI response as JSON', 'raw' => substr($raw, 0, 200)];
        }

        return $decoded;
    }

    // Prompts

    private function invoiceExtractionPrompt(string $text): string
    {
        return <<<PROMPT
You are an Indian accounting AI. Extract structured invoice data from the text below.
Return ONLY a valid JSON object — no explanation, no markdown, just the JSON.

Required JSON structure:
{
  "invoice_number": "string",
  "invoice_date": "YYYY-MM-DD",
  "due_date": "YYYY-MM-DD or null",
  "party_name": "string",
  "party_gstin": "string or null (15-char GSTIN)",
  "party_address": "string or null",
  "lines": [
    {
      "description": "string",
      "hsn_sac": "string",
      "quantity": number,
      "rate": number,
      "amount": number,
      "gst_rate": number
    }
  ],
  "subtotal": number,
  "cgst_amount": number,
  "sgst_amount": number,
  "igst_amount": number,
  "total_amount": number,
  "currency": "INR",
  "payment_terms": "string or null",
  "confidence": number between 0 and 1
}

Invoice text:
{$text}
PROMPT;
    }

    private function nlQueryPrompt(string $query): string
    {
        return <<<PROMPT
You are an Indian accounting AI assistant. Parse the user's question and return a JSON intent object.
Return ONLY valid JSON — no explanation.

Valid intents: sales_total, expense_total, outstanding_ar, outstanding_ap, profit_loss, gst_liability, bank_balance, invoice_count, top_customers, top_vendors

Valid periods: today, this_week, this_month, last_month, this_quarter, last_quarter, this_year, last_year, custom

JSON structure:
{
  "intent": "one of the valid intents above",
  "period": "one of the valid periods above",
  "date_from": "YYYY-MM-DD or null",
  "date_to": "YYYY-MM-DD or null",
  "filters": {},
  "description": "human-readable description of what you computed"
}

User question: {$query}
PROMPT;
    }

    private function accountCodingPrompt(string $description, float $amount, string $vendor): string
    {
        $amt = number_format($amount, 2);
        return <<<PROMPT
You are an Indian accounting AI familiar with standard Indian Chart of Accounts (CoA) under Companies Act 2013.
Suggest the correct ledger account for this transaction.
Return ONLY valid JSON — no explanation.

JSON structure:
{
  "account_name": "exact account name from Indian CoA",
  "account_type": "expense or income or asset or liability",
  "confidence": number between 0 and 1,
  "reason": "one-line reason",
  "alternatives": ["up to 2 alternative account names"]
}

Transaction:
- Description: {$description}
- Amount: Rs {$amt}
- Vendor/Party: {$vendor}
PROMPT;
    }

    private function anomalyPrompt(array $journalData, float $averageAmount): string
    {
        $avg    = number_format($averageAmount, 2);
        $amount = number_format($journalData['amount'] ?? 0, 2);
        $narration = $journalData['narration'] ?? '';
        $party  = $journalData['party'] ?? '';
        $account = $journalData['account'] ?? '';
        $date   = $journalData['date'] ?? '';
        $time   = $journalData['time'] ?? '';
        $user   = $journalData['user'] ?? '';

        return <<<PROMPT
You are an Indian accounting fraud detection AI. Analyze this journal entry for anomalies.
Return ONLY valid JSON — no explanation.

JSON structure:
{
  "is_anomaly": boolean,
  "severity": "low or medium or high or critical",
  "flags": ["list of specific concerns, empty array if none"],
  "recommendation": "one-line recommendation",
  "confidence": number between 0 and 1
}

Journal entry:
- Date: {$date}
- Time: {$time}
- Amount: Rs {$amount}
- Average for this account: Rs {$avg}
- Narration: {$narration}
- Party: {$party}
- Account: {$account}
- Posted by: {$user}
PROMPT;
    }
}
