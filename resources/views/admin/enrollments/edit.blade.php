<x-app-layout>
    <x-slot name="title">Edit Enrollment</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Enrollment</h2>
    </x-slot>

    <div class="py-12"
         x-data="EnrollmentForm({
             formUrl: '{{ route('admin.enrollments.update', $enrollment) }}',
             enrollmentId: {{ $enrollment->id }},
             initialType: '{{ $enrollment->type }}',
             initialStudentCount: {{ $enrollment->students->count() > 0 ? $enrollment->students->count() : 3 }},
             initialParentTiers: {{ json_encode($enrollment->pricing_tiers['parent_rate'] ?? []) }},
             initialTeacherTiers: {{ json_encode($enrollment->pricing_tiers['teacher_rate'] ?? []) }},
         })"
         x-init="init()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form id="enrollment-form" method="POST" class="p-6 space-y-4" @submit.prevent="submit">

                    {{-- Top-level error banner (shown when Alpine errors exist) --}}
                    <div id="form-error-alert" class="mb-4 rounded-md border border-rose-300 bg-rose-50 p-4" role="alert" style="display:none">
                        <h4 class="text-sm font-semibold text-rose-800">
                            Enrollment tidak dapat disimpan. Silakan perbaiki hal berikut:
                        </h4>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700"></ul>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipe Enrollment</label>
                            <select name="type" id="type-select" class="mt-1 w-full border-gray-300 rounded-md" required @change="onTypeChange">
                                <option value="privat" @selected(old('type', $enrollment->type) === 'privat')>Privat (Per Sesi)</option>
                                <option value="kelas" @selected(old('type', $enrollment->type) === 'kelas')>Kelas (Paket Bulanan)</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Privat: tagihan per sesi. Kelas: paket bulanan dengan guru bisa berganti per sesi.</p>
                            <p class="crud-error-type text-sm text-rose-600" style="display:none"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Program</label>
                            <select name="program_id" id="program-select" class="mt-1 w-full border-gray-300 rounded-md" required @change="onProgramChange">
                                @foreach ($programs as $program)
                                    <option
                                        value="{{ $program->id }}"
                                        data-default-parent="{{ $program->default_parent_rate }}"
                                        data-default-teacher="{{ $program->default_teacher_rate }}"
                                        data-type="{{ $program->type }}"
                                        @selected(old('program_id', $enrollment->program_id) == $program->id)
                                    >
                                        {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="crud-error-program_id text-sm text-rose-600" style="display:none"></p>
                        </div>
                    </div>

                    {{-- Teacher Field --}}
                    <div id="teacher-field">
                        <label class="block text-sm font-medium text-gray-700">Guru</label>
                        <select name="teacher_id" id="teacher-select" class="mt-1 w-full border-gray-300 rounded-md">
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('teacher_id', $enrollment->teacher_id) == $teacher->id)>{{ $teacher->displayName }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Guru utama untuk privat. Untuk kelas, guru bisa berubah per sesi saat pencatatan kehadiran.</p>
                        <p class="crud-error-teacher_id text-sm text-rose-600" style="display:none"></p>
                    </div>

                    {{-- Student Selection --}}
                    <div id="student-selection">
                        {{-- Checkbox mode (privat) --}}
                        <div id="student-checkbox-section">
                            <label class="block text-sm font-medium text-gray-700">Daftar Murid</label>
                            <input type="text" id="student-search" placeholder="Cari nama murid..." class="mt-2 mb-2 w-full border-gray-300 rounded-md text-sm"
                                   @input="onStudentSearch" />
                            <div class="mt-1 grid md:grid-cols-2 gap-2 max-h-64 overflow-y-auto border rounded-md p-3">
                                @foreach ($students as $student)
                                    <label class="flex items-center gap-2 text-sm student-label">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox"
                                            @checked(in_array($student->id, old('student_ids', $enrollment->students->pluck('id')->all())))
                                            @change="onStudentChange" />
                                        <span>{{ $student->display_name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="crud-error-student_ids text-sm text-rose-600" style="display:none"></p>
                        </div>

                        {{-- Dropdown mode (kelas) --}}
                        <div id="student-dropdown-section" class="hidden">
                            <label class="block text-sm font-medium text-gray-700">Pilih Murid</label>
                            <select name="student_ids[]" id="student-dropdown" class="mt-1 w-full border-gray-300 rounded-md">
                                <option value="">Pilih murid</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}"
                                        @selected(in_array($student->id, old('student_ids', $enrollment->students->pluck('id')->all())))>
                                        {{ $student->display_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Pilih satu murid untuk enrollment kelas.</p>
                            <p class="crud-error-student_ids text-sm text-rose-600" style="display:none"></p>
                        </div>
                    </div>

                    {{-- Pricing Tiers (managed by EnrollmentForm Alpine component) --}}
                    <div id="pricing-tiers-section" class="border rounded-lg p-4 bg-gray-50 space-y-4">
                        <h3 class="font-semibold text-gray-800">Harga Bertingkat (Pricing Tiers)</h3>
                        <p class="text-xs text-gray-500">Atur harga berbeda berdasarkan jumlah murid yang hadir.</p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jumlah Murid di Enrollment Ini</label>
                            <input type="number" name="student_count" x-model.number="tierCount" min="1" max="10" class="mt-1 w-full sm:w-24 border-gray-300 rounded-md" />
                        </div>

                        <div class="mt-4 grid md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Harga Ortu / Pertemuan</h4>
                                <table class="w-full text-sm">
                                    <template x-for="i in tierCount" :key="i">
                                        <tr>
                                            <td class="py-1 pr-2 text-gray-600 whitespace-nowrap" x-text="i + ' murid:'"></td>
                                            <td class="py-1">
                                                <input type="number" min="0" step="1000" class="w-full border-gray-300 rounded-md text-sm"
                                                       :name="'pricing_tiers_parent[' + i + ']'"
                                                       :value="getParentTier(i)"
                                                       @input="setParentTier(i, $event.target.value)"
                                                       :placeholder="'Rp ' + (i * 100000)" />
                                            </td>
                                        </tr>
                                    </template>
                                </table>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Gaji Guru / Pertemuan</h4>
                                <table class="w-full text-sm">
                                    <template x-for="i in tierCount" :key="i">
                                        <tr>
                                            <td class="py-1 pr-2 text-gray-600 whitespace-nowrap" x-text="i + ' murid:'"></td>
                                            <td class="py-1">
                                                <input type="number" min="0" step="1000" class="w-full border-gray-300 rounded-md text-sm"
                                                       :name="'pricing_tiers_teacher[' + i + ']'"
                                                       :value="getTeacherTier(i)"
                                                       @input="setTeacherTier(i, $event.target.value)"
                                                       :placeholder="'Rp ' + (i * 50000)" />
                                            </td>
                                        </tr>
                                    </template>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Default Rate Fields --}}
                    <div class="grid md:grid-cols-2 gap-4" id="rate-fields">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" id="parent-rate-label">Harga Ortu Default (1 murid)</label>
                            <input type="number" name="parent_rate" id="parent-rate" value="{{ old('parent_rate', $enrollment->parent_rate) }}" step="1000" class="mt-1 w-full border-gray-300 rounded-md"
                                   @input="markTouched" />
                            <p class="crud-error-parent_rate text-sm text-rose-600" style="display:none"></p>
                        </div>
                        <div id="teacher-rate-field">
                            <label class="block text-sm font-medium text-gray-700">Gaji Guru Default (1 murid)</label>
                            <input type="number" name="teacher_rate" id="teacher-rate" value="{{ old('teacher_rate', $enrollment->teacher_rate) }}" step="1000" class="mt-1 w-full border-gray-300 rounded-md"
                                   @input="markTouched" />
                            <p class="crud-error-teacher_rate text-sm text-rose-600" style="display:none"></p>
                        </div>
                    </div>

                    <div id="agreed-sessions-field">
                        <label class="block text-sm font-medium text-gray-700">Janji Les per Bulan</label>
                        <div id="sessions-privat">
                            <select name="agreed_sessions_per_month" id="agreed-sessions-select" class="mt-1 w-full sm:w-48 border-gray-300 rounded-md" required>
                                <option value="4" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 4) == 4)>1x seminggu (4x sebulan)</option>
                                <option value="8" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 4) == 8)>2x seminggu (8x sebulan)</option>
                                <option value="12" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 4) == 12)>3x seminggu (12x sebulan)</option>
                                <option value="16" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 4) == 16)>4x seminggu (16x sebulan)</option>
                                <option value="20" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 4) == 20)>5x seminggu (20x sebulan)</option>
                                <option value="24" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 4) == 24)>6x seminggu (24x sebulan)</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Jika murid hadir kurang dari setengah jumlah ini, rate akan ditambah Rp 5.000/pertemuan.</p>
                        </div>
                        <div id="sessions-kelas" class="hidden">
                            <select name="agreed_sessions_per_month" id="agreed-sessions-select-kelas" class="mt-1 w-full sm:w-48 border-gray-300 rounded-md">
                                <option value="">Pilih frekuensi</option>
                                <option value="4" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 16) == 4)>1x seminggu (4x sebulan)</option>
                                <option value="8" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 16) == 8)>2x seminggu (8x sebulan)</option>
                                <option value="12" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 16) == 12)>3x seminggu (12x sebulan)</option>
                                <option value="16" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 16) == 16)>4x seminggu (16x sebulan)</option>
                                <option value="20" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 16) == 20)>5x seminggu (20x sebulan)</option>
                                <option value="24" @selected(old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 16) == 24)>6x seminggu (24x sebulan)</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Jumlah sesi paket dalam sebulan. Digunakan untuk perhitungan biaya paket les setengah/penuh.</p>
                        </div>
                        <p class="crud-error-agreed_sessions_per_month text-sm text-rose-600" style="display:none"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="active" @selected(old('status', $enrollment->status) === 'active')>active</option>
                            <option value="hibernasi" @selected(old('status', $enrollment->status) === 'hibernasi')>hibernasi</option>
                        </select>
                        <p class="crud-error-status text-sm text-rose-600" style="display:none"></p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.enrollments.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white flex items-center gap-2" :disabled="submitting">
                            <template x-if="submitting">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </template>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
