<x-app-layout pageTitle="Stock Opname Material" breadcrumb="Material & Stok > Material > Stock Opname">
    <div class="max-w-2xl mx-auto space-y-5"
         x-data="{
             currentQty: {{ (float) ($material->stock?->qty_on_hand ?? 0) }},
             avgCost: {{ (float) ($material->stock?->avg_unit_cost ?? 0) }},
             physicalQty: '{{ old('physical_qty', (float)($material->stock?->qty_on_hand ?? 0)) }}',
             unitSymbol: '{{ $material->unit?->symbol ?? '' }}',
             showConfirmModal: false,
             get parsedPhysical() {
                 let val = parseFloat(this.physicalQty);
                 return isNaN(val) ? 0 : val;
             },
             get diff() {
                 return this.parsedPhysical - this.currentQty;
             },
             get diffAbs() {
                 return Math.abs(this.diff);
             },
             get costImpact() {
                 return this.diffAbs * this.avgCost;
             },
             formatQty(val) {
                 return Number(val).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
             },
             formatCurrency(val) {
                 return 'Rp ' + Number(val).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
             }
         }">

        {{-- Header --}}
        <x-feature-header
            :title="$material->material_name"
            subtitle="Penyesuaian stok fisik bahan baku berdasarkan hasil hitung riil di gudang."
            icon="material"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        STOCK OPNAME
                    </span>
                    <span class="badge {{ $material->stock_status_badge }} text-xs">
                        {{ $material->stock_status }}
                    </span>
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('gudang.materials.show', $material) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- System Stock Summary Card (Read-Only) --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card">
            <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider mb-3">
                Informasi Material &amp; Persediaan Sistem
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div class="p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-0.5">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Satuan Unit</span>
                    <span class="text-xs font-bold text-[#172B4D] block">
                        {{ $material->unit?->unit_name }} ({{ $material->unit?->symbol }})
                    </span>
                </div>

                <div class="p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-0.5">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Stok Minimum</span>
                    <span class="text-xs font-mono font-bold text-[#64748B] block">
                        {{ $material->formatted_min_stock }} {{ $material->unit?->symbol }}
                    </span>
                </div>

                <div class="p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-0.5">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Stok Sistem Saat Ini</span>
                    <span class="text-sm font-mono font-black text-[#172B4D] block">
                        {{ $material->formatted_qty }} {{ $material->unit?->symbol }}
                    </span>
                </div>

                <div class="p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-0.5">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Biaya Rata-rata Persediaan</span>
                    <span class="text-xs font-mono font-bold text-[#8E1B5C] block">
                        Rp {{ number_format((float) ($material->stock?->avg_unit_cost ?? 0), 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-5">
            <form x-ref="opnameForm" method="POST" action="{{ route('gudang.materials.stock-opname.store', $material) }}" class="space-y-4">
                @csrf

                {{-- Input Kuantitas Fisik --}}
                <div class="space-y-1.5">
                    <label for="physical_qty" class="block text-xs font-bold text-[#172B4D]">
                        Stok Fisik Hasil Hitung (Opname) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               step="0.0001"
                               min="0"
                               id="physical_qty"
                               name="physical_qty"
                               x-model="physicalQty"
                               class="w-full h-10 px-3.5 pr-14 rounded-xl border {{ $errors->has('physical_qty') ? 'border-red-500 ring-1 ring-red-500' : 'border-[#FFD8D8]' }} bg-white text-sm font-mono font-bold text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition"
                               placeholder="0.0000"
                               required>
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-[#64748B]">
                            {{ $material->unit?->symbol }}
                        </span>
                    </div>
                    @error('physical_qty')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Live Preview Selisih & Dampak Nilai --}}
                <div class="p-4 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] space-y-3">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">
                        Pratinjau Penyesuaian
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-[10px] text-[#64748B] block">Stok Sebelum</span>
                            <span class="font-mono font-bold text-[#172B4D]" x-text="formatQty(currentQty) + ' ' + unitSymbol"></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-[#64748B] block">Stok Sesudah</span>
                            <span class="font-mono font-bold text-[#172B4D]" x-text="formatQty(parsedPhysical) + ' ' + unitSymbol"></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-[#64748B] block">Selisih Penyesuaian</span>
                            <template x-if="diff === 0">
                                <span class="font-mono font-bold text-[#64748B]">0.00 (Sesuai)</span>
                            </template>
                            <template x-if="diff > 0">
                                <span class="font-mono font-bold text-emerald-700" x-text="'+' + formatQty(diff) + ' ' + unitSymbol + ' (Bertambah)'"></span>
                            </template>
                            <template x-if="diff < 0">
                                <span class="font-mono font-bold text-red-700" x-text="'-' + formatQty(diffAbs) + ' ' + unitSymbol + ' (Berkurang)'"></span>
                            </template>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-[#FFD8D8]/80 flex items-center justify-between text-xs">
                        <span class="text-[#64748B]">Estimasi Nilai Selisih Stok:</span>
                        <span class="font-mono font-bold text-[#172B4D]" x-text="formatCurrency(costImpact)"></span>
                    </div>
                </div>

                {{-- Input Catatan --}}
                <div class="space-y-1.5">
                    <label for="notes" class="block text-xs font-bold text-[#172B4D]">
                        Catatan / Alasan Penyesuaian <span class="text-red-500">*</span>
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              class="w-full p-3 rounded-xl border {{ $errors->has('notes') ? 'border-red-500 ring-1 ring-red-500' : 'border-[#FFD8D8]' }} bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition"
                              placeholder="Contoh: Hasil audit fisik bulanan gudang. Ditemukan selisih..."
                              required>{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="pt-2 flex items-center justify-end gap-2">
                    <a href="{{ route('gudang.materials.show', $material) }}"
                       class="inline-flex items-center justify-center h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition">
                        Batal
                    </a>
                    <button type="button"
                            @click="showConfirmModal = true"
                            class="h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                        Simpan Hasil Stock Opname
                    </button>
                </div>
            </form>
        </div>

        {{-- Confirmation Modal --}}
        <div x-show="showConfirmModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
             @click.self="showConfirmModal = false">
            <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl animate-fade-in"
                 @click.stop>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#172B4D]">Simpan hasil stock opname?</h3>
                        <p class="text-xs text-[#64748B]">Material: {{ $material->material_name }}</p>
                    </div>
                </div>

                <p class="text-xs text-[#172B4D] leading-relaxed">
                    Stok sistem akan disesuaikan dengan hasil hitung fisik dan dicatat dalam riwayat mutasi.
                </p>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                    <button type="button"
                            @click="showConfirmModal = false"
                            class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button"
                            @click="$refs.opnameForm.submit()"
                            class="h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                        Simpan Hasil Stock Opname
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
