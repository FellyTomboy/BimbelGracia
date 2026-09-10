<x-app-layout>
    <x-slot name="title">Program Les (Hibernasi)</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Program Les (Hibernasi)</h2>
            <a href="{{ route('admin.programs.index') }}" class="px-4 py-2 rounded-md border text-sm">Kembali ke Aktif</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Nama</th>
                                <th class="py-2">Tipe</th>
                                <th class="py-2">Mapel</th>
                                <th class="py-2">Harga Ortu</th>
                                <th class="py-2">Gaji Guru</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($programs as $program)
                                <tr id="program-row-{{ $program->id }}">
                                    <td class="py-2 font-medium">{{ $program->name }}</td>
                                    <td class="py-2">{{ $program->type }}</td>
                                    <td class="py-2">{{ $program->subject ?? '-' }}</td>
                                    <td class="py-2">Rp {{ number_format($program->default_parent_rate) }}</td>
                                    <td class="py-2">Rp {{ number_format($program->default_teacher_rate) }}</td>
                                    <td class="py-2">hibernasi</td>
                                    <td class="py-2">
                                        <button type="button"
                                                data-program-id="{{ $program->id }}"
                                                data-program-name="{{ $program->name }}"
                                                onclick="restoreProgram(this)"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                            Restore
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

    <script>
        async function restoreProgram(btn) {
            const id = btn.dataset.programId;
            const name = btn.dataset.programName;
            if (!confirm('Pulihkan program "' + name + '"?')) return;

            btn.disabled = true;
            try {
                await window.Ajax.post(`/admin/programs/${id}/restore`);
                window.Toast?.success('Program berhasil dipulihkan.');
                const row = document.getElementById(`program-row-${id}`);
                if (row) row.remove();
            } catch (e) {
                btn.disabled = false;
            }
        }
    </script>
</x-app-layout>