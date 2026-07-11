<?php
namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AiDriverInterface;

class MockDriver implements AiDriverInterface
{
    public function extractInvoice(string $text, ?string $imageBase64 = null): array
    {
        return [
            'invoice_number'  => 'INV-MOCK-001',
            'invoice_date'    => now()->format('Y-m-d'),
            'due_date'        => now()->addDays(30)->format('Y-m-d'),
            // Seller (vendor) issued the bill; buyer is billed to. Kept distinct
            // so the seller/buyer normalization is exercised in mock mode too.
            'seller_name'     => 'Mock Supplier Pvt Ltd',
            'seller_gstin'    => '29AABCT1332L1ZV',
            'seller_address'  => '123 Commercial St, Bengaluru 560001',
            'buyer_name'      => 'Mock Buyer Enterprises',
            'buyer_gstin'     => '27AAECB1234F1Z5',
            'buyer_address'   => '45 Trade Center, Mumbai 400001',
            'lines'           => [
                ['description' => 'Professional Services', 'hsn_sac' => '998313', 'quantity' => 1, 'rate' => 10000.00, 'amount' => 10000.00, 'gst_rate' => 18],
            ],
            'subtotal'        => 10000.00,
            'cgst_amount'     => 900.00,
            'sgst_amount'     => 900.00,
            'igst_amount'     => 0.00,
            'total_amount'    => 11800.00,
            'currency'        => 'INR',
            'payment_terms'   => 'Net 30',
            'confidence'      => 0.95,
            '_mock'           => true,
        ];
    }

    public function parseNlQueryIntent(string $query): array
    {
        return [
            'intent'      => 'sales_total',
            'period'      => 'this_month',
            'date_from'   => now()->startOfMonth()->format('Y-m-d'),
            'date_to'     => now()->format('Y-m-d'),
            'filters'     => [],
            'description' => 'Total sales for the current month (mock response)',
            '_mock'       => true,
        ];
    }

    public function suggestAccount(string $description, float $amount, string $vendor): array
    {
        return [
            'account_name' => 'Office Expenses',
            'account_type' => 'expense',
            'confidence'   => 0.82,
            'reason'       => 'Mock suggestion based on description keywords',
            'alternatives' => ['General Expenses', 'Administrative Expenses'],
            '_mock'        => true,
        ];
    }

    public function detectAnomaly(array $journalData, float $averageAmount): array
    {
        return [
            'is_anomaly'     => false,
            'severity'       => 'low',
            'flags'          => [],
            'recommendation' => 'Transaction appears normal (mock check)',
            'confidence'     => 0.90,
            '_mock'          => true,
        ];
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
