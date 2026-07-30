<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Isi Presensi</h2>
            <p class="text-sm text-gray-500 mt-0.5">Catat kehadiran murid untuk setiap sesi les</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <form method="POST" action="{{ route('guru.presensi.store') }}" class="p-6 space-y-6" enctype="multipart/form-data">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Enrollment</label>
                        <select name="enrollment_id" id="enrollment-select" class="mt-1 w-full rounded-xl border-gray-200 text-sm" required>
                            <option value="">Pilih enrollment</option>
                            @foreach ($enrollments as $enrollment)
                                <option value="{{ $enrollment->id }}" @selected(old('enrollment_id', $enrollments->first()?->id) == $enrollment->id)>
                                    #{{ $enrollment->id }} - {{ $enrollment->program?->name ?? '-' }} - {{ $enrollment->students->pluck('name')->implode(', ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('enrollment_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Les</label>
                        <input type="date" name="lesson_date" value="{{ old('lesson_date', date('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-gray-200 text-sm" required max="{{ date('Y-m-d') }}" />
                        @error('lesson_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Presensi maksimal 7 hari setelah les. Jika lebih, status akan menunggu validasi admin.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti (opsional)</label>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="mt-1 w-full rounded-xl border-gray-200 text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100" />
                        @error('image')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Upload screenshot WA atau foto bersama murid. Maks 5MB.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                        <textarea name="notes" class="mt-1 w-full rounded-xl border-gray-200 text-sm" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('guru.presensi.index') }}" class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">Kirim Presensi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>