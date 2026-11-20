<x-app-layout pageTitle="Edit Sales Order" breadcrumb="Sales Order > Edit">
    @php
        $existingItems = old('items', $order->items->map(function ($item) use ($variantCostService) {
            $ref = isset($variantCostService) && $item->variant ? $variantCostService->getReferenceCost($item->variant) : null;
            return [
                'order_item_id'               => $item->id,
                'variant_id'                  => $item->variant_id,
                'original_variant_id'         => $item->variant_id,
                'original_unit_selling_price' => (string) $item->unit_selling_price,
                'qty_ordered'                 => (float) $item->qty_ordered,
                'unit_selling_price'          => (string) $item->unit_selling_price,
                'default_master_price'        => $item->variant && $item->variant->base_selling_price ? (float) $item->variant->base_selling_price : null,
                'reference_cost'              => $ref && $ref['has_reference'] ? (float) $ref['reference_cost'] : null,
                'reference_label'             => $ref['label'] ?? 'HPP Acuan',
            ];
        })->toArray());
    @endphp

    <div class="max-w-5xl mx-auto space-y-4 sm:space-y-5"
         x-data="salesOrderEditForm({{ Js::from($variantsPayload ?? $variants) }}, {{ Js::from($existingItems) }})">

        {{-- ── 1. Header & Navigation ────────────────────────────────────── --}}
        <x-feature-header
            title="Edit Sales Order"
            subtitle="Perbarui data customer, tanggal pemesanan, atau rincian item produk."
            icon="order"
        >
            <x-slot:badge>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="font-mono text-xs font-bold text-[#8E1B5C] bg-[#FFD8D8] px-2.5 py-0.5 rounded-lg border border-[#FFD8D8]">
                        {{ $order->order_no }}
                    </span>
                    <span class="badge {{ $order->status->badgeClass() }} text-xs px-2.5 py-0.5">
                        {{ $order->status->label() }}
                    </span>
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('admin.orders.show', $order) }}"
                   class="inline-flex items-center justify-center gap-2 h-11 sm:h-12 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Kembali ke detail pesanan">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Errors Summary --}}
        <x-admin.form-errors />

        <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="space-y-4 sm:space-y-5">
            @csrf
            @method('PUT')

            {{-- ── 2. Informasi Pemesanan Card ─────────────────────────────── --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card space-y-4">
                <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2">
                    Informasi Pemesanan
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Readonly Order No --}}
                    <div>
                        <label class="block text-xs font-semibold text-[#172B4D] mb-1.5">Nomor Order</label>
                        <div class="w-full h-10 px-3 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg font-mono font-bold text-[#8E1B5C] cursor-not-allowed flex items-center">
                            {{ $order->order_no }}
                        </div>
                        <p class="text-[10px] text-[#64748B] mt-1">Nomor order bersifat permanen &amp; tidak dapat diubah.</p>
                    </div>

                    {{-- Customer --}}
                    <div>
                        <label for="customer_id" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Customer <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id"
                                id="customer_id"
                                class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('customer_id') border-red-500 @enderror"
                                required>
                            <option value="">-- Pilih Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('customer_id', $order->customer_id) == $c->id ? 'selected' : '' }}>
                                    {{ $c->customer_name }} {{ $c->phone ? "({$c->phone})" : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Order Date --}}
                    <div>
                        <label for="order_date" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Tanggal Order <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               name="order_date"
                               id="order_date"
                               value="{{ old('order_date', $order->order_date?->format('Y-m-d')) }}"
                               class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('order_date') border-red-500 @enderror"
                               required>
                        @error('order_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status Display (Read-only) --}}
                    <div>
                        <label class="block text-xs font-semibold text-[#172B4D] mb-1.5">Status Saat Ini</label>
                        <div class="h-10 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] flex items-center">
                            <span class="badge {{ $order->status->badgeClass() }} text-xs font-semibold">
                                {{ $order->status->label() }}
                            </span>
                        </div>
                    </div>

                    {{-- Notes (Full width) --}}
                    <div class="md:col-span-3">
                        <label for="notes" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Catatan Order <span class="text-[#64748B] text-[11px] font-normal">(Opsional)</span>
                        </label>
                        <textarea name="notes"
                                  id="notes"
                                  rows="3"
                                  placeholder="Instruksi khusus, permintaan kemasan, atau catatan pesanan lainnya..."
                                  class="w-full min-h-[90px] p-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('notes') border-red-500 @enderror">{{ old('notes', $order->notes) }}</textarea>
                        @error('notes')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── 3. Item Pesanan Card ────────────────────────────────────── --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card space-y-4">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-2">
                    <div>
                        <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">Item Pesanan</h2>
                        <p class="text-[11px] text-[#64748B]">Ubah kuantitas atau sesuaikan rincian produk pesanan.</p>
                    </div>
                    <button type="button"
                            @click="addItem()"
                            class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-[#8E1B5C]/30 bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#8E1B5C] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-2xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Item</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="p-4 bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl space-y-3 relative">
                            {{-- Hidden order_item_id binding for existing snapshot preservation --}}
                            <input type="hidden" :name="'items[' + index + '][order_item_id]'" :value="item.order_item_id || ''">

                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#8E1B5C]" x-text="'Item #' + (index + 1)"></span>
                                <button type="button"
                                        @click="removeItem(index)"
                                        x-show="items.length > 1"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 transition-colors">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span>Hapus</span>
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
                                            class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]"
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

                                {{-- Qty (2/12) --}}
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
                                           class="w-full h-10 px-3 text-xs font-mono font-semibold border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] text-center"
                                           required>
                                </div>

                                {{-- Harga Jual (3/12) --}}
                                <div class="md:col-span-3 space-y-1">
                                    <label :for="'price_' + index" class="block text-xs font-semibold text-[#172B4D]">
                                        Harga Jual (Rp) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                           :name="'items[' + index + '][unit_selling_price]'"
                                           :id="'price_' + index"
                                           x-model.number="item.unit_selling_price"
                                           @input="updateMargin(item)"
                                           step="1"
                                           min="0"
                                           class="w-full h-10 px-3 text-xs font-mono font-bold border rounded-lg transition-all text-right"
                                           :class="item.is_below_cost ? 'border-red-500 bg-red-50 text-red-900 focus:ring-red-500/20' : 'border-[#FFD8D8] bg-white text-[#172B4D] focus:border-[#8E1B5C] focus:ring-[#8E1B5C]'"
                                           placeholder="0"
                                           required>
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
                            <div x-show="item.is_below_cost && !item.masterPriceWarning" class="text-[11px] text-red-800 bg-red-50 px-3 py-1.5 rounded-lg border border-red-200 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-red-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="font-semibold">Harga jual harus lebih tinggi dari HPP Acuan.</span>
                            </div>

                            {{-- Price Warning if HPP Acuan is not available --}}
                            <div x-show="item.variant_id && (item.reference_cost === null || isNaN(item.reference_cost)) && !item.masterPriceWarning" class="text-[11px] text-amber-800 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>HPP Acuan variant belum tersedia. Lengkapi data biaya produk terlebih dahulu.</span>
                            </div>

                            {{-- Warning if Master Price is NULL or <= 0 --}}
                            <div x-show="item.masterPriceWarning" class="text-[11px] text-amber-800 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-200 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Harga Master varian belum ditetapkan. Varian ini tidak dapat dipesan.</span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Running Grand Total Footer --}}
                <div class="pt-4 border-t border-[#FFD8D8] flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="text-xs text-[#64748B]">
                        <span class="font-bold text-[#172B4D]" x-text="items.length"></span> item &middot;
                        <span class="font-bold text-[#172B4D]" x-text="calculateTotalQty()"></span> pcs
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-[#64748B] uppercase tracking-wider font-bold block">Total</span>
                        <span class="text-xl font-extrabold text-[#8E1B5C]" x-text="'Rp ' + formatNumber(calculateGrandTotal())"></span>
                    </div>
                </div>
            </div>

            {{-- ── 4. Form Action Buttons ──────────────────────────────────── --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.orders.show', $order) }}"
                   class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    Batal
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Simpan Perubahan</span>
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

        function salesOrderEditForm(variants, existingItems) {
            return {
                variantList: variants,
                items: existingItems && existingItems.length > 0
                    ? existingItems.map(item => ({
                        order_item_id: item.order_item_id || null,
                        variant_id: item.variant_id || '',
                        original_variant_id: item.original_variant_id || item.variant_id || null,
                        original_unit_selling_price: item.original_unit_selling_price || item.unit_selling_price || null,
                        qty_ordered: item.qty_ordered || 1,
                        unit_selling_price: item.unit_selling_price || '',
                        default_master_price: item.default_master_price !== undefined && item.default_master_price !== null ? Number(item.default_master_price) : null,
                        reference_cost: item.reference_cost !== undefined && item.reference_cost !== null && !isNaN(Number(item.reference_cost)) ? Number(item.reference_cost) : null,
                        reference_label: item.reference_label || '',
                        margin_per_unit: null,
                        is_below_cost: false,
                        masterPriceWarning: false
                    }))
                    : [{ order_item_id: null, variant_id: '', original_variant_id: null, original_unit_selling_price: null, qty_ordered: 1, unit_selling_price: '', default_master_price: null, reference_cost: null, reference_label: '', margin_per_unit: null, is_below_cost: false, masterPriceWarning: false }],

                init() {
                    this.items.forEach((item) => {
                        if (item.variant_id) {
                            const variant = this.variantList.find(v => v.id == item.variant_id);
                            if (variant) {
                                item.default_master_price = variant.base_selling_price ? Number(variant.base_selling_price) : null;
                                item.reference_cost = getVariantReferenceCost(variant);
                                item.reference_label = getVariantReferenceLabel(variant);
                            }
                            if (item.unit_selling_price && Number(item.unit_selling_price) > 0) {
                                item.masterPriceWarning = false;
                            } else if (variant && (variant.base_selling_price === null || Number(variant.base_selling_price) <= 0)) {
                                item.masterPriceWarning = true;
                            }

                            this.updateMargin(item);
                        }
                    });
                },

                addItem() {
                    this.items.push({
                        order_item_id: null,
                        variant_id: '',
                        original_variant_id: null,
                        original_unit_selling_price: null,
                        qty_ordered: 1,
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
                        row.default_master_price = null;
                        row.reference_cost = null;
                        row.reference_label = '';
                        row.margin_per_unit = null;
                        row.is_below_cost = false;
                        row.masterPriceWarning = false;
                        return;
                    }

                    const variant = this.variantList.find(v => v.id == row.variant_id);
                    if (variant) {
                        row.default_master_price = variant.base_selling_price ? Number(variant.base_selling_price) : null;
                        row.reference_cost = getVariantReferenceCost(variant);
                        row.reference_label = getVariantReferenceLabel(variant);
                    } else {
                        row.default_master_price = null;
                        row.reference_cost = null;
                        row.reference_label = '';
                    }

                    // If changed back to original variant of this existing item: restore historical snapshot!
                    if (row.original_variant_id && row.variant_id == row.original_variant_id && row.original_unit_selling_price) {
                        row.unit_selling_price = String(row.original_unit_selling_price);
                        row.masterPriceWarning = false;
                        this.updateMargin(row);
                        return;
                    }

                    if (!variant) return;

                    if (variant.base_selling_price !== null && variant.base_selling_price !== undefined && Number(variant.base_selling_price) > 0) {
                        row.unit_selling_price = String(variant.base_selling_price);
                        row.masterPriceWarning = false;
                    } else {
                        row.unit_selling_price = '';
                        row.masterPriceWarning = true;
                    }

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
