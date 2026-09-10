<x-app-layout>
    <x-slot name="title">Riwayat Les Guru</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Les Guru</h2>
            <p class="text-sm text-gray-500 mt-0.5">Riwayat les dan status gaji per periode</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <form method="GET" action="{{ route('guru.history.index') }}" class="flex items-end gap-4">
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

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 font-medium">Periode</th>
                                <th class="py-3 px-4 font-medium">Program</th>
                                <th class="py-3 px-4 font-medium">Murid</th>
                                <th class="py-3 px-4 font-medium">Enrollment</th>
                                <th class="py-3 px-4 font-medium">Total Pertemuan</th>
                                <th class="py-3 px-4 font-medium">Gross</th>
                                <th class="py-3 px-4 font-medium">Potongan Terlambat</th>
                                <th class="py-3 px-4 font-medium">Total Gaji</th>
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
                                    <td class="py-3 px-4 text-gray-600">#{{ $row['enrollment_id'] }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                            {{ $row['session_count'] }}
                                        </span>
                                        @if ($row['late_count'] > 0)
                                            <span class="ml-1 inline-flex items-center justify-center min-w-[1.5rem] px-1 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                                                {{ $row['late_count'] }} late
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">Rp {{ number_format($row['gross_rate']) }}</td>
                                    <td class="py-3 px-4 text-red-600">
                                        @if ($row['late_penalty'] > 0)
                                            -Rp {{ number_format($row['late_penalty']) }}
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-900 font-semibold">Rp {{ number_format($row['total_salary']) }}</td>
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
                                    <td colspan="9">
                                        <x-empty-state icon="📚" title="Belum ada riwayat" description="Belum ada data les untuk periode ini." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
        </div>
    </div>

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