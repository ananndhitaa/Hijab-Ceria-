<x-app-layout pageTitle="Buat Sales Order">
    <div class="max-w-5xl mx-auto space-y-5"
         x-data="salesOrderForm({{ Js::from($variantsPayload ?? $variants) }}, {{ Js::from(old('items', [['variant_id' => '', 'qty_ordered' => 10, 'unit_selling_price' => '']])) }})">


        {{-- Section A: Header & Back Button --}}
        <x-feature-header
            title="Buat Sales Order Baru"
            subtitle="Isi informasi pemesanan dan rincian produk yang dipesan customer."
            icon="order"
        >
            <x-slot:actions>
                <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center gap-2 h-11 sm:h-12 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Daftar</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Validation Error Summary --}}
        <x-admin.form-errors />

        <form method="POST" action="{{ route('admin.orders.store') }}" class="space-y-5">
            @csrf

            {{-- Section B: Informasi Pemesanan Card --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 space-y-4 shadow-card">
                <div class="border-b border-[#FFD8D8] pb-3">
                    <h2 class="text-sm font-bold text-[#172B4D]">Informasi Pemesanan</h2>
                    <p class="text-xs text-[#64748B] mt-0.5">Data utama customer dan tanggal pemesanan.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    {{-- Customer Selection (8/12 on Desktop) --}}
                    <div class="md:col-span-8 space-y-1.5">
                        <label for="customer_id" class="block text-xs font-semibold text-[#172B4D]">
                            Customer <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id"
                                id="customer_id"
                                class="w-full h-11 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer @error('customer_id') border-red-500 @enderror"
                                required>
                            <option value="">-- Pilih Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('customer_id', request('customer_id')) == $c->id ? 'selected' : '' }}>
                                    {{ $c->customer_name }} {{ $c->phone ? "({$c->phone})" : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Order Date (4/12 on Desktop) --}}
                    <div class="md:col-span-4 space-y-1.5">
                        <label for="order_date" class="block text-xs font-semibold text-[#172B4D]">
                            Tanggal Order <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               name="order_date"
                               id="order_date"
                               value="{{ old('order_date', date('Y-m-d')) }}"
                               class="w-full h-11 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer @error('order_date') border-red-500 @enderror"
                               required>
                        @error('order_date')
                            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Order Notes (Full Width) --}}
                    <div class="md:col-span-12 space-y-1.5">
                        <label for="notes" class="block text-xs font-semibold text-[#172B4D]">
                            Catatan Order (Opsional)
                        </label>
                        <textarea name="notes"
                                  id="notes"
                                  rows="3"
                                  class="w-full min-h-[90px] p-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all resize-y @error('notes') border-red-500 @enderror"
                                  placeholder="Instruksi khusus, alamat pengiriman tambahan, atau detail pesanan lainnya...">{{ old('notes') }}</textarea>
                        <p class="text-[11px] text-[#64748B]">Instruksi khusus atau catatan tambahan yang terkait dengan pesanan ini.</p>
                        @error('notes')
                            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Section C: Rincian Item Pesanan Card --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 space-y-4 shadow-card">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3 gap-3">
                    <div class="space-y-0.5">
                        <h2 class="text-sm font-bold text-[#172B4D]">Rincian Item Pesanan</h2>
                        <p class="text-xs text-[#64748B]">Pilih varian produk, masukkan kuantitas, dan tentukan harga jual.</p>
                    </div>
                    <button type="button"
                            @click="addItem()"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer shrink-0">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Item</span>
                    </button>
                </div>

                {{-- Konveksi MOQ Lock Notice --}}
                <div class="p-3 bg-[#FFF3F7] rounded-lg border border-[#F2CFDC] text-[#8E1B5C] text-xs font-semibold flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">🔒</span>
                        <span><strong>Ketentuan Konveksi:</strong> Minimal pemesanan adalah <strong>10 pcs</strong> per sales order (dapat campur varian). Form pembuatan pesanan dikunci jika kuantitas kurang dari 10 pcs.</span>
                    </div>
                    <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-white text-[#8E1B5C] border border-[#F2CFDC] font-bold shrink-0">MOQ 10 Pcs</span>
                </div>

                {{-- Dynamic Item Cards List --}}
                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="p-4 sm:p-4.5 bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl space-y-3 transition-all shadow-2xs">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]/30"
                                      x-text="'Item #' + (index + 1)"></span>
                                <button type="button"
                                        @click="removeItem(index)"
                                        x-show="items.length > 1"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-red-600 hover:text-red-800 transition-colors cursor-pointer py-1 px-2 rounded-lg hover:bg-red-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span>Hapus Item</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                {{-- Varian Produk (5/12) --}}
                                <div class="md:col-span-5 space-y-1">
                                    <label :for="'variant_' + index" class="block text-xs font-semibold text-[#172B4D]">
                                        Varian Produk <span class="text-red-500">*</span>
                                    </label>
                                    <select :name="'items[' + index + '][variant_id]'"
                                            :id="'variant_' + index"
                                            x-model="item.variant_id"
                                            @change="onVariantChange(index)"
                                            class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer"
                                            required>
                                        <option value="">-- Pilih Varian Produk --</option>
                                        <template x-for="v in variantList" :key="v.id">
                                            <option :value="v.id"
                                                    :selected="item.variant_id == v.id"
                                                    x-text="v.product?.product_name + ' — ' + v.variant_name + ' (' + v.sku + ')'">
                                            </option>
                                        </template>
                                    </select>
                                </div>

                                {{-- Quantity (2/12) --}}
                                <div class="md:col-span-2 space-y-1">
                                    <label :for="'qty_' + index" class="block text-xs font-semibold text-[#172B4D]">
                                        Qty (pcs) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                           :name="'items[' + index + '][qty_ordered]'"
                                           :id="'qty_' + index"
                                           x-model.number="item.qty_ordered"
                                           step="1"
                                           min="1"
                                           class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all text-center font-medium"
                                           placeholder="1"
                                           required>
                                </div>

                                {{-- Harga Jual (3/12) --}}
                                <div class="md:col-span-3 space-y-1">
                                    <label :for="'price_' + index" class="block text-xs font-semibold text-[#172B4D]">
                                        Harga Jual (Rp) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number"
                                               :name="'items[' + index + '][unit_selling_price]'"
                                               :id="'price_' + index"
                                               x-model.number="item.unit_selling_price"
                                               @input="updateMargin(item)"
                                               step="1"
                                               min="0"
                                               class="w-full h-10 px-3 text-xs font-mono font-bold border rounded-lg transition-all text-right"
                                               :class="item.is_below_cost ? 'border-red-500 bg-red-50 text-red-900 focus:ring-red-500/20' : 'border-[#FFD8D8] bg-white text-[#172B4D] focus:border-[#FFD8D8] focus:ring-[#8E1B5C]/15'"
                                               placeholder="0"
                                               required>
                                    </div>
                                </div>

                                {{-- Subtotal (2/12) --}}
                                <div class="md:col-span-2 space-y-1">
                                    <label class="block text-xs font-semibold text-[#172B4D]">Subtotal</label>
                                    <div class="w-full h-10 px-3 bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg text-xs font-bold text-[#8E1B5C] flex items-center justify-end font-mono">
                                        <span x-text="'Rp ' + formatNumber(calculateSubtotal(item))"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Price Error if Selling Price <= HPP Acuan --}}
                            <div x-show="item.is_below_cost && !item.masterPriceWarning" class="text-[11px] text-red-800 bg-red-50 px-3 py-1.5 rounded-lg border border-red-200 flex items-center gap-2">
                                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="font-semibold">Harga jual harus lebih tinggi dari HPP Acuan.</span>
                            </div>

                            {{-- Price Warning if HPP Acuan is not available --}}
                            <div x-show="item.variant_id && (item.reference_cost === null || isNaN(item.reference_cost)) && !item.masterPriceWarning" class="text-[11px] text-amber-800 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>HPP Acuan variant belum tersedia. Lengkapi data biaya produk terlebih dahulu.</span>
                            </div>

                            {{-- Price Warning if Master Price is NULL or <= 0 --}}
                            <div x-show="item.masterPriceWarning" class="text-[11px] text-amber-800 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Harga Master varian belum ditetapkan. Varian ini tidak dapat dipesan.</span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Section D: Ringkasan Pesanan (Order Summary) --}}
                <div class="pt-4 border-t border-[#FFD8D8] space-y-3 bg-white">
                    {{-- Real-time MOQ Lock Warning Alert --}}
                    <div x-show="calculateTotalQty() < 10" class="w-full p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs font-semibold flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Minimal total kuantitas pemesanan konveksi adalah <strong>10 pcs</strong>. Total kuantitas saat ini baru <span x-text="calculateTotalQty()"></span> pcs. Kunci pemesanan aktif.</span>
                        </div>
                        <span class="text-[11px] px-2 py-0.5 rounded bg-red-100 text-red-800 font-bold shrink-0">Kurang <span x-text="10 - calculateTotalQty()"></span> pcs</span>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-3 text-xs w-full sm:w-auto">
                            <div class="px-3 py-2 bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg flex-1 sm:flex-initial text-center sm:text-left">
                                <span class="text-[#64748B]">Total Item:</span>
                                <span class="font-bold text-[#172B4D] ml-1" x-text="items.length"></span>
                            </div>
                            <div class="px-3 py-2 border rounded-lg flex-1 sm:flex-initial text-center sm:text-left transition-colors"
                                 :class="calculateTotalQty() < 10 ? 'bg-red-50 border-red-300 text-red-700' : 'bg-[#FFFCFB] border-[#FFD8D8] text-[#172B4D]'">
                                <span :class="calculateTotalQty() < 10 ? 'text-red-600 font-semibold' : 'text-[#64748B]'">Total Kuantitas:</span>
                                <span class="font-bold ml-1" x-text="calculateTotalQty() + ' pcs'"></span>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-end gap-3 text-right w-full sm:w-auto">
                            <span class="text-xs font-semibold text-[#64748B] uppercase tracking-wider">Grand Total:</span>
                            <span class="text-xl sm:text-2xl font-black text-[#8E1B5C] font-mono tracking-tight" x-text="'Rp ' + formatNumber(calculateGrandTotal())"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section D2: Pembayaran Uang Muka (DP) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 space-y-4 shadow-card" x-data="{ recordDp: {{ old('dp_amount') ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                    <div class="space-y-0.5">
                        <h2 class="text-sm font-bold text-[#172B4D]">Konfirmasi Uang Muka (DP)</h2>
                        <p class="text-xs text-[#64748B]">Catat pembayaran DP jika customer sudah menyepakati pesanan dan mentransfer uang muka.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="recordDp" class="rounded border-[#FFD8D8] text-[#8E1B5C] focus:ring-[#8E1B5C] h-4 w-4">
                        <span class="text-xs font-semibold text-[#8E1B5C]">Sudah Bayar DP (50%)</span>
                    </label>
                </div>

                <div x-show="recordDp" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                    <div class="space-y-1.5">
                        <label for="dp_amount" class="block text-xs font-semibold text-[#172B4D]">
                            Nominal DP (50%) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs text-[#64748B]">Rp</span>
                            <input type="number"
                                   name="dp_amount"
                                   id="dp_amount"
                                   :value="recordDp ? Math.round(calculateGrandTotal() * 0.5) : ''"
                                   value="{{ old('dp_amount') }}"
                                   step="0.01"
                                   class="w-full h-11 pl-9 pr-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] font-mono focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="payment_date" class="block text-xs font-semibold text-[#172B4D]">
                            Tanggal Pembayaran <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               name="payment_date"
                               id="payment_date"
                               value="{{ old('payment_date', date('Y-m-d')) }}"
                               class="w-full h-11 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label for="payment_method" class="block text-xs font-semibold text-[#172B4D]">
                            Metode Pembayaran <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method"
                                id="payment_method"
                                class="w-full h-11 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                            <option value="TRANSFER_BANK" {{ old('payment_method') === 'TRANSFER_BANK' ? 'selected' : '' }}>Transfer Bank</option>
                            <option value="TUNAI" {{ old('payment_method') === 'TUNAI' ? 'selected' : '' }}>Tunai / Cash</option>
                            <option value="QRIS" {{ old('payment_method') === 'QRIS' ? 'selected' : '' }}>QRIS</option>
                        </select>
                    </div>

                    <div class="md:col-span-3 space-y-1.5">
                        <label for="payment_notes" class="block text-xs font-semibold text-[#172B4D]">
                            Catatan Pembayaran (Opsional)
                        </label>
                        <input type="text"
                               name="payment_notes"
                               id="payment_notes"
                               value="{{ old('payment_notes') }}"
                               placeholder="Contoh: DP 50% via BCA a.n Toko Aisyah"
                               class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                    </div>
                </div>
            </div>

            {{-- Section E: Form Action Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.orders.index') }}"
                   class="inline-flex items-center justify-center h-11 px-5 rounded-lg border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    Batal
                </a>
                <button type="submit"
                        :disabled="calculateTotalQty() < 10"
                        :class="calculateTotalQty() < 10 ? 'opacity-50 cursor-not-allowed bg-slate-400 text-slate-100 shadow-none' : 'bg-[#8E1B5C] hover:bg-[#701D49] text-white shadow-xs cursor-pointer'"
                        class="inline-flex items-center justify-center gap-2 h-11 px-6 rounded-lg text-xs sm:text-sm font-semibold transition-all duration-200 ease-out">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Simpan Sales Order</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        function getVariantReferenceCost(variant) {
            if (!variant) return null;
            let cost = variant.reference_cost;
            if (cost !== null && typeof cost === 'object') {
                cost = cost.reference_cost;
            }
            if (cost === null || cost === undefined || cost === '' || isNaN(Number(cost))) {
                return null;
            }
            return Number(cost);
        }

        function getVariantReferenceLabel(variant) {
            if (!variant) return '';
            if (variant.reference_cost_label) return variant.reference_cost_label;
            if (variant.reference_cost && typeof variant.reference_cost === 'object' && variant.reference_cost.label) {
                return variant.reference_cost.label;
            }
            return 'HPP Acuan';
        }

        function salesOrderForm(variants, oldItems) {
            return {
                variantList: variants,
                items: oldItems && oldItems.length > 0
                    ? oldItems.map(item => ({
                        variant_id: item.variant_id || '',
                        qty_ordered: Number(item.qty_ordered) > 0 ? Number(item.qty_ordered) : 10,
                        unit_selling_price: item.unit_selling_price || '',
                        default_master_price: null,
                        reference_cost: null,
                        reference_label: '',
                        margin_per_unit: null,
                        is_below_cost: false,
                        masterPriceWarning: false
                    }))
                    : [{ variant_id: '', qty_ordered: 10, unit_selling_price: '', default_master_price: null, reference_cost: null, reference_label: '', margin_per_unit: null, is_below_cost: false, masterPriceWarning: false }],

                init() {
                    // Initialize prices and reference cost for initial items
                    this.items.forEach((item, idx) => {
                        if (item.variant_id) {
                            const variant = this.variantList.find(v => v.id == item.variant_id);
                            if (variant) {
                                item.default_master_price = variant.base_selling_price ? Number(variant.base_selling_price) : null;
                                if (variant.base_selling_price !== null && Number(variant.base_selling_price) > 0) {
                                    item.unit_selling_price = item.unit_selling_price || String(variant.base_selling_price);
                                    item.masterPriceWarning = false;
                                } else {
                                    item.unit_selling_price = '';
                                    item.masterPriceWarning = true;
                                }

                                item.reference_cost = getVariantReferenceCost(variant);
                                item.reference_label = getVariantReferenceLabel(variant);
                                this.updateMargin(item);
                            }
                        }
                    });
                },

                addItem() {
                    this.items.push({
                        variant_id: '',
                        qty_ordered: 10,
                        unit_selling_price: '',
                        default_master_price: null,
                        reference_cost: null,
                        reference_label: '',
                        margin_per_unit: null,
                        is_below_cost: false,
                        masterPriceWarning: false
                    });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },

                onVariantChange(index) {
                    const row = this.items[index];
                    if (!row.variant_id) {
                        row.unit_selling_price = '';
                        row.reference_cost = null;
                        row.reference_label = '';
                        row.margin_per_unit = null;
                        row.is_below_cost = false;
                        row.masterPriceWarning = false;
                        return;
                    }

                    const variant = this.variantList.find(v => v.id == row.variant_id);
                    if (!variant) return;

                    row.default_master_price = variant.base_selling_price ? Number(variant.base_selling_price) : null;
                    if (variant.base_selling_price !== null && variant.base_selling_price !== undefined && Number(variant.base_selling_price) > 0) {
                        row.unit_selling_price = String(variant.base_selling_price);
                        row.masterPriceWarning = false;
                    } else {
                        row.unit_selling_price = '';
                        row.masterPriceWarning = true;
                    }

                    row.reference_cost = getVariantReferenceCost(variant);
                    row.reference_label = getVariantReferenceLabel(variant);
                    this.updateMargin(row);
                },

                updateMargin(row) {
                    const price = Number(row.unit_selling_price);
                    const hasValidPrice = row.unit_selling_price !== '' && row.unit_selling_price !== null && !isNaN(price);
                    const hasValidCost = row.reference_cost !== null && row.reference_cost !== undefined && !isNaN(row.reference_cost);

                    if (hasValidCost && hasValidPrice) {
                        row.margin_per_unit = price - row.reference_cost;
                        row.is_below_cost = price <= row.reference_cost;
                    } else {
                        row.margin_per_unit = null;
                        row.is_below_cost = false;
                    }
                },

                calculateSubtotal(item) {
                    const qty = Number(item.qty_ordered) || 0;
                    const price = Number(item.unit_selling_price) || 0;
                    return qty * price;
                },

                calculateTotalQty() {
                    return this.items.reduce((sum, item) => sum + (Number(item.qty_ordered) || 0), 0);
                },

                calculateGrandTotal() {
                    return this.items.reduce((sum, item) => sum + this.calculateSubtotal(item), 0);
                },

                formatNumber(val) {
                    return new Intl.NumberFormat('id-ID').format(val || 0);
                }
            };
        }
    </script>
</x-app-layout>
