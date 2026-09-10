<x-app-layout>
    <x-slot name="title">Pembayaran Ortu</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pembayaran Ortu</h2>
            <a href="{{ route('admin.payments.ortu-summary') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700
                      hover:bg-indigo-100 rounded-lg text-xs font-medium transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M3 14h18M3 6h18M3 18h18"/>
                </svg>
                Ringkasan Tabel
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.payments.ortu') }}" class="p-6 grid md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bulan</label>
                        <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="mt-1 w-full border-gray-300 rounded-md" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tahun</label>
                        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="mt-1 w-full border-gray-300 rounded-md" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cari Ortu</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama orang tua..." class="mt-1 w-full border-gray-300 rounded-md" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Terapkan</button>
                    </div>
                </form>
            </div>

            @forelse ($summaries as $summary)
                @php
                    $proof = $summary['proof'];
                    $isLunas = $proof?->status === 'approved';
                    $isPending = $proof?->status === 'pending';
                    $hasPaid = $summary['has_paid'] ?? false;
                    $overallStatus = $isLunas ? 'paid' : ($isPending ? 'pending' : ($hasPaid ? 'paid' : 'unpaid'));
                    $idsJson = json_encode($summary['attendance_ids'] ?? []);
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    {{-- Header: nama, total, dan aksi per bulan --}}
                    <div class="p-4 border-b bg-gray-50 flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <span class="font-semibold text-lg">{{ $summary['parent_name'] }}</span>
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ $summary['students']->pluck('student.display_name')->filter()->join(', ') }}
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                @if ($isLunas)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Lunas</span>
                                @elseif ($isPending)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Menunggu</span>
                                @else
                                    @if (isset($summary['total_before']) && $summary['total_before'] > $summary['total'])
                                        <div class="text-xs text-gray-400 line-through">Rp {{ number_format($summary['total_before']) }}</div>
                                    @endif
                                    <span class="font-semibold">Rp {{ number_format($summary['total']) }}</span>
                                @endif
                            </div>
                            {{-- Aksi per bulan (tidak muncul kalau sudah lunas) --}}
                            @if (!$isLunas)
                                <form method="POST" action="{{ route('admin.payments.ortu.monthly-payment') }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="hidden" name="attendance_ids" value="{{ $summary['attendance_ids'] ? implode(',', $summary['attendance_ids']) : '' }}" />
                                    <select name="parent_payment_status" class="border-gray-300 rounded-md text-xs">
                                        <option value="unpaid" @selected($overallStatus === 'unpaid')>Belum bayar</option>
                                        <option value="paid" @selected($overallStatus === 'paid')>Sudah bayar</option>
                                    </select>
                                    <button type="submit" class="text-indigo-600 text-xs font-medium hover:underline">Simpan</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Rincian per murid --}}
                    <div class="p-4 text-gray-900 overflow-x-auto">
                        @foreach ($summary['students'] as $studentEntry)
                            <div class="mb-4 last:mb-0">
                                <h4 class="text-sm font-semibold text-indigo-700 mb-2">{{ $studentEntry['student']?->display_name }}</h4>
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500">
                                            <th class="py-1">Program / Guru</th>
                                            <th class="py-1">Biaya</th>
                                            <th class="py-1">Jumlah</th>
                                            <th class="py-1">Subtotal<br><span class="font-normal text-gray-400">(blm diskon)</span></th>
                                            <th class="py-1">Diskon</th>
                                            <th class="py-1">Total Akhir</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($studentEntry['lines'] as $line)
                                            <tr>
                                                <td class="py-1">{{ $line['label'] }}</td>
                                                <td class="py-1">Rp {{ number_format($line['rate']) }}</td>
                                                <td class="py-1">{{ $line['count'] }}x</td>
                                                <td class="py-1">Rp {{ number_format($line['total']) }}</td>
                                                <td class="py-1">
                                                    @if (($line['discount_amount'] ?? 0) > 0)
                                                        <span class="text-rose-600 text-xs">-Rp {{ number_format($line['discount_amount']) }}</span>
                                                        @if ($line['discount_label'])
                                                            <span class="text-gray-400 text-xs block">{{ $line['discount_label'] }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-gray-400 text-xs">-</span>
                                                    @endif
                                                </td>
                                                <td class="py-1 font-medium">Rp {{ number_format($line['total_after'] ?? $line['total']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>

                    {{-- Bukti Bayar --}}
                    <div class="p-4 border-t bg-gray-50/50">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Bukti Bayar</h4>
                        @if ($proof)
                            <div class="flex items-center gap-3 flex-wrap">
                                <a href="{{ asset('storage/' . $proof->proof_path) }}" target="_blank" class="text-indigo-600 underline text-xs">Lihat Bukti</a>
                                @if ($proof->status === 'pending')
                                    <form method="POST" action="{{ route('admin.payments.ortu.confirm-proof', $proof->id) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="action" value="approve" />
                                        <button type="submit" class="text-xs text-emerald-600 font-medium hover:underline">Setujui</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.payments.ortu.confirm-proof', $proof->id) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="action" value="reject" />
                                        <button type="submit" class="text-xs text-rose-600 font-medium hover:underline">Tolak</button>
                                    </form>
                                @elseif ($proof->status === 'approved')
                                    <span class="text-xs text-emerald-600 font-medium">Disetujui</span>
                                @elseif ($proof->status === 'rejected')
                                    <span class="text-xs text-rose-600 font-medium">Ditolak</span>
                                @endif
                            </div>
                        @else
                            <span class="text-gray-400 text-xs">Belum ada</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Tidak ada data yang cocok dengan pencarian.
                </div>
            @endforelse

            @if ($summaries->hasPages())
                <div class="p-4">{{ $summaries->withQueryString()->links() }}</div>
            @endif

            @if ($summaries->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Tidak ada data pembayaran untuk periode ini.
                </div>
            @endif
        </div>
    </div>

    <script>
    (function () {
        var key = 'scroll_' + location.pathname + '?{{ http_build_query(request()->query()) }}';
        window.addEventListener('load', function () {
            var pos = sessionStorage.getItem(key);
            if (pos !== null) {
                window.scrollTo(0, parseInt(pos, 10));
                sessionStorage.removeItem(key);
            }
        });
        document.querySelectorAll('form[method=POST], a[href*="delete"], a[href*="destroy"]').forEach(function (el) {
            el.addEventListener('click', function () {
                sessionStorage.setItem(key, window.scrollY);
            });
        });
        var forms = document.querySelectorAll('form[method=GET]');
        forms.forEach(function (form) {
            form.addEventListener('submit', function () {
                sessionStorage.setItem(key, 0);
            });
        });
    })();
    </script>
</x-app-layout>
