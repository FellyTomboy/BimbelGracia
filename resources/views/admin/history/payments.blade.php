<x-app-layout>
    <x-slot name="title">Riwayat Pembayaran</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Pembayaran</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.history.payments') }}" class="p-6 grid md:grid-cols-3 gap-4">
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
                                <th class="py-2">Guru</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Enrollment</th>
                                <th class="py-2">Total Pertemuan</th>
                                <th class="py-2">Total Tagihan</th>
                                <th class="py-2">Status Ortu</th>
                                <th class="py-2">Status Guru</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($attendances as $attendance)
                                @php
                                    $calcService = app(\App\Services\CalculationService::class);
                                    $billing = $calcService->calculateAttendanceBilling($attendance);
                                @endphp
                                <tr>
                                    <td class="py-2">{{ sprintf('%02d', $attendance->month) }}/{{ $attendance->year }}</td>
                                    <td class="py-2">
                                        <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" />
                                    </td>
                                    <td class="py-2">
                                        <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->displayName ?? '-'" type="guru" />
                                    </td>
                                    <td class="py-2">
                                        @if ($attendance->students->count() > 0)
                                            @foreach ($attendance->students as $student)
                                                <x-hibernated-label :model="$student" :label="$student->display_name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-2">#{{ $attendance->enrollment_id }}</td>
                                    <td class="py-2">{{ $attendance->total_lessons }}</td>
                                    <td class="py-2">Rp {{ number_format($billing['total']) }}</td>
                                    <td class="py-2">{{ $attendance->parent_payment_status }}</td>
                                    <td class="py-2">{{ $attendance->teacher_payment_status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $attendances->withQueryString()->links() }}
                    </div>
                </div>
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
