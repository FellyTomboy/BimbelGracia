@props([
    'placeholder' => 'Cari...',
    'route' => null,
])

<form method="GET" action="{{ $route ?? request()->url() }}" class="flex items-center gap-2">
    <input
        type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="{{ $placeholder }}"
        class="w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        autocomplete="off"
    />
    @if(request('search'))
        <a href="{{ $route ?? request()->url() }}" class="text-sm text-gray-500 hover:text-gray-700">&times; Reset</a>
    @endif
    @if(request('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}" />
    @endif
    @if(request('direction'))
        <input type="hidden" name="direction" value="{{ request('direction') }}" />
    @endif
</form>