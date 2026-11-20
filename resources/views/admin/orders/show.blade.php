<x-app-layout pageTitle="Detail Sales Order" breadcrumb="Sales Order > Detail">
    <div class="max-w-6xl mx-auto space-y-4 sm:space-y-5">


        {{-- ── 1. Page Header (Concise Top Identity) ───────────────────────── --}}
        <div class="nayara-page-header bg-white border border-[#FFD8D8] border-l-4 border-l-[#8E1B5C] rounded-2xl min-h-[104px] md:min-h-[116px] lg:min-h-[120px] px-5 sm:px-6 md:px-7 py-4 sm:py-5 md:py-[22px] shadow-card flex flex-col lg:flex-row lg:items-center justify-between gap-4 sm:gap-5 w-full">
            <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0 border border-[#FFD8D8]/80 shadow-xs">
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div class="space-y-1 sm:space-y-1.5 min-w-0">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-bold font-mono text-[#8E1B5C] leading-none">{{ $order->order_no }}</h1>
                        <span class="badge {{ $order->status->badgeClass() }} text-xs px-2.5 py-0.5">
                            {{ $order->status->label() }}
                        </span>
                        @php
                            $fulfillment = $order->getFulfillmentPresentation();
                        @endphp
                        @if($order->status === \App\Enums\OrderStatus::PACKING)
                            @if($fulfillment['state'] === 'READY_TO_SHIP')
                                <span class="badge badge-success text-xs px-2.5 py-0.5" title="Packing selesai dan pembayaran telah lunas">
                                    Siap Dikirim
                                </span>
                            @elseif($fulfillment['state'] === 'WAITING_PAYMENT')
                                <span class="badge badge-warning text-xs px-2.5 py-0.5" title="Packing selesai, menunggu pelunasan pembayaran">
                                    Menunggu Pelunasan
                                </span>
                            @endif
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-[#64748B] flex items-center gap-1.5 font-medium">
                        <span>{{ $order->order_date ? $order->order_date->format('d M Y') : '-' }}</span>
                        <span class="text-[#FFD8D8]">&middot;</span>
                        <span class="text-[#172B4D] font-semibold">{{ $order->customer->customer_name ?? '-' }}</span>
                    </p>
                </div>
            </div>

            {{-- Header Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                {{-- Kembali / Koreksi Order --}}
                @if($order->status === \App\Enums\OrderStatus::KOMUNIKASI)
                    <a href="{{ route('admin.orders.edit', $order) }}"
                       class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                       title="Kembali ke form edit untuk mengoreksi pesanan ini">
                        <svg class="w-4 h-4 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        <span>Kembali Edit Order</span>
                    </a>
                @else
                    <a href="{{ route('admin.orders.index') }}"
                       class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                       title="Kembali ke daftar pesanan">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        <span>Kembali</span>
                    </a>
                @endif

                {{-- Lihat di Kanban --}}
                <a href="{{ route('admin.kanban.index', ['search' => $order->order_no]) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg border border-[#8E1B5C]/30 bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#8E1B5C] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Lihat posisi order ini di Kanban Board">
                    <svg class="w-4 h-4 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    <span>Lihat di Kanban</span>
                </a>

                {{-- Edit Order (Only when eligible and not already represented by Kembali Edit Order) --}}
                @if($canEdit && $order->status !== \App\Enums\OrderStatus::KOMUNIKASI)
                    <a href="{{ route('admin.orders.edit', $order) }}"
                       class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                       title="Edit rincian pesanan">
                        <svg class="w-4 h-4 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Edit Order</span>
                    </a>
                @endif

                {{-- Konfirmasi Deal (KOMUNIKASI only) --}}
                @if($canConfirmDeal)
                    <form method="POST" action="{{ route('admin.orders.confirm-deal', $order) }}"
                          onsubmit="return confirm('Konfirmasi pesanan {{ $order->order_no }} menjadi status DEAL?');"
                          class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                                title="Konfirmasi kesepakatan pesanan">
                            <svg class="w-4 h-4 shrink-0 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Konfirmasi Deal</span>
                        </button>
                    </form>
                @endif

                {{-- Buat Batch Produksi (DEAL / BAHAN with remaining unbatched qty and confirmed DP) --}}
                @if($canCreateBatch)
                    <a href="{{ route('admin.orders.batches.create', $order) }}"
                       class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Buat Batch Produksi</span>
                    </a>
                @elseif(in_array($order->status, [\App\Enums\OrderStatus::DEAL, \App\Enums\OrderStatus::BAHAN], true) && ($hasRemainingCapacity ?? false) && !($dpStatus['has_confirmed_dp'] ?? false))
                    <button type="button" disabled
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-gray-100 border border-[#FFD8D8] text-[#64748B]/60 text-xs font-semibold cursor-not-allowed shadow-none"
                            title="Belum dapat membuat batch">
                        <svg class="w-4 h-4 shrink-0 text-[#64748B]/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Buat Batch Produksi</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- ── 2. Ringkasan Pesanan (Single 2-Column Hub) ──────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="bg-[#FFFCFB] px-4 sm:px-5 py-2.5 border-b border-[#FFD8D8] flex items-center justify-between">
                <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">Ringkasan Pesanan</h2>
                @if($order->customer)
                    <a href="{{ route('admin.customers.show', $order->customer) }}"
                       class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg text-xs font-semibold text-[#8E1B5C] bg-transparent border border-transparent hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer"
                       title="Lihat Profil Customer">
                        <svg class="w-3.5 h-3.5 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                        <span>Lihat Profil Customer</span>
                    </a>
                @endif
            </div>
            <div class="p-4 sm:p-5 grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 text-xs">

                {{-- Left: Profil Customer (Customer Relation Single Source of Truth) --}}
                <div class="space-y-2.5">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Profil Customer</span>
                    <div class="space-y-2 bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#64748B]">Nama:</span>
                            <span class="font-bold text-[#172B4D] text-right">{{ $order->customer->customer_name ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#64748B]">Telepon / WA:</span>
                            <span class="font-medium text-[#172B4D] text-right">{{ $order->customer->phone ?: '-' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-[#64748B] shrink-0">Alamat:</span>
                            <span class="font-normal text-[#172B4D] text-right leading-relaxed">{{ $order->customer->address ?: '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 pt-1 border-t border-[#FFD8D8]/60">
                            <span class="text-[#64748B]">Akun User:</span>
                            <span class="font-mono text-[11px] text-[#172B4D] text-right">{{ $order->customer->user->email ?? 'Tidak terhubung' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Right: Pesanan --}}
                <div class="space-y-2.5">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Pesanan</span>
                    <div class="space-y-2 bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#64748B]">Kuantitas:</span>
                            <span class="font-semibold text-[#172B4D]">{{ $order->items->count() }} varian &middot; {{ number_format($order->total_qty, 0, ',', '.') }} pcs</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#64748B]">Total Transaksi:</span>
                            <span class="font-bold text-[#8E1B5C] text-sm">{{ \App\Support\MoneyFormatter::rupiah($order->total_amount) }}</span>
                        </div>
                        @if($order->notes)
                            <div class="pt-1.5 border-t border-[#FFD8D8]/60">
                                <span class="text-[#64748B] text-[11px] block font-semibold">Catatan:</span>
                                <p class="text-[#172B4D] italic text-xs mt-0.5">{{ $order->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- ── 3. Item Pesanan (Main Transaction Table) ─────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <x-nayara-section-header
                title="Item Pesanan"
                meta="{{ $order->items->count() }} Varian"
            />
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-[0.02em]">
                            <th class="px-4 py-2.5 sm:py-3 text-center w-12 align-middle">No</th>
                            <th class="px-4 py-2.5 sm:py-3 align-middle">Produk &amp; Varian</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap w-[130px] align-middle">SKU</th>
                            <th class="px-4 py-2.5 sm:py-3 text-center whitespace-nowrap w-[140px] align-middle">Rute Produksi</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap w-[110px] align-middle">Qty</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap w-[150px] align-middle">Harga Jual</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap w-[160px] align-middle">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @foreach($order->items as $idx => $item)
                            @php
                                $requiresPrinting = $item->variant?->requires_printing;
                            @endphp
                            <tr class="hover:bg-[#FFFCFB]/70 transition-colors">
                                <td class="px-4 py-3 text-center text-xs text-[#64748B] font-medium">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-[#172B4D] block text-xs">
                                        {{ $item->variant->product->product_name ?? 'Produk' }}
                                    </span>
                                    <span class="text-[11px] text-[#64748B]">
                                        {{ $item->variant->variant_name ?? '-' }}
                                    </span>
                                    @if(is_null($requiresPrinting))
                                        <div class="mt-1.5 flex items-center gap-1.5 text-[11px] text-amber-800 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <span>Rute produksi varian belum ditetapkan. Lengkapi kebutuhan Printing pada Master Produk sebelum membuat Batch.</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $item->variant->sku ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($requiresPrinting === true)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]/30">
                                            Dengan Printing
                                        </span>
                                    @elseif($requiresPrinting === false)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-[#FFD8D8] text-[#64748B] border border-[#FFD8D8]">
                                            Tanpa Printing
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            Belum Ditetapkan
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-xs text-[#172B4D] whitespace-nowrap">
                                    {{ number_format($item->qty_ordered, 0, ',', '.') }} pcs
                                </td>
                                <td class="px-4 py-3 text-right text-xs whitespace-nowrap">
                                    <div class="font-bold text-[#172B4D]">
                                        {{ \App\Support\MoneyFormatter::rupiah($item->unit_selling_price) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-xs text-[#172B4D] whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($item->subtotal) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#FFD8D8] font-bold border-t border-[#FFD8D8]">
                            <td colspan="4" class="px-4 py-2.5 text-xs uppercase tracking-wider text-[#172B4D]">
                                Total Keseluruhan
                            </td>
                            <td class="text-right px-4 py-2.5 text-xs text-[#172B4D] whitespace-nowrap">
                                {{ number_format($order->total_qty, 0, ',', '.') }} pcs
                            </td>
                            <td></td>
                            <td class="text-right px-4 py-2.5 text-sm font-extrabold text-[#8E1B5C] whitespace-nowrap">
                                {{ \App\Support\MoneyFormatter::rupiah($order->total_amount) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>


        {{-- ── 4. Batch Produksi (Overview Only) ────────────────────────────── --}}
        @php
            $allBatches = $order->items->flatMap->productionBatches;
            $isFullyBatched = ! ($hasRemainingCapacity ?? false) && in_array($order->status, [\App\Enums\OrderStatus::DEAL, \App\Enums\OrderStatus::BAHAN], true);
        @endphp
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="bg-[#FFFCFB] px-4 sm:px-5 py-3 border-b border-[#FFD8D8] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-[#172B4D]">Batch Produksi</h2>
                    <p class="text-[11px] text-[#64748B]">Ringkasan penjadwalan produksi per varian item pesanan.</p>
                </div>
                @if($canCreateBatch)
                    <div>
                        <a href="{{ route('admin.orders.batches.create', $order) }}"
                           class="inline-flex items-center justify-center gap-1.5 h-8 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Buat Batch Produksi</span>
                        </a>
                    </div>
                @elseif(in_array($order->status, [\App\Enums\OrderStatus::DEAL, \App\Enums\OrderStatus::BAHAN], true) && ($hasRemainingCapacity ?? false) && !($dpStatus['has_confirmed_dp'] ?? false))
                    <div>
                        <button type="button" disabled
                                class="inline-flex items-center justify-center gap-1.5 h-8 px-3.5 rounded-lg bg-gray-100 border border-[#FFD8D8] text-[#64748B]/60 text-xs font-semibold cursor-not-allowed shadow-none"
                                title="Belum dapat membuat batch">
                            <svg class="w-3.5 h-3.5 shrink-0 text-[#64748B]/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Buat Batch Produksi</span>
                        </button>
                    </div>
                @endif
            </div>

            <div class="p-4 sm:p-5 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    @foreach($order->items as $item)
                        @php
                            $coverage = isset($batchService) ? $batchService->calculateProductionCoverage($item) : null;
                            $remQty = $coverage ? $coverage['remaining_to_plan'] : '0.0000';
                            $commQty = $coverage ? $coverage['production_coverage'] : '0.0000';
                            $confirmedGood = $coverage ? $coverage['confirmed_good_qty'] : '0.0000';
                            $totalReject = $coverage ? $coverage['total_reject_qty'] : '0.0000';
                            $remFloat = (float) $remQty;
                            $isItemDone = bccomp($remQty, '0.0000', 4) <= 0;
                            $productFullName = ($item->variant->product->product_name ?? 'Produk') . ' - ' . ($item->variant->variant_name ?? '-');
                            $itemBatches = $item->productionBatches;
                            $activeBatches = $itemBatches->where('status', '!=', \App\Enums\BatchStatus::CANCELLED);
                            $activeBatchesCount = $activeBatches->count();
                            $hasBatchesAlready = $activeBatches->isNotEmpty();
                            $hasDeficitFromQc = ((float) $totalReject > 0 || (float) $confirmedGood > 0) && $remFloat > 0;
                        @endphp
                        <div class="p-3.5 rounded-xl border {{ $isItemDone ? 'bg-emerald-50/30 border-emerald-200' : 'bg-[#FFFCFB] border-[#FFD8D8]' }} text-xs space-y-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="space-y-0.5 flex-1 min-w-0">
                                    <p class="font-bold text-[#172B4D] truncate" title="{{ $productFullName }}">
                                        {{ $productFullName }}
                                    </p>
                                    <p class="font-mono text-[11px] text-[#64748B]">
                                        {{ $item->variant->sku ?? '-' }}
                                    </p>
                                </div>
                                @if($isItemDone)
                                    <span class="badge badge-success text-[10px] shrink-0">
                                        Lengkap (100%)
                                    </span>
                                @elseif($hasDeficitFromQc)
                                    <span class="badge badge-warning text-[10px] shrink-0">
                                        Produksi Tambahan Diperlukan {{ number_format($remFloat, 0, ',', '.') }} pcs
                                    </span>
                                @else
                                    <span class="badge badge-warning text-[10px] shrink-0">
                                        Sisa {{ number_format($remFloat, 0, ',', '.') }} pcs
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-4 gap-2 pt-1.5 border-t border-[#FFD8D8]/60 text-center">
                                <div class="bg-white p-1.5 rounded-lg border border-[#FFD8D8]/50">
                                    <span class="text-[10px] text-[#64748B] block">Dipesan</span>
                                    <span class="font-bold text-[#172B4D] text-xs">{{ number_format($item->qty_ordered, 0, ',', '.') }} pcs</span>
                                </div>
                                <div class="bg-white p-1.5 rounded-lg border border-[#FFD8D8]/50">
                                    <span class="text-[10px] text-[#64748B] block">Terjadwal</span>
                                    <span class="font-bold text-[#8E1B5C] text-xs">{{ number_format((float) $commQty, 0, ',', '.') }} pcs</span>
                                </div>
                                <div class="bg-white p-1.5 rounded-lg border border-[#FFD8D8]/50">
                                    <span class="text-[10px] text-[#64748B] block">Sisa</span>
                                    <span class="font-bold {{ $remFloat > 0 ? 'text-amber-700' : 'text-emerald-700' }} text-xs">{{ number_format($remFloat, 0, ',', '.') }} pcs</span>
                                </div>
                                <div class="bg-white p-1.5 rounded-lg border border-[#FFD8D8]/50">
                                    <span class="text-[10px] text-[#64748B] block">Batch</span>
                                    <span class="font-bold text-[#172B4D] text-xs">{{ $activeBatchesCount }}</span>
                                </div>
                            </div>

                            {{-- Per-Batch Split Pills / Links --}}
                            @if($itemBatches->isNotEmpty())
                                <div class="pt-2 border-t border-[#FFD8D8]/60 flex flex-wrap items-center gap-1.5">
                                    <span class="text-[10px] font-semibold text-[#64748B]">Daftar Batch:</span>
                                    @foreach($itemBatches as $b)
                                        <a href="{{ route('admin.batches.show', $b) }}"
                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-mono font-medium {{ $b->status === \App\Enums\BatchStatus::CANCELLED ? 'bg-gray-100 text-gray-500 line-through border border-gray-200' : 'bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]/40 hover:bg-[#FFD8D8]' }} transition-colors"
                                           title="Lihat Detail Batch {{ $b->batch_no }}">
                                            <span>{{ $b->batch_no }}</span>
                                            <span class="text-[10px] text-[#64748B]">({{ number_format($b->target_qty, 0, ',', '.') }} pcs)</span>
                                            <span class="badge {{ $b->status->badgeClass() }} text-[9px] py-0 px-1">{{ $b->status->label() }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Direct Batch Creation Action for Incomplete Item --}}
                            @if($remFloat > 0)
                                @if($canCreateBatch)
                                    <div class="pt-2 border-t border-[#FFD8D8]/60 flex items-center justify-between gap-2">
                                        <span class="text-[11px] font-medium text-amber-800">
                                            @if($hasBatchesAlready)
                                                Produksi Tambahan Diperlukan: <strong>{{ number_format($remFloat, 0, ',', '.') }} pcs</strong>
                                            @else
                                                Belum Dijadwalkan: <strong>{{ number_format($remFloat, 0, ',', '.') }} pcs</strong>
                                            @endif
                                        </span>
                                        <a href="{{ route('admin.orders.batches.create', ['order' => $order, 'order_item_id' => $item->id]) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#8E1B5C] text-white text-[11px] font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Buat Batch</span>
                                        </a>
                                    </div>
                                @elseif(in_array($order->status, [\App\Enums\OrderStatus::DEAL, \App\Enums\OrderStatus::BAHAN], true) && !($dpStatus['has_confirmed_dp'] ?? false))
                                    <div class="pt-2 border-t border-[#FFD8D8]/60 flex items-center justify-between gap-2">
                                        <span class="text-[11px] font-medium text-[#64748B]">
                                            @if($hasBatchesAlready)
                                                Produksi Tambahan Diperlukan: <strong>{{ number_format($remFloat, 0, ',', '.') }} pcs</strong>
                                            @else
                                                Belum Dijadwalkan: <strong>{{ number_format($remFloat, 0, ',', '.') }} pcs</strong>
                                            @endif
                                        </span>
                                        <button type="button" disabled
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-gray-100 border border-[#FFD8D8] text-[#64748B]/60 text-[11px] font-semibold cursor-not-allowed shadow-none shrink-0"
                                                title="Belum dapat membuat batch">
                                            <svg class="w-3.5 h-3.5 text-[#64748B]/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Buat Batch</span>
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($allBatches->isEmpty())
                    <div class="p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] text-xs text-[#64748B] text-center">
                        Belum ada batch produksi yang dibuat untuk pesanan ini.
                    </div>
                @elseif($isFullyBatched)
                    <div class="p-2.5 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2 font-medium">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Semua item telah terjadwal.</span>
                    </div>
                @endif

                @if($order->status === \App\Enums\OrderStatus::KOMUNIKASI)
                    <div class="p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] text-xs text-[#64748B] flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#8E1B5C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Batch produksi dapat dibuat setelah pesanan dikonfirmasi <strong>DEAL</strong>.</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── 5. Tagihan & Riwayat Pembayaran (Overview) ───────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-4">
            @if($order->invoice)
                @php
                    $inv = $order->invoice;
                    $paid = isset($invoiceService) ? $invoiceService->calculatePaidAmount($inv) : '0.00';
                    $balance = isset($invoiceService) ? $invoiceService->calculateOutstandingBalance($inv) : (string) $inv->total_amount;
                @endphp
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#FFD8D8] pb-3">
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-sm font-bold text-[#172B4D]">Tagihan</h2>
                        <span class="font-mono font-bold text-xs text-[#8E1B5C] bg-[#FFD8D8] px-2 py-0.5 rounded border border-[#FFD8D8]/40">
                            {{ $inv->invoice_no }}
                        </span>
                        <span class="badge {{ $inv->status->badgeClass() }} text-[10px]">
                            {{ $inv->status->label() }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.invoices.print', $inv) }}"
                           target="_blank"
                           class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-[#8E1B5C] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition-all duration-200 cursor-pointer shadow-xs"
                           title="Cetak Faktur Invoice Pesanan">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.05-.37-2.14-.37-3.254 0-4.694 3.806-8.5 8.5-8.5s8.5 3.806 8.5 8.5c0 1.114-.13 2.204-.37 3.254M17 17v4.5a.75.75 0 01-.75.75H7.75a.75.75 0 01-.75-.75V17m10 0H7m10 0v-4.5a.75.75 0 00-.75-.75H7.75a.75.75 0 00-.75.75V17m2-8.5h.01"/>
                            </svg>
                            <span>Cetak Invoice</span>
                        </a>

                        <a href="{{ route('admin.invoices.show', $inv) }}"
                           class="inline-flex items-center justify-center gap-1.5 h-8 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#68113F] transition-all duration-200 cursor-pointer shadow-xs">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <span>Kelola Pembayaran</span>
                        </a>
                    </div>
                </div>

                {{-- 3-Metric Overview Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]">
                        <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Total Tagihan</span>
                        <span class="font-bold text-[#172B4D] text-sm mt-0.5 block">{{ \App\Support\MoneyFormatter::rupiah($inv->total_amount) }}</span>
                    </div>
                    <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]">
                        <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Terbayar</span>
                        <span class="font-bold text-emerald-700 text-sm mt-0.5 block">{{ \App\Support\MoneyFormatter::rupiah($paid) }}</span>
                    </div>
                    <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]">
                        <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Sisa</span>
                        <span class="font-bold {{ bccomp($balance, '0.00', 2) > 0 ? 'text-red-700' : 'text-[#64748B]' }} text-sm mt-0.5 block">{{ \App\Support\MoneyFormatter::rupiah($balance) }}</span>
                    </div>
                </div>

                {{-- Payment History Overview (Max 3 latest payments) --}}
                <div class="space-y-2 pt-2 border-t border-[#FFD8D8]/60">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">Riwayat Pembayaran</h3>
                        <a href="{{ route('admin.invoices.show', $inv) }}"
                           class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg text-xs font-semibold text-[#8E1B5C] bg-transparent border border-transparent hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer"
                           title="Lihat Riwayat Pembayaran di Detail Invoice">
                            <svg class="w-3.5 h-3.5 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Lihat Riwayat Pembayaran</span>
                        </a>
                    </div>
                    <div class="overflow-x-auto border border-[#FFD8D8] rounded-lg">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                                    <th class="px-3 py-2 text-[10px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Tanggal</th>
                                    <th class="px-3 py-2 text-[10px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Jenis</th>
                                    <th class="px-3 py-2 text-[10px] font-bold text-[#64748B] uppercase tracking-wider">Metode</th>
                                    <th class="px-3 py-2 text-[10px] font-bold text-[#64748B] uppercase tracking-wider text-right whitespace-nowrap">Nominal</th>
                                    <th class="px-3 py-2 text-[10px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FFD8D8]/70">
                                @forelse($inv->payments->sortByDesc('payment_date')->take(3) as $payment)
                                    <tr class="hover:bg-[#FFFCFB]/70 transition-colors">
                                        <td class="px-3 py-2 text-xs text-[#64748B] whitespace-nowrap">
                                            {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="px-3 py-2 font-medium text-[#172B4D] whitespace-nowrap">
                                            {{ $payment->payment_type->value ?? $payment->payment_type }}
                                        </td>
                                        <td class="px-3 py-2 text-[#64748B]">
                                            {{ $payment->payment_method ?? '-' }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-bold text-xs text-[#172B4D] whitespace-nowrap">
                                            {{ \App\Support\MoneyFormatter::rupiah($payment->amount) }}
                                        </td>
                                        <td class="px-3 py-2 text-center whitespace-nowrap">
                                            @if($payment->status === \App\Enums\PaymentStatus::CONFIRMED || $payment->status === 'CONFIRMED')
                                                <span class="badge badge-success text-[10px]">CONFIRMED</span>
                                            @elseif($payment->status === \App\Enums\PaymentStatus::CANCELLED || $payment->status === 'CANCELLED')
                                                <span class="badge badge-danger text-[10px]">CANCELLED</span>
                                            @else
                                                <span class="badge badge-warning text-[10px]">{{ $payment->status }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-3 text-center text-[#64748B] text-xs">
                                            Belum ada pembayaran yang dicatat untuk invoice ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                {{-- Empty State for Tagihan --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <h2 class="text-sm font-bold text-[#172B4D]">Tagihan</h2>
                        @if($order->status === \App\Enums\OrderStatus::KOMUNIKASI)
                            <p class="text-xs text-[#64748B] mt-0.5">Invoice dapat dibuat setelah pesanan DEAL.</p>
                        @elseif($canCreateInvoice)
                            <p class="text-xs text-emerald-700 font-medium mt-0.5">Pesanan sudah memenuhi syarat untuk penerbitan tagihan (Invoice).</p>
                        @else
                            <p class="text-xs text-[#64748B] mt-0.5">Belum ada tagihan yang diterbitkan.</p>
                        @endif
                    </div>
                    @if($canCreateInvoice)
                        <div>
                            <form method="POST" action="{{ route('admin.orders.invoice.store', $order) }}"
                                  onsubmit="return confirm('Terbitkan Invoice untuk pesanan ini? Pesanan akan dikunci dari pengeditan.');">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span>Buat Invoice</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- ── 6. Biaya Terkait (Compact Overview — Rendered ONLY if Attributed Expenses Exist) ── --}}
        @if($order->expenses->isNotEmpty())
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#FFD8D8] pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-[#172B4D]">Biaya Terkait</h2>
                        <p class="text-[11px] text-[#64748B]">{{ $order->expenses->count() }} pengeluaran terkait</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($order->expenses->count() === 1)
                            <a href="{{ route('admin.expenses.show', $order->expenses->first()) }}"
                               class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                               title="Lihat Detail Pengeluaran">
                                <svg class="w-3.5 h-3.5 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Lihat Detail</span>
                            </a>
                        @else
                            <a href="{{ route('admin.expenses.index', ['search' => $order->order_no]) }}"
                               class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                               title="Lihat Semua Pengeluaran Terkait Order">
                                <svg class="w-3.5 h-3.5 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Lihat Detail</span>
                            </a>
                        @endif
                        <a href="{{ route('admin.expenses.create', ['order_id' => $order->id]) }}"
                           class="inline-flex items-center justify-center gap-1.5 h-8 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Catat Pengeluaran</span>
                        </a>
                    </div>
                </div>
                <div class="bg-[#FFFCFB] p-3.5 rounded-lg border border-[#FFD8D8] flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Total Biaya Terkait</span>
                        <span class="font-extrabold text-[#8E1B5C] text-base mt-0.5 block">{{ \App\Support\MoneyFormatter::rupiah($totalAttributedExpenses) }}</span>
                    </div>
                    <div class="text-right text-xs text-[#64748B]">
                        <span class="font-medium text-[#172B4D]">{{ $order->expenses->count() }}</span> pengeluaran terkait
                    </div>
                </div>
            </div>
        @endif

        {{-- ── 7 & 8. Pelacakan Pesanan & Riwayat Status ────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">

            {{-- Pelacakan Pesanan --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-3.5"
                 x-data="{ showRegenerateModal: false, copied: false }">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">Pelacakan Pesanan</h2>
                    </div>
                    @if($order->hasActiveTrackingToken())
                        <span class="badge badge-success text-[10px]">
                            &check; AKTIF
                        </span>
                    @endif
                </div>

                @if(session('raw_tracking_url'))
                    <div class="space-y-2.5 bg-[#FFFCFB] p-3.5 rounded-xl border border-[#FFD8D8]">
                        <div class="flex items-center gap-2 text-emerald-700 text-xs font-semibold">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Tautan tracking baru berhasil dibuat.</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text"
                                   readonly
                                   value="{{ session('raw_tracking_url') }}"
                                   id="trackingInput"
                                   class="w-full h-9 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white font-mono text-[#8E1B5C] font-semibold select-all focus:outline-none flex-1">
                            <button type="button"
                                    @click="navigator.clipboard.writeText('{{ session('raw_tracking_url') }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                    class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shrink-0 shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="copied ? 'Tersalin' : 'Salin Tautan'"></span>
                            </button>
                        </div>
                        <p class="text-[11px] text-[#64748B] leading-relaxed">
                            Salin dan kirim tautan ini kepada customer. Tautan tidak akan ditampilkan lagi setelah halaman dimuat ulang.
                        </p>
                    </div>
                @else
                    <div class="space-y-3 text-xs">
                        <p class="text-[#64748B] text-xs leading-relaxed">
                            Tautan dapat digunakan customer selama token masih aktif.
                        </p>

                        <div class="pt-1">
                            <button type="button"
                                    @click="showRegenerateModal = true"
                                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition-all duration-200 cursor-pointer shadow-xs">
                                <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Regenerasi Tautan</span>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Regenerate Confirmation Modal --}}
                <template x-teleport="body">
                    <div x-show="showRegenerateModal"
                         x-cloak
                         class="fixed inset-0 z-50 overflow-y-auto"
                         aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                            <div x-show="showRegenerateModal"
                                 x-transition:enter="ease-out duration-300"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="ease-in duration-200"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 @click="showRegenerateModal = false"
                                 class="fixed inset-0 bg-[#172B4D]/60 backdrop-blur-xs transition-opacity"
                                 aria-hidden="true"></div>

                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                            <div x-show="showRegenerateModal"
                                 x-transition:enter="ease-out duration-300"
                                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                 x-transition:leave="ease-in duration-200"
                                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                 class="inline-block align-bottom bg-white rounded-2xl p-6 text-left overflow-hidden shadow-modal transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-[#FFD8D8]">
                                
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-200">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-[#172B4D]" id="modal-title">
                                                Regenerasi tautan tracking?
                                            </h3>
                                            <p class="text-xs text-[#64748B] mt-0.5">Konfirmasi regenerasi token pelacakan pesanan</p>
                                        </div>
                                    </div>

                                    <p class="text-xs text-[#64748B] leading-relaxed bg-[#FFFCFB] p-3 rounded-xl border border-[#FFD8D8]">
                                        Link tracking lama akan dinonaktifkan dan tidak dapat digunakan lagi. Tautan baru akan ditampilkan satu kali setelah dibuat.
                                    </p>

                                    <form method="POST" action="{{ route('admin.orders.regenerate-tracking-token', $order) }}" class="flex items-center justify-end gap-3 pt-2">
                                        @csrf
                                        <button type="button"
                                                @click="showRegenerateModal = false"
                                                class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition-all duration-200 cursor-pointer shadow-xs">
                                            Batal
                                        </button>
                                        <button type="submit"
                                                class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            <span>Regenerasi Tautan</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Riwayat Status --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-3.5">
                <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2">Riwayat Status</h2>
                <div class="relative pl-6 space-y-3 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-[#FFD8D8] max-h-64 overflow-y-auto pr-2">
                    @forelse($order->statusHistories->sortByDesc('created_at') as $history)
                        <div class="relative flex items-start justify-between gap-2">
                            <div class="absolute -left-6 top-1 w-4 h-4 rounded-full bg-[#8E1B5C] border-2 border-white shadow-xs"></div>
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="badge {{ $history->status->badgeClass() }} text-[10px]">
                                        {{ $history->status->label() }}
                                    </span>
                                    <span class="text-[11px] text-[#64748B]">
                                        {{ $history->user->name ?? 'Sistem' }} &middot; {{ \App\Support\DateTimeFormatter::formatShortDateTime($history->changed_at ?? $history->created_at) }}
                                    </span>
                                </div>
                                @if($history->notes)
                                    <p class="text-xs text-[#64748B] italic mt-0.5">"{{ $history->notes }}"</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-[#64748B]">Belum ada riwayat status tercatat.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
