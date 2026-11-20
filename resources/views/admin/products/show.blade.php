<x-app-layout pageTitle="Detail Produk & Varian">
    <div class="space-y-5" x-data="{ showVariantModal: false }">


        {{-- Section A: Header Card --}}
        <x-feature-header
            :title="$product->product_name"
            :subtitle="$product->description ?? 'Tidak ada deskripsi produk.'"
            icon="product"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="font-mono text-xs font-semibold bg-[#FFD8D8] text-[#8E1B5C] px-2.5 py-0.5 rounded-md border border-[#FFD8D8]">
                        Kode: {{ $product->code }}
                    </span>
                    @if($product->active)
                        <span class="badge-success text-xs px-2.5 py-0.5 rounded-full">Aktif</span>
                    @else
                        <span class="badge-secondary text-xs px-2.5 py-0.5 rounded-full">Nonaktif</span>
                    @endif
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('admin.products.edit', $product) }}"
                   class="inline-flex items-center justify-center gap-2 h-11 sm:h-12 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Produk</span>
                </a>
                <a href="{{ route('admin.products.index') }}"
                   class="inline-flex items-center justify-center gap-2 h-11 sm:h-12 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Daftar</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Variants Section Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="p-4 sm:p-5 border-b border-[#FFD8D8] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-0.5">
                    <h2 class="text-sm font-bold text-[#172B4D]">Daftar Varian Produk</h2>
                    <p class="text-xs text-[#64748B]">Varian memuat SKU, harga jual master, dan resep BOM.</p>
                </div>
                <button type="button"
                        @click="showVariantModal = true"
                        class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer shrink-0">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Varian</span>
                </button>
            </div>

            {{-- Variants Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[180px]">Nama Varian</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[150px]">SKU</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[150px]">Harga Jual Master</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[160px]">HPP / Modal Acuan</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[140px]">Estimasi Margin</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[130px]">Rute Produksi</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[110px]">BOM Items</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[90px]">Status</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[200px]">Aksi / Navigasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @forelse($product->variants as $variant)
                            <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                <td class="px-4 py-3 font-semibold text-xs text-[#172B4D] whitespace-nowrap">
                                    <a href="{{ route('admin.variants.show', $variant) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                        {{ $variant->variant_name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $variant->sku }}
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap">
                                    @if($variant->base_selling_price !== null)
                                        <span class="font-bold text-emerald-700">{{ \App\Support\MoneyFormatter::rupiah($variant->base_selling_price) }}</span>
                                    @else
                                        <span class="text-[#64748B] italic font-normal">Belum ditentukan</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap">
                                    @if(isset($variant->reference_cost_data) && $variant->reference_cost_data['reference_cost'] !== null)
                                        <span class="font-bold text-[#172B4D] font-mono">{{ \App\Support\MoneyFormatter::rupiah($variant->reference_cost_data['reference_cost']) }}</span>
                                        <span class="text-[10px] text-[#64748B] block">({{ $variant->reference_cost_data['label'] }})</span>
                                    @else
                                        <span class="text-[#64748B] italic font-normal">Belum tersedia</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap">
                                    @if(isset($variant->margin_eval) && $variant->margin_eval['margin'] !== null)
                                        @if($variant->margin_eval['is_below_cost'])
                                            <span class="text-red-700 font-bold font-mono">
                                                {{ $variant->margin_eval['margin_per_unit'] >= 0 ? '+Rp ' : '-Rp ' }}{{ number_format(abs($variant->margin_eval['margin_per_unit']), 0, ',', '.') }}
                                            </span>
                                            <span class="text-[10px] text-red-700 font-semibold block" title="Harga jual tidak lebih tinggi dari HPP/modal">⚠️ Harga &le; Modal</span>
                                        @else
                                            <span class="text-emerald-700 font-bold font-mono">
                                                +{{ \App\Support\MoneyFormatter::rupiah($variant->margin_eval['margin']) }}
                                            </span>
                                            @if(!empty($variant->margin_eval['margin_percent']))
                                                <span class="text-[10px] text-emerald-700 font-medium block">
                                                    ({{ number_format((float)$variant->margin_eval['margin_percent'], 2, ',', '.') }}%)
                                                </span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-[#64748B] italic font-normal">Belum tersedia</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($variant->requires_printing === true)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">Dengan Printing</span>
                                    @elseif($variant->requires_printing === false)
                                        <span class="badge-secondary text-[11px]">Tanpa Printing</span>
                                    @else
                                        <span class="badge-warning text-[11px]">Belum Ditetapkan</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 text-blue-700 font-bold rounded-full text-xs">
                                        {{ $variant->bom_items_count }} Material
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($variant->active)
                                        <span class="badge-success text-xs">Aktif</span>
                                    @else
                                        <span class="badge-secondary text-xs">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('admin.variants.show', $variant) }}"
                                           class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs"
                                           title="Buka Detail Varian & Kelola BOM">
                                            <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            <span>Kelola BOM</span>
                                        </a>
                                        <a href="{{ route('admin.variants.edit', $variant) }}"
                                           class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                           title="Edit Varian"
                                           aria-label="Edit Varian">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h10m-7 5h7"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-[#172B4D] text-sm">Belum ada Varian Produk</p>
                                    <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">Tambahkan varian agar harga jual dan BOM dapat dikelola.</p>
                                    <div class="mt-4">
                                        <button type="button"
                                                @click="showVariantModal = true"
                                                class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Tambah Varian Baru</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add Variant Modal --}}
        <div x-show="showVariantModal"
             x-cloak
             x-data="{ requiresPrinting: '{{ old('requires_printing', '0') }}' }"
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showVariantModal = false" class="bg-white rounded-2xl p-5 sm:p-6 max-w-md w-full shadow-2xl space-y-4 border border-[#FFD8D8]">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                    <h3 class="font-bold text-sm text-[#172B4D]">Tambah Varian Produk</h3>
                    <button type="button" @click="showVariantModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.products.variants.store', $product) }}" class="space-y-4">
                    @csrf

                    <div class="space-y-1.5">
                        <label for="variant_name" class="block text-xs font-semibold text-[#172B4D]">
                            Nama Varian <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="variant_name"
                               id="variant_name"
                               placeholder="Contoh: Voal Cream / Hitam Polos"
                               required
                               class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                    </div>

                    <div class="p-3 bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg flex items-center justify-between">
                        <div>
                            <span class="block text-xs font-semibold text-[#172B4D]">SKU (Stock Keeping Unit)</span>
                            <span class="text-[11px] text-[#64748B]">Dibuat otomatis oleh sistem setelah varian disimpan.</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-[#8E1B5C]/10 text-[#8E1B5C]">
                            Auto-generated
                        </span>
                    </div>

                    <div class="space-y-1.5">
                        <label for="base_selling_price" class="block text-xs font-semibold text-[#172B4D]">
                            Harga Jual Master (Rp) <span class="text-[#64748B] font-normal">(Opsional)</span>
                        </label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="base_selling_price"
                               id="base_selling_price"
                               placeholder="Kosongkan jika belum ditentukan"
                               class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-[#172B4D]">
                            Memerlukan Printing <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-4 pt-0.5">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="requires_printing" value="1" x-model="requiresPrinting" {{ old('requires_printing') === '1' ? 'checked' : '' }} required class="text-[#8E1B5C] focus:ring-[#8E1B5C]">
                                <span class="text-xs text-[#172B4D] font-medium">Ya (Dengan Printing)</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="requires_printing" value="0" x-model="requiresPrinting" {{ old('requires_printing', '0') === '0' ? 'checked' : '' }} required class="text-[#8E1B5C] focus:ring-[#8E1B5C]">
                                <span class="text-xs text-[#172B4D] font-medium">Tidak (Tanpa Printing)</span>
                            </label>
                        </div>
                    </div>

                    {{-- Tarif Standar Produksi (Opsional) --}}
                    <div class="pt-3 border-t border-[#FFD8D8] space-y-3">
                        <div class="space-y-0.5">
                            <h4 class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Tarif Standar Produksi (Opsional)</h4>
                            <p class="text-[10px] text-[#64748B]">
                                Tarif ini digunakan sebagai nilai standar saat biaya produksi belum ditentukan pada batch. Kosongkan jika belum diketahui.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div x-show="requiresPrinting === '1'" x-cloak>
                                <label for="printing_cost_modal" class="block text-[11px] font-semibold text-[#172B4D] mb-0.5">
                                    Biaya Printing Standar / pcs
                                </label>
                                <input type="number" step="0.01" min="0" name="printing_cost_per_unit" id="printing_cost_modal"
                                       :disabled="requiresPrinting !== '1'"
                                       placeholder="Kosongkan jika belum diketahui" class="w-full h-8 px-2.5 text-xs font-mono border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] disabled:bg-gray-100 disabled:opacity-60">
                            </div>

                            <div :class="requiresPrinting === '1' ? '' : 'col-span-2'">
                                <label for="labor_cost_modal" class="block text-[11px] font-semibold text-[#172B4D] mb-0.5">
                                    Biaya Tenaga Kerja Standar / pcs
                                </label>
                                <input type="number" step="0.01" min="0" name="direct_labor_cost_per_unit" id="labor_cost_modal"
                                       placeholder="Kosongkan jika belum diketahui" class="w-full h-8 px-2.5 text-xs font-mono border border-[#FFD8D8] rounded-lg bg-[#FFFCFB]">
                            </div>

                            <div>
                                <label for="packaging_cost_modal" class="block text-[11px] font-semibold text-[#172B4D] mb-0.5">
                                    Biaya Packing Standar / pcs
                                </label>
                                <input type="number" step="0.01" min="0" name="packaging_process_cost_per_good_unit" id="packaging_cost_modal"
                                       placeholder="Kosongkan jika belum diketahui" class="w-full h-8 px-2.5 text-xs font-mono border border-[#FFD8D8] rounded-lg bg-[#FFFCFB]">
                            </div>

                            <div>
                                <label for="other_cost_modal" class="block text-[11px] font-semibold text-[#172B4D] mb-0.5">
                                    Biaya Produksi Tambahan / pcs
                                </label>
                                <input type="number" step="0.01" min="0" name="other_direct_cost_per_unit" id="other_cost_modal"
                                       placeholder="Kosongkan jika belum diketahui" class="w-full h-8 px-2.5 text-xs font-mono border border-[#FFD8D8] rounded-lg bg-[#FFFCFB]">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="active" id="variant_active" value="1" checked class="rounded text-[#8E1B5C] focus:ring-[#8E1B5C]">
                        <label for="variant_active" class="text-xs font-semibold text-[#172B4D] cursor-pointer">Varian Aktif</label>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                        <button type="button" @click="showVariantModal = false" class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all cursor-pointer">
                            Simpan Varian
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
