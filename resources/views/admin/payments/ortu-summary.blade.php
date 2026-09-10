<x-app-layout>
    <x-slot name="title">Ringkasan Pembayaran Ortu</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Tagihan'], ['label' => 'Ringkasan Bayar Ortu']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ringkasan Pembayaran Ortu</h2>
                <p class="text-sm text-gray-500 mt-0.5">Matrix tagihan per orang tua per bulan. Klik sel untuk ubah status.</p>
            </div>
            <a href="{{ route('admin.payments.ortu') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 text-gray-700
                      hover:bg-gray-100 rounded-lg text-xs font-medium border border-gray-200 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Versi Detail
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-[100vw] mx-auto px-4 sm:px-6 lg:px-8">
            @if (empty($months))
                <div class="bg-white shadow-sm rounded-xl p-12 text-center text-gray-400">
                    Tidak ada data absensi.</div>
            @else
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-max w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="sticky left-0 z-20 bg-gray-50 px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap border-r border-gray-200 min-w-[180px]">
                                        Nama Murid
                                    </th>
                                    @foreach ($months as $p)
                                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap min-w-[120px]">
                                            {{ $monthName($p['month']) }} {{ $p['year'] }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($parents as $parent)
                                    @php
                                        $students = $parent->students->filter(fn ($s) => $s->status === 'active');
                                        $studentNames = $students->map(fn ($s) => $s->nickname ?: 'Tanpa nama')->join(' & ');
                                    @endphp
                                    <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                                        <td class="sticky left-0 z-10 bg-white px-4 py-3 font-medium text-gray-800 border-r border-gray-200 whitespace-nowrap">
                                            {{ $studentNames ?: '—' }}
                                            <div class="text-xs text-gray-400 font-normal">{{ $parent->name }}</div>
                                        </td>
                                        @foreach ($months as $p)
                                            @php
                                                $key = $parent->id . '-' . $p['month'] . '-' . $p['year'];
                                                $cell = $cellMap[$key] ?? null;
                                                $hasProof = isset($proofMap[$key]);
                                                $isPaid = $cell && $cell['payment_status'] === 'paid';
                                                $isGreen = $isPaid || $hasProof;
                                            @endphp
                                            <td class="px-2 py-2 text-center">
                                                @if ($cell && $cell['grand_total'] > 0)
                                                    <div
                                                        x-data="{
                                                            open: false,
                                                            parentId: {{ $parent->id }},
                                                            month: {{ $p['month'] }},
                                                            year: {{ $p['year'] }},
                                                            currentStatus: '{{ $isGreen ? 'paid' : 'unpaid' }}',
                                                            proofApproved: {{ $hasProof ? 'true' : 'false' }},
                                                            colorClass: '{{ $isGreen ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}',
                                                            async updateStatus(newStatus) {
                                                                const csrf = document.querySelector('meta[name=csrf-token]')?.content;
                                                                try {
                                                                    const res = await window.axios.patch(
                                                                        '{{ route('admin.payments.ortu.status') }}',
                                                                        {
                                                                            parent_id: this.parentId,
                                                                            month: this.month,
                                                                            year: this.year,
                                                                            status: newStatus
                                                                        },
                                                                        { headers: { 'X-CSRF-TOKEN': csrf } }
                                                                    );
                                                                    this.currentStatus = newStatus;
                                                                    this.proofApproved = newStatus === 'paid';
                                                                    this.colorClass = newStatus === 'paid'
                                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                                        : 'bg-rose-50 text-rose-700 border-rose-200';
                                                                    this.open = false;
                                                                } catch (e) {
                                                                    alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message));
                                                                }
                                                            }
                                                        }"
                                                        class="relative"
                                                    >
                                                        <button
                                                            @click="open = !open"
                                                            :class="colorClass"
                                                            class="inline-flex items-center gap-1 px-2 py-1 rounded border text-xs font-medium cursor-pointer transition-colors hover:opacity-80"
                                                        >
                                                            <span>Rp {{ number_format($cell['grand_total']) }}</span>
                                                            @if ($hasProof)
                                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                            @endif
                                                        </button>

                                                        <div x-show="open" x-cloak
                                                            x-transition:enter="transition ease-out duration-100"
                                                            x-transition:enter-start="transform opacity-0 scale-95"
                                                            x-transition:enter-end="transform opacity-100 scale-100"
                                                            x-transition:leave="transition ease-in duration-75"
                                                            x-transition:leave-start="transform opacity-100 scale-100"
                                                            x-transition:leave-end="transform opacity-0 scale-95"
                                                            @click.away="open = false"
                                                            class="absolute z-50 mt-1 left-1/2 -translate-x-1/2 bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-[120px] text-left">
                                                            <button
                                                                @click="updateStatus('paid')"
                                                                class="w-full text-left px-3 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50 flex items-center gap-1.5">
                                                                <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                                                                Lunas
                                                            </button>
                                                            <button
                                                                @click="updateStatus('unpaid')"
                                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-700 hover:bg-rose-50 flex items-center gap-1.5">
                                                                <span class="w-2 h-2 rounded-full bg-rose-400 shrink-0"></span>
                                                                Belum Bayar
                                                            </button>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-gray-300 text-xs">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($months) + 1 }}" class="px-4 py-8 text-center text-gray-400">
                                            Tidak ada data orang tua aktif dengan rekaman absensi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-3 h-3 rounded bg-emerald-100 border border-emerald-200"></span>
                        Lunas
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-3 h-3 rounded bg-rose-100 border border-rose-200"></span>
                        Belum Bayar
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        = Ada bukti bayar disetujui
                    </span>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
