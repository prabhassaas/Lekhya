@extends('layouts.app')
@section('title', $type === 'receipt' ? 'Record Receipt' : 'Record Payment')
@section('page-title', $type === 'receipt' ? 'Record Receipt' : 'Record Payment')

@section('content')
@php
    $billsJson = $bills->map(fn($b) => [
        'id' => $b->id, 'number' => $b->invoice_number,
        'ref' => $b->reference_number, 'date' => $b->invoice_date->format('d M Y'),
        'balance' => round((float) $b->balance_amount, 2), 'checked' => true, 'amount' => round((float) $b->balance_amount, 2),
    ])->values();
    $partyLabel = $type === 'receipt' ? 'Customer' : 'Vendor';
@endphp
<div class="py-4 max-w-4xl space-y-5">

    {{-- Type toggle --}}
    <div class="flex items-center gap-2">
        <a href="{{ route('accounting.payments.record', ['type' => 'receipt', 'party_id' => request('party_id')]) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium {{ $type === 'receipt' ? 'bg-navy-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            <i class="fa fa-arrow-down-long mr-1.5"></i>Receipt (money in)
        </a>
        <a href="{{ route('accounting.payments.record', ['type' => 'payment', 'party_id' => request('party_id')]) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium {{ $type === 'payment' ? 'bg-navy-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            <i class="fa fa-arrow-up-long mr-1.5"></i>Payment (money out)
        </a>
        <a href="{{ route('accounting.payments.history') }}" class="ml-auto text-sm text-navy-600 hover:underline">View history →</a>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    {{-- Party picker --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">{{ $partyLabel }}</label>
        <select onchange="if(this.value) window.location='{{ route('accounting.payments.record') }}?type={{ $type }}&party_id='+this.value"
                class="w-full sm:w-96 border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Select a {{ strtolower($partyLabel) }}…</option>
            @foreach($parties as $p)
            <option value="{{ $p->id }}" @selected($party && $party->id === $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        @if($parties->isEmpty())
        <p class="text-sm text-gray-400 mt-2">No {{ strtolower($partyLabel) }}s with open {{ $type === 'receipt' ? 'invoices' : 'bills' }} right now.</p>
        @endif
    </div>

    @if($party && $bills->isNotEmpty())
    <form method="POST" action="{{ route('accounting.payments.record.store') }}" x-data="recordPayment(@js($billsJson))">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="party_id" value="{{ $party->id }}">

        {{-- Open bills + allocation --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 font-medium text-gray-800">Open {{ $type === 'receipt' ? 'invoices' : 'bills' }} for {{ $party->name }}</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-2.5 w-8"></th>
                        <th class="text-left px-4 py-2.5">Invoice #</th>
                        <th class="text-left px-4 py-2.5">Date</th>
                        <th class="text-right px-4 py-2.5">Balance</th>
                        <th class="text-right px-4 py-2.5 w-40">Settle amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="(b, i) in bills" :key="b.id">
                        <tr :class="b.checked ? '' : 'opacity-50'">
                            <td class="px-4 py-2.5"><input type="checkbox" x-model="b.checked" @change="b.checked ? (b.amount = b.balance) : (b.amount = 0)"></td>
                            <td class="px-4 py-2.5 text-gray-900" x-text="b.number"></td>
                            <td class="px-4 py-2.5 text-gray-500" x-text="b.date"></td>
                            <td class="px-4 py-2.5 text-right text-gray-600">₹<span x-text="fmt(b.balance)"></span></td>
                            <td class="px-4 py-2.5 text-right">
                                <input type="hidden" :name="`allocations[${i}][invoice_id]`" :value="b.id">
                                <input type="number" step="0.01" min="0" :max="b.balance" :name="`allocations[${i}][amount]`"
                                       x-model.number="b.amount" :readonly="!b.checked"
                                       class="w-32 text-right border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:border-navy-400 outline-none">
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Payment details + totals --}}
        <div class="grid md:grid-cols-2 gap-5 mt-5">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">{{ $type === 'receipt' ? 'Received into (bank / cash)' : 'Paid from (bank / cash)' }} *</label>
                    <select name="ledger_account_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select account…</option>
                        @foreach($ledgers as $l)
                        <option value="{{ $l->id }}">{{ $l->code }} · {{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Date *</label>
                        <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Mode</label>
                        <select name="mode" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="bank_transfer">Bank transfer / NEFT</option>
                            <option value="upi">UPI</option>
                            <option value="cheque">Cheque</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Reference (UTR / cheque no.)</label>
                    <input type="text" name="reference" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Notes</label>
                    <input type="text" name="notes" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600"><span>Total settled (gross)</span><span class="font-medium">₹<span x-text="fmt(gross)"></span></span></div>
                    <div class="flex justify-between items-center text-gray-600">
                        <span>Less: TDS {{ $type === 'receipt' ? 'deducted by customer' : 'you deduct' }}</span>
                        <span>₹<input type="number" step="0.01" min="0" name="tds_amount" x-model.number="tds" class="w-24 text-right border border-gray-300 rounded px-2 py-1 text-sm"></span>
                    </div>
                    <div class="flex justify-between font-semibold text-gray-900 text-base pt-2 border-t border-gray-200">
                        <span>{{ $type === 'receipt' ? 'Cash received' : 'Cash paid' }}</span><span>₹<span x-text="fmt(cash)"></span></span>
                    </div>
                    <p class="text-[11px] text-gray-400 pt-1">A {{ $type }} voucher will be posted and the selected bills' balances cleared.</p>
                </div>
                <button type="submit" :disabled="gross <= 0"
                        class="mt-4 w-full py-2.5 rounded-lg bg-navy-600 hover:bg-navy-700 disabled:opacity-40 text-white text-sm font-semibold">
                    <i class="fa fa-check mr-1.5"></i>Record {{ $type === 'receipt' ? 'Receipt' : 'Payment' }}
                </button>
            </div>
        </div>
    </form>
    @elseif($party)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center text-gray-400">
        No open {{ $type === 'receipt' ? 'invoices' : 'bills' }} for {{ $party->name }} — nothing to settle.
    </div>
    @endif
</div>

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('recordPayment', function (bills) {
        return {
            bills: bills,
            tds: 0,
            get gross() { return this.bills.filter(b => b.checked).reduce((s, b) => s + (parseFloat(b.amount) || 0), 0); },
            get cash() { return Math.max(0, this.gross - (parseFloat(this.tds) || 0)); },
            fmt(n) { return (parseFloat(n) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        };
    });
});
</script>
@endsection
