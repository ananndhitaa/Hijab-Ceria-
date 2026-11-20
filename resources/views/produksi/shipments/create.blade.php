<x-app-layout pageTitle="Siapkan Pengiriman Pesanan" breadcrumb="Pengiriman > Siapkan">
    <div class="max-w-4xl mx-auto space-y-5">

        {{-- ── 1. Header & Navigation ────────────────────────────────────── --}}
        <x-feature-header
            :title="$order->order_no"
            subtitle="Penyiapan data logistik dan ekspedisi pengiriman pesanan."
            icon="truck"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        PENGIRIMAN
                    </span>
                    <span class="badge {{ $order->status->badgeClass() }} text-xs">
                        {{ $order->status->label() }}
                    </span>
                    <span class="badge-success text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        Siap Kirim
                    </span>
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('produksi.shipments.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 shrink-0 text-[#64748B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Order Summary Card ─────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div>
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Pemesan &amp; Pelanggan</span>
                    <span class="font-bold text-[#172B4D] mt-0.5 block">{{ $order->customer->customer_name ?? '-' }}</span>
                    <span class="text-[11px] text-[#64748B] block">{{ $order->customer->phone ?? '-' }}</span>
                </div>

                <div>
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Kuantitas Selesai Produksi</span>
                    <span class="font-mono font-bold text-lg text-[#172B4D] mt-0.5 block">{{ number_format($order->total_qty, 0, ',', '.') }} pcs</span>
                    <span class="text-[11px] text-emerald-700 font-semibold block">100% Good Qty Terpenuhi</span>
                </div>

                <div>
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Tanggal Order</span>
                    <span class="font-medium text-[#172B4D] mt-0.5 block">{{ $order->order_date ? $order->order_date->format('d M Y') : '-' }}</span>
                    <span class="text-[11px] text-[#64748B] block">Status: {{ $order->status->label() }}</span>
                </div>
            </div>
        </div>

        {{-- Items breakdown --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card space-y-2">
            <h3 class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Daftar Item Siap Kirim</h3>
            <div class="border border-[#FFD8D8] rounded-xl divide-y divide-[#FFD8D8] overflow-hidden">
                @foreach($order->items as $item)
                    <div class="p-3 bg-white flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-[#172B4D] block">{{ $item->variant->product->product_name ?? 'Produk' }}</span>
                            <span class="text-[#64748B] block text-[11px]">{{ $item->variant->variant_name ?? '-' }} (SKU: {{ $item->variant->sku ?? '-' }})</span>
                        </div>
                        <div class="text-right">
                            <span class="font-mono font-bold text-[#8E1B5C] text-sm block">{{ number_format($item->qty_ordered, 0, ',', '.') }} pcs</span>
                            <span class="text-[10px] text-emerald-700 font-semibold block">Siap Dikemas</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── 3. Shipment Form ─────────────────────────────────────────── --}}
        <form method="POST" action="{{ route('produksi.shipments.store') }}" class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-5">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">

            <div>
                <h2 class="text-base font-bold text-[#172B4D]">Detail Pengiriman</h2>
                <p class="text-xs text-[#64748B] mt-0.5">Lengkapi informasi ekspedisi, nomor resi pengiriman, dan alamat tujuan.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Courier --}}
                <div class="space-y-1">
                    <label for="courier" class="block text-xs font-bold text-[#172B4D]">
                        Nama Kurir / Ekspedisi <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="courier"
                           name="courier"
                           value="{{ old('courier') }}"
                           placeholder="Contoh: JNE, J&T, SiCepat, Kurir Internal"
                           class="w-full h-9 px-3 text-xs rounded-xl border border-[#FFD8D8] bg-white focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C] @error('courier') border-red-500 @enderror"
                           required>
                    @error('courier')
                        <p class="text-[11px] text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Courier Service --}}
                <div class="space-y-1">
                    <label for="courier_service" class="block text-xs font-bold text-[#172B4D]">
                        Layanan Ekspedisi
                    </label>
                    <input type="text"
                           id="courier_service"
                           name="courier_service"
                           value="{{ old('courier_service', 'Reguler') }}"
                           placeholder="Contoh: REG, YES, Cargo, Same Day"
                           class="w-full h-9 px-3 text-xs rounded-xl border border-[#FFD8D8] bg-white focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C] @error('courier_service') border-red-500 @enderror">
                    @error('courier_service')
                        <p class="text-[11px] text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tracking Number / Resi --}}
                <div class="space-y-1">
                    <label for="tracking_no" class="block text-xs font-bold text-[#172B4D]">
                        Nomor Resi Ekspedisi
                    </label>
                    <input type="text"
                           id="tracking_no"
                           name="tracking_no"
                           value="{{ old('tracking_no') }}"
                           placeholder="Masukkan nomor resi dari ekspedisi..."
                           class="w-full h-9 px-3 text-xs font-mono rounded-xl border border-[#FFD8D8] bg-white focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C] @error('tracking_no') border-red-500 @enderror">
                    <p class="text-[10px] text-[#64748B]">Masukkan nomor resi yang diberikan oleh kurir/ekspedisi (wajib jika langsung dikirim).</p>
                    @error('tracking_no')
                        <p class="text-[11px] text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Shipping Cost (Operational) --}}
                <div class="space-y-1">
                    <label for="shipping_cost" class="block text-xs font-bold text-[#172B4D]">
                        Ongkos Kirim (Rp)
                    </label>
                    <input type="number"
                           id="shipping_cost"
                           name="shipping_cost"
                           value="{{ old('shipping_cost', 0) }}"
                           min="0"
                           step="1000"
                           class="w-full h-9 px-3 text-xs rounded-xl border border-[#FFD8D8] bg-white focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C] @error('shipping_cost') border-red-500 @enderror">
                    <p class="text-[10px] text-[#64748B]">Catatan biaya operasional pengiriman (tidak mengubah tagihan/invoice).</p>
                    @error('shipping_cost')
                        <p class="text-[11px] text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Recipient Address Snapshot --}}
            <div class="space-y-1">
                <label for="recipient_address" class="block text-xs font-bold text-[#172B4D]">
                    Alamat Tujuan Pengiriman
                </label>
                <textarea id="recipient_address"
                          name="recipient_address"
                          rows="3"
                          placeholder="Masukkan alamat lengkap tujuan pengiriman..."
                          class="w-full p-3 text-xs rounded-xl border border-[#FFD8D8] bg-white focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C] @error('recipient_address') border-red-500 @enderror">{{ old('recipient_address', $order->customer->address) }}</textarea>
                <p class="text-[10px] text-[#64748B]">Alamat ini disimpan sebagai snapshot saat pengiriman dibuat.</p>
                @error('recipient_address')
                    <p class="text-[11px] text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notes --}}
            <div class="space-y-1">
                <label for="notes" class="block text-xs font-bold text-[#172B4D]">
                    Catatan Pengiriman (Opsional)
                </label>
                <textarea id="notes"
                          name="notes"
                          rows="2"
                          placeholder="Tambahkan catatan khusus pengemasan / pengiriman..."
                          class="w-full p-3 text-xs rounded-xl border border-[#FFD8D8] bg-white focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C] @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="text-[11px] text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="pt-4 border-t border-[#FFD8D8] flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('produksi.shipments.index') }}"
                   class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition flex items-center justify-center">
                    Batal
                </a>

                <button type="submit"
                        name="action"
                        value="draft"
                        class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-xs cursor-pointer">
                    Simpan Draf (Pending)
                </button>

                <button type="submit"
                        name="action"
                        value="ship"
                        class="h-9 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                    <span>Simpan &amp; Tandai Dikirim</span>
                </button>
            </div>

        </form>

    </div>
</x-app-layout>
