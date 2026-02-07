<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Mengajar Guru</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.history.teachers') }}" class="p-6 grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Guru</label>
                        <select name="teacher_id" class="mt-1 w-full border-gray-300 rounded-md">
                            <option value="">Semua</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected($teacherId == $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Terapkan</button>
                    </div>
                    <div class="flex items-end">
                        <a href="{{ route('admin.history.teachers') }}" class="text-sm text-gray-500">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Periode</th>
                                <th class="py-2">Guru</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Program</th>
                                <th class="py-2">Enrollment</th>
                                <th class="py-2">Total Les</th>
                                <th class="py-2">Total Gaji</th>
                                <th class="py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($attendances as $attendance)
                                @php
                                    $rate = $attendance->enrollment?->teacher_rate ?? 0;
                                    $total = $attendance->total_lessons * $rate;
                                @endphp
                                <tr>
                                    <td class="py-2">{{ sprintf('%02d', $attendance->month) }}/{{ $attendance->year }}</td>
                                    <td class="py-2">{{ $attendance->enrollment?->teacher?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $attendance->students->pluck('name')->implode(', ') ?: '-' }}</td>
                                    <td class="py-2">{{ $attendance->enrollment?->program?->name ?? '-' }}</td>
                                    <td class="py-2">#{{ $attendance->enrollment_id }}</td>
                                    <td class="py-2">{{ $attendance->total_lessons }}</td>
                                    <td class="py-2">Rp {{ number_format($total) }}</td>
                                    <td class="py-2">{{ $attendance->teacher_payment_status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $attendances->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
