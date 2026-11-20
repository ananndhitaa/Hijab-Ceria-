<x-app-layout pageTitle="Tambah Satuan" breadcrumb="Material & Stok > Satuan > Tambah">
    <div class="max-w-2xl mx-auto space-y-6">

        {{-- Page Header --}}
        <x-feature-header
            title="Tambah Satuan Baru"
            subtitle="Daftarkan satuan unit pengukuran baru untuk material dan BOM."
            icon="material"
            badge="SATUAN MATERIAL"
        >
            <x-slot:actions>
                <a href="{{ route('gudang.units.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] text-xs sm:text-sm font-semibold transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card">
            <form method="POST" action="{{ route('gudang.units.store') }}" class="space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <label for="unit_name" class="block text-xs font-semibold text-[#172B4D]">
                        Nama Satuan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="unit_name"
                           name="unit_name"
                           value="{{ old('unit_name') }}"
                           placeholder="Contoh: Meter, Kilogram, Pieces, Roll"
                           required
                           class="w-full h-10 px-3.5 text-xs border {{ $errors->has('unit_name') ? 'border-red-500 ring-1 ring-red-500' : 'border-[#FFD8D8]' }} rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                    @error('unit_name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="symbol" class="block text-xs font-semibold text-[#172B4D]">
                        Simbol / Kode Singkat <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="symbol"
                           name="symbol"
                           value="{{ old('symbol') }}"
                           placeholder="Contoh: m, kg, pcs, roll"
                           required
                           class="w-full h-10 px-3.5 text-xs font-mono border {{ $errors->has('symbol') ? 'border-red-500 ring-1 ring-red-500' : 'border-[#FFD8D8]' }} rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                    @error('symbol')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-[#64748B]">Simbol digunakan pada pelabelan kuantitas stok.</p>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-[#FFD8D8]">
                    <a href="{{ route('gudang.units.index') }}"
                       class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] text-xs font-semibold transition-all duration-200 cursor-pointer">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-5 rounded-xl bg-[#8E1B5C] text-white hover:bg-[#8E1B5C] text-xs font-semibold shadow-xs transition-all duration-200 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Simpan Satuan</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
