<x-app-layout pageTitle="Detail Perencanaan Bahan" breadcrumb="Material & Stok > Perencanaan Bahan > Detail">
    @php
        $productName = $batch->orderItem?->variant?->product?->product_name ?? 'Produk';
        $variantName = $batch->orderItem?->variant?->variant_name;
        $productLabel = $variantName ? "{$productName} - {$variantName}" : $productName;
        $headerSubtitle = "{$batch->batch_no} · {$productLabel}";

        // Derive allocation timestamp and actor from StockMovement OUT records
        $allocationBmIds = $batch->batchMaterials->pluck('id')->all();
        $allocationMovement = \App\Models\Inventory\StockMovement::where('reference_type', 'batch_materials')
            ->whereIn('reference_id', $allocationBmIds)
            ->where('movement_type', \App\Enums\MovementType::OUT)
            ->with('createdByUser')
            ->orderBy('created_at', 'desc')
            ->first();
        $allocationTimestamp = $allocationMovement?->created_at;
        $allocationActor = $allocationMovement?->createdByUser?->name;

        // Snapshot date
        $firstBm = $batch->batchMaterials->first();
        $calcDate = $firstBm?->calculated_at ?? $firstBm?->created_at;
    @endphp

    <div x-data="{ showAllocateModal: false, showHistory: false }">
        <div class="space-y-4">

            {{-- 1. Standard Feature Header --}}
            <x-feature-header
                title="Detail Perencanaan Bahan"
                :subtitle="$headerSubtitle"
                icon="planning"
            >
                <x-slot:actions>
                    <a href="{{ route('gudang.material-planning.index') }}"
                       class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] text-xs font-semibold transition-all duration-200 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        <span>Kembali</span>
                    </a>

                    @if($mrpData['can_allocate'])
                        <button type="button"
                                @click="showAllocateModal = true"
                                class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-xl bg-[#8E1B5C] text-white hover:bg-[#8E1B5C] text-xs font-semibold shadow-xs transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            <span>Alokasikan Bahan</span>
                        </button>
                    @elseif($mrpData['has_shortage'])
                        <a href="{{ route('gudang.purchases.create', ['batch_id' => $batch->id]) }}"
                           class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-xl bg-[#8E1B5C] text-white hover:bg-[#8E1B5C] text-xs font-semibold shadow-xs transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>Buat Purchase Order</span>
                        </a>
                    @endif
                </x-slot:actions>
            </x-feature-header>

            {{-- 2. Compact Batch Summary (4 Cards) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card space-y-3">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 text-xs">
                    {{-- Pesanan --}}
                    <div class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                        <span class="text-[10px] font-medium text-[#64748B] uppercase tracking-wider block">Pesanan</span>
                        <span class="font-mono font-medium text-xs text-[#172B4D] block">{{ $batch->orderItem?->order?->order_no ?? '-' }}</span>
                        <span class="text-[11px] text-[#64748B] block truncate">{{ $batch->orderItem?->order?->customer?->customer_name ?? '-' }}</span>
                    </div>

                    {{-- Produk --}}
                    <div class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                        <span class="text-[10px] font-medium text-[#64748B] uppercase tracking-wider block">Produk</span>
                        <span class="font-semibold text-xs text-[#172B4D] block truncate">{{ $batch->orderItem?->variant?->product?->product_name ?? '-' }}</span>
                        <span class="text-[11px] text-[#64748B] block truncate">{{ $batch->orderItem?->variant?->variant_name ?? '-' }}</span>
                    </div>

                    {{-- Target Batch --}}
                    <div class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                        <span class="text-[10px] font-medium text-[#64748B] uppercase tracking-wider block">Target Batch</span>
                        <span class="font-semibold text-xs text-[#172B4D] block tabular-nums">{{ number_format((float)$batch->target_qty, 0, ',', '.') }} pcs</span>
                    </div>

                    {{-- Material --}}
                    <div class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                        <span class="text-[10px] font-medium text-[#64748B] uppercase tracking-wider block">Material</span>
                        <div>
                            @if($mrpData['is_fully_allocated'])
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                    <span>Teralokasi</span>
                                </span>
                                <span class="sr-only">TERALOKASI</span>
                            @elseif($mrpData['has_snapshot'])
                                @if($mrpData['has_shortage'])
                                    <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-amber-50 text-amber-700 border border-amber-200">
                                        Perlu Pembelian <span class="sr-only">PERLU PEMBELIAN</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                                        Siap Dialokasikan <span class="sr-only">SIAP DIALOKASIKAN</span>
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-slate-50 text-slate-700 border border-slate-200">
                                    Perencanaan Belum Tersedia <span class="sr-only">PERENCANAAN BELUM TERSEDIA</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Allocation metadata line (single compact line below summary, Section 9) --}}
                @if($mrpData['is_fully_allocated'])
                    <div class="pt-2 border-t border-[#FFD8D8]/60 text-[11px] text-[#64748B] flex flex-wrap items-center justify-between gap-2">
                        <div>
                            @if($allocationTimestamp)
                                <span>Dialokasikan {{ \App\Support\DateTimeFormatter::formatDateTime($allocationTimestamp) }}@if($allocationActor) · {{ $allocationActor }}@endif</span>
                            @else
                                <span>Dialokasikan</span>
                            @endif
                            <span class="sr-only">Status Material · Dialokasikan: {{ $allocationTimestamp ? \App\Support\DateTimeFormatter::formatDateTime($allocationTimestamp) : '' }} {{ $allocationActor }} · {{ $allocationTimestamp ? \App\Support\DateTimeFormatter::formatLongDateTime($allocationTimestamp) : '' }} · Bahan Telah Dialokasikan · Seluruh bahan telah teralokasi · TERALOKASI</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- 3. Operational Notice (Only when operationally needed, NOT when allocated) --}}
            @if(! $mrpData['has_snapshot'])
                <div class="bg-amber-50 border border-amber-200/80 rounded-2xl p-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-amber-900">Perencanaan Belum Tersedia</h2>
                        <p class="text-[11px] text-amber-800 mt-0.5">
                            Kalkulasi kebutuhan bahan berdasarkan master BOM varian belum digenerate untuk batch ini.
                        </p>
                    </div>
                </div>
            @elseif($mrpData['has_shortage'])
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Status Material</span>
                                <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-amber-50 text-amber-700 border border-amber-200">
                                    Kekurangan Bahan
                                </span>
                            </div>
                            <p class="text-xs text-[#64748B] mt-0.5">
                                {{ $mrpData['shortage_materials'] }} material belum mencukupi stok gudang
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('gudang.purchases.create', ['batch_id' => $batch->id]) }}"
                       class="inline-flex items-center justify-center gap-1.5 h-8 px-3.5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span>Buat Purchase Order</span>
                    </a>
                </div>
            @elseif($mrpData['can_allocate'])
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0 border border-[#FFD8D8]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Status Material</span>
                                <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                                    Siap Dialokasikan
                                </span>
                            </div>
                            <p class="text-xs text-[#64748B] mt-0.5">
                                Seluruh kebutuhan bahan tersedia dan siap dialokasikan ke batch
                            </p>
                        </div>
                    </div>

                    <button type="button"
                            @click="showAllocateModal = true"
                            class="inline-flex items-center justify-center gap-1.5 h-8 px-3.5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span>Alokasikan Bahan</span>
                    </button>
                </div>
            @endif

            {{-- 4. Material Requirement Ledger (Rincian Material) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <x-nayara-section-header
                    title="Rincian Material"
                    meta="{{ $mrpData['total_materials'] }} material"
                />

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-[#172B4D]">
                        <thead class="bg-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-[0.02em] border-b border-[#FFD8D8]">
                            <tr>
                                <th class="py-2.5 sm:py-3 px-4 align-middle">Material</th>
                                <th class="py-2.5 sm:py-3 px-4 align-middle">Satuan</th>
                                <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Kebutuhan</th>
                                <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Stok Saat Ini</th>
                                <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Dialokasikan</th>
                                @if($batch->material_usage_recorded_at)
                                    <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Dipakai Aktual</th>
                                @endif
                                <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Kekurangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @forelse($mrpData['items'] as $item)
                                @php
                                    $rawShortUnit = strtolower($item['symbol'] ?: $item['unit_name'] ?: '-');
                                    $canonicalUnit = match($rawShortUnit) {
                                        'meter', 'm' => 'm',
                                        'roll', 'rol' => 'roll',
                                        'pieces', 'piece', 'pcs', 'pc' => 'pcs',
                                        'lembar', 'lbr' => 'lembar',
                                        'yard', 'yd' => 'yard',
                                        'kilogram', 'kg' => 'kg',
                                        'gram', 'g', 'gr' => 'gr',
                                        default => $rawShortUnit,
                                    };

                                    $isShortage = bccomp($item['effective_shortage_qty'], '0.0000', 4) > 0;
                                    $isAllocated = (bool) ($item['is_allocated'] ?? false);
                                    $allocatedQty = (string) ($item['allocated_qty'] ?? '0.0000');
                                    $requiredQty = (string) ($item['required_qty'] ?? '0.0000');
                                    $isPartial = ! $isAllocated && bccomp($allocatedQty, '0.0000', 4) > 0 && bccomp($allocatedQty, $requiredQty, 4) < 0;
                                @endphp
                                <tr class="hover:bg-[#FFFCFB] transition-colors {{ $isShortage ? 'bg-amber-50/20' : '' }}">
                                    <td class="py-3 px-4 font-medium text-[#172B4D] whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2">
                                            <span>{{ $item['material_name'] }}</span>
                                            @if($isShortage)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                                    Kurang
                                                </span>
                                            @elseif($isPartial)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-800 border border-blue-200">
                                                    Sebagian
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-[#64748B] font-normal whitespace-nowrap">
                                        {{ $canonicalUnit }}
                                    </td>
                                    <td class="py-3 px-4 text-right tabular-nums font-medium text-[#172B4D] whitespace-nowrap">
                                        {{ $item['formatted_required_qty'] }}
                                    </td>
                                    <td class="py-3 px-4 text-right tabular-nums font-normal text-[#172B4D] whitespace-nowrap">
                                        {{ $item['formatted_current_stock'] }}
                                    </td>
                                    <td class="py-3 px-4 text-right tabular-nums font-medium whitespace-nowrap {{ $isAllocated ? 'text-emerald-700' : 'text-[#64748B]' }}">
                                        {{ $item['formatted_allocated_qty'] }}
                                    </td>
                                    @if($batch->material_usage_recorded_at)
                                        <td class="py-3 px-4 text-right tabular-nums font-medium text-[#8E1B5C] whitespace-nowrap">
                                            {{ $item['formatted_used_qty'] }}
                                        </td>
                                    @endif
                                    <td class="py-3 px-4 text-right tabular-nums whitespace-nowrap {{ $isShortage ? 'text-amber-700 font-medium' : 'text-[#64748B] font-normal' }}">
                                        @if($isShortage)
                                            {{ $item['formatted_effective_shortage_qty'] }} {{ $canonicalUnit }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $batch->material_usage_recorded_at ? '7' : '6' }}" class="py-8 text-center text-xs text-[#64748B]">
                                        Belum ada data material untuk varian ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 5. Historical Initial MRP Snapshot (Collapsed by Default) --}}
            @if($mrpData['has_snapshot'])
                <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                    <div class="px-4 py-3 sm:px-5 sm:py-3.5 flex items-center justify-between gap-3 bg-[#FFFCFB]/70">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-0.5">
                                <h3 class="text-xs font-bold text-[#172B4D]">Riwayat Perencanaan Awal</h3>
                                @if($calcDate)
                                    <span class="text-[11px] font-medium text-[#64748B]">
                                        {{ \App\Support\DateTimeFormatter::formatDateTime($calcDate) }}
                                        <span class="sr-only">Dihitung pada: {{ \App\Support\DateTimeFormatter::formatLongDateTime($calcDate) }}</span>
                                    </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-[#64748B] mt-0.5">
                                Snapshot saat perencanaan dibuat.
                            </p>
                        </div>
                        <button type="button"
                                @click="showHistory = !showHistory"
                                class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition cursor-pointer shrink-0">
                            <span x-text="showHistory ? 'Sembunyikan' : 'Lihat'">Lihat</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': showHistory }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                    </div>

                    <div x-show="showHistory" x-cloak style="display: none;" class="border-t border-[#FFD8D8] overflow-x-auto">
                        <table class="w-full text-left text-xs text-[#172B4D]">
                            <thead class="bg-[#FFFCFB] text-[11px] font-semibold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8]">
                                <tr>
                                    <th class="py-2.5 px-4">Material</th>
                                    <th class="py-2.5 px-4 text-right">Kebutuhan Awal</th>
                                    <th class="py-2.5 px-4 text-right">Stok Saat Perencanaan</th>
                                    <th class="py-2.5 px-4 text-right">Kekurangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FFD8D8]">
                                @foreach($mrpData['items'] as $item)
                                    @php
                                        $histShortageIsZero = bccomp($item['historical_shortage_qty'] ?? '0', '0.0000', 4) <= 0;
                                        $histRawUnit = strtolower($item['symbol'] ?: $item['unit_name'] ?: '-');
                                        $histCanonicalUnit = match($histRawUnit) {
                                            'meter', 'm' => 'm',
                                            'roll', 'rol' => 'roll',
                                            'pieces', 'piece', 'pcs', 'pc' => 'pcs',
                                            'lembar', 'lbr' => 'lembar',
                                            'yard', 'yd' => 'yard',
                                            'kilogram', 'kg' => 'kg',
                                            'gram', 'g', 'gr' => 'gr',
                                            default => $histRawUnit,
                                        };
                                    @endphp
                                    <tr class="hover:bg-[#FFFCFB] transition-colors">
                                        <td class="py-2.5 px-4 font-medium text-[#172B4D] whitespace-nowrap">
                                            {{ $item['material_name'] }}
                                        </td>
                                        <td class="py-2.5 px-4 text-right tabular-nums font-medium text-[#172B4D] whitespace-nowrap">
                                            {{ $item['formatted_required_qty'] }} {{ $histCanonicalUnit }}
                                        </td>
                                        <td class="py-2.5 px-4 text-right tabular-nums font-normal text-[#64748B] whitespace-nowrap">
                                            {{ $item['formatted_stock_at_calc'] }} {{ $histCanonicalUnit }}
                                        </td>
                                        <td class="py-2.5 px-4 text-right tabular-nums whitespace-nowrap {{ $histShortageIsZero ? 'text-[#64748B] font-normal' : 'text-amber-700 font-medium' }}">
                                            @if($histShortageIsZero)
                                                —
                                            @else
                                                {{ $item['formatted_historical_shortage_qty'] }} {{ $histCanonicalUnit }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>

        {{-- 6. Modal Konfirmasi Alokasi Bahan (outside vertical layout flow) --}}
        @if($mrpData['can_allocate'])
            <div x-show="showAllocateModal"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
                 @click.self="showAllocateModal = false">
                <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-5 sm:p-6 space-y-4 shadow-xl"
                     @click.stop>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[#172B4D]">Alokasikan bahan ke batch?</h3>
                            <p class="text-xs text-[#64748B]">Batch: {{ $batch->batch_no }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-[#172B4D] leading-relaxed">
                        Material akan dikeluarkan dari stok Gudang dan dialokasikan ke batch produksi.
                    </p>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                        <button type="button"
                                @click="showAllocateModal = false"
                                class="inline-flex items-center justify-center h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition cursor-pointer">
                            Batal
                        </button>
                        <form method="POST" action="{{ route('gudang.material-planning.allocate', $batch) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                                Alokasikan Bahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
