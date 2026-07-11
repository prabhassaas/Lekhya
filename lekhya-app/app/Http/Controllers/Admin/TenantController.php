<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{FiscalYear, User};
use App\Services\Billing\SubscriptionInvoiceService;
use Illuminate\Http\Request;
class TenantController extends Controller {
    public function edit() { return view('settings.company', ['tenant' => auth()->user()->tenant]); }
    public function update(Request $request) {
        $request->validate(['name'=>'required','gstin'=>'nullable|size:15']);
        auth()->user()->tenant->update($request->only(['name','gstin','pan','phone','email','address','city','state','state_code','pincode']));
        return back()->with('success', 'Company details updated.');
    }
    public function users() { return view('settings.users', ['users' => User::where('tenant_id', auth()->user()->tenant_id)->get()]); }
    public function fiscalYears() { return view('settings.fiscal-years', ['fiscalYears' => FiscalYear::where('tenant_id', auth()->user()->tenant_id)->orderByDesc('start_date')->get()]); }

    public function storeFiscalYear(Request $request) {
        $data = $request->validate([
            'name'       => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);
        $tenantId = auth()->user()->tenant_id;
        $current  = $request->boolean('is_current');
        if ($current) {
            FiscalYear::where('tenant_id', $tenantId)->update(['is_current' => false]);
        }
        FiscalYear::create(array_merge($data, ['tenant_id' => $tenantId, 'is_current' => $current]));
        return back()->with('success', 'Fiscal year added.');
    }

    public function setCurrentFiscalYear(FiscalYear $fiscalYear) {
        abort_unless($fiscalYear->tenant_id === auth()->user()->tenant_id, 403);
        FiscalYear::where('tenant_id', $fiscalYear->tenant_id)->update(['is_current' => false]);
        $fiscalYear->update(['is_current' => true]);
        return back()->with('success', "Current fiscal year set to {$fiscalYear->name}.");
    }
    public function billing() { return view('settings.billing', ['tenant' => auth()->user()->tenant]); }

    // Sends a sample subscription invoice to the current user — lets an admin
    // verify SMTP delivery + the invoice/email format before going live.
    public function testInvoice(SubscriptionInvoiceService $svc) {
        $user = auth()->user();
        try {
            $svc->sendSample($user->tenant, $user->email);
            return back()->with('success', "Sample invoice emailed to {$user->email}. Check your inbox (and spam).");
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not send email: ' . $e->getMessage());
        }
    }
}
