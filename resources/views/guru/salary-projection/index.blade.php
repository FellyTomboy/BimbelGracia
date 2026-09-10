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
                    @if (app(\App\Services\AttendanceFineService::class)->isLatePenaltyEnabled())
                        <p class="text-2xl font-bold text-rose-600">-Rp {{ number_format($totals['late_penalty'] ?? 0) }}</p>
                    @else
                        <p class="text-2xl font-bold text-gray-400" title="Denda dinonaktifkan">Nonaktif</p>
                    @endif
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
                                <th class="py-3 px-4 font-medium">Enrollment</th>
                                <th class="py-3 px-4 font-medium">Murid</th>
                                <th class="py-3 px-4 font-medium text-center">Total Pertemuan</th>
                                <th class="py-3 px-4 font-medium">Gaji / Pertemuan</th>
                                <th class="py-3 px-4 font-medium">Total Gaji</th>
                                <th class="py-3 px-4 font-medium">Denda</th>
                                <th class="py-3 px-4 font-medium">Status Presensi</th>
                                <th class="py-3 px-4 font-medium">Status Gaji</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($groupedRows as $row)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 text-gray-900">{{ sprintf('%02d/%04d', $month, $year) }}</td>
                                    <td class="py-3 px-4">
                                        <x-hibernated-label :model="$row['program']" :label="$row['program']?->name ?? '-'" type="program" />
                                        @if (($row['type'] ?? '') === 'kelas')
                                            <span class="ml-1 inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">Kelas</span>
                                        @elseif (($row['type'] ?? '') === 'kelas_tanpa_murid')
                                            <span class="ml-1 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Kelas</span>
                                        @else
                                            <span class="ml-1 inline-flex items-center rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">Privat</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">#{{ $row['enrollment_id'] }}</td>
                                    <td class="py-3 px-4">
                                        @if ($row['students']->count() > 0)
                                            @foreach ($row['students'] as $student)
                                                <x-hibernated-label :model="$student" :label="$student->display_name" type="murid privat" />{{ !$loop->last ? ' & ' : '' }}
                                            @endforeach
                                            @if (($row['present_count'] ?? 0) > 1)
                                                <span class="ml-1 text-xs text-gray-400">({{ $row['label_detail'] ?? '' }})</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                            {{ $row['session_count'] }}
                                        </span>
                                        @if ($row['late_count'] > 0)
                                            <span class="ml-1 text-xs text-amber-600" title="{{ $row['late_count'] }} terlambat">
                                                ({{ $row['late_count'] }} terlambat)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">
                                        @if ($row['session_count'] > 0)
                                            Rp {{ number_format($row['rate_per_session']) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-900 font-semibold">Rp {{ number_format($row['total_salary']) }}</td>
                                    <td class="py-3 px-4">
                                        @if ($row['total_penalty'] > 0)
                                            <span class="text-rose-600 font-medium">-Rp {{ number_format($row['total_penalty']) }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($row['overall_status'] === 'terima')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Diterima</span>
                                        @elseif ($row['overall_status'] === 'terlambat')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Terlambat</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">Pending</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($row['payment_status'] === 'paid')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Dibayar</span>
                                        @elseif ($row['payment_status'] === 'held')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Ditahan</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $row['payment_status'] ?? 'Belum' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10">
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
    <script>
    (function () {
        var key = 'scroll_' + location.pathname + '?{{ http_build_query(request()->query()) }}';
        window.addEventListener('load', function () {
            var pos = sessionStorage.getItem(key);
            if (pos !== null) { window.scrollTo(0, parseInt(pos, 10)); sessionStorage.removeItem(key); }
        });
        document.querySelectorAll('form[method=POST], a[href*="delete"], a[href*="destroy"]').forEach(function (el) {
            el.addEventListener('click', function () { sessionStorage.setItem(key, window.scrollY); });
        });
        document.querySelectorAll('form[method=GET]').forEach(function (form) {
            form.addEventListener('submit', function () { sessionStorage.setItem(key, 0); });
        });
    })();
    </script>
</x-app-layout>