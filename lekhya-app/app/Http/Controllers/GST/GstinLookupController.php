<?php

namespace App\Http\Controllers\GST;

use App\Http\Controllers\Controller;
use App\Services\GST\GstGateway;
use Illuminate\Http\Request;

/**
 * Public, throttled GSTIN lookup used by onboarding (registration auto-fill),
 * company settings, and the GSTIN verify tool. Read-only — it only returns
 * public GST-registry data through the GstGateway (Cashfree in production,
 * mock otherwise), never touching tenant data, so it is safe pre-auth.
 */
class GstinLookupController extends Controller
{
    public function __construct(private readonly GstGateway $gateway) {}

    public function verify(Request $request)
    {
        $gstin = strtoupper(trim((string) $request->query('gstin', $request->input('gstin', ''))));

        if (strlen($gstin) !== 15) {
            return response()->json(['valid' => false, 'message' => 'GSTIN must be 15 characters']);
        }

        // Never surface the raw provider payload to the browser.
        $result = $this->gateway->validateGstin($gstin);
        unset($result['raw']);

        return response()->json($result);
    }
}
