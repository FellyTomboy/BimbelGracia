<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rekening Bimbel</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.bank-accounts.inactive') }}" class="px-4 py-2 rounded-md border text-sm">Data tidak aktif</a>
                <a href="{{ route('admin.bank-accounts.create') }}" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm">Tambah Rekening</a>
            </div>
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
                                <th class="py-2">Bank</th>
                                <th class="py-2">No Rekening</th>
                                <th class="py-2">Pemilik</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($accounts as $account)
                                <tr>
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
