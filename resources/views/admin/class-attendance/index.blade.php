<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Presensi Kelas</h2>
            <p class="text-sm text-gray-500 mt-0.5">Isi daftar murid yang hadir untuk setiap sesi kelas</p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            {{-- Filter --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Bulan</label>
                        <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="w-full sm:w-20 rounded-md border-gray-300 text-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tahun</label>
                        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="w-full sm:w-24 rounded-md border-gray-300 text-sm" required />
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm">Terapkan</button>
                </form>
            </div>

            {{-- Attendance List --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 pr-4">Tanggal</th>
                                <th class="py-2 pr-4">Program</th>
                                <th class="py-2 pr-4">Guru</th>
                                <th class="py-2 pr-4">Murid Hadir</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($attendances as $attendance)
                                <tr>
                                    <td class="py-2 pr-4 whitespace-nowrap">{{ $attendance->lesson_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="py-2 pr-4">{{ $attendance->enrollment?->program?->name ?? '-' }}</td>
                                    <td class="py-2 pr-4">{{ $attendance->enrollment?->teacher?->name ?? '-' }}</td>
                                    <td class="py-2 pr-4">
                                        @if ($attendance->students->isNotEmpty())
                                            <span class="text-emerald-600 font-medium">{{ $attendance->students->count() }} murid</span>
                                            <span class="text-gray-400 text-xs">({{ $attendance->students->pluck('name')->implode(', ') }})</span>
                                        @else
                                            <span class="text-amber-600 italic">Belum diisi</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        <a href="{{ route('admin.class-attendance.edit', $attendance->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $attendance->students->isNotEmpty() ? 'Edit' : 'Isi Murid' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-400">Belum ada presensi kelas untuk periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>