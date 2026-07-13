@extends('layouts.app')
@section('title', ($editing ?? false) ? 'Edit Invoice' : 'New Invoice')
@section('page-title', (($editing ?? false) ? 'Edit ' : 'New ') . (($type ?? 'sales') === 'sales' ? 'Sales' : 'Purchase') . ' Invoice')

@section('content')
@php
    $hsnRates = ($hsnCodes ?? collect())->keyBy('code')->map(fn($h) => ['cgst' => (float) $h->cgst_rate, 'sgst' => (float) $h->sgst_rate, 'igst' => (float) $h->igst_rate]);
    // State from the field, else the GSTIN's first two digits — a blank tenant
    // state must not make everything look interstate.
    $stateOf = fn($code, $gstin) => trim((string) $code) ?: (strlen(trim((string) $gstin)) >= 2 ? substr(trim((string) $gstin), 0, 2) : null);
    $partyStates = ($parties ?? collect())->keyBy('id')->map(fn($p) => $stateOf($p->state_code, $p->gstin));
    $supplierState = $stateOf(($tenant ?? null)?->state_code, ($tenant ?? null)?->gstin);
    $prefill = $prefill ?? null;
    $initLines = $prefill['lines'] ?? [['description' => '', 'hsn_sac_code' => '', 'quantity' => 1, 'rate' => '', 'discount_percent' => 0]];
    $amberFields = collect($prefill['validation']['fields'] ?? [])->filter(fn($f) => $f['status'] === 'amber');
    $failedChecks = collect($prefill['validation']['checks'] ?? [])->filter(fn($c) => ! $c['ok']);
@endphp

@if($prefill && !($editing ?? false))
<div class="max-w-5xl mb-4 bg-amber-50 border border-amber-200 rounded-xl p-4">
    <div class="flex items-start gap-3">
        <i class="fa fa-wand-magic-sparkles text-amber-600 mt-0.5"></i>
        <div class="flex-1">
            <p class="font-semibold text-amber-900 text-sm">Pre-filled from AI invoice scan — please verify before saving.</p>
            @if(!$prefill['party_matched'])
            <p class="text-sm text-amber-800 mt-1">
                Vendor/customer <strong>“{{ $prefill['party_name'] ?: 'not detected' }}”</strong> isn’t in your parties yet — select the right one below{{ $prefill['party_name'] ? ' or add it' : '' }}.
            </p>
            @endif
            @if($amberFields->isNotEmpty() || $failedChecks->isNotEmpty())
            <div class="mt-2 text-xs text-amber-800 space-y-0.5">
                @foreach($failedChecks as $c)
                <div><i class="fa fa-triangle-exclamation mr-1"></i>{{ $c['label'] }}: {{ $c['message'] }}</div>
                @endforeach
                @foreach($amberFields as $name => $f)
                <div><i class="fa fa-circle-exclamation mr-1"></i>{{ ucwords(str_replace('_', ' ', $name)) }} — {{ $f['reason'] ?? 'confirm this value' }}</div>
                @endforeach
            </div>
            @else
            <p class="text-xs text-amber-700 mt-1"><i class="fa fa-circle-check mr-1"></i>All fields passed the GST math &amp; format checks — a quick glance and you’re done.</p>
            @endif
        </div>
    </div>
