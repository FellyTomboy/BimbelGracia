<x-app-layout>
    <x-slot name="title">Lengkapi Data Guru Sebelum Slip Gaji</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lengkapi Data Guru Sebelum Slip Gaji</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Data guru belum lengkap</h3>
                    <p class="text-sm text-gray-600 mt-1">Harap lengkapi semua data yang dibutuhkan untuk menampilkan slip gaji dan dokumen resmi.</p>
                </div>

                <form method="POST" action="{{ route('guru.complete-data') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ $redirect_to }}">

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input type="text" name="full_name" value="{{ old('full_name', $teacher->full_name) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nama lengkap" />
                            @error('full_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Panggilan</label>
                            <input type="text" name="nickname" value="{{ old('nickname', $teacher->nickname) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nama panggilan" />
                            @error('nickname') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jurusan / Bidang</label>
                            <input type="text" name="major" value="{{ old('major', $teacher->major) }}" class="mt-1 w-full border-gray-300 rounded-md" required placeholder="Jurusan / bidang" />
                            @error('major') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mata Pelajaran</label>
                            <input type="text" name="subjects" value="{{ old('subjects', $teacher->subjects) }}" class="mt-1 w-full border-gray-300 rounded-md" required placeholder="Mata pelajaran" />
                            @error('subjects') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat</label>
                        <input type="text" name="address" value="{{ old('address', $teacher->address) }}" class="mt-1 w-full border-gray-300 rounded-md" required placeholder="Alamat lengkap" />
                        @error('address') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Bank</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $teacher->bank_name) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Bank" />
                            @error('bank_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">No. Rekening</label>
                            <input type="text" name="bank_account" value="{{ old('bank_account', $teacher->bank_account) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Nomor rekening" />
                            @error('bank_account') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Pemilik Rekening</label>
                            <input type="text" name="bank_owner" value="{{ old('bank_owner', $teacher->bank_owner) }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Pemilik rekening" />
                            @error('bank_owner') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('guru.salary-projection.index') }}" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan & Lanjutkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
