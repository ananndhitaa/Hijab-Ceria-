<x-guest-layout title="Status Pesanan {{ $order->order_no }} - Hijab ERP">
    <div class="min-h-screen p-4 sm:p-8 bg-[#FFFCFB]">
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl border border-[#FFD8D8] overflow-hidden">
            
            {{-- Header --}}
            <div class="bg-[#8E1B5C] p-6 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-lg font-bold">Lacak Status Pesanan</h1>
                    <p class="text-xs text-[#FFD8D8] font-mono">{{ $order->order_no }}</p>
                </div>
                <a href="{{ route('track.index') }}" class="text-xs text-white/80 hover:text-white underline">Lacak Pesanan Lain</a>
            </div>

            <div class="p-6 space-y-6">
                {{-- Order Summary Header --}}
                <div class="p-4 bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div>
                        <span class="text-[#64748B] block">Pemesan</span>
                        <span class="font-bold text-[#172B4D]">{{ $order->customer->customer_name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-[#64748B] block">Tanggal Order</span>
                        <span class="font-medium text-[#172B4D]">{{ $order->order_date ? $order->order_date->format('d M Y') : '-' }}</span>
                    </div>
                    <div>
                        <span class="text-[#64748B] block">Status Terkini</span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="badge {{ $order->status->badgeClass() }} text-[11px]">
                                {{ $order->status->label() }}
                            </span>
                            @php
                                $fulfillment = $order->getFulfillmentPresentation(isCustomerPortal: false);
                            @endphp
                            @if($order->status === \App\Enums\OrderStatus::PACKING && $fulfillment['state'] === 'READY_TO_SHIP')
                                <span class="badge badge-success text-[11px]">
                                    Siap Dikirim
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($order->status?->value === 'CANCELLED')
                    <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><strong>Pesanan Dibatalkan:</strong> Pesanan ini telah berstatus dibatalkan oleh Admin.</span>
                    </div>
                @endif

                {{-- Shipment Details (if shipment exists) --}}
                @if($order->latestShipment)
                    @php $shipment = $order->latestShipment; @endphp
                    <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-xs space-y-3">
                        <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-2">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-[#64748B]">Informasi Pengiriman</h3>
                                <p class="text-[11px] text-[#64748B]">Rincian ekspedisi dan nomor resi pengiriman</p>
                            </div>
                            <span class="badge {{ $shipment->status->badgeClass() }} text-xs">
                                {{ $shipment->status->label() }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                            <div>
                                <span class="text-[#64748B] block">Kurir / Ekspedisi</span>
                                <span class="font-bold text-[#172B4D]">{{ $shipment->courier ?: '-' }}</span>
                                @if($shipment->courier_service)
                                    <span class="text-[10px] text-[#64748B] block">{{ $shipment->courier_service }}</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-[#64748B] block">No. Resi Pengiriman</span>
                                <span class="font-mono font-medium text-[#8E1B5C]">{{ $shipment->tracking_no ?: 'Belum terbit' }}</span>
                            </div>
                            <div>
                                <span class="text-[#64748B] block">Waktu Dikirim</span>
                                <span class="font-medium text-[#172B4D]">{{ $shipment->shipped_at ? \App\Support\DateTimeFormatter::formatDateTime($shipment->shipped_at) : '-' }}</span>
                            </div>
                            <div>
                                <span class="text-[#64748B] block">Waktu Diterima</span>
                                <span class="font-medium text-[#172B4D]">{{ $shipment->delivered_at ? \App\Support\DateTimeFormatter::formatDateTime($shipment->delivered_at) : '-' }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Order Items (Names and Quantities Only, No Costs/HPP/Prices) --}}
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Daftar Item:</h3>
                    <div class="border border-[#FFD8D8] rounded-xl divide-y divide-[#FFD8D8] overflow-hidden">
                        @foreach($order->items as $item)
                            <div class="p-3 flex justify-between items-center text-xs bg-white">
                                <div>
                                    <span class="font-medium text-[#172B4D]">{{ $item->variant->product->product_name ?? 'Produk' }}</span>
                                    <span class="text-[#64748B] block text-[11px]">{{ $item->variant->variant_name ?? '' }} (SKU: {{ $item->variant->sku ?? '-' }})</span>
                                </div>
                                <span class="font-semibold tabular-nums text-[#8E1B5C]">{{ number_format($item->qty_ordered, 0, ',', '.') }} pcs</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Order Status Timeline --}}
                <div class="space-y-4 pt-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#64748B]">Progres Pesanan:</h3>
                    
                    @php
                        $stages = [
                            \App\Enums\OrderStatus::KOMUNIKASI->value => 'Pesanan Diterima',
                            \App\Enums\OrderStatus::DEAL->value       => 'Dikonfirmasi (Deal)',
                            \App\Enums\OrderStatus::BAHAN->value      => 'Penyiapan Bahan Baku',
                            \App\Enums\OrderStatus::PRINTING->value   => 'Proses Printing Motif',
                            \App\Enums\OrderStatus::PRODUKSI->value   => 'Proses Jahit & Finishing',
                            \App\Enums\OrderStatus::QC->value         => 'Pemeriksaan Kualitas (QC)',
                            \App\Enums\OrderStatus::PACKING->value    => 'Pengemasan Produk',
                            \App\Enums\OrderStatus::DIKIRIM->value    => 'Pesanan Dikirim',
                            \App\Enums\OrderStatus::SELESAI->value    => 'Pesanan Selesai',
                        ];
                        $statusKeys = array_keys($stages);
                        $currentIdx = array_search($order->status->value, $statusKeys, true);
                        if ($currentIdx === false) $currentIdx = 0;
                        $isOrderCompleted = ($order->status === \App\Enums\OrderStatus::SELESAI) || ($fulfillment['state'] === 'COMPLETED');
                    @endphp

                    <div class="relative border-l-2 border-[#FFD8D8] ml-3 pl-6 space-y-4">
                        @foreach($stages as $key => $stageName)
                            @php
                                $stageIdx = array_search($key, $statusKeys, true);
                                $isPast = $stageIdx < $currentIdx;
                                $isCurrent = ($stageIdx === $currentIdx);

                                // If this stage is PACKING and packing is complete, mark it finished
                                if ($key === \App\Enums\OrderStatus::PACKING->value && $fulfillment['timeline_packing_completed']) {
                                    $isPast = true;
                                    $isCurrent = false;
                                }

                                // Pesanan Dikirim is only current if timeline_dikirim_active is true
                                if ($key === \App\Enums\OrderStatus::DIKIRIM->value) {
                                    $isCurrent = $fulfillment['timeline_dikirim_active'];
                                    $isPast = $fulfillment['timeline_dikirim_completed'];
                                }

                                // Terminal state: if order is SELESAI / COMPLETED, ALL stages are complete!
                                if ($isOrderCompleted) {
                                    $isPast = true;
                                    $isCurrent = false;
                                }
                            @endphp
                            <div class="relative">
                                <div class="absolute -left-[31px] top-0.5 w-4 h-4 rounded-full {{ $isCurrent ? 'bg-[#8E1B5C] ring-4 ring-[#FFD8D8]' : ($isPast ? 'bg-emerald-600' : 'bg-gray-200') }}"></div>
                                <p class="text-sm font-semibold {{ $isCurrent ? 'text-[#8E1B5C]' : ($isPast ? 'text-gray-700' : 'text-gray-400') }}">
                                    {{ $stageName }}
                                </p>
                                @if($isCurrent)
                                    <p class="text-xs text-[#8E1B5C] mt-0.5 font-medium">Sedang dalam tahapan ini</p>
                                @elseif($isPast)
                                    <p class="text-[11px] text-emerald-600 mt-0.5 font-medium">Selesai</p>
                                @endif

                                {{-- Context helper when packing is finished but order not yet shipped --}}
                                @if($key === \App\Enums\OrderStatus::PACKING->value && $order->status === \App\Enums\OrderStatus::PACKING && $fulfillment['timeline_packing_completed'])
                                    <div class="mt-2 p-2.5 bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg text-xs flex items-center gap-2">
                                        <span class="badge {{ $fulfillment['badge_class'] }} text-[10px]">
                                            {{ $fulfillment['operational_label'] }}
                                        </span>
                                        <span class="text-[11px] text-[#64748B]">
                                            {{ $fulfillment['customer_message'] }}
                                        </span>
                                    </div>
                                @endif

                                {{-- Context helper when order is completed/delivered --}}
                                @if($key === \App\Enums\OrderStatus::SELESAI->value && $isOrderCompleted)
                                    <div class="mt-2 p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-xs flex items-center gap-2">
                                        <span class="badge badge-success text-[10px]">
                                            Selesai
                                        </span>
                                        <span class="text-[11px] text-emerald-800">
                                            {{ $fulfillment['customer_message'] ?? 'Pesanan telah selesai dan diterima oleh customer.' }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-guest-layout>
