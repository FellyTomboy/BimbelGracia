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
            workbox: {
                navigateFallback: '/offline.html',
                navigateFallbackDenylist: [
                    /^\/api\//,
                    /^\/admin\/_debugbar\//,
                    /^\/build\/assets\//,
                    /^\/sw\.js$/,
                    /^\/workbox-.*\.js$/,
                    /^\/manifest\.webmanifest$/,
                ],
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.bunny\.net\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'bunny-fonts',
                            expiration: { maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 365 },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    {
                        urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
                        handler: 'StaleWhileRevalidate',
                        options: { cacheName: 'google-fonts-stylesheets' },
                    },
                    {
                        urlPattern: /^\/icons\/.*\.(png|jpg|svg)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'app-icons',
                            expiration: { maxEntries: 20, maxAgeSeconds: 60 * 60 * 24 * 30 },
                        },
                    },
                ],
            },
            devOptions: {
                enabled: true,
            },
        }),
    ],
});
