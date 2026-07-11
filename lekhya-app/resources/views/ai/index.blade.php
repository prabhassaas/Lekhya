@extends('layouts.app')
@section('title', 'AI Assistant')
@section('page-title', 'Lekhya AI Assistant')

@section('content')
<div class="py-6 space-y-8" x-data="aiAssistant()">

  {{-- Status Banner --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">AI Assistant</h1>
      <p class="text-sm text-gray-500 mt-1">AI proposes — you approve. Nothing posts automatically.</p>
    </div>
    <div class="flex items-center space-x-2 px-4 py-2 rounded-full border text-sm font-medium
      {{ $aiOnline ? 'bg-green-50 border-green-200 text-green-700' : 'bg-amber-50 border-amber-200 text-amber-700' }}">
      <span class="w-2 h-2 rounded-full {{ $aiOnline ? 'bg-green-500' : 'bg-amber-400' }}"></span>
      @if($aiOnline)
        <span>{{ ucfirst($driverName) }} · Online</span>
      @else
        <span>Mock mode · Ollama offline</span>
      @endif
    </div>
  </div>

  @if(!$aiOnline && $driverName === 'mock')
  <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
    <strong>Ollama not detected.</strong>
    Running in mock mode — results are sample data.
    <a href="{{ route('help.local-llm') }}" class="underline font-medium ml-1">Install Ollama →</a>
    Set <code class="bg-amber-100 px-1 rounded">AI_DRIVER=ollama</code> in your <code class="bg-amber-100 px-1 rounded">.env</code> once installed.
  </div>
  @endif

  {{-- Flash messages --}}
  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ $errors->first() }}</div>
  @endif

  {{-- Tab bar --}}
  <div class="border-b border-gray-200">
    <nav class="flex space-x-1 -mb-px">
      @foreach([['extract','Invoice Extraction','fa-file-invoice'], ['query','Ask a Question','fa-comments'], ['code','Auto Account Coding','fa-tag'], ['pending','Pending Review ('.$pending->total().')','fa-clock'], ['history','History','fa-history']] as [$tab,$label,$icon])
      <button @click="activeTab = '{{ $tab }}'"
        :class="activeTab === '{{ $tab }}' ? 'border-navy-600 text-navy-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
        class="flex items-center space-x-2 px-4 py-3 border-b-2 text-sm font-medium whitespace-nowrap transition-colors">
        <i class="fa {{ $icon }}"></i><span>{{ $label }}</span>
      </button>
      @endforeach
    </nav>
  </div>

  {{-- ─────────────────────────────────────────────────── --}}
  {{-- TAB: Invoice Extraction                             --}}
  {{-- ─────────────────────────────────────────────────── --}}
  <div x-show="activeTab === 'extract'" x-cloak>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {{-- Upload card --}}
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-1">Extract Invoice Data</h2>
        <p class="text-sm text-gray-500 mb-5">Upload a PDF or image — AI reads and structures the fields for you.</p>

        <form action="{{ route('ai.extract') }}" method="POST" enctype="multipart/form-data"
              x-data="{ dragging: false, file: null }"
              @dragover.prevent="dragging=true" @dragleave.prevent="dragging=false"
              @drop.prevent="dragging=false; file=$event.dataTransfer.files[0]; $refs.fileInput.files=$event.dataTransfer.files">
          @csrf
          <div class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-colors"
               :class="dragging ? 'border-blue-400 bg-blue-50' : 'border-gray-300 hover:border-gray-400'"
               @click="$refs.fileInput.click()">
            <i class="fa fa-cloud-arrow-up text-3xl mb-3 text-gray-400"></i>
            <p class="text-sm text-gray-600 font-medium" x-text="file ? file.name : 'Click or drag & drop'"></p>
            <p class="text-xs text-gray-400 mt-1">PDF, PNG, JPG — max 10 MB</p>
          </div>
          <input type="file" name="file" x-ref="fileInput" class="hidden" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/*"
                 @change="file=$event.target.files[0]">

          <button type="submit" x-show="file"
            class="mt-4 w-full py-2.5 bg-navy-600 text-white rounded-lg text-sm font-semibold hover:bg-navy-700 transition-colors">
            <i class="fa fa-brain mr-2"></i>Extract Invoice
          </button>
        </form>

        {{-- Camera capture — separate form so the empty input never clobbers the upload above --}}
        <form action="{{ route('ai.extract') }}" method="POST" enctype="multipart/form-data" class="mt-3">
          @csrf
          <label class="flex items-center justify-center gap-2 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:border-navy-400 cursor-pointer">
            <i class="fa fa-camera"></i> Take a photo instead
            <input type="file" name="file" class="hidden" accept="image/*" capture="environment" onchange="this.form.submit()">
          </label>
        </form>
      </div>

      {{-- How it works --}}
      <div class="bg-gradient-to-br from-navy-50 to-blue-50 rounded-xl border border-navy-100 p-6">
        <h3 class="font-semibold text-navy-800 mb-4">How AI Extraction Works</h3>
        <ol class="space-y-3 text-sm text-navy-700">
          <li class="flex items-start space-x-3">
            <span class="w-6 h-6 rounded-full bg-navy-600 text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
            <span>Upload your purchase invoice (PDF or scan)</span>
          </li>
          <li class="flex items-start space-x-3">
            <span class="w-6 h-6 rounded-full bg-navy-600 text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
            <span>AI reads the text, extracts fields: vendor, GSTIN, HSN, line items, GST breakup</span>
          </li>
          <li class="flex items-start space-x-3">
            <span class="w-6 h-6 rounded-full bg-navy-600 text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
            <span>A suggestion appears in Pending Review — you verify and approve</span>
          </li>
          <li class="flex items-start space-x-3">
            <span class="w-6 h-6 rounded-full bg-navy-600 text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
            <span>Approval pre-fills the invoice form — AI never posts to the ledger directly</span>
          </li>
        </ol>
        <div class="mt-5 pt-4 border-t border-navy-200">
          <p class="text-xs text-navy-600"><strong>Tip:</strong> For best results, use a clear PDF. Scanned images work with llava model.</p>
        </div>
      </div>
    </div>
  </div>

  {{-- ─────────────────────────────────────────────────── --}}
  {{-- TAB: Natural Language Query                         --}}
  {{-- ─────────────────────────────────────────────────── --}}
  <div x-show="activeTab === 'query'" x-cloak>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-2xl">
      <h2 class="text-lg font-semibold text-gray-800 mb-1">Ask Your Books</h2>
      <p class="text-sm text-gray-500 mb-5">Ask any accounting question in plain English or Hindi. AI computes the answer from your data.</p>

      <div class="flex space-x-3">
        <input x-model="nlQuery" type="text" placeholder="e.g. What are my total sales this month?"
          class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-navy-300 focus:border-navy-400 outline-none"
          @keyup.enter="runQuery()">
        <button @click="runQuery()" :disabled="!nlQuery.trim() || nlLoading"
          class="px-5 py-2.5 bg-navy-600 text-white rounded-lg text-sm font-semibold hover:bg-navy-700 disabled:opacity-50 transition-colors">
          <i class="fa fa-search mr-1" x-show="!nlLoading"></i>
          <i class="fa fa-spinner fa-spin mr-1" x-show="nlLoading"></i>
          Ask
        </button>
      </div>

      {{-- Sample queries --}}
      <div class="mt-4 flex flex-wrap gap-2">
        @foreach(['Total sales this month','Outstanding receivables','GST liability this quarter','How many invoices this year?','Top expenses last month'] as $q)
        <button @click="nlQuery='{{ $q }}'; runQuery()"
          class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-navy-100 text-gray-600 hover:text-navy-700 rounded-full border border-gray-200 transition-colors">
          {{ $q }}
        </button>
        @endforeach
      </div>

      {{-- Result --}}
      <div x-show="nlResult" x-cloak class="mt-6 p-5 bg-gradient-to-br from-navy-50 to-blue-50 rounded-xl border border-navy-100">
        <template x-if="nlResult && !nlResult.error">
          <div>
            <p class="text-xs text-navy-500 font-medium uppercase tracking-wide mb-1" x-text="nlResult.period_label || 'Result'"></p>
            <p class="text-3xl font-bold text-navy-800" x-text="formatValue(nlResult)"></p>
            <p class="text-sm text-navy-600 mt-2" x-text="nlResult.description"></p>
            <p class="text-xs text-navy-400 mt-3" x-text="'From ' + (nlResult.date_from || '') + ' to ' + (nlResult.date_to || '')"></p>
            <p x-show="nlResult.note" class="text-xs text-amber-600 mt-2" x-text="nlResult.note"></p>
          </div>
        </template>
        <template x-if="nlResult && nlResult.error">
          <div class="text-red-600 text-sm" x-text="nlResult.error"></div>
        </template>
      </div>
    </div>
  </div>

  {{-- ─────────────────────────────────────────────────── --}}
  {{-- TAB: Auto Account Coding                            --}}
  {{-- ─────────────────────────────────────────────────── --}}
  <div x-show="activeTab === 'code'" x-cloak>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-2xl">
      <h2 class="text-lg font-semibold text-gray-800 mb-1">Auto Account Coding</h2>
      <p class="text-sm text-gray-500 mb-5">Describe a transaction — AI suggests the right ledger account from your Indian CoA.</p>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Transaction Description</label>
          <input x-model="acDesc" type="text" placeholder="e.g. Office printer paper from Staples"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-navy-300 outline-none">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Amount (₹)</label>
            <input x-model="acAmount" type="number" min="0" step="0.01" placeholder="5000"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-navy-300 outline-none">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Vendor / Party</label>
            <input x-model="acVendor" type="text" placeholder="Vendor name (optional)"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-navy-300 outline-none">
          </div>
        </div>
        <button @click="suggestAccount()" :disabled="!acDesc.trim() || acLoading"
          class="w-full py-2.5 bg-navy-600 text-white rounded-lg text-sm font-semibold hover:bg-navy-700 disabled:opacity-50 transition-colors">
          <i class="fa fa-tag mr-2" x-show="!acLoading"></i>
          <i class="fa fa-spinner fa-spin mr-2" x-show="acLoading"></i>
          Suggest Account
        </button>
      </div>

      {{-- Account suggestion result --}}
      <div x-show="acResult" x-cloak class="mt-6">
        <template x-if="acResult && !acResult.error">
          <div class="border border-gray-200 rounded-xl overflow-hidden">
            <div class="bg-navy-600 px-5 py-3 flex items-center justify-between">
              <div>
                <p class="text-white font-semibold" x-text="acResult.account_name"></p>
                <p class="text-navy-200 text-xs capitalize" x-text="acResult.account_type + ' account'"></p>
              </div>
              <div class="text-right">
                <span class="text-white font-bold text-lg" x-text="Math.round((acResult.confidence || 0) * 100) + '%'"></span>
                <p class="text-navy-200 text-xs">confidence</p>
              </div>
            </div>
            <div class="p-5 bg-white">
              <p class="text-sm text-gray-600 mb-3" x-text="acResult.reason"></p>
              <template x-if="acResult.alternatives && acResult.alternatives.length">
                <div>
                  <p class="text-xs font-medium text-gray-500 mb-2">Alternatives:</p>
                  <div class="flex flex-wrap gap-2">
                    <template x-for="alt in acResult.alternatives" :key="alt">
                      <span class="text-xs px-3 py-1 bg-gray-100 text-gray-600 rounded-full" x-text="alt"></span>
                    </template>
                  </div>
                </div>
              </template>
              <p x-show="acResult._mock" class="text-xs text-amber-500 mt-3">
                <i class="fa fa-triangle-exclamation mr-1"></i>Mock response — install Ollama for real suggestions
              </p>
            </div>
          </div>
        </template>
        <template x-if="acResult && acResult.error">
          <div class="text-red-600 text-sm p-4 bg-red-50 rounded-xl" x-text="acResult.error"></div>
        </template>
      </div>
    </div>
  </div>

  {{-- ─────────────────────────────────────────────────── --}}
  {{-- TAB: Pending Review                                 --}}
  {{-- ─────────────────────────────────────────────────── --}}
  <div x-show="activeTab === 'pending'" x-cloak>
    @if($pending->isEmpty())
      <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fa fa-check-circle text-4xl text-green-400 mb-3"></i>
        <p class="text-gray-500">No pending AI suggestions. Upload an invoice or ask a question to get started.</p>
      </div>
    @else
      <div class="space-y-4">
        @foreach($pending as $s)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 flex items-start justify-between border-b border-gray-100">
            <div class="flex items-center space-x-3">
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                @switch($s->type)
                  @case('extraction') bg-blue-100 text-blue-700 @break
                  @case('account_coding') bg-purple-100 text-purple-700 @break
                  @case('nl_query') bg-teal-100 text-teal-700 @break
                  @default bg-gray-100 text-gray-600
                @endswitch">
                {{ ucwords(str_replace('_', ' ', $s->type)) }}
              </span>
              <span class="text-sm text-gray-600">
                @if($s->type === 'extraction')
                  {{ $s->input_context['filename'] ?? 'Unknown file' }}
                @elseif($s->type === 'nl_query')
                  {{ Str::limit($s->input_context['query'] ?? '', 60) }}
                @else
                  {{ Str::limit($s->input_context['description'] ?? '', 60) }}
                @endif
              </span>
            </div>
            <span class="text-xs text-gray-400">{{ $s->created_at->diffForHumans() }}</span>
          </div>

          <div class="px-5 py-4">
            @if($s->type === 'extraction' && $s->suggestion)
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                <div><p class="text-xs text-gray-400">Invoice #</p><p class="font-medium">{{ $s->suggestion['invoice_number'] ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Date</p><p class="font-medium">{{ $s->suggestion['invoice_date'] ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Party</p><p class="font-medium">{{ Str::limit($s->suggestion['party_name'] ?? '—', 25) }}</p></div>
                <div><p class="text-xs text-gray-400">Total</p><p class="font-bold text-navy-700">₹{{ number_format($s->suggestion['total_amount'] ?? 0, 2) }}</p></div>
              </div>
              @if(!empty($s->suggestion['lines']))
              <div class="overflow-x-auto mb-4">
                <table class="w-full text-xs border-collapse">
                  <thead><tr class="bg-gray-50">
                    <th class="text-left p-2 border border-gray-200">Description</th>
                    <th class="text-right p-2 border border-gray-200">HSN/SAC</th>
                    <th class="text-right p-2 border border-gray-200">Qty</th>
                    <th class="text-right p-2 border border-gray-200">Rate</th>
                    <th class="text-right p-2 border border-gray-200">GST%</th>
                    <th class="text-right p-2 border border-gray-200">Amount</th>
                  </tr></thead>
                  <tbody>
                    @foreach($s->suggestion['lines'] ?? [] as $line)
                    <tr>
                      <td class="p-2 border border-gray-200">{{ $line['description'] ?? '' }}</td>
                      <td class="p-2 border border-gray-200 text-right">{{ $line['hsn_sac'] ?? '' }}</td>
                      <td class="p-2 border border-gray-200 text-right">{{ $line['quantity'] ?? 1 }}</td>
                      <td class="p-2 border border-gray-200 text-right">₹{{ number_format($line['rate'] ?? 0, 2) }}</td>
                      <td class="p-2 border border-gray-200 text-right">{{ $line['gst_rate'] ?? 0 }}%</td>
                      <td class="p-2 border border-gray-200 text-right font-medium">₹{{ number_format($line['amount'] ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              @endif
              <p class="text-xs text-gray-400">
                Confidence: <strong>{{ round(($s->suggestion['confidence'] ?? 0) * 100) }}%</strong>
                @if($s->suggestion['_mock'] ?? false) · <span class="text-amber-500">Mock data</span>@endif
              </p>

            @elseif($s->type === 'nl_query' && $s->suggestion)
              <div class="flex items-center space-x-6">
                <div>
                  <p class="text-xs text-gray-400">Result</p>
                  <p class="text-2xl font-bold text-navy-700">
                    @if(($s->suggestion['format'] ?? '') === 'currency')
                      ₹{{ number_format($s->suggestion['value'] ?? 0, 2) }}
                    @else
                      {{ $s->suggestion['value'] ?? '—' }}
                    @endif
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-400">Label</p>
                  <p class="font-medium text-gray-700">{{ $s->suggestion['label'] ?? '' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400">Period</p>
                  <p class="text-sm text-gray-600">{{ $s->suggestion['date_from'] ?? '' }} – {{ $s->suggestion['date_to'] ?? '' }}</p>
                </div>
              </div>

            @elseif($s->type === 'account_coding' && $s->suggestion)
              <div class="flex items-start space-x-4">
                <div class="flex-1">
                  <p class="font-semibold text-gray-800">{{ $s->suggestion['account_name'] ?? '' }}</p>
                  <p class="text-xs text-gray-500 capitalize">{{ $s->suggestion['account_type'] ?? '' }} · {{ round(($s->suggestion['confidence'] ?? 0)*100) }}% confidence</p>
                  <p class="text-sm text-gray-600 mt-1">{{ $s->suggestion['reason'] ?? '' }}</p>
                </div>
              </div>
            @endif
          </div>

          <div class="px-5 py-3 bg-gray-50 flex items-center justify-between">
            <span class="text-xs text-gray-400">Model: {{ $s->model_used ?? 'unknown' }} · Driver: {{ $s->model_metadata['driver'] ?? 'unknown' }}</span>
            <div class="flex space-x-2">
              <form action="{{ route('ai.reject', $s) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-1.5 border border-red-300 text-red-600 rounded-lg text-xs font-medium hover:bg-red-50 transition-colors">
                  <i class="fa fa-times mr-1"></i>Reject
                </button>
              </form>
              <form action="{{ route('ai.approve', $s) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-1.5 bg-green-600 text-white rounded-lg text-xs font-medium hover:bg-green-700 transition-colors">
                  <i class="fa fa-check mr-1"></i>Approve
                </button>
              </form>
            </div>
          </div>
        </div>
        @endforeach
        <div class="mt-4">{{ $pending->links() }}</div>
      </div>
    @endif
  </div>

  {{-- ─────────────────────────────────────────────────── --}}
  {{-- TAB: History                                        --}}
  {{-- ─────────────────────────────────────────────────── --}}
  <div x-show="activeTab === 'history'" x-cloak>
    @if($history->isEmpty())
      <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <i class="fa fa-history text-4xl text-gray-300 mb-3"></i>
        <p class="text-gray-500">No reviewed suggestions yet.</p>
      </div>
    @else
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Summary</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Reviewed</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @foreach($history as $s)
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                  {{ ucwords(str_replace('_', ' ', $s->type)) }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-700">
                @if($s->type === 'extraction')
                  {{ $s->input_context['filename'] ?? '' }} — ₹{{ number_format($s->suggestion['total_amount'] ?? 0, 2) }}
                @elseif($s->type === 'nl_query')
                  {{ Str::limit($s->input_context['query'] ?? '', 60) }}
                @else
                  {{ Str::limit($s->input_context['description'] ?? '', 60) }}
                @endif
              </td>
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $s->status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                  {{ ucfirst($s->status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-400 text-xs">{{ $s->reviewed_at?->diffForHumans() ?? '—' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
        <div class="px-4 py-3">{{ $history->links() }}</div>
      </div>
    @endif
  </div>

</div>
@endsection

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush

@push('scripts')
<script>
function aiAssistant() {
  return {
    activeTab: '{{ $pending->total() > 0 ? "pending" : "extract" }}',
    nlQuery: '',
    nlLoading: false,
    nlResult: null,
    acDesc: '',
    acAmount: '',
    acVendor: '',
    acLoading: false,
    acResult: null,

    async runQuery() {
      if (!this.nlQuery.trim() || this.nlLoading) return;
      this.nlLoading = true;
      this.nlResult  = null;
      try {
        const res = await fetch('{{ route("ai.query") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({ query: this.nlQuery }),
        });
        const data = await res.json();
        this.nlResult = data.result || data;
      } catch (e) {
        this.nlResult = { error: 'Request failed: ' + e.message };
      } finally {
        this.nlLoading = false;
      }
    },

    async suggestAccount() {
      if (!this.acDesc.trim() || this.acLoading) return;
      this.acLoading = true;
      this.acResult  = null;
      try {
        const res = await fetch('{{ route("ai.suggest-account") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({ description: this.acDesc, amount: this.acAmount || 0, vendor: this.acVendor }),
        });
        const data = await res.json();
        this.acResult = data.result || data;
      } catch (e) {
        this.acResult = { error: 'Request failed: ' + e.message };
      } finally {
        this.acLoading = false;
      }
    },

    formatValue(r) {
      if (!r) return '—';
      if (r.format === 'currency') return '₹' + Number(r.value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
      if (r.format === 'number')   return Number(r.value || 0).toLocaleString('en-IN');
      return r.value ?? '—';
    },
  };
}
</script>
@endpush
