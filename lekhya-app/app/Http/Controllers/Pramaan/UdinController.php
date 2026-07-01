<?php
namespace App\Http\Controllers\Pramaan;
use App\Http\Controllers\Controller;
use App\Models\UdinRegister;
use Illuminate\Http\Request;
class UdinController extends Controller {
    public function index() { return view('pramaan.udin.index', ['udins' => UdinRegister::where('tenant_id', auth()->user()->tenant_id)->latest()->paginate(20)]); }
    public function create() { return view('pramaan.udin.form'); }
    public function store(Request $request) { $request->validate(['udin'=>'required|string|max:25','membership_number'=>'required','document_type'=>'required','document_date'=>'required|date','client_name'=>'required']); UdinRegister::create(array_merge($request->all(), ['tenant_id'=>auth()->user()->tenant_id,'generated_by'=>auth()->id()])); return redirect()->route('pramaan.udin.index')->with('success','UDIN registered.'); }
    public function show($id) { return view('pramaan.udin.show', ['udin' => UdinRegister::findOrFail($id)]); }
    public function edit($id) { return view('pramaan.udin.form', ['udin' => UdinRegister::findOrFail($id)]); }
    public function update(Request $request, $id) { return redirect()->route('pramaan.udin.index'); }
    public function destroy($id) { UdinRegister::findOrFail($id)->update(['status'=>'revoked']); return back()->with('success','UDIN revoked.'); }
}
