<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Jadwal Murid Kelas</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.class-student-sessions.update', $classStudentSession) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Murid</label>
                        <select name="class_student_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected(old('class_student_id', $classStudentSession->class_student_id) == $student->id)>{{ $student->name }}</option>
                            @endforeach
                        </select>
                        @error('class_student_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="date" name="session_date" value="{{ old('session_date', $classStudentSession->session_date->format('Y-m-d')) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('session_date')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jam Mulai</label>
                            <input type="time" name="start_time" value="{{ old('start_time', $classStudentSession->start_time?->format('H:i')) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('start_time')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jam Selesai</label>
                            <input type="time" name="end_time" value="{{ old('end_time', $classStudentSession->end_time?->format('H:i')) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                            @error('end_time')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
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
</x-app-layout>
