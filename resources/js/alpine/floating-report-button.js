/**
 * Draggable floating WhatsApp report button.
 * Position persists via localStorage so the user can move it
 * out of the way of other UI elements.
 */
export function floatingReportButton() {
    return {
        x: null,
        y: null,
        dragging: false,
        offsetX: 0,
        offsetY: 0,

        init() {
            const storedX = localStorage.getItem('waBtnX');
            const storedY = localStorage.getItem('waBtnY');

            if (storedX !== null && storedY !== null) {
                this.x = parseInt(storedX, 10);
                this.y = parseInt(storedY, 10);
            } else {
                // Default: bottom-right corner
                this.x = window.innerWidth - 72;
                this.y = window.innerHeight - 96;
            }
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
