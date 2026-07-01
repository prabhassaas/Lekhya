<?php
namespace App\Http\Controllers\Pramaan;
use App\Http\Controllers\Controller;
use App\Models\AuditReport;
use Illuminate\Http\Request;
class AuditReportController extends Controller {
    public function index() { return view('pramaan.audit.index', ['reports' => AuditReport::where('tenant_id', auth()->user()->tenant_id)->latest()->paginate(20)]); }
    public function create() { return view('pramaan.audit.form'); }
    public function store(Request $request) { return redirect()->route('pramaan.audit-reports.index')->with('success','Report created.'); }
    public function show($id) { return view('pramaan.audit.show', ['report' => AuditReport::findOrFail($id)]); }
    public function edit($id) { return view('pramaan.audit.form', ['report' => AuditReport::findOrFail($id)]); }
    public function update(Request $request, $id) { return redirect()->route('pramaan.audit-reports.index'); }
    public function destroy($id) { return redirect()->route('pramaan.audit-reports.index'); }
}
