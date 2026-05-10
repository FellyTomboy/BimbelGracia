<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Isi Presensi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('guru.presensi.store') }}" class="p-6 space-y-4" enctype="multipart/form-data">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Enrollment</label>
                        <select name="enrollment_id" id="enrollment-select" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih enrollment</option>
                            @foreach ($enrollments as $enrollment)
                                <option value="{{ $enrollment->id }}" @selected(old('enrollment_id', $enrollments->first()?->id) == $enrollment->id)>
                                    #{{ $enrollment->id }} - {{ $enrollment->program?->name ?? '-' }} - {{ $enrollment->students->pluck('name')->implode(', ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('enrollment_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Les</label>
                        <input type="date" name="lesson_date" value="{{ old('lesson_date', date('Y-m-d')) }}" class="mt-1 w-full border-gray-300 rounded-md" required max="{{ date('Y-m-d') }}" />
                        @error('lesson_date')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Presensi maksimal 7 hari setelah les. Jika lebih, status akan menunggu validasi admin.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Foto Bukti (opsional)</label>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="mt-1 w-full border-gray-300 rounded-md" />
                        @error('image')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Upload screenshot WA atau foto bersama murid. Maks 5MB.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kehadiran Murid</label>
                        @error('student_totals')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        <div class="mt-2 space-y-3">
                            @foreach ($enrollments as $enrollment)
                                <div class="space-y-2 border border-gray-200 rounded-md p-3" data-enrollment-section="{{ $enrollment->id }}">
                                    <p class="text-xs text-gray-500">#{{ $enrollment->id }} - {{ $enrollment->program?->name ?? '-' }}</p>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        @foreach ($enrollment->students as $student)
                                            <label class="flex items-center justify-between gap-3 text-sm">
                                                <span>{{ $student->name }}</span>
                                                <select name="student_totals[{{ $student->id }}]" class="w-24 border-gray-300 rounded-md" required>
                                                    <option value="0" @selected(old('student_totals.'.$student->id, '0') == '0')>Tidak Hadir</option>
                                                    <option value="1" @selected(old('student_totals.'.$student->id) == '1')>Hadir</option>
                                                </select>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Pilih Hadir/Tidak Hadir untuk setiap murid.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea name="notes" class="mt-1 w-full border-gray-300 rounded-md">{{ old('notes') }}</textarea>
                        @error('notes')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('guru.presensi.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const enrollmentSelect = document.getElementById('enrollment-select');
        const sections = document.querySelectorAll('[data-enrollment-section]');

        const syncSections = () => {
            const activeId = enrollmentSelect.value;
            sections.forEach((section) => {
                const isActive = section.dataset.enrollmentSection === activeId;
                section.classList.toggle('hidden', !isActive);
                section.querySelectorAll('input, select').forEach((input) => {
                    input.disabled = !isActive;
                });
            });
        };

        enrollmentSelect.addEventListener('change', syncSections);
        syncSections();
    </script>
</x-app-layout>
