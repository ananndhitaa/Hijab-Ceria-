<x-app-layout pageTitle="Satuan" breadcrumb="Material & Stok > Satuan">
    <div class="space-y-6">

        {{-- Feature Header --}}
        <x-feature-header
            title="Satuan"
            subtitle="Unit pengukuran material dan BOM."
            icon="box"
        >
            <span class="sr-only">Master Satuan</span>
            <x-slot:actions>
                <a href="{{ route('gudang.units.create') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white hover:bg-[#8E1B5C] text-xs sm:text-sm font-semibold shadow-xs transition-all duration-200 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Satuan</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Search Filter --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-3 sm:p-4 shadow-card">
            <form method="GET" action="{{ route('gudang.units.index') }}" class="flex items-center gap-2.5">
                <div class="relative flex-1">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nama satuan atau simbol..."
                           class="w-full h-9 pl-9 pr-3 text-xs bg-white border border-[#FFD8D8] rounded-xl text-[#172B4D] placeholder-[#64748B]/60 focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
                <button type="submit"
                        class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#8E1B5C] text-white hover:bg-[#8E1B5C] text-xs font-semibold shadow-xs transition cursor-pointer">
                    Filter
                </button>
                @if($search)
                    <a href="{{ route('gudang.units.index') }}"
                       class="inline-flex items-center justify-center h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Units Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-[#FFD8D8] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-[#172B4D]">Daftar Satuan</h2>
                    <p class="text-xs text-[#64748B]">{{ $units->total() }} unit terdaftar dalam sistem</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#172B4D]">
                    <thead class="bg-[#FFFCFB] text-[11px] font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8]">
                        <tr>
                            <th class="py-3 px-4">Nama Satuan</th>
                            <th class="py-3 px-4">Simbol</th>
                            <th class="py-3 px-4">Digunakan Pada</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($units as $unit)
                            @php
                                $materialsCount = $unit->materials_count ?? $unit->materials()->count();
                            @endphp
                            <tr class="hover:bg-[#FFFCFB] transition-colors">
                                <td class="py-3 px-4 font-semibold text-[#172B4D]">
                                    {{ $unit->unit_name }}
                                </td>
                                <td class="py-3 px-4 font-mono font-medium text-[#8E1B5C]">
                                    {{ $unit->symbol }}
                                </td>
                                <td class="py-3 px-4 text-[#64748B]">
                                    @if($materialsCount > 0)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#FFD8D8] text-[#8E1B5C]">
                                            {{ $materialsCount }} material
                                        </span>
                                    @else
                                        <span class="text-[11px] text-[#64748B]/60">Belum digunakan</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <a href="{{ route('gudang.units.edit', $unit) }}"
                                           class="inline-flex items-center justify-center h-7 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition shadow-xs"
                                           title="Edit Satuan">
                                            Edit
                                        </a>

                                        @if($materialsCount === 0)
                                            <form method="POST" action="{{ route('gudang.units.destroy', $unit) }}" class="inline"
                                                  onsubmit="return confirm('Hapus satuan {{ $unit->unit_name }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center h-7 px-2 rounded-lg border border-red-200 bg-white text-[11px] font-semibold text-red-600 hover:bg-red-50 transition shadow-xs cursor-pointer"
                                                        title="Hapus Satuan">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-xs text-[#64748B]">
                                    Belum ada data satuan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($units->hasPages())
                <div class="p-4 border-t border-[#FFD8D8] bg-[#FFFCFB]/50">
                    {{ $units->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
