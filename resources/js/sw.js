/// <reference lib="webworker" />

import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { CacheFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { NetworkOnly } from 'workbox-strategies';

// Precache manifest — injected by vite-plugin-pwa
precacheAndRoute(self.__WB_MANIFEST);

// Cleanup old caches from previous SW versions
cleanupOutdatedCaches();

// ── Runtime caching strategies ────────────────────────────────────────────────

// CacheFirst: fonts
registerRoute(
    ({ url }) => url.origin === 'https://fonts.bunny.net',
    new CacheFirst({
        cacheName: 'bunny-fonts',
        plugins: [
            new ExpirationPlugin({ maxEntries: 20, maxAgeSeconds: 60 * 60 * 24 * 365 }),
        ],
    })
);

// CacheFirst: Google Fonts stylesheets
registerRoute(
    ({ url }) => url.origin === 'https://fonts.googleapis.com',
    new CacheFirst({
        cacheName: 'google-fonts-stylesheets',
        plugins: [
            new ExpirationPlugin({ maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 365 }),
        ],
    })
);

// CacheFirst: Google Fonts static files
registerRoute(
    ({ url }) => url.origin === 'https://fonts.gstatic.com',
    new CacheFirst({
        cacheName: 'google-fonts-files',
        plugins: [
            new ExpirationPlugin({ maxEntries: 30, maxAgeSeconds: 60 * 60 * 24 * 365 }),
        ],
    })
);

// CacheFirst: static assets (js, css, images in build/)
registerRoute(
    ({ request }) =>
        request.destination === 'script' ||
        request.destination === 'style' ||
        request.destination === 'image',
    new CacheFirst({
        cacheName: 'static-assets',
        plugins: [
            new ExpirationPlugin({ maxEntries: 60, maxAgeSeconds: 60 * 60 * 24 * 7 }),
        ],
    })
);

// NetworkOnly: POST/PUT/PATCH/DELETE (never cache mutations)
registerRoute(
    ({ request }) =>
        request.method === 'POST' ||
        request.method === 'PUT' ||
        request.method === 'PATCH' ||
        request.method === 'DELETE',
    new NetworkOnly()
);

// ── Offline fallback ──────────────────────────────────────────────────────────
