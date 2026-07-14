<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\HsnSacCode;
use App\Models\Product;
use App\Models\TenantItem;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use \App\Http\Controllers\Concerns\SortsListings;

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $search   = trim((string) $request->get('q', ''));

        $products = Product::where('tenant_id', $tenantId)
            ->when($search !== '', function ($w) use ($search) {
                $w->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('hsn_sac_code', 'like', "%{$search}%")
                      ->orWhere('quality', 'like', "%{$search}%");
                });
            });
        $this->applySort($products, $request, [
            'name'          => 'name',
            'quality'       => 'quality',
            'dimension'     => 'dimension',
            'hsn_sac_code'  => 'hsn_sac_code',
            'gst_rate'      => 'gst_rate',
            'sale_price'    => 'sale_price',
            'current_stock' => 'current_stock',
        ], fn($q) => $q->orderBy('name'));
        $products = $products->paginate(25)->withQueryString();

        $count = Product::where('tenant_id', $tenantId)->count();

        return view('accounting.products.index', compact('products', 'search', 'count'));
    }

    public function create()
    {
        return view('accounting.products.form', ['product' => new Product(['unit' => 'nos', 'type' => 'product'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['tenant_id'] = auth()->user()->tenant_id;
        $product = Product::create($data);
        $this->learn($product);

        return redirect()->route('accounting.products.index')->with('success', "“{$product->name}” added to inventory.");
    }

    public function edit(Product $product)
    {
        abort_if($product->tenant_id !== auth()->user()->tenant_id, 403);
        return view('accounting.products.form', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        abort_if($product->tenant_id !== auth()->user()->tenant_id, 403);
        $product->update($this->validated($request));
        $this->learn($product);

        return redirect()->route('accounting.products.index')->with('success', "“{$product->name}” updated.");
    }

    public function destroy(Product $product)
    {
        abort_if($product->tenant_id !== auth()->user()->tenant_id, 403);
        $product->delete();

        return redirect()->route('accounting.products.index')->with('success', 'Product removed.');
    }

    /** JSON: GST rate + description for an HSN/SAC — powers the auto-map. */
    public function hsnLookup(Request $request)
    {
        $code = trim((string) $request->get('code', ''));
        $rate = $this->rateFor($code);
        $row  = $code !== '' ? (HsnSacCode::where('code', $code)->first()
                ?? (strlen($code) > 4 ? HsnSacCode::where('code', substr($code, 0, 4))->first() : null)) : null;

        return response()->json([
            'code'        => $code,
            'rate'        => $rate,
            'description' => $row->description ?? null,
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'sku'            => 'nullable|string|max:60',
            'type'           => 'required|in:product,service',
            'dimension'      => 'nullable|string|max:255',
            'quality'        => 'nullable|string|max:255',
            'unit'           => 'nullable|string|max:20',
            'hsn_sac_code'   => 'nullable|string|max:15',
            'gst_rate'       => 'nullable|numeric|min:0|max:100',
            'sale_price'     => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'track_inventory' => 'nullable|boolean',
            'opening_stock'  => 'nullable|numeric',
        ]);

        $data['unit'] = $data['unit'] ?: 'nos';
        $data['track_inventory'] = $request->boolean('track_inventory');
        $data['is_active'] = true;
        $data['opening_stock'] = $data['opening_stock'] ?? 0;
        $data['current_stock'] = $data['opening_stock'];

        // HSN auto-map: fill the GST rate from the HSN/SAC master when left blank.
        if (($data['gst_rate'] ?? null) === null && ! empty($data['hsn_sac_code'])) {
            $data['gst_rate'] = $this->rateFor($data['hsn_sac_code']);
        }

        return $data;
    }

    /** Combined GST rate for an HSN/SAC from the master (exact, then 4-digit chapter). */
    private function rateFor(string $code): ?float
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }
        $row = HsnSacCode::where('code', $code)->first()
            ?? (strlen($code) > 4 ? HsnSacCode::where('code', substr($code, 0, 4))->first() : null);

        return $row ? (float) $row->igst_rate : null;
    }

    /** Feed the product into the scan-learning memory so bills auto-map to it too. */
    private function learn(Product $product): void
    {
        if ($product->name && $product->hsn_sac_code) {
            TenantItem::learn($product->tenant_id, $product->name, $product->hsn_sac_code, $product->gst_rate, $product->unit);
        }
    }
}
