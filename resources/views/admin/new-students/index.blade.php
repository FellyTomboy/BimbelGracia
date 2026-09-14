<x-app-layout>
    <x-slot name="title">Pendaftar Murid Baru</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[['label' => 'Pendaftar Baru']]" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pendaftar Murid Baru</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kelola data pendaftar dari form publik</p>
            </div>
        </div>
    </x-slot>

    <div x-data="newStudentModal({
        previewConvertUrl: '/admin/new-students/{id}/preview-convert',
        convertUrl: '/admin/new-students/{id}/convert',
        deleteUrl: '/admin/new-students/{id}',
        deleteAllUrl: '{{ route('admin.new-students.destroy-all') }}',
        listSelector: 'table',
    })">

        {{-- Modal ─────────────────────────────────────────────────────── --}}
        <div x-show="modalOpen"
            x-on:ns-submit.window="submitModal()"
            x-on:ns-close.window="close()"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="close()">

            {{-- Backdrop ─────────────────────────────────────────────── --}}
            <div x-show="modalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm"
                @click="close()">
            </div>

            {{-- Panel ───────────────────────────────────────────────── --}}
            <div x-show="modalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

                {{-- Header ───────────────────────────────────────────── --}}
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900" x-text="modalTitle">Konfirmasi</h3>
                    <button @click="close()"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body ──────────────────────────────────────────────── --}}
                <div class="p-5">
                    <div x-html="modalBody" class="space-y-0"></div>
                </div>
            </div>
        </div>

        {{-- Main Content ─────────────────────────────────────────────── --}}
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Permanent Link Card ──────────────────────────────── --}}
                <div class="mb-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Link Form Pendaftaran Murid</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">Permanen</span>
                        </div>
                        <p class="text-xs text-gray-400 mb-3">Link ini permanen dan bisa digunakan berkali-kali. Bagikan link ini ke orang tua murid.</p>
                        <div class="flex items-center gap-2">
                            <input id="student-link-copy" type="text" value="{{ $permanentLink }}" readonly
                                class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 focus:outline-none" />
                            <button onclick="copyStudentLink()" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-all shadow-sm whitespace-nowrap">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                Salin Link
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    {{-- Toolbar ──────────────────────────────────────── --}}
                    <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4">
                        <div class="text-sm text-gray-400">
                            {{ $newStudents->total() }} pendaftar
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($newStudents->count() > 0)
                                <button type="button"
                                    @click="openDeleteAllModal()"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                    Hapus Semua
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Table ─────────────────────────────────────────── --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 bg-gray-50/50">
                                    <th class="py-3 px-4 font-medium">Nama Orang Tua</th>
                                    <th class="py-3 px-4 font-medium">No. WA</th>
                                    <th class="py-3 px-4 font-medium">Alamat</th>
                                    <th class="py-3 px-4 font-medium">Murid Didaftarkan</th>
                                    <th class="py-3 px-4 font-medium">Catatan</th>
                                    <th class="py-3 px-4 font-medium">Status</th>
                                    <th class="py-3 px-4 font-medium">Tanggal</th>
                                    <th class="py-3 px-4 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($newStudents as $ns)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-4 font-medium text-gray-900">{{ $ns->parent_name ?? $ns->name ?? '-' }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $ns->whatsapp ?? '-' }}</td>
                                        <td class="py-3 px-4 text-gray-600 max-w-[200px] truncate">{{ $ns->address ?? '-' }}</td>
                                        <td class="py-3 px-4">
                                            @php
                                                $students = $ns->students_data ?? [];
                                            @endphp
                                            @if (!empty($students))
                                                <div class="space-y-1">
                                                    @foreach ($students as $student)
                                                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                                            <span>{{ $student['full_name'] ?? $student['nickname'] ?? '-' }}</span>
                                                            @if (!empty($student['kelas']))
                                                                <span class="text-blue-500">·</span>
                                                                <span class="text-blue-600">{{ $student['kelas'] }}</span>
                                                            @endif
                                                        </div>
                                                        @if (!empty($student['sekolah']))
                                                            <div class="text-xs text-gray-500 ml-1">{{ $student['sekolah'] }}</div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-500 max-w-[150px] truncate">{{ $ns->notes ?? '-' }}</td>
                                        <td class="py-3 px-4">
                                            @if ($ns->converted)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Ditambahkan</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Menunggu</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-500 text-xs">{{ $ns->created_at->format('d M Y H:i') }}</td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2">
                                                @if (!$ns->converted)
                                                    <button type="button"
                                                        @click="openConvertModal({{ $ns->id }})"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                        Tambah ke Data Murid
                                                    </button>
                                                @endif
                                                <button type="button"
                                                    @click="openDeleteModal({{ $ns->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <x-empty-state icon="📋" title="Belum ada pendaftar" description="Bagikan link form pendaftaran untuk mulai menerima pendaftar." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination ────────────────────────────────────── --}}
                    @if ($newStudents->hasPages())
                        <div class="paging-links p-4 border-t border-gray-100">
                            {{ $newStudents->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyStudentLink() {
            const input = document.getElementById('student-link-copy');
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
