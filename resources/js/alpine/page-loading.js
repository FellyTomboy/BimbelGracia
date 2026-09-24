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

        show() {
            this.requestStart();
        },

        init() {
            this._wrapAjax();
            // Navigation interceptors REMOVED — browser native nav handles page transitions.
            // The overlay is shown for AJAX requests (via _wrapAjax) and can be
            // triggered manually via document.dispatchEvent('page-loading', {detail:{show:true}})
            // or by calling Alpine's component method: $data in the page-overlay scope.
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
