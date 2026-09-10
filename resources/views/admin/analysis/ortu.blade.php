<x-app-layout>
    <x-slot name="title">WA Ortu</x-slot>
    <x-slot name="header">
        <div>
            <x-breadcrumb :items="[['label' => 'WA Ortu']]" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">WA Ortu</h2>
            <p class="text-sm text-gray-500 mt-0.5">Analisis dan kirim pesan tagihan ke orang tua (privat & kelas)</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            @php
                $search = trim(strtolower(request('search') ?? ''));
                $waFilter = request('wa_status', 'semua');
                $filteredSummaries = $privatSummaries->filter(function($s) use ($search, $waFilter) {
                    $matchSearch = !$search
                        || str_contains(strtolower($s['parent_name'] ?? ''), $search)
                        || collect($s['students'])->contains(function($st) use ($search) {
                            return str_contains(strtolower($st['student']?->display_name ?? ''), $search);
                        });
                    $matchWa = $waFilter === 'semua'
                        || ($waFilter === 'sudah' && ($s['wa_sent'] ?? false))
                        || ($waFilter === 'belum' && !($s['wa_sent'] ?? false));
                    return $matchSearch && $matchWa;
                });

                $selectedIndex = (int) request('selected', 0);
                $selected = $filteredSummaries[$selectedIndex] ?? null;
            @endphp

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                {{-- Filter --}}
                <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <p class="text-sm text-gray-600">Pilih periode untuk melihat data tagihan.</p>
                    <form method="GET" class="flex items-center gap-2 flex-wrap">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Bulan</label>
                            <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="w-20 rounded-xl border-gray-200 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Tahun</label>
                            <input type="number" name="year" value="{{ $year }}" min="2020" max="2100" class="w-24 rounded-xl border-gray-200 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Cari</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama ortu/murid..." class="w-36 rounded-xl border-gray-200 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Status WA</label>
                            <select name="wa_status" class="w-28 rounded-xl border-gray-200 text-sm">
                                <option value="semua" @selected(request('wa_status') === 'semua' || !request('wa_status'))>Semua</option>
                                <option value="sudah" @selected(request('wa_status') === 'sudah')>Sudah Kirim</option>
                                <option value="belum" @selected(request('wa_status') === 'belum')>Belum Kirim</option>
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors mt-4 sm:mt-0">Terapkan</button>
                    </form>
                </div>

                <div class="flex flex-col lg:flex-row">
                    {{-- SIDEBAR: Daftar Orang Tua (per-parent) --}}
                    <div id="sidebar-ortu" class="w-full lg:w-1/3 border-b lg:border-b-0 lg:border-r-2 lg:border-gray-300">
                        <div class="p-4 font-bold text-gray-700 flex items-center justify-between lg:block">
                            <span>Daftar Orang Tua</span>
                            <span class="text-xs text-gray-400 lg:hidden">Geser untuk lihat detail</span>
                        </div>
                        @php
                            $sudahCount = $privatSummaries->filter(fn($s) => $s['wa_sent'] ?? false)->count();
                            $belumCount = $privatSummaries->filter(fn($s) => !($s['wa_sent'] ?? false))->count();
                        @endphp
                        <div class="px-4 pb-2 flex gap-3 text-xs">
                            <span class="text-emerald-600 font-medium">{{ $sudahCount }} sudah</span>
                            <span class="text-gray-400">|</span>
                            <span class="text-amber-600 font-medium">{{ $belumCount }} belum</span>
                        </div>
                        <div class="overflow-y-auto max-h-48 lg:max-h-[70vh]">
                            @forelse ($filteredSummaries as $index => $summary)
                                @php $waSent = $summary['wa_sent'] ?? false; @endphp
                                <div id="selected-parent-{{ $index }}" class="relative {{ $waSent ? 'bg-green-100 border-l-4 border-green-500' : ($selectedIndex == $index ? 'bg-indigo-100 border-l-4 border-indigo-500' : '') }}">
                                    <a
                                        href="{{ route('admin.analysis.ortu', ['month' => $month, 'year' => $year, 'selected' => $index, 'search' => request('search'), 'wa_status' => request('wa_status')]) }}"
                                        class="ajax-sidebar-link block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors
                                            {{ ($selectedIndex == $index) ? 'font-bold' : '' }}">
                                        <div class="text-base font-bold text-gray-900">
                                            @foreach ($summary['students'] as $s)
                                                {{ $s['student']?->nickname }}{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        </div>
                                        <div class="text-xs text-gray-500">WA: {{ $summary['contact'] }}</div>
                                        <div class="text-sm text-gray-600">{{ $summary['parent_name'] }}</div>
                                        <div class="mt-1 text-[11px] text-gray-600">
                                            Total: Rp {{ number_format($summary['total']) }}
                                        </div>
                                    </a>
                                    @if ($waSent)
                                        <span class="absolute top-2 right-2 text-xs text-green-700 font-medium flex items-center gap-0.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Terkirim
                                        </span>
                                    @endif
                                </div>
                            @empty
                                <p class="p-4 text-sm text-gray-500">Tidak ada data{{ $search || $waFilter !== 'semua' ? ' yang cocok dengan filter' : ' untuk periode ini' }}.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- KONTEN UTAMA: Detail per Parent (multi-student) --}}
                    <div id="detail-panel" class="w-full lg:w-2/3 p-4 sm:p-6">
                        @if($selected)
                            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">
                                        {{ $selected['students']->map(fn($s) => $s['student']?->nickname)->filter()->implode(', ') }}
                                    </h3>
                                    <p class="text-sm text-gray-600">{{ $selected['parent_name'] }}</p>
                                    <p class="text-sm text-gray-500">Kontak: {{ $selected['contact'] }}</p>
                                    <p class="text-xs text-gray-600">
                                        Total Tagihan:
                                        @if (isset($selected['total_before']) && $selected['total_before'] > $selected['total'])
                                            <span class="text-gray-400 line-through text-[11px]">Rp {{ number_format($selected['total_before']) }}</span>
                                        @endif
                                        <span class="font-semibold">Rp {{ number_format($selected['total']) }}</span>
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="https://wa.me/{{ App\Helpers\WhatsappHelper::toWaFormat($selected['contact']) }}?text={{ urlencode($selected['message']) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors">
                                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        Buka WA
                                    </a>
                                    <button onclick="copyTemplate()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                        Salin Pesan
                                    </button>
                                    @if(!empty($selected['pdf_url']))
                                        <a href="{{ $selected['pdf_url'] }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            PDF Invoice
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Tabel Detail Pembayaran per Student --}}
                            @foreach ($selected['students'] as $studentSummary)
                                <div class="mb-6 p-3 bg-gray-50 rounded-lg border-l-4 border-indigo-500">
                                    <h4 class="font-semibold text-sm text-indigo-800">{{ $studentSummary['student']?->nickname }}</h4>
                                    <div class="overflow-x-auto border rounded-lg mt-2 -mx-3 sm:mx-0">
                                        <div class="inline-block min-w-full align-middle">
                                            <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program / Guru</th>
                                                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Biaya</th>
                                                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jml</th>
                                                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal<br><span class="font-normal text-gray-400">(blm diskon)</span></th>
                                                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diskon</th>
                                                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @foreach ($studentSummary['lines'] as $line)
                                                        <tr>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                                                                {{ $line['label'] }}
                                                                @if ($line['type'] === 'kelas')
                                                                    <span class="ml-2 inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">Kelas</span>
                                                                @else
                                                                    <span class="ml-2 inline-flex items-center rounded-full bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700">Privat</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600">Rp {{ number_format($line['rate']) }}</td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600">{{ $line['count'] }}x</td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-600">Rp {{ number_format($line['total']) }}</td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm">
                                                                <form method="POST" action="{{ route('admin.analysis.ortu-discount') }}" class="flex flex-col sm:flex-row items-start sm:items-center gap-1">
                                                                    @csrf
                                                                    <input type="hidden" name="month" value="{{ $month }}">
                                                                    <input type="hidden" name="year" value="{{ $year }}">
                                                                    <input type="hidden" name="enrollment_id" value="{{ $line['enrollment_id'] }}">
                                                                    <input type="hidden" name="student_id" value="{{ $line['student_id'] }}">
                                                                    <select name="discount_type" class="text-xs rounded-lg border-gray-200 w-full sm:w-16 py-1 px-1">
                                                                        <option value="none" {{ !$line['discount']['type'] ? 'selected' : '' }}>Tdk</option>
                                                                        <option value="percent" {{ $line['discount']['type'] === 'percent' ? 'selected' : '' }}>%</option>
                                                                        <option value="amount" {{ $line['discount']['type'] === 'amount' ? 'selected' : '' }}>Rp</option>
                                                                        <option value="final" {{ $line['discount']['type'] === 'final' ? 'selected' : '' }}>Final</option>
                                                                    </select>
                                                                    <input type="number" name="discount_value" value="{{ $line['discount']['value'] ?? '' }}" min="0" class="text-xs rounded-lg border-gray-200 w-full sm:w-20 py-1 px-1" placeholder="0" />
                                                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors w-full sm:w-auto">Simpan</button>
                                                                    @if (($line['discount']['amount'] ?? 0) > 0)
                                                                        <span class="text-rose-600 text-xs font-medium">-Rp {{ number_format($line['discount']['amount']) }}</span>
                                                                    @endif
                                                                </form>
                                                            </td>
                                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm font-medium">Rp {{ number_format($line['total_after'] ?? $line['total']) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Template WA --}}
                            <div>
                                <label class="block text-xs uppercase tracking-wide text-gray-500 font-semibold mb-2">Template WhatsApp</label>
                                <textarea id="wa-template" class="w-full rounded-xl border-gray-200 text-sm font-mono bg-gray-50" rows="12">{{ $selected['message'] }}</textarea>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        id="wa-sent-checkbox"
                                        data-parent-id="{{ $selected['parent_id'] }}"
                                        data-month="{{ $month }}"
                                        data-year="{{ $year }}"
                                        {{ ($selected['wa_sent'] ?? false) ? 'checked' : '' }}
                                        class="wa-sent-toggle w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <span class="text-sm font-medium text-gray-700">Sudah Kirim WA</span>
                                </label>
                                @if ($selected['wa_sent_at'] ?? false)
                                    <p id="wa-sent-label" class="mt-1 text-xs text-gray-400">
                                        Dikirim {{ \Carbon\Carbon::parse($selected['wa_sent_at'])->translatedFormat('d M Y, H:i') }}
                                        oleh {{ $selected['wa_sent_by'] }}
                                    </p>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center justify-center h-48 lg:h-full">
                                <p class="text-gray-500 text-center px-4">Pilih orang tua dari daftar di samping untuk melihat detail.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ── Helpers ──────────────────────────────────────────────
        function scrollSidebarToSelected(sidebarId, prefix) {
            var sidebar = document.getElementById(sidebarId);
            if (!sidebar) return;
            var scrollEl = sidebar.querySelector('.overflow-y-auto');
            if (!scrollEl) return;
            // Always use exact ID from URL — no fallback, no ambiguous selector
            var m = location.search.match(/[?&]selected=(\d+)/);
            if (!m) return;
            var el = document.getElementById(prefix + m[1]);
            if (!el) return;
            scrollEl.scrollTop = Math.max(0, el.offsetTop - (scrollEl.offsetHeight / 2) + (el.offsetHeight / 2));
        }

        function attachWaSentToggle() {
            document.querySelectorAll('.wa-sent-toggle').forEach(function(checkbox) {
                if (checkbox._handlerAttached) return;
                checkbox._handlerAttached = true;
                checkbox.addEventListener('change', function() {
                    var isSent = this.checked;
                    var parentId = this.dataset.parentId;
                    var month = parseInt(this.dataset.month, 10);
                    var year = parseInt(this.dataset.year, 10);
                    var payload = { parent_id: parseInt(parentId, 10), month: month, year: year, sent: isSent };
                    // Store the in-flight promise so sidebar navigation waits for it
                    window._waTogglePromise = fetch('/admin/analysis/ortu/wa-notification', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    }).then(function(res) {
                        if (!res.ok) throw new Error('Request failed');
                        return res.json();
                    }).then(function(data) {
                        var label = document.getElementById('wa-sent-label');
                        if (label) {
                            label.textContent = data.sent
                                ? 'Dikirim ' + new Date(data.sent_at).toLocaleString('id-ID') + ' oleh ' + (data.sent_by || '-')
                                : '';
                        }
                        // Re-fetch current page so sidebar badge + counts update immediately
                        var currentUrl = location.pathname + location.search;
                        return fetch(currentUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    }).then(function(res) { return res.text(); })
                    .then(function(html) {
                        var parser = new DOMParser();
                        var doc = parser.parseFromString(html, 'text/html');
                        var newSidebar = doc.getElementById('sidebar-ortu');
                        var oldSidebar = document.getElementById('sidebar-ortu');
                        // Save scroll position before outerHTML (browser resets it)
                        var oldScrollTop = oldSidebar ? (oldSidebar.querySelector('.overflow-y-auto') || {}).scrollTop : 0;
                        if (newSidebar && oldSidebar) {
                            oldSidebar.outerHTML = newSidebar.outerHTML;
                            // Restore scroll after DOM replacement
                            var newScrollEl = document.getElementById('sidebar-ortu');
                            if (newScrollEl) {
                                var el = newScrollEl.querySelector('.overflow-y-auto');
                                if (el) el.scrollTop = oldScrollTop;
                            }
                        }
                        var detailPanel = document.getElementById('detail-panel');
                        var newDetail = doc.getElementById('detail-panel');
                        if (detailPanel && newDetail) {
                            detailPanel.innerHTML = newDetail.innerHTML;
                        }
                        attachWaSentToggle();
                        attachAjaxSidebarLinks();
                    }).catch(function(err) {
                        console.error(err);
                        checkbox.checked = !isSent;
                    }).finally(function() {
                        window._waTogglePromise = null;
                    });
                });
            });
        }

        function copyTemplate() {
            var textarea = document.getElementById('wa-template');
            if (!textarea) return;
            textarea.select();
            navigator.clipboard.writeText(textarea.value).then(function() {
                var btn = document.querySelector('[onclick="copyTemplate()"]');
                if (!btn) return;
                var orig = btn.innerHTML;
                btn.innerHTML = '&#10003; Tersalin!';
                setTimeout(function() { btn.innerHTML = orig; }, 2000);
            });
        }

        // ── AJAX sidebar handler (reusable) ─────────────────────
        function makeSidebarClickHandler(e) {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            e.preventDefault();
            var url = this.href;
            var sidebarId = this.closest('[id]') ? this.closest('[id]').id : null;
            var prefix = sidebarId === 'sidebar-ortu' ? 'selected-parent-' : 'selected-guru-';
            var detailPanel = document.getElementById('detail-panel');
            if (detailPanel) {
                detailPanel.style.opacity = '0.5';
                detailPanel.style.pointerEvents = 'none';
            }

            // Extract selected from href BEFORE fetch so we know which item to scroll to
            var selectedFromHref = (function() {
                var m = url.match(/[?&]selected=(\d+)/);
                return m ? m[1] : '0';
            })();

            // Wait for any in-flight WA toggle to finish before navigating
            var waitFor = window._waTogglePromise || Promise.resolve();

            waitFor.then(function() {
                return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            }).then(function(res) { return res.text(); })
                .then(function(html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    // pushState BEFORE DOM replacement so scrollSidebarToSelected reads correct URL
                    history.pushState({}, '', url);
                    if (sidebarId) {
                        var newSidebar = doc.getElementById(sidebarId);
                        var oldSidebar = document.getElementById(sidebarId);
                        // Save scroll BEFORE outerHTML (browser resets scroll to 0 on replace)
                        var oldScrollTop = oldSidebar ? (oldSidebar.querySelector('.overflow-y-auto') || {}).scrollTop : 0;
                        if (newSidebar && oldSidebar) {
                            oldSidebar.outerHTML = newSidebar.outerHTML;
                            // Restore scroll to previous position
                            var newScrollEl = document.getElementById(sidebarId);
                            if (newScrollEl) {
                                var el = newScrollEl.querySelector('.overflow-y-auto');
                                if (el) el.scrollTop = oldScrollTop;
                            }
                        }
                    }
                    // Now scroll to selected item using the href-selected we captured earlier
                    var newSidebarEl = document.getElementById(sidebarId);
                    if (newSidebarEl) {
                        var scrollEl = newSidebarEl.querySelector('.overflow-y-auto');
                        if (scrollEl) {
                            var targetEl = document.getElementById(prefix + selectedFromHref);
                            if (targetEl) {
                                scrollEl.scrollTop = Math.max(0, targetEl.offsetTop - (scrollEl.offsetHeight / 2) + (targetEl.offsetHeight / 2));
                            }
                        }
                    }
                    if (detailPanel) {
                        var newDetail = doc.getElementById('detail-panel');
                        if (newDetail) {
                            detailPanel.innerHTML = newDetail.innerHTML;
                            detailPanel.style.opacity = '1';
                            detailPanel.style.pointerEvents = '';
                        }
                    }
                    attachWaSentToggle();
                    attachAjaxSidebarLinks();
                })
                .catch(function() {
                    window.location.href = url;
                });
        }

        function attachAjaxSidebarLinks() {
            document.querySelectorAll('.ajax-sidebar-link').forEach(function(link) {
                if (link._ajaxBound) return;
                link._ajaxBound = true;
                link.addEventListener('click', makeSidebarClickHandler);
            });
        }

        // ── Boot ─────────────────────────────────────────────────
        attachAjaxSidebarLinks();
        attachWaSentToggle();

        // ── Scroll preservation ────────────────────────────────────
        (function () {
            var key = 'scroll_' + location.pathname + '?{{ http_build_query(request()->query()) }}';
            window.addEventListener('load', function () {
                var pos = sessionStorage.getItem(key);
                if (pos !== null) { window.scrollTo(0, parseInt(pos, 10)); sessionStorage.removeItem(key); }
            });
            document.querySelectorAll('form[method=POST], a[href*="delete"], a[href*="destroy"]').forEach(function (el) {
                el.addEventListener('click', function () { sessionStorage.setItem(key, window.scrollY); });
            });
            document.querySelectorAll('form[method=GET]').forEach(function (form) {
                form.addEventListener('submit', function () { sessionStorage.setItem(key, 0); });
            });
        })();
    </script>
</x-app-layout>
