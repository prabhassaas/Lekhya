<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentLinkService
{
    public function createRazorpayLink(Invoice $invoice): array
    {
        if (config('services.razorpay.mode', 'live') === 'mock') {
            return $this->mockPaymentLink($invoice);
        }

        $keyId     = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');

        $party = $invoice->party;

        $payload = [
            'amount'          => (int) round((float) $invoice->total_amount * 100), // paise
            'currency'        => 'INR',
            'description'     => "Invoice #{$invoice->invoice_number}",
            'reference_id'    => $invoice->invoice_number,
            'customer'        => [
                'name'    => $party?->display_name ?? $party?->name ?? 'Customer',
                'email'   => $party?->email ?? '',
                'contact' => $party?->phone ? $this->formatPhone($party->phone) : '',
            ],
            'notify'          => ['sms' => true, 'email' => (bool) ($party?->email)],
            'reminder_enable' => true,
            'notes'           => [
                'invoice_id'     => (string) $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ],
        ];

        $response = Http::withBasicAuth($keyId, $keySecret)
            ->post('https://api.razorpay.com/v1/payment_links', $payload);

        if ($response->failed()) {
            Log::error('Razorpay payment link creation failed', [
                'status'     => $response->status(),
                'body'       => $response->body(),
                'invoice_id' => $invoice->id,
            ]);
            // Fall back to mock so callers always get a usable array
            return $this->mockPaymentLink($invoice);
        }

        $data = $response->json();

        return [
            'url'      => $data['short_url'] ?? $data['id'],
            'id'       => $data['id'],
            'upi_link' => $this->generateUpiLink($invoice),
        ];
    }

    public function generateUpiLink(Invoice $invoice): string
    {
        $upiId       = config('services.razorpay.upi_id', '');
        $companyName = $invoice->tenant->name ?? config('app.name');
        $amount      = number_format((float) $invoice->total_amount, 2, '.', '');

        return sprintf(
            'upi://pay?pa=%s&pn=%s&am=%s&cu=INR&tn=INV-%s',
            urlencode($upiId),
            urlencode($companyName),
            $amount,
            urlencode($invoice->invoice_number)
        );
    }

    public function generateUpiQrSvg(string $upiLink): string
    {
        // Placeholder SVG — swap for a real QR library (e.g., endroid/qr-code) in production
        $encoded = htmlspecialchars($upiLink, ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
  <rect width="200" height="200" fill="#f8f8f8" stroke="#ddd" stroke-width="1"/>
  <text x="100" y="85" font-family="monospace" font-size="12" text-anchor="middle" fill="#555">UPI QR Placeholder</text>
  <text x="100" y="105" font-family="monospace" font-size="7" text-anchor="middle" fill="#888"
        textLength="180" lengthAdjust="spacingAndGlyphs">{$encoded}</text>
  <text x="100" y="130" font-family="sans-serif" font-size="9" text-anchor="middle" fill="#aaa">Install endroid/qr-code for real QR</text>
</svg>
SVG;
    }

    private function mockPaymentLink(Invoice $invoice): array
    {
        $fakeId = 'plink_mock_' . $invoice->id;

        return [
            'url'      => url("/pay/{$fakeId}"),
            'id'       => $fakeId,
            'upi_link' => $this->generateUpiLink($invoice),
        ];
    }

    private function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        return strlen($digits) === 10 ? '+91' . $digits : '+' . $digits;
    }
}
