{{-- Enrollment CRUD Modal Form Partial --}}
{{-- Used by both create and edit modals via AJAX injection --}}
{{-- No layout wrapper — renders only the form content --}}
@php
    $isEdit = $enrollment !== null;
    $initialType = $isEdit ? $enrollment->type : ($defaultType ?? 'privat');
    $initialStudentCount = $isEdit && $enrollment->students->count() > 0
        ? $enrollment->students->count()
        : 3;
    $initialParentTiers = $isEdit ? ($enrollment->pricing_tiers['parent_rate'] ?? []) : [];
    $initialTeacherTiers = $isEdit ? ($enrollment->pricing_tiers['teacher_rate'] ?? []) : [];
    // Fill missing keys up to initialStudentCount for reliable JS array access
    $filledParentTiers = collect(range(1, $initialStudentCount))
        ->map(fn($i) => $initialParentTiers[$i] ?? null)
        ->values()
        ->all();
    $filledTeacherTiers = collect(range(1, $initialStudentCount))
        ->map(fn($i) => $initialTeacherTiers[$i] ?? null)
        ->values()
        ->all();
    $initialSelectedStudentIds = $isEdit ? $enrollment->students->pluck('id')->all() : [];
@endphp

<form id="crud-form" method="POST"
      x-data="EnrollmentForm({
          formUrl: {{ $isEdit ? "'/admin/enrollments/{$enrollment->id}'" : "'/admin/enrollments'" }},
          enrollmentId: {{ $isEdit ? $enrollment->id : 'null' }},
          initialType: '{{ $initialType }}',
          initialStudentCount: {{ $initialStudentCount }},
          initialParentTiers: {{ json_encode($filledParentTiers) }},
          initialTeacherTiers: {{ json_encode($filledTeacherTiers) }},
          idPrefix: 'modal-',
      })"
      x-init="init()"
      @submit.prevent="$parent.submit()">

    @if ($isEdit)
        @csrf
        @method('PUT')
    @else
        @csrf
    @endif

    <div class="space-y-4">
        {{-- Top-level error banner (Alpine shows this when errors exist) --}}
        <div id="form-error-alert" class="rounded-md border border-rose-300 bg-rose-50 p-4" role="alert" style="display:none">
            <h4 class="text-sm font-semibold text-rose-800">
                Enrollment tidak dapat disimpan. Silakan perbaiki hal berikut:
            </h4>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700"></ul>
        </div>

        {{-- Type + Program --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Enrollment</label>
                <select name="type" id="modal-type-select" class="crud-field-type mt-1 w-full border-gray-300 rounded-lg text-sm" required @change="onTypeChange">
                    <option value="privat" @selected($initialType === 'privat')>Privat (Per Sesi)</option>
                    <option value="kelas" @selected($initialType === 'kelas')>Kelas (Paket Bulanan)</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Privat: tagihan per sesi. Kelas: paket bulanan dengan guru bisa berganti per sesi.</p>
                <p class="crud-error-type mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                <select name="program_id" id="modal-program-select" class="crud-field-program_id mt-1 w-full border-gray-300 rounded-lg text-sm" required @change="onProgramChange">
                    <option value="">Pilih program</option>
                    @foreach ($programs as $program)
                        <option
                            value="{{ $program->id }}"
                            data-default-parent="{{ $program->default_parent_rate }}"
                            data-default-teacher="{{ $program->default_teacher_rate }}"
                            data-type="{{ $program->type }}"
                            @if ($isEdit)
                                @selected(old('program_id', $enrollment->program_id) == $program->id)
                            @else
                                @selected(old('program_id') == $program->id)
                            @endif
                        >
                            {{ $program->name }}
                        </option>
                    @endforeach
                </select>
                <p class="crud-error-program_id mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
        </div>

        {{-- Teacher Field --}}
        <div id="modal-teacher-field">
            <label class="block text-sm font-medium text-gray-700 mb-1">Guru</label>
            <select name="teacher_id" id="modal-teacher-select" class="crud-field-teacher_id mt-1 w-full border-gray-300 rounded-lg text-sm">
                <option value="">Pilih guru</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}"
                        @if ($isEdit)
                            @selected(old('teacher_id', $enrollment->teacher_id) == $teacher->id)
                        @else
                            @selected(old('teacher_id') == $teacher->id)
                        @endif
                    >{{ $teacher->displayName }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Guru utama untuk privat. Untuk kelas, guru bisa berubah per sesi saat pencatatan kehadiran.</p>
            <p class="crud-error-teacher_id mt-1 text-sm text-rose-500" style="display:none"></p>
        </div>

        {{-- Student Selection --}}
        <div>
            {{-- Checkbox mode (privat) --}}
            <div id="modal-student-checkbox-section">
                <label class="block text-sm font-medium text-gray-700 mb-1">Daftar Murid</label>
                <input type="text" id="modal-student-search" placeholder="Cari nama murid..." class="mt-1 mb-2 w-full border-gray-300 rounded-lg text-sm"
                       @input="onStudentSearch" />
                <div class="mt-1 grid md:grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                    @foreach ($students as $student)
                        <label class="flex items-center gap-2 text-sm student-label">
                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                   class="student-checkbox"
                                   @if ($isEdit)
                                       @checked(in_array($student->id, $initialSelectedStudentIds))
                                   @else
                                       @checked(is_array(old('student_ids')) && in_array($student->id, old('student_ids', [])))
                                   @endif
                                   @change="onStudentChange" />
                            <span>{{ $student->display_name }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="crud-error-student_ids mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>

            {{-- Dropdown mode (kelas) --}}
            <div id="modal-student-dropdown-section" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Murid</label>
                <select name="student_ids[]" id="modal-student-dropdown" class="crud-field-student_ids mt-1 w-full border-gray-300 rounded-lg text-sm">
                    <option value="">Pilih murid</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}"
                            @if ($isEdit)
                                @selected(in_array($student->id, $initialSelectedStudentIds))
                            @else
                                @selected(is_array(old('student_ids')) && in_array($student->id, old('student_ids', [])))
                            @endif
                        >{{ $student->display_name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Pilih satu murid untuk enrollment kelas.</p>
                <p class="crud-error-student_ids mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
        </div>

        {{-- Pricing Tiers --}}
        <div id="modal-pricing-tiers-section" class="border border-gray-200 rounded-lg p-4 bg-gray-50 space-y-4">
            <h3 class="font-semibold text-gray-800 text-sm">Harga Bertingkat (Pricing Tiers)</h3>
            <p class="text-xs text-gray-500">Atur harga berbeda berdasarkan jumlah murid yang hadir.</p>

            {{-- Tier count --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Murid di Enrollment Ini</label>
                <input type="number" name="student_count" x-model.number="tierCount" min="1" max="10"
                       class="mt-1 w-full sm:w-24 border-gray-300 rounded-lg text-sm" />
            </div>

            {{-- Hidden inputs synced to Alpine state (submitted with form) --}}
            <template x-for="i in tierCount" :key="i">
                <span>
                    <input type="hidden"
                           :name="'pricing_tiers_parent[' + i + ']'"
                           x-model.number="parentTiers[i]" />
                    <input type="hidden"
                           :name="'pricing_tiers_teacher[' + i + ']'"
                           x-model.number="teacherTiers[i]" />
                </span>
            </template>

            {{-- Dynamic tier rows --}}
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Harga Ortu / Pertemuan</h4>
                    <table class="w-full text-sm">
                        <template x-for="i in tierCount" :key="i">
                            <tr>
                                <td class="py-1 pr-2 text-gray-600 whitespace-nowrap" x-text="i + ' murid:'"></td>
                                <td class="py-1">
                                    <input type="number" min="0" step="1000"
                                           class="w-full border-gray-300 rounded-md text-sm"
                                           x-model.number="parentTiers[i]"
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
                                    <input type="number" min="0" step="1000"
                                           class="w-full border-gray-300 rounded-md text-sm"
                                           x-model.number="teacherTiers[i]"
                                           :placeholder="'Rp ' + (i * 50000)" />
                                </td>
                            </tr>
                        </template>
                    </table>
                </div>
            </div>
        </div>

        {{-- Default Rate Fields --}}
        <div class="grid md:grid-cols-2 gap-4" id="modal-rate-fields">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" id="modal-parent-rate-label">Harga Ortu Default (1 murid)</label>
                <input type="number" name="parent_rate" id="modal-parent-rate"
                       @if ($isEdit) value="{{ old('parent_rate', $enrollment->parent_rate) }}"
                       @else value="{{ old('parent_rate') }}"
                       @endif
                       step="1000" class="crud-field-parent_rate mt-1 w-full border-gray-300 rounded-lg text-sm"
                       @input="markTouched" />
                <p class="crud-error-parent_rate mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
            <div id="modal-teacher-rate-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">Gaji Guru Default (1 murid)</label>
                <input type="number" name="teacher_rate" id="modal-teacher-rate"
                       @if ($isEdit) value="{{ old('teacher_rate', $enrollment->teacher_rate) }}"
                       @else value="{{ old('teacher_rate') }}"
                       @endif
                       step="1000" class="crud-field-teacher_rate mt-1 w-full border-gray-300 rounded-lg text-sm"
                       @input="markTouched" />
                <p class="crud-error-teacher_rate mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
        </div>

        {{-- Agreed Sessions --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Janji Les per Bulan</label>
            <div id="modal-sessions-privat">
                <select name="agreed_sessions_per_month" id="modal-agreed-sessions-select"
                        class="crud-field-agreed_sessions_per_month mt-1 w-full sm:w-48 border-gray-300 rounded-lg text-sm" required>
                    @php
                        $agreedValue = $isEdit
                            ? old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 4)
                            : old('agreed_sessions_per_month', 4);
                    @endphp
                    <option value="4" @selected($agreedValue == 4)>1x seminggu (4x sebulan)</option>
                    <option value="8" @selected($agreedValue == 8)>2x seminggu (8x sebulan)</option>
                    <option value="12" @selected($agreedValue == 12)>3x seminggu (12x sebulan)</option>
                    <option value="16" @selected($agreedValue == 16)>4x seminggu (16x sebulan)</option>
                    <option value="20" @selected($agreedValue == 20)>5x seminggu (20x sebulan)</option>
                    <option value="24" @selected($agreedValue == 24)>6x seminggu (24x sebulan)</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Jika murid hadir kurang dari setengah jumlah ini, rate akan ditambah Rp 5.000/pertemuan.</p>
            </div>
            <div id="modal-sessions-kelas" class="hidden">
                <select name="agreed_sessions_per_month" id="modal-agreed-sessions-select-kelas"
                        class="crud-field-agreed_sessions_per_month mt-1 w-full sm:w-48 border-gray-300 rounded-lg text-sm">
                    @php
                        $agreedKelasValue = $isEdit
                            ? old('agreed_sessions_per_month', $enrollment->agreed_sessions_per_month ?? 16)
                            : old('agreed_sessions_per_month', 16);
                    @endphp
                    <option value="">Pilih frekuensi</option>
                    <option value="4" @selected($agreedKelasValue == 4)>1x seminggu (4x sebulan)</option>
                    <option value="8" @selected($agreedKelasValue == 8)>2x seminggu (8x sebulan)</option>
                    <option value="12" @selected($agreedKelasValue == 12)>3x seminggu (12x sebulan)</option>
                    <option value="16" @selected($agreedKelasValue == 16)>4x seminggu (16x sebulan)</option>
                    <option value="20" @selected($agreedKelasValue == 20)>5x seminggu (20x sebulan)</option>
                    <option value="24" @selected($agreedKelasValue == 24)>6x seminggu (24x sebulan)</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Jumlah sesi paket dalam sebulan. Digunakan untuk perhitungan biaya paket les setengah/penuh.</p>
            </div>
            <p class="crud-error-agreed_sessions_per_month mt-1 text-sm text-rose-500" style="display:none"></p>
        </div>

        {{-- Status --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="crud-field-status mt-1 w-full border-gray-300 rounded-lg text-sm" required>
                @php
                    $statusVal = $isEdit ? old('status', $enrollment->status) : old('status');
                @endphp
                <option value="active" @selected($statusVal === 'active' || $statusVal === null)>active</option>
                <option value="hibernasi" @selected($statusVal === 'hibernasi')>hibernasi</option>
            </select>
            <p class="crud-error-status mt-1 text-sm text-rose-500" style="display:none"></p>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] { -moz-appearance: textfield; appearance: textfield; }
    </style>
</form>
