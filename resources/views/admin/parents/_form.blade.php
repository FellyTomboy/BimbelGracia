<form id="crud-form">

    @if($parent)
        @csrf
        @method('PUT')
    @endif

    {{-- Nama Parent --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Nama Parent <span class="text-xs text-gray-400">(opsional)</span>
        </label>
        <input type="text"
               name="name"
               value="{{ $parent?->name ?? '' }}"
               class="crud-field-name w-full border-gray-300 rounded-lg text-sm"
               placeholder="Nama lengkap atau nickname" />
        <p class="crud-error-name mt-1 text-sm text-rose-500" style="display:none"></p>
    </div>

    {{-- No HP --}}
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            No HP <span class="text-xs text-gray-400">(untuk login)</span>
        </label>
        <input type="text"
               name="phone"
               value="{{ $parent?->user?->phone ?? '' }}"
               class="crud-field-phone w-full border-gray-300 rounded-lg text-sm"
               placeholder="08xxxxxxxxxx" />
        <p class="crud-error-phone mt-1 text-sm text-rose-500" style="display:none"></p>
    </div>

    {{-- Alamat --}}
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Alamat <span class="text-xs text-gray-400">(opsional)</span>
        </label>
        <textarea name="address"
                  rows="2"
                  class="crud-field-address w-full border-gray-300 rounded-lg text-sm">{{ $parent?->address ?? '' }}</textarea>
        <p class="crud-error-address mt-1 text-sm text-rose-500" style="display:none"></p>
    </div>

    {{-- Student rows (create mode only) --}}
    @unless($parent)
        <hr class="my-5 border-gray-200">

        <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-medium text-gray-700">
                Daftar Murid <span class="text-xs text-gray-400">(opsional)</span>
            </label>
            <button type="button" id="add-student-row-btn"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                + Tambah Murid
            </button>
        </div>

        <div class="space-y-2" id="students-container"></div>

        <p class="text-xs text-gray-400 mt-2">Isi nickname murid. Kolom lain opsional.</p>
        <p class="crud-error-students mt-1 text-sm text-rose-500" style="display:none"></p>

        <p class="mt-3 text-xs text-gray-500">
            Password default: <code class="bg-gray-100 px-1 rounded">{{ config('bimbel.default_password', 'password') }}</code> (akan diminta ganti saat login pertama)
        </p>
    @endunless
</form>
