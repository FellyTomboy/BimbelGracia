<x-app-layout>
    <x-slot name="title">Lengkapi Data Sebelum Melihat Invoice</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lengkapi Data Sebelum Melihat Invoice</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Data orang tua / wali dan murid belum lengkap</h3>
                    <p class="text-sm text-gray-600 mt-1">Harap lengkapi data berikut agar invoice dapat ditampilkan dengan benar.</p>
                </div>

                <form method="POST" action="{{ route('parent.billing.submit-complete-data') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ $redirect_to }}">

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Orang Tua / Wali</label>
                            <input type="text" name="name" value="{{ old('name', $parent->name) }}" class="mt-1 w-full border-gray-300 rounded-md" required placeholder="Nama orang tua / wali" />
                            @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Alamat</label>
                            <input type="text" name="address" value="{{ old('address', $parent->address) }}" class="mt-1 w-full border-gray-300 rounded-md" required placeholder="Alamat lengkap" />
                            @error('address') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="text-base font-semibold text-gray-900 mb-4">Nama lengkap murid</h4>
                        <div class="space-y-4">
                            @foreach ($students as $student)
                                <div class="grid md:grid-cols-2 gap-4 items-center border border-gray-200 rounded-md p-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nama panggilan</label>
                                        <input type="text" value="{{ $student->nickname }}" class="mt-1 w-full border-gray-300 rounded-md bg-gray-50" disabled />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nama lengkap murid</label>
                                        <input type="hidden" name="students[{{ $loop->index }}][id]" value="{{ $student->id }}">
                                        <input type="text" name="students[{{ $loop->index }}][full_name]" value="{{ old('students.' . $loop->index . '.full_name', $student->full_name) }}" class="mt-1 w-full border-gray-300 rounded-md" required placeholder="Nama lengkap murid" />
                                        @error('students.' . $loop->index . '.full_name')
                                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('parent.billing.index') }}" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan & Lanjutkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
