<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Les Guru</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('guru.history.index') }}" class="p-6 grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bulan</label>
                        <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="mt-1 w-full border-gray-300 rounded-md" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tahun</label>
                        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="mt-1 w-full border-gray-300 rounded-md" required />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Terapkan</button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Periode</th>
                                <th class="py-2">Program</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Enrollment</th>
                                <th class="py-2">Total Pertemuan</th>
                                <th class="py-2">Gaji / Pertemuan</th>
                                <th class="py-2">Total Gaji</th>
                                <th class="py-2">Status Gaji</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($attendances as $attendance)
                                @php
                                    $rate = $attendance->enrollment?->teacher_rate ?? 0;
                                    $total = $attendance->total_lessons * $rate;
                                @endphp
                                <tr>
                                    <td class="py-2">{{ sprintf('%02d', $attendance->month) }}/{{ $attendance->year }}</td>
                                    <td class="py-2">{{ $attendance->enrollment?->program?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $attendance->students->pluck('name')->implode(', ') ?: '-' }}</td>
                                    <td class="py-2">#{{ $attendance->enrollment_id }}</td>
                                    <td class="py-2">{{ $attendance->total_lessons }}</td>
                                    <td class="py-2">Rp {{ number_format($rate) }}</td>
                                    <td class="py-2">Rp {{ number_format($total) }}</td>
                                    <td class="py-2">{{ $attendance->teacher_payment_status ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-500">Belum ada riwayat.</td>
                                </tr>
                            @endforelse
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
