<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\Party;
use App\Models\TallyImport;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

/**
 * Tally ERP → Lekhya migration service.
 *
 * Steps:
 *  1. Export from Tally: Data → Export → XML → All Masters + All Vouchers
 *  2. Upload the XML via the migration wizard UI
 *  3. This service parses, maps, validates, and imports in a transaction
 *
 * Tally XML structure parsed here: Tally ERP 9 / Tally Prime export format.
 */
class TallyMigrationService
{
    public function __construct(private JournalEngine $journal) {}

    public function parseAndPreview(TallyImport $import): array
    {
        $xml = $this->loadXml(Storage::disk('local')->path($import->file_path));

        $summary = [
            'ledgers'  => 0,
            'groups'   => 0,
            'parties'  => 0,
            'vouchers' => 0,
            'errors'   => [],
        ];

        // Count masters
        $summary['groups']  = count($xml->xpath('//GROUP'));
        $summary['ledgers'] = count($xml->xpath('//LEDGER'));
        $summary['vouchers'] = count($xml->xpath('//VOUCHER'));

        // Check for common issues
        foreach ($xml->xpath('//LEDGER') as $ledger) {
            $name = (string) $ledger['NAME'];
            if (empty($name)) {
                $summary['errors'][] = "Ledger with empty name found";
            }
        }

        $import->update([
            'status'        => 'review',
            'summary'       => $summary,
            'total_records' => $summary['ledgers'] + $summary['vouchers'],
        ]);

        return $summary;
    }

    public function import(TallyImport $import, int $tenantId, int $userId): array
    {
        $xml = $this->loadXml(Storage::disk('local')->path($import->file_path));

        $import->update(['status' => 'importing', 'started_at' => now()]);

        $imported = 0;
        $failed   = 0;
        $errors   = [];

        return DB::transaction(function () use ($xml, $tenantId, $userId, $import, &$imported, &$failed, &$errors) {
            // 1. Import Groups (account hierarchy)
            $this->importGroups($xml, $tenantId, $errors);

            // 2. Import Ledgers (accounts & parties)
            [$accountMap, $partyIds] = $this->importLedgers($xml, $tenantId, $errors, $imported);

            // 3. Import Vouchers (journals)
            $this->importVouchers($xml, $tenantId, $userId, $accountMap, $errors, $imported, $failed);

            $import->update([
                'status'           => $failed === 0 ? 'completed' : 'completed',
                'imported_records' => $imported,
                'failed_records'   => $failed,
                'errors'           => $errors,
                'completed_at'     => now(),
            ]);

            return [
                'imported' => $imported,
                'failed'   => $failed,
                'errors'   => array_slice($errors, 0, 50),
            ];
        });
    }

    // ── Group hierarchy ───────────────────────────────────────────────────

    private function importGroups(SimpleXMLElement $xml, int $tenantId, array &$errors): void
    {
        $tallyToLekhyaGroup = [
            'Capital Account'       => ['type' => 'equity',      'sub_type' => 'equity'],
            'Reserves & Surplus'    => ['type' => 'equity',      'sub_type' => 'equity'],
            'Current Assets'        => ['type' => 'asset',       'sub_type' => 'current_asset'],
            'Fixed Assets'          => ['type' => 'asset',       'sub_type' => 'fixed_asset'],
            'Current Liabilities'   => ['type' => 'liability',   'sub_type' => 'current_liability'],
            'Loans (Liability)'     => ['type' => 'liability',   'sub_type' => 'long_term_liability'],
            'Sales Accounts'        => ['type' => 'revenue',     'sub_type' => 'revenue'],
            'Purchase Accounts'     => ['type' => 'expense',     'sub_type' => 'cost_of_sales'],
            'Direct Expenses'       => ['type' => 'expense',     'sub_type' => 'cost_of_sales'],
            'Indirect Expenses'     => ['type' => 'expense',     'sub_type' => 'expense'],
            'Indirect Incomes'      => ['type' => 'revenue',     'sub_type' => 'other_income'],
        ];
        // Groups are implicit — created when ledgers are imported; handled in importLedgers
    }

    private function importLedgers(SimpleXMLElement $xml, int $tenantId, array &$errors, int &$imported): array
    {
        $accountMap = []; // Tally ledger name → Lekhya account id
        $partyIds   = [];
        $code       = 10000;

        $partyGroups = ['Sundry Debtors', 'Sundry Creditors', 'Branch / Divisions'];
        $salesGroups = ['Sales Accounts'];
        $purchaseGroups = ['Purchase Accounts', 'Direct Expenses'];
        $expenseGroups = ['Indirect Expenses', 'Duties & Taxes'];
        $incomeGroups  = ['Indirect Incomes', 'Other Income'];

        foreach ($xml->xpath('//LEDGER') as $ledger) {
            $name    = (string) $ledger['NAME'];
            $parent  = (string) $ledger->PARENT;
            $opening = (float) str_replace(['Dr', 'Cr', ' '], ['', '', ''], (string) $ledger->OPENINGBALANCE);
            $openingType = str_contains((string) $ledger->OPENINGBALANCE, 'Cr') ? 'credit' : 'debit';
            $gstin   = (string) $ledger->GSTIN ?? null;

            if (empty($name)) continue;

            // Determine account type from Tally group
            $type    = $this->mapTallyGroupToType($parent);
            $subType = $this->mapTallyGroupToSubType($parent);

            if (in_array($parent, $partyGroups)) {
                // Import as Party
                $partyType = str_contains($parent, 'Debtor') || str_contains($parent, 'Branch') ? 'customer' : 'vendor';
                $party = Party::firstOrCreate(
                    ['tenant_id' => $tenantId, 'name' => $name],
                    ['type' => $partyType, 'gstin' => $gstin ?: null]
                );
                $partyIds[] = $party->id;
            }

            // Always create as account (parties also need ledger accounts for A/R, A/P)
            $account = Account::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $name],
                [
                    'code'                 => (string) $code++,
                    'type'                 => $type,
                    'sub_type'             => $subType,
                    'is_ledger'            => true,
                    'opening_balance'      => abs($opening),
                    'opening_balance_type' => $openingType,
                ]
            );

