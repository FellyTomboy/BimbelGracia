<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Les Privat</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.lessons.store') }}" class="p-6 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">ID Les</label>
                        <input name="code" value="{{ old('code') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('code')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Murid</label>
                        <select name="student_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="">Pilih murid</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }}</option>
                            @endforeach
                        </select>
                        @error('student_id')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Biaya Ortu / Pertemuan</label>
                            <input type="number" name="parent_rate" value="{{ old('parent_rate') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('parent_rate')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gaji Guru / Pertemuan</label>
                            <input type="number" name="teacher_rate" value="{{ old('teacher_rate') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
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

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.lessons.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
