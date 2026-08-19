<x-app-layout>
    <x-slot name="title">Isi Presensi</x-slot>
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
                                <option value="{{ $enrollment->id }}" data-students="{{ $enrollment->students->map(fn($s) => ['id' => $s->id, 'name' => $s->display_name]) }}" data-type="{{ $enrollment->program?->type }}" @selected(old('enrollment_id', $enrollments->first()?->id) == $enrollment->id)>
                                    #{{ $enrollment->id }} - {{ $enrollment->program?->name ?? '-' }} - {{ $enrollment->students->map->display_name->implode(', ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('enrollment_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Les</label>
                        <input type="date" name="lesson_date" value="{{ old('lesson_date', date('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-gray-200 text-sm" required max="{{ date('Y-m-d') }}" />
                        @error('lesson_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        @if (app(\App\Services\AttendanceFineService::class)->isLatePenaltyEnabled())
                            <p class="mt-1 text-xs text-gray-500">Presensi maksimal 3 hari setelah les. Jika lebih, status akan menunggu validasi admin.</p>
                        @endif
                    </div>

                    {{-- Per-student presence --}}
                    <div id="student-presence-section" class="border rounded-xl p-4 bg-gray-50 @if(!old('enrollment_id', $enrollments->first()?->id)) hidden @endif">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Murid yang Hadir</h3>
                        <p class="text-xs text-gray-500 mb-3" id="student-presence-desc">Centang murid yang benar-benar hadir pada sesi ini. Harga ortu & gaji guru akan dihitung otomatis sesuai jumlah yang hadir.</p>
                        <div id="student-checkboxes" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @php $firstEnrollment = $enrollments->first(); @endphp
                            @if ($firstEnrollment && $firstEnrollment->program?->type !== 'kelas')
                                @foreach ($firstEnrollment->students as $student)
                                    <label class="flex items-center gap-2 text-sm p-2 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors cursor-pointer">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        <span>{{ $student->display_name }}</span>
                                    </label>
                                @endforeach
                            @elseif($firstEnrollment && $firstEnrollment->program?->type === 'kelas')
                                <p class="text-sm text-gray-500 italic">Presensi kelas tidak perlu memilih murid. Admin akan mengisi daftar hadir nanti.</p>
                            @endif
                        </div>
                        @error('student_ids')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const enrollmentSelect = document.getElementById('enrollment-select');
            const studentSection = document.getElementById('student-presence-section');
            const studentCheckboxes = document.getElementById('student-checkboxes');
            const studentPresenceDesc = document.getElementById('student-presence-desc');

            const renderStudentSection = function() {
                const selected = enrollmentSelect.options[enrollmentSelect.selectedIndex];
                if (!selected || !selected.value) {
                    studentSection.classList.add('hidden');
                    return;
                }

                const programType = selected.dataset.type || '';
                let students;
                try {
                    students = JSON.parse(selected.dataset.students || '[]');
                } catch(e) {
                    students = [];
                }

                studentSection.classList.remove('hidden');

                if (programType === 'kelas') {
                    // CLASS: show info message, no checkboxes
                    studentPresenceDesc.textContent = 'Presensi kelas tidak perlu memilih murid. Admin akan mengisi daftar hadir nanti.';
                    studentCheckboxes.innerHTML = '<p class="text-sm text-gray-500 italic">Tidak perlu centang murid untuk program kelas.</p>';
                } else {
                    // PRIVATE: show checkboxes
                    studentPresenceDesc.textContent = 'Centang murid yang benar-benar hadir pada sesi ini. Harga ortu & gaji guru akan dihitung otomatis sesuai jumlah yang hadir.';
                    if (students.length === 0) {
                        studentCheckboxes.innerHTML = '<p class="text-sm text-gray-500 italic">Tidak ada murid terdaftar.</p>';
                    } else {
                        studentCheckboxes.innerHTML = students.map(function(s) {
                            return '<label class="flex items-center gap-2 text-sm p-2 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors cursor-pointer">' +
                                '<input type="checkbox" name="student_ids[]" value="' + s.id + '" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />' +
                                '<span>' + s.name + '</span>' +
                            '</label>';
                        }).join('');
                    }
                }
            };

            enrollmentSelect.addEventListener('change', renderStudentSection);
            renderStudentSection();
        });
    </script>
</x-app-layout>