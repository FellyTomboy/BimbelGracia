<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Validasi Presensi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('admin.presensi.index') }}" class="flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-700">Filter Status:</label>
                    <select name="status" class="border-gray-300 rounded-md text-sm">
                        <option value="">Semua</option>
                        <option value="terima" @selected(request('status') === 'terima')>Diterima</option>
                        <option value="terlambat" @selected(request('status') === 'terlambat')>Terlambat</option>
                        <option value="ditolak" @selected(request('status') === 'ditolak')>Ditolak</option>
                    </select>
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-slate-900 text-white text-sm">Terapkan</button>
                    @if (request('status'))
                        <a href="{{ route('admin.presensi.index') }}" class="text-sm text-gray-500">Reset</a>
                    @endif
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Tanggal</th>
                                <th class="py-2">Program</th>
                                <th class="py-2">Guru</th>
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
                                        <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->name ?? '-'" type="guru" />
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
                                    <td class="py-2 flex gap-2">
                                        <form method="POST" action="{{ route('admin.presensi.validate', $attendance) }}" class="flex items-center gap-1">
                                            @csrf
                                            <select name="status" class="text-xs border-gray-300 rounded-md py-1 px-1" onchange="this.form.submit()">
                                                <option value="">Ubah</option>
                                                <option value="terima">Terima</option>
                                                <option value="terlambat">Terlambat</option>
                                                <option value="ditolak">Tolak</option>
                                            </select>
                                        </form>
                                        <a href="{{ route('admin.presensi.show', $attendance) }}" class="text-indigo-600 text-xs">Detail</a>
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
