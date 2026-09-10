<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Tidak Ditemukan - Bimbel Gracia</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-50 to-white flex items-center justify-center p-6">

    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 text-center">

            {{-- Icon --}}
            <div class="w-20 h-20 mx-auto mb-6 bg-red-50 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            {{-- Heading --}}
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                404 — Halaman Tidak Ditemukan
            </h1>
            <p class="text-gray-600 mb-6">
                Menemukan error? Hubungi Technical Support.
            </p>

            {{-- Description --}}
            <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left text-sm text-gray-600">
                <p>Halaman yang Anda cari tidak ditemukan. Mungkin sudah dipindahkan atau tidak lagi tersedia.</p>
            </div>

            {{-- WhatsApp Button --}}
            <a href="https://wa.me/6285706512155?text={{ urlencode("Masalah : Error 404 - Halaman Tidak Ditemukan\nDeskripsi: (jelaskan halaman/form apa yang Anda buka, tombol apa yang Anda klik)") }}"
               target="_blank"
               class="inline-flex items-center gap-3 bg-green-500 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-xl transition-colors w-full justify-center">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Hubungi Technical Support via WhatsApp
            </a>
        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-gray-400 mt-6">
            Bimbel Gracia — Enjoy Your Learning!
        </p>
    </div>

</body>
</html>
