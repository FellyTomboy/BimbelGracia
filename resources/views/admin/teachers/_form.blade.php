<form id="crud-form">

    <div class="space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-xs text-gray-400">(opsional)</span></label>
                <input type="text"
                       name="full_name"
                       value="{{ old('full_name', $teacher?->full_name ?? '') }}"
                       class="crud-field-full_name w-full border-gray-300 rounded-lg text-sm" />
                <p class="crud-error-full_name mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Panggilan</label>
                <input type="text"
                       name="nickname"
                       value="{{ old('nickname', $teacher?->nickname ?? '') }}"
                       class="crud-field-nickname w-full border-gray-300 rounded-lg text-sm" />
                <p class="crud-error-nickname mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp <span class="text-xs text-gray-400">(untuk login)</span></label>
            <input type="text"
                   name="whatsapp"
                   value="{{ old('whatsapp', $teacher?->user?->phone ?? '') }}"
                   class="crud-field-whatsapp w-full border-gray-300 rounded-lg text-sm"
                   placeholder="08XXXXXXXXXX" />
            <p class="crud-error-whatsapp mt-1 text-sm text-rose-500" style="display:none"></p>
            @if (!$teacher)
                <p class="text-xs text-gray-500 mt-1">Password default: <code class="bg-gray-100 px-1 rounded">{{ config('bimbel.default_password', 'password') }}</code> (akan diminta ganti saat login pertama)</p>
            @endif
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
                <input type="text"
                       name="major"
                       value="{{ old('major', $teacher?->major ?? '') }}"
                       class="crud-field-major w-full border-gray-300 rounded-lg text-sm" />
                <p class="crud-error-major mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mapel</label>
                <textarea name="subjects"
                          rows="2"
                          class="crud-field-subjects w-full border-gray-300 rounded-lg text-sm">{{ old('subjects', $teacher?->subjects ?? '') }}</textarea>
                <p class="crud-error-subjects mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
            <textarea name="address"
                      rows="2"
                      class="crud-field-address w-full border-gray-300 rounded-lg text-sm">{{ old('address', $teacher?->address ?? '') }}</textarea>
            <p class="crud-error-address mt-1 text-sm text-rose-500" style="display:none"></p>
        </div>
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bank</label>
                <input type="text"
                       name="bank_name"
                       value="{{ old('bank_name', $teacher?->bank_name ?? '') }}"
                       class="crud-field-bank_name w-full border-gray-300 rounded-lg text-sm" />
                <p class="crud-error-bank_name mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No Rekening</label>
                <input type="text"
                       name="bank_account"
                       value="{{ old('bank_account', $teacher?->bank_account ?? '') }}"
                       class="crud-field-bank_account w-full border-gray-300 rounded-lg text-sm" />
                <p class="crud-error-bank_account mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pemilik</label>
                <input type="text"
                       name="bank_owner"
                       value="{{ old('bank_owner', $teacher?->bank_owner ?? '') }}"
                       class="crud-field-bank_owner w-full border-gray-300 rounded-lg text-sm" />
                <p class="crud-error-bank_owner mt-1 text-sm text-rose-500" style="display:none"></p>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Biaya Kelas Bersama</label>
            <input type="number"
                   name="class_rate"
                   value="{{ old('class_rate', $teacher?->class_rate ?? 0) }}"
                   min="0" step="1000"
                   class="crud-field-class_rate w-full border-gray-300 rounded-lg text-sm" />
            <p class="crud-error-class_rate mt-1 text-sm text-rose-500" style="display:none"></p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status"
                    class="crud-field-status w-full border-gray-300 rounded-lg text-sm">
                <option value="active" @selected(old('status', $teacher?->status ?? 'active') === 'active')>active</option>
                <option value="hibernasi" @selected(old('status', $teacher?->status ?? '') === 'hibernasi')>hibernasi</option>
            </select>
            <p class="crud-error-status mt-1 text-sm text-rose-500" style="display:none"></p>
        </div>
    </div>
</form>
