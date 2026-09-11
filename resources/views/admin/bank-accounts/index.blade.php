<x-app-layout>
    <x-slot name="title">Rekening Bimbel</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rekening Bimbel</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.bank-accounts.inactive') }}" class="px-4 py-2 rounded-md border text-sm">Data tidak aktif</a>
                <a href="{{ route('admin.bank-accounts.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-all">Tambah Rekening</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12"
         x-data="bulkHibernateActions({
             bulkHibernateUrl: '{{ route('admin.bank-accounts.bulk-destroy') }}',
             resource: 'bank-accounts',
             label: 'rekening',
         })">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-700 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                    <div class="mb-4 flex-1">
                        <x-search-form placeholder="Cari bank, no rekening, pemilik..." />
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-sm text-gray-400">{{ $accounts->total() }} rekening</div>
                        <button type="button"
                                @click="submitBulkHibernate()"
                                x-show="selectedIds.length > 0"
                                x-cloak
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-amber-500 hover:bg-amber-600 transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.316 4.954A9.993 9.993 0 0 0 12 3a9.993 9.993 0 0 0-8.316 4.954C1.992 10.169 1 12.565 1 15.125c0 3.375 2.25 6.375 5.5 8.75 3.25 2.375 6.5 2.5 6.5 2.5s3.25-.125 6.5-2.5c3.25-2.375 5.5-5.375 5.5-8.75 0-2.56-.992-4.956-2.684-7.171z"/></svg>
                            Hapus Terpilih (<span x-text="selectedIds.length"></span>)
                        </button>
                    </div>
                </div>
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2 w-8">
                                    <input type="checkbox"
                                           class="select-all-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           @change="toggleAll($event)" />
                                </th>
                                <x-sortable-header label="Bank" column="bank_name" />
                                <th class="py-2">No Rekening</th>
                                <th class="py-2">Pemilik</th>
                                <x-sortable-header label="Status" column="status" />
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($accounts as $account)
                                <tr>
                                    <td class="py-2">
                                        <input type="checkbox"
                                               class="row-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               value="{{ $account->id }}"
                                               @change="toggleOne($event)" />
                                    </td>
                                    <td class="py-2 font-medium">{{ $account->bank_name }}</td>
                                    <td class="py-2">{{ $account->account_number }}</td>
                                    <td class="py-2">{{ $account->account_holder }}</td>
                                    <td class="py-2">{{ $account->status }}</td>
                                    <td class="py-2 flex gap-2">
                                        <a href="{{ route('admin.bank-accounts.edit', $account) }}" class="text-indigo-600">Edit</a>
                                        <form method="POST" action="{{ route('admin.bank-accounts.destroy', $account) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600">Hibernasi</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-500">Tidak ada rekening ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $accounts->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
