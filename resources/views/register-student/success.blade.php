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
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #f0fdf4 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="py-12 px-4 flex items-center justify-center min-h-screen">
    <div class="max-w-lg mx-auto text-center">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 md:p-12">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-3">Pendaftaran Berhasil!</h1>
            <p class="text-gray-600 mb-6">
                Terima kasih telah mendaftar di Bimbel Gracia. Data kamu akan segera diverifikasi oleh admin.
            </p>
            <div class="bg-emerald-50 rounded-xl p-4 text-sm text-emerald-700 mb-6">
                <p class="font-medium">Apa yang akan terjadi selanjutnya?</p>
                <ul class="mt-2 space-y-1 text-emerald-600">
                    <li>✓ Admin akan memeriksa data pendaftaran</li>
                    <li>✓ Kami akan menghubungi via WhatsApp untuk informasi lebih lanjut</li>
                    <li>✓ Diskusi program belajar yang sesuai</li>
                </ul>
            </div>
            <p class="text-sm text-gray-400">Bimbel Gracia - Bimbingan Belajar Privat & Kelas</p>
        </div>
    </div>
</body>
</html>