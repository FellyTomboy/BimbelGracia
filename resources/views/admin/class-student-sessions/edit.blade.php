<x-app-layout>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Jadwal Murid Kelas</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.class-student-sessions.update', $classStudentSession) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="text" id="session_date" name="session_date" value="{{ old('session_date', $classStudentSession->session_date->format('Y-m-d')) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('session_date')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jam Mulai (24 jam)</label>
                            <input
                                type="text"
                                id="start_time"
                                name="start_time"
                                value="{{ old('start_time', $classStudentSession->start_time?->format('H:i')) }}"
                                placeholder="HH:MM"
                                pattern="^([01]\d|2[0-3]):[0-5]\d$"
                                class="mt-1 w-full border-gray-300 rounded-md"
                                required
                            />
                            @error('start_time')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jam Selesai (24 jam)</label>
                            <input
                                type="text"
                                id="end_time"
                                name="end_time"
                                value="{{ old('end_time', $classStudentSession->end_time?->format('H:i')) }}"
                                placeholder="HH:MM"
                                pattern="^([01]\d|2[0-3]):[0-5]\d$"
                                class="mt-1 w-full border-gray-300 rounded-md"
                            />
                            @error('end_time')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Murid (pilih beberapa)</label>

                        @php
                            $oldIds = collect((array) old('class_student_ids', $classStudentSession->students->pluck('id')->toArray()))->map(fn ($v) => (string)$v)->values();
                        @endphp
                        <input
                            type="text"
                            name="student_search"
                            id="student_search"
                            placeholder="Cari nama murid..."
                            class="mt-1 w-full border-gray-300 rounded-md"
                            value="{{ old('student_search') }}"
                            autocomplete="off"
                        />

                        <div class="mt-2 border border-gray-200 rounded-md p-2 max-h-56 overflow-y-auto">
                            <div id="student_list" class="space-y-2">
                                @foreach ($students as $student)
                                    <label class="flex items-start gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            name="class_student_ids[]"
                                            value="{{ $student->id }}"
                                            class="mt-1"
                                            @checked($oldIds->contains((string)$student->id))
                                        />
                                        <span>
                                            {{ $student->name }}
                                            @if (($student->status ?? '') === 'hibernasi')
                                                <span class="text-xs text-amber-700 font-semibold">(hibernasi)</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        @error('class_student_ids')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Di HP cukup centang, tanpa CTRL/Command.</p>

                        <script>
                            (function () {
                                const input = document.getElementById('student_search');
                                const list = document.getElementById('student_list');
                                if (!input || !list) return;

                                input.addEventListener('input', function () {
                                    const q = (input.value || '').toLowerCase().trim();
                                    const labels = list.querySelectorAll('label');
                                    labels.forEach(label => {
                                        const text = (label.innerText || '').toLowerCase();
                                        label.style.display = (!q || text.includes(q)) ? '' : 'none';
                                    });
                                });
                            })();
                        </script>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan</label>
                        <textarea name="notes" class="mt-1 w-full border-gray-300 rounded-md">{{ old('notes', $classStudentSession->notes) }}</textarea>
                        @error('notes')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.class-student-sessions.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#session_date", {
            dateFormat: "Y-m-d",
        });

        flatpickr("#start_time", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
        });

        flatpickr("#end_time", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
        });
    </script>
</x-app-layout>
