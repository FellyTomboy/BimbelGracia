/**
 * Page Loading Overlay Controller
 *
 * Wires the global #page-overlay in layouts/app.blade.php to show
 * during page navigations and AJAX requests.
 *
 * Navigation flow (MutationObserver + sessionStorage):
 *   1. User clicks a link → overlay shown, sessionStorage flag set
 *   2. Browser navigates to new page
 *   3. New page's Alpine init() checks sessionStorage → knows it came from a nav
 *   4. MutationObserver watches <main> for new content appearing
 *   5. Once content renders → overlay hidden
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
            // 1. Detect if this page was reached via our navigation interceptor
            const wasNavigating = sessionStorage.getItem('bg-page-loading');
            sessionStorage.removeItem('bg-page-loading');

            if (wasNavigating) {
                // We arrived from a nav click. Show overlay, then hide once
                // MutationObserver detects that <main> has new content (page rendered).
                this.show = true;
                this._observePageContent();
            }

            // 2. Navigation click interceptor (no preventDefault — let browser navigate naturally)
            this._setupNavigationClick();

            // 3. AJAX interceptor
            this._wrapAjax();

            // 4. Custom event
            this._setupCustomEventListener();
        },

        // ── MutationObserver ────────────────────────────────────────────────────

        _observePageContent() {
            const main = document.querySelector('main');
            if (!main) return;

            const observer = new MutationObserver((mutations, obs) => {
                // Wait for new page content to appear in <main>
                const hasRealContent = mutations.some((m) =>
                    m.addedNodes.length > 0 &&
                    [...m.addedNodes].some(
                        (n) =>
                            n.nodeType === 1 &&
                            !n.matches('#page-overlay')
                    )
                );

                if (hasRealContent) {
                    obs.disconnect();
                    // Content is rendering — hide the overlay
                    this.show = false;
                }
            });

            observer.observe(main, { childList: true, subtree: false });
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

                // Let browser handle navigation naturally — NO preventDefault()
                // The new page's pageLoading.init() will handle hiding the overlay.
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
