<x-app-layout pageTitle="Edit Purchase Order" breadcrumb="Pembelian > Purchase Order > Edit Draft">
    <div class="max-w-4xl mx-auto space-y-5" x-data="purchaseOrderEditForm()">

        {{-- Header & Back --}}
        <x-feature-header
            title="Edit Purchase Order (Draft)"
            subtitle="Perbarui rincian pesanan pembelian sebelum dikonfirmasi ke supplier."
            icon="purchase"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-mono text-xs font-bold text-[#8E1B5C] bg-[#FFD8D8] px-2.5 py-0.5 rounded-full border border-[#FFD8D8]">
                        {{ $purchase->purchase_no }}
                    </span>
                    <span class="badge {{ $purchase->status->badgeClass() }} text-xs">
                        {{ $purchase->status->label() }}
                    </span>
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('gudang.purchases.show', $purchase) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form --}}
        <form method="POST" action="{{ route('gudang.purchases.update', $purchase) }}" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Section 1: Informasi PO --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card space-y-4">
                <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2">
                    1. Informasi Purchase Order
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Supplier --}}
                    <div>
                        <label for="supplier_id" class="block text-xs font-semibold text-[#172B4D] mb-1">
                            Pilih Supplier <span class="text-red-500">*</span>
                        </label>
                        <select id="supplier_id"
                                name="supplier_id"
                                x-model="supplierId"
                                @change="onSupplierChange()"
                                required
                                class="w-full h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ old('supplier_id', $purchase->supplier_id) == $s->id ? 'selected' : '' }}>
                                    {{ $s->supplier_name }} {{ ! $s->active ? '(Nonaktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                        @enderror

                        {{-- Supplier Change Notice --}}
                        <div x-show="supplierNotice" x-cloak class="mt-2 p-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[11px] flex items-center justify-between">
                            <span x-text="supplierNotice"></span>
                            <button type="button" @click="supplierNotice = ''" class="text-amber-600 hover:text-amber-800 font-bold ml-2 cursor-pointer">&times;</button>
                        </div>
                    </div>

                    {{-- Tanggal PO --}}
                    <div>
                        <label for="purchase_date" class="block text-xs font-semibold text-[#172B4D] mb-1">
                            Tanggal Purchase Order <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="purchase_date"
                               name="purchase_date"
                               value="{{ old('purchase_date', $purchase->purchase_date?->format('Y-m-d')) }}"
                               required
                               class="w-full h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        @error('purchase_date')
                            <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Section 2: Daftar Item Material --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card space-y-4">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-2">
                    <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                        2. Item Material yang Dipesan
                    </h2>
                    <button type="button"
                            @click="addItem()"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-[#8E1B5C] hover:text-[#8E1B5C] transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Material</span>
                    </button>
                </div>

                @error('items')
                    <p class="text-xs text-red-600 font-semibold">{{ $message }}</p>
                @enderror

                {{-- Dynamic Items Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-[#FFD8D8]/60 text-[#64748B] text-[10px] uppercase font-bold tracking-wider border-b border-[#FFD8D8]">
                                <th class="py-2.5 px-3">Material</th>
                                <th class="py-2.5 px-3 w-32">Qty Pesan</th>
                                <th class="py-2.5 px-3 w-20 text-center">Satuan</th>
                                <th class="py-2.5 px-3 w-40 text-right">Harga Beli / Unit</th>
                                <th class="py-2.5 px-3 w-36 text-right">Subtotal</th>
                                <th class="py-2.5 px-2 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]/60">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-[#FFD8D8]/20 transition">
                                    {{-- Material Select --}}
                                    <td class="py-2 px-3">
                                        <input type="hidden" :name="`items[${index}][purchase_item_id]`" :value="item.purchase_item_id || ''">
                                        <select :name="`items[${index}][material_id]`"
                                                x-model="item.material_id"
                                                @change="onMaterialChange(index)"
                                                :disabled="!supplierId || availableMaterials.length === 0"
                                                required
                                                class="w-full h-8 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] transition disabled:bg-gray-50 disabled:text-[#64748B]/60 cursor-pointer">
                                            <option value="" x-text="!supplierId ? '-- Pilih Supplier terlebih dahulu --' : (availableMaterials.length === 0 ? '-- Supplier ini belum memiliki material terdaftar --' : '-- Pilih Material --')"></option>
                                            <template x-for="m in availableMaterials" :key="m.id">
                                                <option :value="m.id"
                                                        :selected="item.material_id == m.id"
                                                        x-text="m.name">
                                                </option>
                                            </template>
                                        </select>
                                    </td>

                                    {{-- Quantity --}}
                                    <td class="py-2 px-3">
                                        <input type="number"
                                               step="0.0001"
                                               min="0.0001"
                                               :name="`items[${index}][qty]`"
                                               x-model.number="item.qty"
                                               placeholder="0.00"
                                               required
                                               class="w-full h-8 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-mono text-right text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] transition">
                                    </td>

                                    {{-- Unit Symbol (Derived from Material) --}}
                                    <td class="py-2 px-3 text-center text-[#64748B] font-mono text-xs">
                                        <span x-text="item.unit_symbol || '—'"></span>
                                    </td>

                                    {{-- Unit Price --}}
                                    <td class="py-2 px-3">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-[10px] text-[#64748B] pointer-events-none">Rp</span>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0.01"
                                                   :name="`items[${index}][unit_price]`"
                                                   x-model.number="item.unit_price"
                                                   placeholder="0"
                                                   required
                                                   class="w-full h-8 pl-7 pr-2.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-mono text-right text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] transition">
                                        </div>
                                    </td>

                                    {{-- Subtotal (Client Preview) --}}
                                    <td class="py-2 px-3 text-right font-mono font-semibold text-[#172B4D]">
                                        <span x-text="formatCurrency(calculateSubtotal(item))"></span>
                                    </td>

                                    {{-- Remove Row Action --}}
                                    <td class="py-2 px-2 text-center">
                                        <button type="button"
                                                @click="removeItem(index)"
                                                :disabled="items.length <= 1"
                                                class="w-7 h-7 inline-flex items-center justify-center rounded-lg text-red-600 hover:bg-red-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                                                title="Hapus baris">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Grand Total Preview --}}
                <div class="flex justify-end pt-3 border-t border-[#FFD8D8]">
                    <div class="bg-[#FFD8D8]/50 rounded-xl p-3 border border-[#FFD8D8] w-64 space-y-1">
                        <div class="flex justify-between items-center text-xs text-[#64748B]">
                            <span>Jumlah Item:</span>
                            <span class="font-mono font-bold text-[#172B4D]" x-text="items.length + ' item'"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs font-bold text-[#8E1B5C] border-t border-[#FFD8D8]/60 pt-1">
                            <span>Estimasi Total:</span>
                            <span class="font-mono text-sm" x-text="formatCurrency(calculateGrandTotal())"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 3: Catatan & Submit --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card space-y-4">
                <div>
                    <label for="notes" class="block text-xs font-semibold text-[#172B4D] mb-1">
                        Catatan Pengadaan (Opsional)
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              placeholder="Ketik catatan pengadaan, instruksi pengiriman, atau termin pembayaran..."
                              class="w-full px-3 py-2 rounded-xl border border-[#FFD8D8] bg-white text-xs text-[#172B4D] placeholder-[#64748B]/60 focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">{{ old('notes', $purchase->notes) }}</textarea>
                    @error('notes')
                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-[#FFD8D8]">
                    <a href="{{ route('gudang.purchases.show', $purchase) }}"
                       class="h-10 px-4 inline-flex items-center justify-center rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 h-10 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Simpan Perubahan Draft</span>
                    </button>
                </div>
            </div>
        </form>

    </div>

    @php
        $initialItemsData = $purchase->items->map(function ($it) {
            return [
                'purchase_item_id' => $it->id,
                'material_id'      => $it->material_id,
                'qty'              => (float) $it->qty,
                'unit_symbol'      => $it->material?->unit?->symbol ?? '',
                'unit_price'       => (float) $it->unit_price,
            ];
        })->values();
    @endphp

    {{-- Alpine JS Data Helper --}}
    <script>
        function purchaseOrderEditForm() {
            const initialItems = @json($initialItemsData);
            const supplierMaterialsMap = {
                @foreach($suppliers as $s)
                    "{{ $s->id }}": [
                        @foreach($s->materials as $mat)
                            {
                                id: "{{ $mat->id }}",
                                name: "{{ addslashes($mat->material_name) }}",
                                unit: "{{ $mat->unit?->symbol ?? '' }}"
                            },
                        @endforeach
                    ],
                @endforeach
            };

            const materialsMap = {
                @foreach($materials as $m)
                    "{{ $m->id }}": { unit: "{{ $m->unit?->symbol ?? '' }}" },
                @endforeach
            };

            return {
                supplierId: '{{ old('supplier_id', $purchase->supplier_id) }}',
                supplierNotice: '',
                items: initialItems.length > 0 ? initialItems : [{ purchase_item_id: null, material_id: '', qty: '', unit_symbol: '', unit_price: '' }],
                supplierMaterialsMap: supplierMaterialsMap,
                materialsMap: materialsMap,

                get availableMaterials() {
                    return this.supplierMaterialsMap[this.supplierId] || [];
                },

                onSupplierChange() {
                    const allowed = this.availableMaterials.map(m => String(m.id));
                    let resetCount = 0;
                    this.items.forEach(item => {
                        if (item.material_id && !allowed.includes(String(item.material_id))) {
                            item.material_id = '';
                            item.unit_symbol = '';
                            resetCount++;
                        }
                    });
                    if (resetCount > 0) {
                        this.supplierNotice = 'Pilihan material pada ' + resetCount + ' baris telah direset karena tidak tersedia pada supplier terpilih.';
                    } else {
                        this.supplierNotice = '';
                    }
                },

                addItem() {
                    this.items.push({ purchase_item_id: null, material_id: '', qty: '', unit_symbol: '', unit_price: '' });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },

                onMaterialChange(index) {
                    const matId = this.items[index].material_id;
                    const found = this.availableMaterials.find(m => String(m.id) === String(matId));
                    if (found) {
                        this.items[index].unit_symbol = found.unit;
                    } else if (matId && this.materialsMap[matId]) {
                        this.items[index].unit_symbol = this.materialsMap[matId].unit;
                    } else {
                        this.items[index].unit_symbol = '';
                    }
                },

                calculateSubtotal(item) {
                    const q = parseFloat(item.qty) || 0;
                    const p = parseFloat(item.unit_price) || 0;
                    return q * p;
                },

                calculateGrandTotal() {
                    return this.items.reduce((sum, item) => sum + this.calculateSubtotal(item), 0);
                },

                formatCurrency(val) {
                    return 'Rp ' + Number(val).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            };
        }
    </script>
</x-app-layout>
