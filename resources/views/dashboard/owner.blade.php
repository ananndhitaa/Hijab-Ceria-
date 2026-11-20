<x-app-layout pageTitle="Dashboard Owner" breadcrumb="Overview > Monitoring Bisnis">
    <div class="space-y-5 sm:space-y-6 pt-1 sm:pt-2">

        @php
            $isReady = $financials['is_ready'] ?? false;
            $revenue = $financials['revenue'] ?? '0.00';
            $hpp = $financials['hpp'] ?? null;
            $gp = $financials['gross_profit'] ?? null;
            $gm = $financials['gross_margin'] ?? null;
            $np = $financials['net_profit'] ?? null;
            $nm = $financials['net_margin'] ?? null;
            $roi = $financials['roi'] ?? null;
            $mer = $financials['mer'] ?? null;
            $ebitda = $financials['ebitda'] ?? null;
            $payReceived = $financials['payment_received'] ?? '0.00';

            $readyCount = $financials['finalized_orders_count'] ?? ($financials['ready_orders_count'] ?? 0);
            $unreadyCount = $financials['pending_orders_count'] ?? ($financials['unready_orders_count'] ?? 0);
            $totalOrderCount = $financials['total_orders'] ?? ($readyCount + $unreadyCount);

            $finalizedHpp = $financials['finalized_hpp'] ?? ($financials['hpp_final_available'] ?? '0.00');
            $finalizedRevenue = $financials['finalized_revenue'] ?? '0.00';
            $finalizedGp = $financials['finalized_gross_profit'] ?? ($isReady ? $gp : null);
            $finalizedGm = $financials['finalized_gross_margin'] ?? ($isReady ? $gm : null);

            $hasFinalizedHpp = $readyCount > 0 && bccomp((string) $finalizedHpp, '0.00', 2) > 0;
            $isFinalizedGpNegative = $finalizedGp !== null && bccomp((string) $finalizedGp, '0.00', 2) < 0;
            $isNpNegative = $np !== null && bccomp((string) $np, '0.00', 2) < 0;
            $isRoiNegative = $roi !== null && bccomp((string) $roi, '0.0000', 4) < 0;
            $isEbitdaNegative = $ebitda !== null && bccomp((string) $ebitda, '0.00', 2) < 0;

            $trendMonths = $trendMonths ?? 6;
            $trendScopeLabel = $analytics['trend_scope_label'] ?? "Tren {$trendMonths} bulan";
            $hasExpenseData = $analytics['expense_composition']['has_data'] ?? false;
            $hasTopProducts = ! empty($analytics['top_products']);
            $hasTopCustomers = ! empty($analytics['top_customers']);
        @endphp

        {{-- ── 1. NAYARA Executive Hero Banner ──────────────────────────────── --}}
        <x-dashboard-hero
            role-label="Owner & Monitoring"
            title="Dashboard Owner"
            description="Selamat Datang, Owner · Pantau penjualan, produksi, biaya, dan kinerja usaha NAYARA."
            action-label="Kinerja Usaha"
            :action-route="route('owner.performance.index')"
            action-icon="chart"
        />

        {{-- ── 2. Compact Period Filter ────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-3.5 sm:px-5 sm:py-3.5 shadow-card">
            <form method="GET" action="{{ route('owner.dashboard') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <input type="hidden" name="trend_months" value="{{ $trendMonths }}">

                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-bold text-[#64748B] uppercase tracking-wider mr-2">Periode:</span>

                    <a href="{{ route('owner.dashboard', array_merge(request()->query(), ['preset' => 'today', 'trend_months' => $trendMonths])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $preset === 'today' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('owner.dashboard', array_merge(request()->query(), ['preset' => 'last_7_days', 'trend_months' => $trendMonths])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $preset === 'last_7_days' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        7 Hari Terakhir
                    </a>
                    <a href="{{ route('owner.dashboard', array_merge(request()->query(), ['preset' => 'this_month', 'trend_months' => $trendMonths])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $preset === 'this_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Bulan Ini
                    </a>
                    <a href="{{ route('owner.dashboard', array_merge(request()->query(), ['preset' => 'last_month', 'trend_months' => $trendMonths])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $preset === 'last_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Bulan Lalu
                    </a>
                    <a href="{{ route('owner.dashboard', array_merge(request()->query(), ['preset' => 'all', 'trend_months' => $trendMonths])) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $preset === 'all' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Semua
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="preset" value="custom">
                    <input type="date"
                           name="start_date"
                           value="{{ $startDate }}"
                           class="h-8 px-2.5 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                    <span class="text-xs text-[#64748B]">s/d</span>
                    <input type="date"
                           name="end_date"
                           value="{{ $endDate }}"
                           class="h-8 px-2.5 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                    <button type="submit"
                            class="inline-flex items-center justify-center h-8 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition cursor-pointer shadow-xs">
                        Terapkan
                    </button>
                </div>
            </form>
        </div>

        {{-- ── 3. Financial Readiness Notice (If unready orders exist) ──────── --}}
        @if(! $isReady && $unreadyCount > 0)
            <div class="bg-amber-50/90 border border-amber-200 rounded-xl p-3.5 sm:px-4 sm:py-3 shadow-card text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="text-xs font-bold text-amber-900">Profitabilitas Periode Belum Final</h4>
                            <span class="text-[11px] text-amber-800">
                                &middot; {{ $unreadyCount }} pesanan masih menunggu finalisasi HPP.
                            </span>
                        </div>
                        <p class="text-[11px] text-amber-800/90 mt-0.5">
                            Nilai profitabilitas pesanan yang sudah final tetap ditampilkan.
                        </p>
                    </div>
                </div>

                <a href="{{ route('owner.profitability.index', ['status' => 'NOT_READY']) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 transition cursor-pointer shrink-0 shadow-2xs whitespace-nowrap">
                    <span>Lihat Pesanan Belum Final</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            </div>
        @endif

        {{-- ── 4. Ringkasan Keuangan ───────────────────────────────────────── --}}
        <div class="space-y-2.5">
            <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                Ringkasan Keuangan
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3.5 sm:gap-4 items-stretch">
                {{-- Kas Saat Ini --}}
                <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                    <div class="flex items-start justify-between gap-2 min-h-[38px]">
                        <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Kas Saat Ini</p>
                        <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                        <div class="flex items-baseline min-h-[28px]">
                            @if($kasTersedia !== null)
                                <p class="text-xl sm:text-2xl font-bold text-[#8E1B5C] font-mono tracking-tight leading-none whitespace-nowrap">
                                    Rp {{ number_format((float) $kasTersedia, 0, ',', '.') }}
                                </p>
                            @else
                                <span class="text-xl font-normal text-[#64748B] leading-none">—</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Saldo operasional</p>
                    </div>
                </div>

                {{-- Revenue --}}
                <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                    <div class="flex items-start justify-between gap-2 min-h-[38px]">
                        <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Pendapatan</p>
                        <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                        <div class="flex items-baseline min-h-[28px]">
                            <p class="text-xl sm:text-2xl font-bold text-emerald-700 font-mono tracking-tight leading-none whitespace-nowrap">
                                Rp {{ number_format((float) $revenue, 0, ',', '.') }}
                            </p>
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-1.5 truncate">
                            {{ number_format((float) ($financials['total_qty_sold'] ?? 0), 0, ',', '.') }} pcs &middot; {{ $totalOrderCount }} pesanan
                        </p>
                    </div>
                </div>

                {{-- HPP Tercatat --}}
                <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                    <div class="flex items-start justify-between gap-2 min-h-[38px]">
                        <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">HPP Tercatat</p>
                        <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                        <div class="flex items-baseline min-h-[28px]">
                            @if($readyCount > 0)
                                <p class="text-xl sm:text-2xl font-bold text-amber-700 font-mono tracking-tight leading-none whitespace-nowrap">
                                    Rp {{ number_format((float) $finalizedHpp, 0, ',', '.') }}
                                </p>
                            @else
                                <span class="text-xl font-normal text-[#64748B] leading-none">—</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-1.5 truncate" title="Total HPP dihitung dari akumulasi desimal presisi tinggi dan dibulatkan pada nilai akhir (round of sum).">
                            @if($readyCount > 0)
                                {{ $readyCount }} dari {{ $totalOrderCount }} pesanan final
                            @else
                                Belum ada HPP final
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Gross Profit --}}
                <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                    <div class="flex items-start justify-between gap-2 min-h-[38px]">
                        <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Laba Kotor</p>
                        <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                        <div class="flex items-baseline min-h-[28px]">
                            @if($readyCount > 0 && $finalizedGp !== null)
                                <p class="text-xl sm:text-2xl font-bold {{ $isFinalizedGpNegative ? 'text-rose-600' : 'text-[#8E1B5C]' }} font-mono tracking-tight leading-none whitespace-nowrap">
                                    {{ $isFinalizedGpNegative ? '-Rp' : 'Rp' }} {{ number_format(abs((float) $finalizedGp), 0, ',', '.') }}
                                </p>
                            @else
                                <span class="text-xl font-normal text-[#64748B] leading-none">—</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-1.5 truncate">
                            @if($readyCount > 0)
                                @if($unreadyCount > 0)
                                    {{ $readyCount }} pesanan final
                                @else
                                    Setelah biaya produksi (HPP)
                                @endif
                            @else
                                Belum ada pesanan final
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Net Profit --}}
                <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                    <div class="flex items-start justify-between gap-2 min-h-[38px]">
                        <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Laba Bersih</p>
                        <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                        <div class="flex items-baseline min-h-[28px]">
                            @if($isReady && $np !== null)
                                <p class="text-xl sm:text-2xl font-bold {{ $isNpNegative ? 'text-rose-600' : 'text-emerald-700' }} font-mono tracking-tight leading-none whitespace-nowrap">
                                    {{ $isNpNegative ? '-Rp' : 'Rp' }} {{ number_format(abs((float) $np), 0, ',', '.') }}
                                </p>
                            @else
                                <span class="text-xl font-normal text-[#64748B] leading-none">—</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-1.5 truncate">
                            @if($isReady && $np !== null)
                                Setelah seluruh beban usaha periode
                            @elseif($unreadyCount > 0)
                                Menunggu {{ $unreadyCount }} pesanan
                            @else
                                Belum ada pesanan
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 5. Indikator Kinerja (Gross Margin, Net Margin, ROI) ───────── --}}
        <div class="space-y-2.5">
            <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                Indikator Kinerja
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4 items-stretch">
                {{-- Gross Margin --}}
                <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                    <div class="flex items-start justify-between gap-2 min-h-[38px]">
                        <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Gross Margin</p>
                        <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                        <div class="flex items-baseline min-h-[28px]">
                            @if($readyCount > 0 && $finalizedGm !== null)
                                <p class="text-2xl font-bold font-mono tracking-tight leading-none {{ bccomp((string) $finalizedGm, '0.0000', 4) < 0 ? 'text-rose-600' : 'text-[#172B4D]' }} whitespace-nowrap">
                                    {{ number_format((float) $finalizedGm, 2, ',', '.') }}%
                                </p>
                            @else
                                <span class="text-xl font-normal text-[#64748B] leading-none">—</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-1.5 truncate">
                            @if($readyCount > 0)
                                @if($unreadyCount > 0)
                                    {{ $readyCount }} pesanan final
                                @else
                                    Laba Kotor / Pendapatan
                                @endif
                            @else
                                Belum ada pesanan final
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Net Margin --}}
                <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                    <div class="flex items-start justify-between gap-2 min-h-[38px]">
                        <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Net Margin</p>
                        <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                        <div class="flex items-baseline min-h-[28px]">
                            @if($isReady && $nm !== null)
                                <p class="text-2xl font-bold font-mono tracking-tight leading-none {{ $isNpNegative ? 'text-rose-600' : 'text-emerald-700' }} whitespace-nowrap">
                                    {{ number_format((float) $nm, 2, ',', '.') }}%
                                </p>
                            @else
                                <span class="text-xl font-normal text-[#64748B] leading-none">—</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-1.5 truncate">
                            @if($isReady && $nm !== null)
                                Laba Bersih / Pendapatan
                            @elseif($unreadyCount > 0)
                                Menunggu {{ $unreadyCount }} pesanan
                            @else
                                Belum ada pesanan
                            @endif
                        </p>
                    </div>
                </div>

                {{-- ROI (Authoritative Standardized KPI: Total Pendapatan / Total Pengeluaran) --}}
                <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                    <div class="flex items-start justify-between gap-2 min-h-[38px]">
                        <div class="flex items-center gap-1.5">
                            <p class="text-[11px] font-bold text-[#8E1B5C] uppercase tracking-wider leading-tight" title="Formula: (Total Pendapatan / Total Pengeluaran) × 100%">ROI</p>
                            <span class="text-[10px] font-bold text-[#8E1B5C] bg-[#FFD8D8] px-1.5 py-0.5 rounded">Utama</span>
                        </div>
                        <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                        <div class="flex items-baseline min-h-[28px]">
                            @if($roi !== null)
                                <p class="text-2xl font-bold font-mono tracking-tight leading-none {{ $isRoiNegative ? 'text-rose-600' : 'text-emerald-700' }} whitespace-nowrap">
                                    {{ number_format((float) $roi, 2, ',', '.') }}%
                                </p>
                            @else
                                <span class="text-xs font-semibold text-amber-700 leading-tight block">N/A</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-1.5 truncate" title="Formula: (Total Pendapatan / Total Pengeluaran) × 100%">
                            Pendapatan / Pengeluaran
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 6. Penerimaan Kas (Arus Kas Masuk Terpisah) ──────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl sm:rounded-2xl p-4 sm:px-5 sm:py-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="space-y-0.5">
                <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">Penerimaan Kas</h3>
                <p class="text-[11px] sm:text-[12px] text-[#64748B]">Pembayaran pelanggan yang telah dikonfirmasi pada periode ini.</p>
            </div>
            <div class="sm:text-right shrink-0">
                <div class="flex items-baseline sm:justify-end gap-1 whitespace-nowrap min-w-0">
                    <span class="text-[11px] sm:text-[12px] font-semibold text-emerald-700/80 shrink-0">Rp</span>
                    <span class="text-[19px] sm:text-[20px] xl:text-[21px] font-bold text-emerald-800 leading-none tracking-tight tabular-nums">
                        {{ number_format((float) $payReceived, 0, ',', '.') }}
                    </span>
                </div>
                <span class="text-[10px] text-emerald-700 font-medium block mt-1">Pembayaran Terkonfirmasi</span>
            </div>
        </div>

        {{-- ── 7. Perlu Tindakan (Action Center) ─────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card space-y-3">
            <div class="flex items-center justify-between pb-2.5 border-b border-[#FFD8D8]">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#8E1B5C]"></span>
                    <h3 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Perlu Tindakan</h3>
                </div>
                @if($ownerActions->isNotEmpty())
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        {{ $ownerActions->count() }} item
                    </span>
                @endif
            </div>

            @if($ownerActions->isNotEmpty())
                <div class="divide-y divide-[#FFD8D8] border border-[#FFD8D8] rounded-xl overflow-hidden bg-[#FFFCFB]/70">
                    @foreach($ownerActions as $item)
                        <div class="px-4 py-3 sm:py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-white transition-colors duration-150 min-h-[52px]">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $item['badge_class'] }} shrink-0">
                                    {{ $item['status_label'] }}
                                </span>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-[#172B4D] font-mono leading-none">{{ $item['reference'] }}</div>
                                    <div class="text-[11px] text-[#64748B] mt-1">{{ $item['subtitle'] }}</div>
                                </div>
                            </div>
                            <div class="flex items-center self-end sm:self-auto shrink-0">
                                <a href="{{ $item['action_url'] }}"
                                   class="inline-flex items-center justify-center min-w-[76px] h-8 px-3.5 rounded-lg text-xs font-semibold {{ $item['button_class'] }} transition shadow-xs">
                                    {{ $item['action_label'] }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-4 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] text-xs text-[#64748B] text-center">
                    Tidak ada tindakan.
                </div>
            @endif
        </div>

        @if($hasAnalyticsEntitlement ?? false)
        {{-- ── 7. Analisis Penjualan (PREMIUM / ADD-ON) ───────────────────── --}}
        <div class="space-y-6">
            <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                Analisis Penjualan
            </h3>

            {{-- ROW 1: Tren Pendapatan (Line Chart) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#FFD8D8] pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-[#172B4D]">Tren Pendapatan</h3>
                            <span class="px-2 py-0.5 rounded-md bg-[#FFD8D8] text-[#8E1B5C] text-[10px] font-bold">
                                {{ $trendScopeLabel }}
                            </span>
                        </div>
                        <p class="text-xs text-[#64748B] mt-0.5">
                            Perkembangan pendapatan selama {{ $trendMonths }} bulan terakhir (berdasarkan tanggal pesanan non-cancelled).
                        </p>
                    </div>

                    {{-- 6 / 12 Trailing Months Toggle (Preserves period query parameters) --}}
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('owner.dashboard', array_merge(request()->query(), ['trend_months' => 6])) }}"
                           class="px-2.5 py-1 rounded-lg text-xs font-semibold transition {{ $trendMonths === 6 ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                            6 Bulan
                        </a>
                        <a href="{{ route('owner.dashboard', array_merge(request()->query(), ['trend_months' => 12])) }}"
                           class="px-2.5 py-1 rounded-lg text-xs font-semibold transition {{ $trendMonths === 12 ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                            12 Bulan
                        </a>
                    </div>
                </div>

                {{-- Chart Canvas Container --}}
                <div class="relative w-full h-[280px] sm:h-[300px]">
                    <canvas id="revenueTrendChart" class="w-full h-full"></canvas>
                </div>
            </div>

            {{-- ROW 2: Comparison (2/3 width) & Expense Composition (1/3 width) --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Chart 2: Pendapatan vs HPP vs Laba Kotor (Grouped Bar Chart) --}}
                <div class="lg:col-span-2 bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card flex flex-col justify-between space-y-4">
                    <div class="space-y-1 border-b border-[#FFD8D8] pb-3">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <h3 class="text-sm font-bold text-[#172B4D]">Pendapatan vs HPP vs Laba Kotor</h3>
                            <span class="text-[10px] font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
                                HPP &amp; Laba hanya final jika seluruh biaya batch lengkap
                            </span>
                        </div>
                        <p class="text-xs text-[#64748B]">
                            Perbandingan performa per bulan. Bulan dengan biaya produksi belum lengkap ditandai <span class="font-semibold text-amber-700">"HPP belum final"</span> tanpa manipulasi angka nol.
                        </p>
                    </div>

                    <div class="relative w-full h-[300px]">
                        <canvas id="profitComparisonChart" class="w-full h-full"></canvas>
                    </div>

                    <div class="text-[11px] text-[#64748B] bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl p-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Nilai HPP dan laba hanya ditampilkan untuk periode dengan biaya produksi final. Pendapatan tetap disajikan apa adanya.</span>
                    </div>
                </div>

                {{-- Chart 3: Komposisi Beban Usaha (Doughnut Chart + Exact Values List) --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card flex flex-col justify-between space-y-4">
                    <div class="space-y-1 border-b border-[#FFD8D8] pb-3">
                        <h3 class="text-sm font-bold text-[#172B4D]">Komposisi Beban Usaha</h3>
                        <p class="text-xs text-[#64748B]">
                            Distribusi beban operasional periode terpilih (di luar HPP produksi).
                        </p>
                    </div>

                    @if($hasExpenseData)
                        <div class="relative w-full h-[220px] sm:h-[240px] flex items-center justify-center">
                            <canvas id="expenseCompositionChart" class="w-full h-full"></canvas>
                        </div>

                        {{-- Compact Exact Values List --}}
                        <div class="pt-2 border-t border-[#FFD8D8] space-y-1.5 text-xs">
                            @foreach($analytics['expense_composition']['categories'] as $cat)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                                        <span class="text-[#64748B]">{{ $cat['label'] }}</span>
                                    </div>
                                    <span class="font-mono font-semibold text-[#172B4D]">{{ $cat['amount_formatted'] }}</span>
                                </div>
                            @endforeach
                            <div class="flex items-center justify-between pt-2 border-t border-[#FFD8D8] font-bold">
                                <span class="text-[#172B4D]">Total Beban Usaha</span>
                                <span class="font-mono text-[#8E1B5C]">{{ $analytics['expense_composition']['total_formatted'] }}</span>
                            </div>
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="py-12 text-center space-y-2">
                            <div class="w-12 h-12 rounded-full bg-[#FFD8D8] text-[#8E1B5C] mx-auto flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-[#172B4D]">Belum ada beban usaha pada periode ini.</p>
                            <p class="text-[11px] text-[#64748B]">Biaya operasional, marketing, bunga, pajak, dan depresiasi akan muncul setelah dicatat.</p>
                        </div>
                    @endif
                </div>

            </div>

            {{-- ROW 3: Top Products & Top Customers (Horizontal Bar Charts) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Chart 4: Produk dengan Penjualan Tertinggi --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card flex flex-col justify-between space-y-4">
                    <div class="space-y-1 border-b border-[#FFD8D8] pb-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-[#172B4D]">Produk dengan Penjualan Tertinggi</h3>
                            <span class="text-[10px] font-semibold text-[#64748B] bg-[#FFFCFB] border border-[#FFD8D8] px-2 py-0.5 rounded-md">
                                Top 5 Varian (Nilai Penjualan)
                            </span>
                        </div>
                        <p class="text-xs text-[#64748B]">
                            Kontribusi nilai transaksi (harga jual snapshot) dari pesanan non-cancelled pada periode terpilih.
                        </p>
                    </div>

                    @if($hasTopProducts)
                        <div class="relative w-full h-[280px]">
                            <canvas id="topProductsChart" class="w-full h-full"></canvas>
                        </div>
                    @else
                        <div class="py-12 text-center space-y-2">
                            <div class="w-12 h-12 rounded-full bg-[#FFD8D8] text-[#8E1B5C] mx-auto flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-[#172B4D]">Belum ada penjualan pada periode ini.</p>
                            <p class="text-[11px] text-[#64748B]">Peringkat produk terlaris akan otomatis terhitung saat terdapat transaksi pesanan aktif.</p>
                        </div>
                    @endif
                </div>

                {{-- Chart 5: Customer dengan Nilai Penjualan Tertinggi --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card flex flex-col justify-between space-y-4">
                    <div class="space-y-1 border-b border-[#FFD8D8] pb-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-[#172B4D]">Customer dengan Nilai Penjualan Tertinggi</h3>
                            <span class="text-[10px] font-semibold text-[#64748B] bg-[#FFFCFB] border border-[#FFD8D8] px-2 py-0.5 rounded-md">
                                Top 5 Customer (Nilai Transaksi)
                            </span>
                        </div>
                        <p class="text-xs text-[#64748B]">
                            Total kontribusi nilai pesanan non-cancelled per pelanggan pada periode terpilih.
                        </p>
                    </div>

                    @if($hasTopCustomers)
                        <div class="relative w-full h-[280px]">
                            <canvas id="topCustomersChart" class="w-full h-full"></canvas>
                        </div>
                    @else
                        <div class="py-12 text-center space-y-2">
                            <div class="w-12 h-12 rounded-full bg-[#FFD8D8] text-[#8E1B5C] mx-auto flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-[#172B4D]">Belum ada data customer pada periode ini.</p>
                            <p class="text-[11px] text-[#64748B]">Daftar customer dengan kontribusi penjualan tertinggi akan muncul setelah ada pesanan masuk.</p>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Compact Link to Detailed Performance Page --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#FFFCFB] border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-xs">
                <div class="space-y-0.5">
                    <h4 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Laporan Kinerja Keuangan Lengkap</h4>
                    <p class="text-xs text-[#64748B]">Lihat profit waterfall terperinci, daftar pesanan dalam periode, dan audit margin per unit pesanan.</p>
                </div>

                <a href="{{ route('owner.performance.index', ['preset' => $preset, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                   class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-xl bg-white border border-[#FFD8D8] text-[#8E1B5C] text-xs font-bold hover:bg-[#FFD8D8] transition shadow-xs shrink-0">
                    <span>Lihat Kinerja Keuangan</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            </div>

        </div>
        @else
        {{-- ── 7. Visual Analytics Upgrade Banner (FREE TIER) ─────────────── --}}
        <div class="bg-gradient-to-br from-white via-[#FFF9FB] to-[#FBF0F5] border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card relative overflow-hidden">
            <div class="absolute -right-8 -bottom-8 w-44 h-44 rounded-full bg-[#8E1B5C]/5 pointer-events-none blur-xl"></div>
            <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0 shadow-xs border border-[#FFD8D8]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Buka Grafik Analisis Penjualan &amp; Tren Bisnis Lanjutan</h3>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                                <span>Fitur Premium</span>
                            </span>
                        </div>
                        <p class="text-xs text-[#64748B] max-w-2xl leading-relaxed">
                            Dashboard Anda saat ini menampilkan ringkasan operasional dan keuangan dasar. Untuk visualisasi grafik tren omset multi-bulan, perbandingan profit vs HPP, komposisi beban operasional, serta top produk dan customer, aktifkan <strong class="text-[#8E1B5C]">Paket Premium</strong> atau <strong class="text-[#8E1B5C]">Add-On Financial Analytics</strong>.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 shrink-0 self-end md:self-center w-full md:w-auto">
                    <a href="{{ route('owner.analytics.index') }}"
                       class="flex-1 md:flex-initial inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-xs">
                        <span>Pratinjau Analitik</span>
                    </a>
                    <a href="{{ route('owner.subscriptions.index') }}"
                       class="flex-1 md:flex-initial inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-bold hover:bg-[#721743] transition shadow-xs">
                        <span>Upgrade Sekarang</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- ── 8. Ringkasan Operasional ──────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card space-y-3">
            <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2.5">
                Ringkasan Operasional
            </h3>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                <div class="p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase block">Total Pesanan</span>
                    <div class="text-xl font-bold text-[#172B4D] mt-1">{{ number_format($totalOrders, 0, ',', '.') }}</div>
                    <span class="text-[10px] text-[#64748B] block">Sepanjang waktu</span>
                </div>

                <div class="p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <span class="text-[10px] font-bold text-amber-700 uppercase block">Batch Aktif</span>
                    <div class="text-xl font-bold text-amber-800 mt-1">{{ number_format($activeBatches, 0, ',', '.') }}</div>
                    <span class="text-[10px] text-[#64748B] block">Sedang diproduksi</span>
                </div>

                <div class="p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <span class="text-[10px] font-bold text-[#8E1B5C] uppercase block">Dalam Pengiriman</span>
                    <div class="text-xl font-bold text-[#172B4D] mt-1">{{ number_format($shippingOrders, 0, ',', '.') }}</div>
                    <span class="text-[10px] text-[#64748B] block">Dalam perjalanan kurir</span>
                </div>

                <div class="p-3 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8]">
                    <span class="text-[10px] font-bold text-emerald-700 uppercase block">Pesanan Selesai</span>
                    <div class="text-xl font-bold text-emerald-800 mt-1">{{ number_format($completedOrders, 0, ',', '.') }}</div>
                    <span class="text-[10px] text-[#64748B] block">Telah diterima tuntas</span>
                </div>
            </div>
        </div>

    </div>

    @if($hasAnalyticsEntitlement ?? false)
    {{-- Local Chart.js Script --}}
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>

    {{-- Safe Data Serialization and Chart Rendering (Zero JS Financial Arithmetic) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js failed to load from local vendor asset.');
                return;
            }

            const analytics = {{ Illuminate\Support\Js::from($analytics) }};

            // Global Defaults
            Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
            Chart.defaults.font.size = 11;
            Chart.defaults.color = '#64748B';

            // ── 1. Tren Pendapatan (Line Chart) ───────────────────────────────
            const trendEl = document.getElementById('revenueTrendChart');
            if (trendEl && analytics.revenue_trend) {
                const trendLabels = analytics.revenue_trend.map(item => item.label);
                const trendValues = analytics.revenue_trend.map(item => item.revenue_numeric);

                new Chart(trendEl, {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            label: 'Pendapatan',
                            data: trendValues,
                            borderColor: '#8E1B5C',
                            backgroundColor: 'rgba(142, 27, 92, 0.08)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#8E1B5C',
                            pointBorderColor: '#FFFFFF',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#FFD8D8',
                                },
                                ticks: {
                                    callback: function (val) {
                                        if (val >= 1000000) {
                                            return 'Rp ' + (val / 1000000).toLocaleString('id-ID') + ' jt';
                                        }
                                        return 'Rp ' + val.toLocaleString('id-ID');
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#172B4D',
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 11 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    title: function (items) {
                                        if (!items.length) return '';
                                        const idx = items[0].dataIndex;
                                        return analytics.revenue_trend[idx].label;
                                    },
                                    label: function (ctx) {
                                        const idx = ctx.dataIndex;
                                        const item = analytics.revenue_trend[idx];
                                        if (!item.has_orders) {
                                            return 'Pendapatan: Rp 0 (Belum ada transaksi)';
                                        }
                                        return 'Pendapatan: ' + item.revenue_formatted;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ── 2. Pendapatan vs HPP vs Laba Kotor (Grouped Bar Chart) ────────
            const profitEl = document.getElementById('profitComparisonChart');
            if (profitEl && analytics.profit_comparison) {
                const profitData = analytics.profit_comparison;
                const labels = profitData.map(item => item.label);
                const revenueData = profitData.map(item => item.revenue_numeric);
                const hppData = profitData.map(item => item.hpp_numeric);
                const gpData = profitData.map(item => item.gross_profit_numeric);

                new Chart(profitEl, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Pendapatan',
                                data: revenueData,
                                backgroundColor: '#8E1B5C',
                                borderRadius: 6,
                                maxBarThickness: 28,
                            },
                            {
                                label: 'HPP (Biaya Produksi)',
                                data: hppData,
                                backgroundColor: '#FFD8D8',
                                borderRadius: 6,
                                maxBarThickness: 28,
                            },
                            {
                                label: 'Laba Kotor',
                                data: gpData,
                                backgroundColor: function (ctx) {
                                    const val = ctx.raw;
                                    if (val === null || typeof val === 'undefined') return '#CBD5E1';
                                    return val < 0 ? '#8E1B5C' : '#059669';
                                },
                                borderRadius: 6,
                                maxBarThickness: 28,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        scales: {
                            y: {
                                grid: {
                                    color: '#FFD8D8'
                                },
                                ticks: {
                                    callback: function (val) {
                                        if (Math.abs(val) >= 1000000) {
                                            return 'Rp ' + (val / 1000000).toLocaleString('id-ID') + ' jt';
                                        }
                                        return 'Rp ' + val.toLocaleString('id-ID');
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: {
                                    boxWidth: 12,
                                    boxHeight: 12,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: '#172B4D',
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 11 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    title: function (items) {
                                        if (!items.length) return '';
                                        const idx = items[0].dataIndex;
                                        return profitData[idx].full_label || profitData[idx].label;
                                    },
                                    label: function (ctx) {
                                        const idx = ctx.dataIndex;
                                        const item = profitData[idx];
                                        const datasetIndex = ctx.datasetIndex;

                                        if (!item.has_orders) {
                                            if (datasetIndex === 0) return 'Status: Belum ada transaksi';
                                            return null;
                                        }

                                        if (datasetIndex === 0) {
                                            return 'Pendapatan: ' + item.revenue_formatted;
                                        } else if (datasetIndex === 1) {
                                            return 'HPP: ' + (item.is_final ? item.hpp_formatted : 'Belum Final');
                                        } else if (datasetIndex === 2) {
                                            return 'Laba Kotor: ' + (item.is_final ? item.gross_profit_formatted : 'Belum Final');
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ── 3. Komposisi Beban Usaha (Doughnut Chart) ─────────────────────
            const expenseEl = document.getElementById('expenseCompositionChart');
            if (expenseEl && analytics.expense_composition && analytics.expense_composition.has_data) {
                const expData = analytics.expense_composition;
                const labels = expData.categories.map(c => c.label);
                const amounts = expData.categories.map(c => c.amount_numeric);
                const colors = expData.categories.map(c => c.color);

                new Chart(expenseEl, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: amounts,
                            backgroundColor: colors,
                            borderWidth: 2,
                            borderColor: '#FFFFFF',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#172B4D',
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 11 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function (ctx) {
                                        const idx = ctx.dataIndex;
                                        const cat = expData.categories[idx];
                                        return cat.label + ': ' + cat.amount_formatted;
                                    }
                                }
                            }
                        },
                        cutout: '65%'
                    }
                });
            }

            // ── 4. Produk dengan Penjualan Tertinggi (Horizontal Bar) ─────────
            const productsEl = document.getElementById('topProductsChart');
            if (productsEl && analytics.top_products && analytics.top_products.length > 0) {
                const products = analytics.top_products;
                const labels = products.map(p => p.label);
                const revenues = products.map(p => p.revenue_numeric);

                new Chart(productsEl, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Pendapatan',
                            data: revenues,
                            backgroundColor: '#8E1B5C',
                            borderRadius: 6,
                            maxBarThickness: 20
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    color: '#FFD8D8'
                                },
                                ticks: {
                                    callback: function (val) {
                                        if (val >= 1000000) {
                                            return 'Rp ' + (val / 1000000).toLocaleString('id-ID') + ' jt';
                                        }
                                        return 'Rp ' + val.toLocaleString('id-ID');
                                    }
                                }
                            },
                            y: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    callback: function (value, index) {
                                        const label = this.getLabelForValue(value);
                                        return label.length > 25 ? label.substring(0, 23) + '...' : label;
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#172B4D',
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 11 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    title: function (items) {
                                        if (!items.length) return '';
                                        const idx = items[0].dataIndex;
                                        return products[idx].label;
                                    },
                                    label: function (ctx) {
                                        const idx = ctx.dataIndex;
                                        const p = products[idx];
                                        return [
                                            'Pendapatan: ' + p.revenue_formatted,
                                            'Qty Dipesan: ' + p.qty_formatted
                                        ];
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ── 5. Customer dengan Nilai Penjualan Tertinggi (Horizontal Bar) ──
            const customersEl = document.getElementById('topCustomersChart');
            if (customersEl && analytics.top_customers && analytics.top_customers.length > 0) {
                const customers = analytics.top_customers;
                const labels = customers.map(c => c.name);
                const revenues = customers.map(c => c.revenue_numeric);

                new Chart(customersEl, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Pendapatan',
                            data: revenues,
                            backgroundColor: '#8E1B5C',
                            borderRadius: 6,
                            maxBarThickness: 20
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    color: '#FFD8D8'
                                },
                                ticks: {
                                    callback: function (val) {
                                        if (val >= 1000000) {
                                            return 'Rp ' + (val / 1000000).toLocaleString('id-ID') + ' jt';
                                        }
                                        return 'Rp ' + val.toLocaleString('id-ID');
                                    }
                                }
                            },
                            y: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    callback: function (value, index) {
                                        const label = this.getLabelForValue(value);
                                        return label.length > 25 ? label.substring(0, 23) + '...' : label;
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#172B4D',
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 11 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    title: function (items) {
                                        if (!items.length) return '';
                                        const idx = items[0].dataIndex;
                                        return customers[idx].name;
                                    },
                                    label: function (ctx) {
                                        const idx = ctx.dataIndex;
                                        const c = customers[idx];
                                        return [
                                            'Pendapatan: ' + c.revenue_formatted,
                                            'Jumlah Pesanan: ' + c.order_count_label
                                        ];
                                    }
                                }
                            }
                        }
                    }
                });
            }

        });
    </script>
    @endif
</x-app-layout>
