import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';
import crypto from 'crypto';

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
            strategies: 'generateSW',
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
            build: {
                // Content-hash sw.js filename — regenerates every build,
                // forcing all clients to update their SW when a new deploy lands.
                filename: `sw.${crypto.randomUUID().split('-')[0]}.js`,
            },
            devOptions: {
                enabled: true,
            },
        }),
    ],
});
