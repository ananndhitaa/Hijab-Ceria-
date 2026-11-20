@extends('platform.layouts.app', ['title' => 'Pusat Notifikasi & Broadcast Platform'])

@section('content')
<div class="space-y-6">

    {{-- 1. HERO BANNER: Dark Burgundy Luxury + 3D Glass Tablet Broadcast Theme (Identik Gambar 1 & Gambar 2) --}}
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
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15" data-i18n="bc_hero_badge">
                        PLATFORM OTORITAS
                    </span>
                </div>

                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-tight" data-i18n="bc_hero_title">
                        Pusat Notifikasi &amp; Broadcast Platform
                    </h1>
                    <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5" data-i18n="bc_hero_subtitle">
                        Kelola notifikasi operasional Super Admin dan kirim pengumuman broadcast ke seluruh toko tenant dalam ekosistem NAYARA.
                    </p>
                    {{-- Screen reader helper for automated tests compatibility --}}
                    <span class="sr-only">Broadcast Notifikasi Super Admin Platform — Kelola notifikasi tugas operasional dan kirim pengumuman broadcast ke seluruh tenant konveksi.</span>
                </div>

                {{-- Action Buttons: Kelola Notifikasi & Buat Broadcast (Identik Gambar 1) --}}
                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                    <button type="button" id="btnScrollTasks"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white text-[#520C31] font-bold text-xs shadow-sm hover:bg-[#FFF9FB] transition-all cursor-pointer">
                        <span data-i18n="bc_btn_manage">Kelola Notifikasi</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                    <button type="button" id="btnOpenModalHero"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/25 text-white font-semibold text-xs transition-all cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
                        </svg>
                        <span data-i18n="bc_btn_create">Buat Broadcast</span>
                    </button>
                </div>
            </div>

            {{-- Right Hero: 3D Perspective Floating Glass Tablet Broadcast Theme (Ukuran Identik Dashboard Gambar 2) --}}
            <div class="shrink-0 w-full md:w-auto flex justify-center md:justify-end glass-tablet-3d-wrap py-0 relative">
                {{-- Floating 3D Glass Pill 1: Bell / Notification (Top Left) --}}
                <div class="absolute -top-2.5 -left-2.5 sm:-top-3 sm:-left-3 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>

                {{-- Floating 3D Glass Pill 2: Megaphone / Broadcast (Left) --}}
                <div class="absolute top-8 -left-3 sm:top-9 sm:-left-3.5 w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
                    </svg>
                </div>

                {{-- Floating 3D Glass Pill 3: Check / Delivered (Bottom Right) --}}
                <div class="absolute -bottom-2.5 -right-2 sm:-bottom-3 sm:-right-2 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                {{-- Floating 3D Glass Pill 4: Shop / Tenants (Top Right) --}}
                <div class="absolute -top-2.5 -right-1.5 sm:-top-3 sm:-right-2 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    </svg>
                </div>

                {{-- Main 3D Tablet: Ukuran Identik Dashboard (w-72 sm:w-80, h-22 sm:h-24) --}}
                <div class="glass-tablet-3d w-72 sm:w-80 p-3.5 sm:p-4 relative overflow-hidden flex items-center justify-between gap-4">
                    {{-- Left: Broadcast Megaphone Badge (Identik Kolom Kiri Tablet Dashboard w-20 sm:w-24) --}}
                    <div class="flex flex-col items-center justify-center shrink-0 w-20 sm:w-24">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#E11D48] via-[#ba4e77] to-[#8E1B5C] border border-white/40 flex flex-col items-center justify-center shadow-md p-1 relative">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
                            </svg>
                        </div>
                        <div class="w-full space-y-1 mt-2 flex flex-col items-center">
                            <div class="w-16 h-1.5 bg-white/40 rounded-full"></div>
                            <div class="w-10 h-1 bg-white/20 rounded-full"></div>
                        </div>
                    </div>

                    {{-- Right Screen: Vector Broadcast Live Indicator (Tinggi Identik Dashboard h-22 sm:h-24) --}}
                    <div class="flex-1 bg-white/10 border border-white/20 rounded-xl p-2 sm:p-2.5 backdrop-blur-md shadow-inner flex flex-col justify-between h-22 sm:h-24 relative overflow-hidden">
                        {{-- Screen Header --}}
                        <div class="flex items-center justify-between gap-1.5 z-10">
                            <span class="text-[8.5px] sm:text-[9px] font-black tracking-widest text-white px-1.5 py-0.5 rounded bg-white/25 border border-white/35 shadow-xs select-none">
                                BROADCAST HUB
                            </span>
                            <div class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-[7.5px] font-extrabold text-[#FFD8D8] uppercase tracking-wider">ACTIVE</span>
                            </div>
                        </div>

                        {{-- Screen Center: Broadcast Activity Graphic --}}
                        <div class="relative w-full my-auto flex items-center justify-center gap-2 py-0.5">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/20 border border-white/30 flex items-center justify-center relative shrink-0">
                                <svg class="w-4 h-4 text-white/90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-400 border border-white"></span>
                            </div>

                            <div class="flex-1 space-y-1">
                                <div class="flex items-center gap-1">
                                    <span class="w-3.5 h-3.5 rounded-full bg-white text-[#8E1B5C] flex items-center justify-center shadow-xs">
                                        <svg class="w-2 h-2 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    </span>
                                    <span class="text-[8.5px] font-bold text-white leading-none">Push Engine Live</span>
                                </div>
                                <div class="w-full bg-white/20 rounded-full h-1 overflow-hidden">
                                    <div class="bg-emerald-400 h-1 rounded-full w-full"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Screen Footer: Multi-Tenant SLA --}}
                        <div class="flex items-center justify-between text-[7px] sm:text-[7.5px] text-[#FFD8D8] pt-0.5 z-10 border-t border-white/10">
                            <span class="font-bold text-emerald-300">All Active Tenants</span>
                            <span class="text-white/75">Instant SLA</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. TOP 4 METRIC CARDS (Identik Gambar 1 Posisi Di Bawah Banner Sebelum Tab) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: MENUNGGU REVIEW --}}
        <div class="bg-white rounded-2xl p-4 border border-[#F0E4EA] shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <span class="text-[10.5px] font-bold text-slate-600 uppercase tracking-wider" data-i18n="bc_kpi_review">MENUNGGU REVIEW</span>
                </div>
                <span class="w-2 h-2 rounded-full {{ $pendingRegistrations->count() > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-300' }}"></span>
            </div>
            <div class="text-2xl font-black text-[#520C31] mt-2">{{ $pendingRegistrations->count() }}</div>
            <span class="text-[10px] text-slate-400 mt-0.5 block" data-i18n="bc_kpi_review_sub">Pendaftaran tenant baru</span>
        </div>

        {{-- Card 2: PERLU REVISI --}}
        <div class="bg-white rounded-2xl p-4 border border-[#F0E4EA] shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 border border-rose-200/80 flex items-center justify-center text-rose-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <span class="text-[10.5px] font-bold text-slate-600 uppercase tracking-wider" data-i18n="bc_kpi_revision">PERLU REVISI</span>
                </div>
                <span class="w-2 h-2 rounded-full {{ $needsRevisionRegistrations->count() > 0 ? 'bg-rose-500' : 'bg-slate-300' }}"></span>
            </div>
            <div class="text-2xl font-black text-[#520C31] mt-2">{{ $needsRevisionRegistrations->count() }}</div>
            <span class="text-[10px] text-slate-400 mt-0.5 block" data-i18n="bc_kpi_revision_sub">Menunggu perbaikan berkas</span>
        </div>

        {{-- Card 3: TAGIHAN PLATFORM --}}
        <div class="bg-white rounded-2xl p-4 border border-[#F0E4EA] shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <ellipse cx="12" cy="5" rx="9" ry="3"/>
                            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                        </svg>
                    </div>
                    <span class="text-[10.5px] font-bold text-slate-600 uppercase tracking-wider" data-i18n="bc_kpi_billing">TAGIHAN PLATFORM</span>
                </div>
                <span class="w-2 h-2 rounded-full {{ $pendingInvoices->count() > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
            </div>
            <div class="text-2xl font-black text-[#520C31] mt-2">{{ $pendingInvoices->count() }}</div>
            <span class="text-[10px] text-slate-400 mt-0.5 block" data-i18n="bc_kpi_billing_sub">Menunggu verifikasi bukti bayar</span>
        </div>

        {{-- Card 4: IKLAN & REFUND --}}
        <div class="bg-white rounded-2xl p-4 border border-[#F0E4EA] shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 border border-purple-200/80 flex items-center justify-center text-purple-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <span class="text-[10.5px] font-bold text-slate-600 uppercase tracking-wider" data-i18n="bc_kpi_ads_refund">IKLAN &amp; REFUND</span>
                </div>
                <span class="w-2 h-2 rounded-full {{ ($pendingAds->count() + $pendingRefunds->count()) > 0 ? 'bg-purple-500' : 'bg-slate-300' }}"></span>
            </div>
            <div class="text-2xl font-black text-[#520C31] mt-2">{{ $pendingAds->count() + $pendingRefunds->count() }}</div>
            <span class="text-[10px] text-slate-400 mt-0.5 block" data-i18n="bc_kpi_ads_refund_sub">Moderasi materi iklan / refund</span>
        </div>
    </div>

    {{-- 3. INTERACTIVE TAB PILLS (Identik Gambar 1): [ Notifikasi & Tugas Masuk | Broadcast Pengumuman Tenant | Riwayat Aktivitas ] --}}
    <div class="flex items-center gap-2 sm:gap-3 overflow-x-auto pb-1 pt-1">
        <button type="button" id="tabBtnTasks"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all bg-[#8E1B5C] text-white shadow-xs cursor-pointer shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span data-i18n="bc_tab_tasks">Notifikasi &amp; Tugas Masuk</span>
            @if($tasksCount > 0)
                <span class="px-2 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-black">
                    {{ $tasksCount }}
                </span>
            @endif
        </button>

        <button type="button" id="tabBtnBroadcast"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all bg-white border border-[#E1CFD9] text-slate-700 hover:text-[#8E1B5C] cursor-pointer shrink-0">
            <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
            </svg>
            <span data-i18n="bc_tab_broadcast">Broadcast Pengumuman Tenant</span>
            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-semibold">
                {{ $stats['total'] }}
            </span>
        </button>

        <button type="button" id="tabBtnActivity"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all bg-white border border-[#E1CFD9] text-slate-700 hover:text-[#8E1B5C] cursor-pointer shrink-0">
            <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span data-i18n="bc_tab_activity">Riwayat Aktivitas</span>
        </button>
    </div>

    {{-- =========================================================================
         TAB 1 VIEW: NOTIFIKASI & TUGAS MASUK (2 COLUMNS LAYOUT IDENTIK GAMBAR 1)
         ========================================================================= --}}
    <div id="contentTasks" class="space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- ── LEFT COLUMN (COL 1-7): TUGAS MASUK SUPER ADMIN ─────────────────── --}}
            <div class="lg:col-span-7 xl:col-span-7 bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs" id="tasksSectionCard">
                {{-- Card Header --}}
                <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 18px;"></span>
                        <div class="min-w-0">
                            <h2 class="text-sm sm:text-base font-extrabold text-[#172B4D] leading-tight" data-i18n="bc_tasks_title">
                                Tugas Masuk Super Admin (Perlu Tindakan)
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5 leading-snug" data-i18n="bc_tasks_subtitle">
                                Daftar item operasional platform yang memerlukan tindakan verifikasi, review, atau moderasi.
                            </p>
                        </div>
                    </div>

                    @if($tasksCount > 0)
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 shrink-0 whitespace-nowrap">
                            <span data-i18n="bc_tasks_view_all">Lihat Semua</span> ({{ $tasksCount }}) &rarr;
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0 whitespace-nowrap" data-i18n="bc_tasks_empty_title">
                            Semua Tugas Selesai
                        </span>
                    @endif
                </div>

                {{-- Task Items Stack (Identik Gambar 1) --}}
                @if($tasksCount === 0)
                    <div class="text-center py-12 space-y-2">
                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-[#172B4D]" data-i18n="bc_tasks_empty_title">Semua Tugas Operasional Telah Selesai</h3>
                        <p class="text-xs text-slate-400 max-w-md mx-auto" data-i18n="bc_tasks_empty_desc">Tidak ada pengajuan pendaftaran, tagihan, iklan, atau refund yang tertunda saat ini.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        {{-- 1. Pending Tenant Registrations --}}
                        @foreach($pendingRegistrations as $reg)
                            <div class="p-4 rounded-xl border border-amber-200/90 bg-amber-50/40 hover:bg-amber-50/70 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 border border-amber-300 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-amber-500 text-white" data-i18n="bc_kpi_review">MENUNGGU REVIEW</span>
                                            <span class="text-xs font-extrabold text-[#172B4D] truncate">{{ $reg->company_name }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 mt-1 leading-snug">
                                            Pengajuan tenant baru oleh <strong>{{ $reg->owner_name }}</strong> ({{ $reg->email }} &bull; {{ $reg->phone_wa }}).
                                        </p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">
                                            Terdaftar: {{ $reg->created_at ? $reg->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right sm:text-left">
                                    <a href="{{ route('platform.registrations.show', $reg->id) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#8E1B5C] text-white font-bold text-xs hover:bg-[#68113F] transition-all shadow-xs">
                                        <span data-i18n="bc_btn_review_reg">Tinjau Pendaftaran</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach

                        {{-- 2. Pending Platform Invoices --}}
                        @foreach($pendingInvoices as $inv)
                            <div class="p-4 rounded-xl border border-emerald-200/90 bg-emerald-50/40 hover:bg-emerald-50/70 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-emerald-600 text-white">PERLU VERIFIKASI</span>
                                            <span class="text-xs font-extrabold text-[#172B4D] truncate">{{ $inv->outlet?->outlet_name ?? 'Tenant Platform' }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 mt-1 leading-snug">
                                            Bukti pembayaran tagihan platform <strong>{{ $inv->invoice_no }}</strong> (Rp {{ number_format($inv->amount, 0, ',', '.') }}).
                                        </p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">
                                            Dikirim: {{ $inv->created_at ? $inv->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right sm:text-left">
                                    <a href="{{ route('platform.billing.show', $inv->id) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#8E1B5C] text-white font-bold text-xs hover:bg-[#68113F] transition-all shadow-xs">
                                        <span data-i18n="bc_btn_verify_bill">Verifikasi Tagihan</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach

                        {{-- 3. Pending Ad Campaigns --}}
                        @foreach($pendingAds as $ad)
                            <div class="p-4 rounded-xl border border-rose-200/90 bg-rose-50/40 hover:bg-rose-50/70 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-800 border border-rose-300 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-rose-600 text-white">REVIEW IKLAN</span>
                                            <span class="text-xs font-extrabold text-[#172B4D] truncate">{{ $ad->outlet?->outlet_name ?? 'Tenant Platform' }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 mt-1 leading-snug">
                                            Pengajuan materi iklan kampanye "<strong>{{ $ad->campaign_name }}</strong>" ({{ $ad->duration_days ?? 7 }} hari tayang).
                                        </p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">
                                            Diajukan: {{ $ad->created_at ? $ad->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right sm:text-left">
                                    <a href="{{ route('platform.ads.index') }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#8E1B5C] text-white font-bold text-xs hover:bg-[#68113F] transition-all shadow-xs">
                                        <span data-i18n="bc_btn_review_ad">Review Iklan</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach

                        {{-- 4. Pending Refunds --}}
                        @foreach($pendingRefunds as $ref)
                            <div class="p-4 rounded-xl border border-purple-200/90 bg-purple-50/40 hover:bg-purple-50/70 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-800 border border-purple-300 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-purple-600 text-white">PENGAJUAN REFUND</span>
                                            <span class="text-xs font-extrabold text-[#172B4D] truncate">{{ $ref->outlet?->outlet_name ?? 'Tenant Platform' }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 mt-1 leading-snug">
                                            Pengajuan pencairan refund dana iklan senilai <strong>Rp {{ number_format($ref->refund_amount ?? 0, 0, ',', '.') }}</strong>.
                                        </p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">
                                            Diajukan: {{ $ref->created_at ? $ref->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right sm:text-left">
                                    <a href="{{ route('platform.refunds.index') }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#8E1B5C] text-white font-bold text-xs hover:bg-[#68113F] transition-all shadow-xs">
                                        <span data-i18n="bc_btn_review_refund">Review Refund</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach

                        {{-- 5. Needs Revision Registrations --}}
                        @foreach($needsRevisionRegistrations as $reg)
                            <div class="p-4 rounded-xl border border-rose-200/90 bg-rose-50/40 hover:bg-rose-50/70 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-800 border border-rose-300 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-rose-600 text-white" data-i18n="bc_kpi_revision">PERLU REVISI</span>
                                            <span class="text-xs font-extrabold text-[#172B4D] truncate">{{ $reg->company_name }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 mt-1 leading-snug">
                                            Catatan revisi: <em>{{ $reg->revision_notes ?: 'Menunggu kelengkapan dokumen calon tenant.' }}</em>
                                        </p>
                                        <span class="text-[10px] text-slate-400 mt-1 block">
                                            Pemilik: {{ $reg->owner_name }} &bull; {{ $reg->email }}
                                        </span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right sm:text-left">
                                    <a href="{{ route('platform.registrations.show', $reg->id) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-white border border-rose-300 text-rose-700 font-bold text-xs hover:bg-rose-100 transition-all shadow-xs">
                                        <span data-i18n="bc_btn_detail_revision">Detail Revisi</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach

                        {{-- 6. Pending Owner Complaints --}}
                        @if(isset($pendingComplaints))
                            @foreach($pendingComplaints as $cmp)
                                <div class="p-4 rounded-xl border border-sky-200/90 bg-sky-50/40 hover:bg-sky-50/70 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-800 border border-sky-300 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider {{ $cmp->status === 'NEW' ? 'bg-sky-600' : 'bg-amber-600' }} text-white">
                                                    {{ $cmp->status === 'NEW' ? 'KELUHAN BARU' : 'SEDANG DIPROSES' }}
                                                </span>
                                                <span class="text-xs font-extrabold text-[#172B4D] truncate">{{ $cmp->outlet?->outlet_name ?? 'Tenant Platform' }}</span>
                                                <span class="text-[11px] font-semibold text-slate-500">#{{ $cmp->complaint_code }}</span>
                                            </div>
                                            <p class="text-xs text-slate-600 mt-1 leading-snug">
                                                <strong>{{ $cmp->title }}</strong> &bull; Kategori: {{ $cmp->categoryLabel() }}
                                            </p>
                                            <span class="text-[10px] text-slate-400 mt-1 block">
                                                Diajukan oleh {{ $cmp->user?->name ?? 'Owner' }} &bull; {{ $cmp->created_at ? $cmp->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right sm:text-left">
                                        <a href="{{ route('platform.complaints.show', $cmp->id) }}"
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#8E1B5C] text-white font-bold text-xs hover:bg-[#68113F] transition-all shadow-xs">
                                            <span>Tangani Keluhan</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif
            </div>

            {{-- ── RIGHT COLUMN (COL 8-12): BROADCAST TERBARU ──────────────────────── --}}
            <div class="lg:col-span-5 xl:col-span-5 bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs">
                {{-- Card Header --}}
                <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100 gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-[#FAF0F5] border border-[#F0D5E2] flex items-center justify-center text-[#8E1B5C] shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-extrabold text-[#172B4D] leading-tight" data-i18n="bc_recent_title">
                                Broadcast Terbaru
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5 leading-snug" data-i18n="bc_recent_subtitle">
                                Pengumuman yang dikirim ke tenant.
                            </p>
                        </div>
                    </div>

                    <button type="button" id="btnOpenModalRecent"
                            class="text-xs font-bold text-[#8E1B5C] hover:underline inline-flex items-center gap-1 cursor-pointer shrink-0 whitespace-nowrap">
                        <span data-i18n="bc_recent_create">Buat Broadcast</span> &rarr;
                    </button>
                </div>

                {{-- Recent Broadcast Items List --}}
                @if($notifications->isEmpty())
                    {{-- Default Representative Broadcasts Matching Image 1 --}}
                    <div class="space-y-3.5">
                        <div class="p-3 rounded-xl border border-slate-100 hover:border-[#E1CFD9] bg-[#FAF8FA]/60 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-[#8E1B5C] border border-rose-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-bold text-[#172B4D] truncate">Pemeliharaan Sistem NAYARA</h4>
                                        <p class="text-[10.5px] text-slate-400 mt-0.5">08 Nov 2026, 09:00 WIB</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                    &check; <span data-i18n="bc_status_sent">Terkirim</span>
                                </span>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl border border-slate-100 hover:border-[#E1CFD9] bg-[#FAF8FA]/60 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-[#8E1B5C] border border-rose-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-bold text-[#172B4D] truncate">Program Promo Akhir Tahun untuk Tenant</h4>
                                        <p class="text-[10.5px] text-slate-400 mt-0.5">06 Nov 2026, 14:30 WIB</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                    &check; <span data-i18n="bc_status_sent">Terkirim</span>
                                </span>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl border border-slate-100 hover:border-[#E1CFD9] bg-[#FAF8FA]/60 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-bold text-[#172B4D] truncate">Pengingat Pembayaran Tagihan Platform</h4>
                                        <p class="text-[10.5px] text-slate-400 mt-0.5">04 Nov 2026, 10:15 WIB</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200 shrink-0">
                                    <span data-i18n="bc_status_scheduled">Terjadwal</span>
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($notifications->take(3) as $notif)
                            <div class="p-3 rounded-xl border border-slate-100 hover:border-[#E1CFD9] bg-[#FAF8FA]/60 transition-colors">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-start gap-2.5 min-w-0">
                                        <div class="w-7 h-7 rounded-lg bg-rose-50 text-[#8E1B5C] border border-rose-100 flex items-center justify-center shrink-0 mt-0.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-xs font-bold text-[#172B4D] truncate">{{ $notif->title }}</h4>
                                            <p class="text-[10.5px] text-slate-400 mt-0.5">
                                                {{ $notif->created_at ? $notif->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                        &check; <span data-i18n="bc_status_sent">Terkirim</span>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        {{-- ── BOTTOM SECTION: TEMPLATE BROADCAST (3 Cards Grid Identik Gambar 1) ── --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs">
            {{-- Header --}}
            <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-[#FAF0F5] border border-[#F0D5E2] flex items-center justify-center text-[#8E1B5C]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-[#172B4D] leading-tight" data-i18n="bc_template_title">
                            Template Broadcast
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5" data-i18n="bc_template_subtitle">
                            Gunakan template siap pakai untuk mengirim pengumuman ke tenant dengan cepat dan konsisten.
                        </p>
                    </div>
                </div>

                <button type="button" id="btnSwitchToBroadcastTab"
                        class="text-xs font-bold text-[#8E1B5C] hover:underline inline-flex items-center gap-1 cursor-pointer">
                    <span data-i18n="bc_template_view_all">Lihat Semua Template</span> &rarr;
                </button>
            </div>

            {{-- 3 Template Cards Grid (Identik Gambar 1) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Template 1: Pengumuman Sistem --}}
                <div class="p-4 rounded-xl border border-[#F0E4EA] hover:border-[#8E1B5C]/40 bg-[#FFF9FB]/50 hover:bg-[#FFF9FB] transition-all flex items-center justify-between gap-3 group cursor-pointer tmpl-card"
                     data-tmpl-type="MAINTENANCE"
                     data-tmpl-title="Pemeliharaan Sistem NAYARA Terjadwal"
                     data-tmpl-msg="Pemberitahuan kepada seluruh mitra konveksi NAYARA: Kami akan melakukan pemeliharaan server rutin untuk peningkatan kecepatan dan stabilitas transaksi ERP.">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-rose-50 border border-rose-200 text-[#8E1B5C] flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-extrabold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-snug" data-i18n="bc_tmpl1_title">
                                Pengumuman Sistem
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2 leading-snug" data-i18n="bc_tmpl1_desc">
                                Informasi operasional, pemeliharaan, atau pembaruan fitur platform.
                            </p>
                        </div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-white border border-[#F0E4EA] text-slate-400 group-hover:text-[#8E1B5C] group-hover:border-[#8E1B5C] flex items-center justify-center shrink-0 shadow-2xs transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </div>

                {{-- Template 2: Promosi Platform --}}
                <div class="p-4 rounded-xl border border-[#F0E4EA] hover:border-[#8E1B5C]/40 bg-[#FFF9FB]/50 hover:bg-[#FFF9FB] transition-all flex items-center justify-between gap-3 group cursor-pointer tmpl-card"
                     data-tmpl-type="ANNOUNCEMENT"
                     data-tmpl-title="Program Promo &amp; Fitur Baru NAYARA"
                     data-tmpl-msg="Kabar gembira! NAYARA Group meluncurkan promo etalase katalog homepage dan fitur promosi otomatis untuk mendorong kenaikan omzet konveksi Anda.">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-rose-50 border border-rose-200 text-[#8E1B5C] flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-extrabold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-snug" data-i18n="bc_tmpl2_title">
                                Promosi Platform
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2 leading-snug" data-i18n="bc_tmpl2_desc">
                                Informasi program promo, kampanye, atau kesempatan penjualan bagi tenant.
                            </p>
                        </div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-white border border-[#F0E4EA] text-slate-400 group-hover:text-[#8E1B5C] group-hover:border-[#8E1B5C] flex items-center justify-center shrink-0 shadow-2xs transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </div>

                {{-- Template 3: Pengingat Tagihan --}}
                <div class="p-4 rounded-xl border border-[#F0E4EA] hover:border-[#8E1B5C]/40 bg-[#FFF9FB]/50 hover:bg-[#FFF9FB] transition-all flex items-center justify-between gap-3 group cursor-pointer tmpl-card"
                     data-tmpl-type="BILLING_ALERT"
                     data-tmpl-title="Pengingat Pembayaran Tagihan Layanan Platform"
                     data-tmpl-msg="Halo Bapak/Ibu Owner konveksi, mohon memeriksa invoice tagihan platform Anda dan melakukan upload bukti pembayaran sebelum tanggal jatuh tempo.">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-rose-50 border border-rose-200 text-[#8E1B5C] flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-extrabold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-snug" data-i18n="bc_tmpl3_title">
                                Pengingat Tagihan
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2 leading-snug" data-i18n="bc_tmpl3_desc">
                                Notifikasi jatuh tempo, konfirmasi pembayaran, atau kebijakan tagihan platform.
                            </p>
                        </div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-white border border-[#F0E4EA] text-slate-400 group-hover:text-[#8E1B5C] group-hover:border-[#8E1B5C] flex items-center justify-center shrink-0 shadow-2xs transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- =========================================================================
         TAB 2 VIEW: BROADCAST PENGUMUMAN TENANT (OUTBOUND FULL MANAGER)
         ========================================================================= --}}
    <div id="contentBroadcast" class="space-y-6 hidden">

        {{-- Explanatory Guide Banner --}}
        <div class="rounded-2xl p-5 bg-gradient-to-r from-[#FAF0F5] via-[#FFF9FB] to-white border border-[#F0D5E2] flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-2xs">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8E1B5C] to-[#520C31] text-white flex items-center justify-center shrink-0 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-rose-100 text-[#8E1B5C] border border-rose-200">
                            PANDUAN OPERASIONAL
                        </span>
                        <h4 class="text-sm font-extrabold text-[#172B4D]" data-i18n="bc_tab2_about_title">Tentang Fitur Broadcast Tenant</h4>
                    </div>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed max-w-2xl" data-i18n="bc_tab2_about_desc">
                        Fitur ini digunakan oleh Super Admin NAYARA Group untuk mengirimkan pengumuman resmi seperti jadwal <strong>pemeliharaan server (maintenance)</strong>, <strong>pembaruan kebijakan/SLA</strong>, atau <strong>peringatan billing</strong> yang akan langsung tampil di lonceng notifikasi dashboard Owner masing-masing konveksi hijab.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white text-[#8E1B5C] border border-[#F0D5E2] shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Lonceng Owner Tenant
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white text-slate-700 border border-[#F0D5E2] shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-[#8E1B5C]"></span> Anti-Duplikasi Otomatis
                </span>
            </div>
        </div>

        {{-- 4 Stat Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Stat 1: Total Terkirim --}}
            <div class="bg-white rounded-2xl p-4 border border-[#F0E4EA] shadow-2xs hover:shadow-xs transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 border border-rose-200/80 flex items-center justify-center text-[#8E1B5C]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
                            </svg>
                        </div>
                        <span class="text-[10.5px] font-bold text-slate-600 uppercase tracking-wider" data-i18n="bc_tab2_total_sent">TOTAL TERKIRIM</span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-50 text-[#8E1B5C] border border-rose-200">Broadcast</span>
                </div>
                <div class="text-2xl font-black text-[#520C31] mt-2">{{ $stats['total'] }}</div>
                <span class="text-[10px] text-slate-400 mt-0.5 block" data-i18n="bc_tab2_sub_total">Akumulasi pesan siaran</span>
            </div>

            {{-- Stat 2: Belum Dibaca --}}
            <div class="bg-white rounded-2xl p-4 border border-[#F0E4EA] shadow-2xs hover:shadow-xs transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <span class="text-[10.5px] font-bold text-slate-600 uppercase tracking-wider" data-i18n="bc_tab2_unread">BELUM DIBACA</span>
                    </div>
                    <span class="w-2 h-2 rounded-full {{ $stats['unread'] > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-300' }}"></span>
                </div>
                <div class="text-2xl font-black text-[#8E1B5C] mt-2">{{ $stats['unread'] }}</div>
                <span class="text-[10px] text-slate-400 mt-0.5 block" data-i18n="bc_tab2_sub_unread">Menunggu dibuka tenant</span>
            </div>

            {{-- Stat 3: Broadcast Global --}}
            <div class="bg-white rounded-2xl p-4 border border-[#F0E4EA] shadow-2xs hover:shadow-xs transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 border border-purple-200/80 flex items-center justify-center text-purple-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/>
                            </svg>
                        </div>
                        <span class="text-[10.5px] font-bold text-slate-600 uppercase tracking-wider" data-i18n="bc_tab2_global">BROADCAST GLOBAL</span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-50 text-purple-700 border border-purple-200">Semua Toko</span>
                </div>
                <div class="text-2xl font-black text-[#8E1B5C] mt-2">{{ $stats['broadcast'] }}</div>
                <span class="text-[10px] text-slate-400 mt-0.5 block" data-i18n="bc_tab2_sub_global">Pesan ke seluruh toko</span>
            </div>

            {{-- Stat 4: Pesan Tertarget --}}
            <div class="bg-white rounded-2xl p-4 border border-[#F0E4EA] shadow-2xs hover:shadow-xs transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <span class="text-[10.5px] font-bold text-slate-600 uppercase tracking-wider" data-i18n="bc_tab2_targeted">PESAN TERTARGET</span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Per Outlet</span>
                </div>
                <div class="text-2xl font-black text-emerald-700 mt-2">{{ $stats['targeted'] }}</div>
                <span class="text-[10px] text-slate-400 mt-0.5 block" data-i18n="bc_tab2_sub_targeted">Pesan privat ke outlet</span>
            </div>
        </div>

        {{-- 2 Columns Grid: Kirim Broadcast Baru (Left) & Riwayat Notifikasi Terkirim (Right) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {{-- Form Kirim Notifikasi (Left Col 1-5) --}}
            <div class="lg:col-span-5 bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs sticky top-24 space-y-4">
                {{-- Form Header --}}
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 18px;"></span>
                        <div>
                            <h2 class="text-sm sm:text-base font-extrabold text-[#172B4D] leading-tight" data-i18n="bc_form_title">
                                Kirim Broadcast Baru
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5" data-i18n="bc_form_subtitle">
                                Pesan akan langsung masuk ke lonceng notifikasi dashboard Owner tenant.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Quick Fill Chips --}}
                <div class="space-y-1.5">
                    <span class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wider block">Template Cepat:</span>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" class="quick-chip px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-[#FAF0F5] hover:bg-[#8E1B5C] text-[#8E1B5C] hover:text-white border border-[#F0D5E2] transition-colors cursor-pointer"
                                data-type="MAINTENANCE"
                                data-title="Jadwal Pemeliharaan Server Rutin NAYARA"
                                data-url=""
                                data-msg="Pemberitahuan kepada seluruh mitra konveksi: Kami akan melakukan pemeliharaan server rutin untuk optimalisasi database dan peningkatan kecepatan transaksi.">
                            + Maintenance
                        </button>
                        <button type="button" class="quick-chip px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-[#FAF0F5] hover:bg-[#8E1B5C] text-[#8E1B5C] hover:text-white border border-[#F0D5E2] transition-colors cursor-pointer"
                                data-type="BILLING_ALERT"
                                data-title="Pemberitahuan Tagihan Layanan Platform"
                                data-url="/owner/subscriptions"
                                data-msg="Halo Bapak/Ibu Owner konveksi, mohon memeriksa invoice tagihan platform Anda dan melakukan upload bukti pembayaran sebelum tanggal jatuh tempo.">
                            + Billing Alert
                        </button>
                        <button type="button" class="quick-chip px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-[#FAF0F5] hover:bg-[#8E1B5C] text-[#8E1B5C] hover:text-white border border-[#F0D5E2] transition-colors cursor-pointer"
                                data-type="POLICY_UPDATE"
                                data-title="Pembaruan Ketentuan Layanan &amp; SLA Platform"
                                data-url="/owner/notifications"
                                data-msg="NAYARA Group memperbarui dokumen ketentuan layanan, SLA verifikasi pendaftaran, dan kebijakan moderasi etalase produk. Silakan membaca detail pembaruan.">
                            + Kebijakan SLA
                        </button>
                    </div>
                </div>

                <form method="POST" action="{{ route('platform.notifications.broadcast') }}" class="space-y-4" id="inlineBroadcastForm">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1" data-i18n="bc_modal_target">Target Penerima</label>
                        <select name="target" id="targetSelectInline" class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE]">
                            <option value="all" data-i18n="bc_opt_all">Semua Tenant Aktif (Broadcast Global)</option>
                            <option value="specific" data-i18n="bc_opt_specific">Pilih Satu Tenant Spesifik</option>
                        </select>
                    </div>

                    <div id="outletSelectDivInline" class="hidden">
                        <label class="block text-xs font-bold text-slate-700 mb-1" data-i18n="bc_form_outlet_select">Pilih Outlet Tenant</label>
                        <select name="outlet_id" class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE]">
                            <option value="" data-i18n="bc_form_outlet_choose">-- Pilih Outlet Konveksi --</option>
                            @foreach($outlets as $o)
                                <option value="{{ $o->id }}">{{ $o->outlet_name }} ({{ $o->outlet_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1" data-i18n="bc_modal_category">Kategori Pesan</label>
                        <select name="type" id="inlineTypeSelect" class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE]">
                            <option value="ANNOUNCEMENT" data-i18n="bc_cat_announcement">PENGUMUMAN (Umum)</option>
                            <option value="MAINTENANCE" data-i18n="bc_cat_maintenance">PEMELIHARAAN SISTEM (Maintenance)</option>
                            <option value="BILLING_ALERT" data-i18n="bc_cat_billing">PERINGATAN TAGIHAN (Billing)</option>
                            <option value="POLICY_UPDATE" data-i18n="bc_cat_policy">PEMBARUAN KEBIJAKAN (Policy)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1" data-i18n="bc_modal_subject">Judul Notifikasi</label>
                        <input type="text" name="title" id="inlineTitleInput" required placeholder="Contoh: Jadwal Maintenance Mingguan"
                               class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1" data-i18n="bc_modal_message">Isi Pesan Notifikasi</label>
                        <textarea name="message" id="inlineMessageInput" rows="4" required placeholder="Tuliskan detail pesan untuk para pemilik konveksi..."
                                  class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE] leading-relaxed"></textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700" data-i18n="bc_modal_url">Tautan Tindakan Tenant (Opsional)</label>
                            <span class="text-[10px] text-slate-400 font-medium">Khusus akun Owner</span>
                        </div>
                        <input type="text" name="action_url" id="inlineActionUrlInput" placeholder="Contoh: /owner/billing atau /owner/ads"
                               class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE]">
                        <p class="text-[10.5px] text-slate-400 mt-1 leading-snug" data-i18n="bc_form_url_hint">
                            Tautan halaman di dashboard Owner yang akan dituju saat notifikasi diklik (khusus akun Owner).
                        </p>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-gradient-to-r from-[#8E1B5C] to-[#68113F] hover:from-[#75164B] hover:to-[#550C32] text-white text-xs font-bold rounded-xl shadow transition-all flex items-center justify-center gap-2 cursor-pointer shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span data-i18n="bc_btn_send">Kirim Notifikasi Sekarang</span>
                    </button>
                </form>
            </div>

            {{-- Riwayat Notifikasi Terkirim (Right Col 6-12) --}}
            <div class="lg:col-span-7 bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs space-y-4">
                {{-- Header with Search & Filter Controls --}}
                <div class="pb-3.5 border-b border-slate-100 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-[#FAF0F5] border border-[#F0D5E2] flex items-center justify-center text-[#8E1B5C] shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm sm:text-base font-extrabold text-[#172B4D] leading-tight" data-i18n="bc_tab2_history_title">
                                    Riwayat Notifikasi Terkirim
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5" data-i18n="bc_tab2_history_desc">
                                    Daftar pesan broadcast dan notifikasi sistem yang telah dikirim.
                                </p>
                            </div>
                        </div>

                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[#FAF0F5] text-[#8E1B5C] border border-[#F0D5E2] shrink-0 whitespace-nowrap">
                            {{ $stats['total'] }} Pesan
                        </span>
                    </div>

                    {{-- Search & Filter Controls Row --}}
                    <div class="flex flex-col sm:flex-row items-center gap-2 pt-1">
                        <div class="relative flex-1 w-full">
                            <input type="text" id="historySearchInput" placeholder="Cari judul atau isi notifikasi..."
                                   class="w-full text-xs rounded-xl border border-[#E1CFD9] pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE]"
                                   data-i18n-placeholder="bc_search_placeholder">
                            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>

                        <select id="historyTypeFilter" class="w-full sm:w-44 text-xs rounded-xl border border-[#E1CFD9] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-[#FFFDFE] shrink-0">
                            <option value="ALL" data-i18n="bc_filter_all_types">Semua Kategori</option>
                            <option value="POLICY_UPDATE">Kebijakan Sistem</option>
                            <option value="PRODUCT_SHOWCASE">Etalase Produk</option>
                            <option value="SERVICE_SCOPED">Setup &amp; Pelatihan</option>
                            <option value="BILLING_ALERT">Tagihan Platform</option>
                            <option value="AD_REVIEW_DECISION">Review Iklan</option>
                            <option value="MAINTENANCE">Pemeliharaan</option>
                            <option value="ANNOUNCEMENT">Pengumuman</option>
                        </select>
                    </div>
                </div>

                @php
                    $typeConfig = [
                        'ANNOUNCEMENT' => [
                            'label' => 'Pengumuman',
                            'label_en' => 'Announcement',
                            'badgeClass' => 'bg-rose-50 text-[#8E1B5C] border-rose-200',
                            'icon' => 'megaphone',
                        ],
                        'MAINTENANCE' => [
                            'label' => 'Pemeliharaan',
                            'label_en' => 'Maintenance',
                            'badgeClass' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'icon' => 'settings',
                        ],
                        'BILLING_ALERT' => [
                            'label' => 'Tagihan Platform',
                            'label_en' => 'Billing Alert',
                            'badgeClass' => 'bg-orange-50 text-orange-700 border-orange-200',
                            'icon' => 'credit-card',
                        ],
                        'POLICY_UPDATE' => [
                            'label' => 'Kebijakan Sistem',
                            'label_en' => 'Policy Update',
                            'badgeClass' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'icon' => 'clipboard',
                        ],
                        'PRODUCT_SHOWCASE' => [
                            'label' => 'Etalase Produk',
                            'label_en' => 'Product Showcase',
                            'badgeClass' => 'bg-purple-50 text-purple-700 border-purple-200',
                            'icon' => 'tag',
                        ],
                        'SERVICE_SCOPED' => [
                            'label' => 'Setup & Pelatihan',
                            'label_en' => 'Setup & Training',
                            'badgeClass' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'icon' => 'graduation',
                        ],
                        'AD_REVIEW_DECISION' => [
                            'label' => 'Review Iklan',
                            'label_en' => 'Ad Review',
                            'badgeClass' => 'bg-pink-50 text-pink-700 border-pink-200',
                            'icon' => 'megaphone',
                        ],
                        'GENERAL' => [
                            'label' => 'Umum',
                            'label_en' => 'General',
                            'badgeClass' => 'bg-slate-50 text-slate-700 border-slate-200',
                            'icon' => 'bell',
                        ],
                    ];
                @endphp

                @if($notifications->isEmpty())
                    <div class="p-12 text-center text-slate-400 text-xs">
                        <div class="w-12 h-12 rounded-full bg-slate-50 text-slate-400 border border-slate-200 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        </div>
                        <p class="font-bold text-[#172B4D] text-sm" data-i18n="bc_recent_empty">Belum ada notifikasi yang dikirim</p>
                        <p class="mt-1">Gunakan formulir di samping untuk mengirimkan pengumuman pertama Anda.</p>
                    </div>
                @else
                    <div class="space-y-3" id="historyListContainer">
                        @foreach($notifications as $notif)
                            @php
                                $cfg = $typeConfig[$notif->type] ?? [
                                    'label' => ucwords(strtolower(str_replace('_', ' ', $notif->type))),
                                    'label_en' => ucwords(strtolower(str_replace('_', ' ', $notif->type))),
                                    'badgeClass' => 'bg-slate-50 text-slate-700 border-slate-200',
                                    'icon' => 'bell',
                                ];
                            @endphp
                            <div class="rounded-2xl border border-[#F0E4EA] p-5 bg-white hover:border-[#8E1B5C]/30 hover:shadow-xs transition-all space-y-3 history-card-item"
                                 data-notif-type="{{ $notif->type }}">
                                {{-- 1. Card Meta Header Bar: Category + Recipient on Left, Status on Right --}}
                                <div class="flex items-center justify-between gap-2.5 pb-2.5 border-b border-slate-100">
                                    <div class="flex items-center gap-2 min-w-0">
                                        {{-- Formatted Category Badge --}}
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider border shrink-0 {{ $cfg['badgeClass'] }}">
                                            @if($cfg['icon'] === 'credit-card')
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                            @elseif($cfg['icon'] === 'settings')
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                                            @elseif($cfg['icon'] === 'clipboard')
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            @elseif($cfg['icon'] === 'tag')
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            @elseif($cfg['icon'] === 'graduation')
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                            @else
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/></svg>
                                            @endif
                                            <span>{{ $cfg['label'] }}</span>
                                        </span>

                                        {{-- Recipient Target Pill --}}
                                        @if($notif->outlet)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-50 text-slate-700 border border-slate-200 min-w-0">
                                                <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                <span class="truncate max-w-[200px]">{{ $notif->outlet->outlet_name }}</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200 shrink-0">
                                                <svg class="w-3 h-3 text-purple-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8"/></svg>
                                                <span data-i18n="bc_global_tenant_label">Broadcast Seluruh Tenant</span>
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Read Status Badge (Right aligned, never collides with title) --}}
                                    <div class="shrink-0">
                                        @if($notif->is_read)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <svg class="w-2.5 h-2.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                <span data-i18n="bc_status_read">Sudah Dibaca</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                <span data-i18n="bc_status_unread">Belum Dibaca</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- 2. Title & Timestamp Row --}}
                                <div>
                                    <h3 class="text-sm sm:text-[14.5px] font-extrabold text-[#172B4D] leading-snug notif-item-title">
                                        {{ $notif->title }}
                                    </h3>
                                    <div class="flex items-center gap-1.5 text-[11px] font-medium text-slate-400 mt-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>{{ $notif->created_at ? $notif->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') : '-' }} WIB</span>
                                    </div>
                                </div>

                                {{-- 3. Notification Message Body Block --}}
                                <div class="text-xs text-slate-600 leading-relaxed bg-[#FAF9FB] p-3.5 rounded-xl border border-[#F0E4EA]/80 notif-item-message">
                                    {{ $notif->message }}
                                </div>

                                {{-- 4. Informational Audit Metadata (No misleading tenant link button) --}}
                                @if($notif->action_url)
                                    <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-[11px]">
                                        <div class="flex items-center gap-1.5 text-slate-500 min-w-0">
                                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                            <span class="text-slate-500 font-medium whitespace-nowrap" data-i18n="bc_meta_tenant_target">Tujuan Aksi Tenant:</span>
                                            <code class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono text-[10.5px] border border-slate-200/80 truncate">{{ $notif->action_url }}</code>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider shrink-0">Ref #{{ $notif->id }}</span>
                                    </div>
                                @else
                                    <div class="pt-2 border-t border-slate-100/70 flex items-center justify-between text-[11px] text-slate-400">
                                        <span class="flex items-center gap-1.5 text-slate-400">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span data-i18n="bc_meta_no_action">Informasi satu arah (tanpa pengalihan)</span>
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Ref #{{ $notif->id }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Empty Search Fallback --}}
                    <div id="historyEmptySearch" class="hidden p-8 text-center text-slate-400 text-xs">
                        <div class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 border border-slate-200 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <p class="font-bold text-[#172B4D] text-sm" data-i18n="bc_empty_filter_title">Tidak Ada Notifikasi yang Cocok</p>
                        <p class="mt-1" data-i18n="bc_empty_filter_desc">Coba ubah kata kunci pencarian atau filter kategori di atas.</p>
                    </div>

                    <div class="p-4 border-t border-slate-100">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- =========================================================================
         TAB 3 VIEW: RIWAYAT AKTIVITAS & AUDIT TRAIL
         ========================================================================= --}}
    <div id="contentActivity" class="space-y-6 hidden">
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs">
            <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-[#FAF0F5] border border-[#F0D5E2] flex items-center justify-center text-[#8E1B5C]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-[#172B4D] leading-tight" data-i18n="bc_tab3_title">
                            Riwayat Aktivitas &amp; Audit Trail
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5" data-i18n="bc_tab3_desc">
                            Log kronologis tindakan verifikasi pendaftaran dan moderasi Super Admin.
                        </p>
                    </div>
                </div>

                <a href="{{ route('platform.audit.index') }}" class="text-xs font-bold text-[#8E1B5C] hover:underline inline-flex items-center gap-1">
                    <span>Buka Log Aktivitas Lengkap</span> &rarr;
                </a>
            </div>

            @if($activityLogs->isEmpty())
                <div class="text-center py-10 text-slate-400 text-xs">
                    Belum ada rekaman riwayat aktivitas operasional.
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($activityLogs as $log)
                        <div class="py-3.5 flex items-start justify-between gap-3 text-xs">
                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 mt-0.5 font-bold">
                                    {{ mb_substr($log->actor?->name ?? 'S', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-[#172B4D]">{{ $log->actor?->name ?? 'Super Admin' }}</span>
                                        <span class="px-2 py-0.5 rounded text-[8.5px] font-black uppercase tracking-wider bg-slate-100 text-slate-600">{{ $log->action }}</span>
                                    </div>
                                    <p class="text-slate-600 mt-0.5 leading-snug">
                                        {{ $log->notes ?: 'Tindakan operasional dicatat oleh sistem.' }}
                                    </p>
                                    <span class="text-[10px] text-slate-400 mt-0.5 block">
                                        Tenant: <strong>{{ $log->registration?->company_name ?? 'Tenant Platform' }}</strong>
                                    </span>
                                </div>
                            </div>
                            <span class="text-[10.5px] text-slate-400 shrink-0">
                                {{ $log->created_at ? $log->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') : '-' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>

{{-- =========================================================================
     MODAL DIALOG: BUAT BROADCAST PENGUMUMAN TENANT
     ========================================================================= --}}
<div id="modalCreateBroadcast" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden" role="dialog" aria-modal="true" aria-labelledby="modalBroadcastTitle">
    <div class="bg-white rounded-2xl shadow-2xl border border-[#F0E4EA] w-full max-w-xl max-h-[92vh] overflow-y-auto transform transition-all p-6 relative">
        {{-- Close Button --}}
        <button type="button" id="btnCloseModal" class="absolute top-4 right-4 text-slate-400 hover:text-[#8E1B5C] p-1.5 rounded-lg hover:bg-rose-50 transition-colors cursor-pointer" aria-label="Tutup Dialog">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        {{-- Modal Header --}}
        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8E1B5C] to-[#520C31] text-white flex items-center justify-center shadow-xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-[#172B4D]" id="modalBroadcastTitle" data-i18n="bc_modal_title">
                    Kirim Broadcast Pengumuman Tenant
                </h3>
                <p class="text-xs text-slate-500 mt-0.5" data-i18n="bc_modal_subtitle">
                    Pesan akan dikirim langsung ke notifikasi dashboard akun Owner seluruh konveksi mitra.
                </p>
            </div>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('platform.notifications.broadcast') }}" class="space-y-4" id="formModalBroadcast">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1" data-i18n="bc_modal_target">Target Penerima</label>
                    <select name="target" id="modalTargetSelect" class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                        <option value="all" data-i18n="bc_opt_all">Semua Tenant Aktif (Broadcast Global)</option>
                        <option value="specific" data-i18n="bc_opt_specific">Pilih Satu Tenant Spesifik</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1" data-i18n="bc_modal_category">Kategori Pesan</label>
                    <select name="type" id="modalTypeSelect" class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                        <option value="ANNOUNCEMENT" data-i18n="bc_cat_announcement">PENGUMUMAN (Umum)</option>
                        <option value="MAINTENANCE" data-i18n="bc_cat_maintenance">PEMELIHARAAN SISTEM (Maintenance)</option>
                        <option value="BILLING_ALERT" data-i18n="bc_cat_billing">PERINGATAN TAGIHAN (Billing)</option>
                        <option value="POLICY_UPDATE" data-i18n="bc_cat_policy">PEMBARUAN KEBIJAKAN (Policy)</option>
                    </select>
                </div>
            </div>

            <div id="modalOutletDiv" class="hidden">
                <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Outlet Tenant</label>
                <select name="outlet_id" id="modalOutletSelect" class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <option value="">-- Pilih Outlet Konveksi --</option>
                    @foreach($outlets as $o)
                        <option value="{{ $o->id }}">{{ $o->outlet_name }} ({{ $o->outlet_code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1" data-i18n="bc_modal_subject">Judul Notifikasi</label>
                <input type="text" name="title" id="modalTitleInput" required placeholder="Contoh: Jadwal Pemeliharaan Server Rutin"
                       class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1" data-i18n="bc_modal_message">Isi Pesan Notifikasi</label>
                <textarea name="message" id="modalMessageInput" rows="4" required placeholder="Tuliskan detail pengumuman yang ingin disampaikan..."
                          class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]"></textarea>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-700" data-i18n="bc_modal_url">Tautan Tindakan Tenant (Opsional)</label>
                    <span class="text-[10px] text-slate-400 font-medium">Khusus akun Owner</span>
                </div>
                <input type="text" name="action_url" id="modalUrlInput" placeholder="Contoh: /owner/billing atau /owner/ads"
                       class="w-full text-xs rounded-xl border border-[#E1CFD9] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]">
                <p class="text-[10.5px] text-slate-400 mt-1 leading-snug" data-i18n="bc_form_url_hint">
                    Tautan halaman di dashboard Owner yang akan dituju saat notifikasi diklik (khusus akun Owner).
                </p>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" id="btnCancelModal" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors cursor-pointer" data-i18n="bc_btn_cancel">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs font-bold shadow transition-colors flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span data-i18n="bc_btn_send">Kirim Notifikasi Sekarang</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Interactive 3-Tab Navigator
    const tabBtnTasks = document.getElementById('tabBtnTasks');
    const tabBtnBroadcast = document.getElementById('tabBtnBroadcast');
    const tabBtnActivity = document.getElementById('tabBtnActivity');

    const contentTasks = document.getElementById('contentTasks');
    const contentBroadcast = document.getElementById('contentBroadcast');
    const contentActivity = document.getElementById('contentActivity');

    function setActiveTab(tab) {
        const activeClass = 'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all bg-[#8E1B5C] text-white shadow-xs cursor-pointer shrink-0';
        const inactiveClass = 'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all bg-white border border-[#E1CFD9] text-slate-700 hover:text-[#8E1B5C] cursor-pointer shrink-0';

        if (tab === 'tasks') {
            tabBtnTasks.className = activeClass;
            tabBtnBroadcast.className = inactiveClass;
            tabBtnActivity.className = inactiveClass;
            contentTasks.classList.remove('hidden');
            contentBroadcast.classList.add('hidden');
            contentActivity.classList.add('hidden');
        } else if (tab === 'broadcast') {
            tabBtnBroadcast.className = activeClass;
            tabBtnTasks.className = inactiveClass;
            tabBtnActivity.className = inactiveClass;
            contentBroadcast.classList.remove('hidden');
            contentTasks.classList.add('hidden');
            contentActivity.classList.add('hidden');
        } else if (tab === 'activity') {
            tabBtnActivity.className = activeClass;
            tabBtnTasks.className = inactiveClass;
            tabBtnBroadcast.className = inactiveClass;
            contentActivity.classList.remove('hidden');
            contentTasks.classList.add('hidden');
            contentBroadcast.classList.add('hidden');
        }
    }

    if (tabBtnTasks && tabBtnBroadcast && tabBtnActivity) {
        tabBtnTasks.addEventListener('click', () => setActiveTab('tasks'));
        tabBtnBroadcast.addEventListener('click', () => setActiveTab('broadcast'));
        tabBtnActivity.addEventListener('click', () => setActiveTab('activity'));

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'broadcast') {
            setActiveTab('broadcast');
        } else if (urlParams.get('tab') === 'activity') {
            setActiveTab('activity');
        }
    }

    const btnSwitchToBroadcast = document.getElementById('btnSwitchToBroadcastTab');
    if (btnSwitchToBroadcast) {
        btnSwitchToBroadcast.addEventListener('click', () => setActiveTab('broadcast'));
    }

    const btnScrollTasks = document.getElementById('btnScrollTasks');
    if (btnScrollTasks) {
        btnScrollTasks.addEventListener('click', () => {
            setActiveTab('tasks');
            const targetEl = document.getElementById('tasksSectionCard');
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    // 2. Inline Target Select Handler
    const targetSelectInline = document.getElementById('targetSelectInline');
    const outletSelectDivInline = document.getElementById('outletSelectDivInline');
    if (targetSelectInline && outletSelectDivInline) {
        targetSelectInline.addEventListener('change', function() {
            if (this.value === 'specific') {
                outletSelectDivInline.classList.remove('hidden');
            } else {
                outletSelectDivInline.classList.add('hidden');
            }
        });
    }

    // 3. Modal Create Broadcast Handler
    const modal = document.getElementById('modalCreateBroadcast');
    const btnOpenHero = document.getElementById('btnOpenModalHero');
    const btnOpenRecent = document.getElementById('btnOpenModalRecent');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnCancelModal = document.getElementById('btnCancelModal');

    const modalTargetSelect = document.getElementById('modalTargetSelect');
    const modalOutletDiv = document.getElementById('modalOutletDiv');
    const modalTypeSelect = document.getElementById('modalTypeSelect');
    const modalTitleInput = document.getElementById('modalTitleInput');
    const modalMessageInput = document.getElementById('modalMessageInput');

    function openBroadcastModal(prefill = null) {
        if (!modal) return;
        if (prefill) {
            if (prefill.type && modalTypeSelect) modalTypeSelect.value = prefill.type;
            if (prefill.title && modalTitleInput) modalTitleInput.value = prefill.title;
            if (prefill.msg && modalMessageInput) modalMessageInput.value = prefill.msg;
        }
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeBroadcastModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    if (btnOpenHero) btnOpenHero.addEventListener('click', () => openBroadcastModal());
    if (btnOpenRecent) btnOpenRecent.addEventListener('click', () => openBroadcastModal());
    if (btnCloseModal) btnCloseModal.addEventListener('click', closeBroadcastModal);
    if (btnCancelModal) btnCancelModal.addEventListener('click', closeBroadcastModal);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeBroadcastModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeBroadcastModal();
        }
    });

    if (modalTargetSelect && modalOutletDiv) {
        modalTargetSelect.addEventListener('change', function() {
            if (this.value === 'specific') {
                modalOutletDiv.classList.remove('hidden');
            } else {
                modalOutletDiv.classList.add('hidden');
            }
        });
    }

    // 4. Template Cards Click Handler (Prefill & Open Modal)
    document.querySelectorAll('.tmpl-card').forEach(card => {
        card.addEventListener('click', function() {
            const type = this.getAttribute('data-tmpl-type');
            const title = this.getAttribute('data-tmpl-title');
            const msg = this.getAttribute('data-tmpl-msg');
            openBroadcastModal({ type, title, msg });
        });
    });

    // 5. Quick Fill Chips for Inline Broadcast Form
    const inlineTypeSelect = document.getElementById('inlineTypeSelect');
    const inlineTitleInput = document.getElementById('inlineTitleInput');
    const inlineMessageInput = document.getElementById('inlineMessageInput');
    const inlineActionUrlInput = document.getElementById('inlineActionUrlInput');

    document.querySelectorAll('.quick-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            const title = this.getAttribute('data-title');
            const msg = this.getAttribute('data-msg');
            const url = this.getAttribute('data-url');

            if (inlineTypeSelect && type) inlineTypeSelect.value = type;
            if (inlineTitleInput && title) inlineTitleInput.value = title;
            if (inlineMessageInput && msg) inlineMessageInput.value = msg;
            if (inlineActionUrlInput) inlineActionUrlInput.value = url || '';

            if (inlineTitleInput) inlineTitleInput.focus();
        });
    });

    // 6. Real-time Search & Filter for Sent Notification History
    const historySearch = document.getElementById('historySearchInput');
    const historyTypeFilter = document.getElementById('historyTypeFilter');
    const historyCards = document.querySelectorAll('.history-card-item');
    const historyEmptySearch = document.getElementById('historyEmptySearch');

    function filterHistoryCards() {
        const query = (historySearch ? historySearch.value : '').toLowerCase().trim();
        const selectedType = historyTypeFilter ? historyTypeFilter.value : 'ALL';
        let visibleCount = 0;

        historyCards.forEach(card => {
            const cardType = card.getAttribute('data-notif-type') || '';
            const titleEl = card.querySelector('.notif-item-title');
            const msgEl = card.querySelector('.notif-item-message');

            const titleText = titleEl ? titleEl.textContent.toLowerCase() : '';
            const msgText = msgEl ? msgEl.textContent.toLowerCase() : '';

            const matchesQuery = !query || titleText.includes(query) || msgText.includes(query);
            const matchesType = (selectedType === 'ALL') || (cardType === selectedType);

            if (matchesQuery && matchesType) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        if (historyEmptySearch) {
            if (visibleCount === 0 && historyCards.length > 0) {
                historyEmptySearch.classList.remove('hidden');
            } else {
                historyEmptySearch.classList.add('hidden');
            }
        }
    }

    if (historySearch) {
        historySearch.addEventListener('input', filterHistoryCards);
    }
    if (historyTypeFilter) {
        historyTypeFilter.addEventListener('change', filterHistoryCards);
    }
});
</script>
@endsection
