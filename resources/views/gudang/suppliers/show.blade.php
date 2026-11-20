<x-app-layout pageTitle="Detail Supplier" breadcrumb="Pembelian > Supplier > Detail">
    <div class="max-w-3xl mx-auto space-y-6" x-data="{ showToggleModal: false }">

        {{-- Page Header --}}
        <x-feature-header
            :title="$supplier->supplier_name"
            :subtitle="'Profil kemitraan dan informasi kontak resmi ' . $supplier->supplier_name . '.'"
            icon="supplier"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        DATA SUPPLIER
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $supplier->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $supplier->active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                        {{ $supplier->active ? 'AKTIF' : 'NONAKTIF' }}
                    </span>
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('gudang.suppliers.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] text-xs sm:text-sm font-semibold transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
                <a href="{{ route('gudang.suppliers.edit', ['supplier' => $supplier, 'from' => 'show']) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white hover:bg-[#8E1B5C] text-xs sm:text-sm font-semibold shadow-xs transition-all duration-200 cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Supplier</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Supplier Details Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-b border-[#FFD8D8] pb-4">
                <div>
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Nama Supplier</span>
                    <span class="text-base font-bold text-[#172B4D] mt-1 block">{{ $supplier->supplier_name }}</span>
                    <span class="text-xs text-[#64748B] font-mono mt-0.5 block">Supplier ID: #{{ $supplier->id }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Status Kemitraan</span>
                    <div class="mt-1 flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 text-xs {{ $supplier->active ? 'text-emerald-800' : 'text-gray-700' }} font-semibold">
                            <span class="w-2 h-2 rounded-full {{ $supplier->active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                            <span>{{ $supplier->active ? 'AKTIF' : 'NONAKTIF' }}</span>
                        </span>
                        <button type="button"
                                @click="showToggleModal = true"
                                class="inline-flex items-center justify-center h-7 px-3 rounded-lg border {{ $supplier->active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }} bg-white text-xs font-medium transition cursor-pointer">
                            {{ $supplier->active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] space-y-1">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Nomor Telepon</span>
                    <span class="text-sm font-mono font-semibold text-[#172B4D] block">{{ $supplier->phone ?? '—' }}</span>
                </div>
                <div class="p-4 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] space-y-1">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Alamat Email</span>
                    <span class="text-sm font-semibold text-[#172B4D] block">{{ $supplier->email ?? '—' }}</span>
                </div>
            </div>

            <div class="space-y-1.5">
                <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Alamat Kantor / Pengiriman</span>
                <div class="p-4 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] text-xs text-[#172B4D] leading-relaxed">
                    {{ $supplier->address ?? 'Tidak ada alamat tercatat.' }}
                </div>
            </div>
        </div>

        {{-- Material Disuplai Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-4">
            <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                <div>
                    <h2 class="text-sm font-bold text-[#172B4D]">Material Disuplai</h2>
                    <p class="text-xs text-[#64748B]">Daftar bahan baku dan perlengkapan yang disediakan oleh supplier ini.</p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                    {{ $supplier->materials->count() }} Material
                </span>
            </div>

            @if($supplier->materials->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#FFFCFB] text-[11px] font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8]">
                            <tr>
                                <th class="py-2.5 px-4">Material</th>
                                <th class="py-2.5 px-4 w-36">Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @foreach($supplier->materials as $mat)
                                <tr class="hover:bg-[#FFFCFB] transition-colors">
                                    <td class="py-2.5 px-4 font-semibold text-[#172B4D]">
                                        {{ $mat->material_name }}
                                    </td>
                                    <td class="py-2.5 px-4 text-[#64748B]">
                                        {{ $mat->unit?->unit_name ?? $mat->unit?->symbol ?? '—' }}
                                        @if($mat->unit?->symbol)
                                            <span class="text-[10px] text-[#64748B]/70 font-mono">({{ $mat->unit->symbol }})</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-6 text-center text-xs text-[#64748B] italic bg-[#FFFCFB] rounded-xl border border-[#FFD8D8]">
                    Belum ada material terdaftar.
                </div>
            @endif
        </div>

        {{-- Modal Toggle Aktif/Nonaktif --}}
        <div x-show="showToggleModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
             @click.self="showToggleModal = false">
            <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-5 sm:p-6 space-y-4 shadow-xl"
                 @click.stop>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl {{ $supplier->active ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#172B4D]">
                            {{ $supplier->active ? 'Nonaktifkan supplier?' : 'Aktifkan supplier?' }}
                        </h3>
                        <p class="text-xs text-[#64748B]">{{ $supplier->supplier_name }}</p>
                    </div>
                </div>

                <p class="text-xs text-[#172B4D] leading-relaxed">
                    @if($supplier->active)
                        Supplier tidak dapat dipilih untuk transaksi baru, tetapi riwayat data tetap tersimpan.
                    @else
                        Supplier akan aktif kembali dan dapat dipilih untuk pemesanan Purchase Order.
                    @endif
                </p>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                    <button type="button"
                            @click="showToggleModal = false"
                            class="inline-flex items-center justify-center h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition cursor-pointer">
                        Batal
                    </button>
                    <form method="POST" action="{{ route('gudang.suppliers.toggle-active', $supplier) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center justify-center h-9 px-4 rounded-xl {{ $supplier->active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white text-xs font-semibold shadow-xs transition cursor-pointer">
                            {{ $supplier->active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
