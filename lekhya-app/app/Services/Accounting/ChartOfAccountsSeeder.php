<?php

namespace App\Services\Accounting;

use App\Models\Account;

/**
 * Seeds a standard GST-ready Indian chart of accounts for a new tenant.
 */
class ChartOfAccountsSeeder
{
    public function seed(int $tenantId): void
    {
        $accounts = [
            // ── Assets ──────────────────────────────────────────────────
            ['code' => '1000', 'name' => 'Current Assets',     'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => false],
            ['code' => '1100', 'name' => 'Accounts Receivable','type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => true,  'is_system' => true],
            ['code' => '1200', 'name' => 'GST Input Credit',   'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => false],
            ['code' => '1210', 'name' => 'CGST Input',         'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => true,  'is_system' => true, 'parent_code' => '1200'],
            ['code' => '1220', 'name' => 'SGST Input',         'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => true,  'is_system' => true, 'parent_code' => '1200'],
            ['code' => '1230', 'name' => 'IGST Input',         'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => true,  'is_system' => true, 'parent_code' => '1200'],
            ['code' => '1300', 'name' => 'Cash',               'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => true,  'is_system' => true],
            ['code' => '1400', 'name' => 'Bank Accounts',      'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => false],
            ['code' => '1410', 'name' => 'Current Account',    'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => true,  'parent_code' => '1400'],
            ['code' => '1500', 'name' => 'Inventory',          'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => true],
            ['code' => '1600', 'name' => 'Prepaid Expenses',   'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => true],
            ['code' => '1700', 'name' => 'TDS Receivable',     'type' => 'asset',     'sub_type' => 'current_asset',    'is_ledger' => true],
            ['code' => '2000', 'name' => 'Fixed Assets',       'type' => 'asset',     'sub_type' => 'fixed_asset',      'is_ledger' => false],
            ['code' => '2010', 'name' => 'Plant & Machinery',  'type' => 'asset',     'sub_type' => 'fixed_asset',      'is_ledger' => true,  'parent_code' => '2000'],
            ['code' => '2020', 'name' => 'Furniture & Fixtures','type' => 'asset',    'sub_type' => 'fixed_asset',      'is_ledger' => true,  'parent_code' => '2000'],
            ['code' => '2030', 'name' => 'Computer & Software','type' => 'asset',     'sub_type' => 'fixed_asset',      'is_ledger' => true,  'parent_code' => '2000'],

            // ── Liabilities ─────────────────────────────────────────────
            ['code' => '2100', 'name' => 'Accounts Payable',   'type' => 'liability', 'sub_type' => 'current_liability','is_ledger' => true,  'is_system' => true],
            ['code' => '2200', 'name' => 'GST Output Payable', 'type' => 'liability', 'sub_type' => 'current_liability','is_ledger' => false],
            ['code' => '2210', 'name' => 'CGST Output',        'type' => 'liability', 'sub_type' => 'current_liability','is_ledger' => true,  'is_system' => true, 'parent_code' => '2200'],
            ['code' => '2220', 'name' => 'SGST Output',        'type' => 'liability', 'sub_type' => 'current_liability','is_ledger' => true,  'is_system' => true, 'parent_code' => '2200'],
            ['code' => '2230', 'name' => 'IGST Output',        'type' => 'liability', 'sub_type' => 'current_liability','is_ledger' => true,  'is_system' => true, 'parent_code' => '2200'],
            ['code' => '2300', 'name' => 'TDS Payable',        'type' => 'liability', 'sub_type' => 'current_liability','is_ledger' => true],
            ['code' => '2400', 'name' => 'Salary Payable',     'type' => 'liability', 'sub_type' => 'current_liability','is_ledger' => true],
            ['code' => '2500', 'name' => 'Short-term Loans',   'type' => 'liability', 'sub_type' => 'current_liability','is_ledger' => true],
            ['code' => '3000', 'name' => 'Long-term Loans',    'type' => 'liability', 'sub_type' => 'long_term_liability','is_ledger' => true],

            // ── Equity ──────────────────────────────────────────────────
            ['code' => '3500', 'name' => 'Owners Equity',      'type' => 'equity',    'sub_type' => 'equity',           'is_ledger' => true,  'is_system' => true],
            ['code' => '3600', 'name' => 'Retained Earnings',  'type' => 'equity',    'sub_type' => 'equity',           'is_ledger' => true,  'is_system' => true],
            ['code' => '3700', 'name' => 'Reserve & Surplus',  'type' => 'equity',    'sub_type' => 'equity',           'is_ledger' => true],

            // ── Revenue ─────────────────────────────────────────────────
            ['code' => '4000', 'name' => 'Sales',              'type' => 'revenue',   'sub_type' => 'revenue',          'is_ledger' => true,  'is_system' => true],
            ['code' => '4100', 'name' => 'Service Revenue',    'type' => 'revenue',   'sub_type' => 'revenue',          'is_ledger' => true],
            ['code' => '4200', 'name' => 'Export Sales',       'type' => 'revenue',   'sub_type' => 'revenue',          'is_ledger' => true],
            ['code' => '4500', 'name' => 'Other Income',       'type' => 'revenue',   'sub_type' => 'other_income',     'is_ledger' => true],

            // ── Cost of Sales ────────────────────────────────────────────
            ['code' => '5000', 'name' => 'Purchases',          'type' => 'expense',   'sub_type' => 'cost_of_sales',    'is_ledger' => true,  'is_system' => true],
            ['code' => '5100', 'name' => 'Import Purchases',   'type' => 'expense',   'sub_type' => 'cost_of_sales',    'is_ledger' => true],

            // ── Operating Expenses ───────────────────────────────────────
            ['code' => '6000', 'name' => 'Salaries & Wages',   'type' => 'expense',   'sub_type' => 'expense',          'is_ledger' => true],
            ['code' => '6100', 'name' => 'Rent',               'type' => 'expense',   'sub_type' => 'expense',          'is_ledger' => true],
            ['code' => '6200', 'name' => 'Utilities',          'type' => 'expense',   'sub_type' => 'expense',          'is_ledger' => true],
            ['code' => '6300', 'name' => 'Office Expenses',    'type' => 'expense',   'sub_type' => 'expense',          'is_ledger' => true],
            ['code' => '6400', 'name' => 'Marketing & Advertising','type' => 'expense','sub_type' => 'expense',         'is_ledger' => true],
            ['code' => '6500', 'name' => 'Travel & Conveyance','type' => 'expense',   'sub_type' => 'expense',          'is_ledger' => true],
            ['code' => '6600', 'name' => 'Professional Fees',  'type' => 'expense',   'sub_type' => 'expense',          'is_ledger' => true],
            ['code' => '6700', 'name' => 'Bank Charges',       'type' => 'expense',   'sub_type' => 'expense',          'is_ledger' => true],
            ['code' => '6800', 'name' => 'Depreciation',       'type' => 'expense',   'sub_type' => 'expense',          'is_ledger' => true],
            ['code' => '7000', 'name' => 'Tax Expense',        'type' => 'expense',   'sub_type' => 'expense',          'is_ledger' => true],
            ['code' => '8000', 'name' => 'Round Off',          'type' => 'expense',   'sub_type' => 'expense',          'is_ledger' => true,  'is_system' => true],
        ];

        $codeToId = [];
        foreach ($accounts as $acc) {
            $parentId = isset($acc['parent_code']) ? ($codeToId[$acc['parent_code']] ?? null) : null;
            $model = Account::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => $acc['code']],
                array_merge($acc, [
                    'tenant_id' => $tenantId,
                    'parent_id' => $parentId,
                    'is_system' => $acc['is_system'] ?? false,
                    'is_active' => true,
                    'level'     => 1,
                ])
            );
            $codeToId[$acc['code']] = $model->id;
        }
    }
}
