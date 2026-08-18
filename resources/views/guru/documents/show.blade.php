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
        $teacherName = $teacherName ?? 'Guru';
        $unlocked = session()->get('document_unlocked_' . $document->id, false);
        $needsPassword = $document->access_type === 'password' && !$unlocked;
    @endphp

    @if ($needsPassword)
        {{-- Password gate --}}
        <div class="py-8">
            <div class="max-w-md mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
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
    @else
        {{-- Document content --}}
        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
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

                                @if ($document->protection_level === 'standard')
                                    <a href="{{ route('guru.documents.download', $document) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                                        Unduh
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 italic">Dokumen ini hanya dapat dilihat, tidak dapat diunduh.</span>
                                @endif

                                @if (!$isPdf && !$isImage)
                                    <span class="text-xs text-gray-400 italic">Format file tidak dapat ditampilkan langsung di browser.</span>
                                @endif
                            </div>
                        </div>
                    </div>
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