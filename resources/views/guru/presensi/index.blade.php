<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Presensi Saya</h2>
            <a href="{{ route('guru.presensi.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Isi Presensi</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Tanggal</th>
                                <th class="py-2">Program</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Enrollment</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($attendances as $attendance)
                                <tr>
                                    <td class="py-2">{{ $attendance->lesson_date?->format('d M Y') ?? '-' }}</td>
                                    <td class="py-2">
                                        <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" />
                                    </td>
                                    <td class="py-2">
                                        @if ($attendance->students->count() > 0)
                                            @foreach ($attendance->students as $student)
                                                <x-hibernated-label :model="$student" :label="$student->name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-2">#{{ $attendance->enrollment_id }}</td>
                                    <td class="py-2">
                                        @if ($attendance->status_validation === 'terima')
                                            <span class="text-emerald-600 font-semibold">Diterima</span>
                                        @elseif ($attendance->status_validation === 'terlambat')
                                            <span class="text-amber-600 font-semibold">Terlambat</span>
                                        @elseif ($attendance->status_validation === 'ditolak')
                                            <span class="text-rose-600 font-semibold">Ditolak</span>
                                        @else
                                            <span class="text-gray-500 font-semibold">Pending</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        @if (!in_array($attendance->status_validation, ['terima', 'ditolak']))
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
