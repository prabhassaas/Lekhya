@extends('layouts.app')
@section('title', 'Audit Reports')
@section('page-title', 'Audit Reports')

@section('content')
@php
    $badge = [
        'draft'        => ['bg-gray-100', 'text-gray-600', 'Draft'],
        'under_review' => ['bg-blue-100', 'text-blue-700', 'Under Review'],
        'signed'       => ['bg-green-100', 'text-green-700', 'Signed'],
        'filed'        => ['bg-purple-100', 'text-purple-700', 'Filed'],
    ];
@endphp
<div class="py-4 space-y-6">
    <div class="flex items-center justify-end">
        <a href="{{ route('pramaan.audit-reports.create') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">
            <i class="fa fa-plus mr-1.5"></i>New Audit Report
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Form</th>
                    <th class="text-left px-5 py-2.5">Client</th>
                    <th class="text-left px-5 py-2.5">FY</th>
                    <th class="text-left px-5 py-2.5">Preparer</th>
                    <th class="text-left px-5 py-2.5">UDIN</th>
                    <th class="text-right px-5 py-2.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($reports as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3"><a href="{{ route('pramaan.audit-reports.show', $r) }}" class="text-amber-700 font-medium hover:underline">{{ str_replace('_', ' ', $r->form_type) }}</a></td>
                    <td class="px-5 py-3 text-gray-900">{{ $r->report_data['client_name'] ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $r->financial_year }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $r->preparer->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-400 font-mono text-xs">{{ $r->udin->udin ?? '—' }}</td>
                    <td class="px-5 py-3 text-right">
                        @php($b = $badge[$r->status] ?? ['bg-gray-100','text-gray-600',$r->status])
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $b[0] }} {{ $b[1] }}">{{ $b[2] }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No audit reports yet. <a href="{{ route('pramaan.audit-reports.create') }}" class="text-amber-700 hover:underline">Create one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($reports->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $reports->links() }}</div>
        @endif
    </div>
</div>
@endsection
