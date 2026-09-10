<x-app-layout>
    <x-slot name="title">Guru (Hibernasi)</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Guru (Hibernasi)</h2>
            <a href="{{ route('admin.teachers.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
        </div>
    </x-slot>

    <div class="py-12"
         x-data="{
             restoreLoading: null,

             async restoreRow(teacherId) {
                 if (!confirm('Pulihkan guru ini?')) return;
                 this.restoreLoading = teacherId;
                 try {
                     await window.Ajax.post(`/admin/teachers/${teacherId}/restore`);
                     window.Toast?.success('Guru berhasil dipulihkan.');
                     window.location.reload();
                 } catch (e) {
                     window.Toast?.error('Gagal memulihkan guru.');
                 } finally {
                     this.restoreLoading = null;
                 }
             }
         }">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-400">{{ $teachers->count() }} guru</div>
                </div>
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Nama</th>
                                <th class="py-2">Nama Panggilan</th>
                                <th class="py-2">Email</th>
                                <th class="py-2">WhatsApp</th>
                                <th class="py-2">Biaya Kelas</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($teachers as $teacher)
                                <tr>
                                    <td class="py-2">{{ $teacher->displayName }}</td>
                                    <td class="py-2">{{ $teacher->nickname ?: '—' }}</td>
                                    <td class="py-2">{{ $teacher->user?->email ?? '-' }}</td>
                                    <td class="py-2">{{ $teacher->whatsapp_number ?? '-' }}</td>
                                    <td class="py-2">Rp {{ number_format($teacher->class_rate ?? 0) }}</td>
                                    <td class="py-2">hibernasi</td>
                                    <td class="py-2">
                                        <button type="button"
                                                @click="restoreRow({{ $teacher->id }})"
                                                :disabled="restoreLoading === {{ $teacher->id }}"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors disabled:opacity-50">
                                            <svg x-show="restoreLoading === {{ $teacher->id }}" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            Pulihkan
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
