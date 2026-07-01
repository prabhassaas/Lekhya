@extends('layouts.marketing')
@section('title', 'Local LLM Setup for Lekhya AI ERP')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <nav class="text-sm text-gray-500 mb-8">
        <a href="{{ route('marketing.help') }}" class="hover:text-navy-600">Help</a> → <span class="text-gray-900">AI / LLM Setup</span>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-4">Setting Up Local LLM for Lekhya AI</h1>
    <p class="text-lg text-gray-600 mb-8">Run AI features on-premise with a local LLM. Your financial data never leaves your server.</p>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-8">
        <h3 class="font-semibold text-blue-900 mb-2"><i class="fa fa-shield-halved mr-2"></i>Why local LLM?</h3>
        <p class="text-sm text-blue-800">Financial data is sensitive. Using a local model means invoice content, GSTIN, and ledger data never goes to an external API. Suitable for CA firms, government clients, and privacy-first businesses.</p>
    </div>

    <div class="space-y-10">

        {{-- Recommended Models --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Recommended Models for ERP Use</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach([
                    ['Llama 3.2 (3B)', 'Best for invoice extraction + coding on CPU/low RAM. Runs on 4GB RAM.', 'llama3.2', 'Ollama', 'Recommended for small setups', 'green'],
                    ['Llama 3.1 (8B)', 'Better reasoning for complex queries. Needs 8GB+ RAM.', 'llama3.1', 'Ollama', 'Recommended for medium setups', 'blue'],
                    ['Mistral 7B', 'Excellent at structured output (JSON extraction). Good for invoice parsing.', 'mistral', 'Ollama', 'Great for data extraction tasks', 'purple'],
                    ['DeepSeek-R1 (7B)', 'Strong math reasoning — useful for reconciliation tasks.', 'deepseek-r1', 'Ollama', 'Best for reconciliation logic', 'orange'],
                ] as [$name, $desc, $model, $tool, $tag, $color])
                <div class="border border-{{ $color }}-200 rounded-xl p-4 bg-{{ $color }}-50">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-{{ $color }}-900">{{ $name }}</h3>
                        <span class="text-xs bg-{{ $color }}-100 text-{{ $color }}-700 px-2 py-0.5 rounded-full">{{ $tag }}</span>
                    </div>
                    <p class="text-sm text-{{ $color }}-800 mb-2">{{ $desc }}</p>
                    <code class="text-xs bg-white px-2 py-1 rounded border border-{{ $color }}-200 text-gray-700">ollama pull {{ $model }}</code>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Step 1: Install Ollama --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Step 1 — Install Ollama</h2>
            <div class="space-y-4">
                <div class="bg-gray-900 text-green-400 rounded-xl p-5 font-mono text-sm overflow-x-auto">
                    <p class="text-gray-400 mb-3"># Linux / Ubuntu (VPS / Hostinger Cloud)</p>
                    <p>curl -fsSL https://ollama.com/install.sh | sh</p>
                    <p class="mt-3 text-gray-400"># Verify installation</p>
                    <p>ollama --version</p>
                    <p class="mt-3 text-gray-400"># Pull recommended model for Lekhya</p>
                    <p>ollama pull llama3.2</p>
                    <p class="mt-3 text-gray-400"># Start Ollama service (or it auto-starts)</p>
                    <p>ollama serve</p>
                    <p class="text-gray-400 mt-1"># Default API: http://localhost:11434</p>
                </div>
            </div>
        </section>

        {{-- Step 2: Configure Lekhya --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Step 2 — Configure Lekhya .env</h2>
            <div class="bg-gray-900 text-green-400 rounded-xl p-5 font-mono text-sm">
                <p class="text-gray-400 mb-3"># In lekhya-app/.env</p>
                <p>AI_DRIVER=ollama</p>
                <p>AI_ENDPOINT=http://localhost:11434/api/generate</p>
                <p>AI_MODEL=llama3.2</p>
                <p>AI_MAX_TOKENS=2048</p>
                <p>AI_TEMPERATURE=0.1</p>
                <p class="text-gray-400 mt-3"># For cloud providers (fallback)</p>
                <p># AI_DRIVER=anthropic</p>
                <p># ANTHROPIC_API_KEY=sk-ant-...</p>
                <p># AI_MODEL=claude-haiku-4-5-20251001</p>
            </div>
        </section>

        {{-- Step 3: Lekhya AI Integration --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Step 3 — How Lekhya Calls the AI</h2>
            <p class="text-gray-600 mb-4">Lekhya uses a single <code class="bg-gray-100 px-1 rounded">AiProvider</code> interface. Switch models by changing .env — no code changes.</p>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Invoice Extraction Prompt (what Lekhya sends)</h3>
                <div class="bg-white border border-gray-200 rounded-lg p-4 text-sm text-gray-700 font-mono whitespace-pre-wrap">You are an Indian GST accounting assistant. Extract structured invoice data from the following text.

Return ONLY valid JSON with this structure:
{
  "invoice_number": "INV-001",
  "invoice_date": "2024-04-15",
  "vendor_name": "Supplier Pvt Ltd",
  "vendor_gstin": "29ABCDE1234F1Z5",
  "line_items": [
    {"description": "IT Services", "hsn_sac": "998314", "quantity": 1, "rate": 10000, "cgst_rate": 9, "sgst_rate": 9}
  ],
  "total_taxable": 10000,
  "total_gst": 1800,
  "total_amount": 11800
}

Invoice text:
{extracted_text_here}</div>
            </div>
        </section>

        {{-- Step 4: OCR for PDF/Image --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Step 4 — OCR for PDF/Image Invoices</h2>
            <div class="bg-gray-900 text-green-400 rounded-xl p-5 font-mono text-sm overflow-x-auto">
                <p class="text-gray-400 mb-2"># Install Tesseract OCR (Ubuntu)</p>
                <p>sudo apt-get install tesseract-ocr tesseract-ocr-hin</p>
                <p class="mt-3 text-gray-400"># Install PHP wrapper</p>
                <p>composer require thiagoalessio/tesseract_ocr</p>
                <p class="mt-3 text-gray-400"># For PDF to image conversion</p>
                <p>sudo apt-get install ghostscript imagemagick</p>
                <p>composer require spatie/pdf-to-image</p>
            </div>
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-xl text-sm text-yellow-800">
                <strong>Tip:</strong> For vision-capable models (LLaVA, Llama 3.2 Vision), you can skip OCR and send the image directly to the model. Enable this in .env: <code>AI_USE_VISION=true</code>
            </div>
        </section>

        {{-- Hardware requirements --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Hardware Requirements</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-gray-200 rounded-xl overflow-hidden">
                    <thead class="bg-gray-50"><tr><th class="text-left p-3">Setup</th><th class="p-3 text-left">RAM</th><th class="p-3 text-left">CPU/GPU</th><th class="p-3 text-left">Model</th><th class="p-3 text-left">Best For</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr><td class="p-3 font-medium">Minimal</td><td class="p-3">4GB</td><td class="p-3">Any modern CPU</td><td class="p-3">Llama 3.2 3B</td><td class="p-3">Basic extraction, small volumes</td></tr>
                        <tr class="bg-gray-50"><td class="p-3 font-medium">Recommended</td><td class="p-3">16GB</td><td class="p-3">CPU or GPU</td><td class="p-3">Llama 3.1 8B</td><td class="p-3">Production use, faster responses</td></tr>
                        <tr><td class="p-3 font-medium">High Volume</td><td class="p-3">32GB+</td><td class="p-3">GPU (NVIDIA)</td><td class="p-3">Llama 3.1 70B</td><td class="p-3">CA firms, bulk processing</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Predictions & Monitoring --}}
        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">AI Predictions & Activity Monitoring</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach([
                    ['Cash Flow Prediction', 'AI analyzes your AR/AP aging, seasonal patterns → predicts cash position 30/60/90 days out. View in Dashboard → AI Insights.'],
                    ['Spend Anomalies', 'AI monitors monthly vendor payments for outliers. "Transport costs 3x this month" → flagged for review.'],
                    ['GST Filing Reminders', 'AI predicts how much GST is due based on current-month postings. Alert before due date.'],
                    ['ITC Optimization', 'AI spots ITC that\'s about to expire (2-year limit) and flags invoices to claim before cutoff.'],
                ] as [$title, $desc])
                <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                    <h3 class="font-semibold text-gray-900 mb-1">{{ $title }}</h3>
                    <p class="text-sm text-gray-600">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
