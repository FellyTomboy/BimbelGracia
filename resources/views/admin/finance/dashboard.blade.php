<x-app-layout>
    <x-slot name="title">Dashboard Keuangan</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Keuangan</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.finance.index') }}" class="p-6 grid md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mode</label>
                        <select name="mode" class="mt-1 w-full border-gray-300 rounded-md">
                            <option value="monthly" {{ ($mode ?? 'monthly') === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                            <option value="yearly" {{ ($mode ?? 'monthly') === 'yearly' ? 'selected' : '' }}>Tahunan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rentang Start</label>
                        <input type="month" name="range_start" value="{{ $rangeStart ?? now()->subMonths(4)->format('Y-m') }}" class="mt-1 w-full border-gray-300 rounded-md" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rentang End</label>
                        <input type="month" name="range_end" value="{{ $rangeEnd ?? now()->format('Y-m') }}" class="mt-1 w-full border-gray-300 rounded-md" />
                    </div>

                    <div>
                        <button type="submit" class="w-full px-4 py-2 rounded-md bg-slate-900 text-white">Terapkan</button>
                    </div>
                </form>
            </div>

            <div class="bg-slate-50 shadow-sm sm:rounded-lg p-3 text-center text-sm text-slate-600">
                Metrik Keuangan untuk periode: <strong>{{ $periodLabel }}</strong>
            </div>

            <div class="grid md:grid-cols-6 gap-4">
                <div class="bg-blue-50 shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">Privat Kotor</p>
                    <p class="text-lg font-semibold text-blue-900">Rp {{ number_format($privatGross) }}</p>
                </div>
                <div class="bg-blue-50 shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">Biaya Guru Privat</p>
                    <p class="text-lg font-semibold text-blue-900">Rp {{ number_format($privatTeacherCost) }}</p>
                </div>
                <div class="bg-blue-100 shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-blue-700 font-medium uppercase tracking-wide">Privat Bersih</p>
                    <p class="text-lg font-semibold text-blue-900">Rp {{ number_format($privatNet) }}</p>
                </div>
                <div class="bg-teal-50 shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-teal-600 font-medium uppercase tracking-wide">Kelas Kotor</p>
                    <p class="text-lg font-semibold text-teal-900">Rp {{ number_format($kelasGross) }}</p>
                </div>
                <div class="bg-teal-50 shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-teal-600 font-medium uppercase tracking-wide">Biaya Guru Kelas</p>
                    <p class="text-lg font-semibold text-teal-900">Rp {{ number_format($kelasTeacherCost) }}</p>
                </div>
                <div class="bg-teal-100 shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-teal-700 font-medium uppercase tracking-wide">Kelas Bersih</p>
                    <p class="text-lg font-semibold text-teal-900">Rp {{ number_format($kelasNet) }}</p>
                </div>
            </div>

            <div class="grid md:grid-cols-6 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Murid Aktif (Total)</p>
                    <p class="text-2xl font-semibold">{{ $activeStudents }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Murid Privat Aktif</p>
                    <p class="text-2xl font-semibold">{{ $activePrivateStudents }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Murid Kelas Aktif</p>
                    <p class="text-2xl font-semibold">{{ $activeClassStudents }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Guru Aktif (Total)</p>
                    <p class="text-2xl font-semibold">{{ $activeTeachers }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Guru (Rata-rata)</p>
                    <p class="text-2xl font-semibold">{{ $activeTeachersPeriod }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <p class="text-sm text-gray-500">Validasi Error</p>
                    <p class="text-2xl font-semibold">{{ $needsFix }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                <h3 class="text-lg font-semibold">Grafik Pertumbuhan Bimbel</h3>

                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Laba Privat (Kotor & Bersih)</h4>
                        <canvas id="privatChart" height="120"></canvas>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Laba Kelas (Kotor & Bersih)</h4>
                        <canvas id="kelasChart" height="120"></canvas>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Laba Total (Kotor & Bersih)</h4>
                        <canvas id="totalChart" height="120"></canvas>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6 mt-4">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Murid Privat vs Kelas</h4>
                        <canvas id="studentsChart" height="100"></canvas>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Jumlah Guru</h4>
                        <canvas id="teachersChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        const financeLabels = @json($chartFinance['labels']);
        const privatGross = @json($chartFinance['privatGross']);
        const privatNet = @json($chartFinance['privatNet']);
        const kelasGross = @json($chartFinance['kelasGross']);
        const kelasNet = @json($chartFinance['kelasNet']);
        const totalGross = privatGross.map((v, i) => v + kelasGross[i]);
        const totalNet = privatNet.map((v, i) => v + kelasNet[i]);

        const studentsLabels = @json($chartStudents['labels']);
        const privateSeries = @json($chartStudents['private']);
        const classSeries = @json($chartStudents['class']);

        const teachersLabels = @json($chartTeachers['labels']);
        const teachersSeries = @json($chartTeachers['series']);

        const currencyOptions = {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        };

        function makeGrossNetChart(canvasId, grossData, netData, grossLabel, netLabel, grossColor, netColor) {
            new Chart(document.getElementById(canvasId), {
                type: 'line',
                data: {
                    labels: financeLabels,
                    datasets: [
                        {
                            label: grossLabel,
                            data: grossData,
                            borderColor: grossColor,
                            backgroundColor: grossColor + '22',
                            tension: 0.3,
                        },
                        {
                            label: netLabel,
                            data: netData,
                            borderColor: netColor,
                            backgroundColor: netColor + '22',
                            borderDash: [5, 5],
                            tension: 0.3,
                        }
                    ]
                },
                options: currencyOptions
            });
        }

        makeGrossNetChart('privatChart', privatGross, privatNet, 'Kotor', 'Bersih', '#1d4ed8', '#60a5fa');
        makeGrossNetChart('kelasChart', kelasGross, kelasNet, 'Kotor', 'Bersih', '#0f766e', '#5eead4');
        makeGrossNetChart('totalChart', totalGross, totalNet, 'Kotor', 'Bersih', '#7c3aed', '#a78bfa');

        new Chart(document.getElementById('studentsChart'), {
            type: 'line',
            data: {
                labels: studentsLabels,
                datasets: [
                    {
                        label: 'Murid Privat',
                        data: privateSeries,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.15)',
                        tension: 0.3,
                    },
                    {
                        label: 'Murid Kelas',
                        data: classSeries,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.15)',
                        tension: 0.3,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        new Chart(document.getElementById('teachersChart'), {
            type: 'line',
            data: {
                labels: teachersLabels,
                datasets: [
                    {
                        label: 'Guru',
                        data: teachersSeries,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.15)',
                        tension: 0.3,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</x-app-layout>
