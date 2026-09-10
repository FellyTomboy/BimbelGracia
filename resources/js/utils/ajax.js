/**
 * AJAX utility wrapper around axios
 * Auto-attaches CSRF, handles 419 refresh, 401 redirect, 422 errors
 */
(function () {
    const _csrfRetrying = { value: false };

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content;
    }

    function getXsrfToken() {
        return decodeURIComponent(
            document.cookie.split('; ')
                .find(row => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] || ''
        );
    }

    async function refreshCsrf() {
        await fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
    }

    function buildConfig(extra = {}) {
        const token = getCsrfToken();
        const config = {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token, 'X-XSRF-TOKEN': getXsrfToken() } : {}),
                ...extra.headers,
            },
            ...extra,
        };
        delete config.headers?.['Content-Type']; // let browser set it for FormData
        return config;
    }

    async function request(method, url, data, extra = {}) {
        const config = buildConfig(extra);

        try {
            const response = await window.axios[method](url, data, config);
            return response;
        } catch (error) {
            // 419: CSRF token mismatch — refresh cookie and retry once
            if (error.response?.status === 419 && !_csrfRetrying.value) {
                _csrfRetrying.value = true;
                try {
                    await refreshCsrf();
                    const newToken = getCsrfToken();
                    if (newToken) {
                        config.headers['X-CSRF-TOKEN'] = newToken;
                        config.headers['X-XSRF-TOKEN'] = getXsrfToken();
                    }
                    return await window.axios[method](url, data, config);
                } finally {
                    _csrfRetrying.value = false;
                }
            }

            // 401: Unauthorized — redirect to login
            if (error.response?.status === 401) {
                window.location.href = '/login';
                return Promise.reject(error);
            }

            // Network error
            if (!error.response) {
                window.Toast?.error('Koneksi gagal. Periksa jaringan Anda.');
                return Promise.reject(error);
            }

            // 422: Validation errors — return parsed errors
            if (error.response?.status === 422) {
                return Promise.reject(error);
            }

            // Other errors — show generic message
            const message = error.response?.data?.message || error.response?.data?.error || 'Terjadi kesalahan.';
            if (error.response?.status !== 422) {
                window.Toast?.error(message);
            }
            return Promise.reject(error);
        }
    }

    window.Ajax = {
        get: (url, params, config = {}) => request('get', url, { params }, config),
        post: (url, data, config = {}) => request('post', url, data, config),
        put: (url, data, config = {}) => request('put', url, data, config),
        patch: (url, data, config = {}) => request('patch', url, data, config),
        delete: (url, data, config = {}) => request('delete', url, data, config),
    };
})();
