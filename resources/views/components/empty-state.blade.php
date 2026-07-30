@props([
    'icon' => '📭',
    'title' => 'Tidak ada data',
    'description' => null,
    'action' => null,
    'actionUrl' => null,
])

<div class="flex flex-col items-center justify-center py-12 text-center">
    <div class="text-5xl mb-4">{{ $icon }}</div>
    <h3 class="text-lg font-semibold text-gray-700">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 text-sm text-gray-500 max-w-sm">{{ $description }}</p>
    @endif
    @if ($action && $actionUrl)
        <a href="{{ $actionUrl }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-all">
            {{ $action }}
        </a>
    @endif
</div>