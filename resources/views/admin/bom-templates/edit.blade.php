<x-app-layout pageTitle="Edit Template BOM">
    <div class="space-y-5 max-w-2xl mx-auto">

        {{-- Header Card --}}
        <x-feature-header
            title="Edit Template BOM"
            subtitle="Perbarui nama, deskripsi, dan status aktif template."
            icon="mrp"
            badge="TEMPLATE BOM"
        >
            <x-slot:actions>
                <a href="{{ route('admin.bom-templates.show', $bomTemplate) }}" class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card">
            <form method="POST" action="{{ route('admin.bom-templates.update', $bomTemplate) }}" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Product Readonly --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-[#64748B]">
                        Produk Terkait
                    </label>
                    <input type="text"
                           disabled
                           value="{{ $bomTemplate->product->product_name ?? '-' }}"
                           class="w-full h-10 px-3 text-xs bg-gray-100 border border-[#FFD8D8] rounded-lg text-gray-700 cursor-not-allowed">
                    <p class="text-[11px] text-[#64748B]">Produk terkait tidak dapat diubah setelah template dibuat.</p>
                </div>

                {{-- Template Name --}}
                <div class="space-y-1.5">
                    <label for="name" class="block text-xs font-semibold text-[#172B4D]">
                        Nama Template <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name', $bomTemplate->name) }}"
                           required
                           class="w-full h-10 px-3 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all @error('name') border-red-500 @enderror">
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
                              class="w-full p-3 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-lg text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all @error('description') border-red-500 @enderror">{{ old('description', $bomTemplate->description) }}</textarea>
                    @error('description')
                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Active Status Toggle --}}
                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $bomTemplate->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-[#8E1B5C] focus:ring-[#8E1B5C] border-[#FFD8D8]">
                        <span class="text-xs font-semibold text-[#172B4D]">Template Aktif (Dapat diterapkan pada varian)</span>
                    </label>
                </div>

                <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                    <a href="{{ route('admin.bom-templates.show', $bomTemplate) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all cursor-pointer">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 h-10 px-5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 shadow-xs cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
