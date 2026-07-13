<?php
namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AiUsage;
use App\Models\Invoice;
use App\Models\Party;
use App\Services\AI\AiService;
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

    /** Quick-create a party inline (from the invoice form) — returns JSON. */
    public function quickStore(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:vendor,customer,both',
            'gstin'          => 'nullable|string|max:15',
            'phone'          => 'nullable|string|max:15',
            'email'          => 'nullable|email|max:255',
            'classification' => 'nullable|in:customer,vendor,supplier,service_provider',
        ]);
        $gstin = $data['gstin'] ? strtoupper(trim($data['gstin'])) : null;

        $party = Party::create([
            'tenant_id'      => auth()->user()->tenant_id,
            'type'           => $data['type'],
            'classification' => $data['classification'] ?? null,
            'name'           => $data['name'],
            'gstin'          => $gstin,
            'phone'          => $data['phone'] ?? null,
            'email'          => $data['email'] ?? null,
            'state_code'     => ($gstin && strlen($gstin) >= 2) ? substr($gstin, 0, 2) : null,
            'is_active'      => true,
        ]);

        return response()->json(['id' => $party->id, 'name' => $party->name, 'gstin' => $party->gstin]);
    }

    /** Read address/bank details from an uploaded image; returns proposed fields for the user to approve. */
    public function extractDetails(Request $request, AiService $ai, Party $party)
    {
        abort_if($party->tenant_id !== auth()->user()->tenant_id, 403);
        $request->validate(['file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:10240']);

        $tenant = auth()->user()->tenant;
        if ($tenant?->aiCreditsExhausted()) {
            return response()->json(['error' => "Monthly AI credits used up — upgrade your plan for more."], 429);
        }

        $ex = $ai->extractFromFile($request->file('file'));
        if (isset($ex['error'])) {
            return response()->json(['error' => 'Could not read the image. Try a clearer, well-lit photo.'], 422);
        }

        AiUsage::create(['tenant_id' => $tenant->id, 'user_id' => auth()->id(), 'type' => 'party_details', 'driver' => $ai->getDriverName(), 'billable' => true]);

        // A letterhead / visiting card reads like a seller block.
        $val = fn ($k) => ($v = trim((string) ($ex["seller_$k"] ?? ''))) !== '' ? $v : null;
        $gstin = strtoupper((string) ($val('gstin') ?? ''));
        $gstin = strlen($gstin) === 15 ? $gstin : null;
        $pan   = strtoupper((string) ($val('pan') ?? ''));
        $ifsc  = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) ($ex['seller_bank_ifsc'] ?? '')));
        $acct  = preg_replace('/[^0-9A-Za-z]/', '', (string) ($ex['seller_bank_account'] ?? ''));

        $fields = array_filter([
            'name'                => $val('name'),
            'gstin'               => $gstin,
            'pan'                 => strlen($pan) === 10 ? $pan : null,
            'address'             => $val('address'),
            'email'               => $val('email'),
            'phone'               => $val('phone'),
            'state_code'          => $gstin ? substr($gstin, 0, 2) : null,
            'bank_name'           => trim((string) ($ex['seller_bank_name'] ?? '')) ?: null,
            'bank_account_number' => $acct ?: null,
            'bank_ifsc'           => preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc) ? $ifsc : null,
            'bank_account_holder' => trim((string) ($ex['seller_account_holder'] ?? '')) ?: null,
            'upi_id'              => trim((string) ($ex['seller_upi'] ?? '')) ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        return response()->json(['fields' => $fields]);
    }

    public function show(Party $party)
    {
        abort_if($party->tenant_id !== auth()->user()->tenant_id, 403);

        $party->load('branches');
        $invoices    = $party->invoices()->latest('invoice_date')->paginate(20);
        $outstanding = (float) $party->invoices()->whereNotIn('status', ['cancelled', 'paid'])->sum('balance_amount');
        $billed      = (float) $party->invoices()->whereNotIn('status', ['cancelled'])->sum('total_amount');

        return view('accounting.parties.show', compact('party', 'invoices', 'outstanding', 'billed'));
    }

    public function edit(Party $party)
    {
        abort_if($party->tenant_id !== auth()->user()->tenant_id, 403);
        return view('accounting.parties.edit', compact('party'));
    }

    public function update(Request $request, Party $party)
    {
        abort_if($party->tenant_id !== auth()->user()->tenant_id, 403);

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'type'       => 'required|in:vendor,customer,both',
            'classification' => 'nullable|in:customer,vendor,supplier,service_provider',
            'tds_rate'   => 'nullable|numeric|min:0|max:100',
            'tds_section' => 'nullable|string|max:20',
            'gstin'      => 'nullable|string|size:15',
            'pan'        => 'nullable|string|size:10',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:15',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:100',
            'state'      => 'nullable|string|max:100',
            'state_code' => 'nullable|string|size:2',
            'pincode'    => 'nullable|string|max:10',
            'bank_name'           => 'nullable|string|max:120',
            'bank_account_number' => 'nullable|string|max:34',
            'bank_ifsc'           => 'nullable|string|max:15',
            'bank_account_holder' => 'nullable|string|max:120',
            'upi_id'              => 'nullable|string|max:120',
        ]);

        $data['gstin'] = $data['gstin'] ? strtoupper(trim($data['gstin'])) : null;
        $data['pan']   = $data['pan'] ? strtoupper(trim($data['pan'])) : null;
        $data['bank_ifsc'] = $data['bank_ifsc'] ? strtoupper(trim($data['bank_ifsc'])) : null;
        $data['bank_account_number'] = $data['bank_account_number']
            ? preg_replace('/[^0-9A-Za-z]/', '', $data['bank_account_number']) : null;
        // Keep the state code in step with the GSTIN when it wasn't set explicitly.
        if (empty($data['state_code']) && $data['gstin']) {
            $data['state_code'] = substr($data['gstin'], 0, 2);
        }
        $data['is_active'] = $request->boolean('is_active');

        $party->update($data);

        return redirect()->route('accounting.parties.show', $party)->with('success', "“{$party->name}” updated.");
    }

    public function destroy(Party $party)
    {
        abort_if($party->tenant_id !== auth()->user()->tenant_id, 403);

        // Don't orphan ledger records — a party with bills/invoices can't be
        // removed until those are dealt with. Wrong auto-created vendors with no
        // linked invoices (the confusing case) delete freely.
        $linked = $party->invoices()->count();
        if ($linked > 0) {
            return back()->with('error', "Can't delete “{$party->name}” — it has {$linked} linked invoice/bill(s). Delete or reassign those first.");
        }

        $tab  = $party->type === 'customer' ? 'customer' : 'vendor';
        $name = $party->name;
        $party->delete(); // soft delete

        return redirect()->route('accounting.parties.index', ['tab' => $tab])->with('success', "“{$name}” deleted.");
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
