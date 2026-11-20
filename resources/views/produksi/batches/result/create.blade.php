<x-app-layout pageTitle="Catat Hasil Produksi - {{ $batch->batch_no }}" breadcrumb="Operasional > Batch Produksi > {{ $batch->batch_no }} > Catat Hasil">
    @php
        $initialUsage = [];
        $allocatedMap = [];
        $materialMetaMap = [];
        $hasServerOld = old('_token') !== null || old('actual_qty') !== null || old('material_usage') !== null;
        $serverOldUsage = old('material_usage', []);

        foreach ($batch->batchMaterials as $bm) {
            $matId = (int) $bm->material_id;
            $allocFormatted = rtrim(rtrim(number_format((float) $bm->allocated_qty, 4), '0'), '.');
            $initialUsage[$bm->id] = isset($serverOldUsage[$bm->id])
                ? (string) $serverOldUsage[$bm->id]
                : $allocFormatted;
            $allocatedMap[$bm->id] = (float) $bm->allocated_qty;
            $materialMetaMap[$bm->id] = [
                'bm_id'        => (int) $bm->id,
                'material_id'  => $matId,
                'name'         => $bm->material?->material_name ?? "Material #{$bm->material_id}",
                'unit'         => $bm->material?->unit?->symbol ?? 'pcs',
                'stock'        => (float) ($bm->material?->stock?->qty_on_hand ?? 0),
                'allocated'    => (float) $bm->allocated_qty,
            ];
        }
    @endphp

    <div class="max-w-5xl mx-auto space-y-5" x-data="productionResultForm(
        {{ (float) $batch->target_qty }},
        {{ json_encode($initialUsage) }},
        {{ json_encode($allocatedMap) }},
        {{ json_encode($materialMetaMap) }},
        '{{ $batch->batch_no }}',
        '{{ $batch->id }}',
        {{ $hasServerOld ? 'true' : 'false' }},
        '{{ old('actual_qty', isset($uninspectedResult) && $uninspectedResult ? (string) (float) $uninspectedResult->actual_qty : '') }}',
        '{{ old('result_date', isset($uninspectedResult) && $uninspectedResult && $uninspectedResult->result_date ? $uninspectedResult->result_date->toDateString() : date('Y-m-d')) }}',
        {{ json_encode(old('notes', isset($uninspectedResult) && $uninspectedResult ? ($uninspectedResult->notes ?? '') : '')) }}
    )">

        {{-- ── 1. Page Header (NAYARA Feature Header) ────────────────────── --}}
        <x-feature-header
            title="Catat Pemakaian Bahan & Hasil Produksi"
            icon="production"
            title-class="text-[20px] font-semibold"
            class="py-3.5 sm:py-4 px-4 sm:px-5"
        >
            <div class="flex items-center gap-2.5 flex-wrap pt-0.5">
                <span class="text-[12px] sm:text-[13px] font-mono font-semibold text-[#8E1B5C] bg-[#FFD8D8] px-2.5 py-0.5 rounded-lg border border-[#FFD8D8]">
                    {{ $batch->batch_no }}
                </span>
                <span class="text-[13px] text-[#64748B] leading-tight">Catat bahan terpakai dan hasil produksi.</span>
            </div>

            <x-slot:actions>
                <a href="{{ route('produksi.batches.show', $batch) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 sm:h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-[13px] sm:text-sm font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs cursor-pointer">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#64748B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Batch Context Summary Card ─────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">Produk &amp; Varian</span>
                    <span class="font-medium text-[14px] text-[#172B4D] mt-0.5 block leading-snug">{{ $batch->orderItem?->variant?->product?->product_name ?? '-' }}</span>
                    <span class="text-[13px] text-[#64748B] block leading-snug">{{ $batch->orderItem?->variant?->variant_name ?? '-' }}</span>
                </div>

                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">Sales Order</span>
                    <span class="font-mono font-semibold text-[14px] text-[#8E1B5C] mt-0.5 block">{{ $batch->orderItem?->order?->order_no ?? '-' }}</span>
                    <span class="text-[13px] text-[#64748B] block">{{ $batch->orderItem?->order?->customer?->customer_name ?? 'Customer Umum' }}</span>
                </div>

                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">Target</span>
                    <span class="tabular-nums font-semibold text-[14px] text-[#172B4D] mt-0.5 block">{{ \App\Support\QuantityFormatter::format($batch->target_qty, 'pcs') }}</span>
                </div>

                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">Target Selesai</span>
                    <span class="font-medium text-[14px] text-[#172B4D] mt-0.5 block">{{ \App\Support\DateTimeFormatter::formatDate($batch->planned_end_date, 'd M Y', 'Belum ditentukan') }}</span>
                    @if($batch->is_overdue)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200 mt-1">Terlambat</span>
                    @endif
                </div>

                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">Rute</span>
                    <div class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[12px] font-medium {{ $batch->route_badge_class }}">
                            {{ $batch->route_label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 3. Entry Form Card ────────────────────────────────────────── --}}
        <form id="resultForm" method="POST" action="{{ route('produksi.batches.production-result.store', $batch) }}" class="space-y-5">
            @csrf

            {{-- SECTION 1: PEMAKAIAN BAHAN --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <x-nayara-section-header
                    title="Pemakaian Bahan"
                    meta="{{ $batch->batchMaterials->count() }} material"
                />

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse table-fixed">
                        <thead class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-[0.02em]">
                            <tr class="h-11 sm:h-12">
                                <th class="w-[18%] px-3 sm:px-4 py-2.5 sm:py-3 align-middle">Nama Material</th>
                                <th class="w-[7%] px-2 py-2.5 sm:py-3 text-center align-middle">Satuan</th>
                                <th class="w-[11%] px-3 py-2.5 sm:py-3 text-right align-middle">Dibutuhkan</th>
                                <th class="w-[11%] px-3 py-2.5 sm:py-3 text-right align-middle">Dialokasikan</th>
                                <th class="w-[16%] px-2 py-2.5 sm:py-3 text-center whitespace-nowrap align-middle">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        <span>Dipakai Aktual</span>
                                        <span class="text-rose-600 font-semibold">*</span>
                                    </div>
                                </th>
                                <th class="w-[10%] px-3 py-2.5 sm:py-3 text-right align-middle">Selisih</th>
                                <th class="w-[9%] px-2 py-2.5 sm:py-3 text-center align-middle">Status</th>
                                <th class="w-[18%] px-2 sm:px-3 py-2.5 sm:py-3 text-center align-middle">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8] bg-white">
                            @foreach($batch->batchMaterials as $bm)
                                @php
                                    $unitSymbol = $bm->material?->unit?->symbol ?? 'pcs';
                                    $reqFormatted = rtrim(rtrim(number_format((float) $bm->required_qty, 4), '0'), '.');
                                    $allocFormatted = rtrim(rtrim(number_format((float) $bm->allocated_qty, 4), '0'), '.');
                                @endphp
                                <tr class="hover:bg-[#FFD8D8]/40 transition">
                                    <td class="px-3 sm:px-4 py-2.5 sm:py-3 font-medium text-[13px] sm:text-[13.5px] text-[#172B4D] align-middle break-words">
                                        {{ $bm->material?->material_name ?? 'Material #' . $bm->material_id }}
                                    </td>
                                    <td class="px-2 py-2.5 sm:py-3 text-center text-[#64748B] font-mono text-[12px] sm:text-[13px] align-middle">
                                        {{ $unitSymbol }}
                                    </td>
                                    <td class="px-3 py-2.5 sm:py-3 text-right font-normal text-[13px] sm:text-[13.5px] text-[#64748B] tabular-nums align-middle">
                                        {{ $reqFormatted }}
                                    </td>
                                    <td class="px-3 py-2.5 sm:py-3 text-right font-medium text-[13px] sm:text-[13.5px] text-[#172B4D] tabular-nums align-middle">
                                        {{ $allocFormatted }}
                                    </td>
                                    <td class="px-2 py-2 text-center align-middle">
                                        <div class="flex flex-col items-center justify-center">
                                            <input type="number"
                                                   name="material_usage[{{ $bm->id }}]"
                                                   id="usage_{{ $bm->id }}"
                                                   step="any"
                                                   min="0"
                                                   required
                                                   x-model="materialUsage[{{ $bm->id }}]"
                                                   @input="saveDraft()"
                                                   class="w-[115px] max-w-full h-9 px-2.5 text-center rounded-lg border @error('material_usage.' . $bm->id) border-rose-500 @else border-[#FFD8D8] @enderror bg-white text-[13px] font-medium text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition input-no-spinner [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:m-0 [&::-webkit-inner-spin-button]:m-0">
                                            @error('material_usage.' . $bm->id)
                                                <p class="text-[11px] text-rose-600 mt-0.5 text-center">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 sm:py-3 text-right font-mono text-[13px] font-medium whitespace-nowrap tabular-nums align-middle" :class="getDiffClass({{ $bm->id }})">
                                        <span x-text="getDiffText({{ $bm->id }}, '{{ $unitSymbol }}')"></span>
                                    </td>
                                    <td class="px-2 py-2.5 sm:py-3 text-center whitespace-nowrap align-middle">
                                        <template x-if="getDiff({{ $bm->id }}) > 0">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200">Sisa</span>
                                        </template>
                                        <template x-if="getDiff({{ $bm->id }}) === 0">
                                            <span class="text-[#64748B]/40 text-xs">—</span>
                                        </template>
                                        <template x-if="getDiff({{ $bm->id }}) < 0">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200">Kurang</span>
                                        </template>
                                    </td>
                                    <td class="px-2 sm:px-3 py-2.5 sm:py-3 text-center whitespace-nowrap align-middle">
                                        <template x-if="getDiff({{ $bm->id }}) < 0">
                                            <button type="button"
                                                    @click="openAdditionalModal({{ $bm->id }})"
                                                    class="inline-flex items-center justify-center gap-1 h-8 sm:h-9 px-2.5 sm:px-3 rounded-lg bg-[#8E1B5C] text-white text-[12px] font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer whitespace-nowrap">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                                </svg>
                                                <span>Tambah Bahan</span>
                                            </button>
                                        </template>
                                        <template x-if="getDiff({{ $bm->id }}) >= 0">
                                            <span class="text-[#64748B]/40 text-xs">—</span>
                                        </template>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="py-2.5 px-4 sm:px-6 bg-[#FFD8D8] border-t border-[#FFD8D8] flex items-center gap-2 text-[12px] sm:text-[13px] text-[#64748B]">
                    <svg class="w-3.5 h-3.5 text-[#8E1B5C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Sisa bahan otomatis kembali ke Gudang.</span>
                </div>
            </div>

            {{-- SECTION 2: HASIL PRODUKSI --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <x-nayara-section-header
                    title="Hasil Produksi"
                />

                <div class="p-5 sm:p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        {{-- Target Qty (Readonly) --}}
                        <div class="space-y-1.5">
                            <label class="block text-[13px] font-medium text-[#172B4D]">Target Produksi</label>
                            <input type="text"
                                   value="{{ number_format((float) $batch->target_qty) }} pcs"
                                   readonly
                                   class="w-full h-11 px-4 rounded-lg border border-[#FFD8D8] bg-[#FFD8D8] text-[14px] font-medium text-[#64748B] cursor-not-allowed">
                        </div>

                        {{-- Actual Qty (Input) --}}
                        <div class="space-y-1.5">
                            <label for="actual_qty" class="block text-[13px] font-medium text-[#172B4D]">
                                Hasil Aktual (pcs) <span class="text-rose-600 font-semibold">*</span>
                            </label>
                            <input type="number"
                                   name="actual_qty"
                                   id="actual_qty"
                                   step="any"
                                   min="0.0001"
                                   required
                                   x-model="actualQty"
                                   @input="saveDraft()"
                                   class="w-full h-11 px-4 rounded-lg border @error('actual_qty') border-rose-500 @else border-[#FFD8D8] @enderror bg-white text-[14px] font-medium text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                            @error('actual_qty')
                                <p class="text-[12px] font-medium text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Live Variance Preview Banner --}}
                    <div x-show="hasInput" class="p-3.5 rounded-lg border transition-all"
                         :class="varianceClass">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div class="space-y-0.5">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-[#64748B] block">Estimasi Selisih terhadap Target</span>
                                <p class="text-[13px] text-[#172B4D]" x-text="varianceDescription"></p>
                            </div>
                            <div class="text-right">
                                <span class="text-[14px] font-semibold font-mono" x-text="varianceText"></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        {{-- Result Date --}}
                        <div class="space-y-1.5">
                            <label for="result_date" class="block text-[13px] font-medium text-[#172B4D]">
                                Tanggal Hasil Produksi <span class="text-rose-600 font-semibold">*</span>
                            </label>
                            <input type="date"
                                   name="result_date"
                                   id="result_date"
                                   required
                                   x-model="resultDate"
                                   @input="saveDraft()"
                                   class="w-full h-11 px-4 rounded-lg border @error('result_date') border-rose-500 @else border-[#FFD8D8] @enderror bg-white text-[14px] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                            @error('result_date')
                                <p class="text-[12px] font-medium text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Notes --}}
                        <div class="space-y-1.5 sm:col-span-2">
                            <label for="notes" class="block text-[13px] font-medium text-[#172B4D]">Catatan Produksi (Opsional)</label>
                            <textarea name="notes"
                                      id="notes"
                                      rows="3"
                                      x-model="notes"
                                      @input="saveDraft()"
                                      placeholder="Catatan produksi..."
                                      class="w-full min-h-[88px] p-3 rounded-lg border @error('notes') border-rose-500 @else border-[#FFD8D8] @enderror bg-white text-[13px] sm:text-[14px] text-[#172B4D] placeholder:text-[#64748B]/50 focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="text-[12px] font-medium text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="pt-4 border-t border-[#FFD8D8] flex items-center justify-end gap-3">
                        <a href="{{ route('produksi.batches.show', $batch) }}"
                           class="h-10 sm:h-11 px-4 rounded-lg border border-[#FFD8D8] bg-white text-[13px] sm:text-sm font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition inline-flex items-center justify-center cursor-pointer">
                            Batal
                        </a>
                        <button type="button"
                                @click="openModal"
                                class="h-10 sm:h-11 px-5 rounded-lg bg-[#8E1B5C] text-white text-[13px] sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs inline-flex items-center justify-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Simpan &amp; Lanjut ke QC</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        {{-- ── 4. Confirmation Modal ──────────────────────────────────────── --}}
        <div x-show="showConfirmModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
             @click.self="showConfirmModal = false">
            <div class="bg-white rounded-2xl p-5 sm:p-6 max-w-md w-full shadow-2xl space-y-4 border border-[#FFD8D8] animate-fade-in"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                    <h3 class="font-semibold text-[15px] sm:text-[16px] text-[#172B4D]">Simpan hasil produksi?</h3>
                    <button type="button" @click="showConfirmModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                </div>

                <div class="space-y-2 text-[13px] text-[#64748B]">
                    <p>Pemakaian bahan dan hasil produksi akan dicatat sebagai <strong>data final batch</strong>, kemudian batch dilanjutkan ke tahap <strong>Quality Control (QC)</strong>.</p>
                    <div class="p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] space-y-1">
                        <div class="flex justify-between">
                            <span class="text-[#64748B]">Target Produksi:</span>
                            <span class="font-semibold text-[#172B4D]">{{ number_format((float) $batch->target_qty, 0, ',', '.') }} pcs</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#64748B]">Hasil Aktual:</span>
                            <span class="font-semibold text-[#8E1B5C]" x-text="actualQty ? actualQty + ' pcs' : '-'"></span>
                        </div>
                        <div class="flex justify-between border-t border-[#FFD8D8] pt-1">
                            <span class="text-[#64748B]">Selisih Output:</span>
                            <span class="font-semibold font-mono" x-text="varianceText"></span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                    <button type="button"
                            @click="showConfirmModal = false"
                            class="h-9 sm:h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-[13px] font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button"
                            @click="submitForm"
                            class="h-9 sm:h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                        Simpan &amp; Lanjut ke QC
                    </button>
                </div>
            </div>
        </div>

        {{-- ── 5. Modal Tambah Bahan dari Gudang ──────────────────────────── --}}
        <div x-show="showAdditionalModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
             @click.self="showAdditionalModal = false">
            <div class="bg-white rounded-2xl p-5 sm:p-6 max-w-md w-full shadow-2xl space-y-4 border border-[#FFD8D8] animate-fade-in"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                    <div>
                        <h3 class="font-semibold text-[15px] sm:text-[16px] text-[#172B4D]">Tambah Bahan dari Gudang</h3>
                        <p class="text-[12px] text-[#64748B] mt-0.5" x-text="selectedMaterialName"></p>
                    </div>
                    <button type="button" @click="showAdditionalModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                </div>

                <form :action="additionalFormAction" method="POST" class="space-y-4">
                    @csrf
                    <div class="p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] space-y-2 text-[13px]">
                        <div class="flex justify-between">
                            <span class="text-[#64748B]">Alokasi Saat Ini:</span>
                            <span class="font-semibold font-mono text-[#172B4D]" x-text="currentAllocated + ' ' + selectedUnit"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#64748B]">Stok Gudang Tersedia:</span>
                            <span class="font-semibold font-mono text-emerald-700" x-text="currentWarehouseStock + ' ' + selectedUnit"></span>
                        </div>
                        <div class="flex justify-between border-t border-[#FFD8D8] pt-1">
                            <span class="text-[#64748B]">Kekurangan Aktual:</span>
                            <span class="font-semibold font-mono text-rose-600" x-text="additionalQtyNeeded + ' ' + selectedUnit"></span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="additional_qty_input" class="block text-[13px] font-medium text-[#172B4D]">
                            Jumlah Tambahan Bahan yang Diambil dari Gudang <span class="text-rose-600 font-semibold">*</span>
                        </label>
                        <div class="relative">
                            <input type="number"
                                   name="additional_qty"
                                   id="additional_qty_input"
                                   step="any"
                                   min="0.0001"
                                   x-model="additionalQtyNeeded"
                                   required
                                   class="w-full h-11 px-4 pr-14 rounded-lg border border-[#FFD8D8] bg-white text-[14px] font-medium text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                            <span class="absolute right-4 top-3 text-[13px] font-semibold text-[#64748B]" x-text="selectedUnit"></span>
                        </div>
                        <p class="text-[12px] text-[#64748B]">Stok Gudang akan otomatis dikurangi dan alokasi batch akan langsung bertambah.</p>
                    </div>

                    <div class="pt-3 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                        <button type="button"
                                @click="showAdditionalModal = false"
                                class="h-9 sm:h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-[13px] font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-9 sm:h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <span>Konfirmasi Tambah Bahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        function productionResultForm(
            targetQty,
            initialUsage,
            allocatedMap,
            materialMetaMap,
            batchNo,
            batchId,
            hasServerOld,
            serverOldActual,
            serverOldDate,
            serverOldNotes
        ) {
            const storageKey = 'nayara:production-result-draft:{{ $batch->batch_no }}';

            // Load draft from sessionStorage if available
            let draft = null;
            try {
                const raw = sessionStorage.getItem(storageKey);
                if (raw) draft = JSON.parse(raw);
            } catch (e) {
                draft = null;
            }

            // Material usage resolution following precedence:
            // 1. Server old input (if hasServerOld)
            // 2. sessionStorage draft (keyed by material_id, do NOT clamp to allocation)
            // 3. Server default/allocation value
            const resolvedUsage = {};
            for (const bmId in initialUsage) {
                const matId = materialMetaMap[bmId]?.material_id;
                if (hasServerOld && initialUsage[bmId] !== undefined) {
                    resolvedUsage[bmId] = initialUsage[bmId];
                } else if (draft && draft.materials && matId !== undefined && draft.materials[String(matId)] !== undefined) {
                    resolvedUsage[bmId] = draft.materials[String(matId)];
                } else {
                    resolvedUsage[bmId] = initialUsage[bmId];
                }
            }

            // actualQty resolution
            let resolvedActual = '';
            if (hasServerOld && serverOldActual !== '') {
                resolvedActual = serverOldActual;
            } else if (draft && draft.actual_qty !== undefined && draft.actual_qty !== null && draft.actual_qty !== '') {
                resolvedActual = draft.actual_qty;
            }

            // resultDate resolution
            let resolvedDate = serverOldDate || '{{ date('Y-m-d') }}';
            if (!hasServerOld && draft && draft.result_date) {
                resolvedDate = draft.result_date;
            }

            // notes resolution
            let resolvedNotes = serverOldNotes || '';
            if (!hasServerOld && draft && draft.notes !== undefined && draft.notes !== null) {
                resolvedNotes = draft.notes;
            }

            return {
                target: targetQty,
                batchNo: batchNo,
                batchId: batchId,
                storageKey: storageKey,
                actualQty: resolvedActual,
                resultDate: resolvedDate,
                notes: resolvedNotes,
                materialUsage: resolvedUsage,
                allocated: allocatedMap || {},
                materialMeta: materialMetaMap || {},
                showConfirmModal: false,
                showAdditionalModal: false,
                selectedMaterialId: null,
                selectedMaterialName: '',
                selectedUnit: '',
                currentAllocated: 0,
                currentWarehouseStock: 0,
                additionalQtyNeeded: 0,

                init() {
                    // Synchronize current resolved values to sessionStorage draft
                    this.saveDraft();
                },

                saveDraft() {
                    try {
                        const materials = {};
                        for (const bmId in this.materialMeta) {
                            const matId = this.materialMeta[bmId]?.material_id;
                            if (matId !== undefined && this.materialUsage[bmId] !== undefined) {
                                materials[String(matId)] = this.materialUsage[bmId];
                            }
                        }
                        const data = {
                            batch_id: this.batchId,
                            batch_no: this.batchNo,
                            materials: materials,
                            actual_qty: this.actualQty || '',
                            result_date: this.resultDate || '',
                            notes: this.notes || ''
                        };
                        sessionStorage.setItem(this.storageKey, JSON.stringify(data));
                    } catch (e) {
                        console.warn('Failed to save production result draft:', e);
                    }
                },

                get additionalFormAction() {
                    if (!this.selectedMaterialId) return '#';
                    return '{{ route('produksi.batches.materials.additional', [$batch, ':id']) }}'.replace(':id', this.selectedMaterialId);
                },

                openAdditionalModal(id) {
                    this.selectedMaterialId = id;
                    this.selectedMaterialName = this.materialMeta[id]?.name || 'Material #' + id;
                    this.selectedUnit = this.materialMeta[id]?.unit || 'unit';
                    this.currentAllocated = this.allocated[id] || 0;
                    this.currentWarehouseStock = this.materialMeta[id]?.stock || 0;
                    const diff = this.getDiff(id);
                    this.additionalQtyNeeded = diff < 0 ? Math.round(Math.abs(diff) * 10000) / 10000 : 0;
                    this.showAdditionalModal = true;
                },

                get hasInput() {
                    return this.actualQty !== '' && !isNaN(this.actualQty) && Number(this.actualQty) > 0;
                },

                get variance() {
                    if (!this.hasInput) return 0;
                    return Number(this.actualQty) - this.target;
                },

                get varianceText() {
                    if (!this.hasInput) return '-';
                    const diff = this.variance;
                    if (diff > 0) return '+' + diff.toLocaleString() + ' pcs · Di atas target';
                    if (diff < 0) return diff.toLocaleString() + ' pcs · Di bawah target';
                    return '0 pcs · Sesuai target';
                },

                get varianceDescription() {
                    if (!this.hasInput) return '';
                    const diff = this.variance;
                    if (diff > 0) return 'Hasil produksi melebihi target awal sebanyak ' + diff.toLocaleString() + ' pcs.';
                    if (diff < 0) return 'Hasil produksi di bawah target awal sebanyak ' + Math.abs(diff).toLocaleString() + ' pcs.';
                    return 'Hasil produksi tepat sesuai target batch.';
                },

                get varianceClass() {
                    if (!this.hasInput) return 'bg-[#FFFCFB] border-[#FFD8D8] text-[#64748B]';
                    const diff = this.variance;
                    if (diff >= 0) return 'bg-emerald-50/70 border-emerald-200 text-emerald-800';
                    return 'bg-amber-50/70 border-amber-200 text-amber-800';
                },

                getDiff(id) {
                    const alloc = this.allocated[id] || 0;
                    const used = parseFloat(this.materialUsage[id]) || 0;
                    return alloc - used;
                },

                getDiffText(id, symbol) {
                    const diff = this.getDiff(id);
                    if (isNaN(diff)) return '-';
                    const formatted = Math.round(diff * 10000) / 10000;
                    if (formatted === 0) return '0 ' + symbol;
                    return (formatted > 0 ? '+' : '') + formatted.toLocaleString() + ' ' + symbol;
                },

                getDiffClass(id) {
                    const diff = this.getDiff(id);
                    if (diff < 0) return 'text-rose-600';
                    if (diff > 0) return 'text-amber-700';
                    return 'text-[#64748B]';
                },

                openModal() {
                    const input = document.getElementById('actual_qty');
                    if (!input.checkValidity()) {
                        input.reportValidity();
                        return;
                    }

                    // Check all material usage inputs
                    for (const id in this.allocated) {
                        const usageInput = document.getElementById('usage_' + id);
                        if (usageInput && !usageInput.checkValidity()) {
                            usageInput.reportValidity();
                            return;
                        }

                        // Check if usage exceeds allocated
                        const diff = this.getDiff(id);
                        if (diff < 0) {
                            const matName = this.materialMeta[id]?.name || 'Material #' + id;
                            const shortfall = Math.round(Math.abs(diff) * 10000) / 10000;
                            const unit = this.materialMeta[id]?.unit || 'unit';
                            alert('Kebutuhan bahan ' + matName + ' melebihi alokasi sebanyak ' + shortfall + ' ' + unit + '. Silakan klik "Tambah Bahan dari Gudang" terlebih dahulu.');
                            return;
                        }
                    }

                    this.showConfirmModal = true;
                },

                submitForm() {
                    document.getElementById('resultForm').submit();
                }
            };
        }
    </script>

    <style>
        /* Hilangkan native number spinner visual pada input tabel pemakaian */
        .input-no-spinner::-webkit-outer-spin-button,
        .input-no-spinner::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .input-no-spinner {
            -moz-appearance: textfield;
            appearance: textfield;
        }
    </style>
</x-app-layout>
