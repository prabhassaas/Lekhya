<?php
namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankPaymentTemplate;
use App\Models\Invoice;
use App\Services\Banking\BankPaymentFormats;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    /**
     * Pending payments from the recorded/uploaded bills.
     *  - payable    → purchases we still owe vendors (money out)
     *  - receivable → sales customers still owe us   (money in)
     */
    public function pending(Request $request)
    {
        $tenantId  = auth()->user()->tenant_id;
        $direction = $this->direction($request);
        $type      = $direction === 'receivable' ? 'sales' : 'purchase';

        $invoices = $this->pendingQuery($tenantId, $type)
            ->with('party')
            ->orderByRaw('due_date IS NULL asc')  // dated bills first
            ->orderBy('due_date')                  // then soonest/overdue first
            ->paginate(25)
            ->withQueryString();

        $summary = $this->summary($tenantId, $type);

        return view('accounting.payments.pending', compact('invoices', 'direction', 'summary'));
    }

    public function export(Request $request): StreamedResponse
    {
        $tenantId  = auth()->user()->tenant_id;
        $direction = $this->direction($request);
        $type      = $direction === 'receivable' ? 'sales' : 'purchase';

        $invoices = $this->pendingQuery($tenantId, $type)->with('party')
            ->orderByRaw('due_date IS NULL asc')->orderBy('due_date')->get();

        $party    = $direction === 'receivable' ? 'Customer' : 'Vendor';
        $filename = "pending-{$direction}-" . now()->format('Y-m-d') . '.csv';
        $columns  = [$party, 'GSTIN', 'Bill / Ref No', 'Invoice No', 'Invoice Date', 'Due Date', 'Status', 'Total (INR)', 'Paid (INR)', 'Balance (INR)', 'Overdue Days'];
        $today    = now()->startOfDay();

        return response()->streamDownload(function () use ($invoices, $columns, $today) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            // Explicit escape '' → RFC-4180 CSV and no PHP 8.4 fputcsv deprecation.
            fputcsv($out, $columns, ',', '"', '');
            foreach ($invoices as $inv) {
                $overdue = $inv->due_date && $inv->due_date->lt($today) ? $today->diffInDays($inv->due_date) : 0;
                fputcsv($out, [
                    $inv->party->name ?? '—',
                    $inv->party->gstin ?? '',
                    $inv->reference_number,
                    $inv->invoice_number,
                    optional($inv->invoice_date)->format('Y-m-d'),
                    optional($inv->due_date)->format('Y-m-d'),
                    $inv->status,
                    number_format((float) $inv->total_amount, 2, '.', ''),
                    number_format((float) $inv->paid_amount, 2, '.', ''),
                    number_format((float) $inv->balance_amount, 2, '.', ''),
                    $overdue,
                ], ',', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Bank payment-file builder: pick a bank, download an invoice-wise upload file. */
    public function bankFile(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $payables = $this->pendingQuery($tenantId, 'purchase')->with('party')
            ->orderByRaw('due_date IS NULL asc')->orderBy('due_date')->get();

        [$ready, $missing] = $payables->partition(fn ($i) => $i->party?->hasBankDetails());

        $formats  = collect(BankPaymentFormats::all())->map(fn ($f, $key) => $f + ['key' => $key])->values();
        $payTotal = (float) $ready->sum('balance_amount');
        $templates = BankPaymentTemplate::where('tenant_id', $tenantId)->latest()->get();

        return view('accounting.payments.bank-file', [
            'formats'      => $formats,
            'templates'    => $templates,
            'readyCount'   => $ready->count(),
            'payTotal'     => $payTotal,
            'missing'      => $missing->values(),
            'rtgsFloor'    => BankPaymentFormats::RTGS_FLOOR,
        ]);
    }

    /** Step 1: read the header row of an uploaded bank sample, guess a mapping. */
    public function uploadTemplate(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048', 'name' => 'nullable|string|max:120']);

        $rows = array_map(fn ($l) => str_getcsv($l, ',', '"', ''), array_filter(explode("\n", file_get_contents($request->file('file')->getRealPath()))));
        $headers = collect($rows[0] ?? [])->map(fn ($h) => trim((string) $h))->filter()->values()->all();
        if (! $headers) {
            return back()->with('error', 'Could not read any column headers from that file. Make sure the first row has the column names.');
        }

        $guess = collect($headers)->mapWithKeys(fn ($h) => [$h => BankPaymentFormats::guessToken($h)])->all();
        $name  = $request->input('name') ?: pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);

        return view('accounting.payments.bank-file-map', [
            'headers' => $headers,
            'guess'   => $guess,
            'name'    => $name,
            'tokens'  => BankPaymentFormats::tokens(),
        ]);
    }

    /** Step 2: save the confirmed header→field mapping as a reusable template. */
    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:120',
            'headers'   => 'required|array|min:1',
            'headers.*' => 'string',
            'mapping'   => 'required|array',
        ]);
        $tokens = array_keys(BankPaymentFormats::tokens());
        $mapping = collect($data['headers'])->mapWithKeys(function ($h) use ($request, $tokens) {
            $t = (string) $request->input("mapping.$h", '');
            return [$h => in_array($t, $tokens, true) ? $t : ''];
        })->all();

        BankPaymentTemplate::create([
            'tenant_id'  => auth()->user()->tenant_id,
            'name'       => $data['name'],
            'headers'    => array_values($data['headers']),
            'mapping'    => $mapping,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('accounting.payments.bankfile')->with('success', 'Bank format saved. You can now download payment files in that layout.');
    }

    public function deleteTemplate(BankPaymentTemplate $template)
    {
        abort_if($template->tenant_id !== auth()->user()->tenant_id, 403);
        $template->delete();

        return back()->with('success', 'Bank format removed.');
    }

    public function exportBank(Request $request, string $bank): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;

        // A user-uploaded custom format is keyed "custom-{id}".
        $template = null;
        $format = null;
        if (str_starts_with($bank, 'custom-')) {
            $template = BankPaymentTemplate::where('tenant_id', $tenantId)->find((int) substr($bank, 7));
            abort_if(! $template, 404);
            $headers = $template->headers;
        } else {
            $format = BankPaymentFormats::find($bank);
            abort_if(! $format, 404);
            $headers = BankPaymentFormats::headers($format);
        }

        $invoices = $this->pendingQuery($tenantId, 'purchase')->with('party')
            ->orderByRaw('due_date IS NULL asc')->orderBy('due_date')->get()
            ->filter(fn ($i) => $i->party?->hasBankDetails())
            ->values();

        // The tenant's own bank supplies the debit/remitter account some formats need.
        $own = BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->first();
        $ctx = ['debit_account' => $own?->account_number ?? '', 'debit_ifsc' => $own?->ifsc_code ?? ''];

        $filename = 'payment-' . preg_replace('/[^a-z0-9\-]/i', '', $bank) . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($invoices, $headers, $format, $template, $ctx) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ',', '"', '');
            foreach ($invoices as $inv) {
                $row = $template
                    ? BankPaymentFormats::rowFromTemplate($template->headers, $template->mapping, $inv, $ctx)
                    : BankPaymentFormats::row($format, $inv, $ctx);
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function direction(Request $request): string
    {
        return $request->get('direction') === 'receivable' ? 'receivable' : 'payable';
    }

    /** Unpaid, non-cancelled invoices of a type with a remaining balance. */
    private function pendingQuery(int $tenantId, string $type)
    {
        return Invoice::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->whereNotIn('status', ['cancelled', 'paid'])
            ->where('balance_amount', '>', 0);
    }

    /** @return array{total: float, overdue: float, count: int} */
    private function summary(int $tenantId, string $type): array
    {
        $rows  = $this->pendingQuery($tenantId, $type)->get(['balance_amount', 'due_date']);
        $today = now()->startOfDay();

        $total = 0.0;
        $overdue = 0.0;
        foreach ($rows as $r) {
            $total += (float) $r->balance_amount;
            if ($r->due_date && $r->due_date->lt($today)) {
                $overdue += (float) $r->balance_amount;
            }
        }

        return ['total' => $total, 'overdue' => $overdue, 'count' => $rows->count()];
    }
}
