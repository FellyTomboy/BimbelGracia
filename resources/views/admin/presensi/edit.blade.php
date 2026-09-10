<x-app-layout>
    <x-slot name="title">Edit Presensi</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Presensi</h2>
                <p class="text-sm text-gray-500 mt-0.5">Ubah enrollment, tanggal, murid hadir, atau keterangan</p>
            </div>
            <a href="{{ route('admin.presensi.index') }}" class="text-sm text-gray-500">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <form method="POST" action="{{ route('admin.presensi.update', $attendance) }}" class="p-6 space-y-5" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Enrollment</label>
                        <select name="enrollment_id" id="enrollment-select" class="mt-1 w-full rounded-xl border-gray-200 text-sm" required>
                            <option value="">Pilih enrollment</option>
                            @foreach ($enrollments as $enrollment)
                                <option
                                    value="{{ $enrollment->id }}"
                                    data-students="{{ $enrollment->students->map(fn($s) => ['id' => $s->id, 'name' => $s->display_name]) }}"
                                    data-type="{{ $enrollment->program?->type }}"
                                    {{ old('enrollment_id', $attendance->enrollment_id) == $enrollment->id ? 'selected' : '' }}>
                                    #{{ $enrollment->id }} - {{ $enrollment->program?->name ?? '-' }} - {{ $enrollment->teacher?->displayName ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        @error('enrollment_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Les</label>
                        <input type="date" name="lesson_date"
                            value="{{ old('lesson_date', $attendance->lesson_date?->format('Y-m-d')) }}"
                            class="mt-1 w-full rounded-xl border-gray-200 text-sm" required max="{{ date('Y-m-d') }}" />
                        @error('lesson_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div id="student-presence-section" class="border rounded-xl p-4 bg-gray-50">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Murid yang Hadir</h3>
                        <p class="text-xs text-gray-500 mb-3" id="student-presence-desc">Kosongkan semua checkbox jika tidak ingin mengubah daftar murid.</p>
                        <div id="student-checkboxes" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        </div>
                        @error('student_ids')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti</label>
                        @if ($attendance->image)
                            <div class="mt-2 mb-3">
                                <img src="{{ asset('storage/' . $attendance->image) }}" class="max-w-xs rounded-xl border" alt="Bukti presensi" />
                            </div>
                        @endif
                        <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif,webp"
                            class="mt-1 w-full rounded-xl border-gray-200 text-sm
                            file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm
                            file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100" />
                        @error('image')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin mengubah foto.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                        <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border-gray-200 text-sm">{{ old('notes', $attendance->notes) }}</textarea>
                        @error('notes')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('admin.presensi.index') }}" class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const enrollmentSelect = document.getElementById('enrollment-select');
            const studentSection = document.getElementById('student-presence-section');
            const studentCheckboxes = document.getElementById('student-checkboxes');
            const studentPresenceDesc = document.getElementById('student-presence-desc');

            // Pre-selected student IDs from the attendance record
            const PRESELECTED_STUDENTS = {{ Js::from($attendance->students->pluck('id')->toArray()) }};

            const renderStudentSection = function() {
                const selected = enrollmentSelect.options[enrollmentSelect.selectedIndex];
                if (!selected || !selected.value) {
                    studentCheckboxes.innerHTML = '<p class="text-sm text-gray-400 col-span-2">Pilih enrollment terlebih dahulu.</p>';
                    return;
                }
                const programType = selected.dataset.type || '';
                let students = [];
                try {
                    students = JSON.parse(selected.dataset.students || '[]');
                } catch(e) { students = []; }

                if (programType === 'kelas') {
                    studentPresenceDesc.textContent = 'Presensi kelas tidak perlu memilih murid. Admin akan mengisi daftar hadir nanti.';
                    studentCheckboxes.innerHTML = '<p class="text-sm text-gray-500 italic col-span-2">Tidak perlu centang murid untuk program kelas.</p>';
                } else if (students.length === 0) {
                    studentCheckboxes.innerHTML = '<p class="text-sm text-gray-400 col-span-2">Tidak ada murid terdaftar.</p>';
                } else {
                    studentCheckboxes.innerHTML = students.map(function(s) {
                        const checked = PRESELECTED_STUDENTS.includes(s.id) ? 'checked' : '';
                        return '<label class="flex items-center gap-2 text-sm p-2 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors cursor-pointer">' +
                            '<input type="checkbox" name="student_ids[]" value="' + s.id + '" ' + checked + ' class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />' +
                            '<span>' + s.name + '</span>' +
                        '</label>';
                    }).join('');
                }
            };

            enrollmentSelect.addEventListener('change', renderStudentSection);
            renderStudentSection();
        });
    </script>
</x-app-layout>
