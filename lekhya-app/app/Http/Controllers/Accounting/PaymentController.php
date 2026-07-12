<?php
namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
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
