import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { CacheFirst, StaleWhileRevalidate } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';

self.skipWaiting();
clientsClaim();

precacheAndRoute(self.__WB_MANIFEST);
cleanupOutdatedCaches();

// Navigation requests are NOT intercepted — Laravel handles all routes via index.php.
// Letting the network handle navigations means the browser shows its own offline UI
// when truly offline, and online requests pass through normally (login, redirects, etc.).

registerRoute(
    ({ url }) => url.origin === 'https://fonts.bunny.net',
    new CacheFirst({
        cacheName: 'bunny-fonts',
        plugins: [
            new ExpirationPlugin({ maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 365 }),
            new CacheableResponsePlugin({ statuses: [0, 200] }),
        ],
    })
);

registerRoute(
    ({ url }) => url.origin === 'https://fonts.googleapis.com',
    new StaleWhileRevalidate({ cacheName: 'google-fonts-stylesheets' })
);

registerRoute(
    ({ request, url }) =>
        request.destination === 'image' && url.pathname.startsWith('/icons/'),
    new CacheFirst({
        cacheName: 'app-icons',
        plugins: [
            new ExpirationPlugin({ maxEntries: 20, maxAgeSeconds: 60 * 60 * 24 * 30 }),
        ],
    })
);
