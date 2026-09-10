@props([
    'name',
    'id' => null,
    'required' => false,
    'autocomplete' => null,
    'disabled' => false,
])

<div>
    <x-input-label :for="$id ?? $name" :value="$slot" />

    <div class="relative">
        <x-text-input
            :id="$id ?? $name"
            :name="$name"
            type="password"
            :required="$required"
            :autocomplete="$autocomplete"
            :disabled="$disabled"
            class="block mt-1 w-full pr-10"
        />

        <button
            type="button"
            data-input-id="{{ $id ?? $name }}"
            class="password-toggle absolute inset-y-0 right-0 flex items-center pr-3 mt-1"
            style="top: 0.25rem; cursor: pointer;"
            aria-label="Tampilkan sembunyikan password"
        >
            <svg data-icon="hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400 hover:text-gray-600" style="width:1.125rem;height:1.125rem;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
            </svg>
            <svg data-icon="visible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400 hover:text-gray-600 hidden" style="width:1.125rem;height:1.125rem;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        </button>
    </div>
</div>

@push('scripts')
<script>
(function () {
    if (window._passwordToggleInit) return;
    window._passwordToggleInit = true;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.password-toggle');
        if (!btn) return;

        var inputId = btn.getAttribute('data-input-id');
        var input = document.getElementById(inputId);
        if (!input) return;

        var isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';

        var iconHidden = btn.querySelector('[data-icon="hidden"]');
        var iconVisible = btn.querySelector('[data-icon="visible"]');
        if (iconHidden) iconHidden.classList.toggle('hidden', isHidden);
        if (iconVisible) iconVisible.classList.toggle('hidden', !isHidden);
    });
}());
</script>
@endpush
