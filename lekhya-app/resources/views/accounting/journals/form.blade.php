@extends('layouts.app')
@section('title', 'New Journal Voucher')
@section('page-title', 'New Journal Voucher')

@section('content')
<div class="py-4 max-w-4xl"
     x-data="{
        lines: [{account_id: '', debit: '', credit: ''}, {account_id: '', debit: '', credit: ''}],
        addLine() { this.lines.push({account_id: '', debit: '', credit: ''}); },
        removeLine(i) { if (this.lines.length > 2) this.lines.splice(i, 1); },
        totalDebit() { return this.lines.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0); },
        totalCredit() { return this.lines.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0); },
        isBalanced() { return Math.abs(this.totalDebit() - this.totalCredit()) < 0.005 && this.totalDebit() > 0; },
     }">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ route('accounting.journals.store') }}">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Voucher Type <span class="text-red-500">*</span></label>
                    <select name="voucher_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        @foreach(['journal' => 'Journal', 'contra' => 'Contra', 'payment' => 'Payment', 'receipt' => 'Receipt'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('voucher_type') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Narration <span class="text-red-500">*</span></label>
                <input type="text" name="narration" required maxlength="500" value="{{ old('narration') }}"
                       placeholder="e.g. Office rent paid for July"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="border border-gray-200 rounded-xl overflow-hidden mb-2">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-4 py-2">Account</th>
                            <th class="text-right px-4 py-2 w-36">Debit</th>
                            <th class="text-right px-4 py-2 w-36">Credit</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, i) in lines" :key="i">
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-2">
                                    <select :name="'lines[' + i + '][account_id]'" x-model="line.account_id" required class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                                        <option value="">Select account…</option>
                                        @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" step="0.01" min="0" :name="'lines[' + i + '][debit]'" x-model="line.debit"
                                           @input="if(line.debit) line.credit=''"
                                           class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-right">
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" step="0.01" min="0" :name="'lines[' + i + '][credit]'" x-model="line.credit"
                                           @input="if(line.credit) line.debit=''"
                                           class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-right">
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <button type="button" @click="removeLine(i)" x-show="lines.length > 2" class="text-gray-300 hover:text-red-500">
                                        <i class="fa fa-xmark"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td class="px-4 py-2 text-right font-medium text-gray-500">Total</td>
                            <td class="px-4 py-2 text-right font-semibold text-gray-900" x-text="'₹' + totalDebit().toFixed(2)"></td>
                            <td class="px-4 py-2 text-right font-semibold text-gray-900" x-text="'₹' + totalCredit().toFixed(2)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" @click="addLine()" class="text-sm text-blue-600 hover:text-blue-700 mb-5">
                <i class="fa fa-plus mr-1"></i>Add line
            </button>

            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                <p class="text-sm" :class="isBalanced() ? 'text-green-600' : 'text-red-500'">
                    <i class="fa" :class="isBalanced() ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
                    <span x-text="isBalanced() ? 'Balanced' : 'Debit and credit must be equal before posting'"></span>
                </p>
                <div class="flex gap-3">
                    <a href="{{ route('accounting.journals.index') }}" class="px-5 py-2.5 text-gray-600 text-sm font-medium hover:text-gray-900">Cancel</a>
                    <button type="submit" :disabled="!isBalanced()" class="px-5 py-2.5 bg-navy-600 hover:bg-navy-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg">
                        Post Journal
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
