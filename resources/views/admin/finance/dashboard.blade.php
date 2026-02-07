<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Keuangan</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.finance.index') }}" class="p-6 grid md:grid-cols-3 gap-4">
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

            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Pendapatan Kotor</p>
                    <p class="text-2xl font-semibold">Rp {{ number_format($gross) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Biaya Guru</p>
                    <p class="text-2xl font-semibold">Rp {{ number_format($teacherCost) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Pendapatan Bersih</p>
                    <p class="text-2xl font-semibold">Rp {{ number_format($net) }}</p>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Murid Aktif</p>
                    <p class="text-2xl font-semibold">{{ $activeStudents }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Guru Aktif</p>
                    <p class="text-2xl font-semibold">{{ $activeTeachers }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Validasi Error</p>
                    <p class="text-2xl font-semibold">{{ $needsFix }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Grafik Bulanan (6 bulan terakhir)</h3>
                <canvas id="financeChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const labels = @json($chart['labels']);
        const grossSeries = @json($chart['gross']);
        const costSeries = @json($chart['cost']);
        const netSeries = @json($chart['net']);

        const ctx = document.getElementById('financeChart');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Pendapatan Kotor',
                        data: grossSeries,
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15, 118, 110, 0.15)',
                        tension: 0.3,
                    },
                    {
                        label: 'Biaya Guru',
                        data: costSeries,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.15)',
                        tension: 0.3,
                    },
                    {
                        label: 'Pendapatan Bersih',
                        data: netSeries,
                        borderColor: '#1d4ed8',
                        backgroundColor: 'rgba(29, 78, 216, 0.15)',
                        tension: 0.3,
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
