<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Pendaftar Baru']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pendaftar Baru</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola data pendaftar dari form publik</p>
            </div>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('admin.new-students.generate-link') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Buat Link Baru
                    </button>
                </form>
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

            @if (session('generated_link'))
                <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-xl text-sm">
                    <p class="font-medium mb-1">Link form pendaftaran:</p>
                    <div class="flex items-center gap-2">
                        <input id="link-copy" type="text" value="{{ session('generated_link') }}" readonly class="flex-1 bg-white border border-blue-300 rounded-lg px-3 py-1.5 text-sm text-blue-800" />
                        <button onclick="copyLink()" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 transition-colors">Salin</button>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                {{-- Toolbar --}}
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4">
                    <div class="text-sm text-gray-400">
                        {{ $newStudents->total() }} pendaftar
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-50/50">
                                <th class="py-3 px-4 font-medium">Nama</th>
                                <th class="py-3 px-4 font-medium">No. WA</th>
                                <th class="py-3 px-4 font-medium">Orang Tua</th>
                                <th class="py-3 px-4 font-medium">WA Ortu</th>
                                <th class="py-3 px-4 font-medium">Sekolah</th>
                                <th class="py-3 px-4 font-medium">Kelas</th>
                                <th class="py-3 px-4 font-medium">Divisi</th>
                                <th class="py-3 px-4 font-medium">Status</th>
                                <th class="py-3 px-4 font-medium">Tanggal</th>
                                <th class="py-3 px-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($newStudents as $ns)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ $ns->name }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $ns->whatsapp ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $ns->parent_name ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $ns->parent_whatsapp ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $ns->school ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $ns->grade ?? '-' }}</td>
                                    <td class="py-3 px-4">
                                        @if ($ns->division)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">{{ $ns->division }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($ns->converted)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Dikonversi</span>
                                        @elseif ($ns->whatsapp)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Menunggu</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-500">Link Baru</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $ns->created_at->format('d M Y') }}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            @if (!$ns->converted && $ns->whatsapp)
                                                <form method="POST" action="{{ route('admin.new-students.convert', $ns) }}" onsubmit="return confirm('Konversi data ini menjadi murid?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                                        Konversi
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.new-students.destroy', $ns) }}" onsubmit="return confirm('Hapus data pendaftar ini?')">
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
                                    <td colspan="10">
                                        <x-empty-state icon="📋" title="Belum ada pendaftar" description="Buat link form pendaftaran untuk mulai menerima pendaftar." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($newStudents->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $newStudents->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function copyLink() {
            const input = document.getElementById('link-copy');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value);
            alert('Link berhasil disalin!');
        }
    </script>
</x-app-layout>