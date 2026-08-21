<x-app-layout>
    <x-slot name="title">Diskon/Promo</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Diskon/Promo</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <form method="POST" action="{{ route('admin.discounts.store') }}" class="space-y-4">
                    @csrf
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
                            <select name="discount_type" id="discount-type-select" class="mt-1 w-full border-gray-300 rounded-md" required onchange="updateDiscountValueLabel()">
                                <option value="percent">Diskon %</option>
                                <option value="amount">Nominal potongan (Rp)</option>
                                <option value="final">Harga final (Rp)</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" id="discount-value-label">Nilai Diskon</label>
                            <input type="number" name="discount_value" id="discount-value-input" min="0" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Contoh: 10 atau 50000" required />
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
                                            <input type="checkbox" name="enrollment_ids[]" value="{{ $enrollment->id }}" class="rounded border-gray-300" />
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
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Terapkan Diskon</button>
                    </div>
                </form>
            </div>
        </div>
    <script>
        function updateDiscountValueLabel() {
            const type = document.getElementById('discount-type-select').value;
            const label = document.getElementById('discount-value-label');
            const input = document.getElementById('discount-value-input');
            if (type === 'percent') {
                label.textContent = 'Nilai Diskon (%)';
                input.placeholder = 'Contoh: 10';
                input.max = 100;
            } else if (type === 'final') {
                label.textContent = 'Harga Final (Rp)';
                input.placeholder = 'Contoh: 150000';
                input.removeAttribute('max');
            } else {
                label.textContent = 'Potongan Nominal (Rp)';
                input.placeholder = 'Contoh: 50000';
                input.removeAttribute('max');
            }
        }
    </script>
</x-app-layout>
