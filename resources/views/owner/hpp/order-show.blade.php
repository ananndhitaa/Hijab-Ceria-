<x-app-layout pageTitle="Detail HPP Pesanan" breadcrumb="HPP Produksi > Detail Pesanan">
    <div class="max-w-5xl mx-auto space-y-4 sm:space-y-6">

        @php
            $isReady = $orderHpp['is_ready'];
            $totalGood = (float) ($orderHpp['total_good_qty'] ?? 0);
        @endphp

        {{-- ── 1. Header & Back Navigation ─────────────────────────────────── --}}
        <x-feature-header
            :title="$order->order_no"
            icon="order"
        >
            <x-slot:badge>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        DETAIL HPP ORDER
                    </span>
                    <span class="badge {{ $order->status->badgeClass() }} text-xs px-2.5 py-0.5">
                        {{ $order->status->label() }}
                    </span>
                    @if($isReady)
                        <span class="badge badge-success text-xs px-2.5 py-0.5">
                            HPP Order Final
                        </span>
                    @else
                        <span class="badge badge-warning text-xs px-2.5 py-0.5">
                            HPP Order Belum Final
                        </span>
                    @endif
                </div>
            </x-slot:badge>
            <p class="text-xs text-[#64748B] flex items-center gap-1.5 font-medium mt-1">
                <span>Pelanggan: <strong class="text-[#172B4D]">{{ $order->customer->customer_name ?? '-' }}</strong></span>
                <span class="text-[#FFD8D8]">&middot;</span>
                <span>Tanggal: {{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : '-' }}</span>
            </p>
            <x-slot:actions>
                <a href="{{ route('owner.hpp.index', ['tab' => 'orders']) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer shadow-xs shrink-0">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali ke HPP</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Order HPP Executive Card ────────────────────────────────── --}}
        @if($isReady)
            <div class="bg-gradient-to-br from-[#8E1B5C] to-[#172B4D] rounded-2xl p-6 text-white shadow-card">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <span class="text-[11px] uppercase tracking-wider text-[#FFD8D8] font-bold block">
                            Total HPP Pesanan Final
                        </span>
                        <div class="text-3xl sm:text-4xl font-extrabold font-mono">
                            Rp {{ number_format((float) $orderHpp['total_hpp'], 0, ',', '.') }}
                        </div>
                        <p class="text-xs text-[#FFD8D8]/90">
                            Agregasi dari {{ $orderHpp['batches_count'] }} batch produksi selesai dan terkonfirmasi.
                        </p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-xs rounded-xl p-4 border border-white/20 text-right shrink-0">
                        <span class="text-[10px] uppercase tracking-wider text-[#FFD8D8] font-bold block">
                            HPP per Good Pcs
                        </span>
                        <div class="text-2xl font-bold font-mono mt-0.5">
                            @if($orderHpp['hpp_per_good_pcs'] !== null)
                                Rp {{ number_format((float) $orderHpp['hpp_per_good_pcs'], 0, ',', '.') }}
                            @else
                                <span class="text-sm font-normal text-[#FFD8D8]">— (0 Good Output)</span>
                            @endif
                        </div>
                        <span class="text-[10px] text-[#FFD8D8] block mt-0.5">
                            Total {{ number_format($totalGood, 0, ',', '.') }} pcs produk lolos QC
                        </span>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-card text-amber-900 space-y-2">
                <div class="flex items-center gap-2 font-bold text-sm">
                    <svg class="w-5 h-5 text-amber-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>HPP Pesanan Belum Final</span>
                </div>
                <p class="text-xs leading-relaxed">
                    {{ $orderHpp['error'] ?? 'Seluruh batch produksi yang dibutuhkan untuk pesanan ini harus telah selesai dan biaya langsungnya terkonfirmasi sebelum HPP pesanan dapat difinalisasi.' }}
                </p>
            </div>
        @endif

        {{-- ── 3. Operational Summary Metrics ─────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Kuantitas Order</span>
                <span class="text-lg font-bold text-[#172B4D] font-mono mt-1 block">
                    {{ number_format($order->total_qty, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B]">pcs</span>
                </span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Total Good Output (QC)</span>
                <span class="text-lg font-bold text-emerald-700 font-mono mt-1 block">
                    {{ number_format($totalGood, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B]">pcs</span>
                </span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Total Batch Aktif</span>
                <span class="text-lg font-bold text-[#172B4D] font-mono mt-1 block">
                    {{ $orderHpp['batches_count'] ?? 0 }} <span class="text-xs font-normal text-[#64748B]">batch</span>
                </span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Batch HPP Final</span>
                <span class="text-lg font-bold text-[#8E1B5C] font-mono mt-1 block">
                    {{ $orderHpp['batches_ready_count'] ?? 0 }}/{{ $orderHpp['batches_count'] ?? 0 }}
                </span>
            </div>
        </div>

        {{-- ── 4. Included Batches Table ──────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-4">
            <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2.5">
                Daftar Batch Produksi Terkait
            </h2>

            @if(empty($orderHpp['batches']))
                <div class="p-6 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] text-xs text-[#64748B] text-center">
                    Belum ada batch produksi aktif yang terkait dengan pesanan ini.
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-[#FFD8D8]">
                    <table class="w-full text-xs text-left text-[#172B4D]">
                        <thead class="bg-[#FFFCFB] text-[11px] text-[#64748B] uppercase font-bold border-b border-[#FFD8D8]">
                            <tr>
                                <th class="px-4 py-3">No. Batch</th>
                                <th class="px-4 py-3">Varian &amp; SKU</th>
                                <th class="px-4 py-3 text-right">Target</th>
                                <th class="px-4 py-3 text-right">Good Qty (QC)</th>
                                <th class="px-4 py-3 text-right">Biaya Material</th>
                                <th class="px-4 py-3 text-right">Biaya Langsung</th>
                                <th class="px-4 py-3 text-right">Total HPP Batch</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @foreach($orderHpp['batches'] as $bDetail)
                                <tr class="hover:bg-[#FFFCFB]/50 transition">
                                    <td class="px-4 py-3 font-mono font-bold text-[#8E1B5C]">
                                        <a href="{{ route('owner.hpp.batches.show', $bDetail['batch_id']) }}" class="hover:underline">
                                            {{ $bDetail['batch_no'] }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-bold text-[#172B4D] block">{{ $bDetail['variant_name'] }}</span>
                                        <span class="text-[10px] text-[#64748B] block">{{ $bDetail['sku'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-[#64748B]">
                                        {{ number_format((float) $bDetail['target_qty'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-emerald-700">
                                        {{ number_format((float) $bDetail['good_qty'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono">
                                        @if($bDetail['material_cost'] !== null)
                                            Rp {{ number_format((float) $bDetail['material_cost'], 0, ',', '.') }}
                                        @else
                                            <span class="text-[#64748B]">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono">
                                        @if($bDetail['direct_cost'] !== null)
                                            Rp {{ number_format((float) $bDetail['direct_cost'], 0, ',', '.') }}
                                        @else
                                            <span class="text-[#64748B]">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-[#172B4D]">
                                        @if($bDetail['total_hpp'] !== null)
                                            Rp {{ number_format((float) $bDetail['total_hpp'], 0, ',', '.') }}
                                        @else
                                            <span class="text-[#64748B] font-normal">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($bDetail['is_ready'])
                                            <span class="badge badge-success text-[9px] px-2 py-0.5">Final</span>
                                        @else
                                            <span class="badge badge-warning text-[9px] px-2 py-0.5">Belum Final</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('owner.hpp.batches.show', $bDetail['batch_id']) }}"
                                           class="inline-flex items-center justify-center h-6 px-2 rounded-md border border-[#FFD8D8] bg-white text-[10px] font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if($isReady)
                            <tfoot class="bg-[#FFFCFB] font-bold border-t border-[#FFD8D8]">
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-[#64748B] uppercase text-[11px]">Total HPP Pesanan</td>
                                    <td class="px-4 py-3 text-right text-sm text-[#8E1B5C] font-mono">
                                        Rp {{ number_format((float) $orderHpp['total_hpp'], 0, ',', '.') }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>

        {{-- ── 5. Cancelled Batches (If Any) ──────────────────────────────── --}}
        @if(!empty($orderHpp['cancelled_batches']))
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card space-y-2">
                <div class="flex items-center gap-2">
                    <span class="badge badge-secondary text-[10px]">Batch Dibatalkan</span>
                    <span class="text-xs text-[#64748B]">Batch berikut dibatalkan dan tidak dihitung ke dalam total HPP pesanan.</span>
                </div>
                <div class="flex flex-wrap gap-2 pt-1">
                    @foreach($orderHpp['cancelled_batches'] as $cBatch)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-mono border border-slate-200">
                            <strong>{{ $cBatch['batch_no'] }}</strong> ({{ $cBatch['variant_name'] }} - {{ number_format((float) $cBatch['target_qty'], 0, ',', '.') }} pcs)
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
