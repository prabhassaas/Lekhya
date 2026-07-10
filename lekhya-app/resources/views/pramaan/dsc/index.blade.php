@extends('layouts.app')
@section('title', 'DSC Vault')
@section('page-title', 'DSC Vault')

@section('content')
<div class="py-4 space-y-6" x-data="{ addOpen: false }">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Digital Signature Certificates used to sign audit reports and certifications.</p>
        <button @click="addOpen = !addOpen" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">
            <i class="fa fa-plus mr-1.5"></i>Add Certificate
        </button>
    </div>

    <div x-show="addOpen" x-cloak class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <form method="POST" action="{{ route('pramaan.dsc.store') }}" enctype="multipart/form-data" class="grid sm:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Holder Name <span class="text-red-500">*</span></label>
                <input type="text" name="holder_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Common Name (CN) <span class="text-red-500">*</span></label>
                <input type="text" name="cn" required placeholder="As printed on the certificate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Valid From <span class="text-red-500">*</span></label>
                <input type="date" name="valid_from" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Valid To <span class="text-red-500">*</span></label>
                <input type="date" name="valid_to" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Certificate File (optional)</label>
                <input type="file" name="certificate" class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700 file:text-sm">
                <p class="text-xs text-gray-400 mt-1">Stored privately. Leave blank to register metadata only.</p>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg">Add to Vault</button>
            </div>
        </form>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($certificates as $cert)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 {{ $cert->is_active ? '' : 'opacity-60' }}">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center"><i class="fa fa-signature text-amber-600"></i></div>
                @if(!$cert->is_active)
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium">Inactive</span>
                @elseif($cert->isExpired())
                <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">Expired</span>
                @elseif($cert->expiringSoon())
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">Expiring soon</span>
                @else
                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">Active</span>
                @endif
            </div>
            <p class="font-semibold text-gray-900">{{ $cert->holder_name }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $cert->cn }}</p>
            <div class="mt-3 pt-3 border-t border-gray-50 text-xs text-gray-500 space-y-1">
                <div class="flex justify-between"><span>Valid from</span><span class="text-gray-700">{{ $cert->valid_from->format('d M Y') }}</span></div>
                <div class="flex justify-between"><span>Valid to</span><span class="text-gray-700">{{ $cert->valid_to->format('d M Y') }}</span></div>
            </div>
            @if($cert->is_active)
            <form method="POST" action="{{ route('pramaan.dsc.destroy', $cert) }}" onsubmit="return confirm('Deactivate this certificate?')" class="mt-3">
                @csrf @method('DELETE')
                <button class="text-xs text-red-600 hover:underline">Deactivate</button>
            </form>
            @endif
        </div>
        @empty
        <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-xl border border-gray-100 shadow-sm p-10 text-center text-gray-400 text-sm">
            No DSC certificates in the vault. Add one to sign audit reports.
        </div>
        @endforelse
    </div>
</div>
@endsection
