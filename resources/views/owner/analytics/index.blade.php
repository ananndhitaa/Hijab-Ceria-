@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- ── 1. Page Header Editorial ───────────────────────────────────── --}}
    <x-feature-header
        title="Financial Analytics"
        subtitle="Pantau tren omzet, biaya, margin, dan profitabilitas usaha dalam satu tampilan."
        icon="analytics"
    >
        <x-slot:badge>
            @if($hasEntitlement)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    PREMIUM ACTIVE
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    FITUR PREMIUM
                </span>
            @endif
        </x-slot:badge>

        <x-slot:actions>
            @if($hasEntitlement)
                <a href="{{ route('owner.analytics.export.csv', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                   class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Unduh CSV/Excel</span>
                </a>
                <a href="{{ route('owner.analytics.export.pdf', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                   target="_blank"
                   class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#721743] transition shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span>Cetak Laporan PDF</span>
                </a>
            @else
                <a href="{{ route('owner.performance.index') }}"
                   class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-xs">
                    <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Ringkasan Keuangan Dasar</span>
                </a>
            @endif
        </x-slot:actions>
    </x-feature-header>

    @if(!$hasEntitlement)
        {{-- ── 2. LOCK SCREEN & ADVANCED PREVIEW (FREE TIER) ─────────── --}}
        <div class="space-y-6">

            {{-- Luxury Executive Banner Top --}}
            <div class="relative bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <div class="p-6 sm:p-8 bg-gradient-to-br from-[#68113F] via-[#8E1B5C] to-[#520C31] text-white relative overflow-hidden">
                    <div class="absolute -right-12 -top-12 w-64 h-64 rounded-full bg-white/5 pointer-events-none blur-2xl"></div>
                    <div class="relative z-10 max-w-3xl space-y-3">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 border border-white/20 text-xs font-semibold text-[#FFD8D8]">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                            <span>MODUL EKSKLUSIF TINGKAT EKSEKUTIF</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                            Analisis Bisnis Lebih Mendalam
                        </h2>
                        <p class="text-xs sm:text-sm text-white/85 leading-relaxed">
                            Bandingkan performa usaha dan evaluasi kondisi keuangan antarperiode.
                        </p>
                        <div class="flex items-center gap-3 pt-2 flex-wrap">
                            <a href="{{ route('owner.subscriptions.index') }}"
                               class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-white text-[#8E1B5C] text-xs sm:text-sm font-bold hover:bg-[#FFD8D8] transition shadow-md">
                                <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                                <span>Upgrade Premium</span>
                            </a>
                            <a href="{{ route('owner.subscriptions.index') }}"
                               class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/25 text-white text-xs sm:text-sm font-semibold transition">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span>Beli Add-On Financial Analytics</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 6 Key Capabilities Grid --}}
                <div class="p-6 sm:p-8 space-y-5">
                    <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                        Kapabilitas Analisis
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {{-- Card 1 --}}
                        <div class="p-4 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] flex flex-col justify-between space-y-2">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-[#172B4D]">Tren Omzet &amp; Margin</h4>
                                <p class="text-xs text-[#64748B] leading-relaxed">
                                    Visualisasi pergerakan omzet bulanan dan rasio margin kotor secara akurat.
                                </p>
                            </div>
                        </div>

                        {{-- Card 2 --}}
                        <div class="p-4 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] flex flex-col justify-between space-y-2">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v9h9"/>
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-[#172B4D]">Komposisi Beban Usaha</h4>
                                <p class="text-xs text-[#64748B] leading-relaxed">
                                    Rincian proporsi biaya operasional, gaji, pemasaran, dan depresiasi per kategori.
                                </p>
                            </div>
                        </div>

                        {{-- Card 3 --}}
                        <div class="p-4 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] flex flex-col justify-between space-y-2">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-[#172B4D]">Evaluasi Antar-Periode</h4>
                                <p class="text-xs text-[#64748B] leading-relaxed">
                                    Komparasi pendapatan terhadap realisasi HPP produksi dan laba kotor antar-bulan.
                                </p>
                            </div>
                        </div>

                        {{-- Card 4 --}}
                        <div class="p-4 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] flex flex-col justify-between space-y-2">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-[#172B4D]">Produk &amp; Pelanggan Utama</h4>
                                <p class="text-xs text-[#64748B] leading-relaxed">
                                    Peringkat produk hijab terlaris dan pelanggan dengan kontribusi transaksi tertinggi.
                                </p>
                            </div>
                        </div>

                        {{-- Card 5 --}}
                        <div class="p-4 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] flex flex-col justify-between space-y-2">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-[#172B4D]">Ekspor PDF &amp; CSV/Excel</h4>
                                <p class="text-xs text-[#64748B] leading-relaxed">
                                    Unduh berkas laporan siap cetak dan rekap data spreadsheet untuk pembukuan.
                                </p>
                            </div>
                        </div>

                        {{-- Card 6 --}}
                        <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/50 flex flex-col justify-between space-y-2">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-emerald-900">Basic Finance Tetap Aktif</h4>
                                <p class="text-xs text-emerald-800 leading-relaxed">
                                    Pantau saldo kas, omzet berjalan, dan catatan HPP gratis di Ringkasan Keuangan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pratinjau Tampilan Visual (Safe Mockup Demo) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-6 sm:p-8 shadow-card space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#FFD8D8] pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Pratinjau Tampilan Visual</h3>
                            <p class="text-xs text-[#64748B]">Simulasi grafik tren dan ringkasan performa konveksi.</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-[#FFD8D8] text-[#8E1B5C]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Tampilan Terkunci
                    </span>
                </div>

                {{-- Demonstration Canvas with Frosted Glass Lock Overlay --}}
                <div class="relative rounded-2xl overflow-hidden border border-[#FFD8D8] bg-[#FFFCFB]">

                    {{-- Visual Demonstration Elements (Synthetic Demonstration Only - Zero Tenant Data Leakage) --}}
                    <div class="p-6 space-y-6 select-none opacity-40 filter blur-[1.5px] pointer-events-none">

                        {{-- 3 Pilar Simulation Cards --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 rounded-xl border border-[#FFD8D8] bg-white space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Pilar 1: Revenue &amp; Profit</span>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">+18.5%</span>
                                </div>
                                <div class="space-y-1">
                                    <div class="h-3 w-20 bg-slate-200 rounded"></div>
                                    <div class="h-6 w-36 bg-[#8E1B5C]/20 rounded font-mono font-bold text-lg text-[#8E1B5C] flex items-center px-1">Rp 48.500.000</div>
                                </div>
                                <div class="pt-2 border-t border-[#FFD8D8]/50 flex justify-between text-xs text-[#64748B]">
                                    <span>Gross Margin: 54,2%</span>
                                    <span>Net Margin: 28,6%</span>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl border border-[#FFD8D8] bg-white space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Pilar 2: Expense &amp; Cash</span>
                                    <span class="text-[10px] font-bold text-[#8E1B5C] bg-[#FFD8D8] px-2 py-0.5 rounded">Rasio 82%</span>
                                </div>
                                <div class="space-y-1">
                                    <div class="h-3 w-24 bg-slate-200 rounded"></div>
                                    <div class="h-6 w-36 bg-emerald-600/20 rounded font-mono font-bold text-lg text-emerald-800 flex items-center px-1">Rp 12.400.000</div>
                                </div>
                                <div class="pt-2 border-t border-[#FFD8D8]/50 flex justify-between text-xs text-[#64748B]">
                                    <span>Kas: Rp 95.000.000</span>
                                    <span>Komitmen: Rp 18.000.000</span>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl border border-[#FFD8D8] bg-white space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Pilar 3: Product &amp; Customer</span>
                                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">Top 5</span>
                                </div>
                                <div class="space-y-1">
                                    <div class="h-3 w-28 bg-slate-200 rounded"></div>
                                    <div class="h-6 w-36 bg-indigo-600/20 rounded font-mono font-bold text-lg text-indigo-800 flex items-center px-1">Pashmina Silk</div>
                                </div>
                                <div class="pt-2 border-t border-[#FFD8D8]/50 flex justify-between text-xs text-[#64748B]">
                                    <span>142 pcs terjual</span>
                                    <span>28 Pelanggan Aktif</span>
                                </div>
                            </div>
                        </div>

                        {{-- Charts Visual Representation --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            {{-- Line Chart Mockup --}}
                            <div class="lg:col-span-2 p-5 rounded-xl border border-[#FFD8D8] bg-white space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="h-4 w-44 bg-slate-200 rounded"></div>
                                    <div class="h-4 w-20 bg-[#FFD8D8] rounded"></div>
                                </div>
                                {{-- SVG Trend Silhouette --}}
                                <div class="h-44 w-full flex items-end justify-between gap-3 pt-6 px-2">
                                    <div class="w-full flex items-end justify-between gap-2 h-32">
                                        <div class="w-1/6 bg-[#FFD8D8] rounded-t h-[45%]"></div>
                                        <div class="w-1/6 bg-[#FFD8D8] rounded-t h-[60%]"></div>
                                        <div class="w-1/6 bg-[#FFD8D8] rounded-t h-[75%]"></div>
                                        <div class="w-1/6 bg-[#FFD8D8] rounded-t h-[90%]"></div>
                                        <div class="w-1/6 bg-[#FFD8D8] rounded-t h-[80%]"></div>
                                        <div class="w-1/6 bg-[#8E1B5C] rounded-t h-[100%]"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Donut Composition Mockup --}}
                            <div class="p-5 rounded-xl border border-[#FFD8D8] bg-white space-y-4 flex flex-col justify-between">
                                <div class="h-4 w-36 bg-slate-200 rounded"></div>
                                <div class="w-28 h-28 rounded-full border-8 border-[#8E1B5C] border-t-[#FFD8D8] border-r-amber-400 mx-auto"></div>
                                <div class="space-y-1.5 pt-2 border-t border-[#FFD8D8]">
                                    <div class="h-3 w-full bg-slate-100 rounded"></div>
                                    <div class="h-3 w-3/4 bg-slate-100 rounded"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Elegant Frosted Glass Lock Overlay --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center p-6 bg-white/75 backdrop-blur-[3px] text-center">
                        <div class="w-12 h-12 rounded-2xl bg-[#FFD8D8] border border-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shadow-md mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                        </div>
                        <h4 class="text-base sm:text-lg font-bold text-[#172B4D] tracking-tight">Grafik Interaktif &amp; Ekspor Terkunci</h4>
                        <p class="text-xs sm:text-sm text-[#64748B] max-w-md mt-1 leading-relaxed">
                            Aktifkan paket Premium atau Add-On untuk membuka seluruh grafik performa dan unduhan laporan.
                        </p>
                        <div class="flex items-center gap-3 mt-4 flex-wrap justify-center">
                            <a href="{{ route('owner.subscriptions.index') }}"
                               class="inline-flex items-center justify-center gap-2 h-10 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-bold hover:bg-[#721743] transition shadow-md">
                                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                                <span>Upgrade Premium</span>
                            </a>
                            <a href="{{ route('owner.subscriptions.index') }}"
                               class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl border border-[#8E1B5C] bg-white text-[#8E1B5C] text-xs sm:text-sm font-semibold hover:bg-[#FFD8D8] transition shadow-xs">
                                <span>Beli Add-On Financial Analytics</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── 4. UPGRADE COMPARISON SECTION ──────────────────────── --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-6 sm:p-8 shadow-card space-y-5">
                <div class="space-y-1">
                    <h3 class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Opsi Langganan</h3>
                    <h2 class="text-lg sm:text-xl font-bold text-[#172B4D]">Pilih Solusi Sesuai Skala Usaha Anda</h2>
                    <p class="text-xs sm:text-sm text-[#64748B]">Tersedia paket lengkap seluruh modul maupun add-on khusus keuangan.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
                    {{-- Option 1: Paket Premium --}}
                    <div class="p-6 rounded-2xl border-2 border-[#8E1B5C] bg-gradient-to-br from-white to-[#FFFCFB] shadow-md flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute -right-8 -top-8 w-28 h-28 bg-[#FFD8D8]/50 rounded-full blur-xl pointer-events-none"></div>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#FFD8D8] text-[#8E1B5C] uppercase tracking-wider">
                                    Solusi Lengkap
                                </span>
                                <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Rekomendasi</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-[#172B4D]">Paket Langganan Premium</h3>
                                <p class="text-xs text-[#64748B] mt-1">Seluruh kapabilitas ERP dan hak prioritas toko.</p>
                            </div>
                            <ul class="space-y-2.5 text-xs text-[#172B4D] pt-2 border-t border-[#FFD8D8]">
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span><strong>Akses Penuh Financial Analytics</strong> &amp; seluruh grafik tren.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span><strong>Ekspor Laporan Tanpa Batas</strong> (PDF resmi &amp; CSV/Excel).</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span><strong>Bonus 10 Slot Produk</strong> di etalase publik NAYARA Group.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span>Prioritas antrean moderasi promosi toko.</span>
                                </li>
                            </ul>
                        </div>
                        <div class="pt-6">
                            <a href="{{ route('owner.subscriptions.index') }}"
                               class="w-full inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-bold hover:bg-[#721743] transition shadow-md">
                                <span>Pilih Paket Premium</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>

                    {{-- Option 2: Add-On Financial Analytics --}}
                    <div class="p-6 rounded-2xl border border-[#FFD8D8] bg-white shadow-card flex flex-col justify-between">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 uppercase tracking-wider">
                                    Modul Mandiri
                                </span>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-[#172B4D]">Add-On Financial Analytics</h3>
                                <p class="text-xs text-[#64748B] mt-1">Khusus pemilik usaha yang membutuhkan visualisasi dan ekspor laporan.</p>
                            </div>
                            <ul class="space-y-2.5 text-xs text-[#172B4D] pt-2 border-t border-[#FFD8D8]">
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span><strong>Akses Penuh Financial Analytics</strong> &amp; grafik perbandingan periode.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span><strong>Ekspor Laporan Resmi</strong> format PDF dan CSV/Excel.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-[#64748B] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                    <span class="text-[#64748B]">Kuota slot etalase mengikuti paket aktif saat ini.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-[#64748B] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                    <span class="text-[#64748B]">Solusi hemat untuk fokus evaluasi biaya produksi.</span>
                                </li>
                            </ul>
                        </div>
                        <div class="pt-6">
                            <a href="{{ route('owner.subscriptions.index') }}"
                               class="w-full inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl border border-[#8E1B5C] bg-white text-[#8E1B5C] text-xs sm:text-sm font-bold hover:bg-[#FFD8D8] transition shadow-xs">
                                <span>Pilih Add-On Saja</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    @else
        {{-- ── 2. PREMIUM & ADD-ON FULL FINANCIAL ANALYTICS ─────────── --}}

        {{-- Filter Periode Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-3.5 sm:px-5 sm:py-3.5 shadow-card">
            <form action="{{ route('owner.analytics.index') }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold text-[#64748B] uppercase tracking-wider mr-2">Filter Periode:</span>
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
                            class="inline-flex items-center justify-center h-8 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#721743] transition cursor-pointer shadow-xs">
                        Terapkan Filter
                    </button>
                </div>
                <span class="text-xs text-[#64748B]">
                    Periode aktif: <strong class="text-[#172B4D]">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</strong> s/d <strong class="text-[#172B4D]">{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</strong>
                </span>
            </form>
        </div>

        @php
            $m = $analytics['metrics'];
            $charts = $analytics['charts'] ?? [];
            $summary = $analytics['summary'] ?? [];
            $hasExpenseData = $charts['expense_composition']['has_data'] ?? false;
            $hasTopProducts = ! empty($charts['top_products']);
            $hasTopCustomers = ! empty($charts['top_customers']);

            // Derived Executive Ratios
            $collectionRate = bccomp((string)$m['revenue'], '0.00', 2) > 0
                ? number_format((float) bcmul(bcdiv((string)$m['confirmed_payments'], (string)$m['revenue'], 4), '100', 2), 2, ',', '.') . '%'
                : '100%';
        @endphp

        {{-- ── 3. EXECUTIVE OVERVIEW: GROUPING 3 PILAR UTAMA ────────── --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Executive Overview</h3>
                    <h2 class="text-base sm:text-lg font-bold text-[#172B4D]">Tiga Pilar Kinerja Finansial Bisnis</h2>
                </div>
                <span class="text-[11px] font-semibold text-[#64748B]">Data Konsolidasi Riil Periode Terpilih</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-stretch">

                {{-- PILAR 1: REVENUE & PROFITABILITY --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card flex flex-col justify-between space-y-4 hover:border-[#FFD8D8] hover:shadow-md transition">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Pilar 1</h4>
                                    <p class="text-xs font-semibold text-[#8E1B5C]">Revenue &amp; Profitability</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-[#FFD8D8] text-[#8E1B5C]">Pertumbuhan</span>
                        </div>

                        {{-- Metric 1: Revenue --}}
                        <div>
                            <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Total Pendapatan (Omset)</span>
                            <p class="text-2xl font-bold font-mono text-emerald-700 tracking-tight leading-tight mt-0.5">
                                Rp {{ number_format((float)$m['revenue'], 0, ',', '.') }}
                            </p>
                        </div>

                        {{-- Metric 2: HPP & Status --}}
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#FFD8D8]/60 text-xs">
                            <div>
                                <span class="text-[#64748B] block text-[11px]">HPP Produksi:</span>
                                @if($m['hpp'] !== null)
                                    <span class="font-mono font-bold text-[#172B4D]">Rp {{ number_format((float)$m['hpp'], 0, ',', '.') }}</span>
                                    <span class="text-[10px] text-emerald-700 font-bold block">✓ Biaya Lengkap</span>
                                @else
                                    <span class="font-mono font-bold text-amber-700">Rp {{ number_format((float)$m['finalized_hpp'], 0, ',', '.') }}</span>
                                    <span class="text-[10px] text-amber-700 font-medium block">Parsial (Batch Berjalan)</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-[#64748B] block text-[11px]">Gross Margin:</span>
                                @if($m['gross_profit'] !== null && $m['gross_margin'] !== null)
                                    <span class="font-mono font-bold text-[#8E1B5C]">{{ number_format((float)$m['gross_margin'], 2, ',', '.') }}%</span>
                                    <span class="text-[10px] text-[#64748B] block">Rp {{ number_format((float)$m['gross_profit'], 0, ',', '.') }}</span>
                                @else
                                    <span class="text-amber-700 font-semibold text-xs">—</span>
                                    <span class="text-[10px] text-[#64748B] block">Menunggu HPP Final</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Net Margin Footer --}}
                    <div class="pt-3 border-t border-[#FFD8D8] flex items-center justify-between text-xs bg-[#FFFCFB] -mx-5 -mb-5 p-4 rounded-b-2xl">
                        <span class="font-semibold text-[#172B4D]">Net Margin Usaha:</span>
                        @if($m['net_profit'] !== null && $m['net_margin'] !== null)
                            <span class="font-mono font-bold {{ (float)$m['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                {{ number_format((float)$m['net_margin'], 2, ',', '.') }}% (Rp {{ number_format((float)$m['net_profit'], 0, ',', '.') }})
                            </span>
                        @else
                            <span class="text-amber-700 font-medium text-xs">Menunggu Finalisasi Batch</span>
                        @endif
                    </div>
                </div>

                {{-- PILAR 2: EXPENSE & CASH FLOW --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card flex flex-col justify-between space-y-4 hover:border-[#FFD8D8] hover:shadow-md transition">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Pilar 2</h4>
                                    <p class="text-xs font-semibold text-[#8E1B5C]">Expense &amp; Cash Flow</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">Likuiditas</span>
                        </div>

                        {{-- Metric 1: Cash In vs Piutang --}}
                        <div>
                            <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Penerimaan Kas (Customer Cash In)</span>
                            <p class="text-2xl font-bold font-mono text-emerald-700 tracking-tight leading-tight mt-0.5">
                                Rp {{ number_format((float)$m['confirmed_payments'], 0, ',', '.') }}
                            </p>
                        </div>

                        {{-- Metric 2: Rasio Penagihan & Beban --}}
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#FFD8D8]/60 text-xs">
                            <div>
                                <span class="text-[#64748B] block text-[11px]">Rasio Penagihan:</span>
                                <span class="font-mono font-bold text-emerald-800">{{ $collectionRate }}</span>
                                <span class="text-[10px] text-[#64748B] block">Piutang: Rp {{ number_format((float)($m['piutang_customer'] ?? 0), 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-[#64748B] block text-[11px]">Beban Operasional:</span>
                                <span class="font-mono font-bold text-rose-600">Rp {{ number_format((float)$m['expenses'], 0, ',', '.') }}</span>
                                <span class="text-[10px] text-[#64748B] block">Di luar modal HPP</span>
                            </div>
                        </div>
                    </div>

                    {{-- ROI Footer --}}
                    <div class="pt-3 border-t border-[#FFD8D8] flex items-center justify-between text-xs bg-[#FFFCFB] -mx-5 -mb-5 p-4 rounded-b-2xl">
                        <span class="font-semibold text-[#172B4D]" title="Formula: (Total Pendapatan / Total Pengeluaran) × 100%">ROI Bisnis:</span>
                        <span class="font-mono font-bold text-[#8E1B5C]" title="Formula: (Total Pendapatan / Total Pengeluaran) × 100%">
                            {{ $m['roi'] !== null ? number_format((float)$m['roi'], 2, ',', '.') . '%' : 'N/A' }}
                        </span>
                    </div>
                </div>

                {{-- PILAR 3: PRODUCT & CUSTOMER PERFORMANCE --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card flex flex-col justify-between space-y-4 hover:border-[#FFD8D8] hover:shadow-md transition">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Pilar 3</h4>
                                    <p class="text-xs font-semibold text-[#8E1B5C]">Product &amp; Customer</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700">Kontribusi</span>
                        </div>

                        {{-- Metric 1: Top Product --}}
                        <div>
                            <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Varian Hijab Terlaris</span>
                            @if($hasTopProducts && isset($charts['top_products'][0]))
                                <p class="text-lg font-bold text-[#172B4D] truncate mt-0.5">
                                    {{ $charts['top_products'][0]['label'] }}
                                </p>
                                <p class="text-xs font-mono font-semibold text-[#8E1B5C]">
                                    {{ $charts['top_products'][0]['revenue_formatted'] }}
                                </p>
                            @else
                                <p class="text-sm font-semibold text-[#64748B] mt-0.5">Belum ada transaksi</p>
                            @endif
                        </div>

                        {{-- Metric 2: Top Customer & Order Stats --}}
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#FFD8D8]/60 text-xs">
                            <div>
                                <span class="text-[#64748B] block text-[11px]">Customer Utama:</span>
                                @if($hasTopCustomers && isset($charts['top_customers'][0]))
                                    <span class="font-bold text-[#172B4D] truncate block">{{ $charts['top_customers'][0]['name'] }}</span>
                                    <span class="text-[10px] text-[#8E1B5C] font-mono block">{{ $charts['top_customers'][0]['revenue_formatted'] }}</span>
                                @else
                                    <span class="text-[#64748B]">—</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-[#64748B] block text-[11px]">Status Pesanan:</span>
                                <span class="font-mono font-bold text-emerald-800">{{ $summary['finalized_orders_count'] ?? 0 }} Final</span>
                                <span class="text-[10px] text-[#64748B] block">{{ $summary['pending_finalization_count'] ?? 0 }} Menunggu Biaya</span>
                            </div>
                        </div>
                    </div>

                    {{-- Kas Tersedia Footer --}}
                    <div class="pt-3 border-t border-[#FFD8D8] flex items-center justify-between text-xs bg-[#FFFCFB] -mx-5 -mb-5 p-4 rounded-b-2xl">
                        <span class="font-semibold text-[#172B4D]">Kas Saat Ini vs Komitmen:</span>
                        <span class="font-mono font-bold text-[#172B4D]">
                            Rp {{ number_format((float)($m['kas_tersedia'] ?? 0), 0, ',', '.') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── 4. ADVANCED VISUAL CHARTS SECTION ──────────────────────── --}}
        <div class="space-y-6">

            {{-- ROW 1: Tren Pendapatan Bulanan --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#FFD8D8] pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Tren Pendapatan Bulanan</h3>
                            <span class="px-2 py-0.5 rounded-md bg-[#FFD8D8] text-[#8E1B5C] text-[10px] font-bold">
                                {{ $charts['trend_scope_label'] ?? 'Trailing 6 Bulan' }}
                            </span>
                        </div>
                        <p class="text-xs text-[#64748B] mt-0.5">
                            Perkembangan pendapatan selama periode aktif berdasarkan pesanan terkonfirmasi non-cancelled.
                        </p>
                    </div>
                </div>
                <div class="relative w-full h-[280px] sm:h-[300px]">
                    <canvas id="revenueTrendChart" class="w-full h-full"></canvas>
                </div>
            </div>

            {{-- ROW 2: Pendapatan vs HPP vs Laba & Komposisi Beban Usaha --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Grouped Bar Chart --}}
                <div class="lg:col-span-2 bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card flex flex-col justify-between space-y-4">
                    <div class="space-y-1 border-b border-[#FFD8D8] pb-3">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Pendapatan vs HPP vs Laba Kotor</h3>
                            <span class="text-[10px] font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
                                HPP &amp; Laba hanya final jika seluruh biaya batch lengkap
                            </span>
                        </div>
                        <p class="text-xs text-[#64748B]">
                            Perbandingan performa per bulan. Periode tanpa kalkulasi biaya final ditandai secara akurat.
                        </p>
                    </div>

                    <div class="relative w-full h-[300px]">
                        <canvas id="profitComparisonChart" class="w-full h-full"></canvas>
                    </div>
                </div>

                {{-- Komposisi Beban Usaha --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card flex flex-col justify-between space-y-4">
                    <div class="space-y-1 border-b border-[#FFD8D8] pb-3">
                        <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Komposisi Beban Usaha</h3>
                        <p class="text-xs text-[#64748B]">
                            Distribusi beban operasional periode terpilih.
                        </p>
                    </div>

                    @if($hasExpenseData)
                        <div class="relative w-full h-[220px] sm:h-[240px] flex items-center justify-center">
                            <canvas id="expenseCompositionChart" class="w-full h-full"></canvas>
                        </div>

                        <div class="pt-2 border-t border-[#FFD8D8] space-y-1.5 text-xs">
                            @foreach($charts['expense_composition']['categories'] as $cat)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                                        <span class="text-[#64748B]">{{ $cat['label'] }}</span>
                                    </div>
                                    <span class="font-mono font-semibold text-[#172B4D]">{{ $cat['amount_formatted'] }}</span>
                                </div>
                            @endforeach
                            <div class="flex items-center justify-between pt-2 border-t border-[#FFD8D8] font-bold">
                                <span class="text-[#172B4D]">Total Beban</span>
                                <span class="font-mono text-[#8E1B5C]">{{ $charts['expense_composition']['total_formatted'] }}</span>
                            </div>
                        </div>
                    @else
                        <div class="py-12 text-center space-y-2">
                            <div class="w-10 h-10 rounded-full bg-[#FFD8D8] text-[#8E1B5C] mx-auto flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-[#172B4D]">Belum ada beban usaha pada periode ini.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ROW 3: Top Products & Top Customers --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Top Products --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card flex flex-col justify-between space-y-4">
                    <div class="space-y-1 border-b border-[#FFD8D8] pb-3">
                        <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Produk dengan Penjualan Tertinggi</h3>
                        <p class="text-xs text-[#64748B]">Top 5 varian berdasarkan nilai transaksi terkonfirmasi.</p>
                    </div>

                    @if($hasTopProducts)
                        <div class="relative w-full h-[280px]">
                            <canvas id="topProductsChart" class="w-full h-full"></canvas>
                        </div>
                    @else
                        <div class="py-12 text-center space-y-2">
                            <p class="text-xs font-semibold text-[#172B4D]">Belum ada data penjualan pada periode ini.</p>
                        </div>
                    @endif
                </div>

                {{-- Top Customers --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-card flex flex-col justify-between space-y-4">
                    <div class="space-y-1 border-b border-[#FFD8D8] pb-3">
                        <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Customer dengan Nilai Penjualan Tertinggi</h3>
                        <p class="text-xs text-[#64748B]">Top 5 pelanggan berdasarkan total transaksi.</p>
                    </div>

                    @if($hasTopCustomers)
                        <div class="relative w-full h-[280px]">
                            <canvas id="topCustomersChart" class="w-full h-full"></canvas>
                        </div>
                    @else
                        <div class="py-12 text-center space-y-2">
                            <p class="text-xs font-semibold text-[#172B4D]">Belum ada data pelanggan pada periode ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Local Chart.js Script --}}
        <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Chart === 'undefined') return;

                const charts = {{ Illuminate\Support\Js::from($charts) }};

                Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
                Chart.defaults.font.size = 11;
                Chart.defaults.color = '#64748B';

                // 1. Revenue Trend
                const trendEl = document.getElementById('revenueTrendChart');
                if (trendEl && charts.revenue_trend) {
                    new Chart(trendEl, {
                        type: 'line',
                        data: {
                            labels: charts.revenue_trend.map(item => item.label),
                            datasets: [{
                                label: 'Pendapatan',
                                data: charts.revenue_trend.map(item => item.revenue_numeric),
                                borderColor: '#8E1B5C',
                                backgroundColor: 'rgba(142, 27, 92, 0.08)',
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.3,
                                pointBackgroundColor: '#8E1B5C',
                                pointBorderColor: '#FFFFFF',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#FFD8D8' },
                                    ticks: {
                                        callback: val => 'Rp ' + (val >= 1000000 ? (val / 1000000).toLocaleString('id-ID') + ' jt' : val.toLocaleString('id-ID'))
                                    }
                                },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }

                // 2. Profit Comparison
                const profitEl = document.getElementById('profitComparisonChart');
                if (profitEl && charts.profit_comparison) {
                    new Chart(profitEl, {
                        type: 'bar',
                        data: {
                            labels: charts.profit_comparison.map(item => item.label),
                            datasets: [
                                {
                                    label: 'Pendapatan',
                                    data: charts.profit_comparison.map(item => item.revenue_numeric),
                                    backgroundColor: '#8E1B5C',
                                    borderRadius: 6,
                                    maxBarThickness: 28,
                                },
                                {
                                    label: 'HPP (Biaya Produksi)',
                                    data: charts.profit_comparison.map(item => item.hpp_numeric),
                                    backgroundColor: '#FFD8D8',
                                    borderRadius: 6,
                                    maxBarThickness: 28,
                                },
                                {
                                    label: 'Laba Kotor',
                                    data: charts.profit_comparison.map(item => item.gross_profit_numeric),
                                    backgroundColor: ctx => (ctx.raw < 0 ? '#8E1B5C' : '#059669'),
                                    borderRadius: 6,
                                    maxBarThickness: 28,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'top', align: 'end' } },
                            scales: {
                                y: {
                                    grid: { color: '#FFD8D8' },
                                    ticks: {
                                        callback: val => 'Rp ' + (Math.abs(val) >= 1000000 ? (val / 1000000).toLocaleString('id-ID') + ' jt' : val.toLocaleString('id-ID'))
                                    }
                                },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }

                // 3. Expense Composition
                const expenseEl = document.getElementById('expenseCompositionChart');
                if (expenseEl && charts.expense_composition && charts.expense_composition.has_data) {
                    new Chart(expenseEl, {
                        type: 'doughnut',
                        data: {
                            labels: charts.expense_composition.categories.map(c => c.label),
                            datasets: [{
                                data: charts.expense_composition.categories.map(c => c.amount_numeric),
                                backgroundColor: charts.expense_composition.categories.map(c => c.color),
                                borderWidth: 2,
                                borderColor: '#FFFFFF',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            cutout: '65%'
                        }
                    });
                }

                // 4. Top Products
                const productsEl = document.getElementById('topProductsChart');
                if (productsEl && charts.top_products && charts.top_products.length > 0) {
                    new Chart(productsEl, {
                        type: 'bar',
                        data: {
                            labels: charts.top_products.map(p => p.label),
                            datasets: [{
                                label: 'Pendapatan',
                                data: charts.top_products.map(p => p.revenue_numeric),
                                backgroundColor: '#8E1B5C',
                                borderRadius: 6,
                                maxBarThickness: 20
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    grid: { color: '#FFD8D8' },
                                    ticks: {
                                        callback: val => 'Rp ' + (val >= 1000000 ? (val / 1000000).toLocaleString('id-ID') + ' jt' : val.toLocaleString('id-ID'))
                                    }
                                },
                                y: { grid: { display: false } }
                            }
                        }
                    });
                }

                // 5. Top Customers
                const customersEl = document.getElementById('topCustomersChart');
                if (customersEl && charts.top_customers && charts.top_customers.length > 0) {
                    new Chart(customersEl, {
                        type: 'bar',
                        data: {
                            labels: charts.top_customers.map(c => c.name),
                            datasets: [{
                                label: 'Pendapatan',
                                data: charts.top_customers.map(c => c.revenue_numeric),
                                backgroundColor: '#8E1B5C',
                                borderRadius: 6,
                                maxBarThickness: 20
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    grid: { color: '#FFD8D8' },
                                    ticks: {
                                        callback: val => 'Rp ' + (val >= 1000000 ? (val / 1000000).toLocaleString('id-ID') + ' jt' : val.toLocaleString('id-ID'))
                                    }
                                },
                                y: { grid: { display: false } }
                            }
                        }
                    });
                }
            });
        </script>
    @endif

</div>
@endsection