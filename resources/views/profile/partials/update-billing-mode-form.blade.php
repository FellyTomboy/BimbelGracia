<section class="mt-8">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Mode Penagihan') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Pilih cara guru mengisi presensi: per pertemuan (harian) atau sekaligus untuk banyak tanggal (bulanan).') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.fine-settings.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Current mode display --}}
        <div class="flex items-start justify-between gap-4 py-4 border-b border-gray-100">
            <div>
                <p class="font-medium text-gray-900">Mode Saat Ini</p>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($billingMode === 'daily')
                        <span class="text-emerald-600 font-medium">Harian</span> — Guru mengisi presensi satu tanggal per form.
                    @else
                        <span class="text-indigo-600 font-medium">Bulanan</span> — Guru mengisi presensi banyak tanggal sekaligus (tabbed).
                    @endif
                </p>
            </div>
        </div>

        {{-- Radio group --}}
        <fieldset>
            <legend class="sr-only">Mode Penagihan</legend>
            <div class="space-y-3">

                <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-colors
                    {{ $billingMode === 'monthly' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}"
                    x-data="{ selected: {{ $billingMode === 'monthly' ? 'true' : 'false' }} }"
                    :class="selected ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                    <input type="radio" name="billing_mode" value="monthly"
                        class="mt-0.5 h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                        {{ $billingMode === 'monthly' ? 'checked' : '' }}
                        @change="selected = true; this.closest('fieldset').querySelectorAll('label').forEach(l => { if (l !== this.closest('label')) l.classList.remove('border-indigo-500', 'bg-indigo-50') }); this.closest('label').classList.add('border-indigo-500', 'bg-indigo-50');">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Penagihan Bulanan</p>
                        <p class="mt-0.5 text-xs text-gray-500">Guru mengisi presensi banyak tanggal sekaligus dalam satu form tabbed.</p>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-colors
                    {{ $billingMode === 'daily' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}"
                    x-data="{ selected: {{ $billingMode === 'daily' ? 'true' : 'false' }} }"
                    :class="selected ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                    <input type="radio" name="billing_mode" value="daily"
                        class="mt-0.5 h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                        {{ $billingMode === 'daily' ? 'checked' : '' }}
                        @change="selected = true; this.closest('fieldset').querySelectorAll('label').forEach(l => { if (l !== this.closest('label')) l.classList.remove('border-indigo-500', 'bg-indigo-50') }); this.closest('label').classList.add('border-indigo-500', 'bg-indigo-50');">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Penagihan Harian</p>
                        <p class="mt-0.5 text-xs text-gray-500">Guru mengisi presensi satu tanggal per form (seperti sekarang).</p>
                    </div>
                </label>

            </div>
        </fieldset>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan') }}</x-primary-button>
            @if (session('status') === 'fine-settings-updated')
                <span class="text-sm text-gray-400 animate-pulse">Tersimpan.</span>
            @endif
        </div>
    </form>
</section>
