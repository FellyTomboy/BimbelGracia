<x-app-layout>
    <x-slot name="title">Tambah Presensi Kelas</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Kalender Kelas', 'url' => route('admin.class-student-sessions.index', ['month' => $month, 'year' => $year])], ['label' => 'Tambah Presensi']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Presensi Kelas</h2>
                <p class="text-sm text-gray-500 mt-0.5">Catat sesi les dan kehadiran murid</p>
            </div>
            <a href="{{ route('admin.class-student-sessions.index', ['month' => $month, 'year' => $year]) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
                ← Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <form method="POST" action="{{ route('admin.class-student-sessions.store') }}"
                      x-data="classPresensiCreate()"
                      @submit="syncHiddenInputs()"
                      class="p-6 space-y-6">
                    @csrf

                    {{-- Program & Tanggal --}}
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Program <span class="text-rose-500">*</span></label>
                            <select name="program_id" id="program-select"
                                    x-model="programId"
                                    @change="onProgramChange()"
                                    class="w-full rounded-xl border-gray-200 text-sm" required>
                                <option value="">Pilih program kelas</option>
                                @foreach ($programs as $p)
                                    <option value="{{ $p->id }}" @selected(old('program_id', request('program_id')) == $p->id)>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('program_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Sesi <span class="text-rose-500">*</span></label>
                            <input type="date" name="session_date"
                                   value="{{ old('session_date', $sessionDate) }}"
                                   max="{{ date('Y-m-d') }}"
                                   class="w-full rounded-xl border-gray-200 text-sm" required />
                            @error('session_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Guru Hadir (multi-select with search) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Guru yang Hadir</label>
                        <div class="relative">
                            <input type="text" placeholder="Ketik nama guru untuk mencari..."
                                   x-model="teacherSearch"
                                   @focusin="showTeacherDropdown = true"
                                   @focusout="setTimeout(() => { if (!document.querySelector('.teacher-dropdown-item:hover')) showTeacherDropdown = false; }, 300)"
                                   class="w-full rounded-xl border-gray-200 text-sm" />
                            <div x-show="showTeacherDropdown && teachers.length > 0"
                                 x-transition
                                 @click="showTeacherDropdown = false"
                                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                                <template x-for="teacher in filteredTeachers()" :key="teacher.id">
                                    <button type="button"
                                            @click="toggleTeacher(teacher)"
                                            class="teacher-dropdown-item w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 flex items-center justify-between transition-colors"
                                            :class="selectedTeachers.find(t => t.id === teacher.id) ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700'">
                                        <span x-text="teacher.name"></span>
                                        <svg x-show="selectedTeachers.find(t => t.id === teacher.id)" class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                                <div x-show="filteredTeachers().length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">
                                    <span x-show="teachers.length === 0">Belum ada guru di program ini.</span>
                                    <span x-show="teachers.length > 0 && teacherSearch">Guru tidak ditemukan</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-2" x-show="selectedTeachers.length > 0">
                            <template x-for="teacher in selectedTeachers" :key="teacher.id">
                                <span class="inline-flex items-center gap-1 pl-3 pr-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                    <span x-text="teacher.name"></span>
                                    <span class="text-blue-500 font-normal" x-show="teacher.rate">&mdash; Rp <span x-text="teacher.rate.toLocaleString('id-ID')"></span>/sesi</span>
                                    <input type="hidden" name="teacher_ids[]" :value="teacher.id" />
                                    <button type="button" @click="toggleTeacher(teacher)" class="ml-1 hover:text-rose-600 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </span>
                            </template>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            <span x-show="teachers.length === 0 && programId !== ''">Tidak ada guru yang terdaftar di program ini. <a :href="'/admin/programs/' + programId + '/edit'" class="text-indigo-600 hover:underline">Tambah guru di program</a>.</span>
                            <span x-show="teachers.length > 0">Klik nama guru untuk menambahkan.</span>
                            <span x-show="teachers.length === 0 && programId === ''">Pilih program kelas terlebih dahulu.</span>
                        </p>
                    </div>

                    {{-- Murid Hadir --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Murid yang Hadir <span class="text-rose-500">*</span>
                            </label>
                            <button type="button"
                                    x-show="students.length > 0"
                                    @click="selectAll()"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                Pilih Semua
                            </button>
                        </div>

                        <div class="border rounded-xl p-4 bg-gray-50 max-h-80 overflow-y-auto">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" id="student-checkboxes">
                                <template x-if="students.length === 0 && programId === ''">
                                    <div class="col-span-2 text-center text-sm text-gray-400 py-4">Pilih program kelas terlebih dahulu.</div>
                                </template>
                                <template x-if="students.length === 0 && programId !== ''">
                                    <div class="col-span-2 text-center text-sm text-gray-400 py-4">Tidak ada murid aktif di enrollment program ini.</div>
                                </template>
                                <template x-for="s in students" :key="s.student_id">
                                    <label class="flex items-center gap-2 text-sm p-2.5 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 cursor-pointer transition-colors"
                                           :class="selectedStudentIds.includes(s.student_id) ? 'border-indigo-400 bg-indigo-50' : ''">
                                        <input type="checkbox"
                                               :value="s.student_id"
                                               @change="toggleStudent(s.student_id, s.enrollment_id)"
                                               :checked="selectedStudentIds.includes(s.student_id)"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        <span x-text="s.student_name"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Hidden inputs for selected students --}}
                        <div id="student-hidden-container"></div>

                        @error('student_enrollment_map')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                        <textarea name="notes" rows="2" class="w-full rounded-xl border-gray-200 text-sm"
                                  placeholder="Opsional...">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('admin.class-student-sessions.index', ['month' => $month, 'year' => $year]) }}"
                           class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                            Simpan Presensi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function classPresensiCreate() {
            return {
                programId: '{{ $selectedProgram?->id ?? request('program_id', '') }}',
                teachers: @json($teachersList),
                teachersByProgram: {},
                allStudentsByProgram: {},
                students: [],
                selectedTeachers: [],
                teacherSearch: '',
                showTeacherDropdown: false,
                selectedStudentIds: [],
                studentEnrollmentMap: {},

                filteredTeachers() {
                    if (!this.teacherSearch) return this.teachers;
                    const q = this.teacherSearch.toLowerCase();
                    return this.teachers.filter(t => t.name.toLowerCase().includes(q));
                },

                toggleTeacher(teacher) {
                    const idx = this.selectedTeachers.findIndex(t => t.id === teacher.id);
                    if (idx >= 0) {
                        this.selectedTeachers.splice(idx, 1);
                    } else {
                        this.selectedTeachers.push(teacher);
                    }
                },

                syncHiddenInputs() {
                    const container = document.getElementById('student-hidden-container');
                    if (!container) return;
                    container.innerHTML = '';
                    this.selectedStudentIds.forEach(studentId => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'student_enrollment_map[]';
                        input.value = this.studentEnrollmentMap[studentId] ?? '';
                        container.appendChild(input);
                    });
                },

                toggleStudent(studentId, enrollmentId) {
                    const idx = this.selectedStudentIds.indexOf(studentId);
                    if (idx >= 0) {
                        this.selectedStudentIds.splice(idx, 1);
                        delete this.studentEnrollmentMap[studentId];
                    } else {
                        this.selectedStudentIds.push(studentId);
                        this.studentEnrollmentMap[studentId] = enrollmentId;
                    }
                    this.syncHiddenInputs();
                },

                selectAll() {
                    this.students.forEach(s => {
                        if (!this.selectedStudentIds.includes(s.student_id)) {
                            this.selectedStudentIds.push(s.student_id);
                            this.studentEnrollmentMap[s.student_id] = s.enrollment_id;
                        }
                    });
                    this.syncHiddenInputs();
                },

                onProgramChange() {
                    this.students = (this.allStudentsByProgram[this.programId] || []);
                    this.teachers = (this.teachersByProgram[this.programId] || this.teachersByProgram[String(this.programId)] || []);
                    this.selectedStudentIds = [];
                    this.studentEnrollmentMap = {};
                },

                init() {
                    // Build allStudentsByProgram directly from $allStudents (each student has program_id)
                    const allStudents = @json($allStudents);
                    const grouped = {};
                    allStudents.forEach(s => {
                        if (!grouped[s.program_id]) grouped[s.program_id] = [];
                        grouped[s.program_id].push(s);
                    });
                    this.allStudentsByProgram = grouped;

                    // Build teachersByProgram from $teachersByProgram
                    this.teachersByProgram = @json($teachersByProgram);

                    // If program already selected (from URL), set students + confirm teachers
                    if (this.programId) {
                        this.students = (this.allStudentsByProgram[this.programId] || []);
                        if (this.teachersByProgram[this.programId]) {
                            this.teachers = this.teachersByProgram[this.programId];
                        } else if (this.teachersByProgram[String(this.programId)]) {
                            this.teachers = this.teachersByProgram[String(this.programId)];
                        }
                    }

                    @php
                        $oldMap = old('student_enrollment_map', []);
                    @endphp
                    @if (!empty($oldMap))
                        const oldEnrollmentIds = @json(array_values($oldMap));
                        this.students.forEach(s => {
                            if (oldEnrollmentIds.includes(s.enrollment_id)) {
                                this.selectedStudentIds.push(s.student_id);
                                this.studentEnrollmentMap[s.student_id] = s.enrollment_id;
                            }
                        });
                        this.syncHiddenInputs();
                    @endif
                }
            }
        }
    </script>
</x-app-layout>
