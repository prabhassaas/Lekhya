@extends('layouts.app')
@section('title', 'Verify GSTIN')
@section('page-title', 'Verify GSTIN')

@section('content')
<div class="py-4 max-w-3xl" x-data="gstinVerify()">

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start justify-between gap-4 mb-1">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">GSTIN Verification</h2>
                <p class="text-sm text-gray-500">Look up any GSTIN against the live GST registry — legal name, status, address and more.</p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full font-medium whitespace-nowrap"
                  :class="provider === 'cashfree' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                  x-text="provider === 'cashfree' ? 'Cashfree · Live' : 'Demo mode'"></span>
        </div>

        <div class="flex gap-2 mt-4">
            <input x-model="gstin" @keyup.enter="verify()" maxlength="15" placeholder="27ABCDE1234F1Z5"
                   class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-mono uppercase tracking-wide focus:ring-2 focus:ring-navy-300 outline-none"
                   style="text-transform:uppercase">
            <button @click="verify()" :disabled="loading || gstin.length !== 15"
                    class="px-5 py-2.5 bg-navy-600 hover:bg-navy-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 whitespace-nowrap">
                <i class="fa fa-magnifying-glass mr-1.5" x-show="!loading"></i>
                <i class="fa fa-spinner fa-spin mr-1.5" x-show="loading"></i>Verify
            </button>
        </div>

        {{-- Error --}}
        <div x-show="error" x-cloak class="mt-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm" x-text="error"></div>

        {{-- Result --}}
        <div x-show="result && result.valid" x-cloak class="mt-5 border border-gray-200 rounded-xl overflow-hidden">
            <div class="bg-green-50 border-b border-green-100 px-5 py-3 flex items-center gap-2">
                <i class="fa fa-circle-check text-green-600"></i>
                <span class="font-semibold text-gray-800" x-text="result && result.legal_name ? result.legal_name : 'Valid GSTIN'"></span>
                <span class="ml-auto text-xs px-2 py-0.5 rounded-full font-medium"
                      :class="statusActive ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
                      x-text="result && result.status ? result.status : ''"></span>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 p-5 text-sm">
                <template x-for="row in rows" :key="row.label">
                    <div class="flex gap-2" x-show="row.value">
                        <dt class="text-gray-400 w-32 flex-shrink-0" x-text="row.label"></dt>
                        <dd class="text-gray-800" :class="row.mono ? 'font-mono' : ''" x-text="row.value"></dd>
                    </div>
                </template>
            </dl>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-3">
        <i class="fa fa-circle-info mr-1"></i>Uses the same verification that auto-fills your company details at sign-up. Configure Cashfree credentials to switch from demo to live data.
    </p>
</div>

@push('scripts')
<script>
function gstinVerify() {
  return {
    gstin: '', loading: false, error: '', result: null, provider: 'mock',
    get statusActive() { return this.result && /active|act/i.test(this.result.status || ''); },
    get rows() {
      const r = this.result || {};
      return [
        { label: 'GSTIN',        value: r.gstin, mono: true },
        { label: 'Legal name',   value: r.legal_name },
        { label: 'Trade name',   value: r.trade_name },
        { label: 'PAN',          value: r.pan, mono: true },
        { label: 'Taxpayer type',value: r.taxpayer_type },
        { label: 'Constitution', value: r.constitution },
        { label: 'Registered on',value: r.registration_date },
        { label: 'State code',   value: r.state_code, mono: true },
        { label: 'Address',      value: r.address },
      ];
    },
    async verify() {
      const g = (this.gstin || '').trim().toUpperCase();
      this.gstin = g;
      if (g.length !== 15) { this.error = 'GSTIN must be 15 characters'; return; }
      this.loading = true; this.error = ''; this.result = null;
      try {
        const res = await fetch('{{ route('gstin.verify') }}?gstin=' + encodeURIComponent(g));
        const data = await res.json();
        this.provider = data.provider || 'mock';
        if (data.valid) { this.result = data; }
        else { this.error = data.message || 'Invalid GSTIN — check and try again'; }
      } catch (e) {
        this.error = 'Could not reach the verification service. Try again.';
      } finally { this.loading = false; }
    },
  };
}
</script>
@endpush
@endsection
