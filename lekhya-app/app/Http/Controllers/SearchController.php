<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Party;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /** Full results page. */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $tenantId = auth()->user()->tenant_id;

        $parties = $invoices = collect();
        if (mb_strlen($q) >= 2) {
            $parties  = $this->parties($q, $tenantId)->limit(50)->get();
            $invoices = $this->invoices($q, $tenantId)->with('party')->limit(50)->get();
        }

        return view('search.results', compact('q', 'parties', 'invoices'));
    }

    /** JSON for the top-bar live dropdown. */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $tenantId = auth()->user()->tenant_id;

        if (mb_strlen($q) < 2) {
            return response()->json(['parties' => [], 'invoices' => []]);
        }

        $parties = $this->parties($q, $tenantId)->limit(6)->get()->map(fn ($p) => [
            'name'  => $p->name,
            'sub'   => collect([$p->gstin, $p->phone])->filter()->implode(' · ') ?: ucfirst($p->type),
            'type'  => $p->type,
            'url'   => route('accounting.parties.show', $p),
        ]);

        $invoices = $this->invoices($q, $tenantId)->with('party')->limit(6)->get()->map(fn ($i) => [
            'number' => $i->invoice_number,
            'sub'    => collect([$i->party?->name, $i->invoice_date?->format('d M Y')])->filter()->implode(' · '),
            'type'   => $i->type,
            'amount' => '₹' . number_format((float) $i->total_amount, 0),
            'url'    => route('accounting.invoices.show', $i),
        ]);

        return response()->json(['parties' => $parties, 'invoices' => $invoices]);
    }

    private function parties(string $q, int $tenantId)
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';

        return Party::where('tenant_id', $tenantId)->where(function ($w) use ($like) {
            $w->where('name', 'like', $like)
              ->orWhere('display_name', 'like', $like)
              ->orWhere('gstin', 'like', $like)
              ->orWhere('pan', 'like', $like)
              ->orWhere('phone', 'like', $like)
              ->orWhere('email', 'like', $like);
        })->orderBy('name');
    }

    private function invoices(string $q, int $tenantId)
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';

        return Invoice::where('tenant_id', $tenantId)->where(function ($w) use ($like) {
            $w->where('invoice_number', 'like', $like)
              ->orWhere('reference_number', 'like', $like)
              ->orWhereHas('party', function ($p) use ($like) {
                  $p->where('name', 'like', $like)
                    ->orWhere('gstin', 'like', $like)
                    ->orWhere('phone', 'like', $like);
              });
        })->latest('invoice_date');
    }
}
