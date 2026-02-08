<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Murid Kelas Bersama</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.class-students.update', $classStudent) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input name="name" value="{{ old('name', $classStudent->name) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('name')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">WhatsApp Utama</label>
                            <input name="whatsapp_primary" value="{{ old('whatsapp_primary', $classStudent->whatsapp_primary) }}" class="mt-1 w-full border-gray-300 rounded-md" required />
                            @error('whatsapp_primary')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">WhatsApp Cadangan</label>
                            <input name="whatsapp_secondary" value="{{ old('whatsapp_secondary', $classStudent->whatsapp_secondary) }}" class="mt-1 w-full border-gray-300 rounded-md" />
                            @error('whatsapp_secondary')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tarif per Pertemuan</label>
                        <input type="number" name="rate_per_meeting" value="{{ old('rate_per_meeting', $classStudent->rate_per_meeting) }}" min="0" class="mt-1 w-full border-gray-300 rounded-md" required />
                        @error('rate_per_meeting')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan</label>
                        <textarea name="notes" class="mt-1 w-full border-gray-300 rounded-md">{{ old('notes', $classStudent->notes) }}</textarea>
                        @error('notes')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 w-full border-gray-300 rounded-md" required>
                            <option value="active" @selected(old('status', $classStudent->status) === 'active')>active</option>
                            <option value="hibernasi" @selected(old('status', $classStudent->status) === 'hibernasi')>hibernasi</option>
                        </select>
                        @error('status')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.class-students.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
