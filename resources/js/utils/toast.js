/**
 * Lightweight Toast Notification System
 * Usage: window.Toast.success('Berhasil!'), window.Toast.error('Gagal.'), window.Toast.info('Memuat...')
 */

(function () {
    const CONTAINER_ID = 'toast-container';
    let container = null;

    function getContainer() {
        if (!container) {
            container = document.getElementById(CONTAINER_ID);
        }
        if (!container) {
            container = document.createElement('div');
            container.id = CONTAINER_ID;
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none';
            document.body.appendChild(container);
        }
        return container;
    }

    function createToast(message, type) {
        const c = getContainer();
        const id = Date.now() + Math.random();

        const bgColors = {
            success: 'bg-emerald-500',
            error: 'bg-rose-500',
            info: 'bg-indigo-500',
            warning: 'bg-amber-500',
        };

        const icons = {
            success: `<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`,
            error: `<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`,
            info: `<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
            warning: `<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
        };

        const toast = document.createElement('div');
        toast.id = `toast-${id}`;
        toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${bgColors[type] || bgColors.info} transition-all duration-300 transform translate-x-full opacity-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <span class="flex-shrink-0">${icons[type] || icons.info}</span>
            <span class="flex-1">${escapeHtml(message)}</span>
            <button onclick="window.Toast.dismiss('${id}')" class="flex-shrink-0 ml-1 hover:opacity-70 transition-opacity" aria-label="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        `;

        c.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        });

        // Auto dismiss after 4s
        setTimeout(() => {
            dismiss(id);
        }, 4000);

        return id;
    }

    function dismiss(id) {
        const toast = document.getElementById(`toast-${id}`);
        if (!toast) return Promise.resolve();

        toast.classList.add('translate-x-full', 'opacity-0');
        return new Promise(resolve => {
            setTimeout(() => {
                toast.remove();
                resolve();
            }, 300);
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    window.Toast = {
        success: (msg) => createToast(msg, 'success'),
        error: (msg) => createToast(msg, 'error'),
        info: (msg) => createToast(msg, 'info'),
        warning: (msg) => createToast(msg, 'warning'),
        dismiss,
    };
})();
