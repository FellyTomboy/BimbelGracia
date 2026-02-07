<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Jadwal Kelas</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.class-sessions.update', $classSession) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kelas</label>
                        <select name="class_group_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}" @selected(old('class_group_id', $classSession->class_group_id) == $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                        @error('class_group_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="date" name="session_date" value="{{ old('session_date', $classSession->session_date->format('Y-m-d')) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('session_date')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jam</label>
                            <input type="time" name="session_time" value="{{ old('session_time', $classSession->session_time?->format('H:i')) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                            @error('session_time')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mapel</label>
                            <input name="subject" value="{{ old('subject', $classSession->subject) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                            @error('subject')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan</label>
                        <textarea name="notes" class="mt-1 w-full border-gray-300 rounded-md">{{ old('notes', $classSession->notes) }}</textarea>
                        @error('notes')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Daftar Murid</label>
                        <div class="mt-2 grid md:grid-cols-2 gap-2 max-h-64 overflow-y-auto border rounded-md p-3">
                            @foreach ($students as $student)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                        @checked(in_array($student->id, old('student_ids', $classSession->students->pluck('id')->all()))) />
                                    <span>{{ $student->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('student_ids')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.class-sessions.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
