<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? 'Super Admin Platform' }} — NAYARA Group</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nayara: {
                            deep:     '#4E0C2E',
                            wine:     '#520C31',
                            burgundy: '#68113F',
                            primary:  '#8E1B5C',
                            medium:   '#701D49',
                            pink:     '#D96B9B',
                            blush:    '#F7E6EE',
                            light:    '#FFD8D8',
                            border:   '#E1CFD9',
                            bg:       '#FFF9FB',
                            dark:     '#172B4D',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- NAYARA Luxury Super Admin CSS -->
    <link rel="stylesheet" href="{{ asset('css/nayara-superadmin.css') . '?v=20261108-luxury' }}">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #FBF8FA;
            font-family: 'Inter', sans-serif;
            color: #172B4D;
            overflow-x: hidden;
        }

        /* Navigation link styles matching Dashboard luxury pill */
        .n-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            line-height: 1rem;
            font-weight: 500;
            color: rgba(247, 230, 238, 0.8);
            transition: all 0.15s ease;
        }
        .n-nav-link:hover {
            color: #FFFFFF;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .n-nav-link.is-active {
            font-weight: 700;
            color: #FFFFFF;
            background: linear-gradient(95deg, #ba4e77, #8E1B5C);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .n-nav-link svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
            color: #D96B9B;
        }
        .n-nav-link.is-active svg {
            color: #FFFFFF;
        }
        .card-watermark-lg {
            position: absolute;
            bottom: -6px;
            right: 6px;
            width: 72px;
            height: 64px;
            color: #D96B9B;
            opacity: 0.22;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .kpi-card:hover .card-watermark-lg,
        .group:hover .card-watermark-lg {
            opacity: 0.32;
            transform: scale(1.05);
        }

        /* 3D Floating Glass Tablet in Dark Burgundy Hero */
        .glass-tablet-3d-wrap {
            perspective: 900px;
            perspective-origin: center center;
        }
        .glass-tablet-3d {
            background: rgba(255, 255, 255, 0.12);
            border: 1.5px solid rgba(255, 255, 255, 0.28);
            box-shadow: 0 24px 48px -12px rgba(0, 0, 0, 0.45), inset 0 1px 2px rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 20px;
            transform: rotateY(-13deg) rotateX(7deg) rotateZ(-1deg);
            transform-style: preserve-3d;
            transition: transform 0.4s ease;
        }
        .glass-tablet-3d:hover {
            transform: rotateY(-8deg) rotateX(4deg) rotateZ(0deg) scale(1.02);
        }
    </style>
</head>
<body class="bg-[#FBF8FA] text-[#172B4D] font-sans antialiased">

@php
    $who = auth()->user();
    $safeName = $who?->name ?: 'Platform Super Admin';

    // Exact official Layanan Tenant reorganization matching specification
    $navSections = [
        ['label' => 'PUSAT KENDALI', 'links' => [
            ['label' => 'Dashboard Platform', 'url' => route('platform.dashboard'), 'icon' => 'grid', 'active' => request()->routeIs('platform.dashboard')],
            ['label' => 'Antrean Pendaftaran', 'url' => route('platform.registrations.index'), 'icon' => 'clipboard', 'active' => request()->routeIs('platform.registrations.*')],
            ['label' => 'Direktori Tenant', 'url' => route('platform.tenants.index'), 'icon' => 'building', 'active' => request()->routeIs('platform.tenants.*')],
        ]],
        ['label' => 'BISNIS & MONETISASI', 'links' => [
            ['label' => 'Tagihan Platform', 'url' => route('platform.billing.index'), 'icon' => 'credit-card', 'active' => request()->routeIs('platform.billing.*')],
            ['label' => 'Review Iklan', 'url' => route('platform.ads.index'), 'icon' => 'megaphone', 'active' => request()->routeIs('platform.ads.*')],
            ['label' => 'Pengembalian Dana', 'url' => route('platform.refunds.index'), 'icon' => 'refresh', 'active' => request()->routeIs('platform.refunds.*')],
            ['label' => 'Analitik Global', 'url' => route('platform.analytics.index'), 'icon' => 'chart', 'active' => request()->routeIs('platform.analytics.*')],
        ]],
        ['label' => 'LAYANAN TENANT', 'links' => [
            ['label' => 'Keluhan Owner', 'url' => route('platform.complaints.index'), 'icon' => 'message-square', 'active' => request()->routeIs('platform.complaints.*')],
            ['label' => 'Pusat Notifikasi & Broadcast', 'url' => route('platform.notifications.index'), 'icon' => 'bell', 'active' => request()->routeIs('platform.notifications.*')],
        ]],
        ['label' => 'TATA KELOLA', 'links' => [
            ['label' => 'Role & Hak Akses', 'url' => route('platform.roles.index'), 'icon' => 'users', 'active' => request()->routeIs('platform.roles.*')],
            ['label' => 'Log Aktivitas', 'url' => route('platform.audit.index'), 'icon' => 'history', 'active' => request()->routeIs('platform.audit.*')],
            ['label' => 'Pengaturan & Kebijakan', 'url' => route('platform.settings.index'), 'icon' => 'settings', 'active' => request()->routeIs('platform.settings.*')],
        ]],
    ];
@endphp

{{-- Global SVG Symbols (Exact Dashboard set) --}}
<svg class="hidden" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <symbol id="i-grid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol>
    <symbol id="i-clipboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M9 10h6M9 14h6"/></symbol>
    <symbol id="i-building" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 21v-5h6v5M8 7h1M15 7h1M8 11h1M15 11h1"/></symbol>
    <symbol id="i-credit-card" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></symbol>
    <symbol id="i-megaphone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/></symbol>
    <symbol id="i-refresh" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7v5h-5M4 17v-5h5M6.5 9A7 7 0 0 1 19 7l1 1M4 16l1 1a7 7 0 0 0 12.5-2"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M7 16l4-6 4 4 5-8"/></symbol>
    <symbol id="i-graduation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m2 9 10-5 10 5-10 5-10-5Zm2 5v5c0 2 4 3 8 3s8-1 8-3v-5M22 9v6"/></symbol>
    <symbol id="i-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2M16 3.13a4 4 0 0 1 0 7.75M22 21v-2a4 4 0 0 0-3-3.87"/></symbol>
    <symbol id="i-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9m7.3 13a2 2 0 0 1-3.4 0"/></symbol>
    <symbol id="i-message-square" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></symbol>
    <symbol id="i-history" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/></symbol>
    <symbol id="i-settings" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></symbol>
    <symbol id="i-activity" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></symbol>
    <symbol id="i-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-7-7 7 7-7 7"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></symbol>
    <symbol id="i-cross" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="i-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></symbol>
    <symbol id="i-layers" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 2 10 5-10 5L2 7l10-5Zm0 10 10 5-10 5-10-5 10-5Zm0 5 10 5-10 5-10-5 10-5Z"/></symbol>
    <symbol id="i-puzzle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.5 11H19V7c0-1.1-.9-2-2-2h-4V3.5a2.5 2.5 0 0 0-5 0V5H4c-1.1 0-1.99.9-1.99 2v3.8H3.5a2.5 2.5 0 0 1 0 5H2v3.8c0 1.1.9 2 2 2h3.8v-1.5a2.5 2.5 0 0 1 5 0V22H17c1.1 0 2-.9 2-2v-4h1.5a2.5 2.5 0 0 0 0-5z"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></symbol>
    <symbol id="i-wallet" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4M3 5v14a2 2 0 0 0 2 2h16v-5M18 12a2 2 0 1 0 0 4h3v-4h-3z"/></symbol>

    {{-- Large Watermarks --}}
    <symbol id="i-wm-store" viewBox="0 0 64 64" fill="currentColor">
        <path d="M8 24 L32 12 L56 24 L52 30 H12 Z M14 30 V52 H26 V38 H38 V52 H50 V30 Z M30 52 V42 H34 V52 Z" opacity="0.9"/>
        <rect x="18" y="34" width="8" height="8" rx="1"/>
        <rect x="38" y="34" width="8" height="8" rx="1"/>
    </symbol>
    <symbol id="i-wm-users" viewBox="0 0 64 64" fill="currentColor">
        <circle cx="32" cy="20" r="10"/>
        <circle cx="16" cy="26" r="8"/>
        <circle cx="48" cy="26" r="8"/>
        <path d="M18 52 C18 42 24 38 32 38 C40 38 46 42 46 52 Z"/>
        <path d="M6 52 C6 44 11 41 17 41 C15 45 15 49 16 52 Z"/>
        <path d="M58 52 C58 44 53 41 47 41 C49 45 49 49 48 52 Z"/>
    </symbol>
    <symbol id="i-wm-clipboard" viewBox="0 0 64 64" fill="currentColor">
        <rect x="14" y="14" width="36" height="44" rx="6"/>
        <rect x="22" y="8" width="20" height="10" rx="3" fill="#ffffff" fill-opacity="0.3"/>
        <path d="M24 34 L30 40 L42 26" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
    </symbol>
    <symbol id="i-wm-file-edit" viewBox="0 0 64 64" fill="currentColor">
        <path d="M12 12 C12 8.7 14.7 6 18 6 H38 L52 20 V52 C52 55.3 49.3 58 46 58 H18 C14.7 58 12 55.3 12 52 Z"/>
        <path d="M38 6 V20 H52 Z" fill="#ffffff" fill-opacity="0.25"/>
        <path d="M24 42 L40 26 L46 32 L30 48 L22 50 Z" fill="#ffffff" fill-opacity="0.4"/>
    </symbol>
    <symbol id="i-wm-ban" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round">
        <circle cx="32" cy="32" r="24"/>
        <line x1="15" y1="15" x2="49" y2="49"/>
    </symbol>
</svg>

<div class="n-app flex min-h-screen w-full bg-[#FBF8FA]">

    {{-- =========================================================================
         SIDEBAR (DEEP BURGUNDY LUXURY WITH PROFILE PILL MATCHING DASHBOARD)
         ========================================================================= --}}
    <aside class="n-sidebar"
           id="nayaraSidebar"
           aria-label="Navigasi Super Admin"
           style="background: linear-gradient(170deg, #4E0C2E 0%, #3B0922 45%, #250515 100%); width: 256px;">

        {{-- Brand Logo Header --}}
        <a class="n-brand flex items-center gap-3 px-5 py-4 border-b border-white/10 hover:bg-white/5 transition-colors group" href="{{ route('platform.dashboard') }}" aria-label="NAYARA GROUP — Dashboard Platform">
            <img src="{{ asset('images/nayara-logo.jpg') }}" alt="NAYARA GROUP" class="w-10 h-10 rounded-xl object-contain shadow-md shrink-0 border border-white/25 group-hover:scale-105 transition-transform">
            <div class="leading-none space-y-1 min-w-0">
                <span class="n-brand-title font-black text-sm tracking-wider text-white block uppercase font-sans">NAYARA GROUP</span>
                <span class="n-brand-subtitle text-[8.5px] text-[#FFD8D8] font-bold tracking-widest uppercase block font-sans" data-i18n="nav_super_admin">SUPER ADMIN PLATFORM</span>
            </div>
        </a>

        {{-- Profile Pill Card under Brand Header --}}
        <div class="px-3.5 pt-3.5">
            <div class="p-2.5 rounded-xl bg-white/5 border border-white/10 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-[#8E1B5C] border border-white/30 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                    {{ mb_strtoupper(mb_substr($safeName, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-xs font-bold text-white block truncate">{{ $safeName }}</span>
                    <span class="inline-block mt-0.5 text-[8.5px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-[#8E1B5C]/80 text-[#FFD8D8] border border-[#FFD8D8]/30" data-i18n="badge_super_admin">SUPER ADMIN</span>
                </div>
            </div>
        </div>

        {{-- Navigation Menu --}}
        <div class="n-sidebar-inner flex-1 overflow-y-auto px-3 py-3 space-y-3">
            @foreach ($navSections as $section)
                @php
                    $isAnyActive = collect($section['links'])->contains(fn($l) => !empty($l['active']));
                    $sectionSlug = Str::slug($section['label'], '_');
                    $storageKey = 'nayara_sb_sa_' . $sectionSlug;
                @endphp
                <div x-data="{ open: {{ $isAnyActive ? 'true' : "localStorage.getItem('{$storageKey}') !== 'false'" }} }" class="n-nav-section space-y-1">
                    <button type="button"
                            @click="open = !open; localStorage.setItem('{{ $storageKey }}', open)"
                            class="w-full flex items-center justify-between text-[9.5px] font-extrabold tracking-widest text-[#FFD8D8]/90 uppercase px-3 py-1 hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                            :aria-expanded="open.toString()">
                        <span data-i18n="sec_{{ $sectionSlug }}">{{ $section['label'] }}</span>
                        <svg class="w-3 h-3 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                             :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <nav x-show="open"
                         x-transition:enter="transition-all ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition-all ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="space-y-1 pt-0.5">
                        @foreach ($section['links'] as $link)
                            <a href="{{ $link['url'] }}" class="n-nav-link {{ !empty($link['active']) ? 'is-active' : '' }}" @if(!empty($link['active'])) aria-current="page" @endif>
                                <svg><use href="#i-{{ $link['icon'] }}"/></svg>
                                <span data-i18n="nav_{{ Str::slug($link['label'], '_') }}">{{ $link['label'] }}</span>
                                @if(str_contains($link['label'], 'Pendaftaran'))
                                    <span class="sr-only">Pendaftaran</span>
                                @endif
                                @if($link['label'] === 'Log Aktivitas')
                                    <span class="sr-only">Audit Trail</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                </div>
            @endforeach
        </div>

        {{-- Logout Foot --}}
        <div class="p-3.5 border-t border-white/10">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#F7E6EE]/80 hover:text-white hover:bg-white/10 transition-colors cursor-pointer">
                    <svg class="w-4 h-4 text-[#D96B9B] shrink-0"><use href="#i-logout"/></svg>
                    <span data-i18n="nav_logout">Keluar (Logout)</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Mobile Overlay --}}
    <div class="n-overlay fixed inset-0 bg-slate-900/50 z-30 lg:hidden" id="nayaraOverlay" hidden></div>

    {{-- =========================================================================
         MAIN APPLICATION WRAPPER & TOPBAR (EXACT MATCH WITH DASHBOARD)
         ========================================================================= --}}
    <div class="n-main-wrap flex-1 flex flex-col min-w-0 lg:ml-64 w-full lg:w-[calc(100%-16rem)] bg-[#FBF8FA]">

        {{-- ── TOPBAR ────────────────────────────────────────────────────────── --}}
        <header class="h-16 bg-white border-b border-[#E1CFD9]/70 px-5 sm:px-7 flex items-center justify-between sticky top-0 z-20 shadow-2xs">
            <div class="flex items-center gap-3">
                <button id="nayaraMenuToggle" type="button" class="lg:hidden p-1.5 rounded-lg text-[#68113F] hover:bg-rose-50" aria-label="Buka navigasi">
                    <svg class="w-5 h-5"><use href="#i-menu"/></svg>
                </button>
                <a href="{{ route('platform.dashboard') }}" class="flex items-center gap-2.5 hover:opacity-85 transition-opacity" title="Platform NAYARA — Live System">
                    <span class="text-sm font-bold text-[#172B4D]" data-i18n="topbar_platform">Platform NAYARA</span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        LIVE
                    </span>
                </a>
            </div>

            <div class="flex items-center gap-3 sm:gap-4">
                {{-- Interactive Notification Bell & Tasks Popover (Synchronized Across Platform) --}}
                @include('platform.partials.topbar-notifications')

                <div class="h-5 w-px bg-slate-200"></div>

                {{-- Interactive Language Switcher Pill: [ ID | EN ] --}}
                <div class="inline-flex items-center p-0.5 rounded-full bg-[#FAF0F4] border border-[#F0D5E2] text-[11px] font-bold shadow-2xs" id="langSwitcher" role="group" aria-label="Pilih Bahasa">
                    <button type="button" id="btnLangId" class="px-2.5 py-0.5 rounded-full transition-all text-xs font-bold cursor-pointer bg-[#8E1B5C] text-white shadow-2xs">ID</button>
                    <button type="button" id="btnLangEn" class="px-2.5 py-0.5 rounded-full transition-all text-xs font-bold cursor-pointer text-slate-600 hover:text-[#8E1B5C]">EN</button>
                </div>

                {{-- Interactive User Profile Menu ("Platform Super Admin" Clickable Button & Dropdown) --}}
                <div class="relative" id="superAdminProfileDropdown">
                    <button type="button" 
                            id="superAdminProfileBtn"
                            class="flex items-center gap-2 pl-2 sm:pl-3 border-l border-slate-200 hover:bg-slate-50 p-1.5 rounded-xl transition-all cursor-pointer group select-none"
                            aria-expanded="false"
                            aria-haspopup="true">
                        <div class="text-right hidden sm:block">
                            <span class="text-xs font-bold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors block leading-tight">{{ $safeName }}</span>
                            <span class="text-[10px] text-slate-400 block leading-tight" data-i18n="topbar_role">Super Admin Platform</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-[#8E1B5C] text-white flex items-center justify-center font-bold text-xs shadow-xs group-hover:scale-105 transition-transform">
                            {{ mb_strtoupper(mb_substr($safeName, 0, 1)) }}
                        </div>
                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-y-0.5 transition-all"><use href="#i-chevron"/></svg>
                    </button>

                    {{-- Dropdown Menu --}}
                    <div id="superAdminMenu" 
                         class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-[#F0E4EA] py-2 z-50 hidden transition-all duration-150"
                         role="menu">
                        <div class="px-4 py-3 border-b border-slate-100 bg-[#FFF9FB] rounded-t-2xl">
                            <p class="text-xs font-bold text-[#172B4D] truncate">{{ $safeName }}</p>
                            <p class="text-[10.5px] text-slate-500 truncate">{{ $who?->email ?? 'superadmin@nayara.test' }}</p>
                            <span class="inline-block mt-1.5 text-[8.5px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-[#8E1B5C] text-white" data-i18n="badge_super_admin">SUPER ADMIN PLATFORM</span>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('platform.settings.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-rose-50/50 hover:text-[#8E1B5C] transition-colors" role="menuitem">
                                <svg class="w-4 h-4 text-[#8E1B5C]"><use href="#i-settings"/></svg>
                                <span data-i18n="profile_settings">Pengaturan Platform</span>
                            </a>
                            <a href="{{ route('platform.roles.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-rose-50/50 hover:text-[#8E1B5C] transition-colors" role="menuitem">
                                <svg class="w-4 h-4 text-[#8E1B5C]"><use href="#i-users"/></svg>
                                <span data-i18n="profile_roles">Role &amp; Hak Akses</span>
                            </a>
                            <a href="{{ route('platform.audit.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-rose-50/50 hover:text-[#8E1B5C] transition-colors" role="menuitem">
                                <svg class="w-4 h-4 text-[#8E1B5C]"><use href="#i-history"/></svg>
                                <span data-i18n="profile_audit">Log Aktivitas</span>
                            </a>
                        </div>
                        <div class="border-t border-slate-100 pt-1">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 transition-colors font-medium cursor-pointer" role="menuitem">
                                    <svg class="w-4 h-4 text-rose-500"><use href="#i-logout"/></svg>
                                    <span data-i18n="profile_logout">Keluar (Logout)</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mx-5 sm:mx-7 mt-4 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium flex items-center gap-2.5">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mx-5 sm:mx-7 mt-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-medium flex items-center gap-2.5">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('info'))
            <div class="mx-5 sm:mx-7 mt-4 p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs sm:text-sm font-medium flex items-center gap-2.5">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        {{-- Page Content Slot --}}
        <main class="p-5 sm:p-6 lg:p-7 max-w-[1600px] w-full mx-auto" id="main-content">
            @yield('content')
        </main>
    </div>
</div>

{{-- Layout Interactive Scripts --}}
<script>
(() => {
    // Mobile navigation toggle
    const toggleBtn = document.getElementById('nayaraMenuToggle');
    const sidebar = document.getElementById('nayaraSidebar');
    const overlay = document.getElementById('nayaraOverlay');

    if (toggleBtn && sidebar && overlay) {
        const toggleMenu = () => {
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            if (isOpen) {
                sidebar.classList.add('-translate-x-full');
                overlay.hidden = true;
            } else {
                sidebar.classList.remove('-translate-x-full');
                overlay.hidden = false;
            }
        };

        toggleBtn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.hidden = true;
        });
    }

    // Topbar notification & profile dropdown handlers
    const notifBtn = document.getElementById('notificationDropdownBtn');
    const notifMenu = document.getElementById('notificationDropdownMenu');
    const notifWrap = document.getElementById('notificationDropdownWrap');

    const profileBtn = document.getElementById('superAdminProfileBtn');
    const profileMenu = document.getElementById('superAdminMenu');
    const profileWrap = document.getElementById('superAdminProfileDropdown');

    if (notifBtn && notifMenu && notifWrap) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (profileMenu) {
                profileMenu.classList.add('hidden');
                if (profileBtn) profileBtn.setAttribute('aria-expanded', 'false');
            }
            const isHidden = notifMenu.classList.contains('hidden');
            if (isHidden) {
                notifMenu.classList.remove('hidden');
                notifBtn.setAttribute('aria-expanded', 'true');
            } else {
                notifMenu.classList.add('hidden');
                notifBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    if (profileBtn && profileMenu && profileWrap) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (notifMenu) {
                notifMenu.classList.add('hidden');
                if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
            }
            const isHidden = profileMenu.classList.contains('hidden');
            if (isHidden) {
                profileMenu.classList.remove('hidden');
                profileBtn.setAttribute('aria-expanded', 'true');
            } else {
                profileMenu.classList.add('hidden');
                profileBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Dismiss on outside click
    document.addEventListener('click', (e) => {
        if (notifWrap && notifMenu && !notifWrap.contains(e.target)) {
            notifMenu.classList.add('hidden');
            if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
        }
        if (profileWrap && profileMenu && !profileWrap.contains(e.target)) {
            profileMenu.classList.add('hidden');
            if (profileBtn) profileBtn.setAttribute('aria-expanded', 'false');
        }
    });

    // Dismiss on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (notifMenu) notifMenu.classList.add('hidden');
            if (profileMenu) profileMenu.classList.add('hidden');
        }
    });

    // Global Bilingual Dictionary & Switcher
    window.nayaraTranslations = window.nayaraTranslations || {
        id: {
            topbar_platform: "Platform NAYARA",
            topbar_role: "Super Admin Platform",
            nav_super_admin: "SUPER ADMIN PLATFORM",
            badge_super_admin: "SUPER ADMIN",
            nav_logout: "Keluar (Logout)",
            profile_settings: "Pengaturan Platform",
            profile_roles: "Role & Hak Akses",
            profile_audit: "Log Aktivitas",
            profile_logout: "Keluar (Logout)",
            
            sec_pusat_kendali: "PUSAT KENDALI",
            sec_bisnis_monetisasi: "BISNIS & MONETISASI",
            sec_layanan_tenant: "LAYANAN TENANT",
            sec_tata_kelola: "TATA KELOLA",

            nav_dashboard_platform: "Dashboard Platform",
            nav_antrean_pendaftaran: "Antrean Pendaftaran",
            nav_direktori_tenant: "Direktori Tenant",
            nav_tagihan_platform: "Tagihan Platform",
            nav_review_iklan: "Review Iklan",
            nav_pengembalian_dana: "Pengembalian Dana",
            nav_analitik_global: "Analitik Global",
            nav_keluhan_owner: "Keluhan Owner",
            nav_pusat_notifikasi_broadcast: "Pusat Notifikasi & Broadcast",
            nav_role_hak_akses: "Role & Hak Akses",
            nav_log_aktivitas: "Log Aktivitas",
            nav_pengaturan_kebijakan: "Pengaturan & Kebijakan",

            notif_title: "Notifikasi & Tugas Super Admin",
            notif_subtitle: "Pemberitahuan tugas yang perlu dikerjakan",
            notif_tasks_incoming: "Tugas Masuk",
            notif_uptodate: "Up to Date",
            notif_badge_review: "Review Pendaftaran",
            notif_pending_title: "Pengajuan Baru Menunggu Review",
            notif_pending_desc: "Calon tenant mengajukan pendaftaran mandiri dan menunggu verifikasi.",
            notif_pending_action: "Tinjau Antrean Pendaftaran",
            notif_badge_revision: "Perlu Revisi",
            notif_revision_title: "Berkas Memerlukan Perbaikan",
            notif_revision_desc: "Data pendaftaran telah dikembalikan untuk diperbaiki oleh pemohon.",
            notif_revision_action: "Lihat Status Berkas",
            notif_badge_billing: "Tagihan Platform",
            notif_invoices_title: "Invoice Menunggu Verifikasi",
            notif_invoices_desc: "Bukti pembayaran langganan SaaS siap diverifikasi.",
            notif_invoices_action: "Verifikasi Sekarang",
            notif_badge_ads: "Review Iklan",
            notif_ads_title: "Kampanye Iklan Menunggu Review",
            notif_ads_desc: "Materi promosi tenant siap diverifikasi sebelum tayang di etalase.",
            notif_ads_action: "Moderasi Iklan Sekarang",
            notif_badge_refunds: "Pengembalian Dana",
            notif_refunds_title: "Pengajuan Refund Menunggu Pencairan",
            notif_refunds_desc: "Permintaan pencairan dana tenant siap diproses.",
            notif_refunds_action: "Proses Refund Sekarang",
            notif_badge_complaints: "Keluhan Tenant",
            notif_complaints_title: "Keluhan Owner Butuh Tindakan",
            notif_complaints_desc: "Tiket keluhan operasional atau kendala fitur memerlukan respon Super Admin.",
            notif_complaints_action: "Tanggapi Keluhan",
            notif_empty_title: "Tidak Ada Tugas Menunggu",
            notif_empty_desc: "Semua pengajuan pendaftaran dan tagihan telah bersih diproses.",
            notif_full_center: "Buka Pusat Notifikasi Lengkap",

            // Registrations Page (Antrean Pendaftaran)
            reg_badge: "PLATFORM OTORITAS \u203A PENDAFTARAN TENANT",
            reg_title: "Antrean Pendaftaran Tenant",
            reg_subtitle: "Tinjau pendaftaran tenant baru dan kelola status verifikasi.",

            kpi_pending: "Menunggu Review",
            kpi_pending_sub: "Pengajuan baru perlu verifikasi",
            kpi_revision: "Perlu Revisi",
            kpi_revision_sub: "Data pendaftaran perlu revisi",
            kpi_approved: "Disetujui",
            kpi_approved_sub: "Tenant aktif di ekosistem",
            kpi_rejected: "Ditolak",
            kpi_rejected_sub: "Pendaftaran tidak memenuhi syarat",
            kpi_total: "Total Pendaftaran",
            kpi_total_sub: "Akumulasi seluruh pengajuan",

            tab_pending: "Menunggu Review",
            tab_needs_revision: "Perlu Revisi",
            tab_approved: "Disetujui",
            tab_rejected: "Ditolak",
            tab_all: "Semua Pendaftaran",

            table_title: "Daftar Pendaftaran Masuk",
            showing_prefix: "Menampilkan",
            showing_from: "dari",
            showing_registrations: "pendaftaran",
            showing_zero: "Menampilkan 0 pendaftaran",
            btn_reset_search: "(Reset Pencarian)",
            search_placeholder: "Cari konveksi, PIC, email...",
            btn_search: "Cari",
            th_no: "No / ID",
            th_garment: "Nama Konveksi",
            th_owner: "Pemilik / PIC",
            th_date: "Tanggal Pengajuan",
            th_status: "Status",
            th_action: "Aksi",
            status_pending: "Menunggu Review",
            status_needs_revision: "Perlu Revisi",
            status_approved: "Disetujui",
            status_rejected: "Ditolak",
            btn_view_detail: "Lihat Detail",

            empty_title: "Belum ada " + "pendaftaran baru",
            empty_desc_search: "Tidak ditemukan " + "pendaftaran yang cocok dengan kata kunci.",
            empty_desc_all: "Belum ada " + "pendaftaran tenant.",
            empty_desc_category: "Belum ada " + "pengajuan pada kategori ini.",

            card_priority_title: "Prioritas Verifikasi",
            priority_item_1: "Tinjau pendaftaran terbaru",
            priority_item_2: "Pastikan kelengkapan data & dokumen",
            priority_item_3: "Berikan status verifikasi maksimal 1\u00D724 jam",

            card_today_title: "Ringkasan Hari Ini",
            today_pending: "Menunggu",
            today_revision: "Perlu Revisi",
            today_approved: "Disetujui",

            card_guide_title: "Panduan Review Cepat",
            guide_step_1: "Buka detail pendaftaran.",
            guide_step_2: "Verifikasi data konveksi, pemilik, dan dokumen.",
            guide_step_3: "Tentukan status: Setujui, Perlu Revisi, atau Tolak.",

            // Detail Pendaftaran (show.blade.php)
            show_badge: "PLATFORM OTORITAS \u203A DETAIL PENDAFTARAN",
            submitted_on: "Diajukan pada",
            btn_back_queue: "Kembali ke Antrean",
            detail_card_title: "Berkas Verifikasi Pendaftaran Tenant",
            detail_card_subtitle: "Periksa kelengkapan identitas pemilik dan informasi operasional konveksi sebelum memberikan keputusan.",
            sec_owner_identity: "Identitas Pemilik & Akun",
            sec_business_info: "Informasi Usaha Konveksi",
            label_owner_name: "Nama Pemilik (Owner)",
            label_business_email: "Email Bisnis / Akun",
            label_whatsapp: "Nomor WhatsApp / Telepon",
            label_company_name: "Nama Perusahaan / Konveksi",
            label_address: "Alamat Workshop / Workshop",
            label_notes: "Catatan Pengajuan",
            note_revision_title: "Catatan Perbaikan / Revisi yang Diminta:",
            note_rejection_title: "Alasan Penolakan:",
            btn_reject: "Tolak Pendaftaran",
            btn_request_revision: "Minta Perbaikan / Revisi",
            btn_approve_activate: "Setujui & Aktivasi Tenant (Free ERP)",
            audit_trail_title: "Riwayat Keputusan & Jejak Audit",
            audit_trail_subtitle: "Catatan kronologis tindakan dan status verifikasi dari pendaftaran ini.",
            audit_empty: "Belum ada riwayat tercatat.",

            // Direktori Tenant (tenants/index.blade.php)
            tenant_badge: "TENANT MANAGEMENT",
            tenant_title: "Direktori Tenant",
            tenant_subtitle: "Kelola seluruh konveksi hijab yang terdaftar di ekosistem platform NAYARA.",
            kpi_total_tenant: "TOTAL TENANT",
            kpi_total_tenant_sub: "Jumlah seluruh tenant yang terdaftar",
            kpi_active_tenant: "TENANT AKTIF",
            kpi_active_tenant_sub: "Tenant dengan status aktif",
            kpi_action_needed: "PERLU TINDAKAN",
            kpi_action_needed_sub: "Tenant yang membutuhkan tindak lanjut",
            kpi_inactive_tenant: "SUSPEND / NONAKTIF",
            kpi_inactive_tenant_sub: "Tenant yang ditangguhkan atau tidak aktif",
            filter_status_label: "Status Tenant",
            opt_all_status: "Semua Status",
            opt_active: "Aktif (ACTIVE)",
            opt_inactive: "Nonaktif (INACTIVE)",
            filter_sort_label: "Urutkan Berdasarkan",
            sort_latest: "Terbaru Terdaftar",
            sort_oldest: "Terlama Terdaftar",
            sort_name_asc: "Nama A–Z",
            sort_name_desc: "Nama Z–A",
            btn_filter: "Filter",
            btn_reset: "Reset",
            dir_table_title: "Direktori Konveksi Terdaftar",
            dir_table_subtitle: "Daftar seluruh tenant yang menjadi bagian dari ekosistem NAYARA.",
            empty_tenant_title: "Tidak ada tenant ditemukan",
            empty_tenant_desc: "Coba sesuaikan filter status atau urutan data untuk melihat konveksi lainnya.",
            th_tenant_code: "Kode Tenant",
            th_tenant_name: "Nama Tenant",
            th_public_slug: "Slug Publik",
            th_registered_since: "Terdaftar Sejak",
            btn_manage: "Kelola",
            badge_active: "Aktif",
            badge_inactive: "Nonaktif",
            showing_tenant: "tenant",

            // Detail Tenant (tenants/show.blade.php)
            back_to_tenants: "Kembali ke Direktori Tenant",
            tenant_detail_badge: "TENANT OTORITAS \u2022 DIREKTORI TENANT",
            tenant_detail_title: "Detail Tenant",
            tenant_detail_subtitle: "Kelola profil, layanan, dan informasi operasional tenant terdaftar di ekosistem NAYARA.",
            btn_manage_tenant: "Kelola Tenant",
            btn_edit_profile: "Edit Profil",
            btn_back_to_directory: "Kembali ke Direktori",
            badge_active_upper: "AKTIF",
            badge_inactive_upper: "NONAKTIF",
            status_active_val: "Aktif",
            status_inactive_val: "Nonaktif",
            kpi_det_total_tenant: "Total Tenant",
            kpi_det_total_tenant_sub: "Total tenant terdaftar di platform.",
            kpi_det_status: "Status",
            kpi_det_status_sub: "Tenant dalam status aktif dan dapat menggunakan layanan.",
            kpi_det_active_service: "Layanan Aktif",
            kpi_det_active_service_sub: "Total layanan yang sedang aktif.",
            kpi_det_action_needed: "Perlu Tindakan",
            kpi_det_action_needed_sub: "Tidak ada tindakan yang perlu dilakukan saat ini.",
            card_main_info_title: "Informasi Utama",
            lbl_tenant_code: "Kode Tenant",
            lbl_outlet_name: "Nama Konveksi / Outlet",
            lbl_public_slug: "Slug Publik",
            lbl_registered_date: "Tanggal Terdaftar",
            lbl_status: "Status",
            lbl_owner_pic: "Pemilik / PIC",
            card_contact_location_title: "Kontak & Lokasi",
            lbl_whatsapp_contact: "WhatsApp / Kontak",
            lbl_email: "Email",
            lbl_workshop_address: "Alamat Workshop",
            lbl_city_province: "Kota / Provinsi",
            lbl_website: "Website",
            card_service_sub_title: "Layanan & Langganan",
            th_det_service: "LAYANAN",
            th_det_package: "PAKET / DETAIL",
            th_det_status: "STATUS",
            th_det_valid_until: "BERLAKU HINGGA",
            th_det_action: "AKSI",
            card_summary_title: "Ringkasan Tenant",
            pill_public_domain: "Domain publik aktif",
            pill_free_tier: "Paket free tier",
            pill_upgrade_ready: "Siap upgrade premium",
            card_operational_notes_title: "Catatan Operasional",
            operational_notes_body: "Tenant aktif di ekosistem NAYARA dan siap untuk pengelolaan layanan, langganan, serta pengembangan ke paket premium sesuai kebutuhan.",

            // Review Iklan (ads/index.blade.php)
            ad_breadcrumb: "Review Iklan",
            ad_hero_badge: "REVIEW IKLAN",
            ad_hero_title: "Kelola & Tinjau Iklan dengan Mudah",
            ad_hero_subtitle: "Pastikan setiap iklan sesuai dengan kebijakan NAYARA untuk pengalaman pengguna yang lebih baik.",
            ad_btn_guide: "Lihat Panduan Review",
            kpi_ad_review: "PERLU REVIEW",
            kpi_ad_review_sub: "Menunggu verifikasi iklan masuk",
            kpi_ad_approved: "DISETUJUI",
            kpi_ad_approved_sub: "Iklan siap tayang",
            kpi_ad_active: "SEDANG TAYANG",
            kpi_ad_active_sub: "Iklan aktif di platform",
            kpi_ad_rejected: "DITOLAK",
            kpi_ad_rejected_sub: "Tidak sesuai kebijakan",
            kpi_ad_total: "TOTAL IKLAN",
            kpi_ad_total_sub: "Seluruh pengajuan iklan",
            tab_ad_pending: "Perlu Review",
            tab_ad_approved: "Disetujui",
            tab_ad_active: "Sedang Tayang",
            tab_ad_rejected: "Ditolak",
            tab_ad_all: "Semua Status",
            ad_filter_search_ph: "Cari nama iklan, tenant, atau produk...",
            ad_filter_type_all: "Semua Jenis Iklan",
            ad_filter_type_shop: "Iklan Toko (Highlight)",
            ad_filter_type_product: "Iklan Produk (Etalase)",
            ad_filter_tenant_all: "Semua Tenant",
            ad_filter_btn: "Filter",
            empty_ad_title: "Belum ada materi iklan",
            empty_ad_desc: "Tidak ada materi iklan yang sesuai dengan status atau filter saat ini.",
            th_ad_material: "Materi Iklan",
            th_ad_tenant: "Tenant",
            th_ad_type: "Jenis Iklan",
            th_ad_date: "Tanggal Pengajuan",
            th_ad_status: "Status",
            th_ad_action: "Aksi",
            card_priority_ad_title: "Prioritas Moderasi",
            prio_high_label: "Tinggi",
            prio_high_sub: "Iklan dengan sponsor aktif",
            prio_med_label: "Sedang",
            prio_med_sub: "Iklan promosi & kampanye tenant",
            prio_norm_label: "Normal",
            prio_norm_sub: "Pengajuan iklan umum",
            card_recent_ad_title: "Aktivitas Terbaru",
            link_all_activity: "Lihat Semua Aktivitas",

            // Role & Hak Akses (roles/index.blade.php)
            role_breadcrumb: "Role & Hak Akses",
            role_hero_badge: "TATA KELOLA KEAMANAN & OTORITAS",
            role_hero_title: "Role & Hak Akses Pengguna",
            role_hero_subtitle: "Kelola definisi role, kebijakan hak akses, dan batas kewenangan dalam ekosistem NAYARA.",
            role_pill_official: "6 Role Resmi",
            role_pill_policy: "Kebijakan Akses Platform",
            role_pill_isolation: "Isolasi Data Tenant",
            role_summary_title: "Ringkasan Role dalam Ekosistem NAYARA",
            role_summary_subtitle: "Enam tingkatan pengguna dengan fungsi dan batas kewenangan yang berbeda.",
            role_detail_title: "Detail Hak Akses per Role",
            role_detail_subtitle: "Lihat cakupan modul, kewenangan, dan batasan akses untuk setiap role.",
            role_access_scope: "Cakupan Akses",
            role_access_limits: "Batasan Akses",
            role_isolation_title: "Isolasi Data Tenant",
            role_isolation_subtitle: "Data setiap konveksi dikelola secara terpisah (tenant isolation) untuk menjaga keamanan dan privasi.",
            role_iso_restricted_title: "Akses Terbatas",
            role_iso_restricted_desc: "Pengguna hanya dapat mengakses data sesuai perannya.",
            role_iso_tenant_title: "Data Per Tenant",
            role_iso_tenant_desc: "Setiap tenant mengelola akun & operasionalnya sendiri (Owner, Admin, Gudang, Produksi).",
            role_iso_security_title: "Keamanan Terjaga",
            role_iso_security_desc: "Tidak ada akses silang antar-tenant.",
            role_admin_section_title: "Administrator Platform",
            role_admin_section_subtitle: "Daftar akun pengelola platform NAYARA (level Super Admin).",
            role_btn_add_admin: "Tambah Administrator",
            role_th_role: "Role Platform",
            role_th_scope: "Cakupan Otoritas",
            role_scope_val: "Platform Global (Non-Tenant)",
            role_th_registered: "Terdaftar",
            role_empty_admin: "Belum ada akun Administrator Platform tambahan.",

            // Setup & Training (services/index.blade.php)
            service_breadcrumb: "Setup & Pelatihan",
            service_hero_badge: "PLATFORM OTORITAS › SETUP & TRAINING",
            service_hero_title: "Manajemen Permintaan Setup & Training",
            service_hero_subtitle: "Kelola permintaan implementasi awal, migrasi master data, dan pelatihan staf konveksi dari para mitra toko.",
            service_btn_manage: "Kelola Permintaan",
            service_btn_guide: "Panduan Layanan",
            service_kpi_new: "PERMINTAAN BARU",
            service_kpi_new_sub: "Permintaan layanan menunggu verifikasi awal",
            service_kpi_scheduled: "DIJADWALKAN",
            service_kpi_scheduled_sub: "Layanan sudah dijadwalkan (setup / training)",
            service_kpi_progress: "SEDANG DIPROSES",
            service_kpi_progress_sub: "Implementasi, migrasi data, atau pelatihan berjalan",
            service_kpi_completed: "SELESAI TRAINING",
            service_kpi_completed_sub: "Layanan sudah selesai dan ditutup",
            service_table_title: "Daftar Permintaan Layanan",
            service_table_subtitle: "Kelola dan pantau seluruh permintaan setup, migrasi data, dan pelatihan dari tenant.",
            service_status_title: "Status Layanan",
            service_status_subtitle: "Distribusi status seluruh permintaan setup & training.",
            service_recent_title: "Aktivitas Terbaru",
            service_recent_subtitle: "Update aktivitas terkini terkait permintaan layanan.",

            // Refunds (refunds/index.blade.php)
            refund_hero_badge: "PLATFORM OTORITAS › PENCAIRAN REFUND",
            refund_hero_title: "Pencairan & Audit Refund Platform",
            refund_hero_subtitle: "Kelola pengembalian dana pembatalan iklan sesuai aturan Section U (Target SLA Operasional: 7 hari kerja).",
            refund_btn_settle: "Audit & Settlement Kas",
            refund_kpi_req: "PERMINTAAN MASUK",
            refund_kpi_req_sub: "Perlu persetujuan awal",
            refund_kpi_approved: "DISETUJUI (ANTRE TRANSFER)",
            refund_kpi_approved_sub: "Menunggu eksekusi transfer bank",
            refund_kpi_completed: "SELESAI DITRANSFER",
            refund_kpi_completed_sub: "Bukti transfer tercatat",
            refund_kpi_total: "TOTAL DANA DI-REFUND",
            refund_kpi_total_sub: "Dana platform yang dikembalikan",

            // Notifications & Broadcast (notifications/index.blade.php)
            bc_hero_badge: "PLATFORM OTORITAS",
            bc_hero_title: "Pusat Notifikasi & Broadcast Platform",
            bc_hero_subtitle: "Kelola notifikasi operasional Super Admin dan kirim pengumuman broadcast ke seluruh toko tenant dalam ekosistem NAYARA.",
            bc_btn_manage: "Kelola Notifikasi",
            bc_btn_create: "Buat Broadcast",
            bc_kpi_review: "MENUNGGU REVIEW",
            bc_kpi_review_sub: "Pendaftaran tenant baru",
            bc_kpi_revision: "PERLU REVISI",
            bc_kpi_revision_sub: "Menunggu perbaikan berkas",
            bc_kpi_billing: "TAGIHAN PLATFORM",
            bc_kpi_billing_sub: "Menunggu verifikasi bukti bayar",
            bc_kpi_ads_refund: "IKLAN & REFUND",
            bc_kpi_ads_refund_sub: "Moderasi materi iklan / refund",
            bc_tab_tasks: "Notifikasi & Tugas Masuk",
            bc_tab_broadcast: "Broadcast Pengumuman Tenant",
            bc_tab_activity: "Riwayat Aktivitas",
            bc_tasks_title: "Tugas Masuk Super Admin (Perlu Tindakan)",
            bc_tasks_subtitle: "Daftar item operasional platform yang memerlukan tindakan verifikasi, review, atau moderasi.",
            bc_tasks_view_all: "Lihat Semua",
            bc_tasks_empty_title: "Semua Tugas Operasional Telah Selesai",
            bc_tasks_empty_desc: "Tidak ada pengajuan pendaftaran, tagihan, iklan, atau refund yang tertunda saat ini.",
            bc_btn_review_reg: "Tinjau Pendaftaran",
            bc_btn_verify_bill: "Verifikasi Tagihan",
            bc_btn_review_ad: "Review Iklan",
            bc_btn_review_refund: "Review Refund",
            bc_btn_detail_revision: "Detail Revisi",
            bc_recent_title: "Broadcast Terbaru",
            bc_recent_subtitle: "Pengumuman yang dikirim ke tenant.",
            bc_recent_create: "Buat Broadcast",
            bc_status_sent: "Terkirim",
            bc_status_scheduled: "Terjadwal",
            bc_recent_empty: "Belum ada broadcast yang dikirim.",
            bc_template_title: "Template Broadcast",
            bc_template_subtitle: "Gunakan template siap pakai untuk mengirim pengumuman ke tenant dengan cepat dan konsisten.",
            bc_template_view_all: "Lihat Semua Template",
            bc_tmpl1_title: "Pengumuman Sistem",
            bc_tmpl1_desc: "Informasi operasional, pemeliharaan, atau pembaruan fitur platform.",
            bc_tmpl2_title: "Promosi Platform",
            bc_tmpl2_desc: "Informasi program promo, kampanye, atau kesempatan penjualan bagi tenant.",
            bc_tmpl3_title: "Pengingat Tagihan",
            bc_tmpl3_desc: "Notifikasi jatuh tempo, konfirmasi pembayaran, atau kebijakan tagihan platform.",
            bc_modal_title: "Kirim Broadcast Pengumuman Tenant",
            bc_modal_subtitle: "Pesan akan dikirim langsung ke notifikasi dashboard akun Owner seluruh konveksi mitra.",
            bc_modal_target: "Target Penerima",
            bc_opt_all: "Semua Tenant Aktif (Broadcast Global)",
            bc_opt_specific: "Pilih Satu Tenant Spesifik",
            bc_modal_category: "Kategori Pesan",
            bc_cat_announcement: "PENGUMUMAN (Umum)",
            bc_cat_maintenance: "PEMELIHARAAN SISTEM (Maintenance)",
            bc_cat_billing: "PERINGATAN TAGIHAN (Billing)",
            bc_cat_policy: "PEMBARUAN KEBIJAKAN (Policy)",
            bc_modal_subject: "Judul Notifikasi",
            bc_modal_message: "Isi Pesan Notifikasi",
            bc_modal_url: "Tautan Tindakan Tenant (Opsional)",
            bc_form_url_hint: "Tautan halaman di dashboard Owner yang akan dituju saat notifikasi diklik (khusus akun Owner).",
            bc_btn_send: "Kirim Notifikasi Sekarang",
            bc_btn_cancel: "Batal",
            bc_tab2_about_title: "Tentang Fitur Broadcast Tenant",
            bc_tab2_about_desc: "Fitur ini digunakan oleh Super Admin NAYARA Group untuk mengirimkan pengumuman resmi seperti jadwal pemeliharaan server, pembaruan kebijakan/SLA, atau peringatan billing yang akan langsung tampil di lonceng notifikasi dashboard Owner masing-masing konveksi hijab.",
            bc_tab2_total_sent: "TOTAL TERKIRIM",
            bc_tab2_unread: "BELUM DIBACA",
            bc_tab2_global: "BROADCAST GLOBAL",
            bc_tab2_targeted: "PESAN TERTARGET",
            bc_tab2_sub_total: "Akumulasi pesan siaran",
            bc_tab2_sub_unread: "Menunggu dibuka tenant",
            bc_tab2_sub_global: "Pesan ke seluruh toko",
            bc_tab2_sub_targeted: "Pesan privat ke outlet",
            bc_form_title: "Kirim Broadcast Baru",
            bc_form_subtitle: "Pesan akan langsung masuk ke lonceng notifikasi dashboard Owner tenant.",
            bc_form_outlet_select: "Pilih Outlet Tenant",
            bc_form_outlet_choose: "-- Pilih Outlet Konveksi --",
            bc_search_placeholder: "Cari judul atau isi notifikasi...",
            bc_filter_all_types: "Semua Kategori",
            bc_filter_all_targets: "Semua Penerima",
            bc_status_read: "Sudah Dibaca",
            bc_status_unread: "Belum Dibaca",
            bc_btn_action_link: "Buka Tautan Tindakan",
            bc_meta_tenant_target: "Tujuan Aksi Tenant:",
            bc_meta_no_action: "Informasi satu arah (tanpa pengalihan)",
            bc_global_tenant_label: "Broadcast Seluruh Tenant",
            bc_for_outlet: "Untuk:",
            bc_empty_filter_title: "Tidak Ada Notifikasi yang Cocok",
            bc_empty_filter_desc: "Coba ubah kata kunci pencarian atau filter kategori di atas.",
            bc_tab2_history_title: "Riwayat Notifikasi Terkirim",
            bc_tab2_history_desc: "Daftar pesan broadcast dan notifikasi sistem yang telah dikirim ke tenant.",
            bc_tab3_title: "Riwayat Aktivitas & Audit Trail",
            bc_tab3_desc: "Log kronologis tindakan verifikasi pendaftaran dan moderasi Super Admin.",

            // Audit Log Page (Log Aktivitas Platform)
            audit_hero_badge: "TATA KELOLA \u203A LOG AKTIVITAS",
            audit_hero_title: "Log Aktivitas & Riwayat Keputusan Platform",
            audit_hero_subtitle: "Mencatat seluruh aksi onboarding, revisi, persetujuan, penolakan, moderasi, refund, dan keputusan penting di platform NAYARA.",
            audit_immutable_badge: "Immutable Decision Log",
            audit_kpi_total: "TOTAL AKTIVITAS",
            audit_kpi_total_sub: "Seluruh aksi tercatat di platform",
            audit_kpi_pending: "MENUNGGU TINJAUAN",
            audit_kpi_pending_sub: "Aktivitas perlu verifikasi",
            audit_kpi_approved: "PERSETUJUAN TERCATAT",
            audit_kpi_approved_sub: "Keputusan disetujui platform",
            audit_kpi_rejected: "PENOLAKAN / REVISI",
            audit_kpi_rejected_sub: "Aktivitas ditolak atau diminta revisi",
            audit_recent_title: "Riwayat Aktivitas Terbaru",
            audit_recent_sub: "Aktivitas sistem, keputusan, dan perubahan status terkini di platform.",
            audit_btn_download: "Unduh Log (.csv)",
            audit_btn_reset: "Reset Filter",
            audit_view_all: "Lihat Semua Log",
            audit_tab_all: "Semua",
            audit_tab_registration: "Pendaftaran",
            audit_tab_ads: "Moderasi Iklan",
            audit_tab_refund: "Refund",
            audit_tab_billing: "Tagihan",
            audit_search_placeholder: "Cari tenant, aksi, aktor, atau catatan...",
            audit_status_all: "Semua Status",
            audit_status_pending: "Pending",
            audit_status_approved: "Disetujui",
            audit_status_rejected: "Ditolak",
            audit_status_revision: "Revisi",
            audit_period_all: "Semua Periode",
            audit_period_today: "Hari Ini",
            audit_period_7d: "7 Hari Terakhir",
            audit_period_30d: "30 Hari Terakhir",
            audit_btn_filter: "Filter",
            audit_th_time: "Waktu",
            audit_th_tenant: "Tenant",
            audit_th_action: "Aksi",
            audit_th_status: "Status",
            audit_th_actor: "Aktor",
            audit_th_notes: "Catatan & Detail",
            audit_tenant_reg: "Pendaftaran Tenant",
            audit_system_internal: "Sistem Internal",
            audit_action_submit_desc: "Tenant baru mendaftar.",
            audit_action_status_desc: "Perubahan status dicatat.",
            audit_act_submit: "Pendaftaran Diajukan",
            audit_act_request_revision: "Permintaan Revisi",
            audit_act_resubmit: "Revisi Diajukan Kembali",
            audit_act_approve: "Pendaftaran Disetujui",
            audit_act_reject: "Pendaftaran Ditolak",
            audit_pill_baru: "BARU",
            audit_pill_pending: "PENDING",
            audit_pill_approved: "DISETUJUI",
            audit_pill_rejected: "DITOLAK",
            audit_pill_needs_revision: "REVISI",
            audit_empty_title: "Belum Ada Riwayat Aktivitas",
            audit_empty_desc: "Tidak ada riwayat aktivitas yang sesuai dengan filter pencarian.",
            audit_summary_title: "Ringkasan Audit",
            audit_summary_sub: "Standar kepatuhan & integritas log.",
            audit_rule_1: "Log tercatat permanen (immutable) dan tidak dapat diubah.",
            audit_rule_2: "Setiap aktivitas terhubung dengan aktor (user/peninjau) yang jelas.",
            audit_rule_3: "Mencakup keputusan penting: persetujuan, penolakan, revisi, moderasi, dan refund.",
            audit_rule_4: "Mendukung tata kelola, kepatuhan, dan audit operasional platform NAYARA.",
            audit_quick_title: "Aksi Cepat",
            audit_quick_sub: "Pintasan operasional audit.",
            audit_download_title: "Unduh Riwayat Log",
            audit_download_sub: "Ekspor data log aktivitas (.xlsx / .csv)",
            audit_advanced_title: "Filter Lanjutan",
            audit_advanced_sub: "Atur rentang waktu, jenis aktivitas, dan aktor",
            audit_3d_verified: "Audit Trail Verified",
            audit_3d_tamper: "Tamper-Proof Records",
            audit_3d_tracked: "Actor Logged & Tracked",

            // Platform Settings Page (Pengaturan & Kebijakan)
            settings_hero_badge: "KONFIGURASI & KEBIJAKAN PLATFORM",
            settings_hero_title: "Pengaturan & Kebijakan NAYARA",
            settings_hero_subtitle: "Kelola harga, layanan, kebijakan komersial, dan konfigurasi seluruh ekosistem NAYARA.",
            settings_pill_active: "Platform Aktif",
            settings_pill_groups: "7 Kelompok Pengaturan",
            settings_badge_active: "Aktif",
            settings_kpi_sub_title: "Paket Langganan",
            settings_kpi_sub_desc: "Free & Premium",
            settings_kpi_addon_title: "Add-on Aktif",
            settings_kpi_addon_desc: "Fitur tambahan",
            settings_kpi_ads_title: "Iklan",
            settings_kpi_ads_desc: "Kebijakan Promosi",
            settings_kpi_sla_title: "SLA",
            settings_kpi_sla_desc: "Operasional",
            settings_section_title: "Kelola Pengaturan",
            settings_section_subtitle: "Pilih kategori pengaturan untuk mengelola detail kebijakan platform NAYARA.",
            settings_card_subscription_title: "Paket Langganan",
            settings_badge_commercial: "Komersial",
            settings_card_subscription_desc: "Atur harga, periode berlangganan, fitur paket Free & Premium, serta promo.",
            settings_card_capacity_title: "Kapasitas & Add-on",
            settings_badge_addon: "Fitur Tambahan",
            settings_card_capacity_desc: "Kelola harga slot etalase permanen dan add-on (mis. Financial Analytics).",
            settings_card_ads_title: "Promosi & Iklan",
            settings_badge_monetization: "Monetisasi",
            settings_card_ads_desc: "Atur tarif iklan, durasi penayangan, kuota, dan aturan promosi tenant.",
            settings_card_service_title: "Layanan Setup & Training",
            settings_badge_service: "Layanan",
            settings_card_service_desc: "Kelola tarif konsultasi, implementasi awal, migrasi data, dan pelatihan tenant.",
            settings_card_refund_title: "Kebijakan Refund",
            settings_badge_refund: "Kebijakan",
            settings_card_refund_desc: "Atur ketentuan pembatalan, persentase pengembalian, dan mekanisme pencairan.",
            settings_card_sla_title: "SLA Operasional",
            settings_badge_sla: "Operasional",
            settings_card_sla_desc: "Tetapkan batas waktu verifikasi, review, persetujuan, dan layanan platform.",
            settings_card_system_title: "Konfigurasi Sistem & Multi-Tenant",
            settings_badge_system: "Sistem",
            settings_card_system_desc: "Atur kebijakan isolasi data tenant, status fitur global, dan parameter operasional platform.",
            settings_btn_cancel: "Batal",
            settings_btn_save: "Simpan Perubahan",
            settings_modal_edit_prefix: "Kelola",

            // Owner Complaints Page (Layanan Tenant)
            complaint_hero_badge: "LAYANAN TENANT • SUPPORT DESK",
            complaint_hero_title: "Keluhan & Pusat Bantuan Owner",
            complaint_hero_subtitle: "Kelola pengaduan, pertanyaan, dan kendala yang disampaikan oleh pemilik perusahaan konveksi di ekosistem NAYARA.",
            complaint_pill_active: "Helpdesk Live",
            complaint_pill_sla: "SLA Respons 1x24 Jam",
            complaint_pill_privacy: "Privasi Tenant Terjaga",
            complaint_kpi_new_label: "KELUHAN BARU",
            complaint_kpi_new_desc: "Menunggu respon awal",
            complaint_kpi_progress_label: "SEDANG DIPROSES",
            complaint_kpi_progress_desc: "Dalam penanganan teknis",
            complaint_kpi_waiting_label: "MENUNGGU BALASAN",
            complaint_kpi_waiting_desc: "Menunggu jawaban owner",
            complaint_kpi_resolved_label: "KELUHAN SELESAI",
            complaint_kpi_resolved_desc: "Tiket tuntas ditutup",
            complaint_search_placeholder: "Cari ID keluhan, nama tenant, judul...",
            complaint_list_heading: "Daftar Keluhan Owner",
            complaint_empty_title: "Tidak Ada Keluhan Ditemukan",
            complaint_empty_desc: "Saat ini belum ada pengaduan atau pertanyaan yang diajukan oleh pemilik perusahaan konveksi di ekosistem platform NAYARA.",
            complaint_btn_detail: "Buka Keluhan"
        },
        en: {
            topbar_platform: "NAYARA Platform",
            topbar_role: "Platform Super Admin",
            nav_super_admin: "PLATFORM SUPER ADMIN",
            badge_super_admin: "SUPER ADMIN",
            nav_logout: "Sign Out (Logout)",
            profile_settings: "Platform Settings",
            profile_roles: "Roles & Permissions",
            profile_audit: "Activity Logs",
            profile_logout: "Sign Out (Logout)",

            sec_pusat_kendali: "CONTROL CENTER",
            sec_bisnis_monetisasi: "BUSINESS & MONETIZATION",
            sec_layanan_tenant: "TENANT SERVICES",
            sec_tata_kelola: "GOVERNANCE",

            nav_dashboard_platform: "Platform Dashboard",
            nav_antrean_pendaftaran: "Registration Queue",
            nav_direktori_tenant: "Tenant Directory",
            nav_tagihan_platform: "Platform Billing",
            nav_review_iklan: "Ad Reviews",
            nav_pengembalian_dana: "Refunds",
            nav_analitik_global: "Global Analytics",
            nav_keluhan_owner: "Owner Complaints",
            nav_pusat_notifikasi_broadcast: "Notification & Broadcast Center",
            nav_role_hak_akses: "Roles & Permissions",
            nav_log_aktivitas: "Activity Logs",
            nav_pengaturan_kebijakan: "Settings & Policies",

            notif_title: "Super Admin Notifications & Tasks",
            notif_subtitle: "Notices and actionable tasks requiring attention",
            notif_tasks_incoming: "Incoming Tasks",
            notif_uptodate: "Up to Date",
            notif_badge_review: "Registration Review",
            notif_pending_title: "New Applications Awaiting Review",
            notif_pending_desc: "Prospective tenants submitted registrations awaiting verification.",
            notif_pending_action: "Review Registration Queue",
            notif_badge_revision: "Needs Revision",
            notif_revision_title: "Documents Requiring Revision",
            notif_revision_desc: "Registration data returned for applicant correction.",
            notif_revision_action: "View Document Status",
            notif_badge_billing: "Platform Billing",
            notif_invoices_title: "Invoices Pending Verification",
            notif_invoices_desc: "SaaS subscription payment proofs ready for verification.",
            notif_invoices_action: "Verify Now",
            notif_badge_ads: "Ad Review",
            notif_ads_title: "Ad Campaigns Awaiting Review",
            notif_ads_desc: "Tenant promotional material ready for review before going live.",
            notif_ads_action: "Moderate Ads Now",
            notif_badge_refunds: "Refunds",
            notif_refunds_title: "Refund Requests Awaiting Approval",
            notif_refunds_desc: "Tenant refund disbursement requests ready for processing.",
            notif_refunds_action: "Process Refund Now",
            notif_badge_complaints: "Tenant Complaints",
            notif_complaints_title: "Owner Complaints Needing Attention",
            notif_complaints_desc: "Operational tickets or feature issues requiring Super Admin response.",
            notif_complaints_action: "Respond to Complaint",
            notif_empty_title: "No Pending Tasks",
            notif_empty_desc: "All registration submissions and invoices have been processed cleanly.",
            notif_full_center: "Open Full Notification Center",

            // Registrations Page (Antrean Pendaftaran)
            reg_badge: "PLATFORM AUTHORITY \u203A TENANT REGISTRATION",
            reg_title: "Tenant Registration Queue",
            reg_subtitle: "Review new tenant registrations and manage verification status.",

            kpi_pending: "Awaiting Review",
            kpi_pending_sub: "New submissions awaiting verification",
            kpi_revision: "Needs Revision",
            kpi_revision_sub: "Registration data needs revision",
            kpi_approved: "Approved",
            kpi_approved_sub: "Active tenants in ecosystem",
            kpi_rejected: "Rejected",
            kpi_rejected_sub: "Applications did not meet requirements",
            kpi_total: "Total Registrations",
            kpi_total_sub: "Accumulation of all applications",

            tab_pending: "Awaiting Review",
            tab_needs_revision: "Needs Revision",
            tab_approved: "Approved",
            tab_rejected: "Rejected",
            tab_all: "All Registrations",

            table_title: "Incoming Registration Queue",
            showing_prefix: "Showing",
            showing_from: "of",
            showing_registrations: "registrations",
            showing_zero: "Showing 0 registrations",
            btn_reset_search: "(Reset Search)",
            search_placeholder: "Search convection, PIC, email...",
            btn_search: "Search",
            th_no: "No / ID",
            th_garment: "Convection Name",
            th_owner: "Owner / PIC",
            th_date: "Submission Date",
            th_status: "Status",
            th_action: "Action",
            status_pending: "Awaiting Review",
            status_needs_revision: "Needs Revision",
            status_approved: "Approved",
            status_rejected: "Rejected",
            btn_view_detail: "View Details",

            empty_title: "No new registrations yet",
            empty_desc_search: "No registrations match the search keyword.",
            empty_desc_all: "No tenant registrations yet.",
            empty_desc_category: "No applications in this category yet.",

            card_priority_title: "Verification Priority",
            priority_item_1: "Review latest applications",
            priority_item_2: "Ensure completeness of data & documents",
            priority_item_3: "Provide verification status within 24 hours",

            card_today_title: "Today's Summary",
            today_pending: "Pending",
            today_revision: "Needs Revision",
            today_approved: "Approved",

            card_guide_title: "Quick Review Guide",
            guide_step_1: "Open registration details.",
            guide_step_2: "Verify convection, owner, and document data.",
            guide_step_3: "Decide status: Approve, Needs Revision, or Reject.",

            // Registration Details (show.blade.php)
            show_badge: "PLATFORM AUTHORITY \u203A REGISTRATION DETAILS",
            submitted_on: "Submitted on",
            btn_back_queue: "Back to Queue",
            detail_card_title: "Tenant Registration Verification Dossier",
            detail_card_subtitle: "Inspect owner identity and convection business profile before making a decision.",
            sec_owner_identity: "Owner & Account Identity",
            sec_business_info: "Convection Business Profile",
            label_owner_name: "Owner Name",
            label_business_email: "Business Email / Account",
            label_whatsapp: "WhatsApp / Phone Number",
            label_company_name: "Company / Convection Name",
            label_address: "Workshop / Factory Address",
            label_notes: "Application Notes",
            note_revision_title: "Revision / Correction Notes Requested:",
            note_rejection_title: "Rejection Reason:",
            btn_reject: "Reject Registration",
            btn_request_revision: "Request Revision",
            btn_approve_activate: "Approve & Activate Tenant (Free ERP)",
            audit_trail_title: "Decision History & Audit Trail",
            audit_trail_subtitle: "Chronological log of verification actions and status changes.",
            audit_empty: "No recorded history yet.",

            // Tenant Directory (tenants/index.blade.php)
            tenant_badge: "TENANT MANAGEMENT",
            tenant_title: "Tenant Directory",
            tenant_subtitle: "Manage all hijab confections registered in the NAYARA platform ecosystem.",
            kpi_total_tenant: "TOTAL TENANTS",
            kpi_total_tenant_sub: "Total registered tenants in ecosystem",
            kpi_active_tenant: "ACTIVE TENANTS",
            kpi_active_tenant_sub: "Tenants with active operating status",
            kpi_action_needed: "ACTION NEEDED",
            kpi_action_needed_sub: "Tenants requiring admin follow-up",
            kpi_inactive_tenant: "SUSPENDED / INACTIVE",
            kpi_inactive_tenant_sub: "Suspended or deactivated tenants",
            filter_status_label: "Tenant Status",
            opt_all_status: "All Statuses",
            opt_active: "Active (ACTIVE)",
            opt_inactive: "Inactive (INACTIVE)",
            filter_sort_label: "Sort By",
            sort_latest: "Latest Registered",
            sort_oldest: "Oldest Registered",
            sort_name_asc: "Name A–Z",
            sort_name_desc: "Name Z–A",
            btn_filter: "Filter",
            btn_reset: "Reset",
            dir_table_title: "Registered Confection Directory",
            dir_table_subtitle: "List of all confections operating within the NAYARA ecosystem.",
            empty_tenant_title: "No tenants found",
            empty_tenant_desc: "Try adjusting the status filter or sort order to view other confections.",
            th_tenant_code: "Tenant Code",
            th_tenant_name: "Tenant Name",
            th_public_slug: "Public Slug",
            th_registered_since: "Registered Since",
            btn_manage: "Manage",
            badge_active: "Active",
            badge_inactive: "Inactive",
            showing_tenant: "tenants",

            // Tenant Details (tenants/show.blade.php)
            back_to_tenants: "Back to Tenant Directory",
            tenant_detail_badge: "PLATFORM AUTHORITY \u2022 TENANT DIRECTORY",
            tenant_detail_title: "Tenant Details",
            tenant_detail_subtitle: "Manage profile, services, and operational information of registered tenants.",
            btn_manage_tenant: "Manage Tenant",
            btn_edit_profile: "Edit Profile",
            btn_back_to_directory: "Back to Directory",
            badge_active_upper: "ACTIVE",
            badge_inactive_upper: "INACTIVE",
            status_active_val: "Active",
            status_inactive_val: "Inactive",
            kpi_det_total_tenant: "Total Tenants",
            kpi_det_total_tenant_sub: "Total tenants registered on platform.",
            kpi_det_status: "Status",
            kpi_det_status_sub: "Tenant is in active status and eligible for services.",
            kpi_det_active_service: "Active Services",
            kpi_det_active_service_sub: "Total active service subscriptions.",
            kpi_det_action_needed: "Action Needed",
            kpi_det_action_needed_sub: "No pending actions required at this time.",
            card_main_info_title: "Main Information",
            lbl_tenant_code: "Tenant Code",
            lbl_outlet_name: "Confection / Outlet Name",
            lbl_public_slug: "Public Slug",
            lbl_registered_date: "Registration Date",
            lbl_status: "Status",
            lbl_owner_pic: "Owner / PIC",
            card_contact_location_title: "Contact & Location",
            lbl_whatsapp_contact: "WhatsApp / Contact",
            lbl_email: "Email",
            lbl_workshop_address: "Workshop Address",
            lbl_city_province: "City / Province",
            lbl_website: "Website",
            card_service_sub_title: "Services & Subscriptions",
            th_det_service: "SERVICE",
            th_det_package: "PACKAGE / DETAILS",
            th_det_status: "STATUS",
            th_det_valid_until: "VALID UNTIL",
            th_det_action: "ACTION",
            card_summary_title: "Tenant Summary",
            pill_public_domain: "Public domain active",
            pill_free_tier: "Free tier package",
            pill_upgrade_ready: "Premium upgrade ready",
            card_operational_notes_title: "Operational Notes",
            operational_notes_body: "Tenant is active in the NAYARA ecosystem and ready for service and subscription management, including premium tier upgrades as needed.",

            // Review Iklan (ads/index.blade.php)
            ad_breadcrumb: "Ad Reviews",
            ad_hero_badge: "AD REVIEW",
            ad_hero_title: "Manage & Review Ads Easily",
            ad_hero_subtitle: "Ensure every ad adheres to NAYARA policies for a better user experience.",
            ad_btn_guide: "View Review Guidelines",
            kpi_ad_review: "NEEDS REVIEW",
            kpi_ad_review_sub: "Awaiting incoming ad verification",
            kpi_ad_approved: "APPROVED",
            kpi_ad_approved_sub: "Ads ready to broadcast",
            kpi_ad_active: "CURRENTLY AIRING",
            kpi_ad_active_sub: "Active ads on platform",
            kpi_ad_rejected: "REJECTED",
            kpi_ad_rejected_sub: "Not adhering to policy",
            kpi_ad_total: "TOTAL ADS",
            kpi_ad_total_sub: "All ad submissions",
            tab_ad_pending: "Needs Review",
            tab_ad_approved: "Approved",
            tab_ad_active: "Currently Airing",
            tab_ad_rejected: "Rejected",
            tab_ad_all: "All Statuses",
            ad_filter_search_ph: "Search ad name, tenant, or product...",
            ad_filter_type_all: "All Ad Types",
            ad_filter_type_shop: "Store Ad (Highlight)",
            ad_filter_type_product: "Product Ad (Showcase)",
            ad_filter_tenant_all: "All Tenants",
            ad_filter_btn: "Filter",
            empty_ad_title: "No ad materials yet",
            empty_ad_desc: "No ad materials match the current status or filter.",
            th_ad_material: "Ad Material",
            th_ad_tenant: "Tenant",
            th_ad_type: "Ad Type",
            th_ad_date: "Submission Date",
            th_ad_status: "Status",
            th_ad_action: "Action",
            card_priority_ad_title: "Moderation Priority",
            prio_high_label: "High",
            prio_high_sub: "Ads with active sponsors",
            prio_med_label: "Medium",
            prio_med_sub: "Tenant promotional & campaign ads",
            prio_norm_label: "Normal",
            prio_norm_sub: "Standard ad submissions",
            card_recent_ad_title: "Recent Activity",
            link_all_activity: "View All Activities",

            // Role & Hak Akses (roles/index.blade.php)
            role_breadcrumb: "Roles & Permissions",
            role_hero_badge: "SECURITY & GOVERNANCE AUTHORITY",
            role_hero_title: "User Roles & Access Permissions",
            role_hero_subtitle: "Manage role definitions, access policies, and authority boundaries within the NAYARA ecosystem.",
            role_pill_official: "6 Official Roles",
            role_pill_policy: "Platform Access Policies",
            role_pill_isolation: "Tenant Data Isolation",
            role_summary_title: "Role Summary in NAYARA Ecosystem",
            role_summary_subtitle: "Six user levels with distinct functions and authority boundaries.",
            role_detail_title: "Access Permissions Detail per Role",
            role_detail_subtitle: "View module scope, authority, and access restrictions for each role.",
            role_access_scope: "Access Scope",
            role_access_limits: "Access Restrictions",
            role_isolation_title: "Tenant Data Isolation",
            role_isolation_subtitle: "Each confection's data is isolated independently (tenant isolation) to preserve security and privacy.",
            role_iso_restricted_title: "Restricted Access",
            role_iso_restricted_desc: "Users can only access data according to their assigned role.",
            role_iso_tenant_title: "Data Per Tenant",
            role_iso_tenant_desc: "Each tenant manages its own accounts and operations (Owner, Admin, Warehouse, Production).",
            role_iso_security_title: "Security Guaranteed",
            role_iso_security_desc: "No cross-tenant data access is permitted.",
            role_admin_section_title: "Platform Administrators",
            role_admin_section_subtitle: "List of NAYARA platform administrator accounts (Super Admin level).",
            role_btn_add_admin: "Add Administrator",
            role_th_role: "Platform Role",
            role_th_scope: "Authority Scope",
            role_scope_val: "Global Platform (Non-Tenant)",
            role_th_registered: "Registered",
            role_empty_admin: "No additional Platform Administrator accounts yet.",

            // Setup & Training (services/index.blade.php)
            service_breadcrumb: "Setup & Training",
            service_hero_badge: "PLATFORM AUTHORITY › SETUP & TRAINING",
            service_hero_title: "Setup & Training Request Management",
            service_hero_subtitle: "Manage initial implementation, master data migration, and confection staff training requests from partner stores.",
            service_btn_manage: "Manage Requests",
            service_btn_guide: "Service Guide",
            service_kpi_new: "NEW REQUESTS",
            service_kpi_new_sub: "Service requests awaiting initial review",
            service_kpi_scheduled: "SCHEDULED",
            service_kpi_scheduled_sub: "Services already scheduled (setup / training)",
            service_kpi_progress: "IN PROGRESS",
            service_kpi_progress_sub: "Implementation, data migration, or training ongoing",
            service_kpi_completed: "TRAINING COMPLETED",
            service_kpi_completed_sub: "Services completed and closed",
            service_table_title: "Service Request List",
            service_table_subtitle: "Manage and monitor all setup, data migration, and training requests from tenants.",
            service_status_title: "Service Status",
            service_status_subtitle: "Status distribution of all setup & training requests.",
            service_recent_title: "Recent Activity",
            service_recent_subtitle: "Latest activity updates regarding service requests.",

            // Refunds (refunds/index.blade.php)
            refund_hero_badge: "PLATFORM AUTHORITY › REFUND SETTLEMENT",
            refund_hero_title: "Platform Refund Settlement & Audit",
            refund_hero_subtitle: "Manage ad cancellation refund disbursements according to Section U (Operational Target SLA: 7 business days).",
            refund_btn_settle: "Cash Audit & Settlement",
            refund_kpi_req: "INCOMING REQUESTS",
            refund_kpi_req_sub: "Initial approval required",
            refund_kpi_approved: "APPROVED (TRANSFER QUEUE)",
            refund_kpi_approved_sub: "Awaiting bank transfer execution",
            refund_kpi_completed: "TRANSFERRED",
            refund_kpi_completed_sub: "Transfer proof recorded",
            refund_kpi_total: "TOTAL REFUNDED AMOUNT",
            refund_kpi_total_sub: "Total platform funds returned",

            // Notifications & Broadcast (notifications/index.blade.php)
            bc_hero_badge: "PLATFORM AUTHORITY",
            bc_hero_title: "Platform Notification & Broadcast Center",
            bc_hero_subtitle: "Manage Super Admin operational notifications and dispatch broadcast announcements to all tenant stores within the NAYARA ecosystem.",
            bc_btn_manage: "Manage Notifications",
            bc_btn_create: "Create Broadcast",
            bc_kpi_review: "AWAITING REVIEW",
            bc_kpi_review_sub: "New tenant registrations",
            bc_kpi_revision: "NEEDS REVISION",
            bc_kpi_revision_sub: "Awaiting document correction",
            bc_kpi_billing: "PLATFORM BILLING",
            bc_kpi_billing_sub: "Awaiting payment proof verification",
            bc_kpi_ads_refund: "ADS & REFUND",
            bc_kpi_ads_refund_sub: "Ad moderation & refund processing",
            bc_tab_tasks: "Notifications & Incoming Tasks",
            bc_tab_broadcast: "Tenant Broadcast Announcements",
            bc_tab_activity: "Activity History",
            bc_tasks_title: "Super Admin Inbound Tasks (Action Required)",
            bc_tasks_subtitle: "List of platform operational items requiring verification, review, or moderation.",
            bc_tasks_view_all: "View All",
            bc_tasks_empty_title: "All Operational Tasks Completed",
            bc_tasks_empty_desc: "There are no pending registrations, invoices, ads, or refunds at this time.",
            bc_btn_review_reg: "Review Registration",
            bc_btn_verify_bill: "Verify Billing",
            bc_btn_review_ad: "Review Ad",
            bc_btn_review_refund: "Review Refund",
            bc_btn_detail_revision: "Revision Details",
            bc_recent_title: "Recent Broadcasts",
            bc_recent_subtitle: "Announcements dispatched to tenants.",
            bc_recent_create: "Create Broadcast",
            bc_status_sent: "Sent",
            bc_status_scheduled: "Scheduled",
            bc_recent_empty: "No broadcasts sent yet.",
            bc_template_title: "Broadcast Templates",
            bc_template_subtitle: "Use ready-to-use templates to send announcements to tenants quickly and consistently.",
            bc_template_view_all: "View All Templates",
            bc_tmpl1_title: "System Announcement",
            bc_tmpl1_desc: "Operational information, maintenance, or platform feature updates.",
            bc_tmpl2_title: "Platform Promotion",
            bc_tmpl2_desc: "Information on promo programs, campaigns, or sales opportunities for tenants.",
            bc_tmpl3_title: "Billing Reminder",
            bc_tmpl3_desc: "Due date notifications, payment confirmations, or platform billing policies.",
            bc_modal_title: "Dispatch Tenant Broadcast Announcement",
            bc_modal_subtitle: "Messages will be delivered directly to the dashboard notifications of all partner convection Owners.",
            bc_modal_target: "Recipient Target",
            bc_opt_all: "All Active Tenants (Global Broadcast)",
            bc_opt_specific: "Select One Specific Tenant",
            bc_modal_category: "Message Category",
            bc_cat_announcement: "ANNOUNCEMENT (General)",
            bc_cat_maintenance: "SYSTEM MAINTENANCE",
            bc_cat_billing: "BILLING ALERT",
            bc_cat_policy: "POLICY UPDATE",
            bc_modal_subject: "Notification Title",
            bc_modal_message: "Notification Message Body",
            bc_modal_url: "Tenant Action URL (Optional)",
            bc_form_url_hint: "Target page in Owner dashboard when notification is clicked (Owner-scoped).",
            bc_btn_send: "Send Notification Now",
            bc_btn_cancel: "Cancel",
            bc_tab2_about_title: "About Tenant Broadcast Feature",
            bc_tab2_about_desc: "Used by NAYARA Group Super Admin to send official announcements such as server maintenance, policy updates, or billing reminders that appear in each convection Owner's dashboard.",
            bc_tab2_total_sent: "TOTAL SENT",
            bc_tab2_unread: "UNREAD",
            bc_tab2_global: "GLOBAL BROADCAST",
            bc_tab2_targeted: "TARGETED MESSAGES",
            bc_tab2_sub_total: "Accumulated broadcast messages",
            bc_tab2_sub_unread: "Awaiting tenant view",
            bc_tab2_sub_global: "Messages to all stores",
            bc_tab2_sub_targeted: "Private messages to outlet",
            bc_form_title: "Compose New Broadcast",
            bc_form_subtitle: "Message will appear immediately in the Owner tenant's dashboard notification bell.",
            bc_form_outlet_select: "Select Tenant Outlet",
            bc_form_outlet_choose: "-- Select Confection Outlet --",
            bc_search_placeholder: "Search notification title or content...",
            bc_filter_all_types: "All Categories",
            bc_filter_all_targets: "All Recipients",
            bc_status_read: "Read",
            bc_status_unread: "Unread",
            bc_btn_action_link: "Open Action Link",
            bc_meta_tenant_target: "Tenant Action Target:",
            bc_meta_no_action: "Direct notice (no action redirect)",
            bc_global_tenant_label: "Broadcast to All Tenants",
            bc_for_outlet: "For:",
            bc_empty_filter_title: "No Matching Notifications",
            bc_empty_filter_desc: "Try adjusting the search keyword or category filter above.",
            bc_tab2_history_title: "Sent Notifications History",
            bc_tab2_history_desc: "List of broadcast messages and system notifications delivered to tenants.",
            bc_tab3_title: "Activity History & Audit Trail",
            bc_tab3_desc: "Chronological log of registration verification and moderation actions by Super Admin.",

            // Audit Log Page (Activity Logs)
            audit_hero_badge: "GOVERNANCE \u203A ACTIVITY LOGS",
            audit_hero_title: "Activity Logs & Platform Decision History",
            audit_hero_subtitle: "Records all onboarding actions, revisions, approvals, rejections, moderation, refunds, and critical decisions across NAYARA platform.",
            audit_immutable_badge: "Immutable Decision Log",
            audit_kpi_total: "TOTAL ACTIVITIES",
            audit_kpi_total_sub: "All recorded platform actions",
            audit_kpi_pending: "AWAITING REVIEW",
            audit_kpi_pending_sub: "Activities requiring verification",
            audit_kpi_approved: "APPROVED DECISIONS",
            audit_kpi_approved_sub: "Approved platform decisions",
            audit_kpi_rejected: "REJECTIONS / REVISIONS",
            audit_kpi_rejected_sub: "Rejected or revision requested actions",
            audit_recent_title: "Recent Activity History",
            audit_recent_sub: "System activities, decisions, and latest status changes across the platform.",
            audit_btn_download: "Download Log (.csv)",
            audit_btn_reset: "Reset Filter",
            audit_view_all: "View All Logs",
            audit_tab_all: "All",
            audit_tab_registration: "Registration",
            audit_tab_ads: "Ad Moderation",
            audit_tab_refund: "Refund",
            audit_tab_billing: "Billing",
            audit_search_placeholder: "Search tenant, action, actor, or notes...",
            audit_status_all: "All Statuses",
            audit_status_pending: "Pending",
            audit_status_approved: "Approved",
            audit_status_rejected: "Rejected",
            audit_status_revision: "Revision",
            audit_period_all: "All Periods",
            audit_period_today: "Today",
            audit_period_7d: "Last 7 Days",
            audit_period_30d: "Last 30 Days",
            audit_btn_filter: "Filter",
            audit_th_time: "Time",
            audit_th_tenant: "Tenant",
            audit_th_action: "Action",
            audit_th_status: "Status",
            audit_th_actor: "Actor",
            audit_th_notes: "Notes & Details",
            audit_tenant_reg: "Tenant Registration",
            audit_system_internal: "Internal System",
            audit_action_submit_desc: "New tenant registered.",
            audit_action_status_desc: "Status change recorded.",
            audit_act_submit: "Registration Submitted",
            audit_act_request_revision: "Revision Requested",
            audit_act_resubmit: "Revision Resubmitted",
            audit_act_approve: "Registration Approved",
            audit_act_reject: "Registration Rejected",
            audit_pill_baru: "NEW",
            audit_pill_pending: "PENDING",
            audit_pill_approved: "APPROVED",
            audit_pill_rejected: "REJECTED",
            audit_pill_needs_revision: "REVISION",
            audit_empty_title: "No Activity History Found",
            audit_empty_desc: "There are no activity records matching your current search filters.",
            audit_summary_title: "Audit Summary",
            audit_summary_sub: "Compliance standards & log integrity.",
            audit_rule_1: "Logs are permanently recorded (immutable) and cannot be tampered with.",
            audit_rule_2: "Every activity is strictly linked to an identifiable actor (user/reviewer).",
            audit_rule_3: "Covers critical decisions: approvals, rejections, revisions, moderation, and refunds.",
            audit_rule_4: "Supports NAYARA platform governance, compliance, and operational auditing.",
            audit_quick_title: "Quick Actions",
            audit_quick_sub: "Audit operational shortcuts.",
            audit_download_title: "Download Activity Log",
            audit_download_sub: "Export activity log data (.xlsx / .csv)",
            audit_advanced_title: "Advanced Filters",
            audit_advanced_sub: "Configure date range, activity type, and actor",
            audit_3d_verified: "Audit Trail Verified",
            audit_3d_tamper: "Tamper-Proof Records",
            audit_3d_tracked: "Actor Logged & Tracked",

            // Platform Settings Page (Pengaturan & Kebijakan)
            settings_hero_badge: "KONFIGURASI & KEBIJAKAN PLATFORM",
            settings_hero_title: "Pengaturan & Kebijakan NAYARA",
            settings_hero_subtitle: "Kelola harga, layanan, kebijakan komersial, dan konfigurasi seluruh ekosistem NAYARA.",
            settings_pill_active: "Platform Aktif",
            settings_pill_groups: "7 Kelompok Pengaturan",
            settings_badge_active: "Aktif",
            settings_kpi_sub_title: "Paket Langganan",
            settings_kpi_sub_desc: "Free & Premium",
            settings_kpi_addon_title: "Add-on Aktif",
            settings_kpi_addon_desc: "Fitur tambahan",
            settings_kpi_ads_title: "Iklan",
            settings_kpi_ads_desc: "Kebijakan Promosi",
            settings_kpi_sla_title: "SLA",
            settings_kpi_sla_desc: "Operasional",
            settings_section_title: "Kelola Pengaturan",
            settings_section_subtitle: "Pilih kategori pengaturan untuk mengelola detail kebijakan platform NAYARA.",
            settings_card_subscription_title: "Paket Langganan",
            settings_badge_commercial: "Komersial",
            settings_card_subscription_desc: "Atur harga, periode berlangganan, fitur paket Free & Premium, serta promo.",
            settings_card_capacity_title: "Kapasitas & Add-on",
            settings_badge_addon: "Fitur Tambahan",
            settings_card_capacity_desc: "Kelola harga slot etalase permanen dan add-on (mis. Financial Analytics).",
            settings_card_ads_title: "Promosi & Iklan",
            settings_badge_monetization: "Monetisasi",
            settings_card_ads_desc: "Atur tarif iklan, durasi penayangan, kuota, dan aturan promosi tenant.",
            settings_card_service_title: "Layanan Setup & Training",
            settings_badge_service: "Layanan",
            settings_card_service_desc: "Kelola tarif konsultasi, implementasi awal, migrasi data, dan pelatihan tenant.",
            settings_card_refund_title: "Kebijakan Refund",
            settings_badge_refund: "Kebijakan",
            settings_card_refund_desc: "Atur ketentuan pembatalan, persentase pengembalian, dan mekanisme pencairan.",
            settings_card_sla_title: "SLA Operasional",
            settings_badge_sla: "Operasional",
            settings_card_sla_desc: "Tetapkan batas waktu verifikasi, review, persetujuan, dan layanan platform.",
            settings_card_system_title: "Konfigurasi Sistem & Multi-Tenant",
            settings_badge_system: "Sistem",
            settings_card_system_desc: "Atur kebijakan isolasi data tenant, status fitur global, dan parameter operasional platform.",
            settings_btn_cancel: "Cancel",
            settings_btn_save: "Save Changes",
            settings_modal_edit_prefix: "Manage",

            // Platform Settings Page (Settings & Policies)
            settings_hero_badge: "PLATFORM CONFIGURATION & POLICIES",
            settings_hero_title: "NAYARA Settings & Policies",
            settings_hero_subtitle: "Manage pricing, services, commercial policies, and configuration across the NAYARA ecosystem.",
            settings_pill_active: "Platform Active",
            settings_pill_groups: "7 Setting Categories",
            settings_badge_active: "Active",
            settings_kpi_sub_title: "Subscription Plans",
            settings_kpi_sub_desc: "Free & Premium",
            settings_kpi_addon_title: "Active Add-ons",
            settings_kpi_addon_desc: "Additional features",
            settings_kpi_ads_title: "Advertisements",
            settings_kpi_ads_desc: "Promotion Policies",
            settings_kpi_sla_title: "SLA",
            settings_kpi_sla_desc: "Operational",
            settings_section_title: "Manage Settings",
            settings_section_subtitle: "Select a setting category to manage NAYARA platform policy details.",
            settings_card_subscription_title: "Subscription Plans",
            settings_badge_commercial: "Commercial",
            settings_card_subscription_desc: "Configure pricing, subscription periods, Free & Premium features, and promos.",
            settings_card_capacity_title: "Capacity & Add-ons",
            settings_badge_addon: "Add-on Features",
            settings_card_capacity_desc: "Manage permanent showcase slot prices and add-ons (e.g. Financial Analytics).",
            settings_card_ads_title: "Promotions & Ads",
            settings_badge_monetization: "Monetization",
            settings_card_ads_desc: "Configure ad rates, airing duration, quotas, and tenant promotion guidelines.",
            settings_card_service_title: "Setup & Training Services",
            settings_badge_service: "Services",
            settings_card_service_desc: "Manage consultation rates, initial rollout, data migration, and tenant training.",
            settings_card_refund_title: "Refund Policy",
            settings_badge_refund: "Policy",
            settings_card_refund_desc: "Set cancellation rules, refund percentages, and payout mechanisms.",
            settings_card_sla_title: "Operational SLA",
            settings_badge_sla: "Operations",
            settings_card_sla_desc: "Set time thresholds for verification, review, approval, and platform services.",
            settings_card_system_title: "System & Multi-Tenant Configuration",
            settings_badge_system: "System",
            settings_card_system_desc: "Set tenant data isolation policies, global feature toggles, and platform operational parameters.",

            // Owner Complaints Page (Tenant Services)
            complaint_hero_badge: "TENANT SERVICES • SUPPORT DESK",
            complaint_hero_title: "Owner Complaints & Help Center",
            complaint_hero_subtitle: "Manage inquiries, questions, and issues submitted by confection company owners across the NAYARA ecosystem.",
            complaint_pill_active: "Helpdesk Live",
            complaint_pill_sla: "1x24h Response SLA",
            complaint_pill_privacy: "Tenant Privacy Preserved",
            complaint_kpi_new_label: "NEW COMPLAINTS",
            complaint_kpi_new_desc: "Awaiting initial response",
            complaint_kpi_progress_label: "IN PROGRESS",
            complaint_kpi_progress_desc: "In technical handling",
            complaint_kpi_waiting_label: "AWAITING OWNER",
            complaint_kpi_waiting_desc: "Waiting for owner response",
            complaint_kpi_resolved_label: "RESOLVED",
            complaint_kpi_resolved_desc: "Tickets resolved and closed",
            complaint_search_placeholder: "Search complaint ID, tenant name, title...",
            complaint_list_heading: "Owner Complaints List",
            complaint_empty_title: "No Complaints Found",
            complaint_empty_desc: "There are currently no complaints or inquiries submitted by tenant owners in the NAYARA platform ecosystem.",
            complaint_btn_detail: "Open Complaint"
        }
    };

    window.setNayaraLanguage = function(lang) {
        if (!window.nayaraTranslations[lang]) return;

        const btnId = document.getElementById('btnLangId');
        const btnEn = document.getElementById('btnLangEn');

        if (btnId && btnEn) {
            if (lang === 'id') {
                btnId.className = 'px-2.5 py-0.5 rounded-full transition-all text-xs font-bold cursor-pointer bg-[#8E1B5C] text-white shadow-2xs';
                btnEn.className = 'px-2.5 py-0.5 rounded-full transition-all text-xs font-bold cursor-pointer text-slate-600 hover:text-[#8E1B5C] bg-transparent';
            } else {
                btnEn.className = 'px-2.5 py-0.5 rounded-full transition-all text-xs font-bold cursor-pointer bg-[#8E1B5C] text-white shadow-2xs';
                btnId.className = 'px-2.5 py-0.5 rounded-full transition-all text-xs font-bold cursor-pointer text-slate-600 hover:text-[#8E1B5C] bg-transparent';
            }
        }

        document.documentElement.lang = lang;
        try {
            localStorage.setItem('nayara_lang', lang);
        } catch (e) {}

        const dict = window.nayaraTranslations[lang];
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (dict && dict[key] !== undefined) {
                el.textContent = dict[key];
            }
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (dict && dict[key] !== undefined) {
                el.setAttribute('placeholder', dict[key]);
            }
        });

        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            const key = el.getAttribute('data-i18n-title');
            if (dict && dict[key] !== undefined) {
                el.setAttribute('title', dict[key]);
            }
        });

        document.dispatchEvent(new CustomEvent('nayara:lang-changed', { detail: { lang } }));
    };

    const btnId = document.getElementById('btnLangId');
    const btnEn = document.getElementById('btnLangEn');
    if (btnId && btnEn) {
        btnId.addEventListener('click', () => window.setNayaraLanguage('id'));
        btnEn.addEventListener('click', () => window.setNayaraLanguage('en'));
    }

    // Auto-initialize from saved preference
    try {
        const savedLang = localStorage.getItem('nayara_lang');
        if (savedLang === 'en') {
            window.setNayaraLanguage('en');
        }
    } catch (e) {}
})();
</script>

</body>
</html>
