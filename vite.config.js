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
            outDir: 'public',
            // Set manifest: false to prevent VitePWA from auto-generating
            // manifest.webmanifest and adding it to precache (which caused the
            // duplicate that broke SW install). We write public/manifest.json
            // manually and reference it via <link> in Blade.
            manifest: false,
            injectManifest: {
                globPatterns: [
                    'build/assets/**/*.{js,css}',
                    'icons/*.png',
                    'offline.html',
                    'manifest.json',
                ],
            },
            devOptions: {
                enabled: true,
                type: 'module',
            },
        }),
    ],
});
