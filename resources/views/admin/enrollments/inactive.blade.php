<x-app-layout>
    <x-slot name="title">Enrollments (Hibernasi)</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Enrollments (Hibernasi)</h2>
            <a href="{{ route('admin.enrollments.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
        </div>
    </x-slot>

    <div class="py-12"
         x-data="{
             // ── Force Delete State ───────────────────────────────────────────
             fd: window.forceDeleteActions({
                 resource: 'enrollments',
                 label: 'enrollment',
                 itemName: 'Enrollment',
                 modalPrefix: 'fd-modal-enrollments',
                 bulkForceUrl: '{{ route('admin.enrollments.bulk-force-destroy') }}',
                 forceDestroyUrl: (id) => '/admin/enrollments/' + id + '/force-destroy',
                 listSelector: 'table',
             }),

             // ── Flash ──────────────────────────────────────────────────────────
             flashMessage: {{ \Illuminate\Support\Js::from(session('status') ?? '') }},
             showFlash: {{ \Illuminate\Support\Js::from((bool) session('status')) }},
             flashTimer: null,
             init() { if (this.showFlash) this._startTimer(); },
             _startTimer() {
                 if (this.flashTimer) clearTimeout(this.flashTimer);
                 this.showFlash = true;
                 this.flashTimer = setTimeout(() => { this.showFlash = false; }, 4000);
             },
             setFlash(msg) { this.flashMessage = msg; this._startTimer(); },

             // ── Restore modal ────────────────────────────────────────────────
             restoreConfirmId: null,
             restoreLoading: false,
             confirmRestore(id) { this.restoreConfirmId = id; },
             cancelRestore() { this.restoreConfirmId = null; },

             async restoreEnrollment(enrollmentId) {
                 this.restoreLoading = true;
                 try {
                     const resp = await window.Ajax.post('/admin/enrollments/' + enrollmentId + '/restore');
                     window.Toast?.success(resp.data?.message || 'Berhasil dipulihkan.');
                     this.restoreConfirmId = null;
                     this.removeRow(enrollmentId);
                     this.setFlash(resp.data?.message || 'Berhasil dipulihkan.');
                 } catch (e) {
                     if (e.response?.status !== 422) window.Toast?.error('Gagal memulihkan enrollment.');
                 } finally {
                     this.restoreLoading = false;
                 }
             },

             removeRow(id) {
                 const row = document.querySelector("[data-enrollment-id='" + id + "']");
                 if (!row) return;
                 row.style.transition = 'opacity 0.3s';
                 row.style.opacity = '0';
                 setTimeout(() => row.remove(), 300);
             },
         }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            {{-- Flash Banner --}}
            <div x-show="showFlash"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="flashMessage"></span>
                <button @click="showFlash = false" class="ml-auto text-emerald-500 hover:text-emerald-700 font-bold text-lg leading-none">&times;</button>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="text-sm text-gray-400">{{ $enrollments->count() }} enrollment</div>
                    <button type="button"
                            @click="fd.openBulkModal()"
                            x-show="fd.selectedIds.length > 0"
                            x-cloak
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-rose-600 hover:bg-rose-700 transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Permanen (<span x-text="fd.selectedIds.length"></span>)
                    </button>
                </div>
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 w-8">
                                    <input type="checkbox"
                                           class="select-all-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           @change="fd.toggleAll($event)" />
                                </th>
                                <th class="py-2">Program</th>
                                <th class="py-2">Guru</th>
                                <th class="py-2">Murid</th>
                                <th class="py-2">Harga Ortu</th>
                                <th class="py-2">Gaji Guru</th>
                                <th class="py-2">Validasi</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($enrollments as $enrollment)
                                <tr data-row-id="{{ $enrollment->id }}">
                                    <td class="py-2">
                                        <input type="checkbox"
                                               class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               value="{{ $enrollment->id }}"
                                               data-name="Enrollment #{{ $enrollment->id }}"
                                               data-cascade-count="{{ $enrollment->students_count ?? 0 }}"
                                               @change="fd.toggleOne($event)" />
                                    </td>
                                    <td class="py-2 font-medium">
                                        <x-hibernated-label :model="$enrollment->program" :label="$enrollment->program?->name ?? '-'" type="program" />
                                    </td>
                                    <td class="py-2">
                                        <x-hibernated-label :model="$enrollment->teacher" :label="$enrollment->teacher?->displayName ?? '-'" type="guru" />
                                    </td>
                                    <td class="py-2">
                                        @if ($enrollment->students->count() > 0)
                                            @foreach ($enrollment->students as $student)
                                                <x-hibernated-label :model="$student" :label="$student->display_name" type="murid privat" />{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-2">Rp {{ number_format($enrollment->parent_rate) }}</td>
                                    <td class="py-2">Rp {{ number_format($enrollment->teacher_rate) }}</td>
                                    <td class="py-2">{{ $enrollment->validation_status }}</td>
                                    <td class="py-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">hibernasi</span>
                                    </td>
                                    <td class="py-2">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    @click="confirmRestore({{ $enrollment->id }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                                Pulihkan
                                            </button>
                                            <button type="button"
                                                    @click="fd.openPerRowModal({{ $enrollment->id }}, 'Enrollment #{{ $enrollment->id }}', {{ $enrollment->students_count ?? 0 }}, [])"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                                                Hapus Permanen
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-gray-400">
                                        Tidak ada enrollment yang dihibernasi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Force Delete Modal --}}
        <x-force-delete-modal
            id="fd-modal-enrollments-single"
            title="Hapus Permanen Enrollment?"
            message="Enrollment akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="#"
            :is-bulk="false"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />
        <x-force-delete-modal
            id="fd-modal-enrollments-bulk"
            title="Hapus Permanen Enrollment?"
            message="Enrollment yang dipilih akan dihapus permanen dan tidak dapat dipulihkan."
            confirm-text="Hapus Permanen"
            ajax-url="{{ route('admin.enrollments.bulk-force-destroy') }}"
            :is-bulk="true"
            :bulk-count="0"
            :cascade-count="0"
            :need-acknowledge="false" />

        {{-- Restore Modal --}}
        <div x-show="restoreConfirmId !== null" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div x-show="restoreConfirmId !== null"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Pulihkan Enrollment?</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Enrollment akan dikembalikan ke daftar aktif.</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-5">
                        <button @click="cancelRestore()" :disabled="restoreLoading"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors">Batal</button>
                        <button @click="restoreEnrollment(restoreConfirmId)" :disabled="restoreLoading"
                                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition-colors flex items-center gap-2">
                            <template x-if="restoreLoading"><svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></template>
                            Pulihkan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <style>[x-cloak] { display: none !important; }</style>
    </div>
</x-app-layout>
