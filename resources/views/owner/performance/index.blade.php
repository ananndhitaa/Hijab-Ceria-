<x-app-layout pageTitle="Ringkasan Keuangan" breadcrumb="Ringkasan Keuangan">
    <div class="max-w-7xl mx-auto space-y-4 sm:space-y-6">

        @php
            $isReady = $financials['is_ready'] ?? false;
            $revenue = $financials['revenue'] ?? $financials['total_penjualan'] ?? '0.00';
            $payReceived = $financials['payment_received'] ?? $financials['pendapatan_diterima'] ?? '0.00';
            $piutangCustomer = $financials['piutang_customer'] ?? '0.00';
            $supplierPayments = $financials['supplier_payments'] ?? '0.00';
            $businessExpenses = $financials['business_expenses'] ?? '0.00';
            $totalPengeluaran = $financials['total_pengeluaran'] ?? '0.00';
            $kasTersedia = $financials['kas_tersedia'] ?? null;
            $hpp = $financials['hpp'] ?? null;
            $hppFinalAvail = $financials['hpp_final_available'] ?? '0.00';
            $gp = $financials['gross_profit'] ?? null;
            $gm = $financials['gross_margin'] ?? null;
            $np = $financials['net_profit'] ?? null;
            $nm = $financials['net_margin'] ?? null;
            $roi = $financials['roi'] ?? null;
            $roiLabel = $financials['roi_label'] ?? 'Belum dapat dihitung';
            $roiHelper = $financials['roi_helper'] ?? null;
            $mer = $financials['mer'] ?? null;
            $ebitda = $financials['ebitda'] ?? null;
            $expenses = $financials['expenses'] ?? [];
            $unreadyCount = $financials['unready_orders_count'] ?? 0;
            $readyCount = $financials['ready_orders_count'] ?? 0;
            $procurementSummary = $financials['procurement_summary'] ?? [];
            $komitmenPo = $financials['komitmen_po'] ?? $procurementSummary['po_outstanding'] ?? '0.00';
            $poSufficiencyStatus = $procurementSummary['sufficiency_status'] ?? 'BELUM_DAPAT_DIHITUNG';

            $isGpNegative = $gp !== null && bccomp((string) $gp, '0.00', 2) < 0;
            $isNpNegative = $np !== null && bccomp((string) $np, '0.00', 2) < 0;
            $isRoiNegative = $roi !== null && bccomp((string) $roi, '0.0000', 4) < 0;
        @endphp

        {{-- Feature Header --}}
        <x-feature-header
            title="Ringkasan Keuangan"
            subtitle="Kinerja Keuangan, profitabilitas, komitmen supplier, dan ROI usaha."
            icon="chart"
            badge="Ringkasan Keuangan • Read-Only"
        >
            <span class="sr-only">Kinerja Keuangan</span>
            <x-slot:actions>
                <a href="{{ route('owner.dashboard') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Compact Period Filter ────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
            <form method="GET" action="{{ route('owner.performance.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-bold text-[#64748B] uppercase tracking-wider mr-2">Periode:</span>

                    <a href="{{ route('owner.performance.index', ['preset' => 'today']) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'today' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('owner.performance.index', ['preset' => 'last_7_days']) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'last_7_days' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        7 Hari Terakhir
                    </a>
                    <a href="{{ route('owner.performance.index', ['preset' => 'this_month']) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'this_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Bulan Ini
                    </a>
                    <a href="{{ route('owner.performance.index', ['preset' => 'last_month']) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'last_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Bulan Lalu
                    </a>
                    <a href="{{ route('owner.performance.index', ['preset' => 'all']) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'all' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Semua
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="preset" value="custom">
                    <input type="date"
                           name="start_date"
                           value="{{ $startDate }}"
                           class="h-9 px-3 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                    <span class="text-xs text-[#64748B]">s/d</span>
                    <input type="date"
                           name="end_date"
                           value="{{ $endDate }}"
                           class="h-9 px-3 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                    <button type="submit"
                            class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition cursor-pointer shadow-xs">
                        Terapkan
                    </button>
                </div>
            </form>
        </div>

        {{-- ── 3. Financial Readiness Notice ───────────────────────────────── --}}
        @if(! $isReady && $unreadyCount > 0)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-card text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-sm font-bold">HPP Belum Final</h4>
                        <p class="text-xs leading-relaxed text-amber-800">
                            Terdapat <strong>{{ $unreadyCount }} pesanan</strong> dalam periode ini yang HPP produksinya belum final. Laba kotor dan laba bersih menunggu kelengkapan biaya produksi, sedangkan arus kas dan ROI tetap dapat dimonitor secara riil.
                        </p>
                    </div>
                </div>

                <a href="{{ route('owner.profitability.index', ['status' => 'NOT_READY']) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-xl bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 transition cursor-pointer shrink-0 shadow-2xs">
                    <span>Lihat Pesanan Belum Final</span>
                </a>
            </div>
        @endif

        {{-- ── 4. Grouping 1: RINGKASAN KEUANGAN ─────────────────────────── --}}
        <div class="space-y-2.5">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                    Ringkasan Keuangan
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4 items-stretch">
                {{-- 1. Total Penjualan --}}
                <div class="h-full min-w-0 bg-white border border-[#FFD8D8] rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-h-[148px]">
                    <div class="min-h-[38px] sm:min-h-[40px] flex items-start">
                        <span class="text-[11px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-wider leading-tight block">Total Penjualan</span>
                    </div>
                    <div class="min-h-[32px] sm:min-h-[34px] flex items-center min-w-0 max-w-full">
                        <div class="inline-flex items-baseline gap-1 min-w-0 whitespace-nowrap">
                            <span class="text-[11px] sm:text-[12px] font-semibold text-[#64748B]/75 shrink-0">Rp</span>
                            <span class="text-[19px] sm:text-[20px] xl:text-[21px] font-bold tracking-tight tabular-nums text-[#172B4D] leading-none">
                                {{ number_format((float) $revenue, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-auto pt-2">
                        <p class="text-[11px] sm:text-[12px] text-[#64748B] leading-snug">Order valid</p>
                    </div>
                </div>

                {{-- 2. Pendapatan Diterima --}}
                <div class="h-full min-w-0 bg-white border border-[#FFD8D8] rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-h-[148px]">
                    <div class="min-h-[38px] sm:min-h-[40px] flex items-start">
                        <span class="text-[11px] sm:text-[12px] font-semibold text-emerald-800 uppercase tracking-wider leading-tight block">Pendapatan Diterima</span>
                    </div>
                    <div class="min-h-[32px] sm:min-h-[34px] flex items-center min-w-0 max-w-full">
                        <div class="inline-flex items-baseline gap-1 min-w-0 whitespace-nowrap">
                            <span class="text-[11px] sm:text-[12px] font-semibold text-emerald-700/80 shrink-0">Rp</span>
                            <span class="text-[19px] sm:text-[20px] xl:text-[21px] font-bold tracking-tight tabular-nums text-emerald-700 leading-none">
                                {{ number_format((float) $payReceived, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-auto pt-2">
                        <p class="text-[11px] sm:text-[12px] text-[#64748B] leading-snug">Payment confirmed</p>
                    </div>
                </div>

                {{-- 3. Piutang / Belum Dibayar Customer --}}
                <div class="h-full min-w-0 bg-white border border-[#FFD8D8] rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-h-[148px]">
                    <div class="min-h-[38px] sm:min-h-[40px] flex items-start">
                        <span class="text-[11px] sm:text-[12px] font-semibold text-amber-800 uppercase tracking-wider leading-tight block">Piutang Pelanggan</span>
                    </div>
                    <div class="min-h-[32px] sm:min-h-[34px] flex items-center min-w-0 max-w-full">
                        <div class="inline-flex items-baseline gap-1 min-w-0 whitespace-nowrap">
                            <span class="text-[11px] sm:text-[12px] font-semibold text-amber-700/80 shrink-0">Rp</span>
                            <span class="text-[19px] sm:text-[20px] xl:text-[21px] font-bold tracking-tight tabular-nums text-amber-700 leading-none">
                                {{ number_format((float) $piutangCustomer, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-auto pt-2">
                        <p class="text-[11px] sm:text-[12px] text-[#64748B] leading-snug">Belum terbayar</p>
                    </div>
                </div>

                {{-- 4. Total Pengeluaran --}}
                <div class="h-full min-w-0 bg-white border border-[#FFD8D8] rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-h-[148px]">
                    <div class="min-h-[38px] sm:min-h-[40px] flex items-start">
                        <span class="text-[11px] sm:text-[12px] font-semibold text-rose-800 uppercase tracking-wider leading-tight block">Total Pengeluaran</span>
                    </div>
                    <div class="min-h-[32px] sm:min-h-[34px] flex items-center min-w-0 max-w-full">
                        <div class="inline-flex items-baseline gap-1 min-w-0 whitespace-nowrap">
                            <span class="text-[11px] sm:text-[12px] font-semibold text-rose-700/80 shrink-0">Rp</span>
                            <span class="text-[19px] sm:text-[20px] xl:text-[21px] font-bold tracking-tight tabular-nums text-rose-700 leading-none">
                                {{ number_format((float) $totalPengeluaran, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-auto pt-2">
                        <p class="text-[11px] sm:text-[12px] text-[#64748B] leading-snug">Supplier + beban usaha</p>
                    </div>
                </div>

                {{-- 5. Kas Saat Ini --}}
                <div class="h-full min-w-0 bg-white border border-[#FFD8D8] rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-xs bg-gradient-to-br from-white to-[#FFFCFB] flex flex-col min-h-[148px]">
                    <div class="min-h-[38px] sm:min-h-[40px] flex items-start">
                        <span class="text-[11px] sm:text-[12px] font-semibold text-[#8E1B5C] uppercase tracking-wider leading-tight block">Kas Saat Ini</span>
                    </div>
                    <div class="min-h-[32px] sm:min-h-[34px] flex items-center min-w-0 max-w-full">
                        @if($kasTersedia !== null)
                            <div class="inline-flex items-baseline gap-1 min-w-0 whitespace-nowrap">
                                <span class="text-[11px] sm:text-[12px] font-semibold text-[#8E1B5C]/75 shrink-0">Rp</span>
                                <span class="text-[19px] sm:text-[20px] xl:text-[21px] font-bold tracking-tight tabular-nums text-[#8E1B5C] leading-none">
                                    {{ number_format((float) $kasTersedia, 0, ',', '.') }}
                                </span>
                            </div>
                        @else
                            <span class="text-[19px] sm:text-[20px] font-normal text-[#64748B] leading-none block">—</span>
                        @endif
                    </div>
                    <div class="mt-auto pt-2">
                        <p class="text-[11px] sm:text-[12px] text-[#64748B] leading-snug">Saldo operasional</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 5. Grouping 2: PROFITABILITAS & ROI ───────────────────────── --}}
        <div class="space-y-2.5">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                    Profitabilitas & ROI
                </h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 items-stretch">
                {{-- 6. HPP / Modal Produksi --}}
                <div class="h-full min-w-0 bg-white border border-[#FFD8D8] rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-h-[148px]">
                    <div class="min-h-[38px] sm:min-h-[40px] flex items-start">
                        <span class="text-[11px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-wider leading-tight block">HPP / Modal Produksi</span>
                    </div>
                    <div class="min-h-[32px] sm:min-h-[34px] flex items-center min-w-0 max-w-full">
                        @if($isReady && $hpp !== null)
                            <div class="inline-flex items-baseline gap-1 min-w-0 whitespace-nowrap">
                                <span class="text-[11px] sm:text-[12px] font-semibold text-[#64748B]/75 shrink-0">Rp</span>
                                <span class="text-[19px] sm:text-[20px] xl:text-[21px] font-bold tracking-tight tabular-nums text-[#172B4D] leading-none">
                                    {{ number_format((float) $hpp, 0, ',', '.') }}
                                </span>
                            </div>
                        @else
                            <span class="text-[19px] sm:text-[20px] font-normal text-[#64748B] leading-none block">—</span>
                        @endif
                    </div>
                    <div class="mt-auto pt-2">
                        <p class="text-[11px] sm:text-[12px] text-[#64748B] leading-snug">
                            @if($isReady && $hpp !== null)
                                Biaya produksi final
                            @elseif($unreadyCount > 0)
                                Menunggu {{ $unreadyCount }} pesanan
                            @else
                                Belum ada pesanan
                            @endif
                        </p>
                    </div>
                </div>

                {{-- 7. Laba Kotor --}}
                <div class="h-full min-w-0 bg-white border border-[#FFD8D8] rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-h-[148px]">
                    <div class="min-h-[38px] sm:min-h-[40px] flex items-start">
                        <span class="text-[11px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-wider leading-tight block">Laba Kotor</span>
                    </div>
                    <div class="min-h-[32px] sm:min-h-[34px] flex items-center min-w-0 max-w-full">
                        @if($isReady && $gp !== null)
                            <div class="inline-flex items-baseline gap-1 min-w-0 whitespace-nowrap">
                                <span class="text-[11px] sm:text-[12px] font-semibold {{ $isGpNegative ? 'text-rose-600/80' : 'text-[#8E1B5C]/75' }} shrink-0">
                                    {{ $isGpNegative ? '-Rp' : 'Rp' }}
                                </span>
                                <span class="text-[19px] sm:text-[20px] xl:text-[21px] font-bold tracking-tight tabular-nums {{ $isGpNegative ? 'text-rose-600' : 'text-[#8E1B5C]' }} leading-none">
                                    {{ number_format(abs((float) $gp), 0, ',', '.') }}
                                </span>
                            </div>
                        @else
                            <span class="text-[19px] sm:text-[20px] font-normal text-[#64748B] leading-none block">—</span>
                        @endif
                    </div>
                    <div class="mt-auto pt-2">
                        <p class="text-[11px] sm:text-[12px] text-[#64748B] leading-snug">
                            @if($isReady && $gp !== null)
                                Margin: {{ $gm !== null ? number_format((float) $gm, 1, ',', '.') . '%' : '—' }}
                            @elseif($unreadyCount > 0)
                                Menunggu {{ $unreadyCount }} pesanan
                            @else
                                Belum ada pesanan
                            @endif
                        </p>
                    </div>
                </div>

                {{-- 8. Laba Bersih --}}
                <div class="h-full min-w-0 bg-white border border-[#FFD8D8] rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-h-[148px]">
                    <div class="min-h-[38px] sm:min-h-[40px] flex items-start">
                        <span class="text-[11px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-wider leading-tight block">Laba Bersih</span>
                    </div>
                    <div class="min-h-[32px] sm:min-h-[34px] flex items-center min-w-0 max-w-full">
                        @if($isReady && $np !== null)
                            <div class="inline-flex items-baseline gap-1 min-w-0 whitespace-nowrap">
                                <span class="text-[11px] sm:text-[12px] font-semibold {{ $isNpNegative ? 'text-rose-600/80' : 'text-emerald-700/80' }} shrink-0">
                                    {{ $isNpNegative ? '-Rp' : 'Rp' }}
                                </span>
                                <span class="text-[19px] sm:text-[20px] xl:text-[21px] font-bold tracking-tight tabular-nums {{ $isNpNegative ? 'text-rose-600' : 'text-emerald-700' }} leading-none">
                                    {{ number_format(abs((float) $np), 0, ',', '.') }}
                                </span>
                            </div>
                        @else
                            <span class="text-[19px] sm:text-[20px] font-normal text-[#64748B] leading-none block">—</span>
                        @endif
                    </div>
                    <div class="mt-auto pt-2">
                        <p class="text-[11px] sm:text-[12px] text-[#64748B] leading-snug">
                            @if($isReady && $np !== null)
                                Margin: {{ $nm !== null ? number_format((float) $nm, 1, ',', '.') . '%' : '—' }}
                            @elseif($unreadyCount > 0)
                                Menunggu {{ $unreadyCount }} pesanan
                            @else
                                Belum ada pesanan
                            @endif
                        </p>
                    </div>
                </div>

                {{-- 9. ROI (Authoritative Standardized KPI: Total Pendapatan / Total Pengeluaran) --}}
                <div class="h-full min-w-0 bg-white border border-[#FFD8D8] rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-xs bg-gradient-to-br from-white to-[#FFFCFB] flex flex-col min-h-[148px]">
                    <div class="min-h-[38px] sm:min-h-[40px] flex items-start justify-between gap-1.5 w-full">
                        <span class="text-[11px] sm:text-[12px] font-semibold text-[#8E1B5C] uppercase tracking-wider leading-tight block">ROI</span>
                        <span class="text-[10px] font-semibold text-[#8E1B5C] bg-[#FFD8D8] px-1.5 py-0.5 rounded">Utama</span>
                    </div>
                    <div class="min-h-[32px] sm:min-h-[34px] flex items-center min-w-0 max-w-full">
                        @if($roi !== null)
                            <span class="text-[19px] sm:text-[20px] xl:text-[21px] font-bold tracking-tight tabular-nums {{ $isRoiNegative ? 'text-rose-600' : 'text-emerald-700' }} leading-none block whitespace-nowrap">
                                {{ number_format((float) $roi, 2, ',', '.') }}%
                            </span>
                        @else
                            <span class="text-[19px] sm:text-[20px] font-normal text-[#64748B] leading-none block">N/A</span>
                        @endif
                    </div>
                    <div class="mt-auto pt-2">
                        <p class="text-[11px] sm:text-[12px] text-[#64748B] leading-snug" title="Formula: (Total Pendapatan / Total Pengeluaran) × 100%">
                            Pendapatan / Pengeluaran
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 6. Grouping 3: KOMITMEN PO & KEWAJIBAN SUPPLIER ─────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#FFD8D8] pb-2.5">
                <div>
                    <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                        Komitmen Purchase Order & Kewajiban Supplier
                    </h3>
                    <p class="text-[11px] text-[#64748B]">
                        Monitoring kewajiban pembayaran bahan baku dan evaluasi kecukupan kas operasional.
                    </p>
                </div>
                <div>
                    @if($poSufficiencyStatus === 'CUKUP')
                        <span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs px-2.5 py-1 font-bold">
                            &check; Kas Cukup untuk Kewajiban PO
                        </span>
                    @elseif($poSufficiencyStatus === 'TIDAK_CUKUP')
                        <span class="badge bg-rose-100 text-rose-800 border border-rose-300 text-xs px-2.5 py-1 font-bold">
                            &excl; Kas Tidak Cukup (Perlu Alokasi)
                        </span>
                    @else
                        <span class="badge bg-amber-100 text-amber-800 border border-amber-300 text-xs px-2.5 py-1 font-bold">
                            Status Kecukupan: Belum Ditentukan
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase block">Total Nilai PO</span>
                    <div class="flex items-baseline gap-1 min-w-0 whitespace-nowrap mt-1 text-sm sm:text-base font-bold tracking-tight tabular-nums text-[#172B4D]">
                        <span class="text-xs font-semibold text-[#64748B] shrink-0">Rp</span>
                        <span>{{ number_format((float) ($procurementSummary['total_po'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase block">PO Sudah Dibayar</span>
                    <div class="flex items-baseline gap-1 min-w-0 whitespace-nowrap mt-1 text-sm sm:text-base font-bold tracking-tight tabular-nums text-emerald-700">
                        <span class="text-xs font-semibold shrink-0">Rp</span>
                        <span>{{ number_format((float) ($procurementSummary['po_paid'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <span class="text-[10px] font-bold text-[#8E1B5C] uppercase block">Komitmen PO (Sisa Kewajiban)</span>
                    <div class="flex items-baseline gap-1 min-w-0 whitespace-nowrap mt-1 text-sm sm:text-base font-bold tracking-tight tabular-nums text-[#8E1B5C]">
                        <span class="text-xs font-semibold shrink-0">Rp</span>
                        <span>{{ number_format((float) $komitmenPo, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase block">Proyeksi Sisa Kas</span>
                    <div class="flex items-baseline gap-1 min-w-0 whitespace-nowrap mt-1 text-sm sm:text-base font-bold tracking-tight tabular-nums text-[#172B4D]">
                        @if($procurementSummary['projected_cash'] !== null)
                            <span class="flex items-baseline gap-1 min-w-0 whitespace-nowrap {{ bccomp((string) $procurementSummary['projected_cash'], '0.00', 2) < 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                                <span class="text-xs font-semibold shrink-0">Rp</span>
                                <span>{{ number_format((float) $procurementSummary['projected_cash'], 0, ',', '.') }}</span>
                            </span>
                        @else
                            <span class="text-[#64748B]">—</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 7. Grouping 4: STRUKTUR LABA RUGI & RINCIAN PENGELUARAN ─────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Ringkasan Laba Rugi --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-3.5">
                <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2.5">
                    Ringkasan Laba Rugi
                </h3>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                        <span class="font-bold text-[#172B4D]">Total Penjualan</span>
                        <span class="font-mono font-bold text-sm text-[#172B4D] whitespace-nowrap">Rp {{ number_format((float) $revenue, 0, ',', '.') }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                        <span class="font-bold text-[#172B4D]">HPP Produksi</span>
                        <span class="font-mono font-bold text-sm text-[#64748B] whitespace-nowrap">
                            @if($isReady && $hpp !== null)
                                Rp {{ number_format((float) $hpp, 0, ',', '.') }}
                            @else
                                <span class="text-[#64748B] font-normal">—</span>
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-[#FFD8D8]/50 border border-[#FFD8D8]">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-[#8E1B5C] text-sm">Laba Kotor</span>
                            @if($isReady && $gm !== null)
                                <span class="badge bg-[#8E1B5C] text-white text-[10px] px-2 py-0.5">{{ number_format((float) $gm, 2, ',', '.') }}%</span>
                            @endif
                        </div>
                        <span class="font-mono font-extrabold text-sm whitespace-nowrap {{ $isGpNegative ? 'text-rose-600' : 'text-[#8E1B5C]' }}">
                            @if($isReady && $gp !== null)
                                {{ $isGpNegative ? '-Rp ' . number_format(abs((float) $gp), 0, ',', '.') : 'Rp ' . number_format((float) $gp, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                        <span class="font-bold text-[#172B4D]">Beban Usaha</span>
                        <span class="font-mono font-bold text-sm text-[#64748B] whitespace-nowrap">Rp {{ number_format((float) ($expenses['total'] ?? 0), 0, ',', '.') }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 rounded-xl bg-emerald-50 border border-emerald-200">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-emerald-900 text-sm">Laba Bersih</span>
                            @if($isReady && $nm !== null)
                                <span class="badge bg-emerald-700 text-white text-[10px] px-2 py-0.5">{{ number_format((float) $nm, 2, ',', '.') }}%</span>
                            @endif
                        </div>
                        <span class="font-mono font-extrabold text-base whitespace-nowrap {{ $isNpNegative ? 'text-rose-600' : 'text-emerald-800' }}">
                            @if($isReady && $np !== null)
                                {{ $isNpNegative ? '-Rp ' . number_format(abs((float) $np), 0, ',', '.') : 'Rp ' . number_format((float) $np, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Expenses Breakdown Table --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-3.5">
                <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2.5">
                    Rincian Pengeluaran
                </h3>

                <div class="overflow-x-auto rounded-xl border border-[#FFD8D8]">
                    <table class="w-full text-xs text-left text-[#172B4D]">
                        <thead class="bg-[#FFFCFB] text-[11.5px] sm:text-[12px] text-[#64748B] uppercase font-semibold border-b border-[#FFD8D8]">
                            <tr>
                                <th class="px-3.5 py-2.5 sm:py-3 align-middle">Kategori</th>
                                <th class="px-3.5 py-2.5 sm:py-3 text-right align-middle">Jumlah</th>
                                <th class="px-3.5 py-2.5 sm:py-3 text-center align-middle">Tipe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            <tr>
                                <td class="px-3.5 py-2.5 font-bold">Pembayaran Supplier</td>
                                <td class="px-3.5 py-2.5 text-right font-mono font-bold text-rose-700 whitespace-nowrap">Rp {{ number_format((float) $supplierPayments, 0, ',', '.') }}</td>
                                <td class="px-3.5 py-2.5 text-center"><span class="badge badge-purple text-[9px] px-2 py-0.5">Arus Kas Keluar</span></td>
                            </tr>
                            <tr>
                                <td class="px-3.5 py-2.5 font-bold">Pemasaran</td>
                                <td class="px-3.5 py-2.5 text-right font-mono font-bold whitespace-nowrap">Rp {{ number_format((float) ($expenses['marketing'] ?? 0), 0, ',', '.') }}</td>
                                <td class="px-3.5 py-2.5 text-center"><span class="badge badge-primary text-[9px] px-2 py-0.5">Beban Usaha</span></td>
                            </tr>
                            <tr>
                                <td class="px-3.5 py-2.5 font-bold">Operasional Usaha</td>
                                <td class="px-3.5 py-2.5 text-right font-mono font-bold whitespace-nowrap">Rp {{ number_format((float) ($expenses['operating'] ?? 0), 0, ',', '.') }}</td>
                                <td class="px-3.5 py-2.5 text-center"><span class="badge badge-primary text-[9px] px-2 py-0.5">Beban Usaha</span></td>
                            </tr>
                            <tr>
                                <td class="px-3.5 py-2.5 font-bold">Bunga</td>
                                <td class="px-3.5 py-2.5 text-right font-mono font-bold whitespace-nowrap">Rp {{ number_format((float) ($expenses['interest'] ?? 0), 0, ',', '.') }}</td>
                                <td class="px-3.5 py-2.5 text-center"><span class="badge badge-warning text-[9px] px-2 py-0.5">Beban Usaha</span></td>
                            </tr>
                            <tr>
                                <td class="px-3.5 py-2.5 font-bold">Pajak</td>
                                <td class="px-3.5 py-2.5 text-right font-mono font-bold whitespace-nowrap">Rp {{ number_format((float) ($expenses['tax'] ?? 0), 0, ',', '.') }}</td>
                                <td class="px-3.5 py-2.5 text-center"><span class="badge badge-cyan text-[9px] px-2 py-0.5">Beban Usaha</span></td>
                            </tr>
                            <tr>
                                <td class="px-3.5 py-2.5 font-bold">Penyusutan</td>
                                <td class="px-3.5 py-2.5 text-right font-mono font-bold whitespace-nowrap">Rp {{ number_format((float) ($expenses['depreciation'] ?? 0), 0, ',', '.') }}</td>
                                <td class="px-3.5 py-2.5 text-center"><span class="badge badge-secondary text-[9px] px-2 py-0.5">Non-Kas</span></td>
                            </tr>
                            <tr>
                                <td class="px-3.5 py-2.5 font-bold">Amortisasi</td>
                                <td class="px-3.5 py-2.5 text-right font-mono font-bold whitespace-nowrap">Rp {{ number_format((float) ($expenses['amortization'] ?? 0), 0, ',', '.') }}</td>
                                <td class="px-3.5 py-2.5 text-center"><span class="badge badge-secondary text-[9px] px-2 py-0.5">Non-Kas</span></td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-[#FFFCFB] font-bold border-t border-[#FFD8D8]">
                            <tr>
                                <td class="px-3.5 py-2.5 uppercase text-[11px] text-[#64748B]">Total Pengeluaran</td>
                                <td class="px-3.5 py-2.5 text-right text-sm text-[#8E1B5C] font-mono whitespace-nowrap">
                                    Rp {{ number_format((float) $totalPengeluaran, 0, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── 8. Grouping 5: DAFTAR PESANAN DALAM PERIODE ──────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden space-y-0">
            <x-nayara-section-header
                title="Daftar Pesanan Dalam Periode"
                subtitle="Seluruh Sales Order dengan tanggal pesanan pada periode terpilih (Read-Only)."
                meta="{{ count($financials['valid_orders'] ?? []) }} Pesanan"
            />

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left text-[#172B4D]">
                    <thead class="bg-[#FFD8D8] text-[11.5px] sm:text-[12px] text-[#64748B] uppercase font-semibold border-b border-[#FFD8D8] tracking-[0.02em]">
                        <tr>
                            <th class="px-3.5 py-2.5 sm:py-3 align-middle whitespace-nowrap min-w-[130px]">No. Pesanan</th>
                            <th class="px-3.5 py-2.5 sm:py-3 align-middle">Pelanggan</th>
                            <th class="px-3.5 py-2.5 sm:py-3 align-middle whitespace-nowrap">Tanggal</th>
                            <th class="px-3.5 py-2.5 sm:py-3 text-right align-middle whitespace-nowrap">Revenue</th>
                            <th class="px-3.5 py-2.5 sm:py-3 text-center align-middle whitespace-nowrap">Status HPP</th>
                            <th class="px-3.5 py-2.5 sm:py-3 text-right align-middle whitespace-nowrap">HPP</th>
                            <th class="px-3.5 py-2.5 sm:py-3 text-center align-middle whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($financials['valid_orders'] ?? [] as $vOrder)
                            <tr class="hover:bg-[#FFFCFB]/50 transition">
                                <td class="px-3.5 py-2.5 font-mono font-bold text-[#8E1B5C] whitespace-nowrap">
                                    <a href="{{ route('owner.profitability.show', $vOrder['order']) }}" class="hover:underline">
                                        {{ $vOrder['order_no'] }}
                                    </a>
                                </td>
                                <td class="px-3.5 py-2.5 font-bold text-[#172B4D]">{{ $vOrder['customer'] }}</td>
                                <td class="px-3.5 py-2.5 text-[#64748B] whitespace-nowrap">{{ $vOrder['order_date'] }}</td>
                                <td class="px-3.5 py-2.5 text-right font-mono font-bold text-[#172B4D] whitespace-nowrap">
                                    Rp {{ number_format((float) $vOrder['revenue'], 0, ',', '.') }}
                                </td>
                                <td class="px-3.5 py-2.5 text-center whitespace-nowrap">
                                    @if($vOrder['is_ready'])
                                        <span class="badge badge-success text-[9px] px-2 py-0.5 whitespace-nowrap">HPP Final</span>
                                    @else
                                        <span class="badge badge-warning text-[9px] px-2 py-0.5 whitespace-nowrap">Belum Final</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5 text-right font-mono text-[#64748B] whitespace-nowrap">
                                    @if($vOrder['is_ready'] && $vOrder['hpp_amount'] !== null)
                                        Rp {{ number_format((float) $vOrder['hpp_amount'], 0, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5 text-center">
                                    <a href="{{ route('owner.profitability.show', $vOrder['order']) }}"
                                       title="Lihat Profitabilitas"
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
                                <td colspan="7" class="p-6 text-center text-xs text-[#64748B]">
                                    Tidak ada pesanan pada periode terpilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
