<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $document->title }}</h2>
            <a href="{{ route('guru.documents.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if ($document->access_type === 'password')
                @php $unlocked = session()->get('document_unlocked_' . $document->id, false); @endphp

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
                                <a href="{{ route('guru.documents.download', $document) }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                {{-- teacher access - show directly --}}
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
                            <a href="{{ route('guru.documents.download', $document) }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>