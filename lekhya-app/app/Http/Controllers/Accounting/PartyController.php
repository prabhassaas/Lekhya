<?php
namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Party;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartyController extends Controller
{
    /** Vendors (default), Customers, or All — with search + per-party outstanding. */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $tab      = $this->tab($request);
        $search   = trim((string) $request->get('q', ''));

        $parties = $this->query($tenantId, $tab, $search)
            ->withCount('invoices')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $balances = $this->outstandingByParty($tenantId, $parties->pluck('id')->all());

        $counts = [
            'vendor'   => Party::where('tenant_id', $tenantId)->whereIn('type', ['vendor', 'both'])->count(),
            'customer' => Party::where('tenant_id', $tenantId)->whereIn('type', ['customer', 'both'])->count(),
            'all'      => Party::where('tenant_id', $tenantId)->count(),
        ];

        return view('accounting.parties.index', compact('parties', 'tab', 'search', 'balances', 'counts'));
    }

    public function show(Party $party)
    {
        abort_if($party->tenant_id !== auth()->user()->tenant_id, 403);

        $invoices    = $party->invoices()->latest('invoice_date')->paginate(20);
        $outstanding = (float) $party->invoices()->whereNotIn('status', ['cancelled', 'paid'])->sum('balance_amount');
        $billed      = (float) $party->invoices()->whereNotIn('status', ['cancelled'])->sum('total_amount');

        return view('accounting.parties.show', compact('party', 'invoices', 'outstanding', 'billed'));
    }

    public function export(Request $request): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $tab      = $this->tab($request);
        $search   = trim((string) $request->get('q', ''));

        $parties  = $this->query($tenantId, $tab, $search)->orderBy('name')->get();
        $balances = $this->outstandingByParty($tenantId, $parties->pluck('id')->all());

        $filename = "parties-{$tab}-" . now()->format('Y-m-d') . '.csv';
        $columns  = ['Name', 'Type', 'GSTIN', 'PAN', 'Phone', 'Email', 'Address', 'City', 'State', 'State Code', 'Pincode', 'Outstanding (INR)', 'Status'];

        return response()->streamDownload(function () use ($parties, $balances, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders ₹ and names correctly
            // Explicit escape '' → RFC-4180 CSV and no PHP 8.4 fputcsv deprecation.
            fputcsv($out, $columns, ',', '"', '');
            foreach ($parties as $p) {
                fputcsv($out, [
                    $p->name, $p->type, $p->gstin, $p->pan, $p->phone, $p->email,
                    $p->address, $p->city, $p->state, $p->state_code, $p->pincode,
                    number_format((float) ($balances[$p->id] ?? 0), 2, '.', ''),
                    $p->is_active ? 'Active' : 'Inactive',
                ], ',', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function tab(Request $request): string
    {
        return in_array($request->get('tab'), ['vendor', 'customer', 'all'], true)
            ? $request->get('tab')
            : 'vendor';
    }

    private function query(int $tenantId, string $tab, string $search)
    {
        $q = Party::where('tenant_id', $tenantId);

        if ($tab === 'vendor') {
            $q->whereIn('type', ['vendor', 'both']);
        } elseif ($tab === 'customer') {
            $q->whereIn('type', ['customer', 'both']);
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('gstin', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $q;
    }

    /**
     * Outstanding balance per party in one grouped query.
     * @return array<int, float>
     */
    private function outstandingByParty(int $tenantId, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return Invoice::where('tenant_id', $tenantId)
            ->whereIn('party_id', $ids)
            ->whereNotIn('status', ['cancelled', 'paid'])
            ->selectRaw('party_id, SUM(balance_amount) as bal')
            ->groupBy('party_id')
            ->pluck('bal', 'party_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }
}
