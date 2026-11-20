<x-app-layout pageTitle="Resep Template BOM">
    <div class="space-y-5" x-data="{
        showAddModal: false,
        showDeleteModal: false,
        deleteActionUrl: '',
        deleteMaterialName: '',
        editingItemId: null,
        editingQty: ''
    }">

        {{-- Section A: Header Card --}}
        <x-feature-header
            :title="$bomTemplate->name"
            icon="mrp"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        TEMPLATE BOM
                    </span>
                    @if($bomTemplate->is_active)
                        <span class="badge-success text-xs">Aktif</span>
                    @else
                        <span class="badge-secondary text-xs">Nonaktif</span>
                    @endif
                </div>
            </x-slot:badge>
            <p class="text-xs text-[#64748B] flex items-center gap-2 mt-1">
                <span class="font-medium text-[#172B4D]">Produk: {{ $bomTemplate->product->product_name ?? '-' }}</span>
                <span>•</span>
                <span>{{ $bomTemplate->description ?? 'Tidak ada deskripsi resep.' }}</span>
            </p>
            <x-slot:actions>
                <a href="{{ route('admin.bom-templates.edit', $bomTemplate) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Template</span>
                </a>
                <a href="{{ route('admin.bom-templates.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Daftar Template</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Recipe Items Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="p-4 sm:p-5 border-b border-[#FFD8D8] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-0.5">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-bold text-[#172B4D]">Daftar Kebutuhan Material</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-[#FFD8D8] text-[#8E1B5C]">
                            {{ $bomTemplate->items->count() }} Jenis Material
                        </span>
                    </div>
                    <p class="text-xs text-[#64748B]">Resep bahan baku standar per 1 unit varian yang akan disalin saat template diterapkan.</p>
                </div>
                <button type="button"
                        @click="showAddModal = true"
                        class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer shrink-0">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Material</span>
                </button>
            </div>

            {{-- Recipe Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                            <th class="px-4 py-3 text-center whitespace-nowrap w-12">#</th>
                            <th class="px-4 py-3 min-w-[200px]">Material Bahan Baku</th>
                            <th class="px-4 py-3 whitespace-nowrap w-24">Satuan</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap w-40">Kebutuhan</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @forelse($bomTemplate->items as $index => $item)
                            <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/40">
                                <td class="px-4 py-3 text-center font-mono text-[#64748B] whitespace-nowrap">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-semibold text-xs text-[#172B4D] leading-snug">
                                    {{ $item->material->material_name }}
                                </td>
                                <td class="px-4 py-3 text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $item->material->unit->unit_name ?? $item->material->unit->symbol ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <template x-if="editingItemId !== {{ $item->id }}">
                                        <div class="inline-flex items-baseline justify-end gap-1.5 font-mono">
                                            <span class="text-sm font-bold text-[#172B4D]">{{ \App\Support\QuantityFormatter::format($item->qty_per_unit) }}</span>
                                            <span class="text-xs font-normal text-[#64748B] font-sans">{{ $item->material->unit->symbol ?? $item->material->unit->unit_name ?? '' }}</span>
                                        </div>
                                    </template>

                                    {{-- Quick Edit Inline Form --}}
                                    <template x-if="editingItemId === {{ $item->id }}">
                                        <form method="POST" action="{{ route('admin.bom-templates.items.update', [$bomTemplate, $item]) }}" class="flex items-center justify-end gap-1.5">
                                            @csrf
                                            @method('PUT')
                                            <input type="number"
                                                   step="0.0001"
                                                   min="0.0001"
                                                   name="qty_per_unit"
                                                   x-model="editingQty"
                                                   required
                                                   class="w-24 h-8 px-2 bg-white border border-[#8E1B5C] rounded-lg text-xs font-mono text-right focus:outline-none">
                                            <button type="submit" class="h-8 px-2.5 bg-[#8E1B5C] text-white text-[11px] font-bold rounded-lg hover:bg-[#8E1B5C] transition-colors">Simpan</button>
                                            <button type="button" @click="editingItemId = null" class="h-8 px-2 bg-gray-100 text-gray-700 text-[11px] rounded-lg hover:bg-gray-200 border border-gray-300 transition-colors">Batal</button>
                                        </form>
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button"
                                                @click="editingItemId = {{ $item->id }}; editingQty = '{{ (float) $item->qty_per_unit }}'"
                                                class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                                title="Edit Kuantitas"
                                                aria-label="Edit Kuantitas">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button type="button"
                                                @click="deleteActionUrl = '{{ route('admin.bom-templates.items.destroy', [$bomTemplate, $item]) }}'; deleteMaterialName = '{{ $item->material->material_name }}'; showDeleteModal = true"
                                                class="w-8 h-8 rounded-lg bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs"
                                                title="Hapus Material"
                                                aria-label="Hapus Material">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-[#172B4D] text-sm">Belum Ada Material Resep</p>
                                    <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">Tambahkan material yang dibutuhkan untuk memproduksi 1 unit varian pada template ini.</p>
                                    <div class="mt-4">
                                        <button type="button"
                                                @click="showAddModal = true"
                                                class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Tambah Material</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add Material Modal --}}
        <div x-show="showAddModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showAddModal = false" class="bg-white rounded-2xl p-5 sm:p-6 max-w-md w-full shadow-2xl space-y-4 border border-[#FFD8D8]">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                    <h3 class="font-bold text-sm text-[#172B4D]">Tambah Material ke Template BOM</h3>
                    <button type="button" @click="showAddModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.bom-templates.items.store', $bomTemplate) }}" class="space-y-4">
                    @csrf

                    <div class="space-y-1.5">
                        <label for="material_id" class="block text-xs font-semibold text-[#172B4D]">
                            Pilih Material Bahan Baku <span class="text-red-500">*</span>
                        </label>
                        <select name="material_id"
                                id="material_id"
                                required
                                class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                            <option value="">-- Pilih Material Aktif --</option>
                            @foreach($materials as $mat)
                                <option value="{{ $mat->id }}">
                                    {{ $mat->material_name }} ({{ $mat->unit->unit_name ?? $mat->unit->symbol ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                        @if($materials->isEmpty())
                            <p class="text-[11px] text-amber-600 mt-1">Semua material aktif sudah terdaftar dalam template ini.</p>
                        @endif
                    </div>

                    <div class="space-y-1.5">
                        <label for="qty_per_unit" class="block text-xs font-semibold text-[#172B4D]">
                            Kebutuhan per 1 Unit Varian <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               step="0.0001"
                               min="0.0001"
                               name="qty_per_unit"
                               id="qty_per_unit"
                               placeholder="Contoh: 1.2500"
                               required
                               class="w-full h-10 px-3 text-xs font-mono border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                        <p class="text-[11px] text-[#64748B]">Kebutuhan material untuk memproduksi 1 pcs barang jadi.</p>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                        <button type="button" @click="showAddModal = false" class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                {{ $materials->isEmpty() ? 'disabled' : '' }}
                                class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            Tambah Material
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete Material Confirmation Modal --}}
        <div x-show="showDeleteModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showDeleteModal = false" class="bg-white rounded-2xl p-5 sm:p-6 max-w-md w-full shadow-2xl space-y-4 border border-[#FFD8D8]">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-[#172B4D]">Hapus Material dari Template?</h3>
                        <p class="text-xs text-[#64748B] leading-relaxed">
                            Material <strong class="text-[#172B4D]" x-text="deleteMaterialName"></strong> akan dihapus dari template ini. Tindakan ini tidak mempengaruhi varian produk yang sudah pernah disalin sebelumnya.
                        </p>
                    </div>
                </div>

                <form :action="deleteActionUrl" method="POST" class="flex items-center justify-end gap-2 pt-3 border-t border-[#FFD8D8]">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="showDeleteModal = false" class="inline-flex items-center justify-center h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all cursor-pointer shadow-xs">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 transition-all cursor-pointer shadow-xs">
                        Hapus Material
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
