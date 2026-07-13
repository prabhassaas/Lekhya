<?php

namespace App\Services\Banking;

use App\Models\Invoice;

/**
 * Column layouts for the bulk vendor-payment (NEFT / RTGS / IMPS) upload files
 * that Indian corporate net-banking portals accept. Each format is modelled on
 * the bank's published bulk-upload template — the exact column order can vary
 * slightly by portal version / H2H agreement, so these are a strong starting
 * point the user tweaks once for their account.
 *
 * Rows are built one-per-invoice from pending payables so payments stay bill-wise.
 */
class BankPaymentFormats
{
    /** RTGS is mandatory at/above ₹2,00,000; below that NEFT is used. */
    public const RTGS_FLOOR = 200000;

    public static function all(): array
    {
        return [
            'hdfc' => [
                'name' => 'HDFC Bank', 'short' => 'HDFC', 'brand' => '#004C8F',
                'note' => 'ENet bulk NEFT / RTGS / IMPS',
                'columns' => [
                    ['Beneficiary Name', 'beneficiary'],
                    ['Beneficiary Account Number', 'account'],
                    ['IFSC', 'ifsc'],
                    ['Transaction Type', 'mode'],
                    ['Amount', 'amount'],
                    ['Debit Account No', 'debit_account'],
                    ['Beneficiary Email', 'email'],
                    ['Payment Date', 'date_dmy'],
                    ['Remarks', 'remark'],
                ],
            ],
            'icici' => [
                'name' => 'ICICI Bank', 'short' => 'ICICI', 'brand' => '#B02A30',
                'note' => 'Corporate Internet Banking (CIB) bulk file',
                'columns' => [
                    ['Payment Type', 'mode'],
                    ['Beneficiary Name', 'beneficiary'],
                    ['Beneficiary Account No', 'account'],
                    ['IFSC Code', 'ifsc'],
                    ['Amount', 'amount'],
                    ['Payable Date', 'date_dmy'],
                    ['Beneficiary Email', 'email'],
                    ['Remarks', 'remark'],
                ],
            ],
            'sbi' => [
                'name' => 'State Bank of India', 'short' => 'SBI', 'brand' => '#22409A',
                'note' => 'SBI Corporate (multi-transfer) upload',
                'columns' => [
                    ['Beneficiary Name', 'beneficiary'],
                    ['Beneficiary Account Number', 'account'],
                    ['IFSC Code', 'ifsc'],
                    ['Amount', 'amount'],
                    ['Payment Mode', 'mode'],
                    ['Mobile Number', 'phone'],
                    ['Remarks', 'remark'],
                ],
            ],
            'axis' => [
                'name' => 'Axis Bank', 'short' => 'AXIS', 'brand' => '#97144D',
                'note' => 'Corporate Internet Banking bulk upload',
                'columns' => [
                    ['Beneficiary Name', 'beneficiary'],
                    ['Account Number', 'account'],
                    ['IFSC Code', 'ifsc'],
                    ['Amount', 'amount'],
                    ['Pay Mode', 'mode'],
                    ['Email', 'email'],
                    ['Narration', 'remark'],
                ],
            ],
            'kotak' => [
                'name' => 'Kotak Mahindra Bank', 'short' => 'KOTAK', 'brand' => '#ED1C24',
                'note' => 'Kotak bulk payment file',
                'columns' => [
                    ['Beneficiary Name', 'beneficiary'],
                    ['Beneficiary Account', 'account'],
                    ['IFSC', 'ifsc'],
                    ['Amount', 'amount'],
                    ['Mode', 'mode'],
                    ['Debit Account', 'debit_account'],
                    ['Remarks', 'remark'],
                ],
            ],
            'yes' => [
                'name' => 'YES Bank', 'short' => 'YES', 'brand' => '#0056A4',
                'note' => 'YES corporate bulk NEFT/RTGS',
                'columns' => [
                    ['Transaction Type', 'mode'],
                    ['Beneficiary Name', 'beneficiary'],
                    ['Account Number', 'account'],
                    ['IFSC', 'ifsc'],
                    ['Amount', 'amount'],
                    ['Email', 'email'],
                    ['Remarks', 'remark'],
                ],
            ],
            'pnb' => [
                'name' => 'Punjab National Bank', 'short' => 'PNB', 'brand' => '#5C2D91',
                'note' => 'PNB corporate bulk payment',
                'columns' => [
                    ['Beneficiary Name', 'beneficiary'],
                    ['Account Number', 'account'],
                    ['IFSC Code', 'ifsc'],
                    ['Amount', 'amount'],
                    ['Payment Mode', 'mode'],
                    ['Remarks', 'remark'],
                ],
            ],
            'bob' => [
                'name' => 'Bank of Baroda', 'short' => 'BoB', 'brand' => '#EF5B25',
                'note' => 'Baroda Connect bulk upload',
                'columns' => [
                    ['Beneficiary Name', 'beneficiary'],
                    ['Beneficiary Account Number', 'account'],
                    ['IFSC', 'ifsc'],
                    ['Amount', 'amount'],
                    ['Transaction Type', 'mode'],
                    ['Email', 'email'],
                    ['Remarks', 'remark'],
                ],
            ],
            'generic' => [
                'name' => 'Universal (NEFT/RTGS)', 'short' => '₹', 'brand' => '#1B2A4A',
                'note' => 'Works with most portals — all common fields',
                'columns' => [
                    ['Beneficiary Name', 'beneficiary'],
                    ['Account Number', 'account'],
                    ['IFSC', 'ifsc'],
                    ['Bank Name', 'bank_name'],
                    ['Amount', 'amount'],
                    ['Payment Mode', 'mode'],
                    ['Email', 'email'],
                    ['Mobile', 'phone'],
                    ['Invoice No', 'txn_ref'],
                    ['Payment Date', 'date'],
                    ['Remarks', 'remark'],
                ],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function headers(array $format): array
    {
        return array_map(fn ($c) => $c[0], $format['columns']);
    }

    /** One CSV row for one payable invoice, ordered to the format's columns. */
    public static function row(array $format, Invoice $inv, array $ctx = []): array
    {
        return array_map(fn ($c) => self::value($c[1], $inv, $ctx), $format['columns']);
    }

    public static function mode(Invoice $inv): string
    {
        return (float) $inv->balance_amount >= self::RTGS_FLOOR ? 'RTGS' : 'NEFT';
    }

    private static function value(string $token, Invoice $i, array $c): string
    {
        $p = $i->party;

        return match ($token) {
            'beneficiary'    => (string) ($p?->bank_account_holder ?: ($p?->name ?? '')),
            'account'        => (string) ($p?->bank_account_number ?? ''),
            'ifsc'           => (string) ($p?->bank_ifsc ?? ''),
            'bank_name'      => (string) ($p?->bank_name ?? ''),
            'upi'            => (string) ($p?->upi_id ?? ''),
            'amount'         => number_format((float) $i->balance_amount, 2, '.', ''),
            'mode'           => self::mode($i),
            'email'          => (string) ($p?->email ?? ''),
            'phone'          => (string) ($p?->phone ?? ''),
            'remark'         => (string) ($i->invoice_number ?: ('Bill ' . $i->id)),
            'txn_ref'        => (string) ($i->invoice_number ?? ''),
            'date'           => now()->format('Y-m-d'),
            'date_dmy'       => now()->format('d/m/Y'),
            'debit_account'  => (string) ($c['debit_account'] ?? ''),
            'debit_ifsc'     => (string) ($c['debit_ifsc'] ?? ''),
            default          => '',
        };
    }
}