</div>
@endif
<div class="py-4 max-w-5xl"
     x-data="{
        supplierState: {{ json_encode($supplierState) }},
        partyStates: {{ $partyStates->toJson() }},
        hsnRates: {{ $hsnRates->toJson() }},
        partyId: '{{ $prefill['party_id'] ?? '' }}',
        lines: {{ json_encode($initLines) }},
        addLine() { this.lines.push({description: '', hsn_sac_code: '', quantity: 1, rate: '', discount_percent: 0, gst_rate: ''}); },
        removeLine(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
        isInterstate() { return this.partyId && this.supplierState && this.partyStates[this.partyId] && this.partyStates[this.partyId] !== this.supplierState; },
        // Effective GST %: the line's own rate wins; else the HSN master rate; else 0 (never guess).
        lineRate(l) {
            let g = parseFloat(l.gst_rate);
            if ((!g || isNaN(g)) && this.hsnRates[l.hsn_sac_code]) g = this.hsnRates[l.hsn_sac_code].igst;
            return (!g || isNaN(g)) ? 0 : g;
        },
        lineTaxable(l) { return (parseFloat(l.quantity)||0) * (parseFloat(l.rate)||0) * (1 - (parseFloat(l.discount_percent)||0)/100); },
        lineTax(l) { return this.lineTaxable(l) * this.lineRate(l) / 100; },
        taxableTotal() { return this.lines.reduce((s,l) => s + this.lineTaxable(l), 0); },
        taxTotal() { return this.lines.reduce((s,l) => s + this.lineTax(l), 0); },
        cgstTotal() { return this.isInterstate() ? 0 : this.taxTotal()/2; },
        sgstTotal() { return this.isInterstate() ? 0 : this.taxTotal()/2; },
        igstTotal() { return this.isInterstate() ? this.taxTotal() : 0; },
        grandTotal() { return this.taxableTotal() + this.taxTotal(); },
        inWords(n) { return window.amountInWords(n); },
     }">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ ($editing ?? false) ? route('accounting.invoices.update', $invoice) : route('accounting.invoices.store') }}">
            @csrf
            @if($editing ?? false) @method('PUT') @endif
            <input type="hidden" name="type" value="{{ $type ?? 'sales' }}">

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ ($type ?? 'sales') === 'sales' ? 'Customer' : 'Vendor' }} <span class="text-red-500">*</span></label>
                    <select name="party_id" x-model="partyId" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select party…</option>
                        @foreach($parties ?? [] as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}{{ $p->gstin ? ' — ' . $p->gstin : '' }}</option>
                        @endforeach
                    </select>
                    @if($prefill['party_branch_id'] ?? null)
                    <input type="hidden" name="party_branch_id" value="{{ $prefill['party_branch_id'] }}">
                    <p class="text-xs text-navy-600 mt-1"><i class="fa fa-code-branch mr-1"></i>Booked to branch: <strong>{{ $prefill['branch_label'] ?: 'Branch' }}</strong>{{ ($prefill['branch_gstin'] ?? null) ? ' · '.$prefill['branch_gstin'] : '' }}</p>
                    @endif
                    <p class="text-xs text-gray-400 mt-1" x-show="partyId" x-cloak>
                        Name or GSTIN wrong?
                        <a :href="'{{ url('accounting/parties') }}/' + partyId + '/edit'" target="_blank" class="text-navy-600 hover:underline">Edit {{ ($type ?? 'sales') === 'purchase' ? 'vendor' : 'customer' }} details →</a>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ ($type ?? 'sales') === 'purchase' ? 'Vendor Bill / Invoice No.' : 'Reference No.' }}</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number', $prefill['reference_number'] ?? '') }}" placeholder="{{ ($type ?? 'sales') === 'purchase' ? "Supplier's invoice number" : 'PO / reference' }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Date <span class="text-red-500">*</span></label>
                    <input type="date" name="invoice_date" required value="{{ old('invoice_date', $prefill['invoice_date'] ?? date('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $prefill['due_date'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex items-center gap-2 mb-3 text-xs">
                <span class="px-2 py-1 rounded-full font-medium" :class="isInterstate() ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                      x-text="isInterstate() ? 'IGST applies (interstate)' : 'CGST + SGST applies (intrastate)'"></span>
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-2">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-3 py-2">Description</th>
                            <th class="text-left px-3 py-2 w-32">HSN/SAC</th>
                            <th class="text-right px-3 py-2 w-20">Qty</th>
                            <th class="text-right px-3 py-2 w-28">Rate</th>
                            <th class="text-right px-3 py-2 w-24">Disc %</th>
                            <th class="text-right px-3 py-2 w-24">GST %</th>
                            <th class="text-right px-3 py-2 w-28">Taxable</th>
                            <th class="text-right px-3 py-2 w-24">Tax</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, i) in lines" :key="i">
                            <tr class="border-t border-gray-100">
                                <td class="px-3 py-2">
                                    <input type="text" :name="'lines[' + i + '][description]'" x-model="line.description" required
                                           class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                                    <input type="hidden" :name="'lines[' + i + '][unit]'" :value="line.unit || 'nos'">
                                </td>
                                <td class="px-3 py-2">
                                    {{-- Free text (any HSN/SAC), with the master as type-ahead suggestions. --}}
                                    <input type="text" list="hsnList" :name="'lines[' + i + '][hsn_sac_code]'" x-model="line.hsn_sac_code" placeholder="HSN"
                                           class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.001" min="0.001" :name="'lines[' + i + '][quantity]'" x-model="line.quantity" required
                                           class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-right">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" min="0" :name="'lines[' + i + '][rate]'" x-model="line.rate" required
                                           class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-right">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" min="0" max="100" :name="'lines[' + i + '][discount_percent]'" x-model="line.discount_percent"
                                           class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-right">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" min="0" max="100" :name="'lines[' + i + '][gst_rate]'" x-model="line.gst_rate" placeholder="%"
                                           class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-right">
                                </td>
                                <td class="px-3 py-2 text-right text-gray-600" x-text="'₹' + lineTaxable(line).toFixed(2)"></td>
                                <td class="px-3 py-2 text-right text-gray-600" x-text="'₹' + lineTax(line).toFixed(2)"></td>
                                <td class="px-2 py-2 text-center">
                                    <button type="button" @click="removeLine(i)" x-show="lines.length > 1" class="text-gray-300 hover:text-red-500">
                                        <i class="fa fa-xmark"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <button type="button" @click="addLine()" class="text-sm text-blue-600 hover:text-blue-700 mb-6">
                <i class="fa fa-plus mr-1"></i>Add line
            </button>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes / Payment Terms</label>
                <textarea name="notes" rows="2" placeholder="Payment terms, remarks…" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes', $prefill['notes'] ?? '') }}</textarea>
            </div>

            <div class="flex flex-col items-end gap-2">
                <div class="w-72 space-y-1.5 text-sm">
                    <div class="flex justify-between text-gray-500"><span>Taxable Value</span><span x-text="'₹' + taxableTotal().toFixed(2)"></span></div>
                    <template x-if="!isInterstate()">
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-gray-500"><span>CGST</span><span x-text="'₹' + cgstTotal().toFixed(2)"></span></div>
                            <div class="flex justify-between text-gray-500"><span>SGST</span><span x-text="'₹' + sgstTotal().toFixed(2)"></span></div>
                        </div>
                    </template>
                    <template x-if="isInterstate()">
                        <div class="flex justify-between text-gray-500"><span>IGST</span><span x-text="'₹' + igstTotal().toFixed(2)"></span></div>
                    </template>
                    <div class="flex justify-between text-gray-500"><span>Total GST</span><span x-text="'₹' + taxTotal().toFixed(2)"></span></div>
                    <div class="flex justify-between font-semibold text-gray-900 text-base pt-1.5 border-t border-gray-200"><span>Total</span><span x-text="'₹' + grandTotal().toFixed(2)"></span></div>
                </div>
                <div class="w-full sm:max-w-md text-xs text-right">
                    <span class="uppercase tracking-wide text-gray-400">In words: </span>
                    <span class="text-gray-700 font-medium" x-text="inWords(grandTotal())"></span>
                </div>
            </div>

            {{-- HSN/SAC type-ahead suggestions from the master --}}
            <datalist id="hsnList">
                @foreach($hsnCodes ?? [] as $h)
                <option value="{{ $h->code }}">{{ $h->code }} — {{ (int) $h->igst_rate }}%</option>
                @endforeach
            </datalist>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-100 mt-6">
                <a href="{{ route('accounting.invoices.index', ['type' => $type ?? 'sales']) }}" class="px-5 py-2.5 text-gray-600 text-sm font-medium hover:text-gray-900">Cancel</a>
                <button type="submit" class="px-5 py-2.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">
                    {{ ($editing ?? false) ? 'Save Changes' : 'Save as Draft' }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Indian-format number to words (lakh / crore), with paise.
window.amountInWords = function (num) {
    num = Math.round((parseFloat(num) || 0) * 100) / 100;
    const rupees = Math.floor(num);
    const paise = Math.round((num - rupees) * 100);
    const a = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    const two = (x) => x < 20 ? a[x] : (b[Math.floor(x / 10)] + (x % 10 ? ' ' + a[x % 10] : ''));
    const three = (x) => (Math.floor(x / 100) ? a[Math.floor(x / 100)] + ' Hundred' + (x % 100 ? ' ' : '') : '') + (x % 100 ? two(x % 100) : '');
    const words = (n) => {
        if (n === 0) return 'Zero';
        let out = '';
        const crore = Math.floor(n / 10000000); n %= 10000000;
        const lakh = Math.floor(n / 100000); n %= 100000;
        const thou = Math.floor(n / 1000); n %= 1000;
        if (crore) out += three(crore) + ' Crore ';
        if (lakh) out += three(lakh) + ' Lakh ';
        if (thou) out += three(thou) + ' Thousand ';
        if (n) out += three(n);
        return out.trim();
    };
    let res = words(rupees) + ' Rupees';
    if (paise) res += ' and ' + words(paise) + ' Paise';
    return res + ' Only';
};
</script>
@endpush
@endsection
