@extends('layouts.app')
@section('title', 'Add Company')
@section('page-title', 'Add Company')

@section('content')
<div class="py-4 max-w-xl" x-data="companyCreate()">
    <a href="{{ route('companies.index') }}" class="text-sm text-navy-600 hover:underline">← Back to companies</a>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mt-4">
        <h2 class="font-semibold text-gray-900 mb-1">Create a new company</h2>
        <p class="text-sm text-gray-500 mb-5">A fresh set of books with its own GSTIN. It's covered by your current subscription — no extra charge.</p>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('companies.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Company Name *</label>
                <input type="text" name="name" x-ref="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-navy-400 outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">GSTIN <span class="text-gray-400 normal-case">(optional)</span></label>
                <div class="flex gap-2">
                    <input type="text" name="gstin" x-ref="gstin" value="{{ old('gstin') }}" maxlength="15" placeholder="27ABCDE1234F1Z5"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:border-navy-400 outline-none">
                    <button type="button" @click="fetchGstin()" :disabled="busy"
                            class="px-3 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 whitespace-nowrap">
                        <i class="fa fa-wand-magic-sparkles mr-1" :class="busy && 'fa-spin'"></i>Fetch
                    </button>
                </div>
                <p class="text-xs mt-1" :class="msgOk ? 'text-green-600' : 'text-gray-400'" x-text="msg"></p>
            </div>
            <button type="submit" class="w-full py-2.5 rounded-lg bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold">
                <i class="fa fa-plus mr-1.5"></i>Create &amp; switch to it
            </button>
        </form>
    </div>
</div>

<script>
function companyCreate() {
    return {
        busy: false, msg: '', msgOk: false,
        async fetchGstin() {
            const g = (this.$refs.gstin.value || '').trim().toUpperCase();
            this.$refs.gstin.value = g;
            if (g.length !== 15) { this.msgOk = false; this.msg = 'Enter a 15-character GSTIN'; return; }
            this.busy = true; this.msg = '';
            try {
                const res = await fetch('{{ route('gstin.verify') }}?gstin=' + encodeURIComponent(g));
                const d = await res.json();
                if (d.valid && d.legal_name) {
                    this.msgOk = true; this.msg = '✓ ' + d.legal_name;
                    if (!this.$refs.name.value.trim()) this.$refs.name.value = d.legal_name;
                } else if (d.valid) {
                    this.msgOk = true; this.msg = d.message || 'GSTIN format is valid';
                } else {
                    this.msgOk = false; this.msg = d.message || 'Invalid GSTIN';
                }
            } catch (e) { this.msgOk = false; this.msg = 'Could not reach verification service'; }
            finally { this.busy = false; }
        },
    };
}
</script>
@endsection
