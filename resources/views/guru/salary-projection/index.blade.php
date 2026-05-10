<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proyeksi Gaji Bulanan</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('guru.salary-projection.index') }}" class="p-6 grid md:grid-cols-3 gap-4">
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

            <div class="grid md:grid-cols-5 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Diterima</p>
                    <p class="text-2xl font-semibold">Rp {{ number_format($totals['validated']) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Pending</p>
                    <p class="text-2xl font-semibold">Rp {{ number_format($totals['pending']) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Ditolak</p>
                    <p class="text-2xl font-semibold">Rp {{ number_format($totals['rejected'] ?? 0) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Denda Keterlambatan</p>
                    <p class="text-2xl font-semibold text-rose-600">-Rp {{ number_format($totals['late_penalty'] ?? 0) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Total Proyeksi</p>
                    <p class="text-2xl font-semibold">Rp {{ number_format($totals['grand']) }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Grafik Bulanan (6 bulan terakhir)</h3>
                <canvas id="salaryChart" height="120"></canvas>
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
                                <th class="py-2">Gaji / Pertemuan</th>
                                <th class="py-2">Total Gaji</th>
                                <th class="py-2">Denda</th>
                                <th class="py-2">Status Presensi</th>
                                <th class="py-2">Status Gaji</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($attendances as $attendance)
                                @php
                                    $rate = $attendance->enrollment?->teacher_rate ?? 0;
                                    $isLate = $attendance->status_validation === 'terlambat';
                                    $penalty = $isLate ? $rate * 0.1 : 0;
                                    $total = $rate - $penalty;
                                @endphp
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
                                    <td class="py-2">Rp {{ number_format($rate) }}</td>
                                    <td class="py-2">Rp {{ number_format($total) }}</td>
                                    <td class="py-2">
                                        @if ($isLate)
                                            <span class="text-rose-600">-Rp {{ number_format($penalty) }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        @if ($attendance->status_validation === 'terima')
                                            <span class="text-emerald-600 font-semibold">Diterima</span>
                                        @elseif ($attendance->status_validation === 'terlambat')
                                            <span class="text-amber-600 font-semibold">Terlambat</span>
                                        @elseif ($attendance->status_validation === 'ditolak')
                                            <span class="text-rose-600 font-semibold">Ditolak</span>
                                        @else
                                            <span class="text-gray-500">Pending</span>
                                        @endif
                                    </td>
                                    <td class="py-2">{{ $attendance->teacher_payment_status ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-4 text-center text-gray-500">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const salaryLabels = @json($chart['labels']);
        const salaryTotals = @json($chart['totals']);
        const salaryCtx = document.getElementById('salaryChart');

        new Chart(salaryCtx, {
            type: 'bar',
            data: {
                labels: salaryLabels,
                datasets: [
                    {
                        label: 'Total Proyeksi',
                        data: salaryTotals,
                        backgroundColor: 'rgba(15, 118, 110, 0.5)',
                        borderColor: '#0f766e',
                        borderWidth: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
</x-app-layout>
