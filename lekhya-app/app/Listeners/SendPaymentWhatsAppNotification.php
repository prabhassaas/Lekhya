<?php

namespace App\Listeners;

use App\Events\InvoicePaymentCollected;
use App\Services\Notification\WhatsAppService;
use App\Services\Payment\PaymentLinkService;
use Illuminate\Support\Facades\Log;

class SendPaymentWhatsAppNotification
{
    public function __construct(
        private WhatsAppService $whatsApp,
        private PaymentLinkService $paymentLink,
    ) {}

    public function handle(InvoicePaymentCollected $event): void
    {
        $invoice = $event->invoice;

        try {
            // Fully paid — send confirmation only, no new payment link needed
            if ((float) $invoice->balance_amount <= 0) {
                $this->whatsApp->sendPaymentConfirmation($invoice);
                return;
            }

            // Partially paid or fresh invoice — generate a payment link and share it
            $link = $this->paymentLink->createRazorpayLink($invoice);
            $this->whatsApp->sendPaymentLink($invoice, $link['url']);
        } catch (\Throwable $e) {
            // Never throw from a listener — log and swallow
            Log::error('SendPaymentWhatsAppNotification failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
