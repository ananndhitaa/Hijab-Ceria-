<x-app-layout pageTitle="Detail Pengiriman {{ $shipment->order->order_no ?? '' }}" breadcrumb="Pengiriman > Detail">
    <div class="max-w-7xl mx-auto space-y-5" x-data="{ showShipModal: false, showDeliverModal: false }">

        {{-- ── 1. Page Header (NAYARA Feature Header) ────────────────────── --}}
        <x-feature-header
            title="Detail Pengiriman"
            icon="truck"
            title-class="text-[20px] font-semibold"
        >
            <div class="flex items-center gap-2 flex-wrap pt-0.5">
                <span class="text-[12px] sm:text-[13px] font-mono font-semibold text-[#8E1B5C] bg-[#FFD8D8] px-2.5 py-0.5 rounded-lg border border-[#FFD8D8]">
                    {{ $shipment->order->order_no ?? 'Pesanan' }}
                </span>
                @if($shipment->status === \App\Enums\ShipmentStatus::DELIVERED)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[12px] font-medium bg-emerald-50 text-emerald-800 border border-emerald-200">
                        {{ $shipment->status->label() }}
                    </span>
                @elseif($shipment->status === \App\Enums\ShipmentStatus::SHIPPED)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[12px] font-semibold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        DIKIRIM
                    </span>
                @elseif($shipment->status === \App\Enums\ShipmentStatus::RETURNED)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[12px] font-semibold bg-red-50 text-red-800 border border-red-200">
                        {{ $shipment->status->label() }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[12px] font-medium bg-amber-50 text-amber-800 border border-amber-200">
                        {{ $shipment->status->label() }}
                    </span>
                @endif
            </div>
            <p class="text-[13px] text-[#64748B] leading-tight pt-0.5">Informasi ekspedisi dan status pengiriman pesanan.</p>

            <x-slot:actions>
                <a href="{{ route('produksi.shipments.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 sm:h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-[13px] sm:text-sm font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs cursor-pointer">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#64748B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Current Operational Status Card ────────────────────────── --}}
        @if($shipment->status === \App\Enums\ShipmentStatus::PENDING || $shipment->status === \App\Enums\ShipmentStatus::PACKED)
            <div class="bg-[#FFFCFB] border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-card">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] font-semibold text-[#64748B] uppercase tracking-wider">Status Pengiriman</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                {{ $shipment->status->label() }}
                            </span>
                        </div>
                        <p class="text-[13px] sm:text-[14px] text-[#172B4D] font-normal">
                            Pesanan telah selesai dikemas dan siap diserahkan ke pihak ekspedisi / kurir.
                        </p>
                    </div>
                </div>
                <button type="button"
                        @click="showShipModal = true"
                        class="inline-flex items-center justify-center gap-1.5 h-9 sm:h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs shrink-0 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                    <span>Tandai Dikirim</span>
                </button>
            </div>
        @elseif($shipment->status === \App\Enums\ShipmentStatus::SHIPPED)
            <div class="bg-[#FFFCFB] border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-card">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-[15px] font-semibold text-[#172B4D]">Dikirim</h2>
                        <p class="text-[13px] sm:text-[14px] text-[#172B4D] font-normal">
                            Pesanan telah diserahkan ke <strong class="font-medium">{{ $shipment->courier ?: 'ekspedisi' }}</strong> dan sedang dalam perjalanan.
                        </p>
                        <div class="text-[13px] text-[#64748B]">
                            Resi: <span class="font-mono font-semibold text-[#8E1B5C]">{{ $shipment->tracking_no ?: '-' }}</span>
                        </div>
                    </div>
                </div>
                <button type="button"
                        @click="showDeliverModal = true"
                        class="inline-flex items-center justify-center gap-1.5 h-9 sm:h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs shrink-0 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Tandai Diterima</span>
                </button>
            </div>
        @elseif($shipment->status === \App\Enums\ShipmentStatus::DELIVERED)
            <div class="bg-emerald-50 border border-emerald-200/80 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-card">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h2 class="text-[15px] font-semibold text-emerald-900">Pengiriman Selesai</h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">✓ Terkirim</span>
                        </div>
                        <p class="text-[13px] sm:text-[14px] text-emerald-800 font-normal">
                            Pesanan telah diterima oleh pelanggan pada {{ $shipment->delivered_at ? \App\Support\DateTimeFormatter::formatLongDateTime($shipment->delivered_at) : '-' }}.
                        </p>
                    </div>
                </div>
                <div class="shrink-0">
                    <span class="inline-flex items-center gap-1.5 bg-white/90 border border-emerald-200 px-3 py-1 rounded-lg text-[12px] font-semibold text-emerald-800 shadow-2xs">
                        Pesanan: Selesai
                    </span>
                </div>
            </div>
        @elseif($shipment->status === \App\Enums\ShipmentStatus::RETURNED)
            <div class="bg-red-50 border border-red-200/80 rounded-2xl p-4 sm:p-5 flex items-center gap-3 shadow-card">
                <div class="w-9 h-9 rounded-xl bg-red-100 text-red-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div class="space-y-0.5">
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] font-semibold text-red-900 uppercase tracking-wider">Status Pengiriman</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-red-100 text-red-800 border border-red-200">DIKEMBALIKAN</span>
                    </div>
                    <p class="text-[13px] text-red-800 font-normal">
                        Paket pengiriman tercatat dikembalikan (Returned).
                    </p>
                </div>
            </div>
        @endif

        {{-- ── 3. Logistics & Destination Information Card ──────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 divide-y md:divide-y-0 md:divide-x divide-[#FFD8D8]">
                {{-- LEFT: INFORMASI EKSPEDISI --}}
                <div class="space-y-3 pb-4 md:pb-0">
                    <h3 class="text-[12px] font-semibold text-[#64748B] uppercase tracking-wider">
                        Informasi Ekspedisi
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-[12px] sm:text-[13px] font-medium text-[#64748B] block">Kurir</span>
                            <span class="text-[14px] font-medium text-[#172B4D] mt-0.5 block">{{ $shipment->courier ?: '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[12px] sm:text-[13px] font-medium text-[#64748B] block">Layanan</span>
                            <span class="text-[14px] font-medium text-[#172B4D] mt-0.5 block">{{ $shipment->courier_service ?: 'Reguler' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-[12px] sm:text-[13px] font-medium text-[#64748B] block">Nomor Resi</span>
                            <span class="font-mono font-semibold text-[14px] text-[#8E1B5C] mt-0.5 block">{{ $shipment->tracking_no ?: '-' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-[12px] sm:text-[13px] font-medium text-[#64748B] block">Ongkos Kirim</span>
                            <span class="text-[14px] font-medium text-[#172B4D] mt-0.5 block">
                                {{ $shipment->shipping_cost !== null ? 'Rp ' . number_format((float)$shipment->shipping_cost, 0, ',', '.') : 'Rp 0' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: TUJUAN PENGIRIMAN --}}
                <div class="space-y-3 pt-4 md:pt-0 md:pl-6">
                    <h3 class="text-[12px] font-semibold text-[#64748B] uppercase tracking-wider">
                        Tujuan Pengiriman
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <span class="text-[12px] sm:text-[13px] font-medium text-[#64748B] block">Penerima</span>
                            <span class="text-[14px] font-medium text-[#172B4D] mt-0.5 block">{{ $shipment->order->customer->customer_name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[12px] sm:text-[13px] font-medium text-[#64748B] block">Telepon</span>
                            <span class="text-[14px] font-medium text-[#172B4D] mt-0.5 block">{{ $shipment->order->customer->phone ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[12px] sm:text-[13px] font-medium text-[#64748B] block">Alamat Tujuan</span>
                            <p class="text-[13px] sm:text-[14px] text-[#172B4D] font-normal leading-relaxed mt-0.5 whitespace-pre-line">{{ $shipment->recipient_address ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 4. Shipping Timeline ──────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <x-nayara-section-header title="Timeline Pengiriman" />
            <div class="p-4 sm:p-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                    <div class="bg-[#FFFCFB] border border-[#FFD8D8]/80 rounded-lg p-3 space-y-1">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#8E1B5C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span class="text-[13px] font-semibold text-[#172B4D]">Disiapkan</span>
                        </div>
                        <span class="text-[13px] font-normal text-[#172B4D] block">
                            {{ $shipment->created_at ? \App\Support\DateTimeFormatter::formatLongDateTime($shipment->created_at) : '-' }}
                        </span>
                    </div>

                    <div class="bg-[#FFFCFB] border border-[#FFD8D8]/80 rounded-lg p-3 space-y-1">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#8E1B5C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                            <span class="text-[13px] font-semibold text-[#172B4D]">Dikirim</span>
                        </div>
                        @if($shipment->shipped_at)
                            <span class="text-[13px] font-normal text-[#172B4D] block">
                                {{ \App\Support\DateTimeFormatter::formatLongDateTime($shipment->shipped_at) }}
                            </span>
                        @else
                            <span class="text-[13px] font-normal text-[#64748B] block">
                                Belum dikirim
                            </span>
                        @endif
                    </div>

                    <div class="bg-[#FFFCFB] border border-[#FFD8D8]/80 rounded-lg p-3 space-y-1">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-[13px] font-semibold text-[#172B4D]">Diterima</span>
                        </div>
                        @if($shipment->delivered_at)
                            <span class="text-[13px] font-normal text-[#172B4D] block">
                                {{ \App\Support\DateTimeFormatter::formatLongDateTime($shipment->delivered_at) }}
                            </span>
                        @else
                            <span class="text-[13px] font-normal text-[#64748B] block">
                                Belum diterima
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 5. Order & Items Breakdown ────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <x-nayara-section-header
                title="Item Pesanan"
                subtitle="Produk yang termasuk dalam pengiriman ini."
                meta="Total: {{ number_format($shipment->order->total_qty ?? 0, 0, ',', '.') }} pcs"
            />

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-[0.02em] border-b border-[#FFD8D8]">
                        <tr>
                            <th class="py-2.5 sm:py-3 px-4 align-middle">Produk &amp; Varian</th>
                            <th class="py-2.5 sm:py-3 px-4 align-middle">SKU</th>
                            <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Qty Pesanan</th>
                            <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Good Qty</th>
                            <th class="py-2.5 sm:py-3 px-4 text-center align-middle">Status Batch</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($shipment->order->items ?? [] as $item)
                            <tr class="hover:bg-[#FFFCFB] transition-colors">
                                <td class="py-2.5 px-4">
                                    <span class="text-[14px] font-semibold text-[#172B4D] block">{{ $item->variant->product->product_name ?? 'Produk' }}</span>
                                    <span class="text-[13px] font-normal text-[#64748B] block">{{ $item->variant->variant_name ?? '-' }}</span>
                                </td>
                                <td class="py-2.5 px-4 font-mono text-[13px] font-normal text-[#64748B]">
                                    {{ $item->variant->sku ?? '-' }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-medium text-[14px] text-[#172B4D] tabular-nums">
                                    {{ number_format((float)$item->qty_ordered, 0, ',', '.') }} pcs
                                </td>
                                <td class="py-2.5 px-4 text-right font-semibold text-[14px] text-emerald-700 tabular-nums">
                                    {{ number_format((float)$item->qty_ordered, 0, ',', '.') }} pcs
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="badge badge-success text-[12px] font-medium rounded-lg">
                                        SELESAI
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-[13px] text-[#64748B]">
                                    Tidak ada item dalam pesanan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── 6. Notes (Render only if present) ─────────────────────────── --}}
        @if($shipment->notes)
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card space-y-2">
                <h3 class="text-[12px] font-semibold text-[#64748B] uppercase tracking-wider">
                    Catatan Pengiriman
                </h3>
                <p class="text-[13px] text-[#172B4D] leading-relaxed italic bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]">
                    {{ $shipment->notes }}
                </p>
            </div>
        @endif

        {{-- ── 7. Confirmation Modal: Mark Shipped ────────────────────────── --}}
        @if($shipment->status === \App\Enums\ShipmentStatus::PENDING || $shipment->status === \App\Enums\ShipmentStatus::PACKED)
            <div x-show="showShipModal"
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
                 @click.self="showShipModal = false">
                <div class="bg-white rounded-2xl p-5 sm:p-6 max-w-md w-full shadow-2xl space-y-4 border border-[#FFD8D8] animate-fade-in"
                     @click.stop>
                    <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                        <h3 class="text-[15px] sm:text-[16px] font-semibold text-[#172B4D]">
                            Konfirmasi Penyerahan ke Kurir
                        </h3>
                        <button type="button" @click="showShipModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                    </div>

                    <form method="POST" action="{{ route('produksi.shipments.ship', $shipment) }}" class="space-y-4 text-[13px]">
                        @csrf
                        <div class="space-y-2 text-[#64748B]">
                            <p>Pesanan <strong class="text-[#172B4D] font-mono">{{ $shipment->order->order_no }}</strong> akan diperbarui ke status <strong>DIKIRIM</strong>.</p>
                        </div>

                        <div class="space-y-1">
                            <label for="modal_courier" class="block text-[13px] font-medium text-[#172B4D]">Kurir / Ekspedisi</label>
                            <input type="text"
                                   id="modal_courier"
                                   name="courier"
                                   value="{{ old('courier', $shipment->courier) }}"
                                   placeholder="Contoh: JNE, J&T, SiCepat"
                                   class="w-full h-9 sm:h-9 px-3 text-[13px] rounded-lg border border-[#FFD8D8] bg-white focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                        </div>

                        <div class="space-y-1">
                            <label for="modal_tracking_no" class="block text-[13px] font-medium text-[#172B4D]">
                                Nomor Resi Ekspedisi <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="modal_tracking_no"
                                   name="tracking_no"
                                   value="{{ old('tracking_no', $shipment->tracking_no) }}"
                                   placeholder="Masukkan nomor resi dari kurir/ekspedisi..."
                                   class="w-full h-9 sm:h-9 px-3 font-mono text-[13px] rounded-lg border border-[#FFD8D8] bg-white focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]"
                                   required>
                            <p class="text-[11px] text-[#64748B]">Masukkan nomor resi yang diberikan oleh kurir/ekspedisi.</p>
                        </div>

                        <div class="pt-3 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                            <button type="button"
                                    @click="showShipModal = false"
                                    class="h-9 sm:h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-[13px] font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    class="h-9 sm:h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                                Tandai Dikirim
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ── 8. Confirmation Modal: Mark Delivered ──────────────────────── --}}
        @if($shipment->status === \App\Enums\ShipmentStatus::SHIPPED)
            <div x-show="showDeliverModal"
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
                 @click.self="showDeliverModal = false">
                <div class="bg-white rounded-2xl p-5 sm:p-6 max-w-md w-full shadow-2xl space-y-4 border border-[#FFD8D8] animate-fade-in"
                     @click.stop>
                    <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                        <h3 class="text-[15px] sm:text-[16px] font-semibold text-[#172B4D]">
                            Konfirmasi Pesanan Diterima
                        </h3>
                        <button type="button" @click="showDeliverModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                    </div>

                    <form method="POST" action="{{ route('produksi.shipments.deliver', $shipment) }}" class="space-y-4 text-[13px]">
                        @csrf
                        <div class="space-y-2 text-[#64748B]">
                            <p>Pesanan akan ditandai telah diterima dan status Sales Order menjadi <strong>Selesai</strong>.</p>
                            <p>Pastikan paket telah diterima dengan baik oleh pelanggan.</p>
                        </div>

                        <div class="space-y-1">
                            <label for="deliver_notes" class="block text-[13px] font-medium text-[#172B4D]">Catatan Penerimaan (Opsional)</label>
                            <textarea id="deliver_notes"
                                      name="notes"
                                      rows="2"
                                      placeholder="Contoh: Diterima oleh pemesan dalam kondisi baik."
                                      class="w-full p-2.5 rounded-lg border border-[#FFD8D8] bg-white text-[13px] focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]"></textarea>
                        </div>

                        <div class="pt-3 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                            <button type="button"
                                    @click="showDeliverModal = false"
                                    class="h-9 sm:h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-[13px] font-medium text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    class="h-9 sm:h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-[13px] font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                                Tandai Diterima
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
