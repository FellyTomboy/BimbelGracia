@props([
    'label' => '',
    'column' => '',
    'sort' => request('sort'),
    'direction' => request('direction'),
])

@php
    $isActive = $sort === $column;
    $newDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $icon = '';
    if ($isActive) {
        $icon = $direction === 'asc' ? ' ↑' : ' ↓';
    }
@endphp

<th class="py-2">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $newDirection]) }}" class="text-gray-500 hover:text-gray-700">
        {{ $label }}{!! $icon !!}
    </a>
</th>