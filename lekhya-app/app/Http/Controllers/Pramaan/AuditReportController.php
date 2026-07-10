<?php
namespace App\Http\Controllers\Pramaan;

use App\Http\Controllers\Controller;
use App\Models\AuditReport;
use App\Models\DscCertificate;
use App\Models\UdinRegister;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuditReportController extends Controller
{
    private const FORM_TYPES = [
        '3CA'          => 'Form 3CA — Tax Audit (audited under other law)',
        '3CB'          => 'Form 3CB — Tax Audit (not audited under other law)',
        '3CD'          => 'Form 3CD — Statement of Particulars',
        '3CEB'         => 'Form 3CEB — Transfer Pricing',
        'Schedule_III' => 'Statutory Audit (Schedule III)',
    ];

    public function index()
    {
        return view('pramaan.audit.index', [
            'reports' => AuditReport::where('tenant_id', auth()->user()->tenant_id)
                ->with(['preparer', 'reviewer', 'signer', 'udin'])
                ->latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return view('pramaan.audit.form', [
            'report'    => null,
            'formTypes' => self::FORM_TYPES,
            'team'      => $this->team(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'form_type'      => 'required|in:' . implode(',', array_keys(self::FORM_TYPES)),
            'financial_year' => 'required|string|max:7',
            'client_name'    => 'required|string|max:255',
            'client_pan'     => 'nullable|string|max:10',
            'reviewer_id'    => 'nullable|exists:users,id',
            'signer_id'      => 'nullable|exists:users,id',
            'observations'   => 'nullable|string',
        ]);

        $report = AuditReport::create([
            'tenant_id'   => auth()->user()->tenant_id,
            'form_type'   => $data['form_type'],
            'financial_year' => $data['financial_year'],
            'status'      => 'draft',
            'preparer_id' => auth()->id(),
            'reviewer_id' => $data['reviewer_id'] ?? null,
            'signer_id'   => $data['signer_id'] ?? null,
            'report_data' => [
                'client_name'  => $data['client_name'],
                'client_pan'   => $data['client_pan'] ?? null,
                'observations' => $data['observations'] ?? null,
            ],
        ]);

        return redirect()->route('pramaan.audit-reports.show', $report)->with('success', 'Audit report created as draft.');
    }

    public function show($id)
    {
        $report = $this->find($id);

        return view('pramaan.audit.show', [
            'report'    => $report->load(['preparer', 'reviewer', 'signer', 'udin', 'dsc', 'workingPapers.uploader']),
            'formTypes' => self::FORM_TYPES,
            'udins'     => UdinRegister::where('tenant_id', auth()->user()->tenant_id)->where('status', 'generated')->latest()->get(),
            'dscs'      => DscCertificate::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->get(),
        ]);
    }

    public function edit($id)
    {
        $report = $this->find($id);
        abort_if($report->isLocked(), 403, 'Signed reports cannot be edited.');

        return view('pramaan.audit.form', [
            'report'    => $report,
            'formTypes' => self::FORM_TYPES,
            'team'      => $this->team(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $report = $this->find($id);
        abort_if($report->isLocked(), 403, 'Signed reports cannot be edited.');

        $data = $request->validate([
            'form_type'      => 'required|in:' . implode(',', array_keys(self::FORM_TYPES)),
            'financial_year' => 'required|string|max:7',
            'client_name'    => 'required|string|max:255',
            'client_pan'     => 'nullable|string|max:10',
            'reviewer_id'    => 'nullable|exists:users,id',
            'signer_id'      => 'nullable|exists:users,id',
            'observations'   => 'nullable|string',
        ]);

        $report->update([
            'form_type'      => $data['form_type'],
            'financial_year' => $data['financial_year'],
            'reviewer_id'    => $data['reviewer_id'] ?? null,
            'signer_id'      => $data['signer_id'] ?? null,
            'report_data'    => array_merge($report->report_data ?? [], [
                'client_name'  => $data['client_name'],
                'client_pan'   => $data['client_pan'] ?? null,
                'observations' => $data['observations'] ?? null,
            ]),
        ]);

        return redirect()->route('pramaan.audit-reports.show', $report)->with('success', 'Audit report updated.');
    }

    public function transition(Request $request, $id)
    {
        $report = $this->find($id);
        $to = $request->validate(['to' => 'required|in:under_review,draft,signed,filed'])['to'];

        $allowed = [
            'draft'        => ['under_review'],
            'under_review' => ['draft', 'signed'],
            'signed'       => ['filed'],
            'filed'        => [],
        ];

        if (! in_array($to, $allowed[$report->status] ?? [], true)) {
            throw ValidationException::withMessages(['to' => "Cannot move from {$report->status} to {$to}."]);
        }

        $payload = ['status' => $to];

        if ($to === 'signed') {
            $signData = $request->validate([
                'udin_id' => 'required|exists:udin_register,id',
                'dsc_id'  => 'required|exists:dsc_certificates,id',
            ]);
            $payload['udin_id']   = $signData['udin_id'];
            $payload['dsc_id']    = $signData['dsc_id'];
            $payload['signer_id'] = $report->signer_id ?? auth()->id();
            $payload['signed_at'] = now();
        }

        $report->update($payload);

        return back()->with('success', 'Audit report moved to ' . str_replace('_', ' ', $to) . '.');
    }

    public function destroy($id)
    {
        $report = $this->find($id);
        abort_if($report->isLocked(), 403, 'Signed reports cannot be deleted.');
        $report->delete();

        return redirect()->route('pramaan.audit-reports.index')->with('success', 'Draft audit report deleted.');
    }

    private function team()
    {
        return User::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->orderBy('name')->get();
    }

    private function find($id): AuditReport
    {
        return AuditReport::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
    }
}
