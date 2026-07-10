@extends('layouts.app')
@section('title', 'Working Papers')
@section('page-title', 'Working Papers')

@section('content')
<div class="py-4 space-y-6" x-data="{ addOpen: false }">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Audit documentation — ledgers, confirmations, checklists, and supporting evidence.</p>
        <button @click="addOpen = !addOpen" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">
            <i class="fa fa-upload mr-1.5"></i>Upload Paper
        </button>
    </div>

    <div x-show="addOpen" x-cloak class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <form method="POST" action="{{ route('pramaan.papers.store') }}" enctype="multipart/form-data" class="grid sm:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Category</label>
                <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— None —</option>
                    @foreach(['Ledgers','Bank Confirmations','Fixed Assets','Statutory Dues','Checklists','Management Rep','Vouching','Other'] as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Link to Audit Report</label>
                <select name="audit_report_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— Unlinked —</option>
                    @foreach($reports as $r)
                    <option value="{{ $r->id }}" @selected(request('audit_report_id') == $r->id)>{{ str_replace('_',' ',$r->form_type) }} — {{ $r->report_data['client_name'] ?? '' }} ({{ $r->financial_year }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">File <span class="text-red-500">*</span></label>
                <input type="file" name="file" required class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700 file:text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg">Upload</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-5 py-2.5">Title</th>
                    <th class="text-left px-5 py-2.5">Category</th>
                    <th class="text-left px-5 py-2.5">Linked Report</th>
                    <th class="text-left px-5 py-2.5">Uploaded By</th>
                    <th class="text-left px-5 py-2.5">Date</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($papers as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-900 font-medium"><i class="fa fa-file text-gray-400 mr-2"></i>{{ $p->title }}</td>
                    <td class="px-5 py-3">@if($p->category)<span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $p->category }}</span>@else<span class="text-gray-300">—</span>@endif</td>
                    <td class="px-5 py-3 text-gray-500">{{ $p->auditReport ? str_replace('_',' ',$p->auditReport->form_type) . ' · ' . ($p->auditReport->report_data['client_name'] ?? '') : '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $p->uploader->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $p->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        <form method="POST" action="{{ route('pramaan.papers.destroy', $p) }}" onsubmit="return confirm('Remove this working paper?')">
                            @csrf @method('DELETE')
                            <button class="text-gray-400 hover:text-red-600"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No working papers uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($papers->hasPages())
        <div class="p-4 border-t border-gray-100">{{ $papers->links() }}</div>
        @endif
    </div>
</div>
@endsection
