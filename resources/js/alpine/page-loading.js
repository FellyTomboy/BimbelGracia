/**
 * Page Loading Overlay Controller
 *
 * Wires the global #page-overlay in layouts/app.blade.php to show
 * during page navigations and AJAX requests.
 *
 * Navigation flow (sessionStorage only):
 *   1. User clicks a link → overlay shown on CURRENT page, sessionStorage flag set
 *   2. Browser navigates naturally to new page
 *   3. New page loads with #page-overlay (show: false = hidden)
 *   4. Overlay from previous page disappears — simple as that
 *
 * AJAX flow:
 *   1. window.Ajax call → overlay shown via interceptor
 *   2. Response arrives → overlay hidden via finally()
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
            // If we arrived via a navigation click: the overlay was ALREADY showing
            // on the PREVIOUS page. This new page's #page-overlay starts with
            // show: false, so the overlay is already hidden.
            sessionStorage.removeItem('bg-page-loading');

            // Navigation click interceptor
            this._setupNavigationClick();

            // AJAX interceptor
            this._wrapAjax();

            // Custom event
            this._setupCustomEventListener();
        },

        // ── Navigation Click Interceptor ────────────────────────────────────────

        _setupNavigationClick() {
            document.addEventListener('click', (e) => {
                const a = e.target.closest('a[href]');
                if (!a) return;
                if (!this._shouldInterceptLink(a)) return;
                if (a.target === '_blank') return;

                // Show overlay immediately and set cross-page flag
                this.requestStart();
                sessionStorage.setItem('bg-page-loading', '1');

                // Let browser navigate naturally — no preventDefault()
            });
        },

        _shouldInterceptLink(a) {
            const href = a.getAttribute('href') || '';
            if (!href || href.startsWith('#')) return false;
            if (href.startsWith('javascript:')) return false;
            if (href.startsWith('http://') || href.startsWith('https://')) {
                try {
                    if (new URL(href).origin !== window.location.origin) return false;
                } catch {
                    return false;
                }
            }
            if (a.hasAttribute('onclick')) return false;
            // Exclude AJAX sidebar links — they handle their own navigation via fetch()
            if (a.classList.contains('ajax-sidebar-link')) return false;
            return true;
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

        // ── Custom Event ───────────────────────────────────────────────────────

        _setupCustomEventListener() {
            document.addEventListener('page-loading', (e) => {
                if (e.detail?.show) this.requestStart();
                else this.requestEnd();
            });
        },
    };
}
