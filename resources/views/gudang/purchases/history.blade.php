@php
    use App\Support\QuantityFormatter;
    use App\Support\MoneyFormatter;
@endphp

<x-app-layout pageTitle="Riwayat Pembelian Bahan Baku" breadcrumb="Pembelian > Riwayat Pembelian">
    <div class="space-y-4 sm:space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Riwayat Pembelian"
            subtitle="Histori penerimaan material supplier."
            icon="purchase"
        >
            <span class="sr-only">Riwayat Pembelian Bahan Baku</span>
        </x-feature-header>

        {{-- ── 2. Navigation Tabs ─────────────────────────────────────────── --}}
        <div class="border-b border-[#FFD8D8]">
            <nav class="flex space-x-6 -mb-px">
                <a href="{{ route('gudang.purchases.index') }}"
                   class="py-3 px-1 border-b-2 text-xs font-medium transition-colors duration-150 inline-flex items-center gap-2 border-transparent text-[#64748B] hover:text-[#172B4D] hover:border-[#FFD8D8]">
                    <svg class="w-4 h-4 text-[#64748B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Daftar Purchase Order</span>
                </a>
                <a href="{{ route('gudang.purchases.history') }}"
                   class="py-3 px-1 border-b-2 text-xs font-semibold transition-colors duration-150 inline-flex items-center gap-2 border-[#8E1B5C] text-[#8E1B5C]">
                    <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Riwayat Pembelian Bahan Baku</span>
                </a>
            </nav>
        </div>

        {{-- ── 3. Filter & Search Card ────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:px-6 sm:py-5 shadow-card">
            <form method="GET" action="{{ route('gudang.purchases.history') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.85fr)_minmax(0,1fr)_minmax(0,1.45fr)_128px] gap-x-3 gap-y-3 items-end">
                    {{-- Search --}}
                    <div class="col-span-1 md:col-span-2 lg:col-span-1 xl:col-span-1 min-w-0">
                        <label for="search" class="block text-[11px] font-semibold text-[#64748B] uppercase tracking-wider mb-2">
                            Pencarian
                        </label>
                        <div class="relative">
                            <input type="text"
                                   id="search"
                                   name="search"
                                   value="{{ $search }}"
                                   placeholder="Cari PO, supplier, atau material..."
                                   class="w-full min-w-0 h-10 pl-9 pr-3 rounded-lg border border-[#FFD8D8] bg-white text-xs sm:text-[13px] text-[#172B4D] placeholder-[#64748B]/60 focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Supplier Filter --}}
                    <div class="col-span-1 md:col-span-1 lg:col-span-1 xl:col-span-1 min-w-0">
                        <label for="supplier_id" class="block text-[11px] font-semibold text-[#64748B] uppercase tracking-wider mb-2">
                            Supplier
                        </label>
                        <select id="supplier_id"
                                name="supplier_id"
                                class="w-full min-w-0 h-10 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs sm:text-[13px] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                            <option value="">Semua Supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ (string)$supplierFilter === (string)$s->id ? 'selected' : '' }}>
                                    {{ $s->supplier_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Material Filter --}}
                    <div class="col-span-1 md:col-span-1 lg:col-span-1 xl:col-span-1 min-w-0">
                        <label for="material_id" class="block text-[11px] font-semibold text-[#64748B] uppercase tracking-wider mb-2">
                            Material
                        </label>
                        <select id="material_id"
                                name="material_id"
                                class="w-full min-w-0 h-10 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs sm:text-[13px] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                            <option value="">Semua Material</option>
                            @foreach($materials as $m)
                                <option value="{{ $m->id }}" {{ (string)$materialFilter === (string)$m->id ? 'selected' : '' }}>
                                    {{ $m->material_name }} ({{ $m->unit?->symbol ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date Range (Periode Terima) --}}
                    <div class="col-span-1 md:col-span-2 lg:col-span-2 xl:col-span-1 min-w-0">
                        <label class="block text-[11px] font-semibold text-[#64748B] uppercase tracking-wider mb-2">
                            Periode Terima
                        </label>
                        <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2">
                            <input type="date"
                                   name="date_from"
                                   value="{{ $dateFrom }}"
                                   aria-label="Tanggal awal penerimaan"
                                   class="w-full min-w-0 h-10 px-2 sm:px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-xs sm:text-[13px] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                            <span class="text-xs sm:text-sm text-[#64748B] font-semibold shrink-0">&ndash;</span>
                            <input type="date"
                                   name="date_to"
                                   value="{{ $dateTo }}"
                                   aria-label="Tanggal akhir penerimaan"
                                   class="w-full min-w-0 h-10 px-2 sm:px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-xs sm:text-[13px] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        </div>
                    </div>

                    {{-- Actions (Filter & Reset) --}}
                    <div class="col-span-1 md:col-span-2 lg:col-span-1 xl:col-span-1 flex items-center gap-1.5 min-w-0">
                        <button type="submit"
                                class="flex-1 w-full h-10 inline-flex items-center justify-center gap-1.5 px-3 rounded-lg bg-[#8E1B5C] text-white text-xs sm:text-[13px] font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer whitespace-nowrap"
                                title="Terapkan Filter">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span>Filter</span>
                        </button>
                        @if($search || $supplierFilter || $materialFilter || $dateFrom || $dateTo)
                            <a href="{{ route('gudang.purchases.history') }}"
                               class="h-10 px-2.5 inline-flex items-center justify-center rounded-lg border border-[#FFD8D8] bg-white text-xs sm:text-[13px] font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer shrink-0"
                                title="Reset filter">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span class="sr-only">Reset</span>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- ── 4. History Table Card ──────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl shadow-card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#FFD8D8] bg-[#FFFCFB]/60 flex items-center justify-between">
                <h2 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                    Daftar Riwayat Penerimaan Material
                </h2>
                <span class="text-[11px] font-medium text-[#64748B]">
                    Total baris: <strong class="text-[#172B4D]">{{ $historyItems->total() }}</strong>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[#64748B] uppercase font-semibold text-[11px] tracking-wider">
                            <th class="py-3 px-4 w-36 whitespace-nowrap">No. PO</th>
                            <th class="py-3 px-4 text-center w-28 whitespace-nowrap">Tanggal PO</th>
                            <th class="py-3 px-4 min-w-[140px]">Supplier</th>
                            <th class="py-3 px-4 min-w-[160px]">Material</th>
                            <th class="py-3 px-4 text-right w-28 whitespace-nowrap">Qty Diterima</th>
                            <th class="py-3 px-4 text-right w-32 whitespace-nowrap">Harga Satuan</th>
                            <th class="py-3 px-4 text-right w-36 whitespace-nowrap">Total Nilai</th>
                            <th class="py-3 px-4 text-center w-32 whitespace-nowrap">Tgl Diterima</th>
                            <th class="py-3 px-4 whitespace-nowrap">Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/60 text-[#172B4D]">
                        @forelse($historyItems as $item)
                            @php
                                $purchase = $item->purchase;
                                $material = $item->material;
                                $unit = $material?->unit;
                                $symbol = $unit?->symbol ?? '-';
                                $movement = $stockMovementsMap[$item->id] ?? null;

                                $totalValue = bcmul((string)$item->qty_received, (string)$item->unit_price, 2);
                                $receivedAt = $movement?->created_at;
                            @endphp
                            <tr class="hover:bg-[#FFFCFB] transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-[#8E1B5C] whitespace-nowrap">
                                    <a href="{{ route('gudang.purchases.show', $purchase) }}"
                                       class="hover:underline inline-flex items-center gap-1 group"
                                       title="Lihat detail PO {{ $purchase->purchase_no }}">
                                        <span>{{ $purchase->purchase_no }}</span>
                                        <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                        </svg>
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <div class="font-medium text-[#172B4D]">{{ $purchase->purchase_date?->translatedFormat('d M Y') ?? '—' }}</div>
                                </td>
                                <td class="py-3 px-4 text-[#172B4D]">
                                    <span class="font-medium">{{ $purchase->supplier?->supplier_name ?? '—' }}</span>
                                </td>
                                <td class="py-3 px-4 text-[#172B4D]">
                                    <div class="font-medium">{{ $material?->material_name ?? '—' }}</div>
                                    @if($material && ! $material->active)
                                        <span class="inline-block mt-0.5 text-[10px] text-red-600 bg-red-50 border border-red-200/60 px-1.5 py-0.2 rounded font-normal">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono tabular-nums font-semibold text-[#172B4D] whitespace-nowrap">
                                    {{ QuantityFormatter::format($item->qty_received) }} {{ $symbol }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono tabular-nums text-[#64748B] whitespace-nowrap">
                                    {{ MoneyFormatter::rupiah($item->unit_price) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono tabular-nums font-bold text-[#8E1B5C] whitespace-nowrap">
                                    {{ MoneyFormatter::rupiah($totalValue) }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($receivedAt)
                                        <div class="font-medium text-[#172B4D]">{{ \App\Support\DateTimeFormatter::formatDateWib($receivedAt) }}</div>
                                        <div class="text-[10px] text-[#64748B] font-mono">{{ \App\Support\DateTimeFormatter::formatTime($receivedAt) }}</div>
                                    @else
                                        <span class="text-[#64748B]/60">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[#64748B] text-[11px] whitespace-nowrap">
                                    {{ $movement?->createdByUser?->name ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-[#64748B]">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-10 h-10 mx-auto rounded-full bg-[#FFFCFB] border border-[#FFD8D8] flex items-center justify-center text-[#64748B]">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-semibold text-[#172B4D]">Belum ada riwayat penerimaan material.</p>
                                        <p class="text-[11px] text-[#64748B] leading-relaxed">
                                            @if($search || $supplierFilter || $materialFilter || $dateFrom || $dateTo)
                                                Tidak ditemukan data yang sesuai dengan kriteria filter saat ini.
                                            @else
                                                Data akan muncul otomatis setelah Purchase Order diterima di Gudang.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($historyItems->hasPages())
                <div class="px-5 py-3.5 border-t border-[#FFD8D8] bg-[#FFFCFB]/40">
                    {{ $historyItems->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- Client-side dynamic supplier -> material dependent filter --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const supplierSelect = document.getElementById('supplier_id');
            const materialSelect = document.getElementById('material_id');
            if (!supplierSelect || !materialSelect) return;

            const allMaterials = @json($materialsJson ?? []);
            const supplierMaterialMap = @json($supplierMaterialMap ?? []);

            function updateMaterialOptions() {
                const selectedSupplier = supplierSelect.value;
                const currentMaterial = materialSelect.value;
                const allowedIds = selectedSupplier ? (supplierMaterialMap[selectedSupplier] || []) : null;

                // Re-populate options
                materialSelect.innerHTML = '<option value="">Semua Material</option>';

                let isStillValid = false;
                allMaterials.forEach(function (m) {
                    if (!allowedIds || allowedIds.includes(m.id)) {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.name + ' (' + m.unit + ')';
                        if (String(m.id) === String(currentMaterial)) {
                            opt.selected = true;
                            isStillValid = true;
                        }
                        materialSelect.appendChild(opt);
                    }
                });

                // Auto-reset invalid material when supplier changes
                if (!isStillValid && currentMaterial !== '') {
                    materialSelect.value = '';
                }
            }

            supplierSelect.addEventListener('change', updateMaterialOptions);
        });
    </script>
</x-app-layout>
