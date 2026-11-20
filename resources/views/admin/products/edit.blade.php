<x-app-layout pageTitle="Edit Produk" breadcrumb="Produk > Edit">
    <div class="max-w-2xl mx-auto space-y-4 sm:space-y-5">
        
        {{-- Header Card --}}
        <x-feature-header
            title="Edit Produk"
            subtitle="Perbarui informasi master produk {{ $product->product_name }}."
            icon="product"
        >
            <x-slot:actions>
                <a href="{{ route('admin.products.show', $product) }}"
                   class="inline-flex items-center justify-center gap-2 h-11 sm:h-12 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Errors Summary --}}
        <x-admin.form-errors />

        {{-- Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card">
            <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Product Name --}}
                <div>
                    <label for="product_name" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                        Nama Produk <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="product_name" 
                           id="product_name" 
                           value="{{ old('product_name', $product->product_name) }}" 
                           required 
                           class="w-full h-10 px-3 text-xs border @error('product_name') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition-all">
                    @error('product_name')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Product Code (Read-Only) --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-[#172B4D]">Kode Produk</label>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-[#8E1B5C]/10 text-[#8E1B5C]">
                            Permanen
                        </span>
                    </div>
                    <input type="text"
                           value="{{ $product->code }}"
                           disabled
                           class="w-full h-10 px-3 text-xs font-mono border border-[#FFD8D8] rounded-lg bg-[#FFD8D8] text-[#64748B] cursor-not-allowed select-none">
                    <p class="text-[11px] text-[#64748B] mt-1">Kode produk bersifat permanen untuk menjaga konsistensi SKU varian.</p>
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                        Deskripsi Produk
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="3" 
                              class="w-full min-h-[90px] p-3 text-xs border @error('description') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition-all">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Active Status --}}
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" 
                           name="active" 
                           id="active" 
                           value="1" 
                           {{ old('active', $product->active) ? 'checked' : '' }} 
                           class="rounded text-[#8E1B5C] focus:ring-[#8E1B5C] accent-[#8E1B5C] h-4 w-4">
                    <label for="active" class="text-xs font-semibold text-[#172B4D]">
                        Status Aktif
                    </label>
                </div>

                {{-- Submit actions --}}
                <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                    <a href="{{ route('admin.products.show', $product) }}"
                       class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all cursor-pointer shadow-xs">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all cursor-pointer shadow-xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
