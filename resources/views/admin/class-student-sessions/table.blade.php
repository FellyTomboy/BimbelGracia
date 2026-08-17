<x-app-layout>
    <x-slot name="title">Jadwal Kelas Bersama</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jadwal Kelas Bersama</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.class-student-sessions.index') }}" class="px-4 py-2 rounded-md border text-sm">Kalender</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
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
                                <th class="py-2">Guru</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Status</th>
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
                                        <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->name ?? '-'" type="guru" />
                                    </td>
                                    <td class="py-2">
                                        @foreach ($attendance->students as $student)
                                            <x-hibernated-label :model="$student" :label="$student->display_name" type="murid privat" /><br>
                                        @endforeach
                                    </td>
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>