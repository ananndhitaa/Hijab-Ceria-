@extends('platform.layouts.app', [
    'title' => 'Log Aktivitas Platform',
    'headerTitle' => 'Log Aktivitas & Riwayat Keputusan Platform',
    'breadcrumb' => 'Platform / Log Aktivitas'
])

@section('content')
<div class="space-y-6">

    {{-- =========================================================================
         1. HERO BANNER: PORTAL SUPER ADMIN NAYARA LOG AKTIVITAS (Ukuran & Proporsi Identik Dashboard)
         ========================================================================= --}}
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
                        <svg class="w-4 h-4 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15" data-i18n="audit_hero_badge">
                        TATA KELOLA &rsaquo; LOG AKTIVITAS
                    </span>
                </div>

                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-tight" data-i18n="audit_hero_title">
                        Log Aktivitas &amp; Riwayat Keputusan Platform
                    </h1>
                    <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5" data-i18n="audit_hero_subtitle">
                        Mencatat seluruh aksi onboarding, revisi, persetujuan, penolakan, moderasi, refund, dan keputusan penting di platform NAYARA.
                    </p>
                </div>

                {{-- Action Pills Underneath (Identik Dashboard) --}}
                <div class="flex flex-wrap items-center gap-2 pt-0.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 border border-white/20 text-white text-[11px] font-bold backdrop-blur-xs select-none shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                        <span data-i18n="audit_immutable_badge">Immutable Decision Log</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 border border-white/20 text-white text-[11px] font-bold backdrop-blur-xs select-none shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>Audit Trail Verified</span>
                    </span>
                </div>
            </div>

            {{-- Right Hero: 3D Perspective Floating Glass Tablet (Ukuran Identik Dashboard w-72 sm:w-80, h-22 sm:h-24, Bisa Bergerak) --}}
            <div class="shrink-0 w-full md:w-auto flex justify-center md:justify-end glass-tablet-3d-wrap py-0 relative select-none" id="auditHero3dWrap">
                {{-- Floating 3D Glass Pill 1: Shield (Top Left) --}}
                <div class="absolute -top-2.5 -left-2.5 sm:-top-3 sm:-left-3 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>

                {{-- Floating 3D Glass Pill 2: Real-time Clock (Top Right) --}}
                <div class="absolute -top-2.5 -right-1.5 sm:-top-3 sm:-right-2 px-2.5 py-0.5 rounded-full bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center gap-1 text-[9.5px] font-bold text-white z-20 pointer-events-none">
                    <svg class="w-3 h-3 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Real-time</span>
                </div>

                {{-- Floating 3D Glass Pill 3: RBAC Audit (Bottom Right) --}}
                <div class="absolute -bottom-2.5 -right-2 sm:-bottom-3 sm:-right-2 px-2.5 py-0.5 rounded-full bg-white text-[#8E1B5C] border border-rose-200 shadow-md flex items-center gap-1 text-[9.5px] font-black z-20 pointer-events-none">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>RBAC Audit</span>
                </div>

                {{-- Main 3D Tablet: Ukuran Identik Dashboard (w-72 sm:w-80, h-22 sm:h-24) --}}
                <div class="glass-tablet-3d w-72 sm:w-80 p-3.5 sm:p-4 relative overflow-hidden flex items-center justify-between gap-4 cursor-pointer" id="audit3dCard">
                    {{-- Left: Shield Badge (Identik Kolom Kiri Tablet Dashboard w-20 sm:w-24) --}}
                    <div class="flex flex-col items-center justify-center shrink-0 w-20 sm:w-24">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#E11D48] via-[#ba4e77] to-[#8E1B5C] border border-white/40 flex flex-col items-center justify-center shadow-md p-1 relative">
                            <svg class="w-6 h-6 text-white drop-shadow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="w-full space-y-1 mt-2 flex flex-col items-center">
                            <div class="w-16 h-1.5 bg-white/40 rounded-full"></div>
                            <div class="w-10 h-1 bg-white/20 rounded-full"></div>
                        </div>
                    </div>

                    {{-- Right: Mini Checklist Board in Glass Tablet (h-22 sm:h-24 Identik Dashboard) --}}
                    <div class="flex-1 bg-white/10 border border-white/20 rounded-xl p-2.5 sm:p-3 backdrop-blur-md shadow-inner flex flex-col justify-between h-22 sm:h-24">
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-300">
                                <span class="w-3.5 h-3.5 rounded-full bg-emerald-500/30 flex items-center justify-center text-[9px]">&check;</span>
                                <span class="truncate" data-i18n="audit_3d_verified">Audit Trail Verified</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] text-rose-200">
                                <span class="w-3.5 h-3.5 rounded-full bg-rose-500/30 flex items-center justify-center text-[9px]">&check;</span>
                                <span class="truncate" data-i18n="audit_3d_tamper">Tamper-Proof Records</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] text-white/90">
                                <span class="w-3.5 h-3.5 rounded-full bg-white/20 flex items-center justify-center text-[9px]">&check;</span>
                                <span class="truncate" data-i18n="audit_3d_tracked">Actor Logged &amp; Tracked</span>
                            </div>
                        </div>

                        <div class="w-full h-0.5 bg-white/25 rounded-full"></div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- =========================================================================
         2. 4 TOP KPI METRIC CARDS (Identik Font, Ikon, Ukuran & Watermark Halaman Dashboard)
         ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Card 1: TOTAL AKTIVITAS --}}
        <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider whitespace-nowrap" data-i18n="audit_kpi_total">TOTAL AKTIVITAS</span>
                <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5"><use href="#i-wm-clipboard"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ $stats['total'] }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="audit_kpi_total_sub">
                    Seluruh aksi tercatat di platform
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg"><use href="#i-wm-clipboard"/></svg>
        </div>

        {{-- Card 2: MENUNGGU TINJAUAN --}}
        <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="audit_kpi_pending">MENUNGGU TINJAUAN</span>
                <div class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5"><use href="#i-clock"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ $stats['pending'] }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="audit_kpi_pending_sub">
                    Aktivitas perlu verifikasi
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg"><use href="#i-wm-clipboard"/></svg>
        </div>

        {{-- Card 3: PERSETUJUAN TERCATAT --}}
        <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="audit_kpi_approved">PERSETUJUAN TERCATAT</span>
                <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5"><use href="#i-check"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ $stats['approved'] }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="audit_kpi_approved_sub">
                    Keputusan disetujui platform
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg"><use href="#i-wm-clipboard"/></svg>
        </div>

        {{-- Card 4: PENOLAKAN / REVISI --}}
        <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="audit_kpi_rejected">PENOLAKAN / REVISI</span>
                <div class="w-6 h-6 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5"><use href="#i-edit"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ $stats['rejected_or_revision'] }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="audit_kpi_rejected_sub">
                    Aktivitas ditolak atau diminta revisi
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg"><use href="#i-wm-ban"/></svg>
        </div>

    </div>

    {{-- =========================================================================
         3. MAIN SECTION: RIWAYAT AKTIVITAS TERBARU (Full Width, Tanpa Ringkasan Audit, Tombol Aksi Lengkap)
         ========================================================================= --}}
    <div class="w-full bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs space-y-4">

        {{-- Header Bar dengan Tombol Aksi Lengkap (Fix Gambar 4) --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 border-b border-slate-100">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 18px;"></span>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-[#172B4D] leading-tight" data-i18n="audit_recent_title">
                        Riwayat Aktivitas Terbaru
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5" data-i18n="audit_recent_sub">
                        Aktivitas sistem, keputusan, dan perubahan status terkini di platform.
                    </p>
                </div>
            </div>

            {{-- Tombol Aksi Header: Unduh Log (.csv) & Reset Filter --}}
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('platform.audit.index', ['export' => 'csv']) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-gradient-to-r from-[#8E1B5C] to-[#68113F] text-white text-xs font-bold shadow-xs hover:from-[#75164B] hover:to-[#550C32] transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span data-i18n="audit_btn_download">Unduh Log (.csv)</span>
                </a>

                <a href="{{ route('platform.audit.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-[#E1CFD9] bg-white text-slate-700 hover:text-[#8E1B5C] hover:border-[#8E1B5C]/30 text-xs font-bold transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span data-i18n="audit_btn_reset">Reset Filter</span>
                </a>
            </div>
        </div>

        {{-- Category Filter Tabs with Icons --}}
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="audit-cat-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-[#8E1B5C] text-white shadow-2xs cursor-pointer" data-category="ALL">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span data-i18n="audit_tab_all">Semua</span>
            </button>
            <button type="button" class="audit-cat-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white border border-[#E1CFD9] text-slate-700 hover:text-[#8E1B5C] cursor-pointer" data-category="REGISTRATION">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span data-i18n="audit_tab_registration">Pendaftaran</span>
            </button>
            <button type="button" class="audit-cat-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white border border-[#E1CFD9] text-slate-700 hover:text-[#8E1B5C] cursor-pointer" data-category="ADS">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/></svg>
                <span data-i18n="audit_tab_ads">Moderasi Iklan</span>
            </button>
            <button type="button" class="audit-cat-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white border border-[#E1CFD9] text-slate-700 hover:text-[#8E1B5C] cursor-pointer" data-category="REFUND">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span data-i18n="audit_tab_refund">Refund</span>
            </button>
            <button type="button" class="audit-cat-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white border border-[#E1CFD9] text-slate-700 hover:text-[#8E1B5C] cursor-pointer" data-category="BILLING">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span data-i18n="audit_tab_billing">Tagihan</span>
            </button>
        </div>

        {{-- Search & Filter Controls Row --}}
        <form method="GET" action="{{ route('platform.audit.index') }}" class="flex flex-col sm:flex-row items-center gap-2 pt-1" id="auditSearchFilterForm">
            <div class="relative flex-1 w-full">
                <input type="text" name="search" id="auditSearchInput" value="{{ request('search') }}"
                       placeholder="Cari tenant, aksi, aktor, atau catatan..."
                       class="w-full text-xs rounded-xl border border-[#E1CFD9] pl-8 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE]"
                       data-i18n-placeholder="audit_search_placeholder">
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="status" id="auditStatusSelect" class="w-full sm:w-36 text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE] shrink-0">
                <option value="ALL" {{ request('status') === 'ALL' ? 'selected' : '' }} data-i18n="audit_status_all">Semua Status</option>
                <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }} data-i18n="audit_status_pending">Pending</option>
                <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }} data-i18n="audit_status_approved">Disetujui</option>
                <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }} data-i18n="audit_status_rejected">Ditolak</option>
                <option value="NEEDS_REVISION" {{ request('status') === 'NEEDS_REVISION' ? 'selected' : '' }} data-i18n="audit_status_revision">Revisi</option>
            </select>

            <select name="period" id="auditPeriodSelect" class="w-full sm:w-36 text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE] shrink-0">
                <option value="ALL" data-i18n="audit_period_all">Semua Periode</option>
                <option value="TODAY" data-i18n="audit_period_today">Hari Ini</option>
                <option value="7DAYS" data-i18n="audit_period_7d">7 Hari Terakhir</option>
                <option value="30DAYS" data-i18n="audit_period_30d">30 Hari Terakhir</option>
            </select>

            <button type="submit" class="w-full sm:w-auto px-4 py-2.5 bg-gradient-to-r from-[#8E1B5C] to-[#68113F] text-white text-xs font-bold rounded-xl shadow-xs hover:from-[#75164B] hover:to-[#550C32] transition-all flex items-center justify-center gap-1.5 cursor-pointer shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                <span data-i18n="audit_btn_filter">Filter</span>
            </button>
        </form>

        {{-- Table Header Labels (Desktop View) --}}
        <div class="hidden lg:grid items-center gap-3 px-4 py-2 rounded-xl bg-slate-50 border border-slate-100 text-[10.5px] font-extrabold uppercase text-slate-500 tracking-wider"
             style="grid-template-columns: 36px 95px 140px 140px 120px 150px 1fr;">
            <div></div>
            <div data-i18n="audit_th_time">Waktu</div>
            <div data-i18n="audit_th_tenant">Tenant</div>
            <div data-i18n="audit_th_action">Aksi</div>
            <div class="text-center" data-i18n="audit_th_status">Status</div>
            <div data-i18n="audit_th_actor">Aktor</div>
            <div data-i18n="audit_th_notes">Catatan &amp; Detail</div>
        </div>

        {{-- Activity Log Item Rows (Full Width, Catatan Tidak Terpotong - Fix Gambar 2) --}}
        <div class="overflow-x-auto -mx-1 px-1">
            <div class="min-w-[800px] lg:min-w-0 space-y-2.5" id="auditRowsContainer">
            @forelse($histories as $h)
                @php
                    $actionName = $h->action;
                    $isApproval = in_array($actionName, ['APPROVE', 'APPROVED']);
                    $isRejection = in_array($actionName, ['REJECT', 'REJECTED']);
                    $isRevision = in_array($actionName, ['REQUEST_REVISION', 'REVISION_REQUESTED', 'RESUBMIT']);

                    $iconBg = 'bg-emerald-50 text-emerald-600 border-emerald-200';
                    if ($isRejection) {
                        $iconBg = 'bg-rose-50 text-[#8E1B5C] border-rose-200';
                    } elseif ($isRevision) {
                        $iconBg = 'bg-blue-50 text-blue-600 border-blue-200';
                    } elseif ($actionName === 'SUBMIT') {
                        $iconBg = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    }

                    $statusTo = $h->to_status;
                    $statusBadgeClass = match($statusTo) {
                        'APPROVED', 'DISETUJUI' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'REJECTED', 'DITOLAK' => 'bg-rose-50 text-rose-700 border-rose-200',
                        'NEEDS_REVISION', 'REVISI' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'PENDING' => 'bg-amber-50 text-amber-700 border-amber-200',
                        default => 'bg-slate-50 text-slate-700 border-slate-200',
                    };
                @endphp
                <div class="p-3 sm:p-3.5 rounded-xl border border-[#F0E4EA] hover:border-[#8E1B5C]/35 bg-white hover:bg-[#FFF9FB]/50 transition-all grid items-center gap-3 text-xs audit-log-row shadow-2xs"
                     style="grid-template-columns: 36px 95px 140px 140px 120px 150px 1fr;"
                     data-category="REGISTRATION">

                    {{-- 1. Action Icon --}}
                    <div class="w-8 h-8 rounded-xl border flex items-center justify-center shrink-0 {{ $iconBg }}">
                        @if($isApproval)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @elseif($isRejection)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        @elseif($isRevision)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        @endif
                    </div>

                    {{-- 2. Waktu & Tanggal --}}
                    <div class="text-left">
                        <div class="font-extrabold text-[#172B4D] leading-tight text-[11px] whitespace-nowrap">
                            {{ $h->created_at ? $h->created_at->format('d M Y,') : '-' }}
                        </div>
                        <div class="text-[10px] text-slate-400 font-semibold mt-0.5 whitespace-nowrap">
                            {{ $h->created_at ? $h->created_at->format('H:i') . ' WIB' : '' }}
                        </div>
                    </div>

                    {{-- 3. Tenant / Perusahaan --}}
                    <div class="min-w-0 text-left">
                        @if($h->registration)
                            <a href="{{ route('platform.registrations.show', $h->registration->id) }}" class="font-extrabold text-[#8E1B5C] hover:underline block truncate text-xs leading-tight">
                                {{ $h->registration->company_name }}
                            </a>
                            <span class="text-[10px] text-slate-400 block truncate mt-0.5" data-i18n="audit_tenant_reg">Pendaftaran Tenant</span>
                        @else
                            <span class="font-extrabold text-slate-800 block truncate text-xs leading-tight">Platform Global</span>
                            <span class="text-[10px] text-slate-400 block truncate mt-0.5" data-i18n="audit_system_internal">Sistem Internal</span>
                        @endif
                    </div>

                    {{-- 4. Aksi & Deskripsi --}}
                    <div class="min-w-0 text-left">
                        <div class="font-extrabold text-[#172B4D] leading-tight text-xs truncate" data-i18n="audit_act_{{ strtolower($actionName) }}">
                            {{ $h->actionLabel() }}
                        </div>
                        <div class="text-[10px] text-slate-400 mt-0.5 truncate" data-i18n="{{ $actionName === 'SUBMIT' ? 'audit_action_submit_desc' : 'audit_action_status_desc' }}">
                            {{ $actionName === 'SUBMIT' ? 'Tenant baru mendaftar.' : 'Perubahan status dicatat.' }}
                        </div>
                    </div>

                    {{-- 5. Status Transition Pill --}}
                    <div class="text-center">
                        <div class="inline-flex items-center gap-1 font-mono text-[9.5px]">
                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-extrabold border border-slate-200" data-i18n="audit_pill_{{ strtolower($h->from_status ?: 'baru') }}">
                                {{ $h->from_status ?: 'BARU' }}
                            </span>
                            <svg class="w-2.5 h-2.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                            <span class="px-1.5 py-0.5 rounded font-extrabold border {{ $statusBadgeClass }}" data-i18n="audit_pill_{{ strtolower($statusTo) }}">
                                {{ $statusTo }}
                            </span>
                        </div>
                    </div>

                    {{-- 6. Aktor --}}
                    <div class="min-w-0 text-left">
                        <div class="font-extrabold text-slate-800 flex items-center gap-1 truncate text-[11.5px]">
                            <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="truncate">{{ $h->actor ? $h->actor->name : ($h->registration?->owner_name ?? 'Sistem / Publik') }}</span>
                        </div>
                        <div class="text-[9.5px] text-slate-400 truncate mt-0.5">
                            ({{ $h->actor ? $h->actor->email : ($h->registration?->email ?? 'public') }})
                        </div>
                    </div>

                    {{-- 7. Catatan / Alasan (Lengkap Tanpa Terpotong - Fix Gambar 2) --}}
                    <div class="min-w-0 text-xs text-slate-600 leading-relaxed text-left break-words">
                        {{ $h->notes ?? '-' }}
                    </div>

                </div>
            @empty
                <div class="p-12 text-center text-slate-400 bg-white rounded-2xl border border-[#F0E4EA]">
                    <div class="w-12 h-12 rounded-full bg-slate-50 text-slate-400 border border-slate-200 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <p class="font-bold text-[#172B4D] text-sm" data-i18n="audit_empty_title">Belum Ada Riwayat Aktivitas</p>
                    <p class="text-xs text-slate-400 mt-1" data-i18n="audit_empty_desc">Tidak ada riwayat aktivitas yang sesuai dengan filter pencarian.</p>
                </div>
            @endforelse
            </div>
        </div>

        @if($histories->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $histories->links() }}
            </div>
        @endif
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Focus Search Shortcut
    const btnFocusSearch = document.getElementById('btnFocusSearch');
    const auditSearchInput = document.getElementById('auditSearchInput');
    if (btnFocusSearch && auditSearchInput) {
        btnFocusSearch.addEventListener('click', function() {
            auditSearchInput.focus();
            auditSearchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    // 2. Client-side Category Filter Tabs
    const catButtons = document.querySelectorAll('.audit-cat-btn');
    const tableRows = document.querySelectorAll('.audit-log-row');

    catButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active styling
            catButtons.forEach(b => {
                b.className = 'audit-cat-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white border border-[#E1CFD9] text-slate-700 hover:text-[#8E1B5C] cursor-pointer';
            });
            this.className = 'audit-cat-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-[#8E1B5C] text-white shadow-2xs cursor-pointer';

            const selectedCat = this.getAttribute('data-category');
            tableRows.forEach(row => {
                const rowCat = row.getAttribute('data-category');
                if (selectedCat === 'ALL' || rowCat === selectedCat) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // 3. Interactive 3D Tilt Motion (Identik Dashboard, Interaktif dengan Kursor Mouse)
    const auditHeroWrap = document.getElementById('auditHero3dWrap');
    const audit3dCard = document.getElementById('audit3dCard');
    if (auditHeroWrap && audit3dCard) {
        auditHeroWrap.addEventListener('mousemove', function(e) {
            const rect = auditHeroWrap.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            const tiltX = -(y / (rect.height / 2)) * 12;
            const tiltY = (x / (rect.width / 2)) * 12;
            audit3dCard.style.transform = `rotateY(${tiltY - 11}deg) rotateX(${tiltX + 6}deg) scale(1.03)`;
        });
        auditHeroWrap.addEventListener('mouseleave', function() {
            audit3dCard.style.transform = 'rotateY(-13deg) rotateX(7deg) rotateZ(-1deg) scale(1)';
        });
    }
});
</script>
@endsection
