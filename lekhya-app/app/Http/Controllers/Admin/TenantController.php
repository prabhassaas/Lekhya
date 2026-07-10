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
    public function fiscalYears() { return view('settings.fiscal-years', ['fiscalYears' => FiscalYear::where('tenant_id', auth()->user()->tenant_id)->get()]); }
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
