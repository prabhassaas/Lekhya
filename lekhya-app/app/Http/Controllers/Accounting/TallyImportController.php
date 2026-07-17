<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use App\Models\TallyImport;
use App\Services\Accounting\TallyMigrationService;
use Illuminate\Http\Request;

class TallyImportController extends Controller {
    public function __construct(private TallyMigrationService $migration) {}

    public function index() {
        $tenantId = auth()->user()->tenant_id;
        $imports = TallyImport::where('tenant_id', $tenantId)->latest()->paginate(10);
        return view('accounting.tally.index', compact('imports'));
    }

    public function upload(Request $request) {
        $request->validate(['file' => 'required|file|mimes:xml|max:51200']);
        $tenantId = auth()->user()->tenant_id;
        $file = $request->file('file');
        $path = $file->store("tally-imports/{$tenantId}");
        $import = TallyImport::create([
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
            'filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'uploaded',
        ]);
        try {
            $summary = $this->migration->parseAndPreview($import);
            return redirect()->route('accounting.tally.preview', $import)->with('success', "Parsed: {$summary['ledgers']} ledgers, {$summary['vouchers']} vouchers.");
        } catch (\Throwable $e) {
            $import->update(['status' => 'failed', 'errors' => ['parse' => $e->getMessage()]]);
            return back()->with('error', 'XML parse error: ' . $e->getMessage());
        }
    }

    public function preview(TallyImport $import) {
        abort_if($import->tenant_id !== auth()->user()->tenant_id, 403);
        return view('accounting.tally.preview', compact('import'));
    }

    public function run(TallyImport $import) {
        abort_if($import->tenant_id !== auth()->user()->tenant_id, 403);
        try {
            $result = $this->migration->import($import, auth()->user()->tenant_id, auth()->id());
            return redirect()->route('accounting.tally.index')
                ->with('success', "Imported {$result['imported']} records. Failed: {$result['failed']}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
