<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Periode Presensi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.attendance-windows.store') }}" class="p-6 grid md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bulan</label>
                        <input type="number" name="month" value="{{ old('month') }}" min="1" max="12" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('month')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tahun</label>
                        <input type="number" name="year" value="{{ old('year') }}" min="2020" max="2100" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('year')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Buka Periode</button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Periode</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Dibuka</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($windows as $window)
                                <tr>
                                    <td class="py-2 font-medium">{{ sprintf('%02d', $window->month) }}/{{ $window->year }}</td>
                                    <td class="py-2">{{ $window->is_open ? 'open' : 'closed' }}</td>
                                    <td class="py-2">{{ $window->opened_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td class="py-2">
                                        @if ($window->is_open)
                                            <form method="POST" action="{{ route('admin.attendance-windows.close', $window) }}">
                                                @csrf
                                                <button type="submit" class="text-rose-600">Tutup</button>
                                            </form>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
