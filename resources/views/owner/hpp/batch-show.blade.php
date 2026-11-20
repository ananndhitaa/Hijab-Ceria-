<x-app-layout pageTitle="Detail HPP Batch" breadcrumb="HPP Produksi > Detail Batch">
    <div class="max-w-5xl mx-auto space-y-4 sm:space-y-6">

        @php
            $isReady = $batchHpp['is_ready'];
            $goodQty = $batch->qcResult ? (float) $batch->qcResult->good_qty : 0;
            $rejectQty = $batch->qcResult ? (float) $batch->qcResult->reject_qty : 0;
            $actualQty = (float) $batch->actual_qty;
            $breakdown = $batchHpp['breakdown'] ?? [];
        @endphp

        {{-- ── 1. Header & Back Navigation ─────────────────────────────────── --}}
        <x-feature-header
            :title="$batch->batch_no"
            icon="production"
        >
            <x-slot:badge>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        DETAIL HPP BATCH
                    </span>
                    <span class="badge {{ $batch->status->badgeClass() }} text-xs px-2.5 py-0.5">
                        {{ $batch->status->label() }}
                    </span>
                    @if($isReady)
                        <span class="badge badge-success text-xs px-2.5 py-0.5">
                            HPP Final
                        </span>
                    @else
                        <span class="badge badge-warning text-xs px-2.5 py-0.5">
                            HPP Belum Final
                        </span>
                    @endif
                </div>
            </x-slot:badge>
            <p class="text-xs text-[#64748B] flex items-center gap-1.5 font-medium mt-1">
                <span>Dibuat {{ $batch->created_at ? $batch->created_at->format('d M Y') : '-' }}</span>
                @if($batch->orderItem && $batch->orderItem->order)
                    <span class="text-[#FFD8D8]">&middot;</span>
                    <span>Sales Order:</span>
                    <a href="{{ route('owner.hpp.orders.show', $batch->orderItem->order) }}" class="font-mono font-semibold text-[#8E1B5C] hover:underline">
                        {{ $batch->orderItem->order->order_no }}
                    </a>
                @endif
            </p>
            <x-slot:actions>
                <a href="{{ route('owner.hpp.index', ['tab' => 'batches']) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer shadow-xs shrink-0">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali ke HPP</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. HPP Readiness Executive Card ────────────────────────────── --}}
        @if($isReady)
            <div class="bg-gradient-to-br from-[#8E1B5C] to-[#172B4D] rounded-2xl p-6 text-white shadow-card">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <span class="text-[11px] uppercase tracking-wider text-[#FFD8D8] font-bold block">
                            Harga Pokok Produksi (HPP) Final
                        </span>
                        <div class="text-3xl sm:text-4xl font-extrabold font-mono">
                            Rp {{ number_format((float) $batchHpp['total_hpp'], 0, ',', '.') }}
                        </div>
                        <p class="text-xs text-[#FFD8D8]/90">
                            Dihitung otomatis oleh sistem dari pemakaian bahan riil dan snapshot tarif produksi.
                        </p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-xs rounded-xl p-4 border border-white/20 text-right shrink-0">
                        <span class="text-[10px] uppercase tracking-wider text-[#FFD8D8] font-bold block">
                            HPP per Good Pcs
                        </span>
                        <div class="text-2xl font-bold font-mono mt-0.5">
                            @if($batchHpp['hpp_per_good_pcs'] !== null)
                                Rp {{ number_format((float) $batchHpp['hpp_per_good_pcs'], 0, ',', '.') }}
                            @else
                                <span class="text-sm font-normal text-[#FFD8D8]">— (0 Good Output)</span>
                            @endif
                        </div>
                        <span class="text-[10px] text-[#FFD8D8] block mt-0.5">
                            Berdasarkan {{ number_format($goodQty, 0, ',', '.') }} pcs output lolos QC
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
                    <span>HPP Batch Belum Final</span>
                </div>
                <p class="text-xs leading-relaxed">
                    {{ $batchHpp['error'] ?? 'Batch ini masih dalam tahapan produksi atau menunggu kelengkapan data operasional.' }}
                </p>
            </div>
        @endif

        {{-- ── 3. Operational Metadata ────────────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Target Batch</span>
                <span class="text-lg font-bold text-[#172B4D] font-mono mt-1 block">
                    {{ number_format($batch->target_qty, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B]">pcs</span>
                </span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Hasil Produksi</span>
                <span class="text-lg font-bold text-[#172B4D] font-mono mt-1 block">
                    {{ number_format($actualQty, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B]">pcs</span>
                </span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Lolos QC (Good)</span>
                <span class="text-lg font-bold text-emerald-700 font-mono mt-1 block">
                    {{ number_format($goodQty, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B]">pcs</span>
                </span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Gagal QC (Reject)</span>
                <span class="text-lg font-bold {{ $rejectQty > 0 ? 'text-rose-600' : 'text-[#64748B]' }} font-mono mt-1 block">
                    {{ number_format($rejectQty, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B]">pcs</span>
                </span>
            </div>
        </div>

        {{-- ── 4. Cost Breakdown (5 Categories) ───────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-4">
            <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2.5">
                Rincian Komponen HPP
            </h2>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                <div class="bg-[#FFFCFB] p-3 rounded-xl border border-[#FFD8D8]">
                    <span class="text-[10px] text-[#64748B] font-semibold block uppercase">1. Bahan Baku</span>
                    <div class="font-bold text-[#8E1B5C] font-mono text-sm mt-1">
                        @if($batchHpp['material_cost'] !== null)
                            Rp {{ number_format((float) $batchHpp['material_cost'], 0, ',', '.') }}
                        @else
                            <span class="text-xs font-normal text-[#64748B]">Belum tercatat</span>
                        @endif
                    </div>
                    <span class="text-[10px] text-[#64748B] block mt-0.5">Pemakaian Aktual BOM</span>
                </div>

                <div class="bg-[#FFFCFB] p-3 rounded-xl border border-[#FFD8D8]">
                    <span class="text-[10px] text-[#64748B] font-semibold block uppercase">2. Jasa Printing</span>
                    <div class="font-bold text-[#172B4D] font-mono text-sm mt-1">
                        Rp {{ number_format((float) ($breakdown['printing'] ?? 0), 0, ',', '.') }}
                    </div>
                    @if($batch->requires_printing === false)
                        <span class="text-[10px] text-amber-700 block mt-0.5">Rute: Tanpa Print</span>
                    @else
                        <span class="text-[10px] text-[#64748B] block mt-0.5">Biaya Jasa Print</span>
                    @endif
                </div>

                <div class="bg-[#FFFCFB] p-3 rounded-xl border border-[#FFD8D8]">
                    <span class="text-[10px] text-[#64748B] font-semibold block uppercase">3. Tenaga Kerja</span>
                    <div class="font-bold text-[#172B4D] font-mono text-sm mt-1">
                        Rp {{ number_format((float) ($breakdown['direct_labor'] ?? 0), 0, ',', '.') }}
                    </div>
                    <span class="text-[10px] text-[#64748B] block mt-0.5">Upah Langsung</span>
                </div>

                <div class="bg-[#FFFCFB] p-3 rounded-xl border border-[#FFD8D8]">
                    <span class="text-[10px] text-[#64748B] font-semibold block uppercase">4. Proses Pengemasan</span>
                    <div class="font-bold text-[#172B4D] font-mono text-sm mt-1">
                        Rp {{ number_format((float) ($breakdown['packaging_process'] ?? 0), 0, ',', '.') }}
                    </div>
                    <span class="text-[10px] text-[#64748B] block mt-0.5">Jasa/Proses Packing</span>
                </div>

                <div class="bg-[#FFFCFB] p-3 rounded-xl border border-[#FFD8D8]">
                    <span class="text-[10px] text-[#64748B] font-semibold block uppercase">5. Biaya Lain</span>
                    <div class="font-bold text-[#172B4D] font-mono text-sm mt-1">
                        Rp {{ number_format((float) ($breakdown['other_direct'] ?? 0), 0, ',', '.') }}
                    </div>
                    <span class="text-[10px] text-[#64748B] block mt-0.5">Lain-lain Langsung</span>
                </div>
            </div>
        </div>

        {{-- ── 5. Material Usage Table ────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-3">
            <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                Rincian Pemakaian Bahan Baku (Snapshot Alokasi Gudang)
            </h2>

            @if(empty($materialBreakdown['items']))
                <div class="p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] text-xs text-[#64748B] text-center">
                    {{ $materialBreakdown['error'] ?? 'Belum ada data pemakaian bahan baku.' }}
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-[#FFD8D8]">
                    <table class="w-full text-xs text-left text-[#172B4D]">
                        <thead class="bg-[#FFFCFB] text-[11px] text-[#64748B] uppercase font-bold border-b border-[#FFD8D8]">
                            <tr>
                                <th class="px-3.5 py-2.5">Nama Material</th>
                                <th class="px-3.5 py-2.5 text-right">Teralokasi</th>
                                <th class="px-3.5 py-2.5 text-right">Terpakai Riil</th>
                                <th class="px-3.5 py-2.5 text-right">Harga Modal Snapshot</th>
                                <th class="px-3.5 py-2.5 text-right">Subtotal Biaya</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @foreach($materialBreakdown['items'] as $mItem)
                                <tr>
                                    <td class="px-3.5 py-2.5 font-semibold text-[#172B4D]">
                                        {{ $mItem['material_name'] }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B]">
                                        {{ number_format((float) $mItem['allocated_qty'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                        {{ number_format((float) $mItem['used_qty'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B]">
                                        Rp {{ number_format((float) $mItem['unit_cost_snapshot'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-right font-mono font-bold text-[#8E1B5C]">
                                        Rp {{ number_format((float) $mItem['subtotal_cost'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-[#FFFCFB] font-bold border-t border-[#FFD8D8]">
                            <tr>
                                <td colspan="4" class="px-3.5 py-2.5 text-[#64748B] uppercase text-[11px]">Total Biaya Bahan Baku</td>
                                <td class="px-3.5 py-2.5 text-right text-sm text-[#8E1B5C] font-mono">
                                    Rp {{ number_format((float) ($materialBreakdown['amount'] ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        {{-- ── 6. Automated Direct Cost Rates Table ─────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-3">
            <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                Rincian Tarif Produksi &amp; Perhitungan Biaya Langsung
            </h2>

            <div class="overflow-x-auto rounded-xl border border-[#FFD8D8]">
                <table class="w-full text-xs text-left text-[#172B4D]">
                    <thead class="bg-[#FFFCFB] text-[11px] text-[#64748B] uppercase font-bold border-b border-[#FFD8D8]">
                        <tr>
                            <th class="px-3.5 py-2.5">Komponen Biaya</th>
                            <th class="px-3.5 py-2.5">Basis Perhitungan</th>
                            <th class="px-3.5 py-2.5 text-right">Tarif Snapshot</th>
                            <th class="px-3.5 py-2.5 text-right">Volume</th>
                            <th class="px-3.5 py-2.5 text-right">Subtotal Biaya</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        {{-- Printing --}}
                        <tr>
                            <td class="px-3.5 py-2.5 font-semibold">
                                <span class="badge bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8] text-[10px]">
                                    Jasa Printing
                                </span>
                            </td>
                            <td class="px-3.5 py-2.5 text-[#64748B]">
                                {{ $batch->effective_requires_printing ? 'Kuantitas Hasil Aktual' : 'Rute Tanpa Printing' }}
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B]">
                                Rp {{ number_format((float) ($batch->printing_cost_per_unit_snapshot ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B]">
                                {{ $batch->effective_requires_printing ? number_format($actualQty, 0, ',', '.') : 0 }} pcs
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                Rp {{ number_format((float) ($breakdown['printing'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Direct Labor --}}
                        <tr>
                            <td class="px-3.5 py-2.5 font-semibold">
                                <span class="badge bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8] text-[10px]">
                                    Tenaga Kerja Langsung
                                </span>
                            </td>
                            <td class="px-3.5 py-2.5 text-[#64748B]">
                                Kuantitas Hasil Aktual
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B]">
                                Rp {{ number_format((float) ($batch->direct_labor_cost_per_unit_snapshot ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B]">
                                {{ number_format($actualQty, 0, ',', '.') }} pcs
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                Rp {{ number_format((float) ($breakdown['direct_labor'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Packaging Process --}}
                        <tr>
                            <td class="px-3.5 py-2.5 font-semibold">
                                <span class="badge bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8] text-[10px]">
                                    Proses Pengemasan
                                </span>
                            </td>
                            <td class="px-3.5 py-2.5 text-[#64748B]">
                                Kuantitas Lolos QC (Good Output)
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B]">
                                Rp {{ number_format((float) ($batch->packaging_process_cost_per_good_unit_snapshot ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B]">
                                {{ number_format($goodQty, 0, ',', '.') }} pcs
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                Rp {{ number_format((float) ($breakdown['packaging_process'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Other Direct --}}
                        <tr>
                            <td class="px-3.5 py-2.5 font-semibold">
                                <span class="badge bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8] text-[10px]">
                                    Biaya Langsung Lain
                                </span>
                            </td>
                            <td class="px-3.5 py-2.5 text-[#64748B]">
                                Kuantitas Hasil Aktual
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B]">
                                Rp {{ number_format((float) ($batch->other_direct_cost_per_unit_snapshot ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B]">
                                {{ number_format($actualQty, 0, ',', '.') }} pcs
                            </td>
                            <td class="px-3.5 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                Rp {{ number_format((float) ($breakdown['other_direct'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-[#FFFCFB] font-bold border-t border-[#FFD8D8]">
                        <tr>
                            <td colspan="4" class="px-3.5 py-2.5 text-[#64748B] uppercase text-[11px]">Total Biaya Langsung Non-Material</td>
                            <td class="px-3.5 py-2.5 text-right text-sm text-[#8E1B5C] font-mono">
                                Rp {{ number_format((float) ($batchHpp['direct_cost'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
