<?php
namespace App\Http\Controllers\Banking;
use App\Http\Controllers\Controller;
use App\Models\{BankAccount, BankTransaction};
use Illuminate\Http\Request;
use League\Csv\Reader;

class BankReconciliationController extends Controller {
    public function index() {
        $tenantId = auth()->user()->tenant_id;
        $bankAccounts = BankAccount::where('tenant_id', $tenantId)->with('account')->get();
        return view('banking.index', compact('bankAccounts'));
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
        $transactions = BankTransaction::where('tenant_id', $tenantId)->where('bank_account_id', $bankAccount->id)->orderBy('transaction_date')->paginate(50);
        return view('banking.reconcile', compact('bankAccount', 'transactions'));
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
