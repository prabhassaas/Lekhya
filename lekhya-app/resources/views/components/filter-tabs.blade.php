@props(['tabs', 'active' => '', 'param' => 'tab'])
{{-- Reusable segmented tab bar for list screens. $tabs is [key => label] or
     [key => ['label' => .., 'count' => ..]]. Preserves other query params
     (sort/search) and resets pagination when switching tabs. --}}
<div class="flex items-center gap-1 border-b border-gray-200 overflow-x-auto">
    @foreach($tabs as $key => $tab)
        @php
            $label = is_array($tab) ? ($tab['label'] ?? $key) : $tab;
            $count = is_array($tab) ? ($tab['count'] ?? null) : null;
            $isActive = (string) $active === (string) $key;
            $qs = array_merge(request()->query(), [$param => $key]);
            unset($qs['page']);
            $href = url()->current() . ($key === '' ? '?' . http_build_query(array_diff_key($qs, [$param => ''])) : '?' . http_build_query($qs));
        @endphp
        <a href="{{ $href }}"
           class="px-3.5 py-2 text-sm font-medium border-b-2 -mb-px whitespace-nowrap transition {{ $isActive ? 'border-navy-600 text-navy-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            {{ $label }}
            @if($count !== null)<span class="ml-1 text-xs px-1.5 py-0.5 rounded-full {{ $isActive ? 'bg-navy-100 text-navy-700' : 'bg-gray-100 text-gray-500' }}">{{ $count }}</span>@endif
        </a>
    @endforeach
</div>
