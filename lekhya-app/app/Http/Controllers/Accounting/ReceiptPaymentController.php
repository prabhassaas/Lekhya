<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Payment;
use App\Services\Accounting\ReceiptPaymentService;
use Illuminate\Http\Request;

class ReceiptPaymentController extends Controller
{
    use \App\Http\Controllers\Concerns\SortsListings;

    public function __construct(private ReceiptPaymentService $service) {}

    /** History of receipts & payments. */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $type     = in_array($request->get('type'), ['receipt', 'payment'], true) ? $request->get('type') : null;

        $query = Payment::where('tenant_id', $tenantId)->with(['party', 'ledgerAccount']);
        if ($type) {
            $query->where('type', $type);
        }
        $this->applySort($query, $request, [
            'reference_number' => 'reference_number',
            'date'             => 'date',
            'type'             => 'type',
            'amount'           => 'amount',
        ], fn ($q) => $q->latest('date'));

        $payments = $query->paginate(25)->withQueryString();
        $counts = [
            'receipt' => Payment::where('tenant_id', $tenantId)->where('type', 'receipt')->count(),
            'payment' => Payment::where('tenant_id', $tenantId)->where('type', 'payment')->count(),
        ];

        return view('accounting.payments.history', compact('payments', 'type', 'counts'));
    }

    /** Record-payment form, scoped to a party's open bills. */
    public function create(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $type     = $request->get('type') === 'payment' ? 'payment' : 'receipt';
        $invType  = $type === 'receipt' ? 'sales' : 'purchase';

        // Parties that actually have something open for this direction.
        $parties = Party::where('tenant_id', $tenantId)
            ->whereHas('invoices', fn ($q) => $q->where('type', $invType)->whereIn('status', ['posted', 'partially_paid'])->where('balance_amount', '>', 0))
            ->orderBy('name')->get();

        $party = null;
        $bills = collect();
        if ($request->filled('party_id')) {
            $party = Party::where('tenant_id', $tenantId)->find($request->get('party_id'));
            if ($party) {
                $bills = Invoice::where('tenant_id', $tenantId)
                    ->where('party_id', $party->id)->where('type', $invType)
                    ->whereIn('status', ['posted', 'partially_paid'])->where('balance_amount', '>', 0)
                    ->orderBy('invoice_date')->get();
            }
        }

        // Bank / cash ledger accounts to receive into / pay from.
        $ledgers = Account::where('tenant_id', $tenantId)->where('is_ledger', true)
            ->where(fn ($q) => $q->where('type', 'asset'))
            ->where(fn ($q) => $q->where('name', 'like', '%bank%')->orWhere('name', 'like', '%cash%')->orWhere('code', 'like', '13%')->orWhere('code', 'like', '14%'))
            ->orderBy('code')->get();

        return view('accounting.payments.record', compact('type', 'parties', 'party', 'bills', 'ledgers'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $data = $request->validate([
            'type'              => 'required|in:receipt,payment',
            'party_id'          => 'required|integer',
            'ledger_account_id' => 'required|integer',
            'payment_date'      => 'required|date',
            'mode'              => 'nullable|string|max:30',
            'reference'         => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:500',
            'tds_amount'        => 'nullable|numeric|min:0',
            'allocations'       => 'required|array|min:1',
            'allocations.*.invoice_id' => 'required|integer',
            'allocations.*.amount'     => 'nullable|numeric|min:0',
        ]);

        abort_if(! Party::where('tenant_id', $tenantId)->whereKey($data['party_id'])->exists(), 403);
        abort_if(! Account::where('tenant_id', $tenantId)->whereKey($data['ledger_account_id'])->exists(), 403);

        try {
            $payment = $this->service->record([
                'tenant_id'         => $tenantId,
                'type'              => $data['type'],
                'party_id'          => $data['party_id'],
                'ledger_account_id' => $data['ledger_account_id'],
                'payment_date'      => $data['payment_date'],
                'mode'              => $data['mode'] ?? null,
                'reference'         => $data['reference'] ?? null,
                'notes'             => $data['notes'] ?? null,
                'tds_amount'        => $data['tds_amount'] ?? 0,
                'allocations'       => $data['allocations'],
                'created_by'        => auth()->id(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $label = $payment->label();
        return redirect()->route('accounting.payments.history', ['type' => $payment->type])
            ->with('success', "{$label} {$payment->reference_number} recorded — ₹" . number_format((float) $payment->amount, 2) . ' settled.');
    }

    public function show(Payment $payment)
    {
        abort_if($payment->tenant_id !== auth()->user()->tenant_id, 403);
        $payment->load(['party', 'ledgerAccount', 'journal.lines.account', 'allocations.invoice']);
        return view('accounting.payments.show', compact('payment'));
    }
}
