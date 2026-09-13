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
        .topbar button:disabled { opacity: 0.35; cursor: default; }

        .pdf-toolbar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
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
        .pdf-toolbar button:disabled { opacity: 0.35; cursor: default; }
        .pdf-toolbar span {
            font-size: 0.75rem;
            color: #64748b;
            white-space: nowrap;
        }
        .pdf-toolbar input[type="number"] {
            width: 44px;
            text-align: center;
            background: #0f172a;
            border: 1px solid #334155;
            color: #e2e8f0;
            border-radius: 3px;
            padding: 1px 4px;
            font-size: 0.75rem;
        }

        /* Single-page viewer: centered, scrollable vertically per page */
        .viewer-wrap {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            background: #374151;
        }
        .viewer-wrap::-webkit-scrollbar { width: 8px; }
        .viewer-wrap::-webkit-scrollbar-track { background: #1e293b; }
        .viewer-wrap::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        .viewer-wrap::-webkit-scrollbar-thumb:hover { background: #64748b; }

        #page-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1rem 3rem;
        }

        /* Single page: watermark overlay centered on canvas */
        .canvas-page {
            position: relative;
            box-shadow: 0 4px 32px rgba(0,0,0,0.5);
            background: white;
            line-height: 0;
        }
        .canvas-page canvas {
            display: block;
        }
        .page-num-label {
            text-align: center;
            font-size: 0.72rem;
            color: #64748b;
            margin-top: 0.5rem;
        }

        /* Diagonal watermark — centered, pointer-events:none */
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
        .watermark-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
            transform: rotate(-28deg);
            pointer-events: none;
            user-select: none;
            -webkit-user-select: none;
        }
        .watermark-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: rgba(180, 190, 200, 0.20);
            letter-spacing: 0.2em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .watermark-date {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(180, 190, 200, 0.16);
            letter-spacing: 0.15em;
            white-space: nowrap;
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

        #loading-msg, #error-msg {
            text-align: center;
            padding: 3rem 1rem;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        #error-msg { display: none; color: #f87171; }
        .spinner {
            display: inline-block;
            width: 22px; height: 22px;
            border: 3px solid #334155;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
            margin-right: 0.5rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media print { body { display: none !important; } }
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
        <button id="btn-prev" onclick="goToPage(currentPage - 1)">‹</button>
        <span>Hal <input type="number" id="page-num" value="1" min="1" onchange="goToPage(this.value)"> / <span id="page-count">–</span></span>
        <button id="btn-next" onclick="goToPage(currentPage + 1)">›</button>
        <span style="margin-left:0.4rem;">Zoom</span>
        <button onclick="applyZoom(scale - 0.25)" title="Perkecil">−</button>
        <button onclick="applyZoom(scale + 0.25)" title="Perbesar">+</button>
    </div>

    <div class="viewer-wrap" id="viewer-wrap">
        <div id="loading-msg"><div class="spinner"></div>Memuat dokumen…</div>
        <div id="error-msg"></div>
        <div id="page-container"></div>
    </div>

    <div class="notice">
        Dokumen ini dilindungi. Pengunduhan, pencetakan, dan penyebarluasan tanpa izin dilarang.
    </div>

    <script>
        // ── Config ──────────────────────────────────────────────────────
        const DOC_URL = @json($viewUrl);
        const WATERMARK_NAME = @json(explode(' — ', $watermarkText)[0]);
        const WATERMARK_DATE = @json($watermarkText);
        const MAX_SCALE = 3.0;
        const MIN_SCALE = 0.25;
        const DEFAULT_SCALE = 1.5;

        let pdfDoc = null;
        let currentPage = 1;
        let totalPages = 0;
        let scale = DEFAULT_SCALE;

        // ── Page rendering ───────────────────────────────────────────────
        function renderPage(pageNum) {
            if (!pdfDoc) return;
            if (pageNum < 1 || pageNum > totalPages) return;

            // Clear container — single-page mode: only current page exists
            const container = document.getElementById('page-container');
            container.innerHTML = '';

            pdfDoc.getPage(pageNum).then(page => {
                const viewport = page.getViewport({ scale });

                const outer = document.createElement('div');
                outer.style.textAlign = 'center';

                const canvas = document.createElement('canvas');
                canvas.width  = viewport.width;
                canvas.height = viewport.height;
                const ctx = canvas.getContext('2d');

                const pageWrap = document.createElement('div');
                pageWrap.className = 'canvas-page';

                const wm = document.createElement('div');
                wm.className = 'watermark';
                wm.innerHTML =
                    '<div class="watermark-inner">' +
                        '<div class="watermark-name">' + WATERMARK_NAME + '</div>' +
                        '<div class="watermark-date">' + WATERMARK_DATE + '</div>' +
                    '</div>';
                pageWrap.appendChild(canvas);
                pageWrap.appendChild(wm);
                outer.appendChild(pageWrap);

                const label = document.createElement('div');
                label.className = 'page-num-label';
                label.textContent = 'Halaman ' + pageNum + ' dari ' + totalPages;
                outer.appendChild(label);

                container.appendChild(outer);

                page.render({ canvasContext: ctx, viewport });
            }).catch(err => {
                console.error('Render error page', pageNum, err);
            });
        }

        // ── Navigation ──────────────────────────────────────────────────
        function goToPage(val) {
            const n = parseInt(val);
            if (isNaN(n) || n < 1) {
                document.getElementById('page-num').value = currentPage;
                return;
            }
            currentPage = Math.min(n, totalPages);
            document.getElementById('page-num').value = currentPage;
            document.getElementById('btn-prev').disabled = currentPage <= 1;
            document.getElementById('btn-next').disabled = currentPage >= totalPages;
            document.getElementById('page-count').textContent = totalPages;
            renderPage(currentPage);
            document.getElementById('viewer-wrap').scrollTop = 0;
        }

        // ── Zoom ────────────────────────────────────────────────────────
        function applyZoom(newScale) {
            scale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, newScale));
            renderPage(currentPage);
        }

        // ── Keyboard shortcuts ──────────────────────────────────────────
        document.addEventListener('keydown', e => {
            const tag = e.target.tagName;
            if (tag === 'INPUT') return;
            if (e.key === 'ArrowLeft'  || e.key === 'PageUp')   { e.preventDefault(); goToPage(currentPage - 1); }
            if (e.key === 'ArrowRight' || e.key === 'PageDown') { e.preventDefault(); goToPage(currentPage + 1); }
        });

        // ── Protection ──────────────────────────────────────────────────
        document.addEventListener('keydown', function (e) {
            const k = (e.key || '').toLowerCase();
            const ctrl = e.ctrlKey || e.metaKey;
            if (ctrl && ['p','s','u','c','x','a'].includes(k)) { e.preventDefault(); return false; }
            if (e.key === 'F12' || e.key === 'PrintScreen') { e.preventDefault(); return false; }
        });
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('copy', e => e.preventDefault());
        window.addEventListener('beforeprint', e => { e.preventDefault(); alert('Pencetakan dokumen tidak diizinkan.'); });
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault(); e.stopPropagation();
                alert('Pencetakan dokumen tidak diizinkan.'); return false;
            }
        }, true);

        // ── Load PDF ────────────────────────────────────────────────────
        pdfjsLib.getDocument(DOC_URL).promise.then(doc => {
            pdfDoc = doc;
            totalPages = doc.numPages;
            document.getElementById('loading-msg').style.display = 'none';
            document.getElementById('page-count').textContent = totalPages;
            goToPage(1);
        }).catch(err => {
            document.getElementById('loading-msg').style.display = 'none';
            document.getElementById('error-msg').style.display = 'block';
            document.getElementById('error-msg').textContent =
                'Gagal memuat dokumen. Pastikan dokumen valid dan Anda memiliki akses.';
            console.error('PDF load error:', err);
        });
    </script>
</body>
</html>
