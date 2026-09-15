<form id="crud-form"
      x-data="{
          type: '{{ ($program->type ?? old('type', 'privat')) }}',
          status: '{{ ($program->status ?? old('status', 'active')) }}',
          selectedTeachers: {},
          teacherRates: {},
          init() {
              @if ($program && $program->teachers->count())
                  @foreach ($program->teachers as $teacher)
                      this.selectedTeachers[{{ $teacher->id }}] = true;
                      this.teacherRates[{{ $teacher->id }}] = {{ $teacher->pivot->rate }};
                  @endforeach
              @endif
          }
      }">

    @csrf

    <div class="space-y-4">
        {{-- Nama Program --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Nama Program <span class="text-rose-500">*</span></label>
            <input name="name"
                   value="{{ old('name', $program->name ?? '') }}"
                   class="mt-1 w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                   required />
            <p x-show="errors.name" x-text="errors.name" class="mt-1 text-sm text-rose-500"></p>
        </div>

        {{-- Tipe --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Tipe <span class="text-rose-500">*</span></label>
            <select name="type" id="modal-program-type" x-model="type"
                    class="mt-1 w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    required>
                <option value="privat" @selected(old('type', $program->type ?? 'privat') === 'privat')>privat</option>
                <option value="kelas" @selected(old('type', $program->type ?? '') === 'kelas')>kelas</option>
            </select>
            <p x-show="errors.type" x-text="errors.type" class="mt-1 text-sm text-rose-500"></p>
        </div>

        {{-- Jenjang (division) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Jenjang <span class="text-xs text-gray-400">(untuk auto-update enrollment saat kenaikan kelas)</span></label>
            <select name="division"
                    class="mt-1 w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">— Tidak terikat jenjang —</option>
                <option value="TK" @selected(old('division', $program->division ?? '') === 'TK')>TK</option>
                <option value="SD" @selected(old('division', $program->division ?? '') === 'SD')>SD</option>
                <option value="SMP" @selected(old('division', $program->division ?? '') === 'SMP')>SMP</option>
                <option value="SMA" @selected(old('division', $program->division ?? '') === 'SMA')>SMA</option>
                <option value="mengaji" @selected(old('division', $program->division ?? '') === 'mengaji')>Mengaji (khusus)</option>
                <option value="mix" @selected(old('division', $program->division ?? '') === 'mix')>Mix (campuran jenjang)</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Dipakai sistem untuk auto-memindahkan enrollment saat siswa naik kelas.</p>
        </div>

        {{-- Mapel (privat only) --}}
        <div :class="type !== 'kelas' ? '' : 'hidden'">
            <label class="block text-sm font-medium text-gray-700">Mapel</label>
            <input name="subject"
                   value="{{ old('subject', $program->subject ?? '') }}"
                   class="mt-1 w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
            <p x-show="errors.subject" x-text="errors.subject" class="mt-1 text-sm text-rose-500"></p>
        </div>

        {{-- Harga privat --}}
        <div :class="type === 'privat' ? '' : 'hidden'">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Default Harga Ortu</label>
                    <input type="number" name="parent_rate_privat"
                           value="{{ old('parent_rate_privat', $program?->default_parent_rate) }}"
                           min="0" step="1000"
                           class="mt-1 w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Default Gaji Guru</label>
                    <input type="number" name="teacher_rate_privat"
                           value="{{ old('teacher_rate_privat', $program?->default_teacher_rate) }}"
                           min="0" step="1000"
                           class="mt-1 w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
            </div>
        </div>

        {{-- Harga kelas --}}
        <div :class="type === 'kelas' ? '' : 'hidden'">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Harga Paket Kelas</label>
                    <input type="number" name="parent_rate_kelas"
                           value="{{ old('parent_rate_kelas', $program?->default_parent_rate) }}"
                           min="0" step="1000"
                           class="mt-1 w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Default Gaji Guru per Sesi</label>
                    <input type="number" name="teacher_rate_kelas"
                           value="{{ old('teacher_rate_kelas', $program?->default_teacher_rate) }}"
                           min="0" step="1000"
                           class="mt-1 w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
            </div>
        </div>

        {{-- Guru & Biaya (kelas only) --}}
        <div x-show="type === 'kelas'" x-cloak>
            <div class="border rounded-lg p-4 bg-gray-50 space-y-3">
                <div>
                    <h3 class="font-semibold text-gray-800 text-sm">Guru &amp; Biaya per Sesi</h3>
                    <p class="text-xs text-gray-500">Pilih guru yang mengajar program ini.</p>
                </div>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse ($teachers as $teacher)
                        @php
                            $pivotRate = $program?->teachers?->firstWhere('id', $teacher->id)?->pivot?->rate;
                        @endphp
                        <div class="flex items-center gap-3 p-2 bg-white rounded border border-gray-200"
                             :class="selectedTeachers[{{ $teacher->id }}] ? 'border-indigo-400' : 'border-gray-200'">
                            <input type="checkbox"
                                   id="modal_teacher_{{ $teacher->id }}"
                                   x-model="selectedTeachers[{{ $teacher->id }}]"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <label for="modal_teacher_{{ $teacher->id }}" class="flex-1 text-sm text-gray-700 cursor-pointer">
                                {{ $teacher->displayName }}
                            </label>
                            <div class="flex items-center gap-1 text-sm">
                                <span class="text-gray-500">Rp</span>
                                <input type="number"
                                       name="teacher_rates[{{ $teacher->id }}]"
                                       x-model="teacherRates[{{ $teacher->id }}]"
                                       min="0" step="1000"
                                       placeholder="0"
                                       class="w-28 border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       x-bind:disabled="!selectedTeachers[{{ $teacher->id }}]" />
                                <span class="text-gray-500 text-xs">/sesi</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 py-2">Belum ada guru.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Status <span class="text-rose-500">*</span></label>
            <select name="status" x-model="status"
                    class="mt-1 w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    required>
                <option value="active">active</option>
                <option value="hibernasi">hibernasi</option>
            </select>
            <p x-show="errors.status" x-text="errors.status" class="mt-1 text-sm text-rose-500"></p>
        </div>

        {{-- Deskripsi --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
            <textarea name="description" rows="2"
                      class="mt-1 w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $program->description ?? '') }}</textarea>
            <p x-show="errors.description" x-text="errors.description" class="mt-1 text-sm text-rose-500"></p>
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
