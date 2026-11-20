<x-app-layout pageTitle="Detail Profitabilitas Pesanan" breadcrumb="Profitabilitas Pesanan > Detail">
    <div class="max-w-5xl mx-auto space-y-4 sm:space-y-6">

        @php
            $isReady = $profitability['is_ready'];
            $revenue = $profitability['revenue_amount'] ?? '0.00';
            $hpp = $profitability['hpp_amount'] ?? null;
            $gp = $profitability['gross_profit'] ?? null;
            $gm = $profitability['gross_margin'] ?? null;
            $opex = $profitability['operating_expense'] ?? '0.00';
            $np = $profitability['net_profit'] ?? null;
            $nm = $profitability['net_margin'] ?? null;
            $isGpNegative = $gp !== null && bccomp((string) $gp, '0.00', 2) < 0;
            $isNpNegative = $np !== null && bccomp((string) $np, '0.00', 2) < 0;
            $rawOrderStatus = $order->getRawOriginal('status') ?? ($order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status);
            $orderStatusBadge = ($order->status instanceof \App\Enums\OrderStatus) ? $order->status->badgeClass() : 'badge-secondary';
            $orderStatusLabel = ($order->status instanceof \App\Enums\OrderStatus) ? $order->status->label() : ($rawOrderStatus === 'CANCELLED' ? 'Dibatalkan' : (string) $rawOrderStatus);
            $totalSold = $totalQtySold ?? $order->total_qty ?? 0;
            $itemBreakdown = $itemBreakdown ?? [];
        @endphp

        {{-- ── 1. Header & Actions ─────────────────────────────────────────── --}}
        <x-feature-header
            :title="$order->order_no"
            icon="order"
        >
            <x-slot:badge>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        PROFITABILITAS ORDER
                    </span>
                    <span class="badge {{ $orderStatusBadge }} text-xs px-2.5 py-0.5">
                        {{ $orderStatusLabel }}
                    </span>
                    @if($isReady)
                        <span class="badge badge-success text-xs px-2.5 py-0.5">
                            Profit Final
                        </span>
                    @else
                        <span class="badge badge-warning text-xs px-2.5 py-0.5">
                            Menunggu HPP Final
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
                <a href="{{ route('owner.hpp.orders.show', $order) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-[#FFF9FB] text-xs sm:text-sm font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition cursor-pointer shadow-2xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span>Lihat Rincian HPP</span>
                </a>

                <a href="{{ route('owner.profitability.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Executive Profitability Summary Card ─────────────────────── --}}
        @if($isReady)
            <div class="bg-gradient-to-br from-[#8E1B5C] to-[#172B4D] rounded-2xl p-6 text-white shadow-card">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <span class="text-[11px] uppercase tracking-wider text-[#FFD8D8] font-bold block">
                            Laba Bersih Pesanan (Net Profit)
                        </span>
                        <div class="text-3xl sm:text-4xl font-extrabold font-mono {{ $isNpNegative ? 'text-[#FFD8D8]' : 'text-white' }}">
                            {{ $isNpNegative ? '-Rp ' . number_format(abs((float) $np), 0, ',', '.') : 'Rp ' . number_format((float) $np, 0, ',', '.') }}
                        </div>
                        <p class="text-xs text-[#FFD8D8]/90">
                            Pendapatan dikurangi total HPP dan beban operasional terkait pesanan.
                        </p>
                    </div>

                    <div class="bg-white/10 backdrop-blur-xs rounded-xl p-4 border border-white/20 text-right shrink-0">
                        <span class="text-[10px] uppercase tracking-wider text-[#FFD8D8] font-bold block">
                            Margin Bersih (Net Margin)
                        </span>
                        <div class="text-2xl font-bold font-mono mt-0.5 {{ $isNpNegative ? 'text-[#FFD8D8]' : 'text-white' }}">
                            @if($nm !== null)
                                {{ number_format((float) $nm, 2, ',', '.') }}%
                            @else
                                <span class="text-sm font-normal text-[#FFD8D8]">—</span>
                            @endif
                        </div>
                        <span class="text-[10px] text-[#FFD8D8] block mt-0.5">
                            Gross Margin: {{ $gm !== null ? number_format((float) $gm, 2, ',', '.') . '%' : '—' }}
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
                    <span>Laba Pesanan Belum Final</span>
                </div>
                <p class="text-xs leading-relaxed">
                    {{ $profitability['error'] ?? 'HPP seluruh batch yang dibutuhkan oleh pesanan ini harus berstatus final sebelum laba kotor dan laba bersih dapat dihitung.' }}
                </p>
            </div>
        @endif

        {{-- ── 3. Ringkasan Penjualan & Laba (Executive Summary) ───────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-4">
            <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#8E1B5C]"></div>
                    <h2 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                        Ringkasan Penjualan & Laba
                    </h2>
                </div>
                <span class="text-[11px] text-[#64748B]">
                    Total Terjual: <strong class="font-mono text-[#172B4D]">{{ number_format((float) $totalSold, 0, ',', '.') }} pcs</strong>
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                {{-- 1. Jumlah Terjual --}}
                <div class="p-3.5 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] space-y-1">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Jumlah Terjual</span>
                    <div class="text-base sm:text-lg font-bold font-mono text-[#172B4D]">
                        {{ number_format((float) $totalSold, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B]">pcs</span>
                    </div>
                    <span class="text-[10px] text-[#64748B] block">Unit barang</span>
                </div>

                {{-- 2. Nilai Penjualan --}}
                <div class="p-3.5 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] space-y-1">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Nilai Penjualan</span>
                    <div class="text-base sm:text-lg font-bold font-mono text-[#172B4D]">
                        Rp {{ number_format((float) $revenue, 0, ',', '.') }}
                    </div>
                    <span class="text-[10px] text-[#64748B] block">Total omset</span>
                </div>

                {{-- 3. Modal Produksi (HPP) --}}
                <div class="p-3.5 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] space-y-1">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Modal Produksi (HPP)</span>
                    <div class="text-base sm:text-lg font-bold font-mono text-[#172B4D]">
                        @if($isReady && $hpp !== null)
                            Rp {{ number_format((float) $hpp, 0, ',', '.') }}
                        @else
                            <span class="text-xs font-semibold text-amber-700">Belum Final</span>
                        @endif
                    </div>
                    <span class="text-[10px] text-[#64748B] block">Biaya produksi</span>
                </div>

                {{-- 4. Laba Kotor --}}
                <div class="p-3.5 rounded-xl bg-[#FFD8D8]/50 border border-[#FFD8D8] space-y-1">
                    <span class="text-[10px] font-bold text-[#8E1B5C] uppercase tracking-wider block">Laba Kotor</span>
                    <div class="text-base sm:text-lg font-bold font-mono {{ $isGpNegative ? 'text-rose-600' : 'text-[#8E1B5C]' }}">
                        @if($isReady && $gp !== null)
                            {{ $isGpNegative ? '-Rp ' . number_format(abs((float) $gp), 0, ',', '.') : 'Rp ' . number_format((float) $gp, 0, ',', '.') }}
                        @else
                            <span class="text-xs font-semibold text-[#64748B]">—</span>
                        @endif
                    </div>
                    <span class="text-[10px] text-[#64748B] block">
                        {{ $gm !== null ? 'Margin: ' . number_format((float) $gm, 2, ',', '.') . '%' : '—' }}
                    </span>
                </div>

                {{-- 5. Beban Operasional --}}
                <div class="p-3.5 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] space-y-1">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Beban Operasional</span>
                    <div class="text-base sm:text-lg font-bold font-mono text-[#172B4D]">
                        Rp {{ number_format((float) $opex, 0, ',', '.') }}
                    </div>
                    <span class="text-[10px] text-[#64748B] block">Biaya pesanan</span>
                </div>

                {{-- 6. Laba Bersih --}}
                <div class="p-3.5 rounded-xl {{ $isReady ? 'bg-emerald-50/70 border-emerald-200' : 'bg-[#FFFCFB] border-[#FFD8D8]' }} border space-y-1">
                    <span class="text-[10px] font-bold {{ $isReady ? 'text-emerald-800' : 'text-[#64748B]' }} uppercase tracking-wider block">Laba Bersih</span>
                    <div class="text-base sm:text-lg font-bold font-mono {{ $isNpNegative ? 'text-rose-600' : ($isReady ? 'text-emerald-700' : 'text-[#172B4D]') }}">
                        @if($isReady && $np !== null)
                            {{ $isNpNegative ? '-Rp ' . number_format(abs((float) $np), 0, ',', '.') : 'Rp ' . number_format((float) $np, 0, ',', '.') }}
                        @else
                            <span class="text-xs font-semibold text-[#64748B]">—</span>
                        @endif
                    </div>
                    <span class="text-[10px] {{ $isReady ? 'text-emerald-700 font-semibold' : 'text-[#64748B]' }} block">
                        {{ $nm !== null ? 'Margin: ' . number_format((float) $nm, 2, ',', '.') . '%' : '—' }}
                    </span>
                </div>
            </div>

            {{-- Formula clarification bar --}}
            <div class="p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] text-[11px] text-[#64748B] flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 font-medium">
                    <span class="font-bold text-[#8E1B5C]">Alur Perhitungan:</span>
                    <span>Penjualan &minus; Modal Produksi (HPP) = <strong>Laba Kotor</strong> &minus; Biaya Operasional = <strong>Laba Bersih</strong></span>
                </div>
                <div class="text-[10px] text-[#64748B]/80 shrink-0">
                    Perhitungan otomatis berbasis snapshot transaksi
                </div>
            </div>
        </div>

        {{-- ── 4. Rincian Item & Penjualan Produk ──────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-4">
            <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#8E1B5C]"></div>
                    <h2 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                        Rincian Item & Penjualan Produk
                    </h2>
                </div>
                <span class="text-[11px] text-[#64748B]">
                    {{ count($itemBreakdown) }} baris item
                </span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-[#FFD8D8]">
                <table class="w-full text-xs text-left text-[#172B4D]">
                    <thead class="bg-[#FFFCFB] text-[10px] text-[#64748B] uppercase font-bold border-b border-[#FFD8D8]">
                        <tr>
                            <th class="py-2.5 px-3.5 w-12 text-center">No</th>
                            <th class="py-2.5 px-3.5">Produk & Varian</th>
                            <th class="py-2.5 px-3.5">SKU</th>
                            <th class="py-2.5 px-3.5 text-right">Qty Terjual</th>
                            <th class="py-2.5 px-3.5 text-right">Harga Jual</th>
                            <th class="py-2.5 px-3.5 text-right">Nilai Penjualan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($itemBreakdown as $idx => $item)
                            <tr class="hover:bg-[#FFFCFB] transition">
                                <td class="py-2.5 px-3.5 text-center text-[#64748B] font-mono">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3.5 font-semibold text-[#172B4D]">
                                    {{ $item['product_name'] }} &mdash; <span class="text-[#8E1B5C]">{{ $item['variant_name'] }}</span>
                                </td>
                                <td class="py-2.5 px-3.5 font-mono text-[#64748B] text-[11px]">{{ $item['sku'] ?? '-' }}</td>
                                <td class="py-2.5 px-3.5 text-right font-mono font-semibold">{{ number_format((float) $item['qty'], 0, ',', '.') }} pcs</td>
                                <td class="py-2.5 px-3.5 text-right font-mono text-[#64748B]">Rp {{ number_format((float) $item['unit_price'], 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3.5 text-right font-mono font-bold text-[#172B4D]">Rp {{ number_format((float) $item['subtotal'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-[#64748B]">Tidak ada rincian item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-[#FFFCFB] font-bold border-t border-[#FFD8D8]">
                        <tr>
                            <td colspan="3" class="py-2.5 px-3.5 text-[#64748B] uppercase text-[10px]">Total Pesanan</td>
                            <td class="py-2.5 px-3.5 text-right font-mono text-[#172B4D]">{{ number_format((float) $totalSold, 0, ',', '.') }} pcs</td>
                            <td></td>
                            <td class="py-2.5 px-3.5 text-right font-mono text-sm text-[#8E1B5C]">Rp {{ number_format((float) $revenue, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- ── 4. Profit Waterfall Breakdown ──────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-4">
            <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2.5">
                Struktur Perhitungan Laba Pesanan (Profit Waterfall)
            </h2>

            <div class="space-y-3 text-xs">
                {{-- Row 1: Revenue --}}
                <div class="flex items-center justify-between p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <div>
                        <span class="font-bold text-[#172B4D] block">Pendapatan Pesanan (Revenue)</span>
                        <span class="text-[10px] text-[#64748B]">Total nilai pesanan berdasarkan snapshot harga transaksi</span>
                    </div>
                    <div class="text-right font-mono font-bold text-sm text-[#172B4D]">
                        Rp {{ number_format((float) $revenue, 0, ',', '.') }}
                    </div>
                </div>

                {{-- Row 2: HPP --}}
                <div class="flex items-center justify-between p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <div>
                        <span class="font-bold text-[#172B4D] block">(-) Harga Pokok Produksi (HPP)</span>
                        <span class="text-[10px] text-[#64748B]">Total biaya material aktual + biaya langsung batch</span>
                    </div>
                    <div class="text-right font-mono font-bold text-sm text-[#64748B]">
                        @if($isReady && $hpp !== null)
                            Rp {{ number_format((float) $hpp, 0, ',', '.') }}
                        @else
                            <span class="text-xs font-normal">— (Belum Final)</span>
                        @endif
                    </div>
                </div>

                {{-- Row 3: Gross Profit --}}
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-[#FFD8D8]/50 border border-[#FFD8D8]">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-[#8E1B5C] text-sm block">(=) Laba Kotor (Gross Profit)</span>
                            @if($isReady && $gm !== null)
                                <span class="badge bg-[#8E1B5C] text-white text-[10px] px-2 py-0.5">
                                    Margin: {{ number_format((float) $gm, 2, ',', '.') }}%
                                </span>
                            @endif
                        </div>
                        <span class="text-[10px] text-[#64748B]">Pendapatan dikurangi total HPP produksi</span>
                    </div>
                    <div class="text-right font-mono font-extrabold text-base {{ $isGpNegative ? 'text-rose-600' : 'text-[#8E1B5C]' }}">
                        @if($isReady && $gp !== null)
                            {{ $isGpNegative ? '-Rp ' . number_format(abs((float) $gp), 0, ',', '.') : 'Rp ' . number_format((float) $gp, 0, ',', '.') }}
                        @else
                            <span class="text-xs font-normal text-[#64748B]">—</span>
                        @endif
                    </div>
                </div>

                {{-- Row 4: Attributed Operating Expense --}}
                <div class="flex items-center justify-between p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <div>
                        <span class="font-bold text-[#172B4D] block">(-) Beban Operasional Terkait Pesanan</span>
                        <span class="text-[10px] text-[#64748B]">Pengeluaran operasional spesifik yang diatribusikan langsung</span>
                    </div>
                    <div class="text-right font-mono font-bold text-sm text-[#64748B]">
                        Rp {{ number_format((float) $opex, 0, ',', '.') }}
                    </div>
                </div>

                {{-- Row 5: Net Profit --}}
                <div class="flex items-center justify-between p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-emerald-900 text-sm block">(=) Laba Bersih Pesanan (Net Profit)</span>
                            @if($isReady && $nm !== null)
                                <span class="badge bg-emerald-700 text-white text-[10px] px-2 py-0.5">
                                    Margin: {{ number_format((float) $nm, 2, ',', '.') }}%
                                </span>
                            @endif
                        </div>
                        <span class="text-[10px] text-emerald-700">Hasil akhir profitabilitas pesanan setelah seluruh beban operasional</span>
                    </div>
                    <div class="text-right font-mono font-extrabold text-lg {{ $isNpNegative ? 'text-rose-600' : 'text-emerald-800' }}">
                        @if($isReady && $np !== null)
                            {{ $isNpNegative ? '-Rp ' . number_format(abs((float) $np), 0, ',', '.') : 'Rp ' . number_format((float) $np, 0, ',', '.') }}
                        @else
                            <span class="text-xs font-normal text-[#64748B]">—</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 5. Attributed Operating Expenses Detail Table ──────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-3">
            <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                Rincian Beban Operasional Terkait Pesanan
            </h2>

            @if(empty($profitability['operating_expenses']['items']))
                <div class="p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] text-xs text-[#64748B] text-center">
                    Tidak ada beban operasional yang diatribusikan langsung ke pesanan ini.
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-[#FFD8D8]">
                    <table class="w-full text-xs text-left text-[#172B4D]">
                        <thead class="bg-[#FFFCFB] text-[11px] text-[#64748B] uppercase font-bold border-b border-[#FFD8D8]">
                            <tr>
                                <th class="px-3.5 py-2.5">Tanggal</th>
                                <th class="px-3.5 py-2.5">Deskripsi Beban</th>
                                <th class="px-3.5 py-2.5 text-right">Jumlah (Rp)</th>
                                <th class="px-3.5 py-2.5">Dicatat Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @foreach($profitability['operating_expenses']['items'] as $expItem)
                                <tr>
                                    <td class="px-3.5 py-2.5 font-mono text-[#64748B]">
                                        {{ $expItem['expense_date'] ?? '-' }}
                                    </td>
                                    <td class="px-3.5 py-2.5 font-semibold text-[#172B4D]">
                                        {{ $expItem['description'] ?: '-' }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                        Rp {{ number_format((float) $expItem['amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-[11px] text-[#64748B]">
                                        {{ $expItem['created_by_name'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-[#FFFCFB] font-bold border-t border-[#FFD8D8]">
                            <tr>
                                <td colspan="2" class="px-3.5 py-2.5 text-[#64748B] uppercase text-[11px]">Total Beban Operasional</td>
                                <td class="px-3.5 py-2.5 text-right text-sm text-[#8E1B5C] font-mono">
                                    Rp {{ number_format((float) $opex, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
