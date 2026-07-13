@extends('layouts.app')
@section('title', 'Edit ' . $party->name)
@section('page-title', 'Edit Party')

@section('content')
@php
    $states = [
        '01'=>'Jammu & Kashmir','02'=>'Himachal Pradesh','03'=>'Punjab','04'=>'Chandigarh','05'=>'Uttarakhand',
        '06'=>'Haryana','07'=>'Delhi','08'=>'Rajasthan','09'=>'Uttar Pradesh','10'=>'Bihar','11'=>'Sikkim',
        '12'=>'Arunachal Pradesh','13'=>'Nagaland','14'=>'Manipur','15'=>'Mizoram','16'=>'Tripura','17'=>'Meghalaya',
        '18'=>'Assam','19'=>'West Bengal','20'=>'Jharkhand','21'=>'Odisha','22'=>'Chhattisgarh','23'=>'Madhya Pradesh',
        '24'=>'Gujarat','26'=>'Dadra & Nagar Haveli and Daman & Diu','27'=>'Maharashtra','29'=>'Karnataka','30'=>'Goa',
        '31'=>'Lakshadweep','32'=>'Kerala','33'=>'Tamil Nadu','34'=>'Puducherry','35'=>'Andaman & Nicobar','36'=>'Telangana',
        '37'=>'Andhra Pradesh','38'=>'Ladakh',
    ];
@endphp
<div class="py-4 max-w-3xl">
    <a href="{{ route('accounting.parties.show', $party) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
        <i class="fa fa-arrow-left mr-1.5"></i>Back
    </a>

    <form method="POST" action="{{ route('accounting.parties.update', $party) }}"
          x-data="{ states: {{ \Illuminate\Support\Js::from($states) }}, stateCode: '{{ old('state_code', $party->state_code) }}' }"
          class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
        @csrf @method('PUT')

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">{{ $errors->first() }}</div>
        @endif

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $party->name) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach(['vendor'=>'Vendor','customer'=>'Customer','both'=>'Both'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('type', $party->type) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Classification (AI-detected role) + TDS --}}
        <div class="grid sm:grid-cols-3 gap-4" x-data="{ cls: '{{ old('classification', $party->classification) }}' }">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Classification</label>
                <select name="classification" x-model="cls" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">— Not set —</option>
                    @foreach(\App\Models\Party::CLASSIFICATIONS as $val => $meta)
                    <option value="{{ $val }}" @selected(old('classification', $party->classification) === $val)>{{ $meta[0] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">TDS rate % <span class="text-gray-400 font-normal">(services)</span></label>
                <input type="number" step="0.01" min="0" max="100" name="tds_rate" value="{{ old('tds_rate', $party->tds_rate) }}" placeholder="e.g. 2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">TDS section</label>
                <input type="text" name="tds_section" value="{{ old('tds_section', $party->tds_section) }}" placeholder="194C, 194J…" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">GSTIN</label>
                <input type="text" name="gstin" value="{{ old('gstin', $party->gstin) }}" maxlength="15" placeholder="27ABCDE1234F1Z5" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PAN</label>
                <input type="text" name="pan" value="{{ old('pan', $party->pan) }}" maxlength="10" placeholder="ABCDE1234F" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $party->email) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $party->phone) }}" maxlength="15" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <textarea name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('address', $party->address) }}</textarea>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                <input type="text" name="city" value="{{ old('city', $party->city) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                <select name="state_code" x-model="stateCode" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Select…</option>
                    @foreach($states as $code => $name)
                    <option value="{{ $code }}" @selected(old('state_code', $party->state_code) === $code)>{{ $name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="state" :value="states[stateCode] || ''">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                <input type="text" name="pincode" value="{{ old('pincode', $party->pincode) }}" maxlength="10" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        {{-- Bank / payment details — used to build bank payment files --}}
        <div class="pt-4 border-t border-gray-100">
            <div class="flex items-center gap-2 mb-3">
                <i class="fa fa-building-columns text-navy-500"></i>
                <h3 class="text-sm font-semibold text-gray-800">Bank &amp; payment details</h3>
                <span class="text-xs text-gray-400">(for payment files / NEFT)</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account holder name</label>
                    <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder', $party->bank_account_holder) }}" maxlength="120" placeholder="As per bank records" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bank name</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $party->bank_name) }}" maxlength="120" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account number</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $party->bank_account_number) }}" maxlength="34" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IFSC</label>
                    <input type="text" name="bank_ifsc" value="{{ old('bank_ifsc', $party->bank_ifsc) }}" maxlength="11" placeholder="HDFC0001234" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">UPI ID <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="upi_id" value="{{ old('upi_id', $party->upi_id) }}" maxlength="120" placeholder="name@bank" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $party->is_active)) class="rounded border-gray-300">
            Active
        </label>

        <div class="flex items-center gap-3 pt-2 border-t border-gray-50">
            <button type="submit" class="px-5 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">Save Changes</button>
            <a href="{{ route('accounting.parties.show', $party) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>
@endsection
