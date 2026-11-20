<x-app-layout pageTitle="Detail Pesanan {{ $order->order_no }}" breadcrumb="Pelanggan > Riwayat Pesanan > {{ $order->order_no }}">
    <div class="max-w-5xl mx-auto space-y-6">

        {{-- 1. Top Header --}}
        <x-feature-header
            :title="$order->order_no"
            icon="order"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        PESANAN SAYA
                    </span>
                    <span class="badge {{ $order->status->badgeClass() }} text-xs px-2.5 py-0.5">
                        {{ $order->status->customerLabel() }}
                    </span>
                    @php
                        $fulfillment = $order->getFulfillmentPresentation(isCustomerPortal: true);
                    @endphp
                    @if($order->status === \App\Enums\OrderStatus::PACKING && $fulfillment['state'] === 'READY_TO_SHIP')
                        <span class="badge badge-success text-xs px-2.5 py-0.5">
                            Siap Dikirim
                        </span>
                    @elseif($order->status === \App\Enums\OrderStatus::PACKING && $fulfillment['state'] === 'WAITING_PAYMENT')
                        <span class="badge badge-warning text-xs px-2.5 py-0.5">
                            Menunggu Pelunasan
                        </span>
                    @endif
                </div>
            </x-slot:badge>
            <p class="text-xs text-[#64748B] flex items-center gap-1.5 font-medium mt-1">
                <span>Tanggal Order: {{ $order->order_date ? $order->order_date->format('d M Y') : '-' }}</span>
                <span class="text-[#FFD8D8]">&middot;</span>
                <span class="text-[#172B4D] font-semibold">{{ $order->customer->customer_name ?? '-' }}</span>
            </p>
            <x-slot:actions>
                <a href="{{ route('customer.orders.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali ke Riwayat</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- 2. Customer-Safe Progress Timeline Card (Reusing Public Tracking Mapping) --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card">
            <h2 class="text-xs font-bold uppercase tracking-wider text-[#64748B] mb-5">Progres & Tahapan Pesanan</h2>

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
                if ($currentIdx === false) {
                    $currentIdx = 0;
                }
                $isOrderCompleted = ($order->status === \App\Enums\OrderStatus::SELESAI) || ($fulfillment['state'] === 'COMPLETED');
            @endphp

            <div class="relative border-l-2 border-[#FFD8D8] ml-4 pl-6 space-y-5">
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
                        <div class="absolute -left-[33px] top-0.5 w-4 h-4 rounded-full {{ $isCurrent ? 'bg-[#8E1B5C] ring-4 ring-[#FFD8D8]' : ($isPast ? 'bg-emerald-600' : 'bg-gray-200') }}"></div>
                        <div>
                            <p class="text-sm font-semibold {{ $isCurrent ? 'text-[#8E1B5C]' : ($isPast ? 'text-gray-800' : 'text-gray-400') }}">
                                {{ $stageName }}
                            </p>
                            @if($isCurrent)
                                <p class="text-xs text-[#8E1B5C] font-medium mt-0.5">Sedang dalam tahapan ini</p>
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
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 3. Shipment Details Card (if shipment exists) --}}
        @if($order->latestShipment)
            @php $shipment = $order->latestShipment; @endphp
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 shadow-card space-y-4">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#64748B]">Informasi Pengiriman</h2>
                        <p class="text-xs text-[#64748B] mt-0.5">Rincian ekspedisi dan status pelacakan paket</p>
                    </div>
                    <span class="badge {{ $shipment->status->badgeClass() }} text-xs">
                        {{ $shipment->status->label() }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-[#64748B] block">Kurir / Ekspedisi</span>
                        <span class="font-bold text-[#172B4D] text-sm">{{ $shipment->courier ?: '-' }}</span>
                        @if($shipment->courier_service)
                            <span class="text-[11px] text-[#64748B] block">Layanan: {{ $shipment->courier_service }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-[#64748B] block">No. Resi Pengiriman</span>
                        <span class="font-mono font-bold text-sm text-[#8E1B5C]">{{ $shipment->tracking_no ?: 'Belum terbit' }}</span>
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

                @if($shipment->recipient_address)
                    <div class="pt-2 border-t border-[#FFD8D8] text-xs">
                        <span class="text-[#64748B] block">Alamat Tujuan Pengiriman:</span>
                        <p class="font-medium text-[#172B4D] mt-0.5">{{ $shipment->recipient_address }}</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- 4. Order Items Table (Sanitized, Immutable Snapshots) --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <x-nayara-section-header title="Daftar Item Pesanan" />
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-[0.02em]">
                            <th class="px-4 py-2.5 sm:py-3 w-12 text-center align-middle">No</th>
                            <th class="px-4 py-2.5 sm:py-3 align-middle">Produk & Variasi</th>
                            <th class="px-4 py-2.5 sm:py-3 text-center whitespace-nowrap align-middle">Jumlah</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap align-middle">Harga Satuan</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap align-middle">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @foreach($order->items as $index => $item)
                            @php
                                $itemSubtotal = bcmul((string) $item->qty_ordered, (string) $item->unit_selling_price, 2);
                            @endphp
                            <tr class="hover:bg-[#FFD8D8]/30 transition-colors">
                                <td class="px-4 py-3 text-center text-xs text-[#64748B]">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-xs">
                                    <p class="font-bold text-[#172B4D]">{{ $item->variant->product->product_name ?? 'Produk Hijab' }}</p>
                                    <p class="text-[11px] text-[#64748B] mt-0.5">
                                        Variasi: {{ $item->variant->variant_name ?? '-' }}
                                        @if($item->variant?->sku)
                                            <span class="font-mono text-gray-400 ml-1">({{ $item->variant->sku }})</span>
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-center text-xs whitespace-nowrap font-medium text-[#172B4D]">
                                    {{ number_format((float) $item->qty_ordered, 0, ',', '.') }} pcs
                                </td>
                                <td class="px-4 py-3 text-right text-xs whitespace-nowrap font-medium text-[#64748B]">
                                    {{ \App\Support\MoneyFormatter::rupiah($item->unit_selling_price) }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs whitespace-nowrap font-bold text-[#172B4D]">
                                    {{ \App\Support\MoneyFormatter::rupiah($itemSubtotal) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#FFFCFB] border-t-2 border-[#FFD8D8] font-bold text-xs">
                            <td colspan="2" class="px-4 py-3 text-[#172B4D] uppercase">Total Keseluruhan</td>
                            <td class="px-4 py-3 text-center text-[#8E1B5C] whitespace-nowrap">
                                {{ number_format((float) $totalQty, 0, ',', '.') }} pcs
                            </td>
                            <td></td>
                            <td class="px-4 py-3 text-right text-sm text-[#8E1B5C] whitespace-nowrap">
                                {{ \App\Support\MoneyFormatter::rupiah($orderTotal) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- 5. Invoice & Payment Summary (Authoritative InvoiceService Source of Truth) --}}
        @if($order->invoice)
            @php $invoice = $order->invoice; @endphp
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 shadow-card space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#FFD8D8] pb-3">
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#64748B]">Informasi Tagihan & Pembayaran</h2>
                        <p class="text-xs text-[#64748B] mt-0.5">
                            No. Invoice: <span class="font-mono font-bold text-[#8E1B5C]">{{ $invoice->invoice_no }}</span>
                            &middot; Tanggal: {{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : '-' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge {{ $invoice->status->badgeClass() }} text-xs">
                            {{ $invoice->status->label() }}
                        </span>

                        <a href="{{ route('customer.invoices.print', $invoice) }}"
                           target="_blank"
                           class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-[#8E1B5C] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition-all shadow-xs"
                           title="Cetak Faktur Tagihan">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.05-.37-2.14-.37-3.254 0-4.694 3.806-8.5 8.5-8.5s8.5 3.806 8.5 8.5c0 1.114-.13 2.204-.37 3.254M17 17v4.5a.75.75 0 01-.75.75H7.75a.75.75 0 01-.75-.75V17m10 0H7m10 0v-4.5a.75.75 0 00-.75-.75H7.75a.75.75 0 00-.75.75V17m2-8.5h.01"/>
                            </svg>
                            <span>Cetak Invoice</span>
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-3 bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg">
                        <span class="text-[#64748B] block font-medium">Total Tagihan</span>
                        <span class="text-base font-extrabold text-[#172B4D] mt-1 block">
                            {{ \App\Support\MoneyFormatter::rupiah($invoice->total_amount) }}
                        </span>
                    </div>
                    <div class="p-3 bg-emerald-50/60 border border-emerald-200 rounded-lg">
                        <span class="text-emerald-700 block font-medium">Sudah Dibayar (Terkonfirmasi)</span>
                        <span class="text-base font-extrabold text-emerald-700 mt-1 block">
                            {{ \App\Support\MoneyFormatter::rupiah($paidAmount) }}
                        </span>
                    </div>
                    <div class="p-3 {{ bccomp($outstandingBalance, '0.00', 2) > 0 ? 'bg-amber-50/60 border-amber-200' : 'bg-gray-50 border-[#FFD8D8]' }} border rounded-lg">
                        <span class="{{ bccomp($outstandingBalance, '0.00', 2) > 0 ? 'text-amber-700' : 'text-gray-600' }} block font-medium">Sisa Tagihan</span>
                        <span class="text-base font-extrabold {{ bccomp($outstandingBalance, '0.00', 2) > 0 ? 'text-amber-700' : 'text-gray-600' }} mt-1 block">
                            {{ \App\Support\MoneyFormatter::rupiah($outstandingBalance) }}
                        </span>
                    </div>
                </div>

                {{-- Confirmed Payments List --}}
                @if($invoice->payments->isNotEmpty())
                    <div class="pt-3 border-t border-[#FFD8D8]">
                        <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider mb-2.5">Histori Pembayaran</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[#64748B] text-[11px]">
                                        <th class="px-3 py-2 font-semibold">Tanggal</th>
                                        <th class="px-3 py-2 font-semibold">Metode</th>
                                        <th class="px-3 py-2 font-semibold">Referensi</th>
                                        <th class="px-3 py-2 font-semibold text-right">Nominal</th>
                                        <th class="px-3 py-2 font-semibold text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#FFD8D8]/60">
                                    @foreach($invoice->payments as $payment)
                                        <tr>
                                            <td class="px-3 py-2 text-[#64748B] whitespace-nowrap">
                                                {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}
                                            </td>
                                            <td class="px-3 py-2 font-medium text-[#172B4D] whitespace-nowrap">
                                                {{ $payment->payment_method ?: '-' }}
                                            </td>
                                            <td class="px-3 py-2 font-mono text-[#64748B] text-[11px] whitespace-nowrap">
                                                {{ $payment->reference_no ?: '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-right font-bold text-[#172B4D] whitespace-nowrap">
                                                {{ \App\Support\MoneyFormatter::rupiah($payment->amount) }}
                                            </td>
                                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                                <span class="badge {{ $payment->status->badgeClass() }} text-[10px]">
                                                    {{ $payment->status->label() }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- 6. Order Notes (Customer instruction/note if provided) --}}
        @if($order->notes)
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 shadow-card">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#64748B] mb-2">Catatan Pesanan</h3>
                <p class="text-xs text-[#172B4D] whitespace-pre-line">{{ $order->notes }}</p>
            </div>
        @endif

        {{-- 7. Customer Review & Rating Section --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-4">
            <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3.5">
                <div>
                    <h3 class="text-sm font-bold text-[#172B4D] flex items-center gap-2">
                        <span class="text-amber-500">★</span>
                        <span>Ulasan &amp; Penilaian Pesanan</span>
                    </h3>
                    <p class="text-xs text-[#64748B] mt-0.5">Bagikan pengalaman Anda mengenai kualitas produk hijab dan layanan konveksi.</p>
                </div>
                @if($order->review)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>Ulasan Diterima</span>
                    </span>
                @endif
            </div>

            @if($order->review)
                {{-- Display existing review --}}
                <div class="p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8]/70 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-amber-400 text-sm">
                            @for($i = 1; $i <= $order->review->rating; $i++)
                                <span>★</span>
                            @endfor
                            @for($i = $order->review->rating + 1; $i <= 5; $i++)
                                <span class="text-gray-300">★</span>
                            @endfor
                            <span class="ml-2 text-xs font-bold text-[#172B4D]">{{ $order->review->rating }}.0 / 5.0</span>
                        </div>
                        <span class="text-[11px] text-[#64748B]">{{ $order->review->created_at ? $order->review->created_at->format('d M Y, H:i') : '' }}</span>
                    </div>
                    <p class="text-xs sm:text-sm text-[#172B4D] leading-relaxed italic">
                        "{{ $order->review->review_text }}"
                    </p>
                    <div class="text-[11px] text-[#8E1B5C] font-medium pt-1">
                        Terverifikasi sebagai ulasan dari {{ $order->customer->customer_name ?? 'Pelanggan' }}
                    </div>
                </div>
            @else
                {{-- Form to submit review --}}
                <form action="{{ route('customer.orders.review', $order) }}" method="POST" class="space-y-4" x-data="{ rating: 5 }">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-[#172B4D] mb-1.5">Penilaian Kualitas Produk &amp; Layanan <span class="text-rose-500">*</span></label>
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="rating" :value="rating">
                            <div class="flex items-center gap-1 cursor-pointer">
                                <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                    <button type="button" @click="rating = star" class="text-2xl transition-transform hover:scale-110 focus:outline-none" :class="star <= rating ? 'text-amber-400' : 'text-gray-300'">
                                        ★
                                    </button>
                                </template>
                            </div>
                            <span class="text-xs font-semibold text-[#8E1B5C] ml-2" x-text="rating + ' Bintang'"></span>
                        </div>
                    </div>

                    <div>
                        <label for="review_text" class="block text-xs font-bold text-[#172B4D] mb-1.5">Tulis Ulasan Anda <span class="text-rose-500">*</span></label>
                        <textarea id="review_text"
                                  name="review_text"
                                  rows="3"
                                  required
                                  placeholder="Contoh: Kualitas jahitan hijab sangat rapi, bahannya jatuh dan adem dipakai seharian. Respon konveksi cepat dan pengiriman tepat waktu..."
                                  class="w-full p-3 text-xs sm:text-sm border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition-all"></textarea>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <p class="text-[11px] text-[#64748B]">Ulasan Anda akan tampil di etalase resmi NAYARA sebagai testimoni terverifikasi.</p>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold transition-all shadow-xs cursor-pointer">
                            <span>Kirim Ulasan</span>
                            <span>&rarr;</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>

    </div>
</x-app-layout>
