@extends('layouts.app')
@section('title', 'Map bank format')
@section('page-title', 'Map your bank format')

@section('content')
<div class="py-4 max-w-2xl">
    <a href="{{ route('accounting.payments.bankfile') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
        <i class="fa fa-arrow-left mr-1.5"></i>Back
    </a>

    <form method="POST" action="{{ route('accounting.payments.bankfile.template.store') }}"
          class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Format name</label>
            <input type="text" name="name" value="{{ $name }}" required class="w-full sm:w-80 border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>

        <div>
            <p class="text-sm font-medium text-gray-700 mb-1">Map each column to a field</p>
            <p class="text-xs text-gray-500 mb-3">We detected {{ count($headers) }} columns and pre-filled our best guess — adjust any that are wrong. Columns set to “leave blank” are written empty for every row.</p>
            <div class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
                @foreach($headers as $h)
                <div class="flex items-center gap-3 px-4 py-2.5">
                    <span class="flex-1 text-sm font-mono text-gray-700 truncate" title="{{ $h }}">{{ $h }}</span>
                    <i class="fa fa-arrow-right text-xs text-gray-300"></i>
                    <input type="hidden" name="headers[]" value="{{ $h }}">
                    <select name="mapping[{{ $h }}]" class="w-56 border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm">
                        @foreach($tokens as $val => $label)
                        <option value="{{ $val }}" @selected(($guess[$h] ?? '') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2 border-t border-gray-50">
            <button type="submit" class="px-5 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">Save format</button>
            <a href="{{ route('accounting.payments.bankfile') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>
@endsection
