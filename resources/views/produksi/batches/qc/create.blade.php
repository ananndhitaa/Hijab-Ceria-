<x-app-layout pageTitle="Pemeriksaan QC - {{ $batch->batch_no }}" breadcrumb="Operasional > Batch Produksi > {{ $batch->batch_no }} > Pemeriksaan QC">
    @php
        $actualQty = (float) ($batch->productionResult?->actual_qty ?? 0);
        $actualQtyInt = (int) $actualQty;
        $rawOldGood = old('good_qty');
        $safeInitialGood = '';
        if ($rawOldGood !== null && $rawOldGood !== '') {
            $numericOld = (int) $rawOldGood;
            if ($numericOld > $actualQtyInt) {
                $safeInitialGood = (string) $actualQtyInt;
            } elseif ($numericOld < 0) {
                $safeInitialGood = '0';
            } else {
                $safeInitialGood = (string) $numericOld;
            }
        }
    @endphp

    <div class="max-w-4xl mx-auto space-y-5" x-data="qcResultForm({{ $actualQtyInt }}, {{ $orderItemQtyOrdered }}, {{ $siblingConfirmedGood }}, {{ $siblingPendingQty }}, '{{ $safeInitialGood }}')">

        {{-- ── 1. Page Header ────────────────────────────────────────────── --}}
        <x-feature-header
            title="Pemeriksaan Quality Control"
            subtitle="Klasifikasikan kuantitas hasil produksi menjadi produk Baik (Good) dan Cacat (Reject)."
            icon="production"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        QUALITY CONTROL
                    </span>
                    <span class="text-xs font-medium font-mono text-[#8E1B5C] bg-[#FFD8D8] px-2.5 py-0.5 rounded-md border border-[#FFD8D8]">
                        {{ $batch->batch_no }}
                    </span>
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('produksi.batches.show', $batch) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Batch Context Summary Card (4 columns desktop) ─────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Produk &amp; Varian</span>
                    <span class="font-medium text-sm text-[#172B4D] mt-0.5 block truncate">{{ $batch->orderItem?->variant?->product?->product_name ?? '-' }}</span>
                    <span class="text-xs text-[#64748B] block truncate">{{ $batch->orderItem?->variant?->variant_name ?? '-' }}</span>
                </div>

                <div>
                    <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Pesanan</span>
                    <span class="font-mono font-medium text-sm text-[#8E1B5C] mt-0.5 block">{{ $batch->orderItem?->order?->order_no ?? '-' }}</span>
                    <span class="text-xs text-[#64748B] block truncate">{{ $batch->orderItem?->order?->customer?->customer_name ?? 'Customer Umum' }}</span>
                </div>

                <div>
                    <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Hasil Produksi</span>
                    <span class="tabular-nums font-semibold text-sm text-[#8E1B5C] mt-0.5 block">{{ \App\Support\QuantityFormatter::format($actualQty, 'pcs') }}</span>
                    <span class="text-xs text-[#64748B] block">Masuk QC</span>
                </div>

                <div>
                    <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Target Batch</span>
                    <span class="tabular-nums font-semibold text-sm text-[#172B4D] mt-0.5 block">{{ \App\Support\QuantityFormatter::format($batch->target_qty, 'pcs') }}</span>
                    <span class="text-xs text-[#64748B] block">Kapasitas rencana</span>
                </div>
            </div>
        </div>

        {{-- ── 3. QC Entry Form Card ─────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <x-nayara-section-header title="Pemeriksaan & Klasifikasi QC" />
            <form id="qcForm" method="POST" action="{{ route('produksi.batches.qc.store', $batch) }}" class="p-5 sm:p-6 space-y-5">
                @csrf

                {{-- Compact Reference Information Strip --}}
                <div class="p-3.5 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] flex items-center justify-between flex-wrap gap-2 text-xs">
                    <div class="flex items-center gap-2 text-[#172B4D]">
                        <svg class="w-4 h-4 text-[#8E1B5C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium text-[#172B4D]">Total yang harus diklasifikasikan: <strong class="text-[#8E1B5C] font-semibold tabular-nums">{{ \App\Support\QuantityFormatter::format($actualQty, 'pcs') }}</strong></span>
                    </div>
                    <span class="text-xs text-[#64748B]">Reject = Aktual − Good (otomatis)</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Actual (read-only) --}}
                    <div class="space-y-1.5">
                        <span class="block text-xs font-semibold text-[#172B4D]">Hasil Aktual</span>
                        <div class="w-full h-10 px-3.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] flex items-center text-xs font-semibold text-[#172B4D] tabular-nums">
                            {{ $actualQtyInt }} pcs
                        </div>
                    </div>

                    {{-- Good Qty --}}
                    <div class="space-y-1.5">
                        <label for="good_qty" class="block text-xs font-semibold text-[#172B4D]">
                            Lolos QC / Good Qty <span class="text-rose-600">*</span>
                        </label>
                        <input type="number"
                               name="good_qty"
                               id="good_qty"
                               min="0"
                               max="{{ $actualQtyInt }}"
                               step="1"
                               required
                               x-model="goodQty"
                               @input="clampGoodQty($event)"
                               @change="clampGoodQty($event)"
                               @paste="setTimeout(() => clampGoodQty($event), 0)"
                               @keydown="if (['.', ',', 'e', 'E', '+', '-'].includes($event.key)) { $event.preventDefault(); }"
                               value="{{ $safeInitialGood }}"
                               placeholder="0"
                               class="w-full h-10 px-3.5 rounded-xl border @error('good_qty') border-rose-500 @else border-[#FFD8D8] @enderror bg-white text-xs font-semibold text-emerald-800 tabular-nums placeholder:text-[#64748B]/40 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
                        @error('good_qty')
                            <p class="text-[11px] font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Reject Qty (computed, preview only; server recomputes) --}}
                    <div class="space-y-1.5">
                        <span class="block text-xs font-semibold text-[#172B4D]">Reject / Cacat</span>
                        <div id="reject_preview" class="w-full h-10 px-3.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] flex items-center text-xs font-semibold text-rose-800 tabular-nums"
                             x-text="(isValid ? rejectQty : '-') + ' pcs'"></div>
                    </div>
                </div>

                {{-- Live Classification Status Banner --}}
                <div class="p-3.5 rounded-xl border transition-all space-y-2" :class="bannerClass">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-[#64748B] block">Status Klasifikasi QC</span>
                            <p class="text-xs font-semibold" :class="bannerTextClass" x-text="bannerMessage"></p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-[#64748B]">Total: </span>
                            <span class="text-xs sm:text-sm font-bold tabular-nums text-[#172B4D]" x-text="(isValid ? totalChecked : 0) + ' / ' + actual + ' pcs'"></span>
                        </div>
                    </div>

                    <template x-if="isBalanced">
                        <div class="pt-2 border-t border-emerald-200/80 flex items-center justify-between text-xs text-emerald-800 font-medium">
                            <span>Good Rate: <strong x-text="goodRate + '%'"></strong></span>
                            <span>Reject Rate: <strong x-text="rejectRate + '%'"></strong></span>
                        </div>
                    </template>
                </div>

                {{-- Live Fulfillment Preview (Dampak ke Pesanan) --}}
                <div class="p-3.5 rounded-xl border transition-all space-y-1.5"
                     :class="hasDeficit ? 'bg-amber-50/90 border-amber-200' : 'bg-emerald-50/90 border-emerald-200'"
                     x-show="goodQty !== '' && isValid">
                    <div class="flex items-start gap-2.5">
                        <div class="mt-0.5 shrink-0">
                            <template x-if="hasDeficit">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </template>
                            <template x-if="!hasDeficit">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                        </div>
                        <div class="space-y-0.5 flex-1">
                            <span class="text-[11px] font-semibold uppercase tracking-wider block"
                                  :class="hasDeficit ? 'text-amber-900' : 'text-emerald-900'">
                                Dampak ke Pesanan
                            </span>
                            <p class="text-xs font-medium leading-relaxed"
                               :class="hasDeficit ? 'text-amber-900' : 'text-emerald-900'"
                               x-text="fulfillmentImpactMessage"></p>
                        </div>
                    </div>
                </div>

                @php
                    $qcProdDateStr = $batch->getActualProductionDate();
                    $qcFormattedProdDate = \App\Support\DateTimeFormatter::formatDate(\Carbon\Carbon::parse($qcProdDateStr));
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Tanggal Produksi (read-only) --}}
                    <div class="space-y-1.5">
                        <span class="block text-xs font-semibold text-[#172B4D]">Tanggal Produksi</span>
                        <div id="production_date_display" class="w-full h-10 px-3.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] flex items-center text-xs font-semibold text-[#172B4D]">
                            {{ $qcFormattedProdDate }}
                        </div>
                        <p class="text-[11px] text-[#64748B]">Tanggal hasil produksi.</p>
                    </div>

                    {{-- Tanggal QC (editable date picker) --}}
                    <div class="space-y-1.5">
                        <label for="qc_date" class="block text-xs font-semibold text-[#172B4D]">Tanggal QC</label>
                        <input type="date"
                               name="qc_date"
                               id="qc_date"
                               required
                               min="{{ $qcProdDateStr }}"
                               value="{{ old('qc_date', $qcProdDateStr) }}"
                               class="w-full h-10 px-3.5 rounded-xl border @error('qc_date') border-rose-500 @else border-[#FFD8D8] @enderror bg-white text-xs font-semibold text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        @error('qc_date')
                            <p class="text-[11px] font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Waktu Pencatatan QC (audit trail, auto at save) --}}
                    <div class="space-y-1.5">
                        <span class="block text-xs font-semibold text-[#172B4D]">Waktu Pencatatan QC</span>
                        <div id="qc_audit_info" class="w-full h-10 px-3.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] flex items-center text-xs font-medium text-[#64748B]">
                            Dicatat otomatis saat QC disimpan.
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="space-y-1.5">
                    <label for="notes" class="block text-xs font-semibold text-[#172B4D]">Catatan Pemeriksaan (Opsional)</label>
                    <textarea name="notes"
                              id="notes"
                              rows="2"
                              placeholder="Catatan jenis cacat kain, jahitan tidak rapi, noda printing, atau catatan QC lainnya..."
                              class="w-full p-3 rounded-xl border @error('notes') border-rose-500 @else border-[#FFD8D8] @enderror bg-white text-xs text-[#172B4D] placeholder:text-[#64748B]/50 focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-[11px] font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Action Buttons --}}
                <div class="pt-4 border-t border-[#FFD8D8] flex items-center justify-end gap-3">
                    <a href="{{ route('produksi.batches.show', $batch) }}"
                       class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition inline-flex items-center justify-center">
                        Batal
                    </a>
                    <button type="button"
                            @click="openModal"
                            class="h-9 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs inline-flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Simpan &amp; Lanjut ke Packing</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- ── 4. Confirmation Modal ──────────────────────────────────────── --}}
        <div x-show="showConfirmModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
             @click.self="showConfirmModal = false">
            <div class="bg-white rounded-2xl p-5 sm:p-6 max-w-md w-full shadow-2xl space-y-4 border border-[#FFD8D8] animate-fade-in"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                    <h3 class="font-bold text-sm text-[#172B4D]">Simpan hasil QC?</h3>
                    <button type="button" @click="showConfirmModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                </div>

                <div class="space-y-3 text-xs text-[#64748B]">
                    <template x-if="hasDeficit">
                        <p class="leading-relaxed text-[#172B4D]">
                            Hasil QC batch akan dicatat dan <strong class="text-emerald-800 tabular-nums" x-text="(parseFloat(goodQty) || 0) + ' pcs'"></strong> produk baik dilanjutkan ke tahap <strong>Packing</strong>. Pesanan masih kekurangan <strong class="text-rose-800 tabular-nums" x-text="remainingToPlan + ' pcs'"></strong> produk baik sehingga <strong>produksi tambahan akan diperlukan</strong>.
                        </p>
                    </template>
                    <template x-if="!hasDeficit">
                        <p class="leading-relaxed text-[#172B4D]">
                            Hasil QC batch akan dicatat dan <strong class="text-emerald-800 tabular-nums" x-text="(parseFloat(goodQty) || 0) + ' pcs'"></strong> produk baik dilanjutkan ke tahap <strong>Packing</strong>. Kebutuhan produk baik untuk pesanan telah terpenuhi.
                        </p>
                    </template>

                    <div class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-[#64748B]">Total Hasil Produksi Batch:</span>
                            <span class="font-semibold text-[#172B4D] tabular-nums">{{ number_format($actualQty, 0, ',', '.') }} pcs</span>
                        </div>
                        <div class="flex justify-between text-emerald-800">
                            <span>Produk Baik (Good):</span>
                            <span class="font-semibold tabular-nums" x-text="(goodQty || 0) + ' pcs'"></span>
                        </div>
                        <div class="flex justify-between text-rose-800">
                            <span>Produk Reject:</span>
                            <span class="font-semibold tabular-nums" x-text="(rejectQty || 0) + ' pcs'"></span>
                        </div>
                        <template x-if="hasDeficit">
                            <div class="flex justify-between text-amber-900 pt-1.5 border-t border-[#FFD8D8] font-semibold">
                                <span>Kekurangan Good Qty Pesanan:</span>
                                <span class="font-bold tabular-nums text-rose-700" x-text="remainingToPlan + ' pcs'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                    <button type="button"
                            @click="showConfirmModal = false"
                            class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button"
                            @click="submitForm"
                            class="h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                        Simpan &amp; Lanjut ke Packing
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function qcResultForm(actualQty, orderQty, siblingGood, siblingPending, initialGood = '') {
            return {
                actual: parseInt(actualQty, 10) || 0,
                orderQty: orderQty,
                siblingGood: siblingGood,
                siblingPending: siblingPending,
                goodQty: initialGood,
                showConfirmModal: false,

                init() {
                    if (this.goodQty !== '') {
                        this.clampGoodQty();
                    }
                },

                clampGoodQty(e) {
                    if (this.goodQty === '' || this.goodQty === null || this.goodQty === undefined) {
                        this.goodQty = '';
                        return;
                    }
                    // Strip non-digits (blocks decimals, negative signs, characters)
                    let cleaned = String(this.goodQty).replace(/[^0-9]/g, '');
                    if (cleaned === '') {
                        this.goodQty = '';
                        if (e && e.target) e.target.value = '';
                        return;
                    }
                    let num = parseInt(cleaned, 10);
                    if (isNaN(num) || num < 0) {
                        num = 0;
                    } else if (num > this.actual) {
                        num = this.actual;
                    }
                    this.goodQty = String(num);
                    if (e && e.target && e.target.value !== this.goodQty) {
                        e.target.value = this.goodQty;
                    }
                },

                get goodNum() {
                    const g = parseInt(this.goodQty, 10);
                    return isNaN(g) ? 0 : g;
                },

                // Preview only. The server always recomputes reject = actual - good.
                get rejectQty() {
                    return this.isValid ? Math.max(0, this.actual - this.goodNum) : 0;
                },

                get totalChecked() {
                    return this.isValid ? (this.goodNum + this.rejectQty) : 0;
                },

                get prospectiveConfirmedGood() {
                    const g = parseInt(this.goodQty, 10) || 0;
                    return this.siblingGood + g;
                },

                get prospectiveCoverage() {
                    return this.prospectiveConfirmedGood + this.siblingPending;
                },

                get remainingToPlan() {
                    const rem = this.orderQty - this.prospectiveCoverage;
                    return rem > 0 ? rem : 0;
                },

                get hasDeficit() {
                    return this.remainingToPlan > 0;
                },

                get fulfillmentImpactMessage() {
                    if (this.hasDeficit) {
                        return 'Good Qty pesanan masih kurang ' + this.remainingToPlan + ' pcs. Setelah QC disimpan, batch ini tetap dilanjutkan ke Packing, tetapi Sales Order memerlukan Batch produksi tambahan.';
                    }
                    return 'Good Qty pesanan telah terpenuhi (' + this.prospectiveConfirmedGood + ' / ' + this.orderQty + ' pcs). Setelah QC disimpan, batch ini dilanjutkan ke Packing.';
                },

                get isValid() {
                    return this.goodQty !== '' && this.goodNum >= 0 && this.goodNum <= this.actual;
                },

                get isBalanced() {
                    return this.isValid;
                },

                get isOver() {
                    return this.goodQty !== '' && this.goodNum > this.actual;
                },

                get goodRate() {
                    if (this.actual <= 0) return 0;
                    return (((parseInt(this.goodQty, 10) || 0) / this.actual) * 100).toFixed(1);
                },

                get rejectRate() {
                    if (this.actual <= 0) return 0;
                    return ((this.rejectQty / this.actual) * 100).toFixed(1);
                },

                get bannerClass() {
                    if (this.isBalanced) return 'bg-emerald-50/80 border-emerald-200';
                    if (this.isOver) return 'bg-rose-50/80 border-rose-200';
                    return 'bg-amber-50/80 border-amber-200';
                },

                get bannerTextClass() {
                    if (this.isBalanced) return 'text-emerald-800';
                    if (this.isOver) return 'text-rose-800';
                    return 'text-amber-800';
                },

                get bannerMessage() {
                    if (this.isBalanced) {
                        return 'Good ' + this.goodNum + ' pcs · Reject ' + this.rejectQty + ' pcs';
                    }
                    if (this.isOver) {
                        const over = (this.goodNum - this.actual).toLocaleString();
                        return 'Lolos QC melebihi hasil aktual sebesar ' + over + ' pcs.';
                    }
                    return 'Masukkan jumlah lolos QC.';
                },

                calculate() {
                    // Reactive getters handle live preview
                },

                openModal() {
                    const goodInput = document.getElementById('good_qty');

                    if (!goodInput.checkValidity()) {
                        goodInput.reportValidity();
                        return;
                    }

                    if (!this.isBalanced) {
                        return;
                    }

                    this.showConfirmModal = true;
                },

                submitForm() {
                    document.getElementById('qcForm').submit();
                }
            };
        }
    </script>
</x-app-layout>
