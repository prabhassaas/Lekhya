<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\GST\GstConnection;
use App\Services\GST\GstGateway;
use Illuminate\Http\Request;

class GstConnectionController extends Controller
{
    public function __construct(private GstConnection $conn) {}

    public function edit()
    {
        $tenant = auth()->user()->tenant;

        return view('settings.gst', [
            'setting'   => $this->conn->setting(),
            'tenant'    => $tenant,
            'entitled'  => $tenant->gstFilingEnabled(),
            'connected' => $tenant->gstConnected(),
            'used'      => $tenant->gstFilingsUsed(),
            'limit'     => $tenant->gstFilingLimit(),
            'unlimited' => $tenant->gstFilingsUnlimited(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->tenant->gstFilingEnabled(), 403, 'GST filing is not included in your current plan.');

        $data = $request->validate([
            'gstin'             => 'required|string|size:15',
            'environment'       => 'required|in:sandbox,production',
            'gsp'               => 'nullable|string|max:30',
            'einvoice_username' => 'nullable|string|max:120',
            'einvoice_password' => 'nullable|string|max:255',
            'ewb_username'      => 'nullable|string|max:120',
            'ewb_password'      => 'nullable|string|max:255',
            'returns_username'  => 'nullable|string|max:120',
        ]);

        $setting = $this->conn->setting();
        $setting->tenant_id         = auth()->user()->tenant_id;
        $setting->gstin             = strtoupper($data['gstin']);
        $setting->environment       = $data['environment'];
        $setting->gsp               = $data['gsp'] ?? null;
        $setting->einvoice_username = $data['einvoice_username'] ?? null;
        $setting->ewb_username      = $data['ewb_username'] ?? null;
        $setting->returns_username  = $data['returns_username'] ?? null;

        // Passwords are only overwritten when a new value is typed — the form
        // never echoes the stored secret back, so a blank field means "keep".
        if (filled($data['einvoice_password'] ?? null)) {
            $setting->einvoice_password = $data['einvoice_password'];
        }
        if (filled($data['ewb_password'] ?? null)) {
            $setting->ewb_password = $data['ewb_password'];
        }
        $setting->save();

        $connected = $setting->hasCredentials('einvoice') || $setting->hasCredentials('ewb');
        $setting->status = $connected ? 'connected' : 'disconnected';
        if ($connected && ! $setting->connected_at) {
            $setting->connected_at = now();
        }
        $setting->save();

        return redirect()->route('settings.gst')->with(
            'success',
            $connected
                ? 'GST connected — e-invoices and returns for this company will run under your own GSTIN.'
                : 'Saved. Add your e-Invoice or e-Way Bill API credentials to finish connecting.'
        );
    }

    /** Verify the GSTIN is real & reachable (central Cashfree lookup). */
    public function test(GstGateway $gateway)
    {
        $setting = $this->conn->setting();
        if (! filled($setting->gstin)) {
            return back()->with('error', 'Enter and save your GSTIN first.');
        }

        $result = $gateway->validateGstin($setting->gstin);
        $ok = (bool) ($result['valid'] ?? false);
        $setting->exists ? $setting->update(['last_verified_at' => now()]) : null;

        return back()->with(
            $ok ? 'success' : 'error',
            $ok
                ? 'GSTIN verified. Once your GSP credentials go live, filing will run under this identity.'
                : ('Could not verify that GSTIN — ' . ($result['message'] ?? 'check it and try again') . '.')
        );
    }

    public function disconnect()
    {
        $setting = $this->conn->setting();
        if ($setting->exists) {
            $setting->update([
                'status'            => 'disconnected',
                'einvoice_password' => null,
                'ewb_password'      => null,
            ]);
        }

        return redirect()->route('settings.gst')->with('success', 'GST disconnected and stored credentials cleared.');
    }
}
