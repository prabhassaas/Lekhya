<?php
namespace App\Http\Controllers\Pramaan;

use App\Http\Controllers\Controller;
use App\Models\DscCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DscController extends Controller
{
    public function index()
    {
        return view('pramaan.dsc.index', [
            'certificates' => DscCertificate::where('tenant_id', auth()->user()->tenant_id)
                ->orderBy('valid_to', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'holder_name' => 'required|string|max:255',
            'cn'          => 'required|string|max:255',
            'valid_from'  => 'required|date',
            'valid_to'    => 'required|date|after:valid_from',
            'certificate' => 'nullable|file|max:5120',
        ]);

        $path = 'metadata-only';
        if ($request->hasFile('certificate')) {
            $path = $request->file('certificate')->store('dsc/' . auth()->user()->tenant_id, 'local');
        }

        DscCertificate::create([
            'tenant_id'        => auth()->user()->tenant_id,
            'holder_name'      => $data['holder_name'],
            'cn'               => $data['cn'],
            'valid_from'       => $data['valid_from'],
            'valid_to'         => $data['valid_to'],
            'certificate_path' => $path,
            'is_active'        => true,
        ]);

        return back()->with('success', 'DSC certificate added to vault.');
    }

    public function destroy($id)
    {
        $cert = DscCertificate::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $cert->update(['is_active' => false]);

        return back()->with('success', 'DSC certificate deactivated.');
    }
}
