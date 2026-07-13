<?php
namespace App\Http\Controllers\Banking;
use App\Http\Controllers\Controller;
use App\Models\{Account, BankAccount, BankTransaction, JournalLine};
use Illuminate\Http\Request;

class BankReconciliationController extends Controller {
    public function index() {
        $tenantId = auth()->user()->tenant_id;
        $bankAccounts = BankAccount::where('tenant_id', $tenantId)->with('account')->get();

        $stats = [];
        foreach ($bankAccounts as $ba) {
            $stats[$ba->id] = [
                'total'        => BankTransaction::where('bank_account_id', $ba->id)->count(),
                'unreconciled' => BankTransaction::where('bank_account_id', $ba->id)->where('status', 'unreconciled')->count(),
            ];
        }

        // Ledger accounts a bank feed can post against (asset/bank/cash leaves).
        $ledgers = Account::where('tenant_id', $tenantId)
            ->where(fn ($q) => $q->where('is_ledger', true)->orWhereDoesntHave('children'))
            ->where(fn ($q) => $q->where('type', 'asset')->orWhere('name', 'like', '%bank%')->orWhere('name', 'like', '%cash%'))
            ->orderBy('code')->get(['id', 'code', 'name']);

        return view('banking.index', compact('bankAccounts', 'stats', 'ledgers'));
    }

    public function createAccount(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $data = $request->validate([
            'bank_name'       => 'required|string|max:120',
            'account_number'  => 'required|string|max:34',
            'ifsc_code'       => 'nullable|string|max:15',
            'branch'          => 'nullable|string|max:120',
            'account_type'    => 'nullable|string|max:30',
            'opening_balance' => 'nullable|numeric',
            'account_id'      => 'required|integer|exists:accounts,id',
        ]);
        Account::where('tenant_id', $tenantId)->findOrFail($data['account_id']);

        BankAccount::create([
            'tenant_id'       => $tenantId,
            'account_id'      => $data['account_id'],
            'bank_name'       => $data['bank_name'],
            'account_number'  => preg_replace('/[^0-9A-Za-z]/', '', $data['account_number']),
            'ifsc_code'       => $data['ifsc_code'] ? strtoupper(trim($data['ifsc_code'])) : null,
            'branch'          => $data['branch'] ?? null,
            'account_type'    => $data['account_type'] ?? 'current',
            'opening_balance' => $data['opening_balance'] ?? 0,
            'is_active'       => true,
        ]);

        return back()->with('success', 'Bank account added. Import a statement to start reconciling.');
    }

    public function importPassbook(Request $request) {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'date_col' => 'required|integer|min:0',
            'desc_col' => 'required|integer|min:0',
            'debit_col' => 'required|integer|min:0',
            'credit_col' => 'required|integer|min:0',
            'balance_col' => 'nullable|integer|min:0',
            'skip_rows' => 'required|integer|min:0',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $bankAccountId = $request->bank_account_id;
        $file = $request->file('file');
        $skipRows = (int)$request->skip_rows;
        $rows = array_map('str_getcsv', array_filter(explode("\n", file_get_contents($file->getRealPath()))));
        $imported = 0;
        $skipped = 0;

        foreach (array_slice($rows, $skipRows) as $row) {
            if (empty($row) || count($row) < 3) continue;
            $dateStr = trim($row[$request->date_col] ?? '');
            if (empty($dateStr)) continue;
            try {
                $date = \Carbon\Carbon::parse($dateStr);
            } catch (\Throwable) { $skipped++; continue; }

            $debit = (float)str_replace([',', ' '], '', $row[$request->debit_col] ?? 0);
            $credit = (float)str_replace([',', ' '], '', $row[$request->credit_col] ?? 0);
            $balance = $request->balance_col !== null ? (float)str_replace([',', ' '], '', $row[$request->balance_col] ?? 0) : 0;
            $desc = trim($row[$request->desc_col] ?? '');

            if ($debit <= 0 && $credit <= 0) { $skipped++; continue; }

            BankTransaction::firstOrCreate(
                ['tenant_id' => $tenantId, 'bank_account_id' => $bankAccountId, 'transaction_date' => $date->format('Y-m-d'), 'description' => $desc, 'debit' => $debit, 'credit' => $credit],
                ['balance' => $balance, 'status' => 'unreconciled', 'source' => 'csv_upload']
            ) ? $imported++ : $skipped++;
        }

        return redirect()->route('banking.reconcile', $bankAccountId)->with('success', "Imported {$imported} transactions. Skipped: {$skipped}.");
    }

    public function reconcile(BankAccount $bankAccount) {
        $tenantId = auth()->user()->tenant_id;
        abort_if($bankAccount->tenant_id !== $tenantId, 403);

        $transactions = BankTransaction::where('tenant_id', $tenantId)
            ->where('bank_account_id', $bankAccount->id)
            ->orderBy('transaction_date')->orderBy('id')->paginate(50);

        // Unmatched ledger postings on this bank's account, to suggest matches from.
        $used = BankTransaction::where('bank_account_id', $bankAccount->id)
            ->whereNotNull('journal_line_id')->pluck('journal_line_id')->all();

        $pool = $bankAccount->account_id
            ? JournalLine::where('tenant_id', $tenantId)
                ->where('account_id', $bankAccount->account_id)
                ->whereNotIn('id', $used ?: [0])
                ->with('journal')->get()
            : collect();

        // Money IN on the statement (credit) ↔ a DEBIT to the bank ledger; money
        // OUT (debit) ↔ a CREDIT. Suggest the same-amount posting nearest in date.
        $suggestions = [];
        foreach ($transactions as $txn) {
            if ($txn->status === 'reconciled') { continue; }
            $isIn  = (float) $txn->credit > 0;
            $amount = $isIn ? (float) $txn->credit : (float) $txn->debit;
            if ($amount <= 0) { continue; }

            $best = $pool
                ->filter(fn ($l) => abs(($isIn ? (float) $l->debit : (float) $l->credit) - $amount) < 0.01)
                ->sortBy(fn ($l) => optional($l->journal)->date
                    ? \Carbon\Carbon::parse($l->journal->date)->diffInDays($txn->transaction_date)
                    : 9999)
                ->first();

            if ($best) {
                $suggestions[$txn->id] = $best;
            }
        }

        return view('banking.reconcile', compact('bankAccount', 'transactions', 'suggestions'));
    }

    public function match(Request $request) {
        $request->validate(['bank_transaction_id' => 'required|exists:bank_transactions,id', 'journal_line_id' => 'required|exists:journal_lines,id']);
        BankTransaction::findOrFail($request->bank_transaction_id)->update(['status' => 'reconciled', 'journal_line_id' => $request->journal_line_id]);
        return response()->json(['status' => 'ok']);
    }

    public function complete(Request $request) {
        $request->validate(['bank_account_id' => 'required|exists:bank_accounts,id', 'statement_date' => 'required|date', 'statement_balance' => 'required|numeric']);
        $tenantId = auth()->user()->tenant_id;
        \App\Models\BankReconciliation::create([
            'tenant_id' => $tenantId,
            'bank_account_id' => $request->bank_account_id,
            'statement_date' => $request->statement_date,
            'statement_balance' => $request->statement_balance,
            'book_balance' => $request->statement_balance,
            'is_reconciled' => true,
            'reconciled_at' => now(),
            'reconciled_by' => auth()->id(),
        ]);
        return back()->with('success', 'Reconciliation completed and locked.');
    }
}
