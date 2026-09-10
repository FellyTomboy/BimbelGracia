import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { crudModal } from './alpine/crud-modal';
import { EnrollmentForm } from './alpine/enrollment-form';

import './utils/toast';
import './utils/ajax';

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.data('crudModal', crudModal);
window.EnrollmentForm = EnrollmentForm;

Alpine.start();

// ── PWA Service Worker Registration ──────────────────────────────────────────
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('[SW] Registered:', registration.scope);

                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('[SW] New version available. Refresh to update.');
                            }
                        });
                    }
                });
            })
            .catch((err) => {
                console.error('[SW] Registration failed:', err);
            });
    });
}
