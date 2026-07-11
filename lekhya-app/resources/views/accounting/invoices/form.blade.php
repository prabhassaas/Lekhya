@extends('layouts.app')
@section('title', 'New Invoice')
@section('page-title', 'New ' . (($type ?? 'sales') === 'sales' ? 'Sales' : 'Purchase') . ' Invoice')

@section('content')
@php
    $hsnRates = ($hsnCodes ?? collect())->keyBy('code')->map(fn($h) => ['cgst' => (float) $h->cgst_rate, 'sgst' => (float) $h->sgst_rate, 'igst' => (float) $h->igst_rate]);
    $partyStates = ($parties ?? collect())->keyBy('id')->map(fn($p) => $p->state_code);
    $supplierState = ($tenant ?? null)?->state_code;
    $prefill = $prefill ?? null;
    $initLines = $prefill['lines'] ?? [['description' => '', 'hsn_sac_code' => '', 'quantity' => 1, 'rate' => '', 'discount_percent' => 0]];
    $amberFields = collect($prefill['validation']['fields'] ?? [])->filter(fn($f) => $f['status'] === 'amber');
    $failedChecks = collect($prefill['validation']['checks'] ?? [])->filter(fn($c) => ! $c['ok']);
@endphp

@if($prefill)
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
        addLine() { this.lines.push({description: '', hsn_sac_code: '', quantity: 1, rate: '', discount_percent: 0}); },
        removeLine(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
        isInterstate() { return this.partyId && this.supplierState && this.partyStates[this.partyId] && this.partyStates[this.partyId] !== this.supplierState; },
        lineTaxable(l) { return (parseFloat(l.quantity)||0) * (parseFloat(l.rate)||0) * (1 - (parseFloat(l.discount_percent)||0)/100); },
        lineTax(l) {
            let r = this.hsnRates[l.hsn_sac_code];
            if (!r) {
                const g = parseFloat(l.gst_rate);
                r = g ? {cgst: g/2, sgst: g/2, igst: g} : {cgst:9, sgst:9, igst:18};
            }
            const t = this.lineTaxable(l);
            return this.isInterstate() ? t * r.igst / 100 : t * (r.cgst + r.sgst) / 100;
        },
        subtotal() { return this.lines.reduce((s,l) => s + (parseFloat(l.quantity)||0)*(parseFloat(l.rate)||0), 0); },
        taxableTotal() { return this.lines.reduce((s,l) => s + this.lineTaxable(l), 0); },
        taxTotal() { return this.lines.reduce((s,l) => s + this.lineTax(l), 0); },
        grandTotal() { return this.taxableTotal() + this.taxTotal(); },
     }">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ route('accounting.invoices.store') }}">
            @csrf
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
                                    {{-- carry unit + the bill's own GST rate captured from the scan --}}
                                    <input type="hidden" :name="'lines[' + i + '][unit]'" :value="line.unit || 'nos'">
                                    <input type="hidden" :name="'lines[' + i + '][gst_rate]'" :value="line.gst_rate ?? ''">
                                </td>
                                <td class="px-3 py-2">
                                    <select :name="'lines[' + i + '][hsn_sac_code]'" x-model="line.hsn_sac_code" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                                        <option value="">—</option>
                                        @foreach($hsnCodes ?? [] as $h)
                                        <option value="{{ $h->code }}">{{ $h->code }} ({{ (int) $h->igst_rate }}%)</option>
                                        @endforeach
                                    </select>
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

            <div class="flex justify-end">
                <div class="w-64 space-y-1.5 text-sm">
                    <div class="flex justify-between text-gray-500"><span>Taxable Value</span><span x-text="'₹' + taxableTotal().toFixed(2)"></span></div>
                    <div class="flex justify-between text-gray-500"><span>Tax</span><span x-text="'₹' + taxTotal().toFixed(2)"></span></div>
                    <div class="flex justify-between font-semibold text-gray-900 text-base pt-1.5 border-t border-gray-200"><span>Total</span><span x-text="'₹' + grandTotal().toFixed(2)"></span></div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-100 mt-6">
                <a href="{{ route('accounting.invoices.index', ['type' => $type ?? 'sales']) }}" class="px-5 py-2.5 text-gray-600 text-sm font-medium hover:text-gray-900">Cancel</a>
                <button type="submit" class="px-5 py-2.5 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">
                    Save as Draft
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
