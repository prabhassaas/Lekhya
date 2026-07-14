@extends('layouts.app')
@section('title', 'Company Settings')
@section('page-title', 'Settings')

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
    @include('settings._nav')

    <form method="POST" action="{{ route('settings.company.update') }}"
          x-data="companyForm({{ \Illuminate\Support\Js::from($states) }}, @js(old('state_code', $tenant->state_code)))"
          class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" x-ref="name" value="{{ old('name', $tenant->name) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">GSTIN</label>
                <div class="flex gap-2">
                    <input type="text" name="gstin" x-ref="gstin" value="{{ old('gstin', $tenant->gstin) }}" maxlength="15" placeholder="27ABCDE1234F1Z5" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase">
                    <button type="button" @click="fetchGstin()" :disabled="gstinBusy"
                            class="px-3 py-2 border border-navy-300 text-navy-700 hover:bg-navy-50 rounded-lg text-sm font-medium whitespace-nowrap disabled:opacity-50">
                        <i class="fa fa-wand-magic-sparkles mr-1" x-show="!gstinBusy"></i>
                        <i class="fa fa-spinner fa-spin mr-1" x-show="gstinBusy"></i>Fetch details
                    </button>
                </div>
                <p class="text-xs mt-1" :class="gstinOk ? 'text-green-600' : (gstinMsg ? 'text-red-500' : 'text-gray-400')"
                   x-text="gstinMsg || '15-character GSTIN — click Fetch to auto-fill from the GST registry.'"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PAN</label>
                <input type="text" name="pan" x-ref="pan" value="{{ old('pan', $tenant->pan) }}" maxlength="10" placeholder="ABCDE1234F" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $tenant->email) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <textarea name="address" x-ref="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('address', $tenant->address) }}</textarea>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                <input type="text" name="city" value="{{ old('city', $tenant->city) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                <select name="state_code" x-model="stateCode" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Select…</option>
                    @foreach($states as $code => $name)
                    <option value="{{ $code }}" @selected(old('state_code', $tenant->state_code) === $code)>{{ $name }}</option>
                    @endforeach
                </select>
                {{-- Persist the state name alongside the GST state code --}}
                <input type="hidden" name="state" :value="states[stateCode] || ''">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                <input type="text" name="pincode" value="{{ old('pincode', $tenant->pincode) }}" maxlength="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2 border-t border-gray-50">
            <button type="submit" class="px-5 py-2 bg-navy-600 hover:bg-navy-700 text-white text-sm font-semibold rounded-lg">Save Changes</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function companyForm(states, stateCode) {
  return {
    states, stateCode: stateCode || '',
    gstinBusy: false, gstinMsg: '', gstinOk: false,
    async fetchGstin() {
      const g = (this.$refs.gstin.value || '').trim().toUpperCase();
      this.$refs.gstin.value = g;
      if (g.length !== 15) { this.gstinOk = false; this.gstinMsg = 'Enter a 15-character GSTIN'; return; }
      this.gstinBusy = true; this.gstinMsg = '';
      try {
        const res = await fetch('{{ route('gstin.verify') }}?gstin=' + encodeURIComponent(g));
        const d = await res.json();
        if (d.valid && d.legal_name) {
          this.gstinOk = true; this.gstinMsg = '✓ ' + d.legal_name;
          if (!this.$refs.name.value.trim()) this.$refs.name.value = d.legal_name;
          if (d.pan) this.$refs.pan.value = d.pan;
          if (d.address && !this.$refs.address.value.trim()) this.$refs.address.value = d.address;
          if (d.state_code) this.stateCode = String(d.state_code).padStart(2, '0');
        } else if (d.valid) {
          // Format valid but no verified name (offline mock). Fill what we can
          // derive; never invent a business name.
          this.gstinOk = true; this.gstinMsg = d.message || 'GSTIN format is valid';
          if (d.pan) this.$refs.pan.value = d.pan;
          if (d.state_code) this.stateCode = String(d.state_code).padStart(2, '0');
        } else {
          this.gstinOk = false; this.gstinMsg = d.message || 'Invalid GSTIN — check and try again';
        }
      } catch (e) {
        this.gstinOk = false; this.gstinMsg = 'Could not reach the verification service';
      } finally {
        this.gstinBusy = false;
      }
    },
  };
}
</script>
@endpush
@endsection
