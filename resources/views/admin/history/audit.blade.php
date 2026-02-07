<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit Log</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Waktu</th>
                                <th class="py-2">User</th>
                                <th class="py-2">Aksi</th>
                                <th class="py-2">Tipe Data</th>
                                <th class="py-2">ID Data</th>
                                <th class="py-2">Before</th>
                                <th class="py-2">After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="py-2">{{ $log->created_at->format('d M Y H:i') }}</td>
                                    <td class="py-2">{{ $log->user?->name ?? '-' }}</td>
                                    <td class="py-2">{{ $log->action }}</td>
                                    <td class="py-2">{{ class_basename($log->auditable_type) }}</td>
                                    <td class="py-2">{{ $log->auditable_id }}</td>
                                    <td class="py-2">
                                        <pre class="text-xs whitespace-pre-wrap">{{ json_encode($log->before, JSON_PRETTY_PRINT) }}</pre>
                                    </td>
                                    <td class="py-2">
                                        <pre class="text-xs whitespace-pre-wrap">{{ json_encode($log->after, JSON_PRETTY_PRINT) }}</pre>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
