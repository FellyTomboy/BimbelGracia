<x-app-layout>
    <x-slot name="title">Ringkasan Tagihan</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ringkasan Tagihan</h2>
            <p class="text-sm text-gray-500 mt-0.5">Riwayat tagihan dan status pembayaran</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            {{-- KPI Totals --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Sudah Dibayar</p>
                    <p class="text-2xl font-bold text-emerald-600">Rp {{ number_format($totals['paid']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs text-gray-500">Belum Dibayar</p>
                    <p class="text-2xl font-bold text-rose-600">Rp {{ number_format($totals['unpaid']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 bg-gradient-to-br from-indigo-50 to-violet-50 sm:col-span-2 md:col-span-1">
                    <p class="text-xs text-gray-500">Total Tagihan</p>
                    <p class="text-2xl font-bold text-indigo-700">Rp {{ number_format($totals['grand']) }}</p>
                </div>
            </div>

            {{-- Monthly List --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="font-semibold text-gray-900">Riwayat Tagihan Per Bulan</h3>
                </div>
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full align-middle">
                        <table class="min-w-full text-xs sm:text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 bg-gray-50/50">
                                    <th class="py-3 px-3 sm:px-4 font-medium">Periode</th>
                                    <th class="py-3 px-3 sm:px-4 font-medium">Total</th>
                                    <th class="py-3 px-3 sm:px-4 font-medium">Status</th>
                                    <th class="py-3 px-3 sm:px-4 font-medium">Invoice</th>
                                    <th class="py-3 px-3 sm:px-4 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($monthlyList as $item)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-3 sm:px-4 font-medium text-gray-900 whitespace-nowrap">{{ $item['period'] }}</td>
                                        <td class="py-3 px-3 sm:px-4 text-gray-900 font-medium whitespace-nowrap">Rp {{ number_format($item['total']) }}</td>
                                        <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                            @if ($item['status'] === 'paid')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Lunas</span>
                                            @elseif ($item['status'] === 'pending')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Menunggu</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Belum</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                            @if ($item['has_invoice'])
                                                <a href="{{ $item['invoice_url'] }}" target="_blank" class="inline-flex items-center gap-1 px-2 sm:px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    <span class="hidden sm:inline">Lihat</span>
                                                </a>
                                            @else
                                                <form method="POST" action="{{ route('murid.billing.download-invoice', ['year' => $item['year'], 'month' => $item['month']]) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 px-2 sm:px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                        <span class="hidden sm:inline">Buat</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                            @if ($item['status'] === 'paid')
                                                <span class="text-gray-400 text-xs">Lunas</span>
                                            @elseif ($item['status'] === 'pending')
                                                <span class="text-gray-400 text-xs">Menunggu</span>
                                            @else
                                                <form method="POST" action="{{ route('murid.billing.upload-proof', $item['attendance_ids'][0]) }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                                    @csrf
                                                    <input type="file" name="payment_proof" accept="image/jpg,image/jpeg,image/png" class="text-xs w-full sm:w-28 rounded-xl border-gray-200 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-600" required />
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700 transition-colors">Upload</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <x-empty-state icon="💰" title="Belum ada tagihan" description="Belum ada tagihan untuk ditampilkan." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>