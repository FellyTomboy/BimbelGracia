<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pendaftaran Guru - Bimbel Gracia</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/website/logo_bimbel.jpg') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #fdf2f8 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="py-12 px-4">
    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-8">
            <div class="w-20 h-20 rounded-2xl overflow-hidden mx-auto shadow-lg mb-4">
                <img src="{{ asset('storage/website/logo_bimbel.jpg') }}" alt="Bimbel Gracia" class="w-full h-full object-cover" />
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Formulir Pendaftaran Guru</h1>
            <p class="text-gray-600 mt-1">Bimbel Gracia - Bimbingan Belajar Privat & Kelas</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
            <form method="POST" action="{{ route('register-teacher.submit', App\Http\Controllers\Admin\TeacherRegistrantController::PERMANENT_TOKEN) }}" class="space-y-6">
                @csrf

                {{-- Data Diri --}}
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Data Diri</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('name') border-red-300 @enderror"
                                placeholder="Nama lengkap" />
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp <span class="text-red-500">*</span></label>
                            <p class="text-xs text-gray-400 mb-1">(digunakan untuk login)</p>
                            <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('whatsapp') border-red-300 @enderror"
                                placeholder="08XXXXXXXXXX" />
                            @error('whatsapp') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="major" class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
                            <input type="text" id="major" name="major" value="{{ old('major') }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('major') border-red-300 @enderror"
                                placeholder="Contoh: Pendidikan Sejarah" />
                            @error('major') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="subjects" class="block text-sm font-medium text-gray-700 mb-1">Mapel</label>
                            <input type="text" id="subjects" name="subjects" value="{{ old('subjects') }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('subjects') border-red-300 @enderror"
                                placeholder="Mata pelajaran yang diajarkan" />
                            @error('subjects') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                            <textarea id="address" name="address" rows="2"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('address') border-red-300 @enderror"
                                placeholder="Alamat lengkap">{{ old('address') }}</textarea>
                            @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Data Bank --}}
                <div class="border-t border-gray-100 pt-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Data Bank (untuk pembayaran gaji)</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Bank</label>
                            <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('bank_name') border-red-300 @enderror"
                                placeholder="Contoh: BCA, Mandiri, BRI" />
                            @error('bank_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank_account" class="block text-sm font-medium text-gray-700 mb-1">No. Rekening</label>
                            <input type="text" id="bank_account" name="bank_account" value="{{ old('bank_account') }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('bank_account') border-red-300 @enderror"
                                placeholder="Nomor rekening" />
                            @error('bank_account') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank_owner" class="block text-sm font-medium text-gray-700 mb-1">Nama Pemilik Rekening</label>
                            <input type="text" id="bank_owner" name="bank_owner" value="{{ old('bank_owner') }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('bank_owner') border-red-300 @enderror"
                                placeholder="Nama pemilik rekening" />
                            @error('bank_owner') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <p class="text-xs text-gray-400">Data akan diverifikasi oleh admin Bimbel Gracia</p>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                        Kirim Pendaftaran
                    </button>
                </div>
            </form>
        </div>

        <p class="text-center text-sm text-gray-400 mt-6">Bimbel Gracia - Bimbingan Belajar Privat & Kelas</p>
    </div>
</body>
</html>