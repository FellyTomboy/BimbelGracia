<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Les Murid</h2>
            <p class="text-sm text-gray-500 mt-0.5">Riwayat les dan tagihan per periode</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <form method="GET" action="{{ route('murid.history.index') }}" class="flex items-end gap-4">
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
                                <th class="py-3 px-4 font-medium">Guru</th>
                                <th class="py-3 px-4 font-medium">Total Pertemuan</th>
                                <th class="py-3 px-4 font-medium">Biaya / Pertemuan</th>
                                <th class="py-3 px-4 font-medium">Total Tagihan</th>
                                <th class="py-3 px-4 font-medium">Status Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($attendances as $attendance)
                                @php
                                    $student = $attendance->students->firstWhere('id', $student?->id ?? 0);
                                    $present = (int) ($student?->pivot?->total_present ?? 0);
                                    $rate = $attendance->enrollment?->parent_rate ?? 0;
                                    $total = $present * $rate;
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 text-gray-900">{{ sprintf('%02d', $attendance->month) }}/{{ $attendance->year }}</td>
                                    <td class="py-3 px-4">
                                        <x-hibernated-label :model="$attendance->enrollment?->program" :label="$attendance->enrollment?->program?->name ?? '-'" type="program" />
                                    </td>
                                    <td class="py-3 px-4">
                                        <x-hibernated-label :model="$attendance->enrollment?->teacher" :label="$attendance->enrollment?->teacher?->name ?? '-'" type="guru" />
                                    </td>
                                    <td class="py-3 px-4 text-gray-900 font-medium">{{ $present }}</td>
                                    <td class="py-3 px-4 text-gray-600">Rp {{ number_format($rate) }}</td>
                                    <td class="py-3 px-4 text-gray-900 font-medium">Rp {{ number_format($total) }}</td>
                                    <td class="py-3 px-4">
                                        @if ($attendance->parent_payment_status === 'paid')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Lunas</span>
                                        @elseif ($attendance->parent_payment_status === 'partial')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Cicil</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">{{ $attendance->parent_payment_status ?? 'Belum' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <x-empty-state icon="📚" title="Belum ada riwayat" description="Belum ada data les untuk periode ini." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>