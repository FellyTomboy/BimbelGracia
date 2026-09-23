import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    define: {
        __BUILD_ID__: JSON.stringify(process.env.BUILD_ID || Date.now().toString(36)),
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            injectRegister: 'auto',
            outDir: 'public',
            manifest: {
                name: 'BimbelGracia',
                short_name: 'BimbelGracia',
                description: 'Aplikasi Manajemen Bimbingan Belajar Gracia',
                lang: 'id',
                theme_color: '#4F46E5',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '/',
                scope: '/',
                icons: [
                    {
                        src: '/icons/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
            injectManifest: {
                globPatterns: [
                    'build/assets/**/*.{js,css}',
                    'build/manifest.webmanifest',
                    'icons/**/*.{png,jpg,svg}',
                    'offline.html',
                ],
            },
            devOptions: {
                enabled: true,
                type: 'module',
            },
        }),
    ],
});
