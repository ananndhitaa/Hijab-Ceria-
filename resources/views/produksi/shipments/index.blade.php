<x-app-layout pageTitle="Pengiriman & Logistik" breadcrumb="Operasional > Pengiriman">
    <div class="space-y-6">

        {{-- Feature Header --}}
        <x-feature-header
            title="Pengiriman & Logistik"
            subtitle="Pengemasan, ekspedisi, dan pengiriman pesanan."
            icon="truck"
        />

        {{-- ── 1. Hero & Metric Summary Cards ──────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Card 1: Siap Dikirim --}}
            <a href="{{ route('produksi.shipments.index', ['tab' => 'ready']) }}"
               class="bg-white rounded-2xl p-4 sm:p-5 border border-[#FFD8D8] border-l-4 shadow-card hover:shadow-card-hover transition-all duration-200 {{ $tab === 'ready' ? 'border-l-[#8E1B5C] bg-[#FFFCFB]' : 'border-l-[#FFD8D8]' }}">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Antrean Siap Kirim</span>
                    <span class="w-8 h-8 rounded-xl bg-[#FFD8D8] flex items-center justify-center text-[#8E1B5C]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-[#172B4D] font-mono">{{ $readyCount }}</span>
                    <span class="text-xs text-[#64748B]">pesanan</span>
                </div>
                <p class="text-[11px] text-[#64748B] mt-1">Batch selesai & Good Qty lengkap</p>
            </a>

            {{-- Card 2: Dalam Pengiriman (Nayara Neutral / Blue Accent) --}}
            <a href="{{ route('produksi.shipments.index', ['tab' => 'active']) }}"
               class="bg-white rounded-2xl p-4 sm:p-5 border border-[#FFD8D8] border-l-4 shadow-card hover:shadow-card-hover transition-all duration-200 {{ $tab === 'active' ? 'border-l-[#8E1B5C] bg-[#FFFCFB]' : 'border-l-[#8E1B5C]/50' }}">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Dalam Pengiriman</span>
                    <span class="w-8 h-8 rounded-xl bg-[#FFD8D8] flex items-center justify-center text-[#8E1B5C]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-[#172B4D] font-mono">{{ $activeCount }}</span>
                    <span class="text-xs text-[#64748B]">resi/paket</span>
                </div>
                <p class="text-[11px] text-[#64748B] mt-1">Pending, dikemas, atau sedang dikirim</p>
            </a>

            {{-- Card 3: Selesai / Terkirim --}}
            <a href="{{ route('produksi.shipments.index', ['tab' => 'delivered']) }}"
               class="bg-white rounded-2xl p-4 sm:p-5 border border-[#FFD8D8] border-l-4 shadow-card hover:shadow-card-hover transition-all duration-200 {{ $tab === 'delivered' ? 'border-l-[#8E1B5C] bg-[#FFFCFB]' : 'border-l-emerald-500' }}">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Selesai Terkirim</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-[#172B4D] font-mono">{{ $deliveredCount }}</span>
                    <span class="text-xs text-[#64748B]">terkirim</span>
                </div>
                <p class="text-[11px] text-[#64748B] mt-1">Telah diterima oleh pelanggan</p>
            </a>
        </div>

        {{-- ── 2. Navigation Tabs & Filters ────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#FFD8D8] pb-3">
            <div class="flex items-center gap-2 overflow-x-auto">
                <a href="{{ route('produksi.shipments.index', ['tab' => 'ready']) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-150 inline-flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'ready' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-white text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] border border-[#FFD8D8]' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Antrean Siap Kirim</span>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'ready' ? 'bg-white/20 text-white' : 'bg-[#FFD8D8] text-[#8E1B5C]' }}">
                        {{ $readyCount }}
                    </span>
                </a>

                <a href="{{ route('produksi.shipments.index', ['tab' => 'active']) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-150 inline-flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'active' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-white text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] border border-[#FFD8D8]' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                    <span>Dalam Pengiriman</span>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'active' ? 'bg-white/20 text-white' : 'bg-[#FFD8D8] text-[#8E1B5C]' }}">
                        {{ $activeCount }}
                    </span>
                </a>

                <a href="{{ route('produksi.shipments.index', ['tab' => 'delivered']) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-150 inline-flex items-center gap-1.5 whitespace-nowrap {{ $tab === 'delivered' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-white text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] border border-[#FFD8D8]' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Riwayat Selesai</span>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'delivered' ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-700' }}">
                        {{ $deliveredCount }}
                    </span>
                </a>
            </div>

            {{-- Search Input --}}
            <form method="GET" action="{{ route('produksi.shipments.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari pesanan, resi, kurir..."
                           class="h-9 w-48 sm:w-64 pl-8 pr-3 text-xs rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C] transition">
                    <svg class="w-4 h-4 text-[#64748B] absolute left-2.5 top-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                @if($search)
                    <a href="{{ route('produksi.shipments.index', ['tab' => $tab]) }}"
                       class="h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition inline-flex items-center">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- ── 3. Content Table based on Tab ───────────────────────────────── --}}
        @if($tab === 'ready')
            {{-- Antrean Siap Kirim --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-[#FFD8D8] flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-[#172B4D]">Antrean Pesanan Siap Kirim</h2>
                        <p class="text-xs text-[#64748B] mt-0.5">Pesanan pada status <strong>PACKING</strong> yang seluruh batch produksinya telah selesai dan memenuhi Good Qty.</p>
                    </div>
                </div>

                @if($readyOrders->isEmpty())
                    <div class="p-8 text-center text-xs text-[#64748B] space-y-2">
                        <svg class="w-8 h-8 text-[#64748B]/40 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="font-medium text-[#172B4D]">Tidak ada antrean pesanan siap kirim saat ini.</p>
                        <p class="text-[11px] text-[#64748B]">Pesanan akan muncul di sini setelah seluruh batch produksi selesai di tahap Packing dan memenuhi kuantitas pesanan.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">No. Pesanan</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Pelanggan</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Item &amp; Varian</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap text-right">Kuantitas</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap text-center">Status Pesanan</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap text-center align-middle w-[160px]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FFD8D8]">
                                @foreach($readyOrders as $order)
                                    <tr class="hover:bg-[#FFFCFB]/60 transition">
                                        <td class="px-4 py-3 font-mono font-bold text-[#8E1B5C] whitespace-nowrap">
                                            {{ $order->order_no }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="font-semibold text-[#172B4D] block">{{ $order->customer->customer_name ?? '-' }}</span>
                                            <span class="text-[11px] text-[#64748B]">{{ $order->customer->phone ?? '-' }}</span>
                                        </td>
                                        <td class="px-4 py-3 space-y-0.5 whitespace-nowrap">
                                            @foreach($order->items as $item)
                                                <div class="text-[11px] text-[#172B4D]">
                                                    <span class="font-medium">{{ $item->variant->product->product_name ?? 'Produk' }}</span>
                                                    <span class="text-[#64748B]">({{ $item->variant->variant_name ?? '-' }})</span>
                                                    <span class="font-mono text-[#8E1B5C] font-semibold">{{ number_format($item->qty_ordered, 0, ',', '.') }} pcs</span>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="px-4 py-3 text-right font-extrabold text-[#172B4D] text-sm whitespace-nowrap">
                                            {{ number_format($order->total_qty, 0, ',', '.') }} pcs
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="badge {{ $order->status->badgeClass() }} text-[11px]">
                                                {{ $order->status->label() }}
                                            </span>
                                            @php
                                                $fulfillment = $order->getFulfillmentPresentation();
                                            @endphp
                                            @if($fulfillment['state'] === 'READY_TO_SHIP')
                                                <span class="badge badge-success text-[10px] block mt-1">
                                                    Siap Dikirim
                                                </span>
                                            @elseif($fulfillment['state'] === 'WAITING_PAYMENT')
                                                <span class="badge badge-warning text-[10px] block mt-1">
                                                    Menunggu Pelunasan
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center align-middle whitespace-nowrap w-[160px]">
                                            <div class="flex items-center justify-center">
                                                @if($order->isFullyPaid())
                                                    <a href="{{ route('produksi.shipments.create', ['order_id' => $order->id]) }}"
                                                       class="h-8 px-3.5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs inline-flex items-center gap-1.5 cursor-pointer">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                                        </svg>
                                                        <span>Siapkan Pengiriman</span>
                                                    </a>
                                                @else
                                                    <button type="button"
                                                            disabled
                                                            title="Pesanan belum dapat dikirim karena pembayaran belum lunas."
                                                            class="h-8 px-3.5 rounded-xl bg-gray-100 text-gray-400 border border-gray-200 text-xs font-semibold cursor-not-allowed inline-flex items-center gap-1.5 opacity-80">
                                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                        </svg>
                                                        <span>Menunggu Pelunasan</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @else
            {{-- Daftar Pengiriman (Active / Delivered) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-[#FFD8D8] flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-[#172B4D]">
                            {{ $tab === 'active' ? 'Pengiriman Aktif & Dalam Proses' : ($tab === 'delivered' ? 'Riwayat Pengiriman Selesai' : 'Daftar Pengiriman') }}
                        </h2>
                        <p class="text-xs text-[#64748B] mt-0.5">Daftar resi, ekspedisi kurir, dan pemantauan status pengiriman.</p>
                    </div>
                </div>

                @if($shipments->isEmpty())
                    <div class="p-8 text-center text-xs text-[#64748B] space-y-2">
                        <svg class="w-8 h-8 text-[#64748B]/40 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                        </svg>
                        <p class="font-medium text-[#172B4D]">Tidak ada data pengiriman pada tampilan ini.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">No. Pesanan</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Pelanggan</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Kurir &amp; Layanan</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">No. Resi</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap text-center">Status Pengiriman</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap text-center">Status Pesanan</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap text-center align-middle w-[120px]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FFD8D8]">
                                @foreach($shipments as $shipment)
                                    <tr class="hover:bg-[#FFFCFB]/60 transition">
                                        <td class="px-4 py-3 font-mono font-bold text-[#8E1B5C] whitespace-nowrap">
                                            {{ $shipment->order->order_no ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="font-semibold text-[#172B4D] block">{{ $shipment->order->customer->customer_name ?? '-' }}</span>
                                            <span class="text-[11px] text-[#64748B]">{{ $shipment->order->customer->phone ?? '-' }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="font-semibold text-[#172B4D] block">{{ $shipment->courier ?: '-' }}</span>
                                            <span class="text-[11px] text-[#64748B]">{{ $shipment->courier_service ?: 'Reguler' }}</span>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-[#172B4D] whitespace-nowrap">
                                            {{ $shipment->tracking_no ?: '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="badge {{ $shipment->status->badgeClass() }} text-[11px]">
                                                {{ $shipment->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="badge {{ $shipment->order->status->badgeClass() }} text-[11px]">
                                                {{ $shipment->order->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center align-middle whitespace-nowrap w-[120px]">
                                            <div class="flex items-center justify-center">
                                                <a href="{{ route('produksi.shipments.show', $shipment) }}"
                                                   class="inline-flex items-center justify-center gap-2 h-8 px-3.5 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition shadow-xs">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                    <span>Detail</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($shipments->hasPages())
                        <div class="p-4 border-t border-[#FFD8D8]">
                            {{ $shipments->links() }}
                        </div>
                    @endif
                @endif
            </div>
        @endif

    </div>
</x-app-layout>
