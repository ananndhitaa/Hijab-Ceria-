<x-app-layout pageTitle="Supplier" breadcrumb="Pembelian > Supplier">
    <div class="space-y-6">

        {{-- Feature Header --}}
        <x-feature-header
            title="Supplier"
            subtitle="Mitra pemasok bahan baku dan kebutuhan produksi."
            icon="supplier"
        >
            <x-slot:actions>
                <a href="{{ route('gudang.suppliers.create') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white hover:bg-[#8E1B5C] text-xs sm:text-sm font-semibold shadow-xs transition-all duration-200 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Supplier</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Simple Filter Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-3 sm:p-4 shadow-card">
            <form method="GET" action="{{ route('gudang.suppliers.index') }}" class="flex flex-wrap items-center gap-2.5">
                {{-- Search Input --}}
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nama supplier, telepon, email, nama material..."
                           class="w-full h-9 pl-9 pr-3 text-xs bg-white border border-[#FFD8D8] rounded-xl text-[#172B4D] placeholder-[#64748B]/60 focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                {{-- Status Filter --}}
                <div class="w-full sm:w-36">
                    <select name="active"
                            class="w-full h-9 px-3 text-xs bg-white border border-[#FFD8D8] rounded-xl text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        <option value="">Semua Status</option>
                        <option value="1" {{ ($activeFilter ?? '') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ ($activeFilter ?? '') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                {{-- Filter & Reset Buttons --}}
                <button type="submit"
                        class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#8E1B5C] text-white hover:bg-[#8E1B5C] text-xs font-semibold shadow-xs transition cursor-pointer">
                    Filter
                </button>
                @if($search || (($activeFilter ?? '') !== null && ($activeFilter ?? '') !== ''))
                    <a href="{{ route('gudang.suppliers.index') }}"
                       class="inline-flex items-center justify-center h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Suppliers Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-[#FFD8D8] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-[#172B4D]">Daftar Supplier</h2>
                    <p class="text-xs text-[#64748B]">{{ $suppliers->total() }} mitra supplier terdaftar</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#172B4D]">
                    <thead class="bg-[#FFFCFB] text-[11px] font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8]">
                        <tr>
                            <th class="py-3 px-4">Nama Supplier</th>
                            <th class="py-3 px-4">Material Disuplai</th>
                            <th class="py-3 px-4">Kontak</th>
                            <th class="py-3 px-4">Alamat</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($suppliers as $supplier)
                            <tr class="hover:bg-[#FFFCFB] transition-colors">
                                <td class="py-3 px-4 font-semibold text-[#172B4D]">
                                    <a href="{{ route('gudang.suppliers.show', $supplier) }}" class="hover:text-[#8E1B5C] hover:underline">
                                        {{ $supplier->supplier_name }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-[#172B4D]">
                                    @if($supplier->materials->isNotEmpty())
                                        <div class="flex flex-col gap-1 items-start">
                                            @foreach($supplier->materials->take(2) as $material)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-[#FFD8D8]/60 text-[#172B4D] border border-[#FFD8D8]/60">
                                                    {{ $material->material_name }}
                                                </span>
                                            @endforeach
                                            @if($supplier->materials->count() > 2)
                                                <span class="text-[11px] font-semibold text-[#8E1B5C] pl-0.5">
                                                    +{{ $supplier->materials->count() - 2 }} lainnya
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-[#64748B]/60 italic text-[11px]">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[#64748B]">
                                    <div>{{ $supplier->phone ?? '—' }}</div>
                                    @if($supplier->email)
                                        <div class="text-[11px] text-[#64748B]/80">{{ $supplier->email }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[#64748B] max-w-xs truncate">
                                    {{ $supplier->address ?? '—' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($supplier->active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            AKTIF
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            NONAKTIF
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <a href="{{ route('gudang.suppliers.show', $supplier) }}"
                                           class="inline-flex items-center justify-center h-7 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition shadow-xs"
                                           title="Detail Supplier">
                                            Detail
                                        </a>
                                        <a href="{{ route('gudang.suppliers.edit', $supplier) }}"
                                           class="inline-flex items-center justify-center h-7 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs"
                                           title="Edit Supplier">
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-xs text-[#64748B]">
                                    Belum ada data supplier yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($suppliers->hasPages())
                <div class="p-4 border-t border-[#FFD8D8] bg-[#FFFCFB]/50">
                    {{ $suppliers->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
