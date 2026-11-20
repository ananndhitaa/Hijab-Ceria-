<x-app-layout pageTitle="Riwayat Mutasi Stok" breadcrumb="Material & Stok > Riwayat Mutasi Stok">
    <div class="space-y-6">

        {{-- Feature Header --}}
        <x-feature-header
            title="Riwayat Mutasi Stok"
            subtitle="Log mutasi persediaan material otomatis."
            icon="material"
        >
            <x-slot:actions>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] text-xs font-semibold text-[#64748B]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Audit Log Otomatis (Read-Only)
                </span>
            </x-slot:actions>
        </x-feature-header>

        {{-- Simple Filter Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-3 sm:p-4 shadow-card">
            <form method="GET" action="{{ route('gudang.stock-movements.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3 items-end">
                {{-- Material Filter --}}
                <div class="md:col-span-4 space-y-1">
                    <label for="material_id" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider">Filter Material</label>
                    <select id="material_id"
                            name="material_id"
                            class="w-full h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        <option value="">Semua Material</option>
                        @foreach($materials as $m)
                            <option value="{{ $m->id }}" {{ (string)$materialFilter === (string)$m->id ? 'selected' : '' }}>
                                {{ $m->material_name }} ({{ $m->unit?->symbol ?? '—' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Movement Type Filter --}}
                <div class="md:col-span-3 space-y-1">
                    <label for="movement_type" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider">Jenis Mutasi</label>
                    <select id="movement_type"
                            name="movement_type"
                            class="w-full h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        <option value="">Semua Jenis Mutasi</option>
                        @foreach($movementTypes as $mt)
                            <option value="{{ $mt->value }}" {{ $typeFilter === $mt->value ? 'selected' : '' }}>
                                {{ $mt->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div class="md:col-span-3 space-y-1">
                    <label for="date_from" class="block text-[10px] font-bold text-[#64748B] uppercase tracking-wider">Dari Tanggal</label>
                    <input type="date"
                           id="date_from"
                           name="date_from"
                           value="{{ $dateFrom }}"
                           class="w-full h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                </div>

                {{-- Actions --}}
                <div class="md:col-span-2 flex items-center gap-2">
                    <button type="submit"
                            class="flex-1 h-9 inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if($materialFilter || $typeFilter || $dateFrom || $dateTo)
                        <a href="{{ route('gudang.stock-movements.index') }}"
                           class="h-9 px-3 inline-flex items-center justify-center rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer"
                           title="Reset filter">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Ledger Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <x-nayara-section-header
                title="Catatan Log Mutasi Persediaan"
                subtitle="{{ $movements->total() }} baris catatan mutasi tercatat"
            />

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#172B4D]">
                    <thead class="bg-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-[0.02em] border-b border-[#FFD8D8]">
                        <tr>
                            <th class="py-2.5 sm:py-3 px-4 align-middle">Tanggal & Waktu</th>
                            <th class="py-2.5 sm:py-3 px-4 align-middle">Nama Material</th>
                            <th class="py-2.5 sm:py-3 px-4 text-center align-middle">Jenis</th>
                            <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Kuantitas</th>
                            <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Harga Satuan</th>
                            <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Total Nilai</th>
                            <th class="py-2.5 sm:py-3 px-4 text-center align-middle">Sebelum &rarr; Sesudah</th>
                            <th class="py-2.5 sm:py-3 px-4 align-middle">Referensi Dokumen</th>
                            <th class="py-2.5 sm:py-3 px-4 align-middle">Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($movements as $sm)
                            @php
                                $unit = $sm->material?->unit?->symbol ?? '';
                                $poReference = null;
                                $batchReference = null;
                                if ($sm->reference_type === 'purchase_items' && isset($purchaseItemsMap[$sm->reference_id])) {
                                    $poReference = $purchaseItemsMap[$sm->reference_id]->purchase;
                                } elseif ($sm->reference_type === 'batch_materials' && isset($batchMaterialsMap[$sm->reference_id])) {
                                    $batchReference = $batchMaterialsMap[$sm->reference_id]->batch;
                                } elseif ($sm->reference_type === 'production_batches' && isset($batchesMap[$sm->reference_id])) {
                                    $batchReference = $batchesMap[$sm->reference_id];
                                }

                                $isPositiveDiff = bccomp((string)$sm->qty_after, (string)$sm->qty_before, 4) > 0;
                                $qtySign = match($sm->movement_type) {
                                    \App\Enums\MovementType::IN => '+',
                                    \App\Enums\MovementType::OUT => '-',
                                    \App\Enums\MovementType::ADJUSTMENT => ($isPositiveDiff ? '+' : '-'),
                                };
                                $qtyColor = match($sm->movement_type) {
                                    \App\Enums\MovementType::IN => 'text-emerald-700',
                                    \App\Enums\MovementType::OUT => 'text-red-700',
                                    \App\Enums\MovementType::ADJUSTMENT => ($isPositiveDiff ? 'text-emerald-700' : 'text-red-700'),
                                };
                            @endphp
                            <tr class="hover:bg-[#FFFCFB] transition-colors">
                                <td class="py-3 px-4 font-mono text-[11px] text-[#64748B] whitespace-nowrap">
                                    <div>{{ \App\Support\DateTimeFormatter::formatDateWib($sm->created_at) }}</div>
                                    <div class="text-[10px]">{{ \App\Support\DateTimeFormatter::formatTime($sm->created_at) }}</div>
                                </td>
                                <td class="py-3 px-4 font-medium text-[#172B4D]">
                                    @if($sm->material)
                                        <a href="{{ route('gudang.materials.show', $sm->material) }}" class="hover:text-[#8E1B5C] hover:underline">
                                             {{ $sm->material->material_name }}
                                        </a>
                                    @else
                                        <span class="text-[#64748B]">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="badge {{ $sm->movement_type->badgeClass() }} text-[10px]">
                                        {{ $sm->movement_type->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums font-semibold whitespace-nowrap {{ $qtyColor }}">
                                    {{ \App\Support\QuantityFormatter::formatSigned($sm->qty, $qtySign, $unit) }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums text-[#64748B] whitespace-nowrap">
                                    Rp {{ number_format((float)$sm->unit_cost_snapshot, 2, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums font-medium text-[#172B4D] whitespace-nowrap">
                                    Rp {{ number_format((float)$sm->total_cost_snapshot, 2, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center tabular-nums text-[11px] text-[#64748B] whitespace-nowrap">
                                    {{ \App\Support\QuantityFormatter::format($sm->qty_before) }} &rarr; {{ \App\Support\QuantityFormatter::format($sm->qty_after) }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-xs">
                                    @if($poReference)
                                        <div>
                                            <a href="{{ route('gudang.purchases.show', $poReference) }}" class="font-mono font-bold text-[#8E1B5C] hover:underline">
                                                {{ $poReference->purchase_no }}
                                            </a>
                                            @if($sm->notes)
                                                <div class="text-[10px] text-[#64748B] truncate max-w-[200px]" title="{{ $sm->notes }}">
                                                    {{ $sm->notes }}
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($batchReference)
                                        <div>
                                            <a href="{{ route('gudang.material-planning.show', $batchReference) }}" class="font-mono font-bold text-[#8E1B5C] hover:underline">
                                                {{ $batchReference->batch_no }}
                                            </a>
                                            @if($sm->notes)
                                                <div class="text-[10px] text-[#64748B] truncate max-w-[200px]" title="{{ $sm->notes }}">
                                                    {{ $sm->notes }}
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($sm->notes)
                                        <span class="text-xs text-[#64748B] truncate max-w-[200px] inline-block" title="{{ $sm->notes }}">
                                            {{ $sm->notes }}
                                        </span>
                                    @else
                                        <span class="text-[#64748B]">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[#64748B] text-[11px] whitespace-nowrap">
                                    {{ $sm->createdByUser?->name ?? 'Sistem' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-xs text-[#64748B]">
                                    Belum ada data mutasi stok yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="p-4 border-t border-[#FFD8D8] bg-[#FFFCFB]/50">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
