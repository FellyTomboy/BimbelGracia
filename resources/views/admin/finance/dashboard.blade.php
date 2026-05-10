<x-app-layout>
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

            <div class="grid md:grid-cols-4 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Murid Aktif</p>
                    <p class="text-2xl font-semibold">{{ $activeStudents }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Murid Kelas Aktif</p>
                    <p class="text-2xl font-semibold">{{ $activeClassStudents }}</p>
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

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                <h3 class="text-lg font-semibold">Grafik Pertumbuhan Bimbel</h3>

                <div class="grid md:grid-cols-3 gap-6">
                    <div class="md:col-span-1 bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Laba Kotor vs Laba Bersih</h4>
                        <canvas id="financeChart" height="120"></canvas>
                    </div>
                    <div class="md:col-span-1 bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Murid Privat vs Kelas</h4>
                        <canvas id="studentsChart" height="120"></canvas>
                    </div>
                    <div class="md:col-span-1 bg-white shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Jumlah Guru</h4>
                        <canvas id="teachersChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        const financeLabels = @json($chartFinance['labels']);
        const grossSeries = @json($chartFinance['gross']);
        const netSeries = @json($chartFinance['net']);

        const studentsLabels = @json($chartStudents['labels']);
        const privateSeries = @json($chartStudents['private']);
        const classSeries = @json($chartStudents['class']);

        const teachersLabels = @json($chartTeachers['labels']);
        const teachersSeries = @json($chartTeachers['teachers']);

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

        new Chart(document.getElementById('financeChart'), {
            type: 'line',
            data: {
                labels: financeLabels,
                datasets: [
                    {
                        label: 'Laba Kotor',
                        data: grossSeries,
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15, 118, 110, 0.15)',
                        tension: 0.3,
                    },
                    {
                        label: 'Laba Bersih',
                        data: netSeries,
                        borderColor: '#1d4ed8',
                        backgroundColor: 'rgba(29, 78, 216, 0.15)',
                        tension: 0.3,
                    }
                ]
            },
            options: currencyOptions
        });

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

