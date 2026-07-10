<?php
namespace App\Http\Controllers\Pramaan;

use App\Http\Controllers\Controller;
use App\Models\AuditReport;
use App\Models\WorkingPaper;
use Illuminate\Http\Request;

class WorkingPaperController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $query = WorkingPaper::where('tenant_id', $tenantId)->with(['uploader', 'auditReport']);

        if ($request->filled('audit_report_id')) {
            $query->where('audit_report_id', $request->get('audit_report_id'));
        }

        return view('pramaan.papers.index', [
            'papers'  => $query->latest()->paginate(30)->withQueryString(),
            'reports' => AuditReport::where('tenant_id', $tenantId)->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'category'        => 'nullable|string|max:50',
            'audit_report_id' => 'nullable|exists:audit_reports,id',
            'file'            => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('working-papers/' . auth()->user()->tenant_id, 'local');

        WorkingPaper::create([
            'tenant_id'       => auth()->user()->tenant_id,
            'audit_report_id' => $data['audit_report_id'] ?? null,
            'title'           => $data['title'],
            'category'        => $data['category'] ?? null,
            'file_path'       => $path,
            'file_name'       => $file->getClientOriginalName(),
            'mime_type'       => $file->getClientMimeType(),
            'uploaded_by'     => auth()->id(),
        ]);

        return back()->with('success', 'Working paper uploaded.');
    }

    public function destroy($id)
    {
        WorkingPaper::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();

        return back()->with('success', 'Working paper removed.');
    }
}
