@props(['model', 'label', 'type' => ''])

@php
    $isHibernated = $model && method_exists($model, 'trashed') && $model->trashed();
    $tooltip = $type ? ucfirst($type) . ' ini sudah dihibernasi' : 'Data ini sudah dihibernasi';
@endphp

@if ($isHibernated)
    <span title="{{ $tooltip }}" class="cursor-help border-b border-dotted border-amber-500">{{ $label }}</span>
@else
    {{ $label }}
@endif