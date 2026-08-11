<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pendaftaran Berhasil - Bimbel Gracia</title>
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
    <div class="max-w-lg mx-auto text-center">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Pendaftaran Berhasil!</h1>
            <p class="text-gray-600 mb-6">Terima kasih telah mendaftar sebagai guru di Bimbel Gracia. Data Anda akan kami verifikasi dan kami akan menghubungi Anda melalui WhatsApp.</p>
            <a href="{{ url('/') }}" class="inline-flex items-center gap-1 px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>