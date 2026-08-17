<x-app-layout>
    <x-slot name="title">Proyeksi Gaji Bulanan</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proyeksi Gaji Bulanan</h2>
            <p class="text-sm text-gray-500 mt-0.5">Proyeksi gaji berdasarkan presensi yang telah divalidasi</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <form method="GET" action="{{ route('guru.salary-projection.index') }}" class="flex items-end gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Bulan</label>
                        <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="w-20 rounded-xl border-gray-200 text-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tahun</label>
                        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="w-24 rounded-xl border-gray-200 text-sm" required />
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">Terapkan</button>
                </form>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Diterima</p>
                    <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($totals['validated']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Pending</p>
                    <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($totals['pending']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Ditolak</p>
                    <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($totals['rejected'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Denda</p>
                    <p class="text-2xl font-bold text-rose-600">-Rp {{ number_format($totals['late_penalty'] ?? 0) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 bg-gradient-to-br from-indigo-50 to-violet-50">
                    <p class="text-xs text-gray-500">Total Proyeksi</p>
                    <p class="text-2xl font-bold text-indigo-700">Rp {{ number_format($totals['grand']) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Grafik Bulanan (6 bulan terakhir)</h3>
                <canvas id="salaryChart" height="100"></canvas>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 font-medium">Periode</th>
                                <th class="py-3 px-4 font-medium">Program</th>
                                <th class="py-3 px-4 font-medium">Murid</th>
                                <th class="py-3 px-4 font-medium">Enrollment</th>
                                <th class="py-3 px-4 font-medium">Gaji / Pertemuan</th>
                                <th class="py-3 px-4 font-medium">Total Gaji</th>
                                <th class="py-3 px-4 font-medium">Denda</th>
                                <th class="py-3 px-4 font-medium">Status Presensi</th>
                                <th class="py-3 px-4 font-medium">Status Gaji</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($attendances as $attendance)
                                @php
                                    $rate = $attendance->teacher_rate ?? $attendance->enrollment?->teacher_rate ?? 0;
                                    $isLate = $attendance->status_validation === 'terlambat';
                                    $penalty = $isLate ? (int) ($rate * 0.1) : 0;
                                    $total = $rate - $penalty;
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 text-gray-900">{{ $attendance->lesson_date?->format('d M Y') ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" />
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($attendance->students->count() > 0)
                                            @foreach ($attendance->students as $student)
                                                <x-hibernated-label :model="$student" :label="$student->display_name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">#{{ $attendance->enrollment_id }}</td>
                                    <td class="py-3 px-4 text-gray-600">Rp {{ number_format($rate) }}</td>
                                    <td class="py-3 px-4 text-gray-900 font-medium">Rp {{ number_format($total) }}</td>
                                    <td class="py-3 px-4">
                                        @if ($isLate)
                                            <span class="text-rose-600 font-medium">-Rp {{ number_format($penalty) }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($attendance->status_validation === 'terima')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Diterima</span>
                                        @elseif ($attendance->status_validation === 'terlambat')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Terlambat</span>
                                        @elseif ($attendance->status_validation === 'ditolak')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Ditolak</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">Pending</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($attendance->teacher_payment_status === 'paid')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Dibayar</span>
                                        @elseif ($attendance->teacher_payment_status === 'held')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Ditahan</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $attendance->teacher_payment_status ?? 'Belum' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <x-empty-state icon="💰" title="Belum ada data" description="Belum ada data gaji untuk periode ini." />
                                    </td>
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
                datasets: [{
                    label: 'Total Proyeksi',
                    data: salaryTotals,
                    backgroundColor: 'rgba(99, 102, 241, 0.3)',
                    borderColor: '#6366f1',
                    borderWidth: 2,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
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