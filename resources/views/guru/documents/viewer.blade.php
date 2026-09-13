<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ $document->title }} - Bimbel Gracia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf_viewer.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #1e293b;
            color: #e2e8f0;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 1.25rem;
            background: #0f172a;
            border-bottom: 1px solid #334155;
            flex-shrink: 0;
        }
        .topbar h1 {
            font-size: 0.9rem;
            font-weight: 600;
            color: #f1f5f9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 55%;
        }
        .topbar .actions { display: flex; align-items: center; gap: 0.75rem; }
        .topbar button {
            color: #94a3b8;
            font-size: 0.8rem;
            background: transparent;
            border: 1px solid #334155;
            padding: 0.3rem 0.75rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.15s;
        }
        .topbar button:hover { color: #fff; border-color: #475569; }
        .topbar button:disabled { opacity: 0.4; cursor: default; }

        .pdf-toolbar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.4rem 1.25rem;
            background: #1e293b;
            border-bottom: 1px solid #334155;
            flex-shrink: 0;
        }
        .pdf-toolbar button {
            color: #94a3b8;
            font-size: 0.8rem;
            background: transparent;
            border: 1px solid #334155;
            padding: 0.25rem 0.6rem;
            border-radius: 0.3rem;
            cursor: pointer;
            transition: all 0.15s;
            min-width: 32px;
        }
        .pdf-toolbar button:hover:not(:disabled) { color: #fff; border-color: #475569; }
        .pdf-toolbar button:disabled { opacity: 0.4; cursor: default; }
        .pdf-toolbar span {
            font-size: 0.75rem;
            color: #64748b;
            white-space: nowrap;
        }

        .viewer-wrap {
            flex: 1;
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
            background: #374151;
        }
        .viewer-wrap::-webkit-scrollbar { width: 8px; }
        .viewer-wrap::-webkit-scrollbar-track { background: #1e293b; }
        .viewer-wrap::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        .viewer-wrap::-webkit-scrollbar-thumb:hover { background: #64748b; }

        #pdf-canvas-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem 1rem 2rem;
            position: relative;
        }
        .canvas-page {
            position: relative;
            box-shadow: 0 4px 24px rgba(0,0,0,0.4);
            margin-bottom: 1.5rem;
            background: white;
            line-height: 0;
        }
        .canvas-page canvas {
            display: block;
        }
        .canvas-page .page-num {
            position: absolute;
            bottom: -1.2rem;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.7rem;
            color: #64748b;
            white-space: nowrap;
        }

        /* Watermark overlay — sits on top of canvas, pointer-events none */
        .watermark {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .watermark span {
            font-size: 1.6rem;
            font-weight: 700;
            color: rgba(200, 210, 220, 0.10);
            transform: rotate(-30deg);
            white-space: nowrap;
            letter-spacing: 0.15em;
            user-select: none;
            -webkit-user-select: none;
            text-shadow: 0 0 12px rgba(0,0,0,0.2);
        }

        .notice {
            padding: 0.5rem 1.25rem;
            background: #0f172a;
            border-top: 1px solid #334155;
            font-size: 0.68rem;
            color: #64748b;
            text-align: center;
            flex-shrink: 0;
        }

        /* Loading / error states */
        #loading-msg, #error-msg {
            text-align: center;
            padding: 3rem 1rem;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        #error-msg { display: none; color: #f87171; }
        #loading-spinner {
            display: inline-block;
            width: 24px; height: 24px;
            border: 3px solid #334155;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
            margin-right: 0.5rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Print protection */
        @media print {
            body { display: none !important; }
        }
    </style>
</head>
<body oncontextmenu="return false;">

    <div class="topbar">
        <h1>{{ $document->title }}</h1>
        <div class="actions">
            <button type="button" onclick="window.close()">Tutup</button>
        </div>
    </div>

    <div class="pdf-toolbar">
        <button id="btn-prev" onclick="changePage(-1)" disabled>‹ Prev</button>
        <span>Hal <input type="number" id="page-num" value="1" min="1" style="width:44px;text-align:center;background:#0f172a;border:1px solid #334155;color:#e2e8f0;border-radius:3px;padding:1px 4px;font-size:0.75rem;" onchange="goToPage(this.value)"> / <span id="page-count">–</span></span>
        <button id="btn-next" onclick="changePage(1)" disabled>Next ›</button>
        <span style="margin-left:0.5rem;">zoom</span>
        <button onclick="changeZoom(-0.25)" title="Zoom Out">−</button>
        <button onclick="changeZoom(0.25)" title="Zoom In">+</button>
    </div>

    <div class="viewer-wrap" id="viewer-wrap">
        <div id="loading-msg">
            <div id="loading-spinner"></div>Memuat dokumen…
        </div>
        <div id="error-msg"></div>
        <div id="pdf-canvas-wrap"></div>
    </div>

    <div class="notice">
        Dokumen ini dilindungi. Pengunduhan, pencetakan, dan penyebarluasan tanpa izin dilarang.
    </div>

    <script>
        // ── PDF.js renderer ──────────────────────────────────────────────
        const DOC_URL = @json($viewUrl);
        const WATERMARK_TEXT = @json($watermarkText);
        const MAX_SCALE = 3.0;
        const MIN_SCALE = 0.25;
        let pdfDoc = null;
        let currentPage = 1;
        let totalPages = 0;
        let scale = 1.5;
        const rendered = {};

        function renderPage(pageNum) {
            if (rendered[pageNum] && rendered[pageNum].scale === scale) return;
            pdfDoc.getPage(pageNum).then(page => {
                const viewport = page.getViewport({ scale });
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width  = viewport.width;
                canvas.height = viewport.height;

                const wrap = document.createElement('div');
                wrap.className = 'canvas-page';
                wrap.id = 'page-wrap-' + pageNum;
                wrap.appendChild(canvas);

                // Watermark overlay per page
                const wm = document.createElement('div');
                wm.className = 'watermark';
                wm.innerHTML = '<span>' + WATERMARK_TEXT + '</span>';
                wrap.appendChild(wm);

                const num = document.createElement('div');
                num.className = 'page-num';
                num.textContent = pageNum;
                wrap.appendChild(num);

                const placeholder = document.getElementById('page-wrap-' + pageNum);
                if (placeholder) {
                    placeholder.replaceWith(wrap);
                } else {
                    document.getElementById('pdf-canvas-wrap').appendChild(wrap);
                }

                page.render({ canvasContext: ctx, viewport }).promise.then(() => {
                    rendered[pageNum] = { scale };
                });
            });
        }

        function rebuildVisiblePages() {
            const wraps = document.querySelectorAll('.canvas-page');
            wraps.forEach(w => {
                const num = parseInt(w.id.replace('page-wrap-', ''));
                if (num >= currentPage - 1 && num <= currentPage + 3) {
                    renderPage(num);
                }
            });
        }

        function changePage(delta) {
            const next = currentPage + delta;
            if (next < 1 || next > totalPages) return;
            currentPage = next;
            updateToolbar();
            renderPage(currentPage);
            rebuildVisiblePages();
            document.getElementById('page-num').value = currentPage;
            document.getElementById('viewer-wrap').scrollTop = 0;
        }

        function goToPage(val) {
            const n = parseInt(val);
            if (isNaN(n) || n < 1) { document.getElementById('page-num').value = currentPage; return; }
            currentPage = Math.min(n, totalPages);
            updateToolbar();
            renderPage(currentPage);
            rebuildVisiblePages();
            document.getElementById('page-num').value = currentPage;
            document.getElementById('viewer-wrap').scrollTop = 0;
        }

        function changeZoom(delta) {
            scale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, scale + delta));
            // Clear rendered cache so pages re-render at new scale
            for (const k in rendered) delete rendered[k];
            document.getElementById('pdf-canvas-wrap').innerHTML = '';
            renderPage(currentPage);
        }

        function updateToolbar() {
            document.getElementById('btn-prev').disabled = currentPage <= 1;
            document.getElementById('btn-next').disabled = currentPage >= totalPages;
            document.getElementById('page-count').textContent = totalPages;
        }

        // ── Load PDF ────────────────────────────────────────────────────
        pdfjsLib.getDocument(DOC_URL).promise
            .then(doc => {
                pdfDoc = doc;
                totalPages = doc.numPages;
                document.getElementById('loading-msg').style.display = 'none';
                updateToolbar();
                renderPage(1);
            })
            .catch(err => {
                document.getElementById('loading-msg').style.display = 'none';
                document.getElementById('error-msg').style.display = 'block';
                document.getElementById('error-msg').textContent =
                    'Gagal memuat dokumen. Pastikan dokumen valid dan Anda memiliki akses.';
                console.error('PDF load error:', err);
            });

        // ── Keyboard navigation ────────────────────────────────────────
        document.addEventListener('keydown', e => {
            const tag = e.target.tagName;
            if (tag === 'INPUT') return;
            if (e.key === 'ArrowLeft' || e.key === 'PageUp')  { e.preventDefault(); changePage(-1); }
            if (e.key === 'ArrowRight' || e.key === 'PageDown') { e.preventDefault(); changePage(1); }
        });

        // ── Protection ─────────────────────────────────────────────────
        document.addEventListener('keydown', function (e) {
            const k = (e.key || '').toLowerCase();
            const ctrl = e.ctrlKey || e.metaKey;
            if (ctrl && ['p', 's', 'u', 'c', 'x', 'a'].includes(k)) { e.preventDefault(); return false; }
            if (e.key === 'F12' || e.key === 'PrintScreen') { e.preventDefault(); return false; }
        });
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('dragstart', e => e.preventDefault());
        document.addEventListener('copy', e => e.preventDefault());
        document.addEventListener('selectstart', e => e.preventDefault());
        window.addEventListener('beforeprint', e => { e.preventDefault(); alert('Pencetakan dokumen tidak diizinkan.'); });
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault(); e.stopPropagation(); alert('Pencetakan dokumen tidak diizinkan.'); return false;
            }
        }, true);
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); e.stopPropagation(); return false; }
        }, true);
    </script>
</body>
</html>
