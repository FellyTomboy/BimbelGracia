<x-app-layout>
    <x-slot name="title">WA Guru</x-slot>
    <x-slot name="header">
        <div>
            <x-breadcrumb :items="[['label' => 'WA Guru']]" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">WA Guru</h2>
            <p class="text-sm text-gray-500 mt-0.5">Analisis dan kirim pesan gaji ke guru</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                {{-- Filter --}}
                <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <p class="text-sm text-gray-600">Pilih periode untuk melihat data gaji guru.</p>
                    <form method="GET" class="flex items-center gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Bulan</label>
                            <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="w-20 rounded-xl border-gray-200 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Tahun</label>
                            <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="w-24 rounded-xl border-gray-200 text-sm" required />
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors mt-4 sm:mt-0">Terapkan</button>
                    </form>
                </div>

                <div class="flex flex-col lg:flex-row">
                    {{-- SIDEBAR: Daftar Guru --}}
                    <div class="w-full lg:w-1/3 border-b lg:border-b-0 lg:border-r border-gray-100">
                        <div class="p-4 font-bold text-gray-700 flex items-center justify-between lg:block">
                            <span>Daftar Guru</span>
                            <span class="text-xs text-gray-400 lg:hidden">Geser untuk lihat detail</span>
                        </div>
                        <div class="overflow-y-auto max-h-48 lg:max-h-[70vh]">
                            @forelse ($summaries as $index => $summary)
                                <a
                                    href="{{ route('admin.analysis.guru', ['month' => $month, 'year' => $year, 'selected' => $index]) }}"
                                    class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors
                                        {{ (request('selected', '0') == $index) ? 'bg-indigo-50/50 font-semibold' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $summary['teacher']?->name ?? 'Guru' }}
                                    </div>
                                    <div class="text-xs text-gray-500">WA: {{ $summary['teacher']?->whatsapp_number ?? '-' }}</div>
                                    <div class="mt-1 text-[11px] text-gray-600">
                                        Rp {{ number_format($summary['total']) }}
                                    </div>
                                </a>
                            @empty
                                <p class="p-4 text-sm text-gray-500">Tidak ada data untuk periode ini.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- KONTEN UTAMA: Detail + WA Template --}}
                    <div class="w-full lg:w-2/3 p-4 sm:p-6">
                        @php
                            $selectedIndex = (int) request('selected', 0);
                            $selected = $summaries[$selectedIndex] ?? null;
                        @endphp

                        @if($selected)
                            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">
                                        {{ $selected['teacher']?->name ?? 'Guru' }}
                                    </h3>
                                    <p class="text-sm text-gray-500">WA: {{ $selected['teacher']?->whatsapp_number ?? '-' }}</p>
                                    <p class="text-xs text-gray-600">
                                        Total Gaji: <span class="font-semibold">Rp {{ number_format($selected['total']) }}</span>
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($selected['teacher']?->whatsapp_number)
                                        <a href="https://wa.me/{{ App\Helpers\WhatsappHelper::toWaFormat($selected['teacher']->whatsapp_number) }}?text={{ urlencode($selected['message']) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                            Buka WA
                                        </a>
                                    @endif
                                    <button onclick="copyTemplate()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                        Salin Pesan
                                    </button>
                                    @if ($selected['teacher'])
                                    <form method="POST" action="{{ route('admin.analysis.generate-salary', ['teacher' => $selected['teacher']->id, 'month' => $month, 'year' => $year]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            PDF Slip Gaji
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>

                            {{-- Tabel Detail Gaji --}}
                            <div class="overflow-x-auto border rounded-lg mb-6 -mx-4 sm:mx-0">
                                <div class="inline-block min-w-full align-middle">
                                    <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Murid / Program</th>
                                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarif</th>
                                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jml</th>
                                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Denda</th>
                                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach ($selected['lines'] as $line)
                                                <tr>
                                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                                                        {{ $line['label'] }}
                                                        @if ($line['type'] === 'kelas')
                                                            <span class="ml-2 inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">Kelas</span>
                                                        @else
                                                            <span class="ml-2 inline-flex items-center rounded-full bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700">Privat</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600">Rp {{ number_format($line['rate']) }}</td>
                                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600">{{ $line['count'] }}x</td>
                                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600">Rp {{ number_format($line['total']) }}</td>
                                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm">
                                                        @if (($line['penalty'] ?? 0) > 0)
                                                            <span class="text-rose-600">-Rp {{ number_format($line['penalty']) }}</span>
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm font-medium">Rp {{ number_format($line['total'] - ($line['penalty'] ?? 0)) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Template WA --}}
                            <div>
                                <label class="block text-xs uppercase tracking-wide text-gray-500 font-semibold mb-2">Template WhatsApp</label>
                                <textarea id="wa-template" class="w-full rounded-xl border-gray-200 text-sm font-mono bg-gray-50" rows="12">{{ $selected['message'] }}</textarea>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-48 lg:h-full">
                                <p class="text-gray-500 text-center px-4">Pilih guru dari daftar di samping untuk melihat detail.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyTemplate() {
            const textarea = document.getElementById('wa-template');
            if (!textarea) return;
            textarea.select();
            navigator.clipboard.writeText(textarea.value).then(() => {
                const btn = document.querySelector('[onclick="copyTemplate()"]');
                const orig = btn.innerHTML;
                btn.innerHTML = '✅ Tersalin!';
                setTimeout(() => btn.innerHTML = orig, 2000);
            });
        }
    </script>
</x-app-layout>