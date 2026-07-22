@extends('layouts.app')
@section('title', $invoice->invoice_number)
@section('page-title', $invoice->invoice_number)

@section('content')
<div class="py-4 space-y-6 max-w-4xl" x-data="{ recurOpen: false }">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-xs px-2.5 py-1 rounded-full font-medium capitalize
                {{ $invoice->status === 'posted' ? 'bg-green-100 text-green-700' :
                   ($invoice->status === 'draft' ? 'bg-gray-100 text-gray-600' :
                   ($invoice->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')) }}">
                {{ $invoice->status }}
            </span>
            <span class="text-gray-500 text-sm">{{ $invoice->invoice_date->format('d M Y') }}</span>
            <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $invoice->isAccountingDocument() ? 'bg-navy-50 text-navy-700' : 'bg-purple-50 text-purple-700' }}">
                <i class="fa fa-file-lines mr-1"></i>{{ $invoice->documentLabel() }}
            </span>
            @if($invoice->irn)<span class="text-xs px-2.5 py-1 rounded-full font-medium bg-navy-50 text-navy-700"><i class="fa fa-qrcode mr-1"></i>e-Invoice generated</span>@endif
            @if($invoice->originalFilePath())
            <a href="{{ route('accounting.invoices.original', $invoice) }}" target="_blank" rel="noopener"
               class="text-xs px-2.5 py-1 rounded-full font-medium bg-blue-50 text-blue-700 hover:bg-blue-100" title="View the original scanned invoice">
                <i class="fa fa-image mr-1"></i>Original
            </a>
            @endif
        </div>
        <div class="flex gap-2">
            @if($invoice->status === 'draft')
            <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                <i class="fa fa-pen mr-1.5"></i>Edit
            </a>
            @if($invoice->isAccountingDocument())
            <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" onsubmit="return confirm('Post this invoice to the ledger? This cannot be undone.');">
                @csrf
                <button class="px-4 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-medium rounded-lg">
                    <i class="fa fa-check mr-1.5"></i>Post to Ledger
                </button>
            </form>
            @else
            <span class="px-4 py-2 text-xs text-purple-700 bg-purple-50 rounded-lg self-center">
                <i class="fa fa-circle-info mr-1"></i>{{ $invoice->documentLabel() }} — not posted to ledger
            </span>
            @endif
            <form method="POST" action="{{ route('accounting.invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel this invoice?');">
                @csrf
                <button class="px-4 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50">Cancel</button>
            </form>
            @endif
            {{-- Sales-cycle conversion: Quote → Order → Tax Invoice --}}
            @php $__conversions = ($invoice->status !== 'cancelled' && $invoice->type === 'sales') ? (\App\Models\Invoice::CONVERSIONS[$invoice->document_type ?? 'tax_invoice'] ?? []) : []; @endphp
            @foreach($__conversions as $__target => $__label)
            <form method="POST" action="{{ route('accounting.invoices.convert', $invoice) }}">
                @csrf
                <input type="hidden" name="document_type" value="{{ $__target }}">
                <button class="px-4 py-2 text-sm font-medium rounded-lg {{ $__target === 'tax_invoice' ? 'bg-navy-600 hover:bg-navy-700 text-white' : 'border border-navy-600 text-navy-700 hover:bg-navy-50' }}">
                    <i class="fa fa-arrow-right-arrow-left mr-1.5"></i>Convert to {{ $__label }}
                </button>
            </form>
            @endforeach
            {{-- Recurring: snapshot this invoice into a schedule that raises drafts on a cadence --}}
            @if($invoice->type === 'sales' && $invoice->status !== 'cancelled')
            <button type="button" @click="recurOpen = true" class="px-4 py-2 border border-indigo-300 text-indigo-700 text-sm font-medium rounded-lg hover:bg-indigo-50">
                <i class="fa fa-repeat mr-1.5"></i>Set up recurring
            </button>
            @endif
            @if(in_array($invoice->status, ['posted', 'partially_paid']) && $invoice->balance_amount > 0)
            <a href="{{ route('accounting.payments.record', ['type' => $invoice->type === 'sales' ? 'receipt' : 'payment', 'party_id' => $invoice->party_id]) }}"
               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                <i class="fa fa-hand-holding-dollar mr-1.5"></i>{{ $invoice->type === 'sales' ? 'Record receipt' : 'Record payment' }}
            </a>
            @endif
            @if($invoice->status === 'posted' && $invoice->type === 'sales' && !$invoice->irn)
            <a href="{{ route('gst.einvoice', $invoice) }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                <i class="fa fa-qrcode mr-1.5"></i>Generate e-Invoice
            </a>
            @endif
            @if($invoice->status === 'posted')
            <form method="POST" action="{{ route('accounting.invoices.reverse', $invoice) }}"
                  onsubmit="var r=prompt('Reverse and void this bill?\n\nThis posts a reversing ledger entry and removes the bill from GST returns, pending payments and reports. The record is kept for audit.\n\nReason:','Duplicate'); if(r===null){return false;} this.reason.value=r; return true;">
                @csrf
                <input type="hidden" name="reason" value="">
                <button class="px-4 py-2 border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50">
                    <i class="fa fa-rotate-left mr-1.5"></i>Reverse / Void
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Set-up-recurring modal --}}
    @if($invoice->type === 'sales' && $invoice->status !== 'cancelled')
    <div x-show="recurOpen" x-cloak class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4" @click.self="recurOpen = false">
        <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" x-data="{ ends: 'never' }">
            <form method="POST" action="{{ route('accounting.recurring.store') }}">
                @csrf
                <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                <div class="flex items-center justify-between px-6 pt-6 pb-1">
                    <h3 class="font-semibold text-gray-900"><i class="fa fa-repeat text-indigo-500 mr-1.5"></i>Set up recurring</h3>
                    <button type="button" @click="recurOpen = false" class="text-gray-400 hover:text-gray-600"><i class="fa fa-xmark"></i></button>
                </div>
                <p class="px-6 text-sm text-gray-500 mb-4">A copy of this {{ strtolower($invoice->documentLabel()) }}’s lines &amp; totals is saved as a schedule. Each period a fresh <span class="font-medium">draft</span> invoice is raised for you to review.</p>

                <div class="px-6 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Schedule name</label>
                        <input type="text" name="title" value="{{ ($invoice->party->name ?? 'Recurring') . ' — ' . $invoice->documentLabel() }}" maxlength="150"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-navy-500 focus:border-navy-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Frequency</label>
                            <select name="frequency" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                @foreach(\App\Models\RecurringInvoice::FREQUENCIES as $__fv => $__fl)
                                <option value="{{ $__fv }}" @selected($__fv === 'monthly')>{{ $__fl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Repeat every</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="interval_count" value="1" min="1" max="60" class="w-20 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <span class="text-sm text-gray-400">period(s)</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">First invoice on</label>
                        <input type="date" name="start_date" value="{{ now()->toDateString() }}" min="{{ now()->toDateString() }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Ends</label>
                        <div class="flex gap-2 text-sm">
                            <label class="flex items-center gap-1.5"><input type="radio" x-model="ends" value="never" checked> Never</label>
                            <label class="flex items-center gap-1.5"><input type="radio" x-model="ends" value="date"> On date</label>
                            <label class="flex items-center gap-1.5"><input type="radio" x-model="ends" value="count"> After N</label>
                        </div>
                        <div class="mt-2">
                            <input x-show="ends === 'date'" x-cloak type="date" name="end_date" min="{{ now()->toDateString() }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <div x-show="ends === 'count'" x-cloak class="flex items-center gap-2">
                                <input type="number" name="occurrences_limit" min="1" max="600" placeholder="12"
                                       class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <span class="text-sm text-gray-400">invoices, then stop</span>
                            </div>
                        </div>
                    </div>

                    @if($invoice->isAccountingDocument())
                    <label class="flex items-start gap-2 bg-gray-50 rounded-lg p-3 cursor-pointer">
                        <input type="checkbox" name="auto_post" value="1" class="mt-0.5">
                        <span class="text-sm text-gray-700">Post to the ledger automatically
                            <span class="block text-xs text-gray-400">Off by default — raise as a draft so you can review before posting.</span>
                        </span>
                    </label>
                    @endif
                </div>

                <div class="flex justify-end gap-2 px-6 py-4 mt-2 border-t border-gray-100">
                    <button type="button" @click="recurOpen = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">Create schedule</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if($invoice->journal)
    {{-- Double-entry journal posted for this bill — shown above the invoice for clarity. --}}
    <div class="bg-white rounded-xl border border-navy-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 bg-navy-50 border-b border-navy-100">
            <div>
                <p class="text-[11px] text-navy-500 uppercase tracking-wider">Journal Entry · Double-Entry</p>
                <p class="text-sm font-semibold text-navy-800">
                    <i class="fa fa-book mr-1"></i>{{ $invoice->journal->voucher_number }}
                    <span class="text-gray-400 font-normal ml-1">{{ ucfirst($invoice->journal->voucher_type) }} · {{ \Illuminate\Support\Carbon::parse($invoice->journal->date)->format('d M Y') }}</span>
                    @if($invoice->journal->is_reversed)<span class="ml-1 text-[11px] px-2 py-0.5 rounded-full bg-red-100 text-red-700">Reversed</span>@endif
                </p>
            </div>
            <a href="{{ route('accounting.journals.show', $invoice->journal) }}" class="text-xs text-navy-600 hover:underline whitespace-nowrap">Open voucher <i class="fa fa-arrow-right ml-0.5"></i></a>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Account</th>
                    <th class="text-left px-5 py-2.5">Narration</th>
                    <th class="text-right px-5 py-2.5">Debit</th>
                    <th class="text-right px-5 py-2.5">Credit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($invoice->journal->lines as $jl)
                <tr>
                    <td class="px-5 py-2.5 text-gray-900 whitespace-nowrap"><span class="font-mono text-xs text-gray-400 mr-1.5">{{ $jl->account->code ?? '' }}</span>{{ $jl->account->name ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-gray-500 text-xs">{{ $jl->narration }}</td>
                    <td class="px-5 py-2.5 text-right {{ $jl->debit > 0 ? 'text-gray-900 font-medium' : 'text-gray-300' }}">{{ $jl->debit > 0 ? '₹'.number_format($jl->debit, 2) : '—' }}</td>
                    <td class="px-5 py-2.5 text-right {{ $jl->credit > 0 ? 'text-gray-900 font-medium' : 'text-gray-300' }}">{{ $jl->credit > 0 ? '₹'.number_format($jl->credit, 2) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 bg-gray-50 font-semibold text-gray-900">
                    <td class="px-5 py-2.5" colspan="2">Total</td>
                    <td class="px-5 py-2.5 text-right">₹{{ number_format($invoice->journal->total_debit, 2) }}</td>
                    <td class="px-5 py-2.5 text-right">₹{{ number_format($invoice->journal->total_credit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
        </div>
        <div class="px-5 py-2 text-[11px] text-gray-400 border-t border-gray-100 flex items-center gap-1.5">
            <i class="fa fa-scale-balanced"></i> Debits equal credits — this is the double-entry posted to your ledger for this bill.
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 grid grid-cols-2 gap-6">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">{{ $invoice->type === 'sales' ? 'Customer' : 'Vendor' }}</p>
            <div class="flex items-center gap-2 flex-wrap">
                <p class="font-semibold text-gray-900">{{ $invoice->party->name ?? '—' }}</p>
                @if($invoice->party)
                @php $pc = $invoice->party->classificationColor(); @endphp
                <span class="text-[11px] px-2 py-0.5 rounded-full font-medium bg-{{ $pc }}-100 text-{{ $pc }}-700">{{ $invoice->party->classificationLabel() }}</span>
                @endif
            </div>
            @if($invoice->party?->gstin)<p class="text-sm text-gray-500 font-mono">{{ $invoice->party->gstin }}</p>@endif
            @if($invoice->party?->address)<p class="text-sm text-gray-500">{{ $invoice->party->address }}, {{ $invoice->party->city }}</p>@endif
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Place of Supply</p>
            <p class="font-medium text-gray-900">{{ $invoice->place_of_supply ?: '—' }} · {{ $invoice->is_interstate ? 'Interstate (IGST)' : 'Intrastate (CGST+SGST)' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Description</th>
                    <th class="text-left px-5 py-2.5">HSN/SAC</th>
                    <th class="text-right px-5 py-2.5">Qty</th>
                    <th class="text-right px-5 py-2.5">Rate</th>
                    <th class="text-right px-5 py-2.5">Taxable</th>
                    <th class="text-right px-5 py-2.5">Tax</th>
                    <th class="text-right px-5 py-2.5">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($invoice->lines as $line)
                <tr>
                    <td class="px-5 py-3 text-gray-900">
                        {{ $line->description }}
                        @if(!empty($line->meta))
                        <span class="block text-[11px] text-gray-400 mt-0.5">
                            @foreach($line->meta as $k => $v)@if($v !== null && $v !== ''){{ ucwords(str_replace('_',' ',$k)) }}: {{ $v }}@if(!$loop->last)  ·  @endif @endif @endforeach
                        </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-400 font-mono text-xs">{{ $line->hsn_sac_code ?: '—' }}</td>
                    <td class="px-5 py-3 text-right text-gray-500">{{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }}</td>
                    <td class="px-5 py-3 text-right text-gray-500">₹{{ number_format($line->rate, 2) }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">₹{{ number_format($line->taxable_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right text-gray-700">₹{{ number_format($line->cgst_amount + $line->sgst_amount + $line->igst_amount, 2) }}</td>
                    <td class="px-5 py-3 text-right font-medium text-gray-900">₹{{ number_format($line->line_total + $line->cgst_amount + $line->sgst_amount + $line->igst_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-5 border-t border-gray-100 flex justify-end">
            <div class="w-64 space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-500"><span>Taxable Value</span><span>₹{{ number_format($invoice->taxable_amount, 2) }}</span></div>
                @if($invoice->cgst_amount > 0)
                <div class="flex justify-between text-gray-500"><span>CGST</span><span>₹{{ number_format($invoice->cgst_amount, 2) }}</span></div>
                <div class="flex justify-between text-gray-500"><span>SGST</span><span>₹{{ number_format($invoice->sgst_amount, 2) }}</span></div>
                @endif
                @if($invoice->igst_amount > 0)
                <div class="flex justify-between text-gray-500"><span>IGST</span><span>₹{{ number_format($invoice->igst_amount, 2) }}</span></div>
                @endif
                <div class="flex justify-between font-semibold text-gray-900 text-base pt-1.5 border-t border-gray-200"><span>Total</span><span>₹{{ number_format($invoice->total_amount, 2) }}</span></div>
                @if($invoice->price_includes_gst)
                <div class="text-[11px] text-gray-400 text-right">Prices were GST-inclusive — tax shown is backed out of the amount.</div>
                @endif
                @if($invoice->tds_amount > 0)
                <div class="flex justify-between text-indigo-600 pt-1 border-t border-gray-100"><span>Less: TDS @ {{ rtrim(rtrim(number_format($invoice->tds_rate, 2), '0'), '.') }}%</span><span>− ₹{{ number_format($invoice->tds_amount, 2) }}</span></div>
                <div class="flex justify-between font-medium text-gray-900"><span>Net payable</span><span>₹{{ number_format($invoice->total_amount - $invoice->tds_amount, 2) }}</span></div>
                @endif
                @if($invoice->balance_amount > 0 && $invoice->balance_amount != $invoice->total_amount)
                <div class="flex justify-between text-orange-600"><span>Balance Due</span><span>₹{{ number_format($invoice->balance_amount, 2) }}</span></div>
                @endif
            </div>
        </div>
    </div>

    @if($invoice->notes)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Notes</p>
        <p class="text-sm text-gray-600">{{ $invoice->notes }}</p>
    </div>
    @endif
</div>
@endsection
