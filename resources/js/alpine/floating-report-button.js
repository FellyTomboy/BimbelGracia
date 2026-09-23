export function floatingReportButton() {
    const storedX = localStorage.getItem('waBtnX');
    const storedY = localStorage.getItem('waBtnY');

    const defaultX = window.innerWidth - 72;
    const defaultY = window.innerHeight - 96;

    return {
        x: storedX !== null ? parseInt(storedX, 10) : defaultX,
        y: storedY !== null ? parseInt(storedY, 10) : defaultY,
        dragging: false,
        offsetX: 0,
        offsetY: 0,

        get style() {
            return `top:${this.y}px!important;left:${this.x}px!important;`;
        },

        startDrag(e) {
            if (e.type === 'mousedown' && e.button !== 0) return;
            this.dragging = true;
            this.offsetX = (e.type === 'touchstart' ? e.touches[0].clientX : e.clientX) - this.x;
            this.offsetY = (e.type === 'touchstart' ? e.touches[0].clientY : e.clientY) - this.y;
        },

        onDrag(e) {
            if (!this.dragging) return;
            const clientX = e.type === 'touchmove' ? e.touches[0].clientX : e.clientX;
            const clientY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;

            this.x = Math.max(0, Math.min(clientX - this.offsetX, window.innerWidth - 56));
            this.y = Math.max(0, Math.min(clientY - this.offsetY, window.innerHeight - 56));
        },

        endDrag() {
            if (!this.dragging) return;
            this.dragging = false;
            localStorage.setItem('waBtnX', this.x);
            localStorage.setItem('waBtnY', this.y);
        }
    };
}
