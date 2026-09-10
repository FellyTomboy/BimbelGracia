<x-app-layout>
    <x-slot name="title">Edit Program Les</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Program Les — {{ $program->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.programs.update', $program) }}"
                      x-data="programForm()"
                      class="p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    @include('admin.programs._form')
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('admin.programs.index') }}" class="px-4 py-2 rounded-md border">Batal</a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-slate-900 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] { -moz-appearance: textfield; appearance: textfield; }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('programForm', () => ({
                type: '{{ old('type', $program->type) }}',
                selectedTeachers: {},
                teacherRates: {},
                errors: {},
                init() {
                    @foreach ($program->teachers as $teacher)
                        this.selectedTeachers[{{ $teacher->id }}] = true;
                        this.teacherRates[{{ $teacher->id }}] = {{ $teacher->pivot->rate }};
                    @endforeach
                    @if (is_array(old('teacher_rates')))
                        const oldRates = @json(old('teacher_rates'));
                        Object.keys(oldRates).forEach(id => {
                            this.selectedTeachers[id] = true;
                            this.teacherRates[id] = oldRates[id];
                        });
                    @endif
                }
            }));
        });
    </script>
</x-app-layout>
