@extends('platform.layouts.app', ['title' => 'Global Platform Analytics & Ekosistem NAYARA'])

@section('content')
<div class="space-y-6">

    {{-- 1. HERO BANNER: Dark Burgundy Luxury + 3D Glass Tablet with Analytics Graphic --}}
    <section class="rounded-2xl px-5 py-3.5 sm:px-6 sm:py-4 text-white relative overflow-hidden shadow-lg border border-white/10"
             style="background: linear-gradient(135deg, #691B3D 0%, #4A132E 55%, #350920 100%);">

        {{-- Ambient wave circles behind --}}
        <div class="absolute -right-16 -top-24 w-96 h-96 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
        <div class="absolute right-48 -bottom-20 w-80 h-80 rounded-full bg-[#8E1B5C]/20 blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-4">
            {{-- Left Hero Copy --}}
            <div class="space-y-2 max-w-xl">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15">
                        PLATFORM OTORITAS &rsaquo; ANALITIK GLOBAL
                    </span>
                </div>

                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-tight">
                        Global Platform Analytics &amp; Ekosistem NAYARA
                    </h1>
                    <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5">
                        Metrik terpadu operasional SaaS: adopsi tenant, monetisasi langganan, performa periklanan, dan peringkat organik.
                    </p>
                </div>

                {{-- Period Selector Pills --}}
                <div class="pt-1.5">
                    <div class="inline-flex rounded-full bg-white p-1 text-xs shadow-md">
                        <a href="{{ route('platform.analytics.index', ['period' => '7d']) }}"
                           class="px-4 py-1.5 rounded-full font-bold transition-all {{ ($period ?? '') === '7d' ? 'bg-[#8E1B5C] text-white shadow-sm' : 'text-slate-600 hover:text-[#8E1B5C]' }}">
                            7 Hari
                        </a>
                        <a href="{{ route('platform.analytics.index', ['period' => '30d']) }}"
                           class="px-4 py-1.5 rounded-full font-bold transition-all {{ ($period ?? '30d') === '30d' ? 'bg-[#8E1B5C] text-white shadow-sm' : 'text-slate-600 hover:text-[#8E1B5C]' }}">
                            30 Hari
                        </a>
                        <a href="{{ route('platform.analytics.index', ['period' => '90d']) }}"
                           class="px-4 py-1.5 rounded-full font-bold transition-all {{ ($period ?? '') === '90d' ? 'bg-[#8E1B5C] text-white shadow-sm' : 'text-slate-600 hover:text-[#8E1B5C]' }}">
                            90 Hari
                        </a>
                    </div>
                </div>
            </div>

            {{-- Right Hero: 3D Perspective Floating Glass Tablet with Analytics Chart Graphic --}}
            <div class="shrink-0 w-full md:w-auto flex justify-center md:justify-end glass-tablet-3d-wrap py-0 relative">
                {{-- Floating 3D Glass Pill 1: Bar Chart (Top Left) --}}
                <div class="absolute -top-2 -left-2 sm:-top-2.5 sm:-left-3 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 20V10m-6 10V4m-6 16v-6" />
                    </svg>
                </div>

                {{-- Floating 3D Glass Pill 2: Store / Convection (Bottom Left) --}}
                <div class="absolute -bottom-2 -left-1.5 sm:-bottom-2.5 sm:-left-2 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1-5h16l1 5v1a3 3 0 01-3 3 3 3 0 01-3-3 3 3 0 01-3 3 3 3 0 01-3-3 3 3 0 01-3 3 3 3 0 01-3-3V9zm2 4v8h14v-8"/>
                    </svg>
                </div>

                {{-- Floating 3D Glass Pill 3: Ecosystem Users (Top Right) --}}
                <div class="absolute -top-2 -right-1.5 sm:-top-2.5 sm:-right-2 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>

                {{-- Main 3D Tablet --}}
                <div class="glass-tablet-3d w-72 sm:w-84 p-3.5 sm:p-4 relative overflow-hidden flex items-center justify-between gap-3.5">
                    {{-- Left: Nayara Icon Badge --}}
                    <div class="flex flex-col items-center justify-center shrink-0 w-16 sm:w-20">
                        <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-[#E11D48] via-[#ba4e77] to-[#8E1B5C] border border-white/40 flex flex-col items-center justify-center shadow-md p-1">
                            <span class="font-serif font-black text-white text-lg sm:text-xl leading-none italic select-none">n</span>
                            <span class="text-[6px] sm:text-[6.5px] font-extrabold text-[#FFD8D8] tracking-widest lowercase -mt-0.5 select-none">nayara</span>
                        </div>
                        <div class="w-full space-y-1 mt-2 flex flex-col items-center">
                            <div class="w-14 h-1.5 bg-white/40 rounded-full"></div>
                            <div class="w-9 h-1 bg-white/20 rounded-full"></div>
                        </div>
                    </div>

                    {{-- Right Screen: Vector Analytics Line Chart Graphic --}}
                    <div class="flex-1 bg-white/10 border border-white/20 rounded-xl p-2.5 sm:p-3 backdrop-blur-md shadow-inner flex flex-col justify-between h-24 sm:h-28 relative overflow-hidden">
                        {{-- Screen Header --}}
                        <div class="flex items-center justify-between gap-1.5 z-10">
                            <span class="text-[9.5px] sm:text-[10px] font-black tracking-widest text-white px-2 py-0.5 rounded-md bg-white/25 border border-white/35 shadow-xs select-none">
                                ANALYTICS
                            </span>
                            <div class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-[8px] font-extrabold text-[#FFD8D8] uppercase tracking-wider">LIVE</span>
                            </div>
                        </div>

                        {{-- SVG Chart Graphic (Line Graph + Glow Points) --}}
                        <div class="relative w-full h-11 sm:h-13 my-auto flex items-center">
                            <svg class="w-full h-full overflow-visible" viewBox="0 0 160 50" fill="none">
                                <defs>
                                    <linearGradient id="analyticsChartGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.35"/>
                                        <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0.0"/>
                                    </linearGradient>
                                </defs>
                                {{-- Grid lines --}}
                                <line x1="0" y1="12" x2="160" y2="12" stroke="rgba(255,255,255,0.08)" stroke-dasharray="3,3" />
                                <line x1="0" y1="28" x2="160" y2="28" stroke="rgba(255,255,255,0.08)" stroke-dasharray="3,3" />
                                <line x1="0" y1="44" x2="160" y2="44" stroke="rgba(255,255,255,0.08)" stroke-dasharray="3,3" />

                                {{-- Filled Area --}}
                                <path d="M 0,44 L 0,34 C 20,38 35,26 55,24 C 75,22 90,32 110,18 C 130,8 145,14 160,6 L 160,44 Z" fill="url(#analyticsChartGradient)" />

                                {{-- Trend Line --}}
                                <path d="M 0,34 C 20,38 35,26 55,24 C 75,22 90,32 110,18 C 130,8 145,14 160,6"
                                      stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" />

                                {{-- Data Points --}}
                                <circle cx="55" cy="24" r="2.5" fill="#FFFFFF" stroke="#8E1B5C" stroke-width="1.5" />
                                <circle cx="110" cy="18" r="2.5" fill="#FFFFFF" stroke="#8E1B5C" stroke-width="1.5" />
                                <circle cx="160" cy="6" r="3" fill="#34D399" stroke="#FFFFFF" stroke-width="1.5" />
                            </svg>
                        </div>

                        {{-- Screen Footer: Growth Indicator --}}
                        <div class="flex items-center justify-between text-[8px] sm:text-[8.5px] text-[#FFD8D8] pt-0.5 z-10">
                            <span class="font-bold text-emerald-300 flex items-center gap-0.5">
                                &#9650; +28.4%
                            </span>
                            <span class="font-medium text-white/75">SaaS Growth</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. PLATFORM TENANT METRICS: 4 KPI CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Toko Terdaftar --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[124px] relative overflow-hidden group">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">Total Toko Terdaftar</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-500 border border-rose-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1-5h16l1 5v1a3 3 0 01-3 3 3 3 0 01-3-3 3 3 0 01-3 3 3 3 0 01-3-3 3 3 0 01-3 3 3 3 0 01-3-3V9zm2 4v8h14v-8"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <span class="text-3xl font-extrabold text-[#172B4D] tracking-tight block">{{ number_format($totalShops, 0, ',', '.') }}</span>
                <span class="text-xs text-slate-500 mt-1 block">{{ $activeShops }} Aktif &bull; {{ $inactiveShops }} Non-Aktif</span>
            </div>
        </div>

        {{-- Card 2: Antrean Registrasi --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[124px] relative overflow-hidden group">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">Antrean Registrasi</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-500 border border-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M9 10h6M9 14h6"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <span class="text-3xl font-extrabold text-[#172B4D] tracking-tight block">{{ number_format($pendingRegistrations, 0, ',', '.') }}</span>
                <span class="text-xs text-slate-500 mt-1 block">{{ $approvedRegistrations }} Disetujui &bull; {{ $rejectedRegistrations }} Ditolak</span>
            </div>
        </div>

        {{-- Card 3: Langganan Aktif --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[124px] relative overflow-hidden group">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">Langganan Aktif</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-500 border border-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <span class="text-3xl font-extrabold text-[#172B4D] tracking-tight block">{{ number_format($activePremiumSubs + $activeAddonSubs, 0, ',', '.') }}</span>
                <span class="text-xs text-slate-500 mt-1 block">{{ $activePremiumSubs }} Premium &bull; {{ $activeAddonSubs }} Add-On</span>
            </div>
        </div>

        {{-- Card 4: Slot Permanen Terjual --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[124px] relative overflow-hidden group">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">Slot Permanen Terjual</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-500 border border-blue-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="5" y="11" width="14" height="10" rx="2"/><circle cx="12" cy="16" r="1.5"/><path d="M8 11V7a4 4 0 018 0v4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <span class="text-3xl font-extrabold text-[#172B4D] tracking-tight block">+{{ number_format($permanentSlotsSold, 0, ',', '.') }}</span>
                <span class="text-xs text-slate-500 mt-1 block">Kapasitas etalase berbayar</span>
            </div>
        </div>
    </div>

    {{-- 3. DETAILED 3-COLUMN METRICS (Bersih, Rapi, Seimbang & Bebas Teks Boros) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">

        {{-- Panel A: Pendapatan Terverifikasi Platform --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between space-y-4">
            <div>
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 16px;"></span>
                        <h2 class="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-[#172B4D]">Pendapatan Platform</h2>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">
                        Kas Masuk
                    </span>
                </div>

                {{-- Hero Metric --}}
                <div class="pb-3">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">TOTAL PEMASUKAN TERVERIFIKASI</span>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#8E1B5C] mt-0.5">Rp {{ number_format($totalVerifiedRevenue, 0, ',', '.') }}</p>
                </div>

                {{-- Breakdown Streams --}}
                <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
                    <span class="font-bold text-[#172B4D] block text-xs">Pemasukan per Layanan</span>
                    @if($revenueByType->isNotEmpty())
                        @foreach($revenueByType as $rev)
                            <div class="flex justify-between items-center py-2 px-2.5 rounded-xl bg-slate-50/70 border border-slate-100">
                                <span class="text-slate-700 font-medium">{{ $rev->service_type }} ({{ $rev->total_count }}x)</span>
                                <span class="font-bold text-[#172B4D]">Rp {{ number_format((float)$rev->total_amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    @else
                        {{-- 4 Core Platform Services (Clean Status List) --}}
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center py-2 px-2.5 rounded-xl bg-slate-50/70 border border-slate-100 text-xs">
                                <span class="text-slate-600">Langganan SaaS Premium</span>
                                <span class="font-bold text-slate-700">Rp 0</span>
                            </div>
                            <div class="flex justify-between items-center py-2 px-2.5 rounded-xl bg-slate-50/70 border border-slate-100 text-xs">
                                <span class="text-slate-600">Ekosistem Iklan (Ads)</span>
                                <span class="font-bold text-slate-700">Rp 0</span>
                            </div>
                            <div class="flex justify-between items-center py-2 px-2.5 rounded-xl bg-slate-50/70 border border-slate-100 text-xs">
                                <span class="text-slate-600">Slot Etalase Tambahan</span>
                                <span class="font-bold text-slate-700">Rp 0</span>
                            </div>
                            <div class="flex justify-between items-center py-2 px-2.5 rounded-xl bg-slate-50/70 border border-slate-100 text-xs">
                                <span class="text-slate-600">Layanan Setup &amp; Add-on</span>
                                <span class="font-bold text-slate-700">Rp 0</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Compact Footer --}}
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Pencairan Refund:</span>
                <span class="font-bold text-[#172B4D]">Rp {{ number_format($completedRefundsTotal, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Panel B: Kapasitas & Status Periklanan --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between space-y-4">
            <div>
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 16px;"></span>
                        <h2 class="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-[#172B4D]">Kapasitas Slot Iklan</h2>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ ($activeShopAds + $activeProductAds) > 0 ? 'bg-[#FDF4F8] text-[#8E1B5C]' : 'bg-slate-100 text-slate-600' }}">
                        {{ $activeShopAds + $activeProductAds }} Kampanye Aktif
                    </span>
                </div>

                {{-- Items --}}
                <div class="space-y-3 text-xs">
                    {{-- Iklan Toko (Highlight) --}}
                    <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-100 space-y-1.5">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[#172B4D]">Iklan Toko (Highlight Banner)</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#FDF4F8] text-[#8E1B5C]">
                                {{ $activeShopAds }}/5 Slot
                            </span>
                        </div>
                        <div class="w-full bg-slate-200/70 rounded-full h-2 overflow-hidden my-1">
                            <div class="bg-[#8E1B5C] h-2 rounded-full transition-all duration-300" style="width: {{ min(100, ($activeShopAds / 5) * 100) }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] pt-0.5">
                            <span class="text-slate-500">Maks. 5 Toko bersamaan</span>
                            <span class="font-bold {{ $activeShopAds >= 5 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $activeShopAds >= 5 ? 'Penuh' : (5 - $activeShopAds) . ' Slot Tersedia' }}
                            </span>
                        </div>
                    </div>

                    {{-- Iklan Produk (Etalase) --}}
                    <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-100 space-y-1.5">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[#172B4D]">Iklan Produk (Etalase Unggulan)</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800">
                                {{ $activeProductAds }}/6 Slot
                            </span>
                        </div>
                        <div class="w-full bg-slate-200/70 rounded-full h-2 overflow-hidden my-1">
                            <div class="bg-amber-500 h-2 rounded-full transition-all duration-300" style="width: {{ min(100, ($activeProductAds / 6) * 100) }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] pt-0.5">
                            <span class="text-slate-500">Maks. 6 Produk di etalase</span>
                            <span class="font-bold {{ $activeProductAds >= 6 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $activeProductAds >= 6 ? 'Penuh' : (6 - $activeProductAds) . ' Slot Tersedia' }}
                            </span>
                        </div>
                    </div>

                    {{-- Summary Capacity Row --}}
                    <div class="p-2.5 bg-slate-50/50 rounded-xl border border-dashed border-slate-200 flex items-center justify-between text-xs text-slate-600">
                        <span>Total Kapasitas Terpasang:</span>
                        <span class="font-bold text-slate-800">11 Slot Periklanan</span>
                    </div>
                </div>
            </div>

            {{-- Compact Footer Link --}}
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-400">Moderasi Kampanye</span>
                <a href="{{ route('platform.ads.index') }}" class="font-bold text-[#8E1B5C] hover:underline">
                    Kelola Iklan &rarr;
                </a>
            </div>
        </div>

        {{-- Panel C: Peringkat Organik Terbaik (Top Organic) --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between space-y-4">
            <div>
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 16px;"></span>
                        <h2 class="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-[#172B4D]">Peringkat Organik</h2>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">
                        Penjualan Riil
                    </span>
                </div>

                <div class="space-y-4">
                    {{-- Top 5 Toko Terlaris --}}
                    <div>
                        <div class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>Top 5 Toko Terlaris</span>
                        </div>
                        <div class="space-y-1.5 text-xs">
                            @forelse($topOrganicShops as $idx => $ts)
                                <div class="flex justify-between items-center py-2 px-2.5 rounded-xl bg-slate-50/70 hover:bg-[#FDF4F8] transition-colors border border-slate-100">
                                    <div class="flex items-center gap-2 min-w-0 flex-1 mr-1.5">
                                        <span class="w-4.5 h-4.5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $idx === 0 ? 'bg-amber-100 text-amber-800' : 'bg-[#FDF4F8] text-[#8E1B5C]' }} shrink-0">
                                            {{ $idx + 1 }}
                                        </span>
                                        <span class="font-semibold text-[11.5px] text-slate-800 truncate" title="{{ $ts->outlet_name ?: $ts->name }}">
                                            {{ $ts->outlet_name ?: ($ts->name ?: 'Outlet #' . ($idx + 1)) }}
                                        </span>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-rose-50 text-rose-700 shrink-0">
                                        {{ $ts->completed_orders_count }} Pesanan
                                    </span>
                                </div>
                            @empty
                                <p class="text-slate-400 italic text-xs py-1">Belum ada pesanan selesai.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Top 5 Produk Terlaris --}}
                    <div class="pt-2 border-t border-slate-100">
                        <div class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400 mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span>Top 5 Produk Terlaris</span>
                        </div>
                        <div class="space-y-1.5 text-xs">
                            @forelse($topOrganicProducts as $idx => $tp)
                                <div class="flex justify-between items-center py-1.5 px-2.5 rounded-xl bg-slate-50/70 hover:bg-[#FDF4F8] transition-colors border border-slate-100">
                                    <div class="flex items-center gap-2 min-w-0 flex-1 mr-1.5">
                                        <span class="w-4.5 h-4.5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $idx === 0 ? 'bg-amber-100 text-amber-800' : 'bg-[#FDF4F8] text-[#8E1B5C]' }} shrink-0">
                                            {{ $idx + 1 }}
                                        </span>
                                        <span class="font-semibold text-[11.5px] text-slate-800 truncate" title="{{ $tp->product_name ?: $tp->name }}">
                                            {{ $tp->product_name ?: ($tp->name ?: 'Produk #' . ($idx + 1)) }}
                                        </span>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-amber-50 text-amber-800 shrink-0">
                                        {{ number_format((float)$tp->total_units_sold, 0, ',', '.') }} pcs
                                    </span>
                                </div>
                            @empty
                                <p class="text-slate-400 italic text-xs py-1">Belum ada data penjualan produk.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Compact Footer Link --}}
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-400">Peringkat murni transaksi</span>
                <a href="{{ route('platform.tenants.index') }}" class="font-bold text-[#8E1B5C] hover:underline">
                    Lihat Direktori &rarr;
                </a>
            </div>
        </div>

    </div>

</div>
@endsection
