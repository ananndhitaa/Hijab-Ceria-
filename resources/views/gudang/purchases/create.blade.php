<x-app-layout pageTitle="Buat Purchase Order" breadcrumb="Pembelian > Purchase Order > Buat Baru">
    <div class="max-w-4xl mx-auto space-y-6" x-data="purchaseOrderForm()">

        {{-- 1. Header & Back Action --}}
        <x-feature-header
            title="Buat Purchase Order Baru"
            subtitle="Siapkan rancangan pesanan pembelian material ke supplier (Status DRAFT)."
            icon="purchase"
        >
            <x-slot:badge>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-[#FCEAEF] text-[#8E1B5C] border border-[#E1CFD9] shadow-2xs">
                    <span>Pengadaan</span>
                    <span class="text-[#8E1B5C]/50">›</span>
                    <span>Purchase Order</span>
                </span>
            </x-slot:badge>

            <x-slot:actions>
                <a href="{{ route('gudang.purchases.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-[#E1CFD9] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FCEAEF] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali ke Daftar PO</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- 2. MRP Origin Context Alert (if created from Batch MRP) --}}
        @if($batch)
            <div class="bg-gradient-to-r from-[#FFF5F8] via-[#FFF9FB] to-white border border-[#F0D5E2] rounded-2xl p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0 border border-[#E1CFD9] shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div class="space-y-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[11px] font-extrabold text-[#8E1B5C] uppercase tracking-wider">Sumber Kebutuhan Batch</span>
                            <span class="font-mono font-bold text-xs text-[#520C31] bg-white px-2.5 py-0.5 rounded-full border border-[#E1CFD9] shadow-2xs">
                                {{ $batch->batch_no }}
                            </span>
                            @if($batch->orderItem?->variant?->product)
                                <span class="text-xs text-[#64748B] font-medium truncate">
                                    &bull; {{ $batch->orderItem->variant->product->product_name }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-[#554C51] leading-relaxed">
                            Kekurangan material pada batch ini telah dimasukkan otomatis sebagai rekomendasi pengadaan awal.
                        </p>
                        @if(!empty($prefillItems))
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach($prefillItems as $pf)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-white border border-[#F0D5E2] text-[11px] text-[#1E1B1D]">
                                        <span class="font-semibold text-[#8E1B5C]">{{ $pf['material_name'] }}:</span>
                                        <span class="font-mono font-bold text-[#520C31]">{{ number_format($pf['qty'], 2, ',', '.') }} {{ $pf['unit_symbol'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <a href="{{ route('gudang.material-planning.show', $batch) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl bg-white border border-[#E1CFD9] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FCEAEF] transition shrink-0 shadow-2xs">
                    <span>Lihat Perencanaan Bahan</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            </div>
        @endif

        {{-- 3. Main Form --}}
        <form method="POST" action="{{ route('gudang.purchases.store') }}" @submit="validateBeforeSubmit($event)" class="space-y-6">
            @csrf
            @if($batch)
                <input type="hidden" name="batch_id" value="{{ $batch->id }}">
            @endif

            {{-- SECTION 1: INFORMASI PURCHASE ORDER --}}
            <div class="bg-white border border-[#E1CFD9] rounded-2xl p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-[#F2E6ED] pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-lg bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center">1</div>
                        <h2 class="text-sm font-bold text-[#172B4D] uppercase tracking-wide">
                            Informasi Purchase Order
                        </h2>
                    </div>
                    <span class="text-[11px] text-[#64748B]">Semua kolom bertanda <span class="text-rose-500 font-bold">*</span> wajib diisi</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-1">
                    {{-- Supplier Selector --}}
                    <div>
                        <label for="supplier_id" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                            Pilih Supplier <span class="text-rose-500">*</span>
                        </label>
                        <select id="supplier_id"
                                name="supplier_id"
                                x-model="supplierId"
                                @change="onSupplierChange()"
                                required
                                class="w-full h-10 px-3 rounded-xl border border-[#E1CFD9] bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition font-medium">
                            <option value="">-- Pilih Supplier Aktif --</option>
                            @foreach($suppliers as $s)
                                @php
                                    $neededIds = !empty($prefillItems) ? array_column($prefillItems, 'material_id') : [];
                                    $suppliesNeeded = !empty($neededIds) && $s->materials->pluck('id')->intersect($neededIds)->isNotEmpty();
                                @endphp
                                <option value="{{ $s->id }}" {{ old('supplier_id', $suggestedSupplierId ?? '') == $s->id ? 'selected' : '' }}>
                                    {{ $s->supplier_name }} ({{ $s->phone ?? $s->email ?? 'No Telp/Email' }}) {{ $suppliesNeeded ? '★ [Menyediakan Bahan]' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-[#64748B] mt-1.5">Pilih rekanan supplier yang akan memasok material pesanan ini.</p>
                        @error('supplier_id')
                            <p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror

                        {{-- Supplier Change Alert Banner --}}
                        <div x-show="supplierNotice" x-cloak class="mt-2.5 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-[11px] flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span>⚠️</span>
                                <span x-text="supplierNotice"></span>
                            </div>
                            <button type="button" @click="supplierNotice = ''" class="text-amber-600 hover:text-amber-800 font-bold ml-2 cursor-pointer">&times;</button>
                        </div>
                    </div>

                    {{-- Tanggal PO --}}
                    <div>
                        <label for="purchase_date" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                            Tanggal Purchase Order <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                                id="purchase_date"
                                name="purchase_date"
                                value="{{ old('purchase_date', now('Asia/Jakarta')->toDateString()) }}"
                                required
                                class="w-full h-10 px-3 rounded-xl border border-[#E1CFD9] bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition font-medium">
                        <p class="text-[11px] text-[#64748B] mt-1.5">Tanggal penerbitan dokumen Purchase Order.</p>
                        @error('purchase_date')
                            <p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- SECTION 2: ITEM MATERIAL YANG DIPESAN --}}
            <div class="bg-white border border-[#E1CFD9] rounded-2xl p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-[#F2E6ED] pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-lg bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center">2</div>
                        <h2 class="text-sm font-bold text-[#172B4D] uppercase tracking-wide">
                            Item Material yang Dipesan
                        </h2>
                    </div>
                    <button type="button"
                            @click="addItem()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-[#8E1B5C]/30 bg-[#FCEAEF] text-xs font-bold text-[#8E1B5C] hover:bg-[#8E1B5C] hover:text-white transition shadow-2xs cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Material</span>
                    </button>
                </div>

                @error('items')
                    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                        {{ $message }}
                    </div>
                @enderror

                {{-- Dynamic Items Table --}}
                <div class="overflow-x-auto border border-[#E1CFD9] rounded-xl shadow-2xs">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-[#FAF4F7] text-[#68113F] text-[10.5px] uppercase font-extrabold tracking-wider border-b border-[#E1CFD9]">
                                <th class="py-3 px-3.5">Material</th>
                                <th class="py-3 px-3 w-32 text-right">Qty Pesan</th>
                                <th class="py-3 px-3 w-24 text-center">Satuan</th>
                                <th class="py-3 px-3 w-44 text-right">Harga Beli / Unit</th>
                                <th class="py-3 px-3 w-40 text-right">Subtotal</th>
                                <th class="py-3 px-2 w-12 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F2E6ED]">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-[#FFF9FB] transition">
                                    {{-- Material Select --}}
                                    <td class="py-3 px-3.5">
                                        <div class="space-y-1">
                                            <select :name="`items[${index}][material_id]`"
                                                    x-model="item.material_id"
                                                    @change="onMaterialChange(index)"
                                                    :disabled="!supplierId || availableMaterials.length === 0"
                                                    required
                                                    class="w-full h-9 px-3 rounded-xl border border-[#E1CFD9] bg-white text-xs text-[#172B4D] font-medium focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition disabled:bg-gray-50 disabled:text-[#888185] cursor-pointer">
                                                <option value="" x-text="!supplierId ? '-- Pilih Supplier terlebih dahulu --' : (availableMaterials.length === 0 ? '-- Supplier ini belum memiliki material terdaftar --' : '-- Pilih Material --')"></option>
                                                <template x-for="m in availableMaterials" :key="m.id">
                                                    <option :value="m.id"
                                                            :selected="item.material_id == m.id"
                                                            x-text="m.name">
                                                    </option>
                                                </template>
                                            </select>
                                            <template x-if="item.batch_note">
                                                <p class="text-[10px] text-[#8E1B5C] font-semibold flex items-center gap-1" x-text="item.batch_note"></p>
                                            </template>
                                        </div>
                                    </td>

                                    {{-- Quantity --}}
                                    <td class="py-3 px-3">
                                        <input type="number"
                                               step="0.0001"
                                               min="0.0001"
                                               :name="`items[${index}][qty]`"
                                               x-model.number="item.qty"
                                               placeholder="0.00"
                                               required
                                               class="w-full h-9 px-3 rounded-xl border border-[#E1CFD9] bg-white text-xs font-mono text-right text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                                    </td>

                                    {{-- Unit Symbol --}}
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-block px-2.5 py-1 rounded-lg bg-[#FAF4F7] border border-[#E1CFD9] text-[#68113F] font-mono text-xs font-bold"
                                              x-text="item.unit_symbol || '—'"></span>
                                    </td>

                                    {{-- Unit Price --}}
                                    <td class="py-3 px-3">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs font-semibold text-[#888185] pointer-events-none">Rp</span>
                                            <input type="number"
                                                   step="0.01"
                                                   min="0.01"
                                                   :name="`items[${index}][unit_price]`"
                                                   x-model.number="item.unit_price"
                                                   placeholder="0"
                                                   required
                                                   :class="{'border-amber-400 bg-amber-50/20': !item.unit_price || item.unit_price <= 0}"
                                                   class="w-full h-9 pl-8 pr-3 rounded-xl border border-[#E1CFD9] bg-white text-xs font-mono text-right text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                                        </div>
                                        <template x-if="!item.unit_price || item.unit_price <= 0">
                                            <p class="text-[9.5px] text-amber-700 mt-0.5 text-right font-medium">Harga wajib diisi (> 0)</p>
                                        </template>
                                    </td>

                                    {{-- Subtotal (Client Preview) --}}
                                    <td class="py-3 px-3 text-right font-mono font-bold text-[#1E1B1D]">
                                        <span x-text="formatCurrency(calculateSubtotal(item))"></span>
                                    </td>

                                    {{-- Remove Row Action --}}
                                    <td class="py-3 px-2 text-center">
                                        <button type="button"
                                                @click="removeItem(index)"
                                                :disabled="items.length <= 1"
                                                class="w-8 h-8 inline-flex items-center justify-center rounded-xl text-rose-500 hover:bg-rose-50 disabled:opacity-20 disabled:cursor-not-allowed transition"
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

                {{-- Grand Total Preview Card --}}
                <div class="flex justify-end pt-3">
                    <div class="bg-gradient-to-br from-[#FFF9FB] to-white rounded-2xl p-4 border border-[#E1CFD9] w-72 space-y-2 shadow-2xs">
                        <div class="flex justify-between items-center text-xs text-[#64748B]">
                            <span class="font-medium">Jumlah Baris Material:</span>
                            <span class="font-mono font-bold text-[#172B4D]" x-text="items.length + ' Item'"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs font-extrabold text-[#520C31] border-t border-[#F0D5E2] pt-2">
                            <span>Estimasi Total PO:</span>
                            <span class="font-mono text-base font-bold text-[#8E1B5C]" x-text="formatCurrency(calculateGrandTotal())"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 3: CATATAN & ACTIONS --}}
            <div class="bg-white border border-[#E1CFD9] rounded-2xl p-6 shadow-xs space-y-5">
                <div>
                    <label for="notes" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                        Catatan Pengadaan (Opsional)
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              placeholder="Ketik catatan tambahan pengadaan, instruksi pengiriman khusus, atau termin pembayaran..."
                              class="w-full px-3.5 py-2.5 rounded-xl border border-[#E1CFD9] bg-white text-xs text-[#172B4D] placeholder-[#888185] focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition font-medium">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-[11px] text-rose-600 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-[#F2E6ED]">
                    <div class="flex items-center gap-2 text-[11px] text-[#64748B]">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Dokumen akan dibuat sebagai <strong class="text-[#8E1B5C]">DRAFT</strong> dan dapat dikonfirmasi/diajukan approval.</span>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <a href="{{ route('gudang.purchases.index') }}"
                           class="flex-1 sm:flex-initial inline-flex items-center justify-center h-11 px-5 rounded-xl border border-[#E1CFD9] bg-white text-xs font-bold text-[#172B4D] hover:bg-[#FCEAEF] hover:text-[#8E1B5C] transition shadow-2xs">
                            Batal
                        </a>
                        <button type="submit"
                                :disabled="isSubmitting"
                                class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 h-11 px-6 rounded-xl bg-gradient-to-r from-[#ba4e77] to-[#8E1B5C] hover:from-[#aa3d66] hover:to-[#7b174f] text-white text-xs font-bold shadow-md hover:shadow-lg transition-all active:scale-[0.98] cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg x-show="isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan sebagai Draft PO'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </div>

    {{-- Alpine JS Data Helper --}}
    <script>
        function purchaseOrderForm() {
            const initialPrefill = @json($prefillItems ?? []);
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
                    "{{ $m->id }}": {
                        name: "{{ addslashes($m->material_name) }}",
                        unit: "{{ $m->unit?->symbol ?? '' }}"
                    },
                @endforeach
            };

            // Pre-calculate suggested supplier ID from backend or fallback
            const preselectedSupplierId = '{{ old('supplier_id', $suggestedSupplierId ?? '') }}';

            let initialItems = [];
            if (initialPrefill.length > 0) {
                initialItems = initialPrefill.map(item => ({
                    material_id: item.material_id,
                    qty: item.qty,
                    unit_symbol: item.unit_symbol,
                    unit_price: item.unit_price || '',
                    batch_note: 'Dibutuhkan Batch: ' + item.qty + ' ' + item.unit_symbol
                }));
            } else {
                initialItems = [{ material_id: '', qty: '', unit_symbol: '', unit_price: '', batch_note: '' }];
            }

            return {
                supplierId: preselectedSupplierId,
                supplierNotice: '',
                isSubmitting: false,
                items: initialItems,
                supplierMaterialsMap: supplierMaterialsMap,
                materialsMap: materialsMap,

                get availableMaterials() {
                    return this.supplierMaterialsMap[this.supplierId] || [];
                },

                init() {
                    // If a supplier was preselected and we have prefilled items, ensure unit_symbol is populated
                    if (this.supplierId) {
                        this.items.forEach((item, idx) => {
                            if (item.material_id && !item.unit_symbol) {
                                this.onMaterialChange(idx);
                            }
                        });
                    }
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
                    this.items.push({ material_id: '', qty: '', unit_symbol: '', unit_price: '', batch_note: '' });
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
                },

                validateBeforeSubmit(event) {
                    if (!this.supplierId) {
                        alert('Silakan pilih Supplier aktif terlebih dahulu.');
                        event.preventDefault();
                        return;
                    }

                    for (let i = 0; i < this.items.length; i++) {
                        const item = this.items[i];
                        if (!item.material_id) {
                            alert('Baris ke-' + (i + 1) + ': Silakan pilih Material.');
                            event.preventDefault();
                            return;
                        }
                        if (!item.qty || parseFloat(item.qty) <= 0) {
                            alert('Baris ke-' + (i + 1) + ': Kuantitas (Qty) harus lebih besar dari 0.');
                            event.preventDefault();
                            return;
                        }
                        if (!item.unit_price || parseFloat(item.unit_price) <= 0) {
                            alert('Baris ke-' + (i + 1) + ': Harga beli satuan harus lebih besar dari 0.');
                            event.preventDefault();
                            return;
                        }
                    }

                    this.isSubmitting = true;
                }
            };
        }
    </script>
</x-app-layout>