<form id="crud-form"
      x-data='{
          accessType: "{{ old('access_type', $document?->access_type ?? 'teacher') }}",
      }'
      @submit.prevent="$parent.submit()">

    @csrf

    {{-- Judul --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Judul <span class="text-rose-500">*</span>
        </label>
        <input type="text" name="title"
               value="{{ old('title', $document?->title ?? '') }}"
               class="crud-field-title w-full border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-sm"
               required />
        <p class="crud-error-title text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Deskripsi --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
        <textarea name="description" rows="2"
                  class="crud-field-description w-full border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-sm">{{ old('description', $document?->description ?? '') }}</textarea>
        <p class="crud-error-description text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- File --}}
    <div class="mb-4">
        @if ($document)
            <p class="text-sm text-gray-600 mb-1">
                File saat ini: <span class="font-medium">{{ $document->file_name }}</span>
                ({{ $document->formatted_size }})
            </p>
            <p class="text-xs text-gray-400 mb-2">Kosongkan jika tidak ingin mengganti file.</p>
        @endif
        <label class="block text-sm font-medium text-gray-700 mb-1">
            File {{ $document ? '(opsional)' : '' }} <span class="text-rose-500">*</span>
        </label>
        <input type="file" name="file" id="file-input"
               class="crud-field-file w-full border border-gray-300 rounded-lg text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"
               {{ $document ? '' : 'required' }} />
        <p class="text-xs text-gray-500 mt-1">Maks 50MB. Format: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, PNG.</p>
        <p class="crud-error-file text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Tingkat Proteksi --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Tingkat Proteksi</label>
        <select name="protection_level"
                class="crud-field-protection_level w-full border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-sm">
            <option value="standard" @selected(old('protection_level', $document?->protection_level ?? 'standard') === 'standard')>
                Longgar — guru bisa download
            </option>
            <option value="strict" @selected(old('protection_level', $document?->protection_level ?? '') === 'strict')>
                Ketat — hanya bisa dilihat
            </option>
        </select>
        <p class="crud-error-protection_level text-sm text-rose-500 mt-1" style="display:none"></p>
    </div>

    {{-- Metode Akses --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Metode Akses</label>
        <div class="flex gap-4 mb-3">
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="radio" name="access_type" value="teacher" x-model="accessType"
                       class="text-indigo-600" />
                <span>Guru Tertentu</span>
            </label>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="radio" name="access_type" value="password" x-model="accessType"
                       class="text-indigo-600" />
                <span>Password</span>
            </label>
        </div>

        {{-- Teacher checkboxes --}}
        <div x-show="accessType === 'teacher'" class="mb-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Guru</label>
            <div class="grid grid-cols-2 gap-2 max-h-44 overflow-y-auto border rounded-lg p-3">
                @php
                    $selectedIds = old('teacher_ids', $document?->teachers?->pluck('id')->all() ?? []);
                @endphp
                @forelse ($teachers as $teacher)
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}"
                               @checked(in_array($teacher->id, $selectedIds))
                               class="rounded border-gray-300 text-indigo-600" />
                        <span>{{ $teacher->displayName }}</span>
                    </label>
                @empty
                    <p class="text-sm text-gray-400 col-span-2">Belum ada guru.</p>
                @endforelse
            </div>
            <p class="crud-error-teacher_ids text-sm text-rose-500 mt-1" style="display:none"></p>
        </div>

        {{-- Password input --}}
        <div x-show="accessType === 'password'" class="mb-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Password
                @if ($document?->access_password_plain)
                    <span class="text-xs text-gray-400 font-normal">(biarkan kosong jika tidak diubah)</span>
                @endif
            </label>
            <input type="text" name="access_password"
                   value="{{ old('access_password') }}"
                   class="crud-field-access_password w-full sm:w-64 border border-gray-300 rounded-lg px-3 py-2 text-sm"
                   placeholder="Password baru" />
            @if ($document?->access_password_plain)
                <p class="text-xs text-gray-500 mt-1">
                    Password saat ini: <span class="font-mono bg-gray-100 px-1 rounded">{{ $document->access_password_plain }}</span>
                </p>
            @endif
            <p class="crud-error-access_password text-sm text-rose-500 mt-1" style="display:none"></p>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</form>
