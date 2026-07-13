<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use App\Models\{Journal, FiscalYear, Account, Invoice};
use App\Services\Accounting\JournalEngine;
use Illuminate\Http\Request;

class JournalController extends Controller {
    public function __construct(private JournalEngine $engine) {}

    public function index() {
        $tenantId = auth()->user()->tenant_id;
        $journals = Journal::where('tenant_id', $tenantId)->with('createdBy')->latest('date')->paginate(20);
        return view('accounting.journals.index', compact('journals'));
    }
    public function create() {
        $tenantId = auth()->user()->tenant_id;
        $accounts = Account::where('tenant_id', $tenantId)->where('is_ledger', true)->where('is_active', true)->get();
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->first();
        return view('accounting.journals.form', compact('accounts', 'fiscalYear'));
    }
    public function store(Request $request) {
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate([
            'date' => 'required|date',
            'narration' => 'required|string|max:500',
            'voucher_type' => 'required|in:journal,contra,payment,receipt',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);
        $fiscalYear = FiscalYear::where('tenant_id', $tenantId)->where('is_current', true)->firstOrFail();
        try {
            $journal = $this->engine->post(array_merge($validated, [
                'tenant_id' => $tenantId,
                'fiscal_year_id' => $fiscalYear->id,
                'created_by' => auth()->id(),
            ]));
            return redirect()->route('accounting.journals.show', $journal)->with('success', 'Journal posted.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
    public function show(Journal $journal) {
        return view('accounting.journals.show', compact('journal'));
    }
    public function edit(Journal $journal) { return back()->with('error', 'Posted journals cannot be edited. Use a reversal.'); }
    public function update(Request $request, Journal $journal) { return back()->with('error', 'Use reversal to correct a journal.'); }
    public function destroy(Journal $journal) { return back()->with('error', 'Journals cannot be deleted.'); }

    public function reverse(Journal $journal, Request $request) {
        $request->validate(['date' => 'required|date', 'reason' => 'nullable|string|max:255']);
        try {
            $reversal = $this->engine->reverse($journal, $request->date, auth()->id(), $request->reason ?? '');

            // Keep linked modules in step: if this journal came from an invoice,
            // void that invoice too so it leaves GST returns, payments & reports.
            $voided = Invoice::where('tenant_id', $journal->tenant_id)->where('journal_id', $journal->id)
                ->where('status', 'posted')->get();
            foreach ($voided as $inv) {
                $inv->update(['status' => 'cancelled', 'balance_amount' => 0]);
            }
            $note = $voided->isNotEmpty() ? ' Linked bill ' . $voided->pluck('invoice_number')->implode(', ') . ' voided.' : '';

            return redirect()->route('accounting.journals.show', $reversal)->with('success', 'Journal reversed. Reversal voucher created.' . $note);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
