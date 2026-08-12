<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pembayaran Ortu</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.payments.ortu') }}" class="p-6 grid md:grid-cols-3 gap-4">
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

            @foreach ($summaries as $summary)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                        <div>
                            <span class="font-semibold text-lg">{{ $summary['parent_name'] }}</span>
                            <div class="text-xs text-gray-500 mt-1">
                                @foreach ($summary['students'] as $s)
                                    <span class="inline-block mr-2">{{ $s['student']?->display_name }}</span>
                                @endforeach
                            </div>
                        </div>
                        <span class="font-semibold">Total: Rp {{ number_format($summary['total']) }}</span>
                    </div>
                    <div class="p-4 text-gray-900 overflow-x-auto">
                        @foreach ($summary['students'] as $studentEntry)
                            <div class="mb-4 last:mb-0">
                                <h4 class="text-sm font-semibold text-indigo-700 mb-2">{{ $studentEntry['student']?->display_name }}</h4>
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500">
                                            <th class="py-1">Program / Guru</th>
                                            <th class="py-1">Tarif</th>
                                            <th class="py-1">Jumlah</th>
                                            <th class="py-1">Subtotal</th>
                                            <th class="py-1">Status</th>
                                            <th class="py-1">Bukti</th>
                                            <th class="py-1">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($studentEntry['lines'] as $line)
                                            <tr>
                                                <td class="py-1">{{ $line['label'] }}</td>
                                                <td class="py-1">Rp {{ number_format($line['rate']) }}</td>
                                                <td class="py-1">{{ $line['count'] }}x</td>
                                                <td class="py-1">Rp {{ number_format($line['total']) }}</td>
                                                <td class="py-1">{{ $line['payment_status'] }}</td>
                                                <td class="py-1">
                                                    @if ($line['proof_url'])
                                                        <a href="{{ asset('storage/' . $line['proof_url']) }}" target="_blank" class="text-indigo-600 underline text-xs">Lihat Bukti</a>
                                                        @if ($line['proof_status'] === 'pending')
                                                            <div class="flex gap-1 mt-1">
                                                                <form method="POST" action="{{ route('admin.payments.confirm-proof', $line['attendance_id']) }}" class="inline">
                                                                    @csrf
                                                                    <input type="hidden" name="action" value="approve" />
                                                                    <button type="submit" class="text-xs text-emerald-600 font-medium">Setujui</button>
                                                                </form>
                                                                <form method="POST" action="{{ route('admin.payments.confirm-proof', $line['attendance_id']) }}" class="inline">
                                                                    @csrf
                                                                    <input type="hidden" name="action" value="reject" />
                                                                    <button type="submit" class="text-xs text-rose-600 font-medium">Tolak</button>
                                                                </form>
                                                            </div>
                                                        @elseif ($line['proof_status'] === 'approved')
                                                            <span class="text-xs text-emerald-600">Disetujui</span>
                                                        @elseif ($line['proof_status'] === 'rejected')
                                                            <span class="text-xs text-rose-600">Ditolak</span>
                                                        @endif
                                                    @else
                                                        <span class="text-gray-400 text-xs">Belum ada</span>
                                                    @endif
                                                </td>
                                                <td class="py-1">
                                                    <form method="POST" action="{{ route('admin.payments.ortu.payment', $line['attendance_id']) }}" class="flex items-center gap-1">
                                                        @csrf
                                                        <select name="parent_payment_status" class="border-gray-300 rounded-md text-xs">
                                                            <option value="unpaid" @selected($line['payment_status'] === 'unpaid')>Belum bayar</option>
                                                            <option value="paid" @selected($line['payment_status'] === 'paid')>Sudah bayar</option>
                                                        </select>
                                                        <button type="submit" class="text-indigo-600 text-xs">Simpan</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if ($summaries->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Tidak ada data pembayaran untuk periode ini.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>