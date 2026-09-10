@props(['status'])

@php
    $config = match ($status) {
        'lulus' => ['classes' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'label' => '🎓 Lulus'],
        'hibernasi' => ['classes' => 'bg-gray-100 text-gray-600 border-gray-200', 'label' => 'Hibernasi'],
        'active' => ['classes' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'Aktif'],
        default => ['classes' => 'bg-gray-100 text-gray-600 border-gray-200', 'label' => ucfirst((string) $status)],
    };
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $config['classes'] }}">
    {{ $config['label'] }}
</span>
