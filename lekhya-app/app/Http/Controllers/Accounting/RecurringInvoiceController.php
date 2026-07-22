<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Services\Accounting\RecurringInvoiceService;
use Illuminate\Http\Request;

class RecurringInvoiceController extends Controller
{
    public function __construct(private RecurringInvoiceService $service) {}

    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $schedules = RecurringInvoice::where('tenant_id', $tenantId)
            ->with(['party', 'lastInvoice'])
            ->orderByRaw("FIELD(status,'active','paused','ended')")
            ->orderBy('next_run_date')
            ->paginate(20);

        return view('accounting.recurring.index', compact('schedules'));
    }

    /** Turn an existing invoice into a recurring schedule. */
    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $data = $request->validate([
            'invoice_id'        => 'required|integer',
            'title'             => 'nullable|string|max:150',
            'frequency'         => 'required|in:weekly,monthly,quarterly,yearly',
            'interval_count'    => 'nullable|integer|min:1|max:60',
            'start_date'        => 'required|date|after_or_equal:today',
            'end_date'          => 'nullable|date|after:start_date',
            'occurrences_limit' => 'nullable|integer|min:1|max:600',
            'auto_post'         => 'nullable|boolean',
        ]);

        $invoice = Invoice::where('tenant_id', $tenantId)->with('lines')->findOrFail($data['invoice_id']);
        if ($invoice->type !== 'sales') {
            return back()->with('error', 'Only sales documents can be set to recur.');
        }

        $schedule = $this->service->snapshotFrom($invoice, [
            'title'             => $data['title'] ?? null,
            'frequency'         => $data['frequency'],
            'interval_count'    => $data['interval_count'] ?? 1,
            'start_date'        => $data['start_date'],
            'end_date'          => $data['end_date'] ?? null,
            'occurrences_limit' => $data['occurrences_limit'] ?? null,
            'auto_post'         => $request->boolean('auto_post'),
            'created_by'        => auth()->id(),
        ]);

        return redirect()->route('accounting.recurring.index')
            ->with('success', "Recurring schedule created — next invoice on {$schedule->next_run_date->format('d M Y')}.");
    }

    public function show(RecurringInvoice $recurring)
    {
        abort_if($recurring->tenant_id !== auth()->user()->tenant_id, 403);
        $recurring->load(['party', 'invoices' => fn ($q) => $q->latest('invoice_date')]);

        return view('accounting.recurring.show', compact('recurring'));
    }

    public function pause(RecurringInvoice $recurring)
    {
        abort_if($recurring->tenant_id !== auth()->user()->tenant_id, 403);
        if ($recurring->status === 'active') {
            $recurring->update(['status' => 'paused']);
        }

        return back()->with('success', 'Schedule paused — no invoices will be raised until you resume it.');
    }

    public function resume(RecurringInvoice $recurring)
    {
        abort_if($recurring->tenant_id !== auth()->user()->tenant_id, 403);
        if ($recurring->status === 'paused') {
            // If the next run drifted into the past while paused, catch it up to today.
            $next = $recurring->next_run_date && $recurring->next_run_date->isPast()
                ? now()->toDateString() : $recurring->next_run_date?->toDateString();
            $recurring->update(['status' => 'active', 'next_run_date' => $next]);
        }

        return back()->with('success', 'Schedule resumed.');
    }

    /** Raise the next invoice immediately, regardless of the schedule date. */
    public function runNow(RecurringInvoice $recurring)
    {
        abort_if($recurring->tenant_id !== auth()->user()->tenant_id, 403);
        if ($recurring->status === 'ended') {
            return back()->with('error', 'This schedule has ended.');
        }

        $invoice = $this->service->generate($recurring);
        if (! $invoice) {
            return back()->with('error', 'Could not raise the invoice — no open fiscal year for the run date.');
        }

        return redirect()->route('accounting.invoices.show', $invoice)
            ->with('success', "Draft {$invoice->documentLabel()} {$invoice->invoice_number} raised from the schedule.");
    }

    public function destroy(RecurringInvoice $recurring)
    {
        abort_if($recurring->tenant_id !== auth()->user()->tenant_id, 403);
        $recurring->delete();

        return redirect()->route('accounting.recurring.index')->with('success', 'Recurring schedule deleted.');
    }
}
