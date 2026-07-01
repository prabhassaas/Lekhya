@extends('layouts.marketing')
@section('title', 'GST API Integration Guide — Lekhya')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <nav class="text-sm text-gray-500 mb-8">
        <a href="{{ route('marketing.help') }}" class="hover:text-navy-600">Help</a> → <span class="text-gray-900">GST API Integration</span>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-4">GST API Integration Guide</h1>
    <p class="text-lg text-gray-600 mb-8">How to connect Lekhya to production GST APIs through a GSP gateway for e-invoicing, GSTIN validation, GSTR filing, and more.</p>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-8">
        <h3 class="font-semibold text-amber-900 mb-2"><i class="fa fa-triangle-exclamation mr-2"></i>Architecture Rule</h3>
        <p class="text-sm text-amber-800">Lekhya NEVER calls GST APIs directly. All calls go through a <strong>GstGateway interface</strong>. This lets you swap between the mock gateway (for testing) and a real GSP gateway (for production) by changing one line in config. You are never locked to a specific GSP provider.</p>
    </div>

    <div class="space-y-10">

        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">GST API Ecosystem — What You Need</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach([
                    ['GSP (GST Suvidha Provider)', 'Mandatory intermediary to access NIC/GSTN APIs. Examples: Karvy, Masters India, ClearTax, IRIS, Mastercard, E2I. Your company must register with one.', 'landmark', 'blue'],
                    ['GSTIN Validation', 'Free API via GST portal. Validates GSTIN format + returns legal name, trade name, status, registration date.', 'check-circle', 'green'],
                    ['e-Invoice (IRN Generation)', 'Available through GSP. Required if annual turnover > ₹5 crore (threshold configurable in Lekhya settings).', 'file-invoice', 'purple'],
                    ['e-Way Bill', 'Available through GSP. For movement of goods > ₹50,000. Auto-generated when e-invoice is created.', 'truck', 'orange'],
                    ['GSTR-1 Filing', 'File monthly/quarterly outward supply return via GSP or GST portal APIs.', 'upload', 'teal'],
                    ['GSTR-2B Pull', 'Pull auto-drafted ITC statement from GSTN via GSP. Used for GSTR-2B reconciliation.', 'download', 'navy'],
                ] as [$title, $desc, $icon, $color])
                <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                    <div class="flex items-center space-x-2 mb-2">
                        <i class="fa fa-{{ $icon }} text-{{ $color }}-600"></i>
                        <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
                    </div>
                    <p class="text-sm text-gray-600">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Step 1 — Choose & Register with a GSP</h2>
            <p class="text-gray-600 mb-4">Register your business with a GSP. Popular options:</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-gray-200 rounded-xl overflow-hidden">
                    <thead class="bg-gray-50"><tr><th class="text-left p-3">GSP</th><th class="p-3 text-left">Website</th><th class="p-3 text-left">Best For</th><th class="p-3 text-left">Pricing</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach([
                            ['Masters India', 'mastersindia.co', 'SME & Enterprise', '₹1-3/IRN'],
                            ['ClearTax', 'cleartax.in', 'CA Firms, Mid-Market', '₹2-4/IRN'],
                            ['IRIS Business', 'irisgst.com', 'Enterprise', 'Volume pricing'],
                            ['Karvy Computershare', 'karvy.com', 'Large Enterprise', 'Custom'],
                            ['E2I Technologies', 'e2i.in', 'SME', '₹1-2/IRN'],
                        ] as [$gsp, $url, $target, $price])
                        <tr><td class="p-3 font-medium">{{ $gsp }}</td><td class="p-3 text-blue-600">{{ $url }}</td><td class="p-3 text-gray-600">{{ $target }}</td><td class="p-3 text-gray-600">{{ $price }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Step 2 — Configure Lekhya .env</h2>
            <div class="bg-gray-900 text-green-400 rounded-xl p-5 font-mono text-sm">
                <p class="text-gray-400 mb-2"># In lekhya-app/.env</p>
                <p>GST_DRIVER=masters_india  # or: cleartax | iris | karvy | mock</p>
                <p class="mt-2">GST_CLIENT_ID=your-client-id</p>
                <p>GST_CLIENT_SECRET=your-client-secret</p>
                <p>GST_USERNAME=your-gstn-username</p>
                <p>GST_PASSWORD=your-gstn-password</p>
                <p class="mt-2 text-gray-400"># E-invoice threshold (turnover in crores)</p>
                <p>GST_EINVOICE_THRESHOLD_CRORE=5</p>
                <p class="mt-2 text-gray-400"># GSP base URLs (example: Masters India)</p>
                <p>GST_API_BASE=https://api.mastersindia.co/mastersindia</p>
                <p>GST_AUTH_URL=https://api.mastersindia.co/oauth/token</p>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Step 3 — Implement Your GSP Gateway Class</h2>
            <p class="text-gray-600 mb-4">Create a new class implementing the <code class="bg-gray-100 px-1 rounded">GstGateway</code> interface for your GSP:</p>
            <div class="bg-gray-900 text-green-400 rounded-xl p-5 font-mono text-sm overflow-x-auto">
                <p class="text-gray-500">// app/Services/GST/MastersIndiaGateway.php</p>
                <p class="text-blue-300 mt-2">class MastersIndiaGateway implements GstGateway {</p>
                <p class="ml-4">private string $accessToken;</p>
                <p class="ml-4 mt-2">public function generateIrn(array $payload): array {</p>
                <p class="ml-8">$token = $this->getAccessToken();</p>
                <p class="ml-8">$response = Http::withToken($token)</p>
                <p class="ml-12">->post(config('services.gst.base') . '/einvoice/generate', [</p>
                <p class="ml-16">'gstin' => config('services.gst.gstin'),</p>
                <p class="ml-16">'payload' => $payload,</p>
                <p class="ml-12">]);</p>
                <p class="ml-8">return $response->json();</p>
                <p class="ml-4">}</p>
                <p class="ml-4 mt-2">// ... implement all GstGateway methods</p>
                <p class="text-blue-300">}</p>
            </div>
            <p class="mt-3 text-sm text-gray-600">Then register in <code class="bg-gray-100 px-1 rounded">AppServiceProvider::register()</code>:</p>
            <div class="bg-gray-900 text-green-400 rounded-xl p-4 font-mono text-sm mt-2">
                <p>$this->app->singleton(GstGateway::class, function () {</p>
                <p class="ml-4">return match(config('services.gst.driver')) {</p>
                <p class="ml-8">'masters_india' => new MastersIndiaGateway(),</p>
                <p class="ml-8">'cleartax'      => new CleartaxGateway(),</p>
                <p class="ml-8">default         => new MockGstGateway(),</p>
                <p class="ml-4">};</p>
                <p>});</p>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">GST APIs Available in Lekhya</h2>
            <div class="space-y-3">
                @foreach([
                    ['GSTIN Validation', 'GET', '/gst/validate-gstin?gstin=29ABCDE1234F1Z5', 'Validates format + checksum + live status from GSTN'],
                    ['Generate IRN', 'POST', '/gst/e-invoice/{invoice}/generate', 'Builds e-invoice JSON, calls GSP, stores IRN + QR'],
                    ['Generate e-Way Bill', 'POST', '/gst/eway-bill/{invoice}/generate', 'Auto-creates EWB from invoice data'],
                    ['GSTR-1 Generate', 'POST', '/gst/gstr1/generate', 'Aggregates posted invoices into GSTR-1 JSON format'],
                    ['GSTR-1 File', 'POST', '/gst/gstr1/file', 'Pushes GSTR-1 JSON to GSTN via GSP. Returns ARN.'],
                    ['GSTR-2B Import', 'POST', '/gst/gstr2b/import', 'Upload 2B JSON from GST portal → stored for reconciliation'],
                    ['GSTR-2B Reconcile', 'GET', '/gst/gstr2b/reconcile', 'Match purchase invoices vs 2B. Flag mismatches.'],
                ] as [$name, $method, $path, $desc])
                <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                    <span class="text-xs font-bold px-2 py-0.5 rounded {{ $method === 'GET' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }} w-12 text-center shrink-0">{{ $method }}</span>
                    <code class="text-sm text-gray-700 font-mono">{{ $path }}</code>
                    <span class="text-sm text-gray-500 mt-1 sm:mt-0">{{ $desc }}</span>
                </div>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Testing in Sandbox Mode</h2>
            <div class="bg-gray-900 text-green-400 rounded-xl p-5 font-mono text-sm">
                <p class="text-gray-400 mb-2"># Use mock gateway (default in development)</p>
                <p>GST_DRIVER=mock</p>
                <p class="mt-3 text-gray-400"># For GSP sandbox (if your GSP provides one)</p>
                <p>GST_DRIVER=masters_india</p>
                <p>GST_API_BASE=https://sandbox-api.mastersindia.co</p>
                <p class="mt-3 text-gray-400"># Run e-invoice generation test</p>
                <p>php artisan gst:test-irn --invoice=1</p>
            </div>
        </section>
    </div>
</div>
@endsection
