<x-app-layout>
    <x-slot name="title">Diskon/Promo</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Diskon/Promo</h2>
    </x-slot>

    <div x-data="discountModal({
        previewUrl: '{{ route('admin.discounts.preview') }}',
        storeUrl: '{{ route('admin.discounts.store') }}',
        listSelector: 'table',
    })">

        {{-- Modal ──────────────────────────────────────────────── --}}
        <div x-show="modalOpen"
            x-on:discount-close.window="close()"
            x-on:discount-submit.window="submitDiscount()"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="close()">

            {{-- Backdrop ──────────────────────────────────────── --}}
            <div x-show="modalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm"
                @click="close()">
            </div>

            {{-- Panel ─────────────────────────────────────────── --}}
            <div x-show="modalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

                {{-- Header ──────────────────────────────────── --}}
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900" x-text="modalTitle">Konfirmasi</h3>
                    <button @click="close()"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body ──────────────────────────────────────── --}}
                <div class="p-5">
                    <div x-html="modalBody" class="space-y-0"></div>
                </div>
            </div>
        </div>

        {{-- Main Content ───────────────────────────────────────── --}}
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
                @if (session('status'))
                    <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div id="discount-form-container">
                        <div class="space-y-4">
                            <div class="grid md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Bulan</label>
                                    <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="mt-1 w-full border-gray-300 rounded-md" required />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tahun</label>
                                    <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="mt-1 w-full border-gray-300 rounded-md" required />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Jenis Diskon</label>
                                    <select name="discount_type" id="discount-type-select"
                                        class="mt-1 w-full border-gray-300 rounded-md"
                                        required
                                        x-on:change="updateDiscountLabel()">
                                        <option value="percent">Diskon %</option>
                                        <option value="amount">Nominal potongan (Rp)</option>
                                        <option value="final">Harga final (Rp)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700" id="discount-value-label">Nilai Diskon</label>
                                    <input type="number" name="discount_value" id="discount-value-input" min="0"
                                        class="mt-1 w-full border-gray-300 rounded-md"
                                        placeholder="Contoh: 10 atau 50000" required />
                                </div>
                                <div class="md:col-span-2 text-sm text-gray-500 flex items-end">
                                    <p>Isi nilai 0 untuk menghapus diskon pada enrollment yang dipilih.</p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500">
                                            <th class="py-2">Pilih</th>
                                            <th class="py-2">Enrollment</th>
                                            <th class="py-2">Program</th>
                                            <th class="py-2">Guru</th>
                                            <th class="py-2">Murid</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($enrollments as $enrollment)
                                            <tr>
                                                <td class="py-2">
                                                    <input type="checkbox" name="enrollment_ids[]"
                                                        value="{{ $enrollment->id }}"
                                                        class="rounded border-gray-300" />
                                                </td>
                                                <td class="py-2">#{{ $enrollment->id }}</td>
                                                <td class="py-2">
                                                    <x-hibernated-label :model="$enrollment->program" :label="$enrollment->program?->name ?? '-'" type="program" />
                                                </td>
                                                <td class="py-2">
                                                    <x-hibernated-label :model="$enrollment->teacher" :label="$enrollment->teacher?->displayName ?? '-'" type="guru" />
                                                </td>
                                                <td class="py-2">
                                                    {{ $enrollment->students->map->display_name->implode(', ') ?: '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex justify-end">
                                <button type="button"
                                    @click="openPreviewModal()"
                                    class="px-4 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                                    Terapkan Diskon
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
