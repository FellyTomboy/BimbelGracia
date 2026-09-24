<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#4F46E5">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="BimbelGracia">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">
        <link rel="manifest" href="/manifest.json">

        <title>{{ config('app.name') }}@isset($title) - {{ $title }}@endisset</title>
        <link rel="icon" type="image/jpeg" href="{{ asset('storage/website/logo_bimbel.jpg') }}" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <script>
            window.toggleAddPresensiModal = function(show) {
                document.dispatchEvent(new CustomEvent('toggle-presensi-modal', { detail: { show: show } }));
            };
        </script>

        <!-- Page Overlay (loading state) -->
        <div id="page-overlay"
             x-data="pageLoading()"
             x-show="show"
             x-cloak
             class="fixed inset-0 bg-black/30 z-[200] flex items-center justify-center transition-opacity">
            <div class="bg-white rounded-2xl p-6 flex items-center gap-3 shadow-xl">
                <svg class="animate-spin w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm font-medium text-gray-700">Memuat...</span>
            </div>
        </div>

        @stack('scripts')
        <x-floating-report-button />
    </body>
</html>
