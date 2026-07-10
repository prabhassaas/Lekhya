<?php

// Seller identity for Prabhas SaaS subscription tax invoices.
// Set the real values via env on the server (especially the GSTIN).
return [
    'name'      => env('PRABHAS_NAME', 'Prabhas SaaS'),
    'legal_name'=> env('PRABHAS_LEGAL_NAME', 'Prabhas SaaS'),
    'gstin'     => env('PRABHAS_GSTIN', ''),           // set your real GSTIN
    'pan'       => env('PRABHAS_PAN', ''),
    'address'   => env('PRABHAS_ADDRESS', 'India'),
    'email'     => env('PRABHAS_EMAIL', 'support@prabhassaas.in'),
    'phone'     => env('PRABHAS_PHONE', ''),
    'website'   => env('PRABHAS_WEBSITE', 'https://prabhassaas.in'),
    'sac'       => env('PRABHAS_SAC', '997331'),        // licensing of software / SaaS
    'gst_rate'  => (float) env('PRABHAS_GST_RATE', 18), // % on SaaS subscriptions
    'state_code'=> env('PRABHAS_STATE_CODE', ''),
];
