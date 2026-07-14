@props(['label', 'column', 'align' => 'left'])
@php
    $curSort = request('sort');
    $curDir  = strtolower((string) request('dir')) === 'desc' ? 'desc' : 'asc';
    $active  = $curSort === $column;
    $nextDir = $active && $curDir === 'asc' ? 'desc' : 'asc';
    $qs = array_merge(request()->query(), ['sort' => $column, 'dir' => $nextDir]);
@endphp
<a href="{{ url()->current() }}?{{ http_build_query($qs) }}"
   {{ $attributes->merge(['class' => 'group inline-flex items-center gap-1 hover:text-gray-700 select-none '.($align === 'right' ? 'flex-row-reverse' : '')]) }}>
    <span>{{ $label }}</span>
    @if($active)
        <i class="fa fa-sort-{{ $curDir === 'asc' ? 'up' : 'down' }} text-[10px] text-navy-500"></i>
    @else
        <i class="fa fa-sort text-[10px] text-gray-300 group-hover:text-gray-400"></i>
    @endif
</a>
