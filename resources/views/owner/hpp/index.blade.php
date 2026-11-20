<x-app-layout pageTitle="HPP Produksi" breadcrumb="Executive Monitoring > HPP Produksi">
    <div class="max-w-7xl mx-auto space-y-4 sm:space-y-6">

        {{-- ── 1. Page Header ─────────────────────────────────────────────── --}}
        <x-feature-header
            title="HPP Produksi"
            subtitle="Pantau kesiapan dan biaya produksi per batch serta pesanan."
            badge="EXECUTIVE MONITORING"
            icon="chart"
        >
            <x-slot:actions>
                {{-- Tabs Switcher (Segmented Control ~40px) --}}
                <div class="flex items-center bg-[#FFFCFB] p-1 rounded-xl border border-[#FFD8D8] shrink-0 h-10">
                    <a href="{{ route('owner.hpp.index', array_merge(request()->query(), ['tab' => 'batches'])) }}"
                       class="px-4 py-1.5 rounded-lg text-xs sm:text-[13px] font-bold transition-all {{ $tab === 'batches' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'text-[#64748B] hover:text-[#8E1B5C]' }}">
                        Batch Produksi
                    </a>
                    <a href="{{ route('owner.hpp.index', array_merge(request()->query(), ['tab' => 'orders'])) }}"
                       class="px-4 py-1.5 rounded-lg text-xs sm:text-[13px] font-bold transition-all {{ $tab === 'orders' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'text-[#64748B] hover:text-[#8E1B5C]' }}">
                        Pesanan
                    </a>
                </div>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Filters & Search Bar ────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
            <form method="GET" action="{{ route('owner.hpp.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="{{ $tab === 'batches' ? 'Cari nomor batch, sales order, pelanggan, atau varian...' : 'Cari nomor order atau pelanggan...' }}"
                           class="w-full pl-10 pr-4 h-10 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] placeholder-[#64748B] focus:outline-hidden focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                </div>

                @if($tab === 'batches')
                    <div class="w-full sm:w-56 shrink-0">
                        <select name="status"
                                onchange="this.form.submit()"
                                class="w-full h-10 px-3 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:outline-hidden focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                            <option value="">Semua Status Biaya</option>
                            <option value="READY" {{ $statusFilter === 'READY' ? 'selected' : '' }}>HPP Final</option>
                            <option value="NOT_READY" {{ $statusFilter === 'NOT_READY' ? 'selected' : '' }}>Belum Final</option>
                            <option value="CANCELLED" {{ $statusFilter === 'CANCELLED' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center justify-center h-10 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition cursor-pointer shadow-xs">
                        Filter
                    </button>

                    @if($search || $statusFilter)
                        <a href="{{ route('owner.hpp.index', ['tab' => $tab]) }}"
                           class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── 3. Tab: Batch Produksi ─────────────────────────────────────── --}}
        @if($tab === 'batches')
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left text-[#172B4D]">
                        <thead class="bg-[#FFFCFB] text-[11px] text-[#64748B] uppercase font-bold border-b border-[#FFD8D8]">
                            <tr>
                                <th class="px-4 py-3.5">No. Batch</th>
                                <th class="px-4 py-3.5">Sales Order &amp; Pelanggan</th>
                                <th class="px-4 py-3.5">Produk / Varian</th>
                                <th class="px-4 py-3.5 text-right">Good Qty</th>
                                <th class="px-4 py-3.5 text-center">Status HPP</th>
                                <th class="px-4 py-3.5 text-right">Total HPP</th>
                                <th class="px-4 py-3.5 text-right">HPP / Good Pcs</th>
                                <th class="px-4 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @forelse($batches as $b)
                                @php
                                    $hpp = $batchHppResults[$b->id] ?? null;
                                    $isReady = $hpp && $hpp['is_ready'];
                                    $goodQty = $b->qcResult ? (float) $b->qcResult->good_qty : 0;
                                @endphp
                                <tr class="hover:bg-[#FFFCFB]/50 transition">
                                    <td class="px-4 py-3.5 font-mono font-bold text-[#8E1B5C]">
                                        <a href="{{ route('owner.hpp.batches.show', $b) }}" class="hover:underline">
                                            {{ $b->batch_no }}
                                        </a>
                                        <span class="badge {{ $b->status->badgeClass() }} text-[9px] block w-max mt-1">
                                            {{ $b->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        @if($b->orderItem && $b->orderItem->order)
                                            <a href="{{ route('owner.hpp.orders.show', $b->orderItem->order) }}" class="font-mono font-bold text-[#172B4D] hover:text-[#8E1B5C] hover:underline block">
                                                {{ $b->orderItem->order->order_no }}
                                            </a>
                                            <span class="text-[11px] text-[#64748B] block truncate">
                                                {{ $b->orderItem->order->customer->customer_name ?? '-' }}
                                            </span>
                                        @else
                                            <span class="text-[#64748B]">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="font-bold text-[#172B4D] block">
                                            {{ $b->orderItem->variant->product->product_name ?? '-' }}
                                        </span>
                                        <span class="text-[11px] text-[#64748B] block">
                                            {{ $b->orderItem->variant->variant_name ?? '-' }} ({{ $b->orderItem->variant->sku ?? '-' }})
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-semibold">
                                        @if($b->qcResult)
                                            <span>{{ number_format($goodQty, 0, ',', '.') }}</span>
                                            <span class="text-[10px] text-[#64748B] block">dari {{ number_format($b->target_qty, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-[#64748B]">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        @if($b->status === \App\Enums\BatchStatus::CANCELLED || ($hpp['status'] ?? null) === 'BATCH_CANCELLED')
                                            <span class="badge badge-secondary text-[10px] px-2 py-0.5">Dibatalkan</span>
                                        @elseif($isReady)
                                            <span class="badge badge-success text-[10px] px-2 py-0.5">HPP Final</span>
                                        @elseif(($hpp['status'] ?? null) === 'BATCH_NOT_COMPLETED')
                                            <span class="badge badge-secondary text-[10px] px-2 py-0.5">Batch Belum Selesai</span>
                                        @elseif(($hpp['status'] ?? null) === 'MATERIAL_USAGE_UNRECORDED')
                                            <span class="badge badge-warning text-[10px] px-2 py-0.5">Pemakaian Belum Dicatat</span>
                                        @elseif(in_array($hpp['status'] ?? null, ['COSTING_SNAPSHOT_MISSING', 'COST_CONFIG_INCOMPLETE']))
                                            <span class="badge badge-warning text-[10px] px-2 py-0.5">Konfigurasi Biaya Belum Lengkap</span>
                                        @elseif(($hpp['status'] ?? null) === 'QC_RESULT_MISSING')
                                            <span class="badge badge-danger text-[10px] px-2 py-0.5">Data QC Tidak Lengkap</span>
                                        @elseif(($hpp['status'] ?? null) === 'PRODUCTION_RESULT_MISSING')
                                            <span class="badge badge-danger text-[10px] px-2 py-0.5">Data Produksi Tidak Lengkap</span>
                                        @elseif(($hpp['status'] ?? null) === 'MATERIAL_INTEGRITY_ERROR')
                                            <span class="badge badge-danger text-[10px] px-2 py-0.5">Data Material Bermasalah</span>
                                        @else
                                            <span class="badge badge-secondary text-[10px] px-2 py-0.5">Belum Final</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-bold text-sm text-[#172B4D]">
                                        @if($isReady)
                                            <span>Rp {{ number_format((float) $hpp['total_hpp'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-[#64748B] font-normal">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-bold text-xs text-[#8E1B5C]">
                                        @if($isReady && $hpp['hpp_per_good_pcs'] !== null)
                                            <span>Rp {{ number_format((float) $hpp['hpp_per_good_pcs'], 0, ',', '.') }}</span>
                                        @elseif($isReady && $goodQty == 0)
                                            <span class="text-[10px] text-[#64748B] font-normal">— (All Reject)</span>
                                        @else
                                            <span class="text-[#64748B] font-normal">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <a href="{{ route('owner.hpp.batches.show', $b) }}"
                                           class="inline-flex items-center justify-center gap-1 h-7 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                                            <span>Detail</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-xs text-[#64748B]">
                                        Tidak ada data batch produksi yang cocok dengan kriteria pencarian.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($batches->hasPages())
                    <div class="p-4 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                        {{ $batches->links() }}
                    </div>
                @endif
            </div>
        @endif

        {{-- ── 4. Tab: Pesanan ────────────────────────────────────────────── --}}
        @if($tab === 'orders')
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left text-[#172B4D]">
                        <thead class="bg-[#FFFCFB] text-[11px] text-[#64748B] uppercase font-bold border-b border-[#FFD8D8]">
                            <tr>
                                <th class="px-4 py-3.5">Nomor Order</th>
                                <th class="px-4 py-3.5">Pelanggan</th>
                                <th class="px-4 py-3.5 text-center">Total Batch</th>
                                <th class="px-4 py-3.5 text-right">Total Good Qty</th>
                                <th class="px-4 py-3.5 text-center">Status HPP Order</th>
                                <th class="px-4 py-3.5 text-right">Total HPP Pesanan</th>
                                <th class="px-4 py-3.5 text-right">HPP / Good Pcs</th>
                                <th class="px-4 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @forelse($orders as $o)
                                @php
                                    $orderHpp = $orderHppResults[$o->id] ?? null;
                                    $isReady = $orderHpp && $orderHpp['is_ready'];
                                    $totalGood = $orderHpp ? (float) $orderHpp['total_good_qty'] : 0;
                                @endphp
                                <tr class="hover:bg-[#FFFCFB]/50 transition">
                                    <td class="px-4 py-3.5 font-mono font-bold text-[#8E1B5C]">
                                        <a href="{{ route('owner.hpp.orders.show', $o) }}" class="hover:underline">
                                            {{ $o->order_no }}
                                        </a>
                                        <span class="text-[11px] text-[#64748B] font-sans block font-normal mt-0.5">
                                            {{ $o->order_date ? \Carbon\Carbon::parse($o->order_date)->format('d M Y') : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="font-bold text-[#172B4D] block">
                                            {{ $o->customer->customer_name ?? '-' }}
                                        </span>
                                        <span class="text-[11px] text-[#64748B] block">
                                            {{ $o->customer->phone ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center font-semibold">
                                        <span>{{ $orderHpp['batches_count'] ?? 0 }}</span>
                                        @if($orderHpp && $orderHpp['batches_count'] > 0)
                                            <span class="text-[10px] text-[#64748B] block font-normal">
                                                ({{ $orderHpp['batches_ready_count'] }}/{{ $orderHpp['batches_count'] }} Final)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-semibold">
                                        <span>{{ number_format($totalGood, 0, ',', '.') }}</span>
                                        <span class="text-[10px] text-[#64748B] block">Order: {{ number_format($o->total_qty, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        @if($isReady)
                                            <span class="badge badge-success text-[10px] px-2 py-0.5">HPP Final</span>
                                        @elseif($orderHpp['status'] === 'ORDER_FULFILLMENT_REMAINING')
                                            <span class="badge badge-warning text-[10px] px-2 py-0.5">Menunggu Pemenuhan</span>
                                        @elseif($orderHpp['status'] === 'ORDER_BATCHES_NOT_READY')
                                            <span class="badge badge-warning text-[10px] px-2 py-0.5">Batch Belum Final</span>
                                        @elseif($orderHpp['status'] === 'ORDER_NO_ACTIVE_BATCHES')
                                            <span class="badge badge-secondary text-[10px] px-2 py-0.5">Belum Ada Batch</span>
                                        @else
                                            <span class="badge badge-secondary text-[10px] px-2 py-0.5">Belum Final</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-bold text-sm text-[#172B4D]">
                                        @if($isReady)
                                            <span>Rp {{ number_format((float) $orderHpp['total_hpp'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-[#64748B] font-normal">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-bold text-xs text-[#8E1B5C]">
                                        @if($isReady && $orderHpp['hpp_per_good_pcs'] !== null)
                                            <span>Rp {{ number_format((float) $orderHpp['hpp_per_good_pcs'], 0, ',', '.') }}</span>
                                        @elseif($isReady && $totalGood == 0)
                                            <span class="text-[10px] text-[#64748B] font-normal">— (All Reject)</span>
                                        @else
                                            <span class="text-[#64748B] font-normal">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <a href="{{ route('owner.hpp.orders.show', $o) }}"
                                           class="inline-flex items-center justify-center gap-1 h-7 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                                            <span>Detail</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-xs text-[#64748B]">
                                        Tidak ada data sales order yang cocok dengan kriteria pencarian.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($orders->hasPages())
                    <div class="p-4 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        @endif

    </div>
</x-app-layout>
