<x-app-layout>
    <x-slot name="title">Detail Dokumen</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $document->title }}</h2>
            <a href="{{ route('guru.documents.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Kembali</a>
        </div>
    </x-slot>

    @php
        $isPdf = $document->file_type === 'application/pdf';
        $isImage = str_starts_with($document->file_type ?? '', 'image/');
        $streamUrl = route('guru.documents.stream', $document);
        $teacherName = $teacherName ?? 'Guru';
        $unlocked = session()->get('document_unlocked_' . $document->id, false);
        $needsPassword = $document->access_type === 'password' && !$unlocked;
    @endphp

<<<<<<< HEAD
    @if ($needsPassword)
        {{-- Password gate --}}
        <div class="py-8">
            <div class="max-w-md mx-auto sm:px-6 lg:px-8">
=======
                @if (!$unlocked)
                    {{-- Password form --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 max-w-md mx-auto">
                        <h3 class="font-semibold text-gray-900">Dokumen Terkunci</h3>
                        <p class="text-sm text-gray-500 mt-1">Masukkan password untuk mengakses dokumen ini.</p>

                        <form method="POST" action="{{ route('guru.documents.verify-password', $document) }}" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <input type="password" name="password" class="w-full border-gray-300 rounded-md" placeholder="Password" required />
                                @error('password')<p class="text-sm text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="w-full px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Buka Dokumen</button>
                        </form>
                    </div>
                @else
                    {{-- Document content --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-16 h-16 rounded-xl bg-indigo-50 flex items-center justify-center">
                                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 text-lg">{{ $document->title }}</h3>
                                @if ($document->description)
                                    <p class="text-sm text-gray-600 mt-1">{{ $document->description }}</p>
                                @endif
                                <div class="flex items-center gap-3 mt-3 text-sm text-gray-500">
                                    <span>{{ $document->file_name }}</span>
                                    <span>•</span>
                                    <span>{{ $document->formatted_size }}</span>
                                </div>
                                <div class="flex items-center gap-3 mt-4">
                                    <a href="{{ route('guru.documents.viewer', $document) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Buka Dokumen
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                {{-- teacher access - show directly --}}
>>>>>>> 1a30744 (feat: secure document storage and add download with access logging)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
<<<<<<< HEAD
                        <h3 class="font-semibold text-gray-900">Dokumen Terkunci</h3>
                        <p class="text-sm text-gray-500 mt-1">Masukkan password untuk mengakses dokumen ini.</p>
                    </div>

                    <form method="POST" action="{{ route('guru.documents.verify-password', $document) }}" class="mt-5 space-y-3">
                        @csrf
                        <div>
                            <input type="password" name="password" class="w-full border-gray-300 rounded-md" placeholder="Password" required />
                            @error('password')
                                <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="w-full px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Buka Dokumen</button>
                    </form>
                </div>
            </div>
        </div>
    @elseif ($isPdf)
        {{-- PDF Viewer: PDF.js without download/print --}}
        <div class="py-4 px-2">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden"
                     id="pdf-viewer-container"
                     data-stream-url="{{ $streamUrl }}"
                     data-teacher-name="{{ e($teacherName) }}">

                    {{-- Info bar --}}
                    <div class="px-4 py-2 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                        <div class="text-sm text-gray-500 truncate max-w-md">
                            <span class="font-medium text-gray-700">{{ $document->title }}</span>
                            <span class="mx-1">•</span>
                            <span>{{ $document->file_name }}</span>
                            <span class="mx-1">•</span>
                            <span>{{ $document->formatted_size }}</span>
                        </div>
                        <div class="text-xs text-gray-400 italic">
                            Akses oleh: {{ $teacherName }}
=======
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 text-lg">{{ $document->title }}</h3>
                            @if ($document->description)
                                <p class="text-sm text-gray-600 mt-1">{{ $document->description }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-3 text-sm text-gray-500">
                                <span>{{ $document->file_name }}</span>
                                <span>•</span>
                                <span>{{ $document->formatted_size }}</span>
                            </div>
                            <div class="flex items-center gap-3 mt-4">
                                <a href="{{ route('guru.documents.viewer', $document) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Buka Dokumen
                                </a>
                            </div>
>>>>>>> 1a30744 (feat: secure document storage and add download with access logging)
                        </div>
                    </div>

                    {{-- PDF.js canvas --}}
                    <div id="pdf-wrapper" class="relative bg-gray-200" style="height: calc(100vh - 280px); min-height: 500px; overflow: auto;"
                         oncontextmenu="return false"
                         onkeydown="handleDocShortcuts(event)">

                        {{-- Watermark overlay --}}
                        <div id="watermark-overlay" class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center overflow-hidden">
                            <div class="watermark-text text-center select-none"
                                 style="font-size: clamp(1.5rem, 4vw, 3rem); font-weight: 700; color: rgba(200,200,200,0.35); transform: rotate(-30deg); white-space: nowrap; font-family: sans-serif; letter-spacing: 0.05em; user-select: none;">
                                {{ e($teacherName) }} &nbsp;•&nbsp; BimbelGracia
                            </div>
                        </div>

                        {{-- PDF.js render target --}}
                        <div id="pdf-canvas-container" class="flex justify-center p-4 relative z-0">
                            <canvas id="pdf-canvas" class="shadow-lg"></canvas>
                        </div>

                        {{-- Loading / error states --}}
                        <div id="pdf-loading" class="absolute inset-0 flex items-center justify-center bg-gray-50">
                            <div class="text-center">
                                <div class="w-8 h-8 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                                <p class="text-sm text-gray-500">Memuat dokumen...</p>
                            </div>
                        </div>

                        <div id="pdf-error" class="absolute inset-0 flex items-center justify-center bg-gray-50 hidden">
                            <div class="text-center max-w-sm">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                                </svg>
                                <p class="text-sm text-gray-500">Tidak dapat memuat pratinjau dokumen.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Page navigation --}}
                    <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-center gap-4 bg-gray-50">
                        <button id="prev-page" class="px-3 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">← Sebelumnya</button>
                        <span id="page-info" class="text-sm text-gray-500">Halaman <span id="page-num">-</span> / <span id="page-count">-</span></span>
                        <button id="next-page" class="px-3 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">Selanjutnya →</button>
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Prevent text selection as additional deterrent */
            #pdf-wrapper { user-select: none; -webkit-user-select: none; }
            #pdf-canvas-container canvas { display: block; max-width: 100%; }
            /* Watermark layering */
            #watermark-overlay { pointer-events: none; }
        </style>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script>
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            (function() {
                const container = document.getElementById('pdf-viewer-container');
                const streamUrl = container.dataset.streamUrl;
                const teacherName = container.dataset.teacherName;

                let pdfDoc = null;
                let currentPage = 1;
                let totalPages = 0;
                let rendering = false;

                const canvas = document.getElementById('pdf-canvas');
                const ctx = canvas.getContext('2d');
                const loadingEl = document.getElementById('pdf-loading');
                const errorEl = document.getElementById('pdf-error');
                const pageNumEl = document.getElementById('page-num');
                const pageCountEl = document.getElementById('page-count');
                const prevBtn = document.getElementById('prev-page');
                const nextBtn = document.getElementById('next-page');
                const wrapper = document.getElementById('pdf-wrapper');
                const watermark = document.getElementById('watermark-overlay');

                // Disable PDF.js built-in download/print
                pdfjsLib.removePDFJSIntegration();

                function showLoading() { loadingEl.classList.remove('hidden'); errorEl.classList.add('hidden'); }
                function showError() { loadingEl.classList.add('hidden'); errorEl.classList.remove('hidden'); }
                function hideLoading() { loadingEl.classList.add('hidden'); }

                function renderPage(num) {
                    if (rendering) return;
                    rendering = true;

                    pdfDoc.getPage(num).then(function(page) {
                        const viewport = page.getViewport({ scale: 1.5 });
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;

                        const renderContext = {
                            canvasContext: ctx,
                            viewport: viewport,
                        };

                        page.render(renderContext).promise.then(function() {
                            rendering = false;
                            hideLoading();
                            // Re-draw watermark on top via canvas after render
                            drawCanvasWatermark(viewport);
                        }).catch(function() {
                            rendering = false;
                            showError();
                        });
                    }).catch(function() {
                        rendering = false;
                        showError();
                    });

                    pageNumEl.textContent = num;
                    prevBtn.disabled = num <= 1;
                    nextBtn.disabled = num >= totalPages;
                }

                // Draw watermark on canvas after PDF renders
                function drawCanvasWatermark(viewport) {
                    ctx.save();
                    ctx.font = 'bold 24px sans-serif';
                    ctx.fillStyle = 'rgba(200, 200, 200, 0.3)';
                    ctx.translate(canvas.width / 2, canvas.height / 2);
                    ctx.rotate(-Math.PI / 6);
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(teacherName + ' — BimbelGracia', 0, 0);
                    ctx.restore();
                }

                prevBtn.addEventListener('click', function() {
                    if (currentPage > 1) { currentPage--; renderPage(currentPage); }
                });
                nextBtn.addEventListener('click', function() {
                    if (currentPage < totalPages) { currentPage++; renderPage(currentPage); }
                });

                // Load PDF from authenticated stream endpoint
                showLoading();
                fetch(streamUrl)
                    .then(function(response) {
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        return response.arrayBuffer();
                    })
                    .then(function(data) {
                        return pdfjsLib.getDocument({ data: data }).promise;
                    })
                    .then(function(pdf) {
                        pdfDoc = pdf;
                        totalPages = pdf.numPages;
                        pageCountEl.textContent = totalPages;
                        renderPage(1);
                    })
                    .catch(function() {
                        showError();
                    });

                // Global shortcut blocker (Ctrl+S, Ctrl+P, Ctrl+A, F12, etc.)
                document.addEventListener('keydown', function(e) {
                    // Ctrl+S = Save → block
                    if (e.ctrlKey && e.key === 's') { e.preventDefault(); return false; }
                    // Ctrl+P = Print → block
                    if (e.ctrlKey && e.key === 'p') { e.preventDefault(); return false; }
                    // Ctrl+Shift+I / F12 = Dev tools → block
                    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) { e.preventDefault(); return false; }
                    // Ctrl+U = View source → block
                    if (e.ctrlKey && e.key === 'u') { e.preventDefault(); return false; }
                    // Ctrl+A = Select all (reduce in wrapper with user-select:none)
                    if (e.ctrlKey && e.key === 'a') { e.preventDefault(); return false; }
                    // Ctrl+C on canvas (only block if selection is the canvas)
                    if (e.ctrlKey && e.key === 'c') {
                        // Let browser handle copy of selected text, but prevent easy full doc copy
                    }
                    // Print screen (OS-level, cannot fully block)
                    if (e.key === 'PrintScreen') { e.preventDefault(); return false; }
                });

                // Block right-click on PDF wrapper
                wrapper.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    return false;
                });

                // Block drag of canvas
                canvas.addEventListener('dragstart', function(e) { e.preventDefault(); });

                // Block Ctrl+Shift+C (devtools)
                document.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.shiftKey && e.key === 'C') { e.preventDefault(); }
                    if (e.ctrlKey && e.shiftKey && e.key === 'J') { e.preventDefault(); }
                    // Ctrl+Shift+Delete
                    if (e.ctrlKey && e.shiftKey && e.key === 'Delete') { e.preventDefault(); }
                });

                // Block onbeforeprint (attempt to block print via JS)
                window.addEventListener('beforeprint', function(e) {
                    // This fires but we can't prevent the dialog - just log/alert
                    alert('Fungsi cetak tidak diizinkan untuk dokumen ini.');
                });

                // Block copy from canvas (context menu already blocked)
                // Some browsers allow canvas copy via edit menu
                if (document.addEventListener) {
                    document.addEventListener('copy', function(e) {
                        // Only block if the selection is within the PDF wrapper
                        if (document.getElementById('pdf-wrapper') &&
                            document.getElementById('pdf-wrapper').contains(document.activeElement)) {
                            e.stopPropagation();
                        }
                    }, true);
                }

            })();
        </script>
    @elseif ($isImage)
        {{-- Image viewer with watermark --}}
        <div class="py-4 px-2">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden"
                     id="image-viewer"
                     data-stream-url="{{ $streamUrl }}"
                     data-teacher-name="{{ e($teacherName) }}">

                    {{-- Info bar --}}
                    <div class="px-4 py-2 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                        <div class="text-sm text-gray-500">
                            <span class="font-medium text-gray-700">{{ $document->title }}</span>
                            <span class="mx-1">•</span>
                            <span>{{ $document->file_name }}</span>
                            <span class="mx-1">•</span>
                            <span>{{ $document->formatted_size }}</span>
                        </div>
                        <div class="text-xs text-gray-400 italic">Akses oleh: {{ $teacherName }}</div>
                    </div>

                    {{-- Image with watermark overlay --}}
                    <div class="relative overflow-auto bg-gray-100 flex justify-center p-4"
                         style="max-height: calc(100vh - 260px);"
                         oncontextmenu="return false"
                         onkeydown="handleDocShortcuts(event)">

                        {{-- Watermark overlay --}}
                        <div class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none overflow-hidden">
                            <div class="select-none"
                                 style="font-size: clamp(1.5rem, 4vw, 3rem); font-weight: 700; color: rgba(200,200,200,0.35); transform: rotate(-30deg); white-space: nowrap; font-family: sans-serif; letter-spacing: 0.05em; user-select: none;">
                                {{ e($teacherName) }} &nbsp;•&nbsp; BimbelGracia
                            </div>
                        </div>

                        <img id="doc-image"
                             src="{{ $streamUrl }}"
                             alt="{{ e($document->title) }}"
                             class="relative z-0 max-w-full object-contain shadow-lg"
                             style="user-select: none; -webkit-user-drag: none;"
                             ondragstart="return false;"
                        />
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Block shortcuts on image viewer
            document.getElementById('image-viewer').addEventListener('contextmenu', function(e) { e.preventDefault(); return false; });
            document.getElementById('doc-image').addEventListener('dragstart', function(e) { e.preventDefault(); return false; });

            // Also block on the viewer wrapper
            document.getElementById('image-viewer').addEventListener('dragstart', function(e) { e.preventDefault(); });
        </script>
    @else
        {{-- Non-viewable file: show info, no download --}}
        <div class="py-8">
            <div class="max-w-md mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                    <div class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 text-lg">{{ $document->title }}</h3>
                    @if ($document->description)
                        <p class="text-sm text-gray-500 mt-1">{{ $document->description }}</p>
                    @endif
                    <div class="flex items-center justify-center gap-3 mt-3 text-sm text-gray-400">
                        <span>{{ $document->file_name }}</span>
                        <span>•</span>
                        <span>{{ $document->formatted_size }}</span>
                    </div>
                    <p class="text-sm text-gray-400 mt-4 italic">
                        Format file tidak dapat ditampilkan langsung di browser.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <script>
        // Global shortcut blocker for all document views
        function handleDocShortcuts(e) {
            if (e.ctrlKey && e.key === 's') { e.preventDefault(); return false; }
            if (e.ctrlKey && e.key === 'p') { e.preventDefault(); alert('Fungsi cetak tidak diizinkan.'); return false; }
            if (e.key === 'F12') { e.preventDefault(); return false; }
            if (e.ctrlKey && e.shiftKey && e.key === 'I') { e.preventDefault(); return false; }
            if (e.ctrlKey && e.shiftKey && e.key === 'J') { e.preventDefault(); return false; }
            if (e.ctrlKey && e.key === 'u') { e.preventDefault(); return false; }
        }

        // Block right-click globally on document views
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('contextmenu', function(e) {
                // Allow context menu on text inputs and password fields
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                e.preventDefault();
                return false;
            });

            // Block beforeprint
            window.addEventListener('beforeprint', function(e) {
                e.preventDefault();
                alert('Fungsi cetak tidak diizinkan untuk dokumen ini.');
            });
        });

        // Block Ctrl+P globally
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                alert('Fungsi cetak tidak diizinkan untuk dokumen ini.');
                return false;
            }
        });
    </script>
</x-app-layout>
