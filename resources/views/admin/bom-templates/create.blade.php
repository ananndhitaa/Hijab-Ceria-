<x-app-layout pageTitle="Tambah Template BOM">
    <div class="space-y-5 max-w-2xl mx-auto">

        {{-- Header Card --}}
        <x-feature-header
            title="Tambah Template BOM"
            subtitle="Definisikan nama template resep bahan baku dan pilih produk terkait."
            icon="mrp"
            badge="TEMPLATE BOM"
        >
            <x-slot:actions>
                <a href="{{ route('admin.bom-templates.index') }}" class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card">
            <form method="POST" action="{{ route('admin.bom-templates.store') }}" class="space-y-4">
                @csrf

                {{-- Product Selection --}}
                <div class="space-y-1.5">
                    <label for="product_id" class="block text-xs font-semibold text-[#172B4D]">
                        Produk Terkait <span class="text-red-500">*</span>
                    </label>
                    <select name="product_id"
                            id="product_id"
                            required
                            class="w-full h-10 px-3 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer @error('product_id') border-red-500 @enderror">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" {{ (string) old('product_id', $selectedProductId) === (string) $prod->id ? 'selected' : '' }}>
                                {{ $prod->product_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-[#64748B]">Template BOM hanya dapat diterapkan pada varian dari produk yang dipilih.</p>
                </div>

                {{-- Template Name --}}
                <div class="space-y-1.5">
                    <label for="name" class="block text-xs font-semibold text-[#172B4D]">
                        Nama Template <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name') }}"
                           required
                           placeholder="Contoh: Pashmina Kaos Standard, Voal Polos 115x115"
                           class="w-full h-10 px-3 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="space-y-1.5">
                    <label for="description" class="block text-xs font-semibold text-[#172B4D]">
                        Deskripsi / Catatan Resep
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="3"
                              placeholder="Keterangan mengenai standar resep atau acuan produksi..."
                              class="w-full p-3 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                    <a href="{{ route('admin.bom-templates.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all cursor-pointer">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 h-10 px-5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 shadow-xs cursor-pointer">
                        Simpan & Lanjutkan
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
