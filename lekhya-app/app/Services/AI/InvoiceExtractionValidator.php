<?php
namespace App\Services\AI;

/**
 * The "math gate" behind reliable OCR. Takes a raw AI extraction and returns
 * per-field trust flags plus hard arithmetic/format checks, so the review UI
 * can mark fields green (trust) or amber (confirm). This is what turns
 * imperfect OCR into 100%-correct *postings* — a human only checks the ambers.
 */
class InvoiceExtractionValidator
{
    private const CONFIDENCE_THRESHOLD = 0.80;
    private const MONEY_TOLERANCE      = 1.00; // ₹1 rounding slack
    private const GSTIN_REGEX          = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/';

    /** @return array{fields: array, checks: array, needs_review: bool, amber_count: int} */
    public function validate(array $ex): array
    {
        $fieldConf = $ex['field_confidence'] ?? [];
        $overall   = (float) ($ex['confidence'] ?? 0);

        $required = ['invoice_number', 'invoice_date', 'party_name', 'total_amount'];
        $tracked  = ['invoice_number', 'invoice_date', 'party_name', 'party_gstin', 'subtotal', 'total_amount'];

        $fields = [];
        foreach ($tracked as $name) {
            $value    = $ex[$name] ?? null;
            $conf     = isset($fieldConf[$name]) ? (float) $fieldConf[$name] : $overall;
            $missing  = in_array($name, $required, true) && blank($value);
            $lowConf  = $conf < self::CONFIDENCE_THRESHOLD;

            $fields[$name] = [
                'value'      => $value,
                'confidence' => round($conf, 2),
                'status'     => ($missing || $lowConf) ? 'amber' : 'green',
                'reason'     => $missing ? 'Missing — please fill' : ($lowConf ? 'Low confidence — confirm' : null),
            ];
        }

        $checks = $this->hardChecks($ex);

        // Any failed hard check pushes its field(s) to amber.
        foreach ($checks as $c) {
            if (! $c['ok']) {
                foreach ($c['fields'] as $f) {
                    if (isset($fields[$f])) {
                        $fields[$f]['status'] = 'amber';
                        $fields[$f]['reason'] = $c['message'];
                    }
                }
            }
        }

        $amber = collect($fields)->where('status', 'amber')->count();

        return [
            'fields'       => $fields,
            'checks'       => $checks,
            'amber_count'  => $amber,
            'needs_review' => $amber > 0 || collect($checks)->contains(fn($c) => ! $c['ok']),
        ];
    }

    /** @return array<int, array{key:string, label:string, ok:bool, message:string, fields:array}> */
    private function hardChecks(array $ex): array
    {
        $subtotal = (float) ($ex['subtotal'] ?? 0);
        $cgst     = (float) ($ex['cgst_amount'] ?? 0);
        $sgst     = (float) ($ex['sgst_amount'] ?? 0);
        $igst     = (float) ($ex['igst_amount'] ?? 0);
        $total    = (float) ($ex['total_amount'] ?? 0);
        $tax      = $cgst + $sgst + $igst;
        $gstin    = $ex['party_gstin'] ?? null;

        $checks = [];

        // 1. taxable + tax = total
        $expected = $subtotal + $tax;
        $checks[] = [
            'key'     => 'totals',
            'label'   => 'Taxable + GST = Total',
            'ok'      => abs($expected - $total) <= self::MONEY_TOLERANCE,
            'message' => 'Totals don\'t reconcile (₹' . number_format($expected, 2) . ' vs ₹' . number_format($total, 2) . ')',
            'fields'  => ['subtotal', 'total_amount'],
        ];

        // 2. GSTIN format (only if one was read)
        if (filled($gstin)) {
            $checks[] = [
                'key'     => 'gstin',
                'label'   => 'GSTIN format valid',
                'ok'      => (bool) preg_match(self::GSTIN_REGEX, strtoupper((string) $gstin)),
                'message' => 'GSTIN format looks invalid',
                'fields'  => ['party_gstin'],
            ];
        }

        // 3. Can't have both intra-state (CGST/SGST) and inter-state (IGST) tax
        if ($igst > 0 && ($cgst > 0 || $sgst > 0)) {
            $checks[] = [
                'key'     => 'tax_mix',
                'label'   => 'Tax type consistent',
                'ok'      => false,
                'message' => 'Both IGST and CGST/SGST present — only one applies',
                'fields'  => ['subtotal'],
            ];
        }

        // 4. CGST should equal SGST on intra-state invoices
        if ($cgst > 0 || $sgst > 0) {
            $checks[] = [
                'key'     => 'cgst_sgst',
                'label'   => 'CGST = SGST',
                'ok'      => abs($cgst - $sgst) <= self::MONEY_TOLERANCE,
                'message' => 'CGST and SGST differ — they should match',
                'fields'  => ['subtotal'],
            ];
        }

        return $checks;
    }
}
