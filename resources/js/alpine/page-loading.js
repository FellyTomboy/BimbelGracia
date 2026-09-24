/**
 * Page Loading Overlay Controller
 *
 * Wires the global #page-overlay in layouts/app.blade.php to show
 * during AJAX requests and full-page navigations.
 *
 * Usage:
 *   - Automatically active via x-data="pageLoading()" on #page-overlay
 *   - Manual trigger: document.dispatchEvent(new CustomEvent('page-loading', {detail:{show:true}}))
 *   - Manual hide:   document.dispatchEvent(new CustomEvent('page-loading', {detail:{show:false}}))
 */

export function pageLoading() {
    return {
        show: false,
        requestCount: 0,

        requestStart() {
            this.requestCount++;
            this.show = true;
        },

        requestEnd() {
            this.requestCount = Math.max(0, this.requestCount - 1);
            if (this.requestCount === 0) {
                this.show = false;
            }
        },

        init() {
            this._wrapAjax();
            this._setupNavigationInterceptor();
            this._setupCustomEventListener();
        },

        // ── AJAX Interceptor ────────────────────────────────────────────────────

        _wrapAjax() {
            const methods = ['get', 'post', 'put', 'patch', 'delete'];
            methods.forEach((method) => {
                const orig = window.Ajax[method].bind(window.Ajax);
                window.Ajax[method] = (url, data, config) => {
                    this.requestStart();
                    return orig(url, data, config)
                        .finally(() => this.requestEnd());
                };
            });
        },

        // ── Navigation Interceptor ─────────────────────────────────────────────

        _setupNavigationInterceptor() {
            // Intercept link clicks for full-page navigation
            document.addEventListener('click', (e) => {
                const a = e.target.closest('a[href]');
                if (!a) return;
                if (!this._shouldInterceptLink(a)) return;
                if (a.target === '_blank') return;
                e.preventDefault();
                this.requestStart();
                window.location.href = a.href;
            });

            // Intercept form submits (non-AJAX forms, e.g. logout)
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!this._shouldInterceptForm(form)) return;
                e.preventDefault();
                this.requestStart();
                form.submit();
            });
        },

        _shouldInterceptLink(a) {
            const href = a.getAttribute('href') || '';
            // Skip: anchors, javascript:, external URLs, existing onclick/remote handlers
            if (!href || href.startsWith('#')) return false;
            if (href.startsWith('javascript:')) return false;
            if (href.startsWith('http://') || href.startsWith('https://')) {
                try {
                    const url = new URL(href);
                    if (url.origin !== window.location.origin) return false;
                } catch {
                    return false;
                }
            }
            if (a.hasAttribute('onclick')) return false;
            if (a.dataset.remote !== undefined) return false; // Rails UJS
            if (a.dataset.method !== undefined) return false;  // Laravel method spoofing
            return true;
        },

        _shouldInterceptForm(form) {
            // Only POST/PUT/PATCH/DELETE forms
            const method = (form.method || 'get').toLowerCase();
            if (method === 'get') return false;
            // Skip if already wired for AJAX (e.g. Alpine remote submit)
            if (form.dataset.ajax !== undefined) return false;
            return true;
        },

        // ── Custom Event ───────────────────────────────────────────────────────

        _setupCustomEventListener() {
            document.addEventListener('page-loading', (e) => {
                if (e.detail?.show) {
                    this.requestStart();
                } else {
                    this.requestEnd();
                }
            });
        },
    };
}
