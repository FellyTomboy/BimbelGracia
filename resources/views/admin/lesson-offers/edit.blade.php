<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Tawaran Les</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.lesson-offers.update', $lessonOffer) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">ID Tawaran</label>
                        <input name="code" value="{{ old('code', $lessonOffer->code) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('code')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tingkat Pendidikan</label>
                        <select name="education_level" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih tingkat pendidikan</option>
                            @foreach ($educationLevels as $level)
                                <option value="{{ $level }}" @selected(old('education_level', $lessonOffer->education_level) === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                        @error('education_level')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mapel</label>
                        <input name="subject" value="{{ old('subject', $lessonOffer->subject) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('subject')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Jadwal (multi pasang hari-jam) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jadwal</label>
                        <div id="schedules_wrapper" class="space-y-2 mt-1">
                            @php
                                $existingSchedules = old('schedules', $lessonOffer->schedules ?: [['day' => '', 'time' => '']]);
                            @endphp
                            @foreach ($existingSchedules as $idx => $sch)
                                <div class="schedule-row flex items-center gap-2">
                                    <select name="schedules[{{ $idx }}][day]" class="w-full border-gray-300 rounded-md" required>
                                        <option value="">Hari</option>
                                        @foreach ($days as $day)
                                            <option value="{{ $day }}" @selected(($sch['day'] ?? '') === $day)>{{ $day }}</option>
                                        @endforeach
                                    </select>
                                    <select name="schedules[{{ $idx }}][time]" class="w-full border-gray-300 rounded-md" required>
                                        <option value="">Waktu</option>
                                        @foreach ($times as $time)
                                            <option value="{{ $time }}" @selected(($sch['time'] ?? '') === $time)>{{ ucfirst($time) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="remove-schedule text-rose-600 text-sm font-medium px-2">✕</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" id="add_schedule" class="mt-2 text-sm text-indigo-600 font-medium">+ Tambah jadwal</button>
                        @error('schedules')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        @error('schedules.*.day')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        @error('schedules.*.time')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kontak WhatsApp (opsional)</label>
                        <input name="contact_whatsapp" value="{{ old('contact_whatsapp', $lessonOffer->contact_whatsapp) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                        <p class="text-xs text-gray-500 mt-1">Jika kosong, gunakan kontak default di config.</p>
                        @error('contact_whatsapp')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan</label>
                        <textarea name="note" rows="3" class="mt-1 w-full border-gray-300 rounded-md">{{ old('note', $lessonOffer->note) }}</textarea>
                        @error('note')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="open" @selected(old('status', $lessonOffer->status) === 'open')>open</option>
                            <option value="closed" @selected(old('status', $lessonOffer->status) === 'closed')>closed</option>
                        </select>
                        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.lesson-offers.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const wrapper = document.getElementById('schedules_wrapper');
            const addBtn = document.getElementById('add_schedule');
            if (!wrapper || !addBtn) return;

            let idx = wrapper.querySelectorAll('.schedule-row').length;

            addBtn.addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'schedule-row flex items-center gap-2';
                row.innerHTML = `
                    <select name="schedules[${idx}][day]" class="w-full border-gray-300 rounded-md" required>
                        <option value="">Hari</option>
                        @foreach ($days as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                    <select name="schedules[${idx}][time]" class="w-full border-gray-300 rounded-md" required>
                        <option value="">Waktu</option>
                        @foreach ($times as $time)
                            <option value="{{ $time }}">{{ ucfirst($time) }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="remove-schedule text-rose-600 text-sm font-medium px-2">✕</button>
                `;
                wrapper.appendChild(row);
                idx++;
            });

            wrapper.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-schedule')) {
                    const row = e.target.closest('.schedule-row');
                    if (wrapper.querySelectorAll('.schedule-row').length > 1) {
                        row.remove();
                    }
                }
            });
        })();
    </script>
</x-app-layout>
