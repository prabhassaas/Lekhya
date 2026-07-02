<?php

namespace App\Services\Notification;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private bool $enabled;
    private ?string $token;
    private ?string $phoneNumberId;

    public function __construct()
    {
        $this->enabled       = (bool) config('services.whatsapp.enabled', false);
        $this->token         = config('services.whatsapp.token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }

    public function sendInvoiceReady(Invoice $invoice): bool
    {
        $party = $invoice->party;
        if (! $party?->phone) {
            Log::warning('WhatsApp: party has no phone', ['invoice_id' => $invoice->id]);
            return false;
        }

        $companyName = $invoice->tenant->name ?? config('app.name');

        return $this->sendTemplate($party->phone, 'invoice_payment_request', [
            $companyName,
            $invoice->invoice_number,
            '₹' . number_format((float) $invoice->total_amount, 2),
            $invoice->due_date?->format('d M Y') ?? 'N/A',
        ]);
    }

    public function sendPaymentConfirmation(Invoice $invoice): bool
    {
        $party = $invoice->party;
        if (! $party?->phone) {
            return false;
        }

        $companyName = $invoice->tenant->name ?? config('app.name');

        return $this->sendTemplate($party->phone, 'payment_received', [
            $companyName,
            $invoice->invoice_number,
            '₹' . number_format((float) $invoice->paid_amount, 2),
        ]);
    }

    public function sendPaymentLink(Invoice $invoice, string $paymentUrl): bool
    {
        $party = $invoice->party;
        if (! $party?->phone) {
            return false;
        }

        return $this->sendTemplate($party->phone, 'payment_link_share', [
            $party->display_name ?? $party->name,
            $invoice->invoice_number,
            '₹' . number_format((float) $invoice->total_amount, 2),
            $paymentUrl,
        ]);
    }

    private function sendTemplate(string $to, string $templateName, array $params): bool
    {
        $phone = $this->formatPhone($to);

        $body = [
            'messaging_product' => 'whatsapp',
            'to'                => $phone,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => 'en'],
                'components' => [
                    [
                        'type'       => 'body',
                        'parameters' => array_map(
                            fn($p) => ['type' => 'text', 'text' => (string) $p],
                            $params
                        ),
                    ],
                ],
            ],
        ];

        if (! $this->enabled) {
            Log::info('WhatsApp (mock): would send template', [
                'to'       => $phone,
                'template' => $templateName,
                'params'   => $params,
            ]);
            return true;
        }

        try {
            $response = Http::withToken($this->token)
                ->post("https://graph.facebook.com/v18.0/{$this->phoneNumberId}/messages", $body);

            if ($response->failed()) {
                Log::error('WhatsApp API error', [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'template' => $templateName,
                    'to'       => $phone,
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp send exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        // 10-digit Indian mobile → prepend country code 91
        if (strlen($digits) === 10) {
            $digits = '91' . $digits;
        }

        return $digits;
    }
}
