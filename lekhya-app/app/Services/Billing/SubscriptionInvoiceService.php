<?php
namespace App\Services\Billing;

use App\Mail\SubscriptionInvoiceMail;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

/**
 * Builds and emails the Prabhas SaaS GST tax invoice for a subscription
 * payment. The future Razorpay webhook calls issueAndSend(); the settings
 * screen calls sendSample() so admins can verify email delivery + format.
 */
class SubscriptionInvoiceService
{
    /** Real flow: persist an invoice for a paid subscription and email it. */
    public function issueAndSend(Subscription $subscription, array $payment = []): SubscriptionInvoice
    {
        $tenant = $subscription->tenant;
        $plan   = $subscription->plan;
        $amount = (float) ($payment['amount'] ?? $subscription->amount);

        $gstRate = (float) config('prabhas.gst_rate');
        $gst     = round($amount * $gstRate / 100, 2);

        $invoice = SubscriptionInvoice::create([
            'tenant_id'           => $tenant->id,
            'subscription_id'     => $subscription->id,
            'invoice_number'      => $this->nextInvoiceNumber(),
            'invoice_date'        => now()->toDateString(),
            'amount'              => $amount,
            'gst_amount'          => $gst,
            'total_amount'        => round($amount + $gst, 2),
            'status'              => 'sent',
            'razorpay_payment_id' => $payment['payment_id'] ?? null,
            'razorpay_order_id'   => $payment['order_id'] ?? null,
            'paid_at'             => now(),
        ]);

        $data = $this->buildData($tenant, $plan, [
            'invoice_number' => $invoice->invoice_number,
            'amount'         => $amount,
            'payment_id'     => $invoice->razorpay_payment_id,
            'cycle'          => $subscription->billing_cycle,
        ]);

        $to = $tenant->email ?: $tenant->users()->first()?->email;
        if ($to) {
            $this->dispatchMail($data, $to);
        }

        return $invoice;
    }

    /** Test flow: send a sample invoice to an address without persisting. */
    public function sendSample(Tenant $tenant, string $toEmail): void
    {
        $plan   = Plan::where('is_active', true)->orderBy('monthly_price')->first();
        $amount = (float) ($plan->monthly_price ?? 499);

        $data = $this->buildData($tenant, $plan, [
            'invoice_number' => 'PS/INV/SAMPLE-' . now()->format('ymd-His'),
            'amount'         => $amount,
            'payment_id'     => 'pay_SAMPLE0000000',
            'cycle'          => 'monthly',
            'sample'         => true,
        ]);

        $this->dispatchMail($data, $toEmail);
    }

    private function dispatchMail(array $data, string $toEmail): void
    {
        $pdf = Pdf::loadView('pdf.subscription-invoice', ['d' => $data])->setPaper('A4')->output();
        Mail::to($toEmail)->send(new SubscriptionInvoiceMail($data, $pdf));
    }

    private function buildData(Tenant $tenant, ?Plan $plan, array $o): array
    {
        $amount  = (float) ($o['amount'] ?? 0);
        $gstRate = (float) config('prabhas.gst_rate');
        $gst     = round($amount * $gstRate / 100, 2);

        // Intra-state (CGST+SGST) if seller & buyer share a state code, else IGST.
        $sellerState = (string) config('prabhas.state_code');
        $buyerState  = (string) ($tenant->state_code ?? '');
        $interstate  = $sellerState === '' || $buyerState === '' || $sellerState !== $buyerState;

        return [
            'sample'         => $o['sample'] ?? false,
            'seller'         => config('prabhas'),
            'buyer'          => [
                'name'      => $tenant->name,
                'gstin'     => $tenant->gstin,
                'address'   => trim(implode(', ', array_filter([$tenant->city ?? null, $tenant->state ?? null]))) ?: '—',
                'client_id' => $tenant->ulid ?? ('LEK-' . str_pad((string) $tenant->id, 5, '0', STR_PAD_LEFT)),
            ],
            'invoice_number' => $o['invoice_number'],
            'invoice_date'   => now()->format('d M Y'),
            'plan_name'      => $plan?->name ?? 'Lekhya Subscription',
            'cycle'          => ucfirst($o['cycle'] ?? 'monthly'),
            'payment_id'     => $o['payment_id'] ?? null,
            'amount'         => $amount,
            'gst_rate'       => $gstRate,
            'interstate'     => $interstate,
            'cgst'           => $interstate ? 0 : round($gst / 2, 2),
            'sgst'           => $interstate ? 0 : round($gst / 2, 2),
            'igst'           => $interstate ? $gst : 0,
            'gst_total'      => $gst,
            'total'          => round($amount + $gst, 2),
        ];
    }

    private function nextInvoiceNumber(): string
    {
        $fy = now()->month >= 4 ? now()->year : now()->year - 1;
        $seq = SubscriptionInvoice::whereYear('created_at', now()->year)->count() + 1;
        return sprintf('PS/%d-%02d/%05d', $fy, ($fy + 1) % 100, $seq);
    }
}
