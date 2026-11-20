<x-app-layout pageTitle="Edit Varian Produk">
    <div class="max-w-4xl mx-auto space-y-5">

        {{-- Section A: Header Card --}}
        <x-feature-header
            title="Edit Varian Produk"
            subtitle="Perbarui nama varian, SKU, harga jual master, dan status varian."
            icon="product"
        >
            <x-slot:badge>
                <div class="flex items-center gap-1.5 text-xs text-[#64748B] mb-1">
                    <a href="{{ route('admin.products.show', $variant->product) }}" class="hover:underline hover:text-[#8E1B5C]">{{ $variant->product->product_name }}</a>
                    <span>/</span>
                    <span class="font-bold text-[#8E1B5C]">{{ $variant->variant_name }}</span>
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('admin.variants.show', $variant) }}" class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Detail Varian</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Errors Summary --}}
        <x-admin.form-errors />

        {{-- Section B: Main Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-6 shadow-card"
             x-data="{
                 requiresPrinting: '{{ old('requires_printing', $variant->requires_printing !== null ? ($variant->requires_printing ? '1' : '0') : '') }}',
                 imagePreview: '{{ $variant->image_url ?? '' }}',
                 removeImage: false,
                 fileChosen(event) {
                     const file = event.target.files[0];
                     if (!file) return;
                     if (file.size > 2 * 1024 * 1024) {
                         alert('Ukuran file foto maksimal 2MB.');
                         event.target.value = '';
                         return;
                     }
                     const reader = new FileReader();
                     reader.onload = (e) => {
                         this.imagePreview = e.target.result;
                         this.removeImage = false;
                     };
                     reader.readAsDataURL(file);
                 },
                 clearImage() {
                     this.imagePreview = '';
                     this.removeImage = true;
                     if (this.$refs.fileInput) {
                         this.$refs.fileInput.value = '';
                     }
                 }
             }">
            <form method="POST" action="{{ route('admin.variants.update', $variant) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')
                <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">

                {{-- Header & Context --}}
                <div class="border-b border-[#FFD8D8] pb-3">
                    <h2 class="text-sm font-bold text-[#172B4D]">Informasi Varian</h2>
                    <p class="text-xs text-[#64748B] mt-0.5">Pengaturan data master varian dan harga jual produk.</p>
                </div>

                {{-- Product Context (Read-Only) --}}
                <div class="p-3.5 bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg">
                    <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Produk Utama</span>
                    <p class="text-sm font-bold text-[#172B4D] mt-0.5">{{ $variant->product->product_name }}</p>
                </div>

                {{-- Nama Varian --}}
                <div class="space-y-1.5">
                    <label for="variant_name" class="block text-xs font-semibold text-[#172B4D]">
                        Nama Varian <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="variant_name"
                           id="variant_name"
                           value="{{ old('variant_name', $variant->variant_name) }}"
                           required
                           placeholder="Contoh: Dusty Pink / Hitam Polos"
                           class="w-full h-11 px-3 text-xs border @error('variant_name') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                    @error('variant_name')
                        <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- SKU (Read-Only) --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-[#172B4D]">
                            SKU (Stock Keeping Unit)
                        </label>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-[#8E1B5C]/10 text-[#8E1B5C]">
                            Dibuat otomatis oleh sistem
                        </span>
                    </div>
                    <input type="text"
                           value="{{ $variant->sku }}"
                           disabled
                           class="w-full h-11 px-3 text-xs font-mono border border-[#FFD8D8] rounded-lg bg-[#FFD8D8] text-[#64748B] cursor-not-allowed select-none">
                    <p class="text-[11px] text-[#64748B]">SKU dikelola otomatis oleh sistem dan tidak dapat diubah agar konsistensi dokumen, BOM, dan reporting tetap terjaga.</p>
                </div>

                {{-- Foto Varian --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-[#172B4D]">
                        Foto Spesifik Varian
                    </label>
                    <div class="flex items-center gap-4 p-3 bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg">
                        <div class="w-16 h-16 rounded-lg bg-white border border-[#FFD8D8] overflow-hidden shrink-0 flex items-center justify-center">
                            <template x-if="imagePreview">
                                <img :src="imagePreview" alt="Preview Foto Varian" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!imagePreview">
                                <span class="text-[10px] text-slate-400 text-center p-1 font-medium">Tanpa Foto</span>
                            </template>
                        </div>
                        <div class="flex-1 space-y-1.5">
                            <input type="file"
                                   name="image"
                                   x-ref="fileInput"
                                   @change="fileChosen"
                                   accept="image/jpeg,image/png,image/webp,image/jpg"
                                   class="block w-full text-xs text-[#64748B] file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-[#F7E6EE] file:text-[#8E1B5C] hover:file:bg-[#FFD8D8] cursor-pointer">
                            <p class="text-[11px] text-[#64748B]">Foto khusus varian ini untuk ditampilkan di katalog/etalase ketika varian dipilih. Format: JPG, PNG, WEBP (maks 2MB).</p>
                            <template x-if="imagePreview">
                                <button type="button"
                                        @click="clearImage"
                                        class="text-[11px] font-semibold text-red-600 hover:underline inline-block">
                                    Hapus Foto
                                </button>
                            </template>
                        </div>
                    </div>
                    @error('image')
                        <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Base Selling Price --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="base_selling_price" class="block text-xs font-semibold text-[#172B4D]">
                            Harga Jual Master (Rp)
                        </label>
                        <span class="text-[11px] text-[#64748B]">Opsional. Kosongkan jika belum ditentukan.</span>
                    </div>
                    <input type="number"
                           step="0.01"
                           min="0"
                           name="base_selling_price"
                           id="base_selling_price"
                           value="{{ old('base_selling_price', $variant->base_selling_price) }}"
                           placeholder="0.00"
                           class="w-full h-11 px-3 text-xs border @error('base_selling_price') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                    <p class="text-[11px] text-[#64748B]">Harga ini digunakan sebagai harga master dan dapat menjadi nilai awal harga transaksi pada Sales Order.</p>
                    @if(isset($referenceCost) && $referenceCost['reference_cost'] !== null)
                        <div class="p-2.5 rounded-lg bg-[#FFFCFB] border border-[#FFD8D8] flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs">
                            <div>
                                <span class="text-[#64748B]">HPP / Modal Referensi:</span>
                                <span class="font-mono font-bold text-[#172B4D] ml-1">{{ \App\Support\MoneyFormatter::rupiah($referenceCost['reference_cost']) }}</span>
                                <span class="text-[10px] text-[#64748B]">({{ $referenceCost['label'] }})</span>
                            </div>
                            <span class="text-[11px] text-[#64748B]">Arahan: Harga jual sebaiknya lebih tinggi dari HPP/modal</span>
                        </div>
                    @endif
                    @error('base_selling_price')
                        <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Memerlukan Printing --}}
                <div class="space-y-1.5 pt-2">
                    <label class="block text-xs font-semibold text-[#172B4D]">
                        Memerlukan Printing <span class="text-red-500">*</span>
                    </label>
                    @php
                        $currentPrinting = old('requires_printing', $variant->requires_printing !== null ? ($variant->requires_printing ? '1' : '0') : '');
                    @endphp
                    <div class="flex items-center gap-6 pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="radio"
                                   name="requires_printing"
                                   value="1"
                                   x-model="requiresPrinting"
                                   {{ $currentPrinting === '1' ? 'checked' : '' }}
                                   required
                                   class="text-[#8E1B5C] focus:ring-[#8E1B5C]">
                            <span class="text-xs font-semibold text-[#172B4D]">Ya (Dengan Printing)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="radio"
                                   name="requires_printing"
                                   value="0"
                                   x-model="requiresPrinting"
                                   {{ $currentPrinting === '0' ? 'checked' : '' }}
                                   required
                                   class="text-[#8E1B5C] focus:ring-[#8E1B5C]">
                            <span class="text-xs font-semibold text-[#172B4D]">Tidak (Tanpa Printing)</span>
                        </label>
                    </div>
                    @if($variant->requires_printing === null)
                        <p class="text-[11px] text-amber-600 font-medium mt-1">Status saat ini: Belum ditetapkan. Silakan tentukan rute printing untuk varian ini.</p>
                    @endif
                    @error('requires_printing')
                        <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tarif Standar Produksi (Opsional) --}}
                <div class="pt-4 border-t border-[#FFD8D8] space-y-3.5">
                    <div class="space-y-1">
                        <h3 class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Tarif Standar Produksi (Opsional)</h3>
                        <p class="text-[11px] text-[#64748B] leading-relaxed">
                            Tarif ini digunakan sebagai nilai standar saat biaya produksi belum ditentukan pada batch. Kosongkan jika belum diketahui. Tarif akan disalin sebagai snapshot saat Batch dibuat sehingga perubahan tarif master tidak mengubah HPP Batch lama.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Printing Cost --}}
                        <div class="space-y-1" x-show="requiresPrinting === '1'" x-cloak>
                            <label for="printing_cost_per_unit" class="block text-xs font-semibold text-[#172B4D]">
                                Biaya Printing Standar / pcs (Rp)
                            </label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="printing_cost_per_unit"
                                   id="printing_cost_per_unit"
                                   :disabled="requiresPrinting !== '1'"
                                   value="{{ old('printing_cost_per_unit', $variant->printing_cost_per_unit) }}"
                                   placeholder="Kosongkan jika belum diketahui"
                                   class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 font-mono disabled:bg-gray-100 disabled:opacity-60">
                            <span class="text-[10px] text-[#64748B]">Opsional. Otomatis dinormalisasi menjadi 0 jika tanpa printing.</span>
                            @error('printing_cost_per_unit')
                                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Direct Labor Cost --}}
                        <div class="space-y-1" :class="requiresPrinting === '1' ? '' : 'sm:col-span-2'">
                            <label for="direct_labor_cost_per_unit" class="block text-xs font-semibold text-[#172B4D]">
                                Biaya Tenaga Kerja Standar / pcs (Rp)
                            </label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="direct_labor_cost_per_unit"
                                   id="direct_labor_cost_per_unit"
                                   value="{{ old('direct_labor_cost_per_unit', $variant->direct_labor_cost_per_unit) }}"
                                   placeholder="Kosongkan jika belum diketahui"
                                   class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 font-mono">
                            <span class="text-[10px] text-[#64748B]">Opsional. Standar upah langsung per unit aktual produksi.</span>
                            @error('direct_labor_cost_per_unit')
                                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Packaging Process Cost --}}
                        <div class="space-y-1">
                            <label for="packaging_process_cost_per_good_unit" class="block text-xs font-semibold text-[#172B4D]">
                                Biaya Packing Standar / pcs (Rp)
                            </label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="packaging_process_cost_per_good_unit"
                                   id="packaging_process_cost_per_good_unit"
                                   value="{{ old('packaging_process_cost_per_good_unit', $variant->packaging_process_cost_per_good_unit) }}"
                                   placeholder="Kosongkan jika belum diketahui"
                                   class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 font-mono">
                            <span class="text-[10px] text-[#64748B]">Opsional. Standar biaya pengemasan per unit lolos QC.</span>
                            @error('packaging_process_cost_per_good_unit')
                                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Other Direct Cost --}}
                        <div class="space-y-1">
                            <label for="other_direct_cost_per_unit" class="block text-xs font-semibold text-[#172B4D]">
                                Biaya Produksi Tambahan / pcs (Rp)
                            </label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="other_direct_cost_per_unit"
                                   id="other_direct_cost_per_unit"
                                   value="{{ old('other_direct_cost_per_unit', $variant->other_direct_cost_per_unit) }}"
                                   placeholder="Kosongkan jika belum diketahui"
                                   class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 font-mono">
                            <span class="text-[10px] text-[#64748B]">Opsional. Standar biaya langsung tambahan per unit aktual.</span>
                            @error('other_direct_cost_per_unit')
                                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Status Section --}}
                <div class="pt-3 border-t border-[#FFD8D8] space-y-1.5">
                    <span class="block text-xs font-semibold text-[#172B4D]">Status Varian</span>
                    <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                        <input type="checkbox"
                               name="active"
                               id="active"
                               value="1"
                               {{ old('active', $variant->active) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-[#8E1B5C] border-[#FFD8D8] focus:ring-[#8E1B5C]">
                        <span class="text-xs font-semibold text-[#172B4D]">Varian Aktif</span>
                    </label>
                    <p class="text-[11px] text-[#64748B]">Varian aktif dapat digunakan dalam transaksi dan perencanaan produksi.</p>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-4 border-t border-[#FFD8D8] flex items-center justify-end gap-3">
                    <a href="{{ route('admin.variants.show', $variant) }}"
                       class="inline-flex items-center justify-center h-11 px-5 rounded-lg border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 h-11 px-6 rounded-lg bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Perbarui Varian</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
