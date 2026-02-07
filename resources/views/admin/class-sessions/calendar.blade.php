<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kalender Kelas Bersama</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.class-sessions.index') }}" class="px-4 py-2 rounded-md border text-sm">Daftar</a>
                <a href="{{ route('admin.class-sessions.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Jadwal</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.class-sessions.calendar') }}" class="p-6 grid md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bulan</label>
                        <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="mt-1 w-full border-gray-300 rounded-md" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tahun</label>
                        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="mt-1 w-full border-gray-300 rounded-md" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Guru</label>
                        <select name="teacher_id" class="mt-1 w-full border-gray-300 rounded-md">
                            <option value="">Semua</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected($teacherId == $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kelas</label>
                        <select name="class_group_id" class="mt-1 w-full border-gray-300 rounded-md">
                            <option value="">Semua</option>
                            @foreach ($classGroups as $group)
                                <option value="{{ $group->id }}" @selected($classGroupId == $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <div class="flex items-center gap-3">
                            <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Terapkan</button>
                            <a href="{{ route('admin.class-sessions.calendar') }}" class="text-sm text-gray-500">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    @php
                        $startOffset = $firstDayOfWeek - 1;
                        $totalCells = $startOffset + $daysInMonth;
                        $rows = (int) ceil($totalCells / 7);
                        $day = 1;
                        $dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
                    @endphp

                    <table class="min-w-full text-sm border-collapse">
                        <thead>
                            <tr class="text-left text-gray-500">
                                @foreach ($dayNames as $dayName)
                                    <th class="py-2 px-2 border-b">{{ $dayName }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @for ($row = 0; $row < $rows; $row++)
                                <tr>
                                    @for ($col = 0; $col < 7; $col++)
                                        @php
                                            $cellIndex = ($row * 7) + $col;
                                            $inMonth = $cellIndex >= $startOffset && $day <= $daysInMonth;
                                            $dateKey = $inMonth ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
                                            $items = $inMonth ? ($sessionsByDate[$dateKey] ?? collect()) : collect();
                                        @endphp
                                        <td class="align-top border-b border-gray-100 px-2 py-2 w-1/7">
                                            @if ($inMonth)
                                                <div class="text-xs font-semibold text-gray-500">{{ $day }}</div>
                                                <div class="mt-2 space-y-2">
                                                    @foreach ($items as $session)
                                                        <div class="rounded-md border border-gray-200 p-2 text-xs">
                                                            <div class="font-semibold">{{ $session->classGroup?->name ?? 'Kelas' }}</div>
                                                            <div>{{ $session->teacher?->name ?? '-' }}</div>
                                                            <div>{{ $session->session_time?->format('H:i') ?? '-' }} • {{ $session->subject ?? '-' }}</div>
                                                            <div class="text-gray-500">Hadir: {{ $session->present_students_count }} / {{ $session->total_students_count }}</div>
                                                            <a href="{{ route('admin.class-sessions.show', $session) }}" class="text-indigo-600">Detail</a>
                                                        </div>
                                                    @endforeach
                                                    @if ($items->isEmpty())
                                                        <div class="text-xs text-gray-400">-</div>
                                                    @endif
                                                </div>
                                                @php $day++; @endphp
                                            @else
                                                <div class="text-xs text-gray-300">&nbsp;</div>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
