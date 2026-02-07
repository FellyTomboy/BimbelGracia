<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Enrollment</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.enrollments.store') }}" class="p-6 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Program</label>
                        <select name="program_id" id="program-select" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih program</option>
                            @foreach ($programs as $program)
                                <option
                                    value="{{ $program->id }}"
                                    data-default-parent="{{ $program->default_parent_rate }}"
                                    data-default-teacher="{{ $program->default_teacher_rate }}"
                                    @selected(old('program_id') == $program->id)
                                >
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('program_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Guru</label>
                        <select name="teacher_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih guru</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                        @error('teacher_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Harga Ortu / Pertemuan</label>
                            <input type="number" name="parent_rate" id="parent-rate" value="{{ old('parent_rate') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('parent_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gaji Guru / Pertemuan</label>
                            <input type="number" name="teacher_rate" id="teacher-rate" value="{{ old('teacher_rate') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('teacher_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="active" @selected(old('status') === 'active')>active</option>
                            <option value="hibernasi" @selected(old('status') === 'hibernasi')>hibernasi</option>
                        </select>
                        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Daftar Murid</label>
                        <div class="mt-2 grid md:grid-cols-2 gap-2 max-h-64 overflow-y-auto border rounded-md p-3">
                            @foreach ($students as $student)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                        @checked(is_array(old('student_ids')) && in_array($student->id, old('student_ids', []))) />
                                    <span>{{ $student->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('student_ids')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.enrollments.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const programSelect = document.getElementById('program-select');
        const parentRateInput = document.getElementById('parent-rate');
        const teacherRateInput = document.getElementById('teacher-rate');

        const markTouched = (event) => {
            event.target.dataset.touched = 'true';
        };

        const applyDefaults = () => {
            const selected = programSelect.options[programSelect.selectedIndex];
            if (!selected || !selected.value) {
                return;
            }

            const defaultParent = selected.dataset.defaultParent ?? '';
            const defaultTeacher = selected.dataset.defaultTeacher ?? '';

            if (!parentRateInput.dataset.touched && parentRateInput.value === '') {
                parentRateInput.value = defaultParent;
            }

            if (!teacherRateInput.dataset.touched && teacherRateInput.value === '') {
                teacherRateInput.value = defaultTeacher;
            }
        };

        parentRateInput.addEventListener('input', markTouched);
        teacherRateInput.addEventListener('input', markTouched);
        programSelect.addEventListener('change', applyDefaults);
        applyDefaults();
    </script>
</x-app-layout>
