@extends('layouts.app')
@section('title', 'Recurring Invoices')
@section('page-title', 'Recurring Invoices')

@section('content')
<div class="py-4 space-y-5">
    <div class="flex items-start justify-between gap-4">
        <p class="text-sm text-gray-500 max-w-2xl">
            Schedules raise a <span class="font-medium text-gray-700">draft invoice</span> automatically each period — retainers, rent, AMC, subscriptions.
            To create one, open any sales invoice and choose <span class="font-medium text-gray-700">“Set up recurring”</span>.
        </p>
        <a href="{{ route('accounting.invoices.index', ['type' => 'sales']) }}" class="shrink-0 px-4 py-2 border border-navy-600 text-navy-700 text-sm font-medium rounded-lg hover:bg-navy-50">
            <i class="fa fa-file-invoice mr-1.5"></i>Go to invoices
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Schedule</th>
                    <th class="text-left px-5 py-2.5">Party</th>
                    <th class="text-left px-5 py-2.5">Cadence</th>
                    <th class="text-left px-5 py-2.5">Next invoice</th>
                    <th class="text-right px-5 py-2.5">Raised</th>
                    <th class="text-right px-5 py-2.5">Amount</th>
                    <th class="text-center px-5 py-2.5">Status</th>
                    <th class="text-right px-5 py-2.5">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($schedules as $s)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <a href="{{ route('accounting.recurring.show', $s) }}" class="text-navy-600 font-medium hover:underline">{{ $s->title }}</a>
                        @if($s->auto_post)<span class="ml-1.5 text-[10px] px-1.5 py-0.5 rounded bg-green-50 text-green-600 font-medium" title="Posts to the ledger automatically">Auto-post</span>@endif
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $s->party->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $s->frequencyLabel() }}</td>
                    <td class="px-5 py-3 {{ $s->status === 'active' && $s->next_run_date && $s->next_run_date->isToday() ? 'text-orange-600 font-medium' : 'text-gray-500' }}">
                        {{ $s->status === 'ended' ? '—' : optional($s->next_run_date)->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3 text-right text-gray-500">{{ $s->occurrences_generated }}@if($s->occurrences_limit)<span class="text-gray-300">/{{ $s->occurrences_limit }}</span>@endif</td>
                    <td class="px-5 py-3 text-right font-medium text-gray-900">₹{{ number_format((float) ($s->header['total_amount'] ?? 0), 2) }}</td>
                    <td class="px-5 py-3 text-center">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $s->status === 'active' ? 'bg-green-100 text-green-700' : ($s->status === 'paused' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                            {{ ucfirst($s->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1.5">
                            @if($s->status !== 'ended')
                            <form method="POST" action="{{ route('accounting.recurring.run', $s) }}" onsubmit="return confirm('Raise the next draft invoice now?')">
                                @csrf
                                <button class="px-2 py-1 text-xs rounded-lg border border-navy-200 text-navy-700 hover:bg-navy-50" title="Raise the next invoice now"><i class="fa fa-bolt"></i></button>
                            </form>
                            @if($s->status === 'active')
                            <form method="POST" action="{{ route('accounting.recurring.pause', $s) }}">
                                @csrf
                                <button class="px-2 py-1 text-xs rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50" title="Pause"><i class="fa fa-pause"></i></button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('accounting.recurring.resume', $s) }}">
                                @csrf
                                <button class="px-2 py-1 text-xs rounded-lg border border-green-200 text-green-700 hover:bg-green-50" title="Resume"><i class="fa fa-play"></i></button>
                            </form>
                            @endif
                            @endif
                            <form method="POST" action="{{ route('accounting.recurring.destroy', $s) }}" onsubmit="return confirm('Delete this recurring schedule? Invoices already raised are kept.')">
                                @csrf @method('DELETE')
                                <button class="px-2 py-1 text-xs rounded-lg border border-red-200 text-red-600 hover:bg-red-50" title="Delete"><i class="fa fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-12 text-center text-gray-400">
                    <i class="fa fa-repeat text-2xl text-gray-300 mb-2 block"></i>
                    No recurring schedules yet. Open a sales invoice and choose “Set up recurring” to bill it automatically.
                </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($schedules->hasPages())<div class="p-4 border-t border-gray-100">{{ $schedules->links() }}</div>@endif
    </div>
</div>
@endsection
