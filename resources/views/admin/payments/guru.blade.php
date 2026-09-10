<x-app-layout>
    <x-slot name="title">Pembayaran Guru</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pembayaran Guru</h2>
            <a href="{{ route('admin.payments.guru-summary') }}"
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
                <form method="GET" action="{{ route('admin.payments.guru') }}" class="p-6 grid md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bulan</label>
                        <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="mt-1 w-full border-gray-300 rounded-md" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tahun</label>
                        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="mt-1 w-full border-gray-300 rounded-md" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cari Guru</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama guru..." class="mt-1 w-full border-gray-300 rounded-md" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Terapkan</button>
                    </div>
                </form>
            </div>

            @forelse ($summaries as $summary)
                @php
                    $idsList = $summary['attendance_ids'] ?? [];
                    $idsString = implode(',', $idsList);
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    {{-- Header: nama guru, total, dan aksi per bulan --}}
                    <div class="p-4 border-b bg-gray-50 flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <span class="font-semibold text-lg">{{ $summary['teacher']?->displayName ?? 'Unknown' }}</span>
                            @if($summary['teacher']?->bank_name || $summary['teacher']?->bank_account)
                                <div class="text-xs text-gray-500 mt-0.5">
                                    {{ $summary['teacher']->bank_name ?? '' }}
                                    a/n {{ $summary['teacher']->bank_owner ?? $summary['teacher']->displayName }}
                                    No. {{ $summary['teacher']->bank_account ?? '-' }}
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right text-sm">
                                <div class="font-semibold">Total: Rp {{ number_format($summary['total']) }}</div>
                                @if($summary['penalty'] > 0)
                                    <div class="text-xs text-rose-600">Denda: -Rp {{ number_format($summary['penalty']) }}</div>
                                    <div class="font-semibold text-emerald-600">Final: Rp {{ number_format($summary['net_total']) }}</div>
                                @endif
                            </div>
                            {{-- Aksi per bulan --}}
                            @if ($idsString)
                                <form method="POST" action="{{ route('admin.payments.guru.monthly-payment') }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="hidden" name="attendance_ids" value="{{ $idsString }}" />
                                    <select name="teacher_payment_status" class="border-gray-300 rounded-md text-xs">
                                        <option value="unpaid" @selected($summary['payment_status'] === 'unpaid')>Belum</option>
                                        <option value="paid" @selected($summary['payment_status'] === 'paid')>Sudah</option>
                                        <option value="held" @selected($summary['payment_status'] === 'held')>Ditahan</option>
                                    </select>
                                    <button type="submit" class="text-indigo-600 text-xs font-medium hover:underline">Simpan</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Rincian --}}
                    <div class="p-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-1">Murid / Program</th>
                                    <th class="py-1">Biaya</th>
                                    <th class="py-1">Jumlah</th>
                                    <th class="py-1">Subtotal</th>
                                    <th class="py-1">Denda</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach ($summary['lines'] as $line)
                                    <tr>
                                        <td class="py-1">
                                            {{ $line['label'] }}
                                            @if (($line['type'] ?? '') === 'kelas_tanpa_murid')
                                                <span class="ml-1.5 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Kelas</span>
                                            @elseif (($line['type'] ?? '') === 'kelas')
                                                <span class="ml-1.5 inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">Kelas</span>
                                            @else
                                                <span class="ml-1.5 inline-flex items-center rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">Privat</span>
                                            @endif
                                        </td>
                                        <td class="py-1">Rp {{ number_format($line['rate']) }}</td>
                                        <td class="py-1">{{ $line['count'] }}x</td>
                                        <td class="py-1">Rp {{ number_format($line['total']) }}</td>
                                        <td class="py-1">{{ $line['penalty'] > 0 ? '-Rp '.number_format($line['penalty']) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-semibold bg-gray-50">
                                    <td colspan="3" class="py-1 text-right">Total:</td>
                                    <td class="py-1">Rp {{ number_format($summary['total']) }}</td>
                                    <td class="py-1">{{ $summary['penalty'] > 0 ? '-Rp '.number_format($summary['penalty']) : '-' }}</td>
                                </tr>
                            </tfoot>
                        </table>
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
