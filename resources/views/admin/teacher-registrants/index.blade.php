<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Pendaftar Guru Baru']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pendaftar Guru Baru</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola data pendaftar guru dari form publik</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            {{-- Permanent Link Card --}}
            <div class="mb-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Link Form Pendaftaran Guru</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">Permanen</span>
                    </div>
                    <p class="text-xs text-gray-400 mb-3">Link ini permanen dan bisa digunakan berkali-kali. Bagikan link ini ke calon guru.</p>
                    <div class="flex items-center gap-2">
                        <input id="teacher-link-copy" type="text" value="{{ $permanentLink }}" readonly
                            class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none" />
                        <button onclick="copyTeacherLink()" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            Salin Link
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                {{-- Toolbar --}}
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4">
                    <div class="text-sm text-gray-400">
                        {{ $teacherRegistrants->total() }} pendaftar
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($teacherRegistrants->count() > 0)
                            <form method="POST" action="{{ route('admin.teacher-registrants.destroy-all') }}" onsubmit="return confirm('Hapus SEMUA data pendaftar guru? Tindakan ini tidak bisa dibatalkan.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                    Hapus Semua
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 font-medium">Nama</th>
                                <th class="py-3 px-4 font-medium">No. WA</th>
                                <th class="py-3 px-4 font-medium">Jurusan</th>
                                <th class="py-3 px-4 font-medium">Mapel</th>
                                <th class="py-3 px-4 font-medium">Alamat</th>
                                <th class="py-3 px-4 font-medium">Bank</th>
                                <th class="py-3 px-4 font-medium">Status</th>
                                <th class="py-3 px-4 font-medium">Tanggal</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($teacherRegistrants as $tr)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ $tr->name }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $tr->whatsapp ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $tr->major ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $tr->subjects ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600 max-w-[150px] truncate">{{ $tr->address ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        @if ($tr->bank_name)
                                            <span class="text-xs text-gray-600">{{ $tr->bank_name }} - {{ $tr->bank_account }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($tr->converted)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Ditambahkan</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Menunggu</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $tr->created_at->format('d M Y H:i') }}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            @if (!$tr->converted)
                                                <form method="POST" action="{{ route('admin.teacher-registrants.convert', $tr) }}" onsubmit="return confirm('Tambahkan data pendaftar ini ke data guru?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                        Tambah ke Data Guru
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.teacher-registrants.destroy', $tr) }}" onsubmit="return confirm('Hapus data pendaftar ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <x-empty-state icon="📋" title="Belum ada pendaftar guru" description="Bagikan link form pendaftaran guru untuk mulai menerima pendaftar." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($teacherRegistrants->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $teacherRegistrants->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function copyTeacherLink() {
            const input = document.getElementById('teacher-link-copy');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                const btn = input.nextElementSibling;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Tersalin!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        }
    </script>
</x-app-layout>