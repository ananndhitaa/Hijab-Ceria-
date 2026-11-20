@extends('layouts.app')

@section('content')
<style>
    main { min-width: 0 !important; max-width: 100% !important; overflow-x: hidden; }
</style>

<div class="space-y-6 sm:space-y-7 w-full min-w-0 max-w-full pb-10">

    {{-- ── 1. GLOBAL FEATURE HEADER WITH EXECUTIVE MONITORING BADGE ──────── --}}
    <x-feature-header
        title="Langganan & Pembelian Layanan Platform"
        subtitle="Kelola paket Premium, Financial Analytics Add-On, dan kapasitas slot etalase publik toko Anda.">
        <x-slot:badge>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                EXECUTIVE MONITORING
            </span>
        </x-slot:badge>
        <x-slot:iconSlot>
            <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px] text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-6.75 4.5h18a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-18A2.25 2.25 0 001.5 6.75v10.5A2.25 2.25 0 003.75 21z" />
            </svg>
        </x-slot:iconSlot>
    </x-feature-header>

    {{-- ── FLASH ALERTS ─────────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="w-full bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 flex items-center gap-3 text-emerald-800 text-xs sm:text-sm font-medium shadow-xs">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="flex-1">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="w-full bg-rose-50 border border-rose-200 rounded-xl p-3.5 flex items-center gap-3 text-rose-800 text-xs sm:text-sm font-medium shadow-xs">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span class="flex-1">{{ session('error') }}</span>
        </div>
    @endif

    {{-- ── 2. STATUS HAK AKSES & LAYANAN TOKO ANDA ─────────────────────────── --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm sm:text-base font-bold text-[#172B4D] tracking-tight">Status Hak Akses & Layanan Toko Anda</h2>
                <p class="text-xs text-[#64748B] mt-0.5">Ringkasan hak akses aktif, lisensi fitur premium, dan ketersediaan kuota slot etalase.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
            {{-- CARD 1: PAKET PREMIUM --}}
            <div class="bg-white rounded-2xl border border-border-c p-5 flex flex-col justify-between shadow-card hover:shadow-card-hover transition-all min-h-[128px]">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-xs font-semibold text-text-sec uppercase tracking-wider">Paket Premium</p>
                                @if($activePremium)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>AKTIF</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>TIDAK AKTIF</span>
                                    </span>
                                @endif
                            </div>
                            <div class="mt-1">
                                @if($activePremium)
                                    <p class="text-xl sm:text-2xl font-extrabold text-[#172B4D]">Berlaku s/d {{ $activePremium->expires_at->format('d M Y') }}</p>
                                @else
                                    <p class="text-xl sm:text-2xl font-extrabold text-[#172B4D]">Paket Free</p>
                                @endif
                            </div>
                        </div>
                        <div class="w-9 h-9 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <p class="text-[11px] text-text-sec mt-3 pt-2.5 border-t border-slate-100">
                    @if($activePremium)
                        Sisa {{ now()->diffInDays($activePremium->expires_at) }} hari (+10 Slot & Analitik Penuh)
                    @else
                        Toko menggunakan paket Free (5 slot etalase toko)
                    @endif
                </p>
            </div>

            {{-- CARD 2: FINANCIAL ANALYTICS ADD-ON --}}
            <div class="bg-white rounded-2xl border border-border-c p-5 flex flex-col justify-between shadow-card hover:shadow-card-hover transition-all min-h-[128px]">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-xs font-semibold text-text-sec uppercase tracking-wider">Financial Analytics</p>
                                @if($activeAddon)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>AKTIF</span>
                                    </span>
                                @elseif($activePremium)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C]"></span>
                                        <span>PREMIUM</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>TIDAK AKTIF</span>
                                    </span>
                                @endif
                            </div>
                            <div class="mt-1">
                                @if($activeAddon)
                                    <p class="text-xl sm:text-2xl font-extrabold text-[#172B4D]">Berlaku s/d {{ $activeAddon->expires_at->format('d M Y') }}</p>
                                @elseif($activePremium)
                                    <p class="text-xl sm:text-2xl font-extrabold text-[#8E1B5C]">Fitur Lengkap Aktif</p>
                                @else
                                    <p class="text-xl sm:text-2xl font-extrabold text-[#172B4D]">Akses Terbatas</p>
                                @endif
                            </div>
                        </div>
                        <div class="w-9 h-9 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <p class="text-[11px] text-text-sec mt-3 pt-2.5 border-t border-slate-100">
                    @if($activeAddon || $activePremium)
                        Grafik performa bisnis & ekspor laporan aktif
                    @else
                        Analitik dasar gratis &bull; Grafik lanjutan & ekspor terkunci
                    @endif
                </p>
            </div>

            {{-- CARD 3: SLOT ETALASE PUBLIK --}}
            <div class="bg-white rounded-2xl border border-border-c p-5 flex flex-col justify-between shadow-card hover:shadow-card-hover transition-all min-h-[128px]">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-xs font-semibold text-text-sec uppercase tracking-wider">Kapasitas Slot Etalase</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                                    Kapasitas Toko
                                </span>
                            </div>
                            <div class="mt-1">
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-2xl sm:text-3xl font-extrabold text-[#8E1B5C] tracking-tight tabular-nums">{{ $totalCapacity }}</span>
                                    <span class="text-sm font-semibold text-[#172B4D]">Slot</span>
                                </div>
                            </div>
                        </div>
                        <div class="w-9 h-9 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <p class="text-[11px] text-text-sec mt-3 pt-2.5 border-t border-slate-100">
                    5 Gratis &bull; {{ $purchasedSlots }} Permanen {{ $activePremium ? '+ 10 Premium' : '' }}
                </p>
            </div>
        </div>
    </div>

    {{-- ── 3. UPGRADE ADD-ON KE PREMIUM BANNER (IF ELIGIBLE) ──────────────── --}}
    @if($upgradeQuote)
    <div class="w-full bg-[#FFF9FB] border border-[#8E1B5C]/30 rounded-xl p-5 shadow-xs">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="space-y-1 max-w-3xl">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#8E1B5C] text-white uppercase tracking-wider">
                    PROMO UPGRADE ADD-ON KE PREMIUM
                </span>
                <h3 class="text-base font-bold text-[#172B4D] tracking-tight">Tingkatkan Add-On Anda Menjadi Paket Premium Penuh</h3>
                <p class="text-xs text-[#64748B] leading-relaxed">
                    Sisa waktu paket Add-on Anda: <span class="font-semibold text-[#172B4D]">{{ $upgradeQuote['remaining_hours'] }} jam</span>.
                    Upgrade dengan biaya prorata hanya <strong class="text-[#8E1B5C]">Rp {{ number_format($upgradeQuote['prorated_amount'], 0, ',', '.') }}</strong> hingga {{ $upgradeQuote['expires_at']->format('d M Y') }}.
                </p>
            </div>
            <form action="{{ route('owner.subscriptions.upgrade') }}" method="POST" class="shrink-0 w-full sm:w-auto">
                @csrf
                <button type="submit"
                        class="w-full sm:w-auto h-9 px-5 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold rounded-lg transition-colors shadow-xs"
                        onclick="return confirm('Buat invoice upgrade prorata senilai Rp {{ number_format($upgradeQuote['prorated_amount'], 0, ',', '.') }}?')">
                    Upgrade ke Premium Sekarang
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- ── 4. PAKET NAYARA PREMIUM ──────────────────────────────────────────── --}}
    <div class="space-y-3.5">
        <div>
            <h2 class="text-sm sm:text-base font-bold text-[#172B4D] tracking-tight">Paket NAYARA Premium</h2>
            <p class="text-xs text-[#64748B] mt-0.5">Mencakup +10 Slot Etalase Publik, Grafik Analitik Keuangan Lanjutan, serta Ekspor Laporan PDF & Excel.</p>
        </div>

        @php
            $premiumOptions = [
                ['months' => 1, 'price' => 59000, 'label' => '1 Bulan', 'per_month' => 'Rp 59.000 / bln'],
                ['months' => 3, 'price' => 169000, 'label' => '3 Bulan', 'per_month' => 'Rp 56.333 / bln', 'popular' => true],
                ['months' => 6, 'price' => 319000, 'label' => '6 Bulan', 'per_month' => 'Rp 53.167 / bln'],
                ['months' => 12, 'price' => 569000, 'label' => '12 Bulan', 'per_month' => 'Rp 47.417 / bln', 'hemat' => 'Hemat Rp 139.000'],
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            @foreach($premiumOptions as $opt)
            <div class="rounded-2xl {{ !empty($opt['popular']) ? 'border border-[#8E1B5C] bg-[#FFFBFD] shadow-card ring-1 ring-[#8E1B5C]/20' : 'border border-border-c bg-white shadow-card' }} p-5 flex flex-col justify-between hover:shadow-card-hover transition-all duration-150">
                <div>
                    {{-- Header Row: Label & Badges --}}
                    <div class="flex items-center justify-between gap-2 min-h-[22px]">
                        <span class="text-xs font-bold uppercase tracking-wider {{ !empty($opt['popular']) ? 'text-[#8E1B5C]' : 'text-[#64748B]' }}">
                            {{ $opt['label'] }}
                        </span>
                        @if(!empty($opt['popular']))
                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded uppercase tracking-wider bg-[#8E1B5C] text-white">
                                PALING DIMINATI
                            </span>
                        @elseif(!empty($opt['hemat']))
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ $opt['hemat'] }}
                            </span>
                        @endif
                    </div>

                    {{-- Price Block --}}
                    <div class="mt-2.5">
                        <p class="text-2xl font-extrabold text-[#8E1B5C] tracking-tight tabular-nums">
                            Rp {{ number_format($opt['price'], 0, ',', '.') }}
                        </p>
                        <p class="text-[11px] text-[#64748B] mt-0.5 font-medium">{{ $opt['per_month'] }}</p>
                    </div>

                    {{-- Divider --}}
                    <div class="h-px bg-slate-100 my-3.5"></div>

                    {{-- Feature Bullets --}}
                    <ul class="space-y-2 text-xs text-[#172B4D]">
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>+10 Slot Etalase Publik</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Grafik Financial Analytics Pro</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Ekspor Laporan PDF & Excel</span>
                        </li>
                    </ul>
                </div>

                {{-- Action Button --}}
                <form action="{{ route('owner.subscriptions.premium') }}" method="POST" class="mt-5">
                    @csrf
                    <input type="hidden" name="months" value="{{ $opt['months'] }}">
                    <button type="submit"
                            class="w-full h-10 px-4 rounded-xl text-xs sm:text-sm font-semibold transition-colors shadow-xs flex items-center justify-center {{ !empty($opt['popular']) ? 'bg-[#8E1B5C] hover:bg-[#68113F] text-white' : 'bg-[#8E1B5C] hover:bg-[#68113F] text-white' }}"
                            onclick="return confirm('Buat invoice paket Premium {{ $opt['label'] }}?')">
                        <span>{{ $activePremium ? 'Perpanjang Masa Aktif' : 'Pilih Paket' }}</span>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── 5. FINANCIAL ANALYTICS ADD-ON ──────────────────────────────────── --}}
    <div class="space-y-3.5">
        <div>
            <h2 class="text-sm sm:text-base font-bold text-[#172B4D] tracking-tight">Financial Analytics Add-On</h2>
            <p class="text-xs text-[#64748B] mt-0.5">Akses grafik performa laba/rugi bulanan, komposisi beban operasional, serta ekspor laporan PDF dan Excel tanpa bonus slot etalase.</p>
        </div>

        @php
            $addonOptions = [
                ['months' => 1, 'price' => 45000, 'label' => '1 Bulan', 'per_month' => 'Rp 45.000 / bln'],
                ['months' => 3, 'price' => 129000, 'label' => '3 Bulan', 'per_month' => 'Rp 43.000 / bln'],
                ['months' => 6, 'price' => 249000, 'label' => '6 Bulan', 'per_month' => 'Rp 41.500 / bln'],
                ['months' => 12, 'price' => 489000, 'label' => '12 Bulan', 'per_month' => 'Rp 40.750 / bln', 'hemat' => 'Hemat Rp 51.000'],
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            @foreach($addonOptions as $opt)
            <div class="rounded-2xl border border-border-c bg-white shadow-card p-5 flex flex-col justify-between hover:shadow-card-hover transition-all duration-150">
                <div>
                    {{-- Header Row: Label & Badges --}}
                    <div class="flex items-center justify-between gap-2 min-h-[22px]">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#64748B]">
                            {{ $opt['label'] }}
                        </span>
                        @if(!empty($opt['hemat']))
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ $opt['hemat'] }}
                            </span>
                        @endif
                    </div>

                    {{-- Price Block --}}
                    <div class="mt-2.5">
                        <p class="text-2xl font-extrabold text-[#172B4D] tracking-tight tabular-nums">
                            Rp {{ number_format($opt['price'], 0, ',', '.') }}
                        </p>
                        <p class="text-[11px] text-[#64748B] mt-0.5 font-medium">{{ $opt['per_month'] }}</p>
                    </div>

                    {{-- Divider --}}
                    <div class="h-px bg-slate-100 my-3.5"></div>

                    {{-- Feature Bullets --}}
                    <ul class="space-y-2 text-xs text-[#172B4D]">
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Grafik Laba/Rugi Bulanan</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Komposisi Beban Operasional</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Ekspor Laporan PDF & Excel</span>
                        </li>
                    </ul>
                </div>

                {{-- Action Button --}}
                <form action="{{ route('owner.subscriptions.analytics-addon') }}" method="POST" class="mt-5">
                    @csrf
                    <input type="hidden" name="months" value="{{ $opt['months'] }}">
                    <button type="submit"
                            class="w-full h-10 px-4 rounded-xl text-xs sm:text-sm font-semibold transition-colors shadow-xs flex items-center justify-center {{ $activePremium ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed' : 'border border-[#FFD8D8] bg-white hover:bg-[#FFF9FB] text-[#172B4D] hover:text-[#8E1B5C] hover:border-[#8E1B5C]' }}"
                            {{ $activePremium ? 'disabled' : '' }}
                            onclick="{{ $activePremium ? 'return false;' : "return confirm('Buat invoice Financial Analytics Add-On {$opt['label']}?');" }}">
                        <span>{{ $activePremium ? 'Tercakup di Premium' : 'Pilih Add-On' }}</span>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── 6. PEMBELIAN SLOT ETALASE PUBLIK PERMANEN ──────────────────────── --}}
    <div class="space-y-3.5">
        <div>
            <h2 class="text-sm sm:text-base font-bold text-[#172B4D] tracking-tight">Pembelian Slot Etalase Publik Permanen</h2>
            <p class="text-xs text-[#64748B] mt-0.5">Tambah kapasitas etalase toko secara permanen selama akun toko aktif. Dapat dibeli berulang kali.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 w-full">
            {{-- CARD A: +5 SLOT --}}
            <div class="rounded-2xl border border-border-c bg-white shadow-card p-5 flex flex-col justify-between hover:shadow-card-hover transition-all">
                <div>
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#64748B] bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                PERMANEN
                            </span>
                            <h3 class="text-base font-bold text-[#172B4D] mt-1.5">+5 Slot Etalase Tambahan</h3>
                            <p class="text-xs text-[#64748B] mt-0.5">Berlaku permanen selama akun toko aktif.</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xl font-extrabold text-[#8E1B5C] tracking-tight tabular-nums">Rp 49.000</p>
                            <p class="text-[11px] text-[#64748B]">Sekali bayar</p>
                        </div>
                    </div>

                    <ul class="space-y-1.5 text-xs text-[#172B4D] mt-3">
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Menambah kapasitas terbit publik sebanyak <strong>5 produk</strong></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Kapasitas tersimpan selamanya tanpa batas kedaluwarsa</span>
                        </li>
                    </ul>
                </div>

                <form action="{{ route('owner.subscriptions.slots') }}" method="POST" class="mt-4 pt-3 border-t border-slate-100">
                    @csrf
                    <input type="hidden" name="slots" value="5">
                    <button type="submit"
                            class="w-full h-10 px-4 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs sm:text-sm font-semibold rounded-xl transition-colors shadow-xs flex items-center justify-center gap-1.5"
                            onclick="return confirm('Buat invoice pembelian +5 slot etalase permanen seharga Rp 49.000?')">
                        <span>Beli +5 Slot</span>
                    </button>
                </form>
            </div>

            {{-- CARD B: +10 SLOT --}}
            <div class="rounded-2xl border border-[#8E1B5C]/40 bg-[#FFFBFD] shadow-card p-5 flex flex-col justify-between hover:shadow-card-hover transition-all ring-1 ring-[#8E1B5C]/15">
                <div>
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E1B5C] bg-[#F7E6EE] px-2 py-0.5 rounded border border-[#FFD8D8]">
                                    PERMANEN
                                </span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Hemat Rp 9.000
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-[#172B4D] mt-1.5">+10 Slot Etalase Tambahan</h3>
                            <p class="text-xs text-[#64748B] mt-0.5">Berlaku permanen selama akun toko aktif.</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xl font-extrabold text-[#8E1B5C] tracking-tight tabular-nums">Rp 89.000</p>
                            <p class="text-[11px] text-[#64748B]">Sekali bayar</p>
                        </div>
                    </div>

                    <ul class="space-y-1.5 text-xs text-[#172B4D] mt-3">
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Menambah kapasitas terbit publik sebanyak <strong>10 produk</strong></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Investasi terbaik untuk ekspansi katalog skala penuh</span>
                        </li>
                    </ul>
                </div>

                <form action="{{ route('owner.subscriptions.slots') }}" method="POST" class="mt-4 pt-3 border-t border-slate-100">
                    @csrf
                    <input type="hidden" name="slots" value="10">
                    <button type="submit"
                            class="w-full h-10 px-4 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs sm:text-sm font-semibold rounded-xl transition-colors shadow-xs flex items-center justify-center gap-1.5"
                            onclick="return confirm('Buat invoice pembelian +10 slot etalase permanen seharga Rp 89.000 (Hemat Rp 9.000)?')">
                        <span>Beli +10 Slot</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── 7. RIWAYAT TAGIHAN & INVOICE PLATFORM (FULL WIDTH) ───────────────── --}}
    <div class="bg-white rounded-2xl border border-border-c shadow-card overflow-hidden w-full min-w-0 max-w-full">
        <div class="px-5 py-4 border-b border-[#E1CFD9] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-[#172B4D] tracking-tight">Riwayat Tagihan & Invoice Platform</h3>
                <p class="text-xs text-[#64748B] mt-0.5">Daftar transaksi langganan paket, add-on, dan kuota slot platform toko Anda.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-[#64748B]">
                    Total: <strong class="text-[#172B4D]">{{ $invoices->total() }}</strong> Invoice
                </span>
            </div>
        </div>

        <div class="overflow-x-auto w-full max-w-full">
            <table class="w-full text-left border-collapse {{ $invoices->count() > 0 ? 'min-w-[650px]' : '' }}">
                <thead>
                    <tr class="bg-[#F7E4EC] border-b border-[#EAD0DC] text-[11px] font-bold text-[#53677F] uppercase tracking-wider">
                        <th class="py-3 px-5">No. Invoice</th>
                        <th class="py-3 px-4">Layanan</th>
                        <th class="py-3 px-4">Nominal</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Batas Waktu / Dibuat</th>
                        <th class="py-3 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-3.5 px-5 font-mono font-bold text-[#172B4D]">
                            {{ $inv->invoice_no }}
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($inv->service_type === 'PREMIUM')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                                        Paket Premium
                                    </span>
                                @elseif($inv->service_type === 'ANALYTICS_ADDON')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        Analytics Add-On
                                    </span>
                                @elseif($inv->service_type === 'LISTING_SLOTS')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Slot (+{{ $inv->metadata['slots'] ?? 5 }})
                                    </span>
                                @elseif($inv->service_type === 'UPGRADE_PREMIUM')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        Upgrade Premium
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $inv->service_type }}
                                    </span>
                                @endif

                                @if($inv->package_duration_months)
                                    <span class="text-xs text-[#64748B]">({{ $inv->package_duration_months }} bln)</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-bold text-[#8E1B5C] tabular-nums">
                            Rp {{ number_format($inv->amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($inv->status === 'VERIFIED')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Lunas & Terverifikasi</span>
                                </span>
                            @elseif($inv->status === 'PAYMENT_SUBMITTED')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span>Menunggu Verifikasi</span>
                                </span>
                            @elseif($inv->status === 'UNPAID')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span>Menunggu Pembayaran</span>
                                </span>
                            @elseif($inv->status === 'EXPIRED')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    <span>Kedaluwarsa</span>
                                </span>
                            @elseif($inv->status === 'REFUNDED')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                    <span>Di-refund</span>
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    {{ $inv->status }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-xs text-[#64748B]">
                            <p class="font-medium text-[#172B4D]">{{ $inv->created_at->timezone(config('app.display_timezone', 'Asia/Jakarta'))->translatedFormat('d M Y H:i') }} WIB</p>
                            @if($inv->isUnpaid() && $inv->expires_at)
                                <p class="text-[11px] text-rose-600 mt-0.5">Batas: {{ $inv->expires_at->timezone(config('app.display_timezone', 'Asia/Jakarta'))->translatedFormat('d M Y H:i') }} WIB</p>
                            @endif
                        </td>
                        <td class="py-3.5 px-5 text-right whitespace-nowrap">
                            <a href="{{ route('owner.subscriptions.invoice', $inv->id) }}"
                               class="inline-flex items-center gap-1 h-7 px-2.5 rounded-lg text-xs font-semibold border transition-colors shadow-xs {{ $inv->isPaid() ? 'border-slate-200 bg-white hover:bg-slate-50 text-[#172B4D]' : 'border-[#FFD8D8] bg-[#FFF9FB] hover:bg-[#F7E6EE] text-[#8E1B5C]' }}">
                                <span>{{ $inv->isPaid() ? 'Lihat Bukti' : 'Bayar / Detail' }}</span>
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 text-[#64748B]">
                            <div class="max-w-xs mx-auto space-y-2">
                                <div class="w-10 h-10 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center mx-auto shadow-xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <p class="font-bold text-xs text-[#172B4D]">Belum ada riwayat tagihan platform.</p>
                                <p class="text-[11px] text-[#64748B]">Invoice akan tercatat secara otomatis saat Anda melakukan transaksi langganan atau pembelian slot.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
        <div class="p-3.5 border-t border-[#E1CFD9] bg-slate-50/50">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
