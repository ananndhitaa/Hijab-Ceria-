<x-app-layout pageTitle="Detail Material" breadcrumb="Material & Stok > Material > Detail">
    <div class="max-w-2xl mx-auto space-y-5" x-data="{ showToggleModal: false }">

        {{-- Header Card --}}
        <x-feature-header
            :title="$material->material_name"
            subtitle="Informasi spesifikasi dan ringkasan persediaan material."
            icon="material"
            badge="DETAIL MATERIAL"
        >
            <x-slot:actions>
                <a href="{{ route('gudang.materials.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
                <a href="{{ route('gudang.materials.stock-opname', $material) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#8E1B5C] bg-[#FFFCFB] text-xs sm:text-sm font-semibold text-[#8E1B5C] hover:bg-[#8E1B5C] hover:text-white transition shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span>Stock Opname</span>
                </a>
                @if($material->active)
                    <a href="{{ route('gudang.purchases.create') }}"
                       class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs"
                       title="Buat Purchase Order untuk membeli bahan ini">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span>Beli Bahan</span>
                    </a>
                @endif
                <a href="{{ route('gudang.materials.edit', ['material' => $material, 'from' => 'show']) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Detail Information Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-b border-[#FFD8D8] pb-5">
                <div>
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Nama Material</span>
                    <span class="text-base font-bold text-[#172B4D] mt-0.5 block">{{ $material->material_name }}</span>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Status Penggunaan</span>
                    <div class="mt-1.5 flex items-center gap-2.5">
                        @if($material->active)
                            <span class="badge badge-success text-[10px]">AKTIF</span>
                            <button type="button"
                                    @click="showToggleModal = true"
                                    class="inline-flex items-center justify-center h-6 px-2.5 rounded-lg border border-red-200 bg-white text-[11px] font-semibold text-red-600 hover:bg-red-50 transition shadow-xs cursor-pointer">
                                Nonaktifkan
                            </button>
                        @else
                            <span class="badge badge-secondary text-[10px]">NONAKTIF</span>
                            <button type="button"
                                    @click="showToggleModal = true"
                                    class="inline-flex items-center justify-center h-6 px-2.5 rounded-lg border border-emerald-200 bg-white text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50 transition shadow-xs cursor-pointer">
                                Aktifkan
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stock Overview Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Satuan Unit</span>
                    <span class="text-sm font-bold text-[#172B4D] block">
                        {{ $material->unit?->unit_name }} ({{ $material->unit?->symbol }})
                    </span>
                </div>

                <div class="p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Stok Minimum</span>
                    <span class="text-sm font-mono font-bold text-[#64748B] block">
                        {{ $material->formatted_min_stock }} {{ $material->unit?->symbol }}
                    </span>
                </div>

                <div class="p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Stok Saat Ini</span>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-base font-mono font-black text-[#172B4D]">
                            {{ $material->formatted_qty }} {{ $material->unit?->symbol }}
                        </span>
                        <span class="badge {{ $material->stock_status_badge }} text-[10px]">
                            {{ $material->stock_status }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Biaya & Modal Persediaan (Moving Weighted Average) --}}
            <div class="pt-4 border-t border-[#FFD8D8] space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Biaya &amp; Modal Persediaan (Costing MWA)</h2>
                        <p class="text-[11px] text-[#64748B]">Nilai modal bahan dihitung otomatis menggunakan metode Moving Weighted Average (MWA) saat penerimaan pembelian.</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        Internal Inventory (Bahan Baku)
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Modal Rata-rata (MWA) --}}
                    <div class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                        <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Modal Rata-rata (MWA)</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-base font-extrabold text-[#172B4D] font-mono">
                                {{ \App\Support\MoneyFormatter::rupiah($material->stock?->avg_unit_cost ?? 0) }}
                            </span>
                            <span class="text-xs text-[#64748B]">/ {{ $material->unit?->symbol ?? 'unit' }}</span>
                        </div>
                        <span class="text-[10px] text-[#64748B] block">Basis biaya aktual saat dialokasikan ke produksi</span>
                    </div>

                    {{-- Harga Beli Terakhir --}}
                    <div class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                        <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Harga Beli Terakhir</span>
                        @if($latestReceivedItem)
                            <div class="flex items-baseline gap-1">
                                <span class="text-base font-extrabold text-[#8E1B5C] font-mono">
                                    {{ \App\Support\MoneyFormatter::rupiah($latestReceivedItem->unit_price) }}
                                </span>
                                <span class="text-xs text-[#64748B]">/ {{ $material->unit?->symbol ?? 'unit' }}</span>
                            </div>
                            <span class="text-[10px] text-[#64748B] block">
                                PO: {{ $latestReceivedItem->purchase->po_number ?? '-' }}
                                @if($latestReceivedItem->purchase?->received_at)
                                    &middot; {{ \App\Support\DateTimeFormatter::formatShortDate($latestReceivedItem->purchase->received_at) }}
                                @endif
                            </span>
                        @else
                            <span class="text-base font-bold text-[#64748B] font-mono block">—</span>
                            <span class="text-[10px] text-[#64748B] block">Belum ada penerimaan PO</span>
                        @endif
                    </div>

                    {{-- Total Nilai Persediaan --}}
                    <div class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1">
                        <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Total Nilai Persediaan</span>
                        <span class="text-base font-extrabold text-emerald-700 font-mono block">
                            {{ \App\Support\MoneyFormatter::rupiah($totalStockValue) }}
                        </span>
                        <span class="text-[10px] text-[#64748B] block">Stok fisik ({{ $material->formatted_qty }} {{ $material->unit?->symbol }}) &times; Modal MWA</span>
                    </div>
                </div>

                {{-- Catatan Penjelasan Pembimbing --}}
                <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-200/80 text-[11px] text-amber-900 leading-relaxed flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <span class="font-bold">Ketentuan Modal &amp; Harga Jual Material:</span>
                        Material adalah persediaan bahan baku operasional produksi, bukan barang eceran yang dijual ke konsumen, sehingga material tidak memiliki harga jual. Biaya modal material (MWA) akan dibebankan ke HPP produk jadi secara akurat saat material dialokasikan dan digunakan pada batch produksi.
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Stock Movements (Max 5) --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-[#FFD8D8] flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                        Mutasi Stok Terakhir (Maks. 5)
                    </h2>
                    <p class="text-[11px] text-[#64748B] mt-0.5">Log perubahan persediaan fisik material ini.</p>
                </div>
                <a href="{{ route('gudang.stock-movements.index', ['material_id' => $material->id]) }}"
                   class="inline-flex items-center gap-1 text-xs font-semibold text-[#8E1B5C] hover:text-[#8E1B5C] hover:bg-[#FFD8D8] px-2.5 py-1 rounded-lg transition">
                    <span>Lihat Semua</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[#64748B] uppercase font-bold text-[10px] tracking-wider">
                            <th class="py-2.5 px-4">Tanggal</th>
                            <th class="py-2.5 px-4 text-center">Jenis</th>
                            <th class="py-2.5 px-4 text-right">Qty</th>
                            <th class="py-2.5 px-4 text-center">Sebelum &rarr; Sesudah</th>
                            <th class="py-2.5 px-4">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/60 text-[#172B4D]">
                        @forelse($recentMovements ?? [] as $mv)
                            @php
                                $unitSym = $material->unit?->symbol ?? '';
                                $isPositiveDiff = bccomp((string)$mv->qty_after, (string)$mv->qty_before, 4) > 0;
                                $qtySign = match($mv->movement_type) {
                                    \App\Enums\MovementType::IN => '+',
                                    \App\Enums\MovementType::OUT => '-',
                                    \App\Enums\MovementType::ADJUSTMENT => ($isPositiveDiff ? '+' : '-'),
                                };
                                $qtyColor = match($mv->movement_type) {
                                    \App\Enums\MovementType::IN => 'text-emerald-700',
                                    \App\Enums\MovementType::OUT => 'text-red-700',
                                    \App\Enums\MovementType::ADJUSTMENT => ($isPositiveDiff ? 'text-emerald-700' : 'text-red-700'),
                                };
                            @endphp
                            <tr class="hover:bg-[#FFD8D8]/20 transition">
                                <td class="py-2.5 px-4 whitespace-nowrap text-[#64748B]">
                                    <div>{{ \App\Support\DateTimeFormatter::formatDateWib($mv->created_at) }}</div>
                                    <div class="text-[10px] font-mono">{{ \App\Support\DateTimeFormatter::formatTime($mv->created_at) }}</div>
                                </td>
                                <td class="py-2.5 px-4 text-center whitespace-nowrap">
                                    <span class="badge {{ $mv->movement_type->badgeClass() }} text-[10px]">
                                        {{ $mv->movement_type->label() }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold whitespace-nowrap {{ $qtyColor }}">
                                    {{ $qtySign }}{{ number_format((float)$mv->qty, 2, ',', '.') }} {{ $unitSym }}
                                </td>
                                <td class="py-2.5 px-4 text-center font-mono text-[11px] text-[#64748B] whitespace-nowrap">
                                    {{ number_format((float)$mv->qty_before, 2, ',', '.') }} &rarr; <strong class="text-[#172B4D]">{{ number_format((float)$mv->qty_after, 2, ',', '.') }}</strong>
                                </td>
                                <td class="py-2.5 px-4 text-[#64748B] text-[11px] truncate max-w-[200px]" title="{{ $mv->notes }}">
                                    {{ $mv->notes ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-[#64748B]">
                                    Belum ada catatan mutasi stok untuk material ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Supplier Terdaftar Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-4">
            <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                <div>
                    <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">Supplier Terdaftar</h2>
                    <p class="text-[11px] text-[#64748B] mt-0.5">Mitra pemasok yang terdaftar menyediakan material ini.</p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                    {{ $material->suppliers->count() }} Supplier
                </span>
            </div>

            @if($material->suppliers->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($material->suppliers as $sup)
                        <div class="p-3.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] flex items-center justify-between">
                            <div>
                                <a href="{{ route('gudang.suppliers.show', $sup) }}" class="text-xs font-bold text-[#172B4D] hover:text-[#8E1B5C] transition">
                                    {{ $sup->supplier_name }}
                                </a>
                                <p class="text-[11px] text-[#64748B] font-mono mt-0.5">{{ $sup->phone ?? $sup->email ?? '—' }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $sup->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ $sup->active ? 'AKTIF' : 'NONAKTIF' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-xs text-[#64748B] italic bg-[#FFFCFB] rounded-xl border border-[#FFD8D8]">
                    Belum ada supplier terdaftar yang menyediakan material ini.
                </div>
            @endif
        </div>

        {{-- Modal Konfirmasi Toggle Active --}}
        <div x-show="showToggleModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
             @click.self="showToggleModal = false">
            <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl animate-fade-in"
                 @click.stop>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl {{ $material->active ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#172B4D]">
                            {{ $material->active ? 'Nonaktifkan material?' : 'Aktifkan kembali material?' }}
                        </h3>
                        <p class="text-xs text-[#64748B]">{{ $material->material_name }}</p>
                    </div>
                </div>

                <p class="text-xs text-[#172B4D] leading-relaxed">
                    {{ $material->active
                        ? 'Material tidak dapat dipilih untuk transaksi baru, tetapi riwayat data tetap tersimpan.'
                        : 'Material akan dapat digunakan kembali pada transaksi baru.' }}
                </p>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                    <button type="button"
                            @click="showToggleModal = false"
                            class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition cursor-pointer">
                        Batal
                    </button>
                    <form method="POST" action="{{ route('gudang.materials.toggle-active', $material) }}">
                        @csrf
                        <button type="submit"
                                class="h-9 px-4 rounded-xl {{ $material->active ? 'bg-red-600 hover:bg-red-700' : 'bg-[#8E1B5C] hover:bg-[#8E1B5C]' }} text-white text-xs font-semibold transition shadow-xs cursor-pointer">
                            {{ $material->active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
