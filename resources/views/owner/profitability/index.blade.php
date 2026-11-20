<x-app-layout pageTitle="Profitabilitas Pesanan" breadcrumb="Profitabilitas Pesanan">
    <div class="max-w-7xl mx-auto space-y-4 sm:space-y-6">

        {{-- ── 1. Page Header ─────────────────────────────────────────────── --}}
        <x-feature-header
            title="Profitabilitas Pesanan"
            subtitle="Pantau pendapatan, HPP, dan laba setiap Sales Order."
            badge="Executive Monitoring"
            icon="finance"
        >
            <x-slot:actions>
                <a href="{{ route('owner.hpp.index', ['tab' => 'orders']) }}"
                   class="inline-flex items-center justify-center gap-2 h-11 sm:h-12 px-5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs sm:text-sm font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span>Monitoring HPP</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Compact Financial Summary Cards ──────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Status Profit</span>
                <div class="mt-1 space-y-0.5">
                    <div class="text-base font-bold text-emerald-700 font-mono flex items-center gap-1.5">
                        <span>{{ $readyOrdersCount }}</span>
                        <span class="text-[11px] font-normal text-[#172B4D]">Profit Final</span>
                    </div>
                    <div class="text-xs font-semibold text-amber-700 font-mono flex items-center gap-1.5">
                        <span>{{ $notReadyOrdersCount }}</span>
                        <span class="text-[10px] font-normal text-[#64748B]">Menunggu HPP Final</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Total Revenue</span>
                <div class="flex items-baseline gap-1 min-w-0 whitespace-nowrap mt-1 text-base sm:text-lg font-bold tracking-tight tabular-nums text-[#172B4D]">
                    <span class="text-xs font-semibold text-[#64748B] shrink-0">Rp</span>
                    <span>{{ number_format((float) $totalRevenue, 0, ',', '.') }}</span>
                </div>
                <span class="text-[10px] text-[#64748B] block mt-0.5">Semua pesanan aktif pada hasil ini</span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Total Laba Kotor Final</span>
                <div class="mt-1">
                    @if($readyOrdersCount > 0)
                        <div class="flex items-baseline gap-1 min-w-0 whitespace-nowrap text-base sm:text-lg font-bold tracking-tight tabular-nums {{ bccomp((string) $totalGrossProfit, '0.00', 2) >= 0 ? 'text-[#8E1B5C]' : 'text-rose-600' }}">
                            <span class="text-xs font-semibold shrink-0">{{ bccomp((string) $totalGrossProfit, '0.00', 2) < 0 ? '-Rp' : 'Rp' }}</span>
                            <span>{{ number_format(abs((float) $totalGrossProfit), 0, ',', '.') }}</span>
                        </div>
                    @else
                        <span class="text-[#64748B] font-normal text-base">—</span>
                    @endif
                </div>
                <span class="text-[10px] text-[#64748B] block mt-0.5">
                    @if($readyOrdersCount > 0)
                        Dari {{ $readyOrdersCount }} pesanan profit final
                    @else
                        Belum ada pesanan dengan profit final pada hasil ini.
                    @endif
                </span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Total Laba Bersih Final</span>
                <div class="mt-1">
                    @if($readyOrdersCount > 0)
                        <div class="flex items-baseline gap-1 min-w-0 whitespace-nowrap text-base sm:text-lg font-bold tracking-tight tabular-nums {{ bccomp((string) $totalNetProfit, '0.00', 2) >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            <span class="text-xs font-semibold shrink-0">{{ bccomp((string) $totalNetProfit, '0.00', 2) < 0 ? '-Rp' : 'Rp' }}</span>
                            <span>{{ number_format(abs((float) $totalNetProfit), 0, ',', '.') }}</span>
                        </div>
                    @else
                        <span class="text-[#64748B] font-normal text-base">—</span>
                    @endif
                </div>
                <span class="text-[10px] text-[#64748B] block mt-0.5">
                    @if($readyOrdersCount > 0)
                        Setelah beban operasional terkait
                    @else
                        Belum ada pesanan dengan profit final pada hasil ini.
                    @endif
                </span>
            </div>
        </div>

        {{-- ── 3. Filters & Search Bar ────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
            <form method="GET" action="{{ route('owner.profitability.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nomor sales order atau nama pelanggan..."
                           class="w-full pl-10 pr-4 h-10 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] placeholder-[#64748B] focus:outline-hidden focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                </div>

                <div class="w-full sm:w-48 shrink-0">
                    <select name="status"
                            onchange="this.form.submit()"
                            class="w-full h-10 px-3 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:outline-hidden focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                        <option value="">Semua Status Profit</option>
                        <option value="READY" {{ $statusFilter === 'READY' ? 'selected' : '' }}>Profit Final</option>
                        <option value="NOT_READY" {{ $statusFilter === 'NOT_READY' ? 'selected' : '' }}>Menunggu HPP Final</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center justify-center h-10 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition cursor-pointer shadow-xs">
                        Filter
                    </button>

                    @if($search || $statusFilter || $startDate || $endDate)
                        <a href="{{ route('owner.profitability.index') }}"
                           class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── 4. Profitability Table ─────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left text-[#172B4D]">
                    <thead class="bg-[#FFFCFB] text-[11px] text-[#64748B] uppercase font-bold border-b border-[#FFD8D8]">
                        <tr>
                            <th class="px-3.5 py-3.5 whitespace-nowrap min-w-[135px]">No. Pesanan</th>
                            <th class="px-3.5 py-3.5 min-w-[130px] max-w-[180px]">Pelanggan</th>
                            <th class="px-3.5 py-3.5 whitespace-nowrap">Tanggal</th>
                            <th class="px-3.5 py-3.5 text-right whitespace-nowrap min-w-[110px]">Revenue</th>
                            <th class="px-3.5 py-3.5 text-right whitespace-nowrap min-w-[110px]">HPP</th>
                            <th class="px-3.5 py-3.5 text-right whitespace-nowrap min-w-[115px]">Gross Profit</th>
                            <th class="px-3.5 py-3.5 text-right whitespace-nowrap">Gross Margin</th>
                            <th class="px-3.5 py-3.5 text-right whitespace-nowrap min-w-[115px]">Net Profit</th>
                            <th class="px-3.5 py-3.5 text-right whitespace-nowrap">Net Margin</th>
                            <th class="px-3.5 py-3.5 text-center whitespace-nowrap">Status</th>
                            <th class="px-3.5 py-3.5 text-center whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($displayOrders as $o)
                            @php
                                $prof = $profitabilityResults[$o->id] ?? null;
                                $isReady = $prof && $prof['is_ready'];
                                $revenue = $prof['revenue_amount'] ?? '0.00';
                                $hpp = $prof['hpp_amount'] ?? null;
                                $gp = $prof['gross_profit'] ?? null;
                                $gm = $prof['gross_margin'] ?? null;
                                $np = $prof['net_profit'] ?? null;
                                $nm = $prof['net_margin'] ?? null;
                            @endphp
                            <tr class="hover:bg-[#FFFCFB]/50 transition">
                                <td class="px-3.5 py-2.5 sm:py-3 font-mono font-bold text-[#8E1B5C] whitespace-nowrap">
                                    <a href="{{ route('owner.profitability.show', $o) }}" class="hover:underline">
                                        {{ $o->order_no }}
                                    </a>
                                </td>
                                <td class="px-3.5 py-2.5 sm:py-3 min-w-[130px] max-w-[180px]">
                                    <span class="font-bold text-[#172B4D] block line-clamp-2 leading-tight">{{ $o->customer->customer_name ?? '-' }}</span>
                                    <span class="text-[10px] text-[#64748B] block leading-tight mt-0.5 whitespace-nowrap">{{ $o->customer->phone ?? '-' }}</span>
                                </td>
                                <td class="px-3.5 py-2.5 sm:py-3 text-[#64748B] whitespace-nowrap">
                                    {{ $o->order_date ? \Carbon\Carbon::parse($o->order_date)->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-3.5 py-2.5 sm:py-3 text-right font-mono font-semibold text-[13px] text-[#172B4D] tabular-nums whitespace-nowrap">
                                    Rp {{ number_format((float) $revenue, 0, ',', '.') }}
                                </td>
                                <td class="px-3.5 py-2.5 sm:py-3 text-right font-mono text-[13px] text-[#64748B] tabular-nums whitespace-nowrap">
                                    @if($isReady && $hpp !== null)
                                        Rp {{ number_format((float) $hpp, 0, ',', '.') }}
                                    @else
                                        <span class="text-[#64748B] font-normal">—</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5 sm:py-3 text-right font-mono font-semibold text-[13px] tabular-nums whitespace-nowrap">
                                    @if($isReady && $gp !== null)
                                        <span class="{{ bccomp((string) $gp, '0.00', 2) >= 0 ? 'text-[#8E1B5C]' : 'text-rose-600' }}">
                                            {{ bccomp((string) $gp, '0.00', 2) < 0 ? '-Rp ' . number_format(abs((float) $gp), 0, ',', '.') : 'Rp ' . number_format((float) $gp, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-[#64748B] font-normal">—</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5 sm:py-3 text-right font-mono text-[11px] tabular-nums whitespace-nowrap">
                                    @if($isReady && $gm !== null)
                                        <span class="{{ bccomp((string) $gm, '0.0000', 4) >= 0 ? 'text-[#172B4D] font-semibold' : 'text-rose-600 font-bold' }}">
                                            {{ number_format((float) $gm, 2, ',', '.') }}%
                                        </span>
                                    @else
                                        <span class="text-[#64748B] font-normal">—</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5 sm:py-3 text-right font-mono font-semibold text-[13px] tabular-nums whitespace-nowrap">
                                    @if($isReady && $np !== null)
                                        <span class="{{ bccomp((string) $np, '0.00', 2) >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                            {{ bccomp((string) $np, '0.00', 2) < 0 ? '-Rp ' . number_format(abs((float) $np), 0, ',', '.') : 'Rp ' . number_format((float) $np, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-[#64748B] font-normal">—</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5 sm:py-3 text-right font-mono text-[11px] tabular-nums whitespace-nowrap">
                                    @if($isReady && $nm !== null)
                                        <span class="{{ bccomp((string) $nm, '0.0000', 4) >= 0 ? 'text-emerald-700 font-bold' : 'text-rose-600 font-bold' }}">
                                            {{ number_format((float) $nm, 2, ',', '.') }}%
                                        </span>
                                    @else
                                        <span class="text-[#64748B] font-normal">—</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5 sm:py-3 text-center whitespace-nowrap">
                                    @if($isReady)
                                        <span class="badge badge-success text-[9px] px-2 py-0.5 whitespace-nowrap">Profit Final</span>
                                    @elseif(($prof['status'] ?? '') === 'ORDER_CANCELLED')
                                        <span class="badge badge-secondary text-[9px] px-2 py-0.5 whitespace-nowrap">Dibatalkan</span>
                                    @elseif(($prof['status'] ?? '') === 'DATA_INTEGRITY_ERROR')
                                        <span class="badge badge-danger text-[9px] px-2 py-0.5 whitespace-nowrap">Bermasalah</span>
                                    @else
                                        <span class="badge badge-warning text-[9px] px-2 py-0.5 whitespace-nowrap">Menunggu HPP</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5 sm:py-3 text-center whitespace-nowrap">
                                    <a href="{{ route('owner.profitability.show', $o) }}"
                                       title="Lihat Rincian Profitabilitas"
                                       class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-[#FFD8D8] bg-white text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="p-8 text-center text-xs text-[#64748B]">
                                    Tidak ada data pesanan yang cocok dengan kriteria pencarian.
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

    </div>
</x-app-layout>
