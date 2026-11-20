@extends('platform.layouts.app', [
    'title' => 'Pengaturan & Kebijakan Platform',
    'headerTitle' => 'Pengaturan & Kebijakan NAYARA',
    'breadcrumb' => 'Tata Kelola Platform > Pengaturan & Kebijakan'
])

@section('content')
<div class="space-y-6">

    {{-- Flash Message Feedback --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs">&check;</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 cursor-pointer">&times;</button>
        </div>
    @endif

    {{-- =========================================================================
         1. HERO BANNER: PENGATURAN & KEBIJAKAN NAYARA (Identik Referensi Gambar)
         ========================================================================= --}}
    <section class="rounded-2xl px-5 py-3.5 sm:px-6 sm:py-4 text-white relative overflow-hidden shadow-lg border border-white/10"
             style="background: linear-gradient(135deg, #691B3D 0%, #4A132E 55%, #350920 100%);">

        {{-- Ambient wave blur circles behind --}}
        <div class="absolute -right-16 -top-24 w-96 h-96 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
        <div class="absolute right-48 -bottom-20 w-80 h-80 rounded-full bg-[#8E1B5C]/20 blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-4">

            {{-- Left Hero Copy --}}
            <div class="space-y-2 max-w-xl">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-xs">
                        <svg class="w-4 h-4 text-[#FFD8D8]"><use href="#i-settings"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15" data-i18n="settings_hero_badge">
                        KONFIGURASI &amp; KEBIJAKAN PLATFORM
                    </span>
                </div>

                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-tight" data-i18n="settings_hero_title">
                        Pengaturan &amp; Kebijakan NAYARA
                    </h1>
                    <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5" data-i18n="settings_hero_subtitle">
                        Kelola harga, layanan, kebijakan komersial, dan konfigurasi seluruh ekosistem NAYARA.
                    </p>
                </div>

                {{-- Action Pills Underneath (Identik Gambar Referensi) --}}
                <div class="flex flex-wrap items-center gap-2 pt-0.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 border border-white/20 text-white text-[11px] font-bold backdrop-blur-xs select-none shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span data-i18n="settings_pill_active">Platform Aktif</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 border border-white/20 text-white text-[11px] font-bold backdrop-blur-xs select-none shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-rose-200"><use href="#i-layers"/></svg>
                        <span data-i18n="settings_pill_groups">7 Kelompok Pengaturan</span>
                    </span>
                </div>
            </div>

            {{-- Right Hero: 3D Perspective Floating Glass Tablet (Ukuran Identik Dashboard w-72 sm:w-80, h-22 sm:h-24, Interaktif Tilt) --}}
            <div class="shrink-0 w-full md:w-auto flex justify-center md:justify-end glass-tablet-3d-wrap py-0 relative select-none" id="settingsHero3dWrap">
                {{-- Floating Pill 1: Gear (Top Left) --}}
                <div class="absolute -top-2.5 -left-2.5 sm:-top-3 sm:-left-3 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]"><use href="#i-settings"/></svg>
                </div>

                {{-- Floating Pill 2: Live Configuration (Top Right) --}}
                <div class="absolute -top-2.5 -right-1.5 sm:-top-3 sm:-right-2 px-2.5 py-0.5 rounded-full bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center gap-1 text-[9.5px] font-bold text-white z-20 pointer-events-none">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Live Policy</span>
                </div>

                {{-- Floating Pill 3: Super Admin Hub (Bottom Right) --}}
                <div class="absolute -bottom-2.5 -right-2 sm:-bottom-3 sm:-right-2 px-2.5 py-0.5 rounded-full bg-white text-[#8E1B5C] border border-rose-200 shadow-md flex items-center gap-1 text-[9.5px] font-black z-20 pointer-events-none">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Configured</span>
                </div>

                {{-- Main 3D Tablet (w-72 sm:w-80, h-22 sm:h-24 Identik Dashboard & Referensi Gambar) --}}
                <div class="glass-tablet-3d w-72 sm:w-80 p-3.5 sm:p-4 relative overflow-hidden flex items-center justify-between gap-4 cursor-pointer" id="settings3dCard">
                    {{-- Left: Admin Hijab Avatar / Policy Icon --}}
                    <div class="flex flex-col items-center justify-center shrink-0 w-20 sm:w-24">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#E11D48] via-[#ba4e77] to-[#8E1B5C] border border-white/40 flex flex-col items-center justify-center shadow-md p-1 relative">
                            <svg class="w-6 h-6 text-white drop-shadow"><use href="#i-settings"/></svg>
                        </div>
                        <div class="w-full space-y-1 mt-2 flex flex-col items-center">
                            <div class="w-16 h-1.5 bg-white/40 rounded-full"></div>
                            <div class="w-10 h-1 bg-white/20 rounded-full"></div>
                        </div>
                    </div>

                    {{-- Right: Settings Checklist Checklist Board in Glass Tablet --}}
                    <div class="flex-1 bg-white/10 border border-white/20 rounded-xl p-2.5 sm:p-3 backdrop-blur-md shadow-inner flex flex-col justify-between h-22 sm:h-24">
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-300">
                                <span class="w-3.5 h-3.5 rounded-full bg-emerald-500/30 flex items-center justify-center text-[9px]">&check;</span>
                                <span class="truncate">Multi-Tenant Isolated</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] text-rose-200">
                                <span class="w-3.5 h-3.5 rounded-full bg-rose-500/30 flex items-center justify-center text-[9px]">&check;</span>
                                <span class="truncate">Tariff Synchronized</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] text-white/90">
                                <span class="w-3.5 h-3.5 rounded-full bg-white/20 flex items-center justify-center text-[9px]">&check;</span>
                                <span class="truncate">SLA Rules Active</span>
                            </div>
                        </div>
                        <div class="w-full h-0.5 bg-white/25 rounded-full"></div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- =========================================================================
         2. 4 TOP METRIC CARDS (Identik Susunan Referensi Gambar)
         ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Card 1: 2 Paket Langganan (Free & Premium) --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 sm:p-4.5 shadow-2xs hover:shadow-md transition-all flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-5 h-5"><use href="#i-layers"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xl font-black text-[#520C31] leading-none">2</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span data-i18n="settings_badge_active">Aktif</span>
                    </span>
                </div>
                <div class="text-xs sm:text-[13px] font-extrabold text-slate-800 mt-1 leading-snug" data-i18n="settings_kpi_sub_title">Paket Langganan</div>
                <div class="text-[10.5px] text-slate-400 font-semibold mt-0.5" data-i18n="settings_kpi_sub_desc">Free &amp; Premium</div>
            </div>
        </div>

        {{-- Card 2: 2 Add-on Aktif (Fitur tambahan) --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 sm:p-4.5 shadow-2xs hover:shadow-md transition-all flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-5 h-5"><use href="#i-puzzle"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xl font-black text-[#520C31] leading-none">2</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span data-i18n="settings_badge_active">Aktif</span>
                    </span>
                </div>
                <div class="text-xs sm:text-[13px] font-extrabold text-slate-800 mt-1 leading-snug" data-i18n="settings_kpi_addon_title">Add-on Aktif</div>
                <div class="text-[10.5px] text-slate-400 font-semibold mt-0.5" data-i18n="settings_kpi_addon_desc">Fitur tambahan</div>
            </div>
        </div>

        {{-- Card 3: Iklan (Kebijakan Promosi) --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 sm:p-4.5 shadow-2xs hover:shadow-md transition-all flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-5 h-5"><use href="#i-megaphone"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-xs sm:text-[13px] font-extrabold text-slate-800 leading-tight" data-i18n="settings_kpi_ads_title">Iklan</div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span data-i18n="settings_badge_active">Aktif</span>
                    </span>
                </div>
                <div class="text-[10.5px] text-slate-400 font-semibold mt-1" data-i18n="settings_kpi_ads_desc">Kebijakan Promosi</div>
            </div>
        </div>

        {{-- Card 4: SLA (Operasional) --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 sm:p-4.5 shadow-2xs hover:shadow-md transition-all flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-5 h-5"><use href="#i-clock"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-xs sm:text-[13px] font-extrabold text-slate-800 leading-tight" data-i18n="settings_kpi_sla_title">SLA</div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span data-i18n="settings_badge_active">Aktif</span>
                    </span>
                </div>
                <div class="text-[10.5px] text-slate-400 font-semibold mt-1" data-i18n="settings_kpi_sla_desc">Operasional</div>
            </div>
        </div>

    </div>

    {{-- =========================================================================
         3. SECTION TITLE: KELOLA PENGATURAN (Identik Referensi Gambar)
         ========================================================================= --}}
    <div class="flex items-center gap-2.5 pt-1">
        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 18px;"></span>
        <div>
            <h2 class="text-sm sm:text-base font-extrabold text-[#172B4D] leading-tight" data-i18n="settings_section_title">
                Kelola Pengaturan
            </h2>
            <p class="text-xs text-slate-500 mt-0.5" data-i18n="settings_section_subtitle">
                Pilih kategori pengaturan untuk mengelola detail kebijakan platform NAYARA.
            </p>
        </div>
    </div>

    {{-- =========================================================================
         4. 7 SETTINGS CATEGORY CARDS (Grid 3 Kolom + 1 Full-Width Identik Gambar)
         ========================================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">

        {{-- Card 1: Paket Langganan --}}
        <div onclick="openSettingModal('modalSubscription')"
             class="group bg-white rounded-2xl border border-[#F0E4EA] hover:border-[#8E1B5C]/40 p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all cursor-pointer flex items-center justify-between gap-3">
            <div class="flex items-start gap-3.5 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-rose-50 text-[#8E1B5C] border border-rose-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform mt-0.5">
                    <svg class="w-5 h-5"><use href="#i-wallet"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-xs sm:text-sm font-extrabold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-tight" data-i18n="settings_card_subscription_title">
                            Paket Langganan
                        </h3>
                        <span class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-rose-100 text-[#8E1B5C] shrink-0" data-i18n="settings_badge_commercial">
                            Komersial
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-snug" data-i18n="settings_card_subscription_desc">
                        Atur harga, periode berlangganan, fitur paket Free &amp; Premium, serta promo.
                    </p>
                </div>
            </div>
            <div class="text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-1 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>
        </div>

        {{-- Card 2: Kapasitas & Add-on --}}
        <div onclick="openSettingModal('modalCapacity')"
             class="group bg-white rounded-2xl border border-[#F0E4EA] hover:border-[#8E1B5C]/40 p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all cursor-pointer flex items-center justify-between gap-3">
            <div class="flex items-start gap-3.5 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-purple-50 text-purple-700 border border-purple-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform mt-0.5">
                    <svg class="w-5 h-5"><use href="#i-layers"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-xs sm:text-sm font-extrabold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-tight" data-i18n="settings_card_capacity_title">
                            Kapasitas &amp; Add-on
                        </h3>
                        <span class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-purple-100 text-purple-700 shrink-0" data-i18n="settings_badge_addon">
                            Fitur Tambahan
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-snug" data-i18n="settings_card_capacity_desc">
                        Kelola harga slot etalase permanen dan add-on (mis. Financial Analytics).
                    </p>
                </div>
            </div>
            <div class="text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-1 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>
        </div>

        {{-- Card 3: Promosi & Iklan --}}
        <div onclick="openSettingModal('modalAds')"
             class="group bg-white rounded-2xl border border-[#F0E4EA] hover:border-[#8E1B5C]/40 p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all cursor-pointer flex items-center justify-between gap-3">
            <div class="flex items-start gap-3.5 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform mt-0.5">
                    <svg class="w-5 h-5"><use href="#i-megaphone"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-xs sm:text-sm font-extrabold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-tight" data-i18n="settings_card_ads_title">
                            Promosi &amp; Iklan
                        </h3>
                        <span class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-amber-100 text-amber-800 shrink-0" data-i18n="settings_badge_monetization">
                            Monetisasi
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-snug" data-i18n="settings_card_ads_desc">
                        Atur tarif iklan, durasi penayangan, kuota, dan aturan promosi tenant.
                    </p>
                </div>
            </div>
            <div class="text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-1 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>
        </div>

        {{-- Card 4: Layanan Setup & Training --}}
        <div onclick="openSettingModal('modalService')"
             class="group bg-white rounded-2xl border border-[#F0E4EA] hover:border-[#8E1B5C]/40 p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all cursor-pointer flex items-center justify-between gap-3">
            <div class="flex items-start gap-3.5 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-700 border border-blue-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform mt-0.5">
                    <svg class="w-5 h-5"><use href="#i-graduation"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-xs sm:text-sm font-extrabold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-tight" data-i18n="settings_card_service_title">
                            Layanan Setup &amp; Training
                        </h3>
                        <span class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-blue-100 text-blue-700 shrink-0" data-i18n="settings_badge_service">
                            Layanan
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-snug" data-i18n="settings_card_service_desc">
                        Kelola tarif konsultasi, implementasi awal, migrasi data, dan pelatihan tenant.
                    </p>
                </div>
            </div>
            <div class="text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-1 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>
        </div>

        {{-- Card 5: Kebijakan Refund --}}
        <div onclick="openSettingModal('modalRefund')"
             class="group bg-white rounded-2xl border border-[#F0E4EA] hover:border-[#8E1B5C]/40 p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all cursor-pointer flex items-center justify-between gap-3">
            <div class="flex items-start gap-3.5 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform mt-0.5">
                    <svg class="w-5 h-5"><use href="#i-refresh"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-xs sm:text-sm font-extrabold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-tight" data-i18n="settings_card_refund_title">
                            Kebijakan Refund
                        </h3>
                        <span class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-emerald-100 text-emerald-800 shrink-0" data-i18n="settings_badge_refund">
                            Kebijakan
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-snug" data-i18n="settings_card_refund_desc">
                        Atur ketentuan pembatalan, persentase pengembalian, dan mekanisme pencairan.
                    </p>
                </div>
            </div>
            <div class="text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-1 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>
        </div>

        {{-- Card 6: SLA Operasional --}}
        <div onclick="openSettingModal('modalSla')"
             class="group bg-white rounded-2xl border border-[#F0E4EA] hover:border-[#8E1B5C]/40 p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all cursor-pointer flex items-center justify-between gap-3">
            <div class="flex items-start gap-3.5 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform mt-0.5">
                    <svg class="w-5 h-5"><use href="#i-clock"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-xs sm:text-sm font-extrabold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-tight" data-i18n="settings_card_sla_title">
                            SLA Operasional
                        </h3>
                        <span class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-rose-100 text-rose-800 shrink-0" data-i18n="settings_badge_sla">
                            Operasional
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-snug" data-i18n="settings_card_sla_desc">
                        Tetapkan batas waktu verifikasi, review, persetujuan, dan layanan platform.
                    </p>
                </div>
            </div>
            <div class="text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-1 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>
        </div>

        {{-- Card 7: Konfigurasi Sistem & Multi-Tenant (Full-Width Row across all columns Identik Gambar) --}}
        <div onclick="openSettingModal('modalSystem')"
             class="md:col-span-2 lg:col-span-3 group bg-white rounded-2xl border border-[#F0E4EA] hover:border-[#8E1B5C]/40 p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all cursor-pointer flex items-center justify-between gap-3">
            <div class="flex items-start gap-3.5 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-purple-50 text-[#8E1B5C] border border-purple-200/80 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform mt-0.5">
                    <svg class="w-5 h-5"><use href="#i-shield"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-xs sm:text-sm font-extrabold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-tight" data-i18n="settings_card_system_title">
                            Konfigurasi Sistem &amp; Multi-Tenant
                        </h3>
                        <span class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-purple-100 text-[#8E1B5C] shrink-0" data-i18n="settings_badge_system">
                            Sistem
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-snug" data-i18n="settings_card_system_desc">
                        Atur kebijakan isolasi data tenant, status fitur global, dan parameter operasional platform.
                    </p>
                </div>
            </div>
            <div class="text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-1 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </div>
        </div>

    </div>

</div>

</div>

{{-- =========================================================================
     5. INTERACTIVE EDIT MODALS (DETAIL SETTINGS & PRICING CONFIGURATION)
     ========================================================================= --}}

{{-- Backdrop --}}
<div id="settingModalBackdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden transition-opacity" onclick="closeAllSettingModals()"></div>

{{-- MODAL 1: PAKET LANGGANAN --}}
<div id="modalSubscription" class="setting-modal fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl z-50 hidden border border-[#F0E4EA] p-5 sm:p-6 space-y-4">
    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-rose-50 text-[#8E1B5C] border border-rose-200 flex items-center justify-center">
                <svg class="w-4 h-4"><use href="#i-wallet"/></svg>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-[#172B4D]">Kelola Paket Langganan</h3>
                <p class="text-[11px] text-slate-400">Atur harga dan batas fitur paket Free &amp; Premium.</p>
            </div>
        </div>
        <button type="button" onclick="closeAllSettingModals()" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
    </div>

    <form method="POST" action="{{ route('platform.settings.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="setting_category" value="subscription">

        {{-- Free ERP --}}
        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-extrabold text-slate-800">Paket Dasar (Free ERP)</h4>
                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">Gratis (Rp 0)</span>
            </div>
            <p class="text-[11px] text-slate-500">Mencakup: Sistem ERP Konveksi Standar Lengkap, Manajemen Pesanan &amp; Produksi, serta <strong>5 Slot Etalase Publik Bawaan</strong>.</p>
        </div>

        {{-- Paket Premium --}}
        <div class="p-3.5 rounded-xl border border-rose-200 bg-rose-50/40 space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-extrabold text-[#8E1B5C]">Paket NAYARA Premium</h4>
                <span class="px-2 py-0.5 rounded bg-rose-100 text-[#8E1B5C] text-[10px] font-bold">PILIHAN RESMI</span>
            </div>
            <p class="text-[11px] text-slate-600">Mencakup +10 Slot Etalase Publik Tambahan, Grafik Analitik Finansial Lanjutan, serta Ekspor PDF &amp; Excel.</p>

            <div class="grid grid-cols-2 gap-3 pt-1">
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">1 Bulan (Rp)</label>
                    <input type="number" name="premium_1m" value="59000" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <span class="text-[9.5px] text-slate-400">Rp 59.000 / bulan</span>
                </div>
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">3 Bulan (Rp)</label>
                    <input type="number" name="premium_3m" value="169000" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <span class="text-[9.5px] text-slate-400">Rp 169.000 / 3 bulan</span>
                </div>
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">6 Bulan (Rp)</label>
                    <input type="number" name="premium_6m" value="319000" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <span class="text-[9.5px] text-slate-400">Rp 319.000 / 6 bulan</span>
                </div>
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">12 Bulan (1 Tahun) (Rp)</label>
                    <input type="number" name="premium_12m" value="569000" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <span class="text-[9.5px] text-slate-400">Rp 569.000 / tahun</span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeAllSettingModals()" class="px-3.5 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#8E1B5C] to-[#68113F] text-white text-xs font-bold shadow-xs hover:from-[#75164B] hover:to-[#550C32] cursor-pointer">Simpan Perubahan</button>
        </div>
    </form>
</div>

{{-- MODAL 2: KAPASITAS & ADD-ON --}}
<div id="modalCapacity" class="setting-modal fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl z-50 hidden border border-[#F0E4EA] p-5 sm:p-6 space-y-4">
    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 border border-purple-200 flex items-center justify-center">
                <svg class="w-4 h-4"><use href="#i-layers"/></svg>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-[#172B4D]">Kelola Kapasitas &amp; Add-on</h3>
                <p class="text-[11px] text-slate-400">Slot etalase permanen dan modul Financial Analytics.</p>
            </div>
        </div>
        <button type="button" onclick="closeAllSettingModals()" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
    </div>

    <form method="POST" action="{{ route('platform.settings.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="setting_category" value="capacity">

        {{-- Slot Etalase Permanen --}}
        <div class="p-3.5 rounded-xl border border-purple-200 bg-purple-50/40 space-y-2.5">
            <h4 class="text-xs font-extrabold text-purple-900">Slot Etalase Publik Permanen</h4>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">+5 Slot Permanen (Rp)</label>
                    <input type="number" name="slot_5" value="49000" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <span class="text-[9.5px] text-slate-400">Rp 49.000 seumur hidup</span>
                </div>
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">+10 Slot Permanen (Rp)</label>
                    <input type="number" name="slot_10" value="89000" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <span class="text-[9.5px] text-slate-400">Rp 89.000 seumur hidup</span>
                </div>
            </div>
        </div>

        {{-- Financial Analytics Add-On --}}
        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2.5">
            <h4 class="text-xs font-extrabold text-slate-800">Financial Analytics Add-On</h4>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">1 Bulan (Rp)</label>
                    <input type="number" name="analytics_1m" value="45000" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <span class="text-[9.5px] text-slate-400">Rp 45.000 / bulan</span>
                </div>
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">3 Bulan (Rp)</label>
                    <input type="number" name="analytics_3m" value="129000" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <span class="text-[9.5px] text-slate-400">Rp 129.000 / 3 bulan</span>
                </div>
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">6 Bulan (Rp)</label>
                    <input type="number" name="analytics_6m" value="249000" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <span class="text-[9.5px] text-slate-400">Rp 249.000 / 6 bulan</span>
                </div>
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">12 Bulan (Rp)</label>
                    <input type="number" name="analytics_12m" value="489000" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                    <span class="text-[9.5px] text-slate-400">Rp 489.000 / tahun</span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeAllSettingModals()" class="px-3.5 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#8E1B5C] to-[#68113F] text-white text-xs font-bold shadow-xs hover:from-[#75164B] hover:to-[#550C32] cursor-pointer">Simpan Perubahan</button>
        </div>
    </form>
</div>

{{-- MODAL 3: PROMOSI & IKLAN --}}
<div id="modalAds" class="setting-modal fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl z-50 hidden border border-[#F0E4EA] p-5 sm:p-6 space-y-4">
    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center">
                <svg class="w-4 h-4"><use href="#i-megaphone"/></svg>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-[#172B4D]">Kelola Promosi &amp; Iklan</h3>
                <p class="text-[11px] text-slate-400">Aturan tayang iklan dan SLA verifikasi materi.</p>
            </div>
        </div>
        <button type="button" onclick="closeAllSettingModals()" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
    </div>

    <form method="POST" action="{{ route('platform.settings.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="setting_category" value="ads_monetization">

        <div class="space-y-3 text-xs text-slate-600">
            <div class="p-3 rounded-xl bg-amber-50/50 border border-amber-200">
                <span class="font-bold text-amber-900 block mb-1">Model Anggaran &amp; Ranking:</span>
                <p>Anggaran Fleksibel (> Rp 0) dengan Ranking Tayang Dinamis Berbasis Tawaran Harian Efektif (Daily Bid).</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">SLA Peninjauan &amp; Revisi (Jam)</label>
                    <input type="number" name="sla_review_hours" value="12" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                </div>
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">Masa Berlaku Invoice (Jam)</label>
                    <input type="number" name="invoice_validity_hours" value="2" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                </div>
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">Hold Verifikasi Super Admin (Jam)</label>
                    <input type="number" name="verification_hold_hours" value="24" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                </div>
                <div>
                    <label class="text-[10.5px] font-bold text-slate-600 block mb-1">Batas Maksimal Revisi Materi</label>
                    <input type="number" name="max_revisions" value="2" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeAllSettingModals()" class="px-3.5 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#8E1B5C] to-[#68113F] text-white text-xs font-bold shadow-xs hover:from-[#75164B] hover:to-[#550C32] cursor-pointer">Simpan Perubahan</button>
        </div>
    </form>
</div>

{{-- MODAL 4: LAYANAN SETUP & TRAINING --}}
<div id="modalService" class="setting-modal fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl z-50 hidden border border-[#F0E4EA] p-5 sm:p-6 space-y-4">
    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center">
                <svg class="w-4 h-4"><use href="#i-graduation"/></svg>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-[#172B4D]">Layanan Setup &amp; Training</h3>
                <p class="text-[11px] text-slate-400">Implementasi awal, migrasi master data, dan pelatihan staf.</p>
            </div>
        </div>
        <button type="button" onclick="closeAllSettingModals()" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
    </div>

    <form method="POST" action="{{ route('platform.settings.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="setting_category" value="service">

        <div class="space-y-3 text-xs text-slate-600">
            <div class="p-3 rounded-xl bg-blue-50/50 border border-blue-200">
                <span class="font-bold text-blue-900 block mb-1">Status Penentuan Tarif:</span>
                <p>Tarif Ditetapkan Berdasarkan Penawaran Khusus / Kesepakatan Terpisah per tenant.</p>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                <span class="font-bold text-slate-800 block mb-1">Cakupan Layanan Resmi:</span>
                <p>Pelatihan Onsite Staf Operasional, Migrasi Data Konveksi, dan Konsultasi Alur ERP.</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeAllSettingModals()" class="px-3.5 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#8E1B5C] to-[#68113F] text-white text-xs font-bold shadow-xs hover:from-[#75164B] hover:to-[#550C32] cursor-pointer">Simpan Perubahan</button>
        </div>
    </form>
</div>

{{-- MODAL 5: KEBIJAKAN REFUND --}}
<div id="modalRefund" class="setting-modal fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl z-50 hidden border border-[#F0E4EA] p-5 sm:p-6 space-y-4">
    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center">
                <svg class="w-4 h-4"><use href="#i-refresh"/></svg>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-[#172B4D]">Kebijakan Refund</h3>
                <p class="text-[11px] text-slate-400">Aturan pengembalian dana iklan tenant.</p>
            </div>
        </div>
        <button type="button" onclick="closeAllSettingModals()" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
    </div>

    <form method="POST" action="{{ route('platform.settings.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="setting_category" value="refund">

        <div class="space-y-3 text-xs text-slate-600">
            <div class="p-3 rounded-xl border border-emerald-200 bg-emerald-50">
                <span class="font-bold text-emerald-900 block mb-1">Sebelum Jadwal Iklan Mulai:</span>
                <p class="text-emerald-800">100% Pengembalian Penuh (Full Refund) tanpa potongan biaya.</p>
            </div>
            <div class="p-3 rounded-xl border border-amber-200 bg-amber-50">
                <span class="font-bold text-amber-900 block mb-1">Setelah Iklan Berjalan Sebagian:</span>
                <p class="text-amber-800">Pengembalian Proporsional berdasarkan sisa hari tayang belum aktif.</p>
            </div>
            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50">
                <span class="font-bold text-slate-800 block mb-1">Mekanisme Pembayaran:</span>
                <p>Transfer Manual Rekening Bank Penerima terverifikasi Super Admin dengan bukti transfer wajib.</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeAllSettingModals()" class="px-3.5 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#8E1B5C] to-[#68113F] text-white text-xs font-bold shadow-xs hover:from-[#75164B] hover:to-[#550C32] cursor-pointer">Simpan Perubahan</button>
        </div>
    </form>
</div>

{{-- MODAL 6: SLA OPERASIONAL --}}
<div id="modalSla" class="setting-modal fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl z-50 hidden border border-[#F0E4EA] p-5 sm:p-6 space-y-4">
    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 flex items-center justify-center">
                <svg class="w-4 h-4"><use href="#i-clock"/></svg>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-[#172B4D]">SLA Operasional</h3>
                <p class="text-[11px] text-slate-400">Ambang batas waktu layanan Super Admin.</p>
            </div>
        </div>
        <button type="button" onclick="closeAllSettingModals()" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
    </div>

    <form method="POST" action="{{ route('platform.settings.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="setting_category" value="sla">

        <div class="grid grid-cols-2 gap-3 text-xs">
            <div>
                <label class="text-[10.5px] font-bold text-slate-600 block mb-1">Review Iklan (Jam)</label>
                <input type="number" name="sla_review_hours" value="12" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
            </div>
            <div>
                <label class="text-[10.5px] font-bold text-slate-600 block mb-1">Kedaluwarsa Invoice (Jam)</label>
                <input type="number" name="invoice_validity_hours" value="2" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
            </div>
            <div>
                <label class="text-[10.5px] font-bold text-slate-600 block mb-1">Maksimal Hold Verifikasi (Jam)</label>
                <input type="number" name="verification_hold_hours" value="24" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
            </div>
            <div>
                <label class="text-[10.5px] font-bold text-slate-600 block mb-1">Batas Revisi Materi</label>
                <input type="number" name="max_revisions" value="2" class="w-full text-xs font-bold rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-[#8E1B5C] bg-white">
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeAllSettingModals()" class="px-3.5 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#8E1B5C] to-[#68113F] text-white text-xs font-bold shadow-xs hover:from-[#75164B] hover:to-[#550C32] cursor-pointer">Simpan Perubahan</button>
        </div>
    </form>
</div>

{{-- MODAL 7: KONFIGURASI SISTEM & MULTI-TENANT --}}
<div id="modalSystem" class="setting-modal fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl z-50 hidden border border-[#F0E4EA] p-5 sm:p-6 space-y-4">
    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-purple-50 text-[#8E1B5C] border border-purple-200 flex items-center justify-center">
                <svg class="w-4 h-4"><use href="#i-shield"/></svg>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-[#172B4D]">Konfigurasi Sistem &amp; Multi-Tenant</h3>
                <p class="text-[11px] text-slate-400">Parameter keamanan dan isolasi entitas platform.</p>
            </div>
        </div>
        <button type="button" onclick="closeAllSettingModals()" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
    </div>

    <form method="POST" action="{{ route('platform.settings.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="setting_category" value="system">

        <div class="space-y-3 text-xs text-slate-600">
            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                <div>
                    <span class="font-bold text-emerald-900 block">Isolasi Multi-Tenant:</span>
                    <span class="text-[11px] text-emerald-700">Setiap outlet beroperasi sebagai entitas independen (1 Outlet = 1 Perusahaan).</span>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-600 text-white">AKTIF</span>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-1">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 font-bold uppercase block">Total Tenant Terdaftar</span>
                    <span class="text-lg font-black text-slate-800">{{ $platformConfig['outlets_count'] }} Outlet</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 font-bold uppercase block">Langganan Aktif</span>
                    <span class="text-lg font-black text-[#8E1B5C]">{{ $platformConfig['active_subscriptions_count'] }} Aktif</span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeAllSettingModals()" class="px-3.5 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer">Batal</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#8E1B5C] to-[#68113F] text-white text-xs font-bold shadow-xs hover:from-[#75164B] hover:to-[#550C32] cursor-pointer">Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
function openSettingModal(modalId) {
    document.querySelectorAll('.setting-modal').forEach(m => m.classList.add('hidden'));
    const modal = document.getElementById(modalId);
    const backdrop = document.getElementById('settingModalBackdrop');
    if (modal && backdrop) {
        backdrop.classList.remove('hidden');
        modal.classList.remove('hidden');
    }
}

function closeAllSettingModals() {
    const backdrop = document.getElementById('settingModalBackdrop');
    if (backdrop) backdrop.classList.add('hidden');
    document.querySelectorAll('.setting-modal').forEach(m => m.classList.add('hidden'));
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAllSettingModals();
});

document.addEventListener('DOMContentLoaded', function() {
    // 3D Tilt Motion pada Hero Tablet
    const settingsHeroWrap = document.getElementById('settingsHero3dWrap');
    const settings3dCard = document.getElementById('settings3dCard');
    if (settingsHeroWrap && settings3dCard) {
        settingsHeroWrap.addEventListener('mousemove', function(e) {
            const rect = settingsHeroWrap.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            const tiltX = -(y / (rect.height / 2)) * 12;
            const tiltY = (x / (rect.width / 2)) * 12;
            settings3dCard.style.transform = `rotateY(${tiltY - 11}deg) rotateX(${tiltX + 6}deg) scale(1.03)`;
        });
        settingsHeroWrap.addEventListener('mouseleave', function() {
            settings3dCard.style.transform = 'rotateY(-13deg) rotateX(7deg) rotateZ(-1deg) scale(1)';
        });
    }
});
</script>
@endsection