            $accountMap[$name] = $account->id;
            $imported++;
        }

        return [$accountMap, $partyIds];
    }

    private function importVouchers(SimpleXMLElement $xml, int $tenantId, int $userId, array $accountMap, array &$errors, int &$imported, int &$failed): void
    {
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->first();
        if (! $fiscalYear) {
            $errors[] = 'No current fiscal year found — cannot import vouchers.';
            return;
        }

        foreach ($xml->xpath('//VOUCHER') as $voucher) {
            $type    = strtolower((string) $voucher->VOUCHERTYPENAME);
            $date    = $this->parseTallyDate((string) $voucher->DATE);
            $narr    = (string) $voucher->NARRATION;
            $vNumber = (string) $voucher->VOUCHERNUMBER;

            $lines = [];
            foreach ($voucher->ALLLEDGERENTRIES as $entry) {
                $ledgerName = (string) $entry->LEDGERNAME;
                $amount     = (float) str_replace(['Dr', 'Cr', ' '], ['', '', ''], (string) $entry->AMOUNT);
                $isDr       = ! str_contains((string) $entry->AMOUNT, 'Cr');

                $accountId = $accountMap[$ledgerName] ?? null;
                if (! $accountId) {
                    // Auto-create account
                    $acc = Account::firstOrCreate(
                        ['tenant_id' => $tenantId, 'name' => $ledgerName],
                        ['code' => 'TALLY-' . abs(crc32($ledgerName)), 'type' => 'expense', 'is_ledger' => true]
                    );
                    $accountMap[$ledgerName] = $acc->id;
                    $accountId = $acc->id;
                }

                $lines[] = [
                    'account_id' => $accountId,
                    'debit'      => $isDr ? abs($amount) : 0,
                    'credit'     => $isDr ? 0 : abs($amount),
                    'narration'  => $narr,
                ];
            }

            if (empty($lines) || count($lines) < 2) {
                $failed++;
                $errors[] = "Voucher {$vNumber}: insufficient lines";
                continue;
            }

            try {
                $this->journal->post([
                    'tenant_id'      => $tenantId,
                    'fiscal_year_id' => $fiscalYear->id,
                    'voucher_type'   => $this->mapTallyVoucherType($type),
                    'date'           => $date,
                    'narration'      => $narr ?: "Imported from Tally: {$vNumber}",
                    'reference'      => $vNumber,
                    'lines'          => $lines,
                    'created_by'     => $userId,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Voucher {$vNumber}: " . $e->getMessage();
            }
        }
    }

    private function parseTallyDate(string $tallyDate): string
    {
        // Tally date format: YYYYMMDD
        return date('Y-m-d', strtotime($tallyDate));
    }

    private function mapTallyVoucherType(string $type): string
    {
        return match (true) {
            str_contains($type, 'sales')    => 'sales',
            str_contains($type, 'purchase') => 'purchase',
            str_contains($type, 'receipt')  => 'receipt',
            str_contains($type, 'payment')  => 'payment',
            str_contains($type, 'contra')   => 'contra',
            str_contains($type, 'journal')  => 'journal',
            str_contains($type, 'debit')    => 'debit_note',
            str_contains($type, 'credit')   => 'credit_note',
            default                         => 'journal',
        };
    }

    private function mapTallyGroupToType(string $group): string
    {
        return match (true) {
            in_array($group, ['Current Assets', 'Fixed Assets', 'Investments', 'Loans & Advances (Asset)']) => 'asset',
            in_array($group, ['Current Liabilities', 'Loans (Liability)', 'Secured Loans', 'Unsecured Loans']) => 'liability',
            in_array($group, ['Capital Account', 'Reserves & Surplus']) => 'equity',
            in_array($group, ['Sales Accounts', 'Indirect Incomes', 'Other Income']) => 'revenue',
            default => 'expense',
        };
    }

    private function mapTallyGroupToSubType(string $group): string
    {
        return match ($group) {
            'Current Assets'         => 'current_asset',
            'Fixed Assets'           => 'fixed_asset',
            'Current Liabilities'    => 'current_liability',
            'Secured Loans', 'Unsecured Loans', 'Loans (Liability)' => 'long_term_liability',
            'Capital Account', 'Reserves & Surplus' => 'equity',
            'Sales Accounts'         => 'revenue',
            'Purchase Accounts', 'Direct Expenses' => 'cost_of_sales',
            'Indirect Incomes'       => 'other_income',
            default                  => 'expense',
        };
    }

    private function loadXml(string $path): SimpleXMLElement
    {
        if (! file_exists($path)) {
            throw new \RuntimeException("Tally export file not found: {$path}");
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            $err = libxml_get_last_error();
            throw new \RuntimeException("XML parse error: " . ($err ? $err->message : 'unknown'));
        }

        return $xml;
    }
}
