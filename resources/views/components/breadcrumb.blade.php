@props(['items' => []])

<nav class="flex items-center gap-1 text-sm text-gray-500 mb-2">
    <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700 transition-colors">Dashboard</a>
    @foreach ($items as $item)
        <span class="text-gray-300">/</span>
        @if (isset($item['url']))
            <a href="{{ $item['url'] }}" class="hover:text-gray-700 transition-colors">{{ $item['label'] }}</a>
        @else
            <span class="text-gray-700 font-medium">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>