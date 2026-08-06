<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Formulir Ditutup - Bimbel Gracia</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('logo_bimbel.jpg') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 50%, #fef2f2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="py-12 px-4 flex items-center justify-center min-h-screen">
    <div class="max-w-lg mx-auto text-center">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 md:p-12">
            <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-3">Formulir Ditutup</h1>
            <p class="text-gray-600 mb-6">
                Maaf, formulir pendaftaran ini sudah tidak aktif. Data pendaftar sudah diproses oleh admin.
            </p>
            <p class="text-sm text-gray-400">Bimbel Gracia - Bimbingan Belajar Privat & Kelas</p>
        </div>
    </div>
</body>
</html>