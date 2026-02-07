<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Presensi Bulanan</h2>
            <a href="{{ route('guru.presensi.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Isi Presensi</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($errors->has('period'))
                <div class="bg-rose-50 text-rose-700 px-4 py-3 rounded-md">
                    {{ $errors->first('period') }}
                </div>
            @endif
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600">
                    Periode aktif: {{ $openWindow ? sprintf('%02d', $openWindow->month).'/'.$openWindow->year : 'Belum dibuka' }}
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Periode</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">ID Les</th>
                                <th class="py-2">Total</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($attendances as $attendance)
                                <tr>
                                    <td class="py-2">{{ sprintf('%02d', $attendance->month) }}/{{ $attendance->year }}</td>
                                    <td class="py-2">{{ $attendance->student?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $attendance->lesson?->code ?? '-' }}</td>
                                    <td class="py-2">{{ $attendance->total_lessons }}</td>
                                    <td class="py-2">{{ $attendance->status }}</td>
                                    <td class="py-2">
                                        @if ($attendance->status !== 'validated')
                                            <a href="{{ route('guru.presensi.edit', $attendance) }}" class="text-indigo-600">Edit</a>
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
