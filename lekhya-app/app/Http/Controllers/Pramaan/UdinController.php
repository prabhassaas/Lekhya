<?php
namespace App\Http\Controllers\Pramaan;

use App\Http\Controllers\Controller;
use App\Models\UdinRegister;
use Illuminate\Http\Request;

class UdinController extends Controller
{
    public function index(Request $request)
    {
        $query = UdinRegister::where('tenant_id', auth()->user()->tenant_id)
            ->with('generatedBy');

        if ($search = $request->get('q')) {
            $query->where(fn($w) => $w->where('client_name', 'like', "%{$search}%")
                ->orWhere('udin', 'like', "%{$search}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        return view('pramaan.udin.index', [
            'udins'  => $query->latest()->paginate(20)->withQueryString(),
            'search' => $search,
            'status' => $request->get('status'),
        ]);
    }

    public function create()
    {
        return view('pramaan.udin.form', ['udin' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'udin'              => 'required|string|max:25|unique:udin_register,udin',
            'membership_number' => 'required|string|max:15',
            'document_type'     => 'required|string|max:100',
            'document_date'     => 'required|date',
            'client_name'       => 'required|string|max:255',
            'client_pan'        => 'nullable|string|max:10',
            'particulars'       => 'nullable|string',
        ]);

        UdinRegister::create(array_merge($data, [
            'tenant_id'    => auth()->user()->tenant_id,
            'status'       => 'generated',
            'generated_by' => auth()->id(),
        ]));

        return redirect()->route('pramaan.udin.index')->with('success', 'UDIN registered successfully.');
    }

    public function show($id)
    {
        return view('pramaan.udin.show', ['udin' => $this->find($id)]);
    }

    public function edit($id)
    {
        return view('pramaan.udin.form', ['udin' => $this->find($id)]);
    }

    public function update(Request $request, $id)
    {
        $udin = $this->find($id);
        $data = $request->validate([
            'membership_number' => 'required|string|max:15',
            'document_type'     => 'required|string|max:100',
            'document_date'     => 'required|date',
            'client_name'       => 'required|string|max:255',
            'client_pan'        => 'nullable|string|max:10',
            'particulars'       => 'nullable|string',
        ]);
        $udin->update($data);

        return redirect()->route('pramaan.udin.show', $udin)->with('success', 'UDIN updated.');
    }

    public function destroy($id)
    {
        $this->find($id)->update(['status' => 'revoked', 'revoked_at' => now()]);

        return redirect()->route('pramaan.udin.index')->with('success', 'UDIN revoked.');
    }

    private function find($id): UdinRegister
    {
        return UdinRegister::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
    }
}
