<x-app-layout pageTitle="Material" breadcrumb="Material & Stok > Material">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Material"
            subtitle="Katalog dan persediaan bahan baku."
            icon="material"
        >
            <span class="sr-only">Master Material</span>
            <x-slot:actions>
                <a href="{{ route('gudang.materials.create') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Material</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Filter & Search Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card">
            <form method="GET" action="{{ route('gudang.materials.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nama material atau satuan..."
                           class="w-full h-9 pl-9 pr-3 text-xs border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                    <svg class="w-4 h-4 text-[#64748B] absolute left-3 top-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <div class="w-full sm:w-44">
                    <select name="stock_status"
                            onchange="this.form.submit()"
                            class="w-full h-9 px-3 text-xs border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                        <option value="">Semua Stok</option>
                        <option value="AMAN" {{ $statusFilter === 'AMAN' ? 'selected' : '' }}>Stok Aman</option>
                        <option value="RENDAH" {{ $statusFilter === 'RENDAH' ? 'selected' : '' }}>Stok Rendah</option>
                        <option value="HABIS" {{ $statusFilter === 'HABIS' ? 'selected' : '' }}>Stok Habis</option>
                    </select>
                </div>

                <div class="w-full sm:w-40">
                    <select name="active"
                            onchange="this.form.submit()"
                            class="w-full h-9 px-3 text-xs border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                        <option value="">Semua Status</option>
                        <option value="1" {{ $activeFilter === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ $activeFilter === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="submit"
                            class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs w-full sm:w-auto">
                        Filter
                    </button>
                    @if($search || $statusFilter || ($activeFilter !== null && $activeFilter !== ''))
                        <a href="{{ route('gudang.materials.index') }}"
                           class="inline-flex items-center justify-center h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Materials Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#172B4D]">
                    <thead class="bg-[#FFFCFB] text-[11px] font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8]">
                        <tr>
                            <th class="py-3 px-4">Nama Material</th>
                            <th class="py-3 px-4">Satuan</th>
                            <th class="py-3 px-4 text-right">Stok Saat Ini</th>
                            <th class="py-3 px-4 text-right">Modal Rata-rata (MWA)</th>
                            <th class="py-3 px-4 text-right">Stok Minimum</th>
                            <th class="py-3 px-4 text-center">Status Stok</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($materials as $material)
                            <tr class="hover:bg-[#FFFCFB] transition-colors {{ ! $material->active ? 'opacity-70 bg-gray-50/50' : '' }}">
                                <td class="py-3 px-4 font-semibold text-[#172B4D]">
                                    <a href="{{ route('gudang.materials.show', $material) }}" class="hover:text-[#8E1B5C] hover:underline">
                                        {{ $material->material_name }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-[#64748B]">
                                    {{ $material->unit?->unit_name }} ({{ $material->unit?->symbol }})
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-semibold text-[#172B4D]">
                                    {{ $material->formatted_qty }} {{ $material->unit?->symbol }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-xs font-semibold text-[#172B4D]">
                                    {{ \App\Support\MoneyFormatter::rupiah($material->stock?->avg_unit_cost ?? 0) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-[#64748B]">
                                    {{ $material->formatted_min_stock }} {{ $material->unit?->symbol }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="badge {{ $material->stock_status_badge }} text-[10px]">
                                        {{ $material->stock_status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($material->active)
                                        <span class="badge badge-success text-[10px]">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary text-[10px]">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <a href="{{ route('gudang.materials.show', $material) }}"
                                           class="inline-flex items-center justify-center h-7 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition shadow-xs"
                                           title="Detail Material">
                                            Detail
                                        </a>
                                        <a href="{{ route('gudang.materials.edit', ['material' => $material, 'from' => 'index']) }}"
                                           class="inline-flex items-center justify-center h-7 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs"
                                           title="Edit Material">
                                            Edit
                                        </a>

                                        {{-- Toggle Active Form --}}
                                        <form method="POST" action="{{ route('gudang.materials.toggle-active', $material) }}" class="inline">
                                             @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center h-7 px-2 rounded-lg {{ $material->active ? 'border border-red-200 bg-white text-red-600 hover:bg-red-50' : 'border border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50' }} text-[11px] font-semibold transition shadow-xs cursor-pointer"
                                                    title="{{ $material->active ? 'Nonaktifkan Material' : 'Aktifkan Material' }}">
                                                {{ $material->active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        {{-- Safe Delete if completely unused --}}
                                        @if(
                                            bccomp((string)($material->stock?->qty_on_hand ?? '0'), '0', 4) <= 0 &&
                                            !$material->bomItems()->exists() &&
                                            !$material->batchMaterials()->exists() &&
                                            !$material->purchaseItems()->exists() &&
                                            !$material->stockMovements()->exists()
                                        )
                                            <form method="POST" action="{{ route('gudang.materials.destroy', $material) }}"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus material ini?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center h-7 px-2 rounded-lg border border-red-200 bg-white text-[11px] font-semibold text-red-600 hover:bg-red-50 transition shadow-xs cursor-pointer"
                                                        title="Hapus Material">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-xs text-[#64748B]">
                                    Tidak ada data material yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($materials->hasPages())
                <div class="p-4 border-t border-[#FFD8D8]">
                    {{ $materials->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
