<x-app-layout>
    <x-slot name="title">Dokumen Buku Pelajaran</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dokumen Buku Pelajaran</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($documents as $doc)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 truncate">{{ $doc->title }}</h3>
                                @if ($doc->description)
                                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $doc->description }}</p>
                                @endif
                                <div class="flex items-center gap-2 mt-2 text-xs text-gray-400">
                                    <span>{{ $doc->file_name }}</span>
                                    <span>•</span>
                                    <span>{{ $doc->formatted_size }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            @if ($doc->access_type === 'password')
                                @php $unlocked = session()->get('document_unlocked_' . $doc->id, false); @endphp
                                @if ($unlocked)
                                    <a href="{{ route('guru.documents.show', $doc) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">Lihat</a>
                                @else
                                    <a href="{{ route('guru.documents.show', $doc) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">Masukkan Password</a>
                                @endif
                            @else
                                <a href="{{ route('guru.documents.show', $doc) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">Lihat</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 text-gray-400">
                        <p class="text-lg">Belum ada dokumen yang tersedia untuk Anda.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>