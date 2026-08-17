<x-app-layout>
    <x-slot name="title">Edit Dokumen</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Dokumen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.documents.update', $document) }}" enctype="multipart/form-data" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Judul</label>
                        <input type="text" name="title" value="{{ old('title', $document->title) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('title')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" class="mt-1 w-full border-gray-300 rounded-md" rows="3">{{ old('description', $document->description) }}</textarea>
                        @error('description')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">File Saat Ini</label>
                        <p class="text-sm text-gray-600 mt-1">{{ $document->file_name }} ({{ $document->formatted_size }})</p>
                        <label class="block text-sm font-medium text-gray-700 mt-4">Ganti File (opsional)</label>
                        <input type="file" name="file" class="mt-1 w-full border-gray-300 rounded-md text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100" />
                        @error('file')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div x-data="{ accessType: '{{ old('access_type', $document->access_type) }}' }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Metode Akses</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="access_type" value="teacher" x-model="accessType" class="text-indigo-600" />
                                <span class="text-sm">Guru Tertentu</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="access_type" value="password" x-model="accessType" class="text-indigo-600" />
                                <span class="text-sm">Password</span>
                            </label>
                        </div>

                        <div x-show="accessType === 'teacher'" class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Guru yang Bisa Akses</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto border rounded-md p-3">
                                @php $selectedTeachers = old('teacher_ids', $document->teachers->pluck('id')->all()); @endphp
                                @foreach ($teachers as $teacher)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}"
                                            @checked(in_array($teacher->id, $selectedTeachers))
                                            class="rounded border-gray-300 text-indigo-600" />
                                        <span>{{ $teacher->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div x-show="accessType === 'password'" class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">
                                Password
                                <span class="text-xs text-gray-400 font-normal">(biarkan kosong jika tidak diubah)</span>
                            </label>
                            <div class="flex items-center gap-2 mt-1">
                                <input type="text" name="access_password" class="w-full sm:w-64 border-gray-300 rounded-md" placeholder="Password baru" />
                            </div>
                            @if ($document->access_password_plain)
                                <p class="text-xs text-gray-500 mt-1">Password saat ini: <span class="font-mono bg-gray-100 px-1 rounded">{{ $document->access_password_plain }}</span></p>
                            @endif
                            @error('access_password')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('admin.documents.index') }}" class="px-4 py-2 rounded-md border text-sm">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</x-app-layout>