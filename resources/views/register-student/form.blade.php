<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pendaftaran - Bimbel Gracia</title>
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
            <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white font-bold text-2xl flex items-center justify-center mx-auto shadow-lg mb-4">
                BG
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Formulir Pendaftaran</h1>
            <p class="text-gray-600 mt-1">Bimbel Gracia - Bimbingan Belajar Privat & Kelas</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
            <form method="POST" action="{{ route('register-student.submit', $newStudent->token) }}" class="space-y-6">
                @csrf

                {{-- Data Murid --}}
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Data Murid</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('name') border-red-300 @enderror"
                                placeholder="Nama lengkap murid" />
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp Murid</label>
                            <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('whatsapp') border-red-300 @enderror"
                                placeholder="08xxxxxxxxxx" />
                            @error('whatsapp') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="school" class="block text-sm font-medium text-gray-700 mb-1">Sekolah</label>
                            <input type="text" id="school" name="school" value="{{ old('school') }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('school') border-red-300 @enderror"
                                placeholder="Nama sekolah" />
                            @error('school') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="grade" class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                            <input type="text" id="grade" name="grade" value="{{ old('grade') }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('grade') border-red-300 @enderror"
                                placeholder="Contoh: 10, 11, 12, atau 6 SD" />
                            @error('grade') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="division" class="block text-sm font-medium text-gray-700 mb-1">Divisi</label>
                            <select id="division" name="division"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('division') border-red-300 @enderror">
                                <option value="">Pilih divisi</option>
                                <option value="TK" {{ old('division') == 'TK' ? 'selected' : '' }}>TK</option>
                                <option value="SD" {{ old('division') == 'SD' ? 'selected' : '' }}>SD</option>
                                <option value="SMP" {{ old('division') == 'SMP' ? 'selected' : '' }}>SMP</option>
                                <option value="SMA" {{ old('division') == 'SMA' ? 'selected' : '' }}>SMA</option>
                                <option value="UTBK" {{ old('division') == 'UTBK' ? 'selected' : '' }}>UTBK</option>
                            </select>
                            @error('division') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Data Orang Tua --}}
                <div class="border-t border-gray-100 pt-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Data Orang Tua / Wali</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="parent_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Orang Tua</label>
                            <input type="text" id="parent_name" name="parent_name" value="{{ old('parent_name') }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('parent_name') border-red-300 @enderror"
                                placeholder="Nama orang tua / wali" />
                            @error('parent_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="parent_whatsapp" class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp Orang Tua</label>
                            <input type="text" id="parent_whatsapp" name="parent_whatsapp" value="{{ old('parent_whatsapp') }}"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('parent_whatsapp') border-red-300 @enderror"
                                placeholder="08xxxxxxxxxx" />
                            @error('parent_whatsapp') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="border-t border-gray-100 pt-6">
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
                        <textarea id="notes" name="notes" rows="3"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors @error('notes') border-red-300 @enderror"
                            placeholder="Informasi tambahan yang perlu diketahui">{{ old('notes') }}</textarea>
                        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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