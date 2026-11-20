<x-app-layout pageTitle="Edit Material" breadcrumb="Material & Stok > Material > Edit">
    @php
        $origin = in_array(request('from', $from ?? ''), ['show', 'index'], true) ? request('from', $from ?? 'index') : 'index';
        $cancelRoute = $origin === 'show' ? route('gudang.materials.show', $material) : route('gudang.materials.index');
    @endphp

    <div class="max-w-xl mx-auto space-y-5">

        {{-- Header Card --}}
        <x-feature-header
            title="Edit Material"
            subtitle="Perbarui nama, satuan, atau batas stok minimum material."
            icon="material"
            badge="MATERIAL &amp; STOK"
        >
            <x-slot:actions>
                <a href="{{ $cancelRoute }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card">
            <form method="POST" action="{{ route('gudang.materials.update', ['material' => $material, 'from' => $origin]) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="from" value="{{ $origin }}">

                <div>
                    <label for="material_name" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                        Nama Material <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="material_name"
                           name="material_name"
                           value="{{ old('material_name', $material->material_name) }}"
                           required
                           class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('material_name') border-red-500 @enderror">
                    @error('material_name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="unit_id" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                        Satuan Pengukuran <span class="text-red-500">*</span>
                    </label>
                    <select id="unit_id"
                            name="unit_id"
                            required
                            class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('unit_id') border-red-500 @enderror">
                        <option value="">-- Pilih Satuan --</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ old('unit_id', $material->unit_id) == $u->id ? 'selected' : '' }}>
                                {{ $u->unit_name }} ({{ $u->symbol }})
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="min_stock" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                        Stok Minimum <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           id="min_stock"
                           name="min_stock"
                           step="0.0001"
                           min="0"
                           value="{{ old('min_stock', $material->min_stock) }}"
                           required
                           class="w-full h-10 px-3 text-xs font-mono border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('min_stock') border-red-500 @enderror">
                    @error('min_stock')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="active"
                               value="1"
                               {{ old('active', $material->active ? '1' : '0') == '1' ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-[#8E1B5C] border-[#FFD8D8] focus:ring-[#8E1B5C]">
                        <span class="text-xs font-medium text-[#172B4D]">Material Aktif</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#FFD8D8]">
                    <a href="{{ $cancelRoute }}"
                       class="inline-flex items-center justify-center h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
