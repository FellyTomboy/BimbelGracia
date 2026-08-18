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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
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
            padding: 0.75rem 1.25rem;
            background: #0f172a;
            border-bottom: 1px solid #334155;
            flex-shrink: 0;
        }
        .topbar h1 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #f1f5f9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 60%;
        }
        .topbar .actions { display: flex; align-items: center; gap: 0.75rem; }
        .topbar a, .topbar button {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.8rem;
            background: transparent;
            border: 1px solid #334155;
            padding: 0.35rem 0.75rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.15s;
        }
        .topbar a:hover, .topbar button:hover { color: #fff; border-color: #475569; }
        .viewer-wrap {
            flex: 1;
            position: relative;
            overflow: hidden;
        }
        .viewer-frame {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }
        /* Watermark overlay */
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
            font-size: 1.4rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.12);
            transform: rotate(-30deg);
            white-space: nowrap;
            letter-spacing: 0.1em;
            user-select: none;
            -webkit-user-select: none;
            text-shadow: 0 0 8px rgba(0,0,0,0.3);
        }
        .notice {
            padding: 0.5rem 1.25rem;
            background: #0f172a;
            border-top: 1px solid #334155;
            font-size: 0.7rem;
            color: #64748b;
            text-align: center;
            flex-shrink: 0;
        }
        /* Print protection: hide ALL content when printing */
        @media print {
            body { display: none !important; }
            html, body { background: #fff; }
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

    <div class="viewer-wrap">
        <iframe
            class="viewer-frame"
            src="{{ $viewUrl }}"
            title="{{ $document->title }}"
            sandbox="allow-same-origin allow-scripts allow-forms"
        ></iframe>
        <div class="watermark">
            <span>{{ $watermarkText }}</span>
        </div>
    </div>

    <div class="notice">
        Dokumen ini dilindungi. Pengunduhan, pencetakan, dan penyebarluasan tanpa izin dilarang.
    </div>

    <script>
        // Prevent keyboard shortcuts for print/save
        document.addEventListener('keydown', function (e) {
            const k = (e.key || '').toLowerCase();
            const ctrl = e.ctrlKey || e.metaKey;
            if (ctrl && ['p', 's', 'u', 'c', 'x', 'a'].includes(k)) {
                e.preventDefault();
                return false;
            }
            if (e.key === 'F12' || e.key === 'PrintScreen') {
                e.preventDefault();
                return false;
            }
        });

        // Prevent context menu
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            return false;
        });

        // Prevent drag of the iframe content
        document.addEventListener('dragstart', function (e) {
            e.preventDefault();
            return false;
        });

        // Prevent copy
        document.addEventListener('copy', function (e) {
            e.preventDefault();
            return false;
        });

        // Prevent selection
        document.addEventListener('selectstart', function (e) {
            e.preventDefault();
            return false;
        });

        // Block print via beforeprint
        window.addEventListener('beforeprint', function (e) {
            e.preventDefault();
            alert('Pencetakan dokumen tidak diizinkan.');
        });

        // Block Ctrl+P at capture phase (more reliable)
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                e.stopPropagation();
                alert('Pencetakan dokumen tidak diizinkan.');
                return false;
            }
        }, true);

        // Block Ctrl+S (save page)
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);
    </script>
</body>
</html>