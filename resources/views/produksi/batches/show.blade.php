<x-app-layout pageTitle="Detail Batch {{ $batch->batch_no }}" breadcrumb="Operasional > Batch Produksi > {{ $batch->batch_no }}">
    @php
        $prodResult = $batch->productionResult ?? $batch->productionResults->first();
        $qcResult = $batch->qcResult ?? $prodResult?->qcResult;
    @endphp
    <div class="max-w-7xl mx-auto space-y-5" x-data="{ showStartModal: false, showCompletePrintingModal: false, showCompletePackingModal: false }">

        {{-- ── 1. Page Header (Standard Feature Header) ──────────────────── --}}
        <x-feature-header
            :title="$batch->batch_no"
            icon="batch"
            title-class="text-[18px] sm:text-[20px] font-semibold"
        >
            <div class="flex items-center gap-2 flex-wrap pt-0.5">
                <span class="text-[13px] sm:text-[14px] text-[#64748B] font-medium">
                    {{ $batch->orderItem?->variant?->product?->product_name ?? 'Produk' }} &middot; {{ $batch->orderItem?->variant?->variant_name ?? '-' }}
                </span>
                <span class="text-[#FFD8D8]">&bull;</span>
                @if($batch->status === \App\Enums\BatchStatus::SELESAI)
                    <x-status-badge :status="$batch->status" context="batch" label="Batch Selesai" size="card" />
                @else
                    <x-status-badge :status="$batch->status" context="batch" size="card" />
                @endif
                <span class="inline-flex items-center justify-center px-3 h-7 rounded-lg text-xs font-semibold whitespace-nowrap border {{ $batch->route_badge_class }}">
                    {{ $batch->route_label }}
                </span>
            </div>

            <x-slot:actions>
                <a href="{{ route('produksi.batches.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 px-3 rounded-lg border border-[#FFD8D8] bg-white text-[13px] font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-[#64748B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Current Operational Status Card ────────────────────────── --}}
        @if($batch->status === \App\Enums\BatchStatus::BAHAN)
            @php
                $effectivePrinting = $batch->effective_requires_printing;
                $isLegacyRoute = ($batch->requires_printing === null && $effectivePrinting !== null);
            @endphp
            <div class="bg-[#FFFCFB] border border-[#FFD8D8] rounded-2xl p-3.5 sm:p-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-card">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] font-medium text-[#64748B]">Tahap Saat Ini</span>
                            <span class="badge badge-warning text-[11px] sm:text-[12px] font-medium">Bahan</span>
                            <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] sm:text-[12px] font-medium border {{ $batch->route_badge_class }}">
                                {{ $batch->route_label }}
                            </span>
                        </div>
                        @if($batch->isMaterialReady())
                            @if($effectivePrinting !== null)
                                <p class="text-[13px] sm:text-[14px] text-[#172B4D] font-normal leading-relaxed">
                                    Bahan siap digunakan.
                                    @if($isLegacyRoute)
                                        <span class="block text-amber-700 text-[12px] font-normal mt-0.5 leading-normal">Rute batch akan dikunci dari master saat proses dimulai.</span>
                                    @endif
                                </p>
                            @else
                                <p class="text-[12px] sm:text-[13px] text-amber-700 font-normal leading-relaxed">Rute produksi varian belum ditetapkan pada Master Produk. Hubungi Admin untuk menentukan kebutuhan Printing.</p>
                            @endif
                        @else
                            <p class="text-[12px] sm:text-[13px] text-amber-700 font-normal leading-relaxed">Bahan belum selesai dialokasikan oleh Gudang.</p>
                        @endif
                    </div>
                </div>

                <div class="shrink-0">
                    @if($batch->isMaterialReady())
                        @if($effectivePrinting !== null)
                            <button type="button"
                                    @click="showStartModal = true"
                                    class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>{{ $effectivePrinting ? 'Mulai Printing' : 'Mulai Produksi' }}</span>
                            </button>
                        @endif
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 text-[12px] sm:text-[13px] font-medium">
                            Menunggu Alokasi Gudang
                        </span>
                    @endif
                </div>
            </div>
        @elseif($batch->status === \App\Enums\BatchStatus::PRINTING)
            <div class="bg-[#FFFCFB] border border-[#FFD8D8] rounded-2xl p-3.5 sm:p-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-card">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] font-medium text-[#64748B]">Tahap Saat Ini</span>
                            <span class="badge bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8] text-[11px] sm:text-[12px] font-medium">Printing</span>
                        </div>
                        <p class="text-[13px] sm:text-[14px] text-[#172B4D] font-normal leading-relaxed">Proses printing motif kain sedang berjalan.</p>
                    </div>
                </div>

                <button type="button"
                        @click="showCompletePrintingModal = true"
                        class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs shrink-0 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <span>Lanjut ke Produksi</span>
                </button>
            </div>
        @elseif($batch->status === \App\Enums\BatchStatus::PRODUKSI)
            <div class="bg-[#FFFCFB] border border-[#FFD8D8] rounded-2xl p-3.5 sm:p-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-card">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                        </svg>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] font-medium text-[#64748B]">Tahap Saat Ini</span>
                            <span class="badge bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8] text-[11px] sm:text-[12px] font-medium">Produksi</span>
                        </div>
                        <p class="text-[13px] sm:text-[14px] text-[#172B4D] font-normal leading-relaxed">Proses pemotongan dan penjahitan berjalan.</p>
                    </div>
                </div>

                <a href="{{ route('produksi.batches.production-result.create', $batch) }}"
                   class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs shrink-0 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Catat Hasil Produksi</span>
                </a>
            </div>
        @elseif($batch->status === \App\Enums\BatchStatus::QC)
            @php
                $isQcEligible = $batch->is_qc_date_eligible;
                $prodStartDateStr = $batch->getActualProductionDate();
                $formattedProdStartDate = \App\Support\DateTimeFormatter::formatDate(\Carbon\Carbon::parse($prodStartDateStr));
            @endphp
            <div class="bg-[#FFFCFB] border border-[#FFD8D8] rounded-2xl p-3.5 sm:p-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-card">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] font-medium text-[#64748B]">Tahap Saat Ini</span>
                            <span class="badge bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8] text-[11px] sm:text-[12px] font-medium">Quality Control (QC)</span>
                        </div>
                        <p class="text-[13px] sm:text-[14px] text-[#172B4D] font-normal leading-relaxed">Pemeriksaan kualitas fisik produk siap dilakukan.</p>
                        @if(! $isQcEligible)
                            <p class="text-[12px] font-normal text-amber-700 leading-normal">QC dapat dilakukan mulai {{ $formattedProdStartDate }}.</p>
                        @endif
                    </div>
                </div>

                @if($isQcEligible)
                    <a href="{{ route('produksi.batches.qc.create', $batch) }}"
                       class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Mulai QC</span>
                    </a>
                @else
                    <button type="button"
                            disabled
                            title="QC dapat dilakukan mulai {{ $formattedProdStartDate }}"
                            class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-lg bg-gray-200 text-gray-500 text-[13px] sm:text-sm font-semibold cursor-not-allowed shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>QC (Terkunci)</span>
                    </button>
                @endif
            </div>
        @elseif($batch->status === \App\Enums\BatchStatus::PACKING)
            @php
                $batchService = app(\App\Services\Production\BatchService::class);
                $coverage = $batch->orderItem ? $batchService->calculateProductionCoverage($batch->orderItem) : null;
                $remQty = $coverage ? (float) $coverage['remaining_to_plan'] : 0.0;
                $batchGood = (float) ($qcResult?->good_qty ?? 0);
            @endphp
            <div class="bg-[#FFFCFB] border border-[#FFD8D8] rounded-2xl p-3.5 sm:p-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-card">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[12px] font-medium text-[#64748B]">Tahap Saat Ini</span>
                            <span class="badge bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8] text-[11px] sm:text-[12px] font-medium">Pengemasan (Packing)</span>
                            @if($remQty > 0)
                                <span class="badge badge-warning text-[11px] sm:text-[12px] font-medium">Pesanan Belum Terpenuhi</span>
                            @endif
                        </div>
                        <p class="text-[13px] sm:text-[14px] text-[#172B4D] font-normal leading-relaxed">Proses produksi dan QC telah selesai.</p>
                        @if($remQty > 0)
                            <div class="p-2.5 sm:p-3 bg-amber-50 rounded-xl border border-amber-200 text-[12px] sm:text-[13px] text-amber-900 font-normal leading-relaxed mt-1 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Batch ini menghasilkan <strong class="font-semibold">{{ \App\Support\QuantityFormatter::format($batchGood, 'pcs') }}</strong> produk baik. Masih diperlukan produksi tambahan <strong class="font-semibold">{{ \App\Support\QuantityFormatter::format($remQty, 'pcs') }}</strong> untuk memenuhi pesanan.</span>
                            </div>
                        @endif
                    </div>
                </div>

                <button type="button"
                        @click="showCompletePackingModal = true"
                        class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs shrink-0 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Selesaikan Packing</span>
                </button>
            </div>
        @elseif($batch->status === \App\Enums\BatchStatus::SELESAI)
            @php
                $batchService = app(\App\Services\Production\BatchService::class);
                $coverage = $batch->orderItem ? $batchService->calculateProductionCoverage($batch->orderItem) : null;
                $remQty = $coverage ? (float) $coverage['remaining_to_plan'] : 0.0;
                $orderStatus = $batch->orderItem?->order?->status;
            @endphp
            <div class="bg-emerald-50 border border-emerald-200/80 rounded-2xl p-3.5 sm:p-4.5 shadow-card space-y-2.5">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-[15px] sm:text-[16px] font-semibold text-emerald-900 leading-snug">Proses Batch Selesai</h2>
                            <span class="badge badge-success text-[11px] sm:text-[12px] font-medium">Batch Selesai</span>
                        </div>
                        <p class="text-[13px] sm:text-[14px] text-emerald-800 leading-relaxed font-normal">
                            Seluruh proses produksi, QC, dan packing untuk batch ini telah selesai. Pesanan belum dianggap selesai sampai proses pengiriman selesai.
                        </p>
                        <div class="pt-1 text-[12px] font-medium">
                            @if($remQty > 0)
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 bg-amber-50 border border-amber-200 text-amber-900 px-2 py-0.5 rounded-lg">
                                        <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        Status Pesanan: Produksi Tambahan Diperlukan
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 bg-rose-50 border border-rose-200 text-rose-800 px-2 py-0.5 rounded-lg">
                                        Kekurangan Good Qty: {{ \App\Support\QuantityFormatter::format($remQty, 'pcs') }}
                                    </span>
                                </div>
                            @elseif($orderStatus === \App\Enums\OrderStatus::PACKING)
                                <span class="inline-flex items-center gap-1.5 bg-white/80 border border-emerald-200 text-emerald-900 px-2 py-0.5 rounded-lg">
                                    <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    Status Pesanan: Menunggu Pengiriman
                                </span>
                            @elseif($orderStatus === \App\Enums\OrderStatus::DIKIRIM)
                                <span class="inline-flex items-center gap-1.5 bg-white/80 border border-emerald-200 text-emerald-900 px-2 py-0.5 rounded-lg">
                                    <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                    </svg>
                                    Status Pesanan: Sedang Dikirim
                                </span>
                            @elseif($orderStatus === \App\Enums\OrderStatus::SELESAI)
                                <span class="inline-flex items-center gap-1.5 bg-white/80 border border-emerald-200 text-emerald-900 px-2 py-0.5 rounded-lg">
                                    <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Status Pesanan: Selesai
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @elseif($batch->status === \App\Enums\BatchStatus::SELESAI_PARSIAL)
            <div class="bg-amber-50 border border-amber-300 rounded-2xl p-3.5 sm:p-4.5 shadow-card space-y-2.5">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-[15px] sm:text-[16px] font-semibold text-amber-900 leading-snug">Batch Selesai Parsial</h2>
                            <span class="badge bg-amber-100 text-amber-800 border border-amber-300 text-[11px] sm:text-[12px] font-medium">Selesai Parsial</span>
                        </div>
                        <p class="text-[13px] sm:text-[14px] text-amber-800 leading-relaxed font-normal">
                            Batch ini telah diselesaikan secara parsial oleh Admin dengan kekurangan produksi sebesar <strong>{{ number_format($batch->calculated_shortfall_qty) }} pcs</strong>.
                        </p>
                        <div class="text-xs text-amber-900 bg-white/70 p-2.5 rounded-lg border border-amber-200 mt-2 space-y-1">
                            <div><strong>Alasan Penutupan:</strong> <em>"{{ $batch->close_reason }}"</em></div>
                            <div class="text-[11px] text-amber-800">
                                Disetujui oleh: <strong>{{ $batch->closedByUser?->name ?? 'Admin' }}</strong> pada {{ $batch->closed_at ? $batch->closed_at->format('d M Y, H:i') : '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── 3. Batch Summary Card ────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">Produk</span>
                    <span class="font-semibold text-[14px] sm:text-[15px] text-[#172B4D] mt-1 block leading-snug break-words" title="{{ $batch->orderItem?->variant?->product?->product_name ?? '-' }}">{{ $batch->orderItem?->variant?->product?->product_name ?? '-' }}</span>
                    <span class="text-[13px] font-normal text-[#64748B] mt-0.5 block leading-normal break-words">{{ $batch->orderItem?->variant?->variant_name ?? '-' }} ({{ $batch->orderItem?->variant?->sku ?? '-' }})</span>
                </div>
                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">Pesanan</span>
                    <span class="font-mono font-semibold text-[14px] sm:text-[15px] text-[#8E1B5C] mt-1 block leading-snug">{{ $batch->orderItem?->order?->order_no ?? '-' }}</span>
                    <span class="text-[13px] font-normal text-[#64748B] mt-0.5 block leading-normal break-words">{{ $batch->orderItem?->order?->customer?->customer_name ?? 'Customer Umum' }}</span>
                </div>
                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">PIC Produksi</span>
                    @if($batch->productionEmployee)
                        <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                            <span class="font-semibold text-[14px] sm:text-[15px] text-[#172B4D] leading-snug">
                                {{ $batch->productionEmployee->name }}
                            </span>
                            @if(auth()->id() === $batch->production_employee_id)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#8E1B5C]/10 text-[#8E1B5C] border border-[#8E1B5C]/20">
                                    PIC Anda
                                </span>
                            @endif
                        </div>
                    @else
                        <span class="text-[13px] text-amber-700 italic mt-1 block">Belum ditentukan</span>
                    @endif
                </div>
                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">Target</span>
                    <span class="tabular-nums font-semibold text-[14px] sm:text-[15px] text-[#172B4D] mt-1 block leading-snug">
                        {{ \App\Support\QuantityFormatter::format($batch->target_qty, 'pcs') }}
                    </span>
                    @if($batch->planned_start_date)
                        <span class="text-[13px] font-normal text-[#64748B] mt-0.5 block leading-normal">Mulai: {{ \App\Support\DateTimeFormatter::formatDate($batch->planned_start_date, 'd M Y') }}</span>
                    @endif
                </div>
                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">Target Selesai</span>
                    <span class="font-semibold text-[14px] sm:text-[15px] text-[#172B4D] mt-1 block leading-snug">{{ \App\Support\DateTimeFormatter::formatDate($batch->planned_end_date, 'd M Y', 'Belum ditentukan') }}</span>
                    @if($batch->is_overdue)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200 mt-1">Terlambat</span>
                    @endif
                </div>
                <div>
                    <span class="text-[12px] font-medium text-[#64748B] block">Material</span>
                    <div class="mt-1">
                        @if($batch->isMaterialReady())
                            <span class="inline-flex items-center gap-1.5 text-[14px] sm:text-[15px] font-semibold text-emerald-700 leading-snug">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Siap</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-[14px] sm:text-[15px] font-semibold text-amber-700 leading-snug">
                                <span>Belum Siap</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 4. Material Usage Table Card ──────────────────────────────── --}}
        @php
            $hasUsage = (bool) $batch->material_usage_recorded_at;
            $hasAllocationException = $batch->batchMaterials->contains(function ($bm) {
                return bccomp((string) $bm->allocated_qty, (string) $bm->required_qty, 4) !== 0 || bccomp((string) $bm->required_qty, '0.0000', 4) <= 0;
            });
            $hasSurplusReturn = $hasUsage && $batch->batchMaterials->contains(function ($bm) {
                return bccomp((string) $bm->allocated_qty, (string) $bm->used_qty, 4) > 0;
            });
        @endphp
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <x-nayara-section-header title="Pemakaian Bahan">
                @if($hasUsage)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[12px] font-medium bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Pemakaian Final Tercatat
                    </span>
                @elseif($prodResult)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[12px] font-medium bg-gray-100 text-gray-700 border border-gray-200">
                        Data Sebelum Pemakaian Aktual
                    </span>
                @endif
            </x-nayara-section-header>

            @if($prodResult && ! $hasUsage)
                <div class="p-3 bg-amber-50/70 border-b border-amber-200 text-[12px] sm:text-[13px] text-amber-900 flex items-center gap-2 leading-relaxed">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Pemakaian bahan aktual belum tercatat pada data ini (data dibuat sebelum fitur pencatatan pemakaian bahan diterapkan).</span>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-left text-[14px] text-[#172B4D]">
                    <thead class="bg-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-[0.02em] border-b border-[#FFD8D8]">
                        <tr>
                            <th class="py-2.5 px-3.5 sm:px-4 align-middle">Material</th>
                            <th class="py-2.5 px-3.5 sm:px-4 text-right align-middle">Kebutuhan</th>
                            <th class="py-2.5 px-3.5 sm:px-4 text-right align-middle">Dialokasikan</th>
                            @if($hasUsage)
                                <th class="py-2.5 px-3.5 sm:px-4 text-right align-middle">Digunakan</th>
                                <th class="py-2.5 px-3.5 sm:px-4 text-right align-middle">Selisih</th>
                                <th class="py-2.5 px-3.5 sm:px-4 text-center align-middle">Status</th>
                            @elseif($hasAllocationException)
                                <th class="py-2.5 px-3.5 sm:px-4 text-center align-middle">Status</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($batch->batchMaterials as $bm)
                            @php
                                $unit = $bm->material?->unit?->symbol ?? '';
                                $isAllocated = bccomp((string) $bm->allocated_qty, (string) $bm->required_qty, 4) === 0 && bccomp((string) $bm->required_qty, '0.0000', 4) > 0;
                                $unused = $hasUsage ? bcsub((string) $bm->allocated_qty, (string) $bm->used_qty, 4) : null;
                                $unusedNum = (float) $unused;
                            @endphp
                            <tr class="hover:bg-[#FFFCFB] transition-colors">
                                <td class="py-2.5 px-3.5 sm:px-4 font-medium text-[14px] text-[#172B4D] leading-snug">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $bm->material?->material_name ?? 'Material #' . $bm->material_id }}</span>
                                        @if(! $hasUsage && ! $isAllocated)
                                            @if(bccomp((string) $bm->allocated_qty, '0.0000', 4) > 0)
                                                <span class="badge badge-warning text-[11px] font-medium">Sebagian</span>
                                            @else
                                                <span class="badge badge-warning text-[11px] font-medium">Belum Siap</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2.5 px-3.5 sm:px-4 text-right tabular-nums text-[14px] font-normal text-[#64748B] leading-snug">
                                    {{ \App\Support\QuantityFormatter::format($bm->required_qty, $unit) }}
                                </td>
                                <td class="py-2.5 px-3.5 sm:px-4 text-right tabular-nums font-medium text-[14px] text-[#172B4D] leading-snug">
                                    {{ \App\Support\QuantityFormatter::format($bm->allocated_qty, $unit) }}
                                </td>
                                @if($hasUsage)
                                    <td class="py-2.5 px-3.5 sm:px-4 text-right tabular-nums font-medium text-[14px] text-[#8E1B5C] leading-snug">
                                        {{ \App\Support\QuantityFormatter::format($bm->used_qty, $unit) }}
                                    </td>
                                    <td class="py-2.5 px-3.5 sm:px-4 text-right tabular-nums text-[14px] leading-snug {{ $unusedNum > 0 ? 'font-medium text-amber-700' : ($unusedNum < 0 ? 'font-medium text-rose-700' : 'font-normal text-[#64748B]') }}">
                                        @if($unusedNum > 0)
                                            {{ \App\Support\QuantityFormatter::format($unusedNum, $unit) }} tersisa
                                        @elseif($unusedNum < 0)
                                            -{{ \App\Support\QuantityFormatter::format(abs($unusedNum), $unit) }}
                                        @else
                                            0 {{ $unit }}
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3.5 sm:px-4 text-center">
                                        @if($unusedNum === 0.0)
                                            <span class="badge badge-success text-[11px] font-medium">Sesuai</span>
                                        @elseif($unusedNum > 0)
                                            <span class="badge bg-amber-50 text-amber-800 border border-amber-200 text-[11px] font-medium">Kembali ke Gudang</span>
                                        @else
                                            <span class="badge badge-warning text-[11px] font-medium">Kurang</span>
                                        @endif
                                    </td>
                                @elseif($hasAllocationException)
                                    <td class="py-2.5 px-3.5 sm:px-4 text-center">
                                        @if(! $isAllocated)
                                            @if(bccomp((string) $bm->allocated_qty, '0.0000', 4) > 0)
                                                <span class="badge badge-warning text-[11px] font-medium">Sebagian</span>
                                            @else
                                                <span class="badge badge-warning text-[11px] font-medium">Belum Siap</span>
                                            @endif
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $hasUsage ? 6 : ($hasAllocationException ? 4 : 3) }}" class="py-8 text-center text-[13px] text-[#64748B] leading-relaxed">
                                    Perhitungan kebutuhan bahan (MRP) belum dibuat oleh Gudang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($hasUsage)
                <div class="p-3 bg-[#FFFCFB] border-t border-[#FFD8D8] flex items-center justify-between flex-wrap gap-2 text-[13px] text-[#64748B] leading-normal">
                    @if($hasSurplusReturn)
                        <span>Sisa bahan yang tidak terpakai telah dikembalikan ke stok Gudang.</span>
                    @else
                        <span></span>
                    @endif
                    <span>Dicatat oleh: <strong class="text-[#172B4D] font-medium">{{ $batch->materialUsageRecordedByUser?->name ?? 'Staff Produksi' }}</strong> ({{ \App\Support\DateTimeFormatter::formatDateTime($batch->material_usage_recorded_at) }})</span>
                </div>
            @endif
        </div>

        {{-- ── 5. Production Result Section ──────────────────────────────── --}}
        @if($prodResult)
            @php
                $variance = bcsub((string) $prodResult->actual_qty, (string) $batch->target_qty, 4);
                $varianceNum = (float) $variance;
                $diffText = match(true) {
                    $varianceNum > 0 => '+' . \App\Support\QuantityFormatter::format($varianceNum, 'pcs') . ' · Melebihi Target',
                    $varianceNum < 0 => \App\Support\QuantityFormatter::format($varianceNum, 'pcs') . ' · Di Bawah Target',
                    default => '0 pcs · Sesuai Target',
                };
            @endphp
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <div class="p-3.5 sm:p-4 border-b border-[#FFD8D8] flex items-center justify-between flex-wrap gap-2">
                    <h2 class="text-[16px] sm:text-[17px] font-semibold text-[#172B4D] leading-snug">Hasil Produksi</h2>
                </div>

                <div class="p-3.5 sm:p-4 grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <span class="text-[12px] font-medium text-[#64748B] block">Target</span>
                        <span class="tabular-nums font-semibold text-[14px] sm:text-[15px] text-[#172B4D] mt-0.5 block leading-snug">{{ \App\Support\QuantityFormatter::format($batch->target_qty, 'pcs') }}</span>
                    </div>

                    <div>
                        <span class="text-[12px] font-medium text-[#64748B] block">Hasil Aktual</span>
                        <span class="tabular-nums font-semibold text-[14px] sm:text-[15px] text-[#8E1B5C] mt-0.5 block leading-snug">{{ \App\Support\QuantityFormatter::format($prodResult->actual_qty, 'pcs') }}</span>
                    </div>

                    <div>
                        <span class="text-[12px] font-medium text-[#64748B] block">Selisih</span>
                        <span class="tabular-nums font-semibold text-[14px] sm:text-[15px] mt-0.5 block leading-snug {{ $varianceNum >= 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $diffText }}
                        </span>
                    </div>

                    <div>
                        <span class="text-[12px] font-medium text-[#64748B] block">Tanggal Hasil</span>
                        <span class="font-semibold text-[14px] sm:text-[15px] text-[#172B4D] mt-0.5 block leading-snug">{{ \App\Support\DateTimeFormatter::formatDate($prodResult->result_date) }}</span>
                    </div>
                </div>

                @if($prodResult->notes)
                    <div class="p-3 sm:p-3.5 border-t border-[#FFD8D8] bg-[#FFFCFB] text-[13px] sm:text-[14px] space-y-1">
                        <span class="text-[12px] font-medium text-[#64748B] block">Catatan Produksi:</span>
                        <p class="text-[#172B4D] font-normal leading-relaxed mt-0.5">{{ $prodResult->notes }}</p>
                    </div>
                @endif

                <div class="p-3 bg-[#FFFCFB] border-t border-[#FFD8D8] text-[13px] text-[#64748B] leading-normal">
                    <span>Dicatat oleh: <strong class="text-[#172B4D] font-medium">{{ $prodResult->recordedByUser?->name ?? 'Staff Produksi' }}</strong></span>
                </div>
            </div>
        @endif

        {{-- ── 6. QC Result Section ──────────────────────────────────────── --}}
        @if($qcResult)
            @php
                $good = (float) $qcResult->good_qty;
                $reject = (float) $qcResult->reject_qty;
                $total = (float) $qcResult->total_checked;
                $goodRate = $total > 0 ? number_format(($good / $total) * 100, 1) : 0;
            @endphp
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <div class="p-3.5 sm:p-4 border-b border-[#FFD8D8] flex items-center justify-between flex-wrap gap-2">
                    <h2 class="text-[16px] sm:text-[17px] font-semibold text-[#172B4D] leading-snug">Hasil Quality Control</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] sm:text-[12px] font-medium bg-emerald-50 text-emerald-800 border border-emerald-200">Good Rate: {{ $goodRate }}%</span>
                </div>

                <div class="p-3.5 sm:p-4 grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <span class="text-[12px] font-medium text-[#64748B] block">Total Diperiksa</span>
                        <span class="tabular-nums font-semibold text-[14px] sm:text-[15px] text-[#172B4D] mt-0.5 block leading-snug">{{ \App\Support\QuantityFormatter::format($total, 'pcs') }}</span>
                    </div>

                    <div>
                        <span class="text-[12px] font-medium text-[#64748B] block">Good (Produk Baik)</span>
                        <span class="tabular-nums font-semibold text-[14px] sm:text-[15px] text-emerald-700 mt-0.5 block leading-snug">{{ \App\Support\QuantityFormatter::format($good, 'pcs') }}</span>
                    </div>

                    <div>
                        <span class="text-[12px] font-medium text-[#64748B] block">Reject (Cacat/Afkir)</span>
                        <span class="tabular-nums font-semibold text-[14px] sm:text-[15px] mt-0.5 block leading-snug {{ $reject > 0 ? 'text-rose-700' : 'text-[#64748B]' }}">{{ \App\Support\QuantityFormatter::format($reject, 'pcs') }}</span>
                    </div>

                    <div>
                        <span class="text-[12px] font-medium text-[#64748B] block">Tanggal QC</span>
                        <span class="font-semibold text-[14px] sm:text-[15px] text-[#172B4D] mt-0.5 block leading-snug">{{ \App\Support\DateTimeFormatter::formatDate($qcResult->qc_date) }}</span>
                        @if($qcResult->qc_at)
                            <span class="text-[12px] text-[#64748B] block mt-0.5 leading-normal">Dicatat: {{ \App\Support\DateTimeFormatter::formatDateTime($qcResult->qc_at) }}</span>
                        @endif
                    </div>
                </div>

                @if($qcResult->notes)
                    <div class="p-3 sm:p-3.5 border-t border-[#FFD8D8] bg-[#FFFCFB] text-[13px] sm:text-[14px] space-y-1">
                        <span class="text-[12px] font-medium text-[#64748B] block">Catatan QC:</span>
                        <p class="text-[#172B4D] font-normal leading-relaxed mt-0.5">{{ $qcResult->notes }}</p>
                    </div>
                @endif

                <div class="p-3 bg-[#FFFCFB] border-t border-[#FFD8D8] text-[13px] text-[#64748B] leading-normal">
                    <span>Diperiksa oleh: <strong class="text-[#172B4D] font-medium">{{ $qcResult->qcByUser?->name ?? 'Staff QC' }}</strong></span>
                </div>
            </div>
        @endif

        {{-- ── 7. Notes Card if present ──────────────────────────────────── --}}
        @if($batch->notes)
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-3.5 sm:p-4 shadow-card space-y-1.5">
                <h3 class="text-[12px] font-medium text-[#64748B]">Catatan Batch</h3>
                <p class="text-[13px] sm:text-[14px] text-[#172B4D] leading-relaxed italic bg-[#FFFCFB] p-2.5 sm:p-3 rounded-xl border border-[#FFD8D8] mt-1">{{ $batch->notes }}</p>
            </div>
        @endif

        {{-- ── 8. Confirmation Modals ────────────────────────────────────── --}}
        @if($batch->status === \App\Enums\BatchStatus::BAHAN && $batch->isMaterialReady() && $batch->effective_requires_printing !== null)
            @php
                $modalEffectivePrinting = $batch->effective_requires_printing;
            @endphp
            <div x-show="showStartModal"
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
                 @click.self="showStartModal = false">
                <div class="bg-white rounded-2xl p-4.5 sm:p-5 max-w-md w-full shadow-2xl space-y-3.5 border border-[#FFD8D8] animate-fade-in"
                     @click.stop>
                    <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-2.5">
                        <h3 class="font-semibold text-[15px] sm:text-[16px] text-[#172B4D]">
                            {{ $modalEffectivePrinting ? 'Mulai proses printing?' : 'Mulai proses produksi?' }}
                        </h3>
                        <button type="button" @click="showStartModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                    </div>

                    <div class="space-y-1.5 text-[13px] sm:text-[14px] text-[#64748B] leading-relaxed">
                        @if($modalEffectivePrinting)
                            <p>Batch <strong class="text-[#172B4D] font-mono font-medium">{{ $batch->batch_no }}</strong> akan masuk ke tahap <strong class="text-[#172B4D] font-medium">Printing</strong>.</p>
                            <p>Pastikan operator kain dan mesin printing telah siap menerima bahan baku yang telah dialokasikan.</p>
                        @else
                            <p>Batch <strong class="text-[#172B4D] font-mono font-medium">{{ $batch->batch_no }}</strong> tanpa proses printing akan langsung masuk ke tahap <strong class="text-[#172B4D] font-medium">Produksi</strong>.</p>
                            <p>Pastikan operator pemotongan (cutting) dan penjahitan siap memproses bahan baku.</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('produksi.batches.start', $batch) }}" class="pt-3.5 flex items-center justify-end gap-2 border-t border-[#FFD8D8]">
                        @csrf
                        <button type="button"
                                @click="showStartModal = false"
                                class="h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-[13px] font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-[13px] font-medium sm:font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                            {{ $modalEffectivePrinting ? 'Mulai Printing' : 'Mulai Produksi' }}
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if($batch->status === \App\Enums\BatchStatus::PRINTING)
            <div x-show="showCompletePrintingModal"
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
                 @click.self="showCompletePrintingModal = false">
                <div class="bg-white rounded-2xl p-4.5 sm:p-5 max-w-md w-full shadow-2xl space-y-3.5 border border-[#FFD8D8] animate-fade-in"
                     @click.stop>
                    <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-2.5">
                        <h3 class="font-semibold text-[15px] sm:text-[16px] text-[#172B4D]">
                            Selesaikan tahap printing?
                        </h3>
                        <button type="button" @click="showCompletePrintingModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                    </div>

                    <div class="space-y-1.5 text-[13px] sm:text-[14px] text-[#64748B] leading-relaxed">
                        <p>Batch <strong class="text-[#172B4D] font-mono font-medium">{{ $batch->batch_no }}</strong> akan dipindahkan dari tahap <strong class="text-[#172B4D] font-medium">Printing</strong> ke <strong class="text-[#172B4D] font-medium">Produksi</strong>.</p>
                        <p>Pastikan proses pencetakan motif kain telah selesai dan siap diserahkan ke bagian pemotongan dan penjahitan.</p>
                    </div>

                    <form method="POST" action="{{ route('produksi.batches.complete-printing', $batch) }}" class="pt-3.5 flex items-center justify-end gap-2 border-t border-[#FFD8D8]">
                        @csrf
                        <button type="button"
                                @click="showCompletePrintingModal = false"
                                class="h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-[13px] font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-[13px] font-medium sm:font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                            Lanjut ke Produksi
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if($batch->status === \App\Enums\BatchStatus::PACKING)
            <div x-show="showCompletePackingModal"
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
                 @click.self="showCompletePackingModal = false">
                <div class="bg-white rounded-2xl p-4.5 sm:p-5 max-w-md w-full shadow-2xl space-y-3.5 border border-[#FFD8D8] animate-fade-in"
                     @click.stop>
                    <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-2.5">
                        <h3 class="font-semibold text-[15px] sm:text-[16px] text-[#172B4D]">
                            Selesaikan tahap packing?
                        </h3>
                        <button type="button" @click="showCompletePackingModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                    </div>

                    <div class="space-y-1.5 text-[13px] sm:text-[14px] text-[#64748B] leading-relaxed">
                        <p>Batch <strong class="text-[#172B4D] font-mono font-medium">{{ $batch->batch_no }}</strong> akan ditandai selesai diproses (<strong class="text-[#172B4D] font-medium">Batch Selesai</strong>).</p>
                        <p>Status Sales Order tetap mengikuti proses keseluruhan pesanan dan pengiriman.</p>
                    </div>

                    <form method="POST" action="{{ route('produksi.batches.complete-packing', $batch) }}" class="pt-3.5 flex items-center justify-end gap-2 border-t border-[#FFD8D8]">
                        @csrf
                        <button type="button"
                                @click="showCompletePackingModal = false"
                                class="h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-[13px] font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-[13px] font-medium sm:font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                            Selesaikan Packing
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if($batch->status !== \App\Enums\BatchStatus::PRODUKSI || (session('success') && str_contains(session('success'), 'hasil produksi')))
            <script>
                try {
                    sessionStorage.removeItem('nayara:production-result-draft:{{ $batch->batch_no }}');
                    sessionStorage.removeItem('nayara:production-result-draft:{{ $batch->id }}');
                } catch (e) {}
            </script>
        @endif

    </div>
</x-app-layout>
