<x-app-layout pageTitle="Edit Supplier" breadcrumb="Pembelian > Supplier > Edit">
    @php
        $origin = in_array(request('from', $from ?? ''), ['show', 'index'], true) ? request('from', $from ?? 'index') : 'index';
        $cancelRoute = $origin === 'show' ? route('gudang.suppliers.show', $supplier) : route('gudang.suppliers.index');
    @endphp

    <div class="max-w-2xl mx-auto space-y-6">

        {{-- Page Header --}}
        <x-feature-header
            title="Edit Supplier"
            :subtitle="'Perbarui profil kontak dan informasi operasional untuk ' . $supplier->supplier_name . '.'"
            icon="supplier"
            badge="DATA SUPPLIER"
        >
            <x-slot:actions>
                <a href="{{ $cancelRoute }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] text-xs sm:text-sm font-semibold transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-5">
            <form method="POST" action="{{ route('gudang.suppliers.update', ['supplier' => $supplier, 'from' => $origin]) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="from" value="{{ $origin }}">

                {{-- Nama Supplier --}}
                <div class="space-y-1.5">
                    <label for="supplier_name" class="block text-xs font-semibold text-[#172B4D]">
                        Nama Supplier <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           id="supplier_name"
                           name="supplier_name"
                           value="{{ old('supplier_name', $supplier->supplier_name) }}"
                           required
                           class="w-full h-10 px-3.5 rounded-xl border {{ $errors->has('supplier_name') ? 'border-red-500 ring-1 ring-red-500' : 'border-[#FFD8D8]' }} bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                    @error('supplier_name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Telepon & Email --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="phone" class="block text-xs font-semibold text-[#172B4D]">
                            Nomor Telepon
                        </label>
                        <input type="text"
                               id="phone"
                               name="phone"
                               value="{{ old('phone', $supplier->phone) }}"
                               class="w-full h-10 px-3.5 rounded-xl border {{ $errors->has('phone') ? 'border-red-500 ring-1 ring-red-500' : 'border-[#FFD8D8]' }} bg-white text-xs text-[#172B4D] font-mono focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        @error('phone')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="email" class="block text-xs font-semibold text-[#172B4D]">
                            Alamat Email
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email', $supplier->email) }}"
                               class="w-full h-10 px-3.5 rounded-xl border {{ $errors->has('email') ? 'border-red-500 ring-1 ring-red-500' : 'border-[#FFD8D8]' }} bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        @error('email')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Alamat --}}
                <div class="space-y-1.5">
                    <label for="address" class="block text-xs font-semibold text-[#172B4D]">
                        Alamat Supplier
                    </label>
                    <textarea id="address"
                              name="address"
                              rows="3"
                              class="w-full p-3 text-xs rounded-xl border {{ $errors->has('address') ? 'border-red-500 ring-1 ring-red-500' : 'border-[#FFD8D8]' }} bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">{{ old('address', $supplier->address) }}</textarea>
                    @error('address')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Material Disuplai --}}
                <div class="space-y-2" x-data="{
                    search: '',
                    selected: {{ json_encode(array_map('intval', old('material_ids', $supplier->materials->pluck('id')->all()))) }},
                    materials: {{ $materials->map(fn($m) => [
                        'id' => $m->id,
                        'name' => $m->material_name,
                        'unit' => $m->unit?->unit_name ?? $m->unit?->symbol ?? '',
                        'symbol' => $m->unit?->symbol ?? ''
                    ])->toJson() }},
                    get filteredMaterials() {
                        if (!this.search.trim()) return this.materials;
                        const s = this.search.toLowerCase();
                        return this.materials.filter(m => m.name.toLowerCase().includes(s));
                    },
                    selectAllFiltered() {
                        const idsToAdd = this.filteredMaterials.map(m => m.id);
                        this.selected = Array.from(new Set([...this.selected, ...idsToAdd]));
                    },
                    clearAll() {
                        this.selected = [];
                    }
                }">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-semibold text-[#172B4D]">
                                Material Disuplai
                            </label>
                            <span class="text-[11px] text-[#64748B]">Atur bahan baku yang disediakan oleh supplier ini. Menghapus relasi tidak akan menghapus riwayat PO lama.</span>
                        </div>
                        <span class="text-xs font-semibold text-[#8E1B5C]" x-text="selected.length + ' material dipilih'"></span>
                    </div>

                    {{-- Search & Bulk Actions Bar --}}
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <input type="text"
                                   x-model="search"
                                   placeholder="Ketik untuk mencari material..."
                                   class="w-full h-8 pl-8 pr-3 text-xs bg-white border border-[#FFD8D8] rounded-lg text-[#172B4D] placeholder-[#64748B]/60 focus:outline-none focus:border-[#8E1B5C] transition">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-[#64748B]">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                        <button type="button"
                                @click="selectAllFiltered()"
                                class="h-8 px-2.5 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-[11px] font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition cursor-pointer">
                            Pilih Semua
                        </button>
                        <button type="button"
                                @click="clearAll()"
                                class="h-8 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#64748B] hover:bg-gray-50 transition cursor-pointer">
                            Reset
                        </button>
                    </div>

                    {{-- Checklist Box --}}
                    <div class="max-h-56 overflow-y-auto border border-[#FFD8D8] rounded-xl bg-white p-2 divide-y divide-[#FFD8D8]/60">
                        <template x-for="mat in filteredMaterials" :key="mat.id">
                            <label class="flex items-center justify-between py-2 px-2 hover:bg-[#FFFCFB] rounded-lg cursor-pointer transition">
                                <div class="flex items-center gap-2.5">
                                    <input type="checkbox"
                                           name="material_ids[]"
                                           :value="mat.id"
                                           x-model="selected"
                                           class="rounded border-[#FFD8D8] text-[#8E1B5C] focus:ring-[#8E1B5C]">
                                    <span class="text-xs font-medium text-[#172B4D]" x-text="mat.name"></span>
                                </div>
                                <span class="text-[11px] text-[#64748B] font-mono" x-text="mat.symbol ? mat.unit + ' (' + mat.symbol + ')' : mat.unit"></span>
                            </label>
                        </template>

                        <div x-show="filteredMaterials.length === 0" class="py-6 text-center text-xs text-[#64748B] italic">
                            Tidak ada material yang sesuai pencarian.
                        </div>
                    </div>
                    @error('material_ids')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status Aktif --}}
                <div class="p-3.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-[#172B4D] block">Status Kemitraan Aktif</span>
                        <span class="text-[11px] text-[#64748B] block">Supplier aktif dapat langsung dipilih pada formulir pembuatan Purchase Order.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="active" value="1" {{ old('active', $supplier->active ? '1' : '0') == '1' ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#8E1B5C]"></div>
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-[#FFD8D8]">
                    <a href="{{ $cancelRoute }}"
                       class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] text-xs font-semibold transition-all duration-200 cursor-pointer">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-5 rounded-xl bg-[#8E1B5C] text-white hover:bg-[#8E1B5C] text-xs font-semibold shadow-xs transition-all duration-200 cursor-pointer">
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
