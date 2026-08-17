<x-app-layout>
    <x-slot name="title">Upload Dokumen</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upload Dokumen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Judul</label>
                        <input type="text" name="title" value="{{ old('title') }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('title')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" class="mt-1 w-full border-gray-300 rounded-md" rows="3">{{ old('description') }}</textarea>
                        @error('description')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">File</label>
                        <input type="file" name="file" class="mt-1 w-full border-gray-300 rounded-md text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100" required />
                        @error('file')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        <p class="text-xs text-gray-500 mt-1">Maks 50MB. Format: PDF, DOC, DOCX, dll.</p>
                    </div>

                    <div x-data="{ accessType: '{{ old('access_type', 'teacher') }}' }">
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

                        {{-- Teacher selection --}}
                        <div x-show="accessType === 'teacher'" class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Guru yang Bisa Akses</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto border rounded-md p-3">
                                @foreach ($teachers as $teacher)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="teacher_ids[]" value="{{ $teacher->id }}" class="rounded border-gray-300 text-indigo-600" />
                                        <span>{{ $teacher->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Password input --}}
                        <div x-show="accessType === 'password'" class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="text" name="access_password" value="{{ old('access_password') }}" class="mt-1 w-full sm:w-64 border-gray-300 rounded-md" placeholder="Contoh: buku123" />
                            <p class="text-xs text-gray-500 mt-1">Guru harus memasukkan password ini untuk mengakses dokumen.</p>
                            @error('access_password')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('admin.documents.index') }}" class="px-4 py-2 rounded-md border text-sm">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</x-app-layout>