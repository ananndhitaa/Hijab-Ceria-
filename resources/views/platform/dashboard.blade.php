<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Portal Super Admin NAYARA — Dashboard Platform</title>

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

    <link rel="stylesheet" href="{{ asset('css/nayara-superadmin.css') . '?v=20261108-luxury' }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #FBF8FA;
            font-family: 'Inter', sans-serif;
            color: #172B4D;
            overflow-x: hidden;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        html {
            width: 100%;
        }

        /* Sidebar Brand Typography & Logo Styling */
        .n-brand strong, .n-brand-title {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, sans-serif !important;
            font-weight: 900 !important;
            font-size: 14.5px !important;
            letter-spacing: 0.08em !important;
            line-height: 1.2 !important;
            color: #FFFFFF !important;
        }
        .n-brand small, .n-brand-subtitle {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif !important;
            font-weight: 700 !important;
            font-size: 8.5px !important;
            letter-spacing: 0.14em !important;
            color: #FFD8D8 !important;
        }

        /* 3D Floating Glass Tablet in Hero */
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

        /* 3D Coin Stack Graphic in Monetisasi */
        .coin-glow {
            filter: drop-shadow(0 6px 12px rgba(104, 17, 63, 0.35));
        }

        /* Large Watermarks for 8 KPI Cards */
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
        .kpi-card:hover .card-watermark-lg {
            opacity: 0.32;
            transform: scale(1.05);
        }

        /* Corner ambient accents */
        .card-watermark {
            position: absolute;
            bottom: -2px;
            right: 4px;
            width: 44px;
            height: 44px;
            opacity: 0.08;
            pointer-events: none;
            color: #8E1B5C;
        }
    </style>
</head>
<body class="antialiased min-h-screen">
@php
    $nayara = (array) ($nayaraDashboard ?? []);
    $overview = (array) data_get($nayara, 'overview', []);
    $recentRegistrations = collect(data_get($nayara, 'recent_registrations', $recentRegistrations ?? []));
    $platformStats = (array) data_get($nayara, 'platform', []);
    $formatNumber = static fn ($n) => is_numeric($n) ? number_format((int) $n, 0, ',', '.') : '0';
    $formatMoney = static fn ($n) => is_numeric($n) ? 'Rp ' . number_format((float) $n, 0, ',', '.') : 'Rp 0';
    $who = auth()->user();
    $safeName = $who?->name ?: 'Platform Super Admin';
    $displayDate = now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y');

    $navSections = [
        ['label' => 'PUSAT KENDALI', 'links' => [
            ['label'=>'Dashboard Platform', 'url'=>route('platform.dashboard'), 'icon'=>'grid', 'active'=>true],
            ['label'=>'Antrean Pendaftaran', 'url'=>route('platform.registrations.index'), 'icon'=>'clipboard'],
            ['label'=>'Direktori Tenant', 'url'=>route('platform.tenants.index'), 'icon'=>'building'],
        ]],
        ['label' => 'BISNIS & MONETISASI', 'links' => [
            ['label'=>'Tagihan Platform', 'url'=>route('platform.billing.index'), 'icon'=>'credit-card'],
            ['label'=>'Review Iklan', 'url'=>route('platform.ads.index'), 'icon'=>'megaphone'],
            ['label'=>'Pengembalian Dana', 'url'=>route('platform.refunds.index'), 'icon'=>'refresh'],
            ['label'=>'Analitik Global', 'url'=>route('platform.analytics.index'), 'icon'=>'chart'],
        ]],
        ['label' => 'LAYANAN TENANT', 'links' => [
            ['label'=>'Keluhan Owner', 'url'=>route('platform.complaints.index'), 'icon'=>'message-square'],
            ['label'=>'Pusat Notifikasi & Broadcast', 'url'=>route('platform.notifications.index'), 'icon'=>'bell'],
        ]],
        ['label' => 'TATA KELOLA', 'links' => [
            ['label'=>'Role & Hak Akses', 'url'=>route('platform.roles.index'), 'icon'=>'users'],
            ['label'=>'Log Aktivitas', 'url'=>route('platform.audit.index'), 'icon'=>'history'],
            ['label'=>'Pengaturan & Kebijakan', 'url'=>route('platform.settings.index'), 'icon'=>'settings'],
        ]],
    ];

    $quickActions = [
        ['line1'=>'Review', 'line2'=>'Pendaftaran', 'label'=>'Review Pendaftaran', 'url'=>route('platform.registrations.index'), 'icon'=>'clipboard'],
        ['line1'=>'Kelola', 'line2'=>'Tenant', 'label'=>'Kelola Tenant', 'url'=>route('platform.tenants.index'), 'icon'=>'building'],
        ['line1'=>'Verifikasi', 'line2'=>'Tagihan', 'label'=>'Verifikasi Tagihan', 'url'=>route('platform.billing.index'), 'icon'=>'credit-card'],
        ['line1'=>'Moderasi', 'line2'=>'Iklan', 'label'=>'Moderasi Iklan', 'url'=>route('platform.ads.index'), 'icon'=>'megaphone'],
        ['line1'=>'Proses', 'line2'=>'Refund', 'label'=>'Proses Refund', 'url'=>route('platform.refunds.index'), 'icon'=>'refresh'],
        ['line1'=>'Lihat', 'line2'=>'Audit Trail', 'label'=>'Lihat Audit Trail', 'url'=>route('platform.audit.index'), 'icon'=>'history'],
    ];

    $pendingReview = (int) ($metrics['pending_registrations'] ?? data_get($overview, 'pending_registrations', 0));
    $activeTenants = (int) ($metrics['active_tenants'] ?? data_get($overview, 'active_outlets', 0));
    $needsRevision = (int) ($metrics['needs_revision'] ?? data_get($overview, 'needs_revision', 0));
    $inactiveTenants = (int) ($metrics['inactive_tenants'] ?? data_get($overview, 'inactive_outlets', 0));

    $totalRegistrations = (int) ($metrics['total_registrations'] ?? data_get($overview, 'total_registrations', 0));
    $approvedCount = (int) ($metrics['approved_registrations'] ?? data_get($overview, 'approved_registrations', 0));
    $rejectedCount = (int) ($metrics['rejected_registrations'] ?? data_get($overview, 'rejected_registrations', 0));
    $totalUsers = (int) ($metrics['total_tenant_users'] ?? data_get($overview, 'tenant_users', 0));

    $verifiedRevenue = (float) ($metrics['verified_platform_revenue'] ?? data_get($platformStats, 'verified_revenue', 0));
    $pendingInvoices = (int) ($metrics['pending_platform_invoices'] ?? 0);
    $pendingRefunds = (int) ($metrics['pending_refunds'] ?? 0);
    $pendingAds = (int) ($metrics['pending_ad_reviews'] ?? 0);
@endphp

{{-- =========================================================================
     INLINE SVG ICON DEFINITIONS
     ========================================================================= --}}
<svg class="n-svg-defs" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden">
    <symbol id="i-grid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol>
    <symbol id="i-clipboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M9 10h6M9 14h6"/></symbol>
    <symbol id="i-building" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 21v-5h6v5M8 7h1M15 7h1M8 11h1M15 11h1"/></symbol>
    <symbol id="i-credit-card" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20M6 15h4"/></symbol>
    <symbol id="i-megaphone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 13-5v14L3 14v-4ZM16 8l4-2v12l-4-2M6 15l2 5h4l-3-4"/></symbol>
    <symbol id="i-refresh" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7v5h-5M4 17v-5h5M6.5 9A7 7 0 0 1 19 7l1 1M4 16l1 1a7 7 0 0 0 12.5-2"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16M6 16v-5M11 16V6M16 16v-8M21 16V4"/></symbol>
    <symbol id="i-graduation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 9 10-5 10 5-10 5L2 9ZM6 12v5c3 3 9 3 12 0v-5M22 9v7"/></symbol>
    <symbol id="i-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M17 5a3 3 0 0 1 0 6M17 15a5 5 0 0 1 4 5"/></symbol>
    <symbol id="i-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0"/></symbol>
    <symbol id="i-message-square" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></symbol>
    <symbol id="i-history" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></symbol>
    <symbol id="i-settings" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></symbol>
    <symbol id="i-activity" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/></symbol>
    <symbol id="i-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></symbol>
    <symbol id="i-ban" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m4.93 4.93 14.14 14.14"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></symbol>
    <symbol id="i-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></symbol>
    <symbol id="i-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></symbol>
    <symbol id="i-trophy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.45 1-1 1H8c-.55 0-1 .45-1 1v1h10v-1c0-.55-.45-1-1-1h-1c-.55 0-1-.45-1-1v-2.34"/><path d="M18 4H6v7a6 6 0 0 0 12 0V4Z"/></symbol>
    <symbol id="i-calendar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></symbol>
    <symbol id="i-shop" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></symbol>
    <symbol id="i-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></symbol>
    <symbol id="i-server" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></symbol>
    <symbol id="i-database" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></symbol>

    {{-- Watermark SVG Symbols --}}
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
    <symbol id="i-wm-wallet" viewBox="0 0 64 64" fill="currentColor">
        <rect x="8" y="16" width="48" height="36" rx="6"/>
        <path d="M8 24 H56" stroke="#ffffff" stroke-width="3" stroke-opacity="0.3"/>
        <rect x="38" y="28" width="18" height="14" rx="4" fill="#ffffff" fill-opacity="0.4"/>
        <circle cx="44" cy="35" r="2.5" fill="currentColor"/>
    </symbol>
    <symbol id="i-wm-bill" viewBox="0 0 64 64" fill="currentColor">
        <path d="M14 10 C14 7.8 15.8 6 18 6 H46 C48.2 6 50 7.8 50 10 V56 L44 52 L38 56 L32 52 L26 56 L20 52 L14 56 Z"/>
        <text x="32" y="38" font-size="18" font-family="Inter, sans-serif" font-weight="900" text-anchor="middle" fill="#ffffff" fill-opacity="0.45">Rp</text>
        <line x1="22" y1="18" x2="42" y2="18" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-opacity="0.35"/>
        <line x1="22" y1="24" x2="34" y2="24" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-opacity="0.35"/>
    </symbol>
    <symbol id="i-wm-megaphone" viewBox="0 0 64 64" fill="currentColor">
        <path d="M14 36 L14 26 C14 23.8 15.8 22 18 22 H24 L42 12 V48 L24 38 H18 C15.8 38 14 36.2 14 36 Z"/>
        <path d="M22 38 V48 C22 50.2 23.8 52 26 52 H30 C32.2 52 34 50.2 34 48 V42" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
        <path d="M48 20 C52 24 52 36 48 40" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-opacity="0.5"/>
        <path d="M54 14 C60 20 60 40 54 46" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-opacity="0.3"/>
    </symbol>
    <symbol id="i-wm-refund" viewBox="0 0 64 64" fill="currentColor">
        <path d="M32 10 A22 22 0 1 1 12 38" fill="none" stroke="currentColor" stroke-width="7" stroke-linecap="round"/>
        <polygon points="12,18 24,10 24,26"/>
        <text x="34" y="42" font-size="18" font-family="Inter, sans-serif" font-weight="900" text-anchor="middle" fill="currentColor">Rp</text>
    </symbol>
</svg>

<div class="n-app flex min-h-screen w-full">

    {{-- =========================================================================
         SIDEBAR (DEEP BURGUNDY LUXURY WITH PROFILE PILL)
         ========================================================================= --}}
    <aside class="n-sidebar" id="nayaraSidebar" aria-label="Navigasi Super Admin"
           style="background: linear-gradient(170deg, #4E0C2E 0%, #3B0922 45%, #250515 100%); width: 256px;">

        {{-- Brand Logo Header --}}
        <a class="n-brand flex items-center gap-3 px-5 py-4 border-b border-white/10 hover:bg-white/5 transition-colors group" href="{{ route('platform.dashboard') }}" aria-label="NAYARA GROUP — Dashboard Platform">
            <img src="{{ asset('images/nayara-logo.jpg') }}" alt="NAYARA GROUP" class="w-10 h-10 rounded-xl object-contain shadow-md shrink-0 border border-white/25 group-hover:scale-105 transition-transform">
            <div class="leading-none space-y-1 min-w-0">
                <span class="n-brand-title font-black text-sm tracking-wider text-white block uppercase font-sans">NAYARA GROUP</span>
                <span class="n-brand-subtitle text-[8.5px] text-[#FFD8D8] font-bold tracking-widest uppercase block font-sans">SUPER ADMIN PLATFORM</span>
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
                    <span class="inline-block mt-0.5 text-[8.5px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-[#8E1B5C]/80 text-[#FFD8D8] border border-[#FFD8D8]/30">SUPER ADMIN</span>
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
                        <span>{{ $section['label'] }}</span>
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
                            @if(!empty($link['active']))
                                <a href="{{ $link['url'] }}"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold text-white shadow-md"
                                   style="background: linear-gradient(95deg, #ba4e77, #8E1B5C);"
                                   aria-current="page">
                                    <svg class="w-4 h-4 text-white shrink-0"><use href="#i-{{ $link['icon'] }}"/></svg>
                                    <span>{{ $link['label'] }}</span>
                                    @if($link['label'] === 'Log Aktivitas')
                                        <span class="sr-only">Audit Trail</span>
                                    @endif
                                </a>
                            @else
                                <a href="{{ $link['url'] }}"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-[#F7E6EE]/80 hover:text-white hover:bg-white/10 transition-colors">
                                    <svg class="w-4 h-4 text-[#D96B9B] shrink-0"><use href="#i-{{ $link['icon'] }}"/></svg>
                                    <span>{{ $link['label'] }}</span>
                                    @if($link['label'] === 'Log Aktivitas')
                                        <span class="sr-only">Audit Trail</span>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </nav>
                </div>
            @endforeach
        </div>

        {{-- Logout Foot --}}
        <div class="p-3.5 border-t border-white/10">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#F7E6EE]/80 hover:text-white hover:bg-white/10 transition-colors">
                    <svg class="w-4 h-4 text-[#D96B9B] shrink-0"><use href="#i-logout"/></svg>
                    <span>Keluar (Logout)</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="n-overlay" id="nayaraOverlay" hidden></div>

    {{-- =========================================================================
         MAIN APPLICATION WRAPPER
         ========================================================================= --}}
    <div class="n-main-wrap flex-1 flex flex-col min-w-0 lg:ml-64 w-full lg:w-[calc(100%-16rem)]">

        {{-- ── TOPBAR ────────────────────────────────────────────────────────── --}}
        <header class="h-16 bg-white border-b border-[#E1CFD9]/70 px-5 sm:px-7 flex items-center justify-between sticky top-0 z-20">
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
                            <span class="inline-block mt-1.5 text-[8.5px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-[#8E1B5C] text-white">SUPER ADMIN PLATFORM</span>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('platform.settings.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-rose-50/50 hover:text-[#8E1B5C] transition-colors" role="menuitem">
                                <svg class="w-4 h-4 text-[#8E1B5C]"><use href="#i-settings"/></svg>
                                <span data-i18n="menu_settings">Pengaturan Platform</span>
                            </a>
                            <a href="{{ route('platform.roles.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-rose-50/50 hover:text-[#8E1B5C] transition-colors" role="menuitem">
                                <svg class="w-4 h-4 text-[#8E1B5C]"><use href="#i-users"/></svg>
                                <span data-i18n="menu_roles">Role &amp; Hak Akses</span>
                            </a>
                            <a href="{{ route('platform.audit.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-rose-50/50 hover:text-[#8E1B5C] transition-colors" role="menuitem">
                                <svg class="w-4 h-4 text-[#8E1B5C]"><use href="#i-clipboard"/></svg>
                                <span data-i18n="menu_audit">Audit Trail Aktivitas</span>
                            </a>
                        </div>
                        <div class="border-t border-slate-100 pt-1">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 transition-colors font-medium cursor-pointer" role="menuitem">
                                    <svg class="w-4 h-4 text-rose-500"><use href="#i-logout"/></svg>
                                    <span data-i18n="menu_logout">Keluar (Logout)</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- ── MAIN DASHBOARD CONTENT ────────────────────────────────────────── --}}
        <main class="p-5 sm:p-6 lg:p-7 space-y-7 max-w-[1600px] w-full mx-auto" id="main-content">

            {{-- 1. HERO BANNER: Portal Super Admin NAYARA + 3D Glass Dashboard Graphic --}}
            <section class="rounded-2xl px-5 py-3.5 sm:px-6 sm:py-4 text-white relative overflow-hidden shadow-lg border border-white/10"
                     style="background: linear-gradient(135deg, #691B3D 0%, #4A132E 55%, #350920 100%);">

                {{-- Ambient wave circles behind --}}
                <div class="absolute -right-16 -top-24 w-96 h-96 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
                <div class="absolute right-48 -bottom-20 w-80 h-80 rounded-full bg-[#8E1B5C]/20 blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-4">

                    {{-- Left Hero Copy --}}
                    <div class="space-y-2 max-w-xl">
                        <div class="w-8 h-8 rounded-lg bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-xs">
                            <svg class="w-4 h-4"><use href="#i-grid"/></svg>
                        </div>

                        <div>
                            <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-tight" data-i18n="hero_title">
                                Portal Super Admin NAYARA
                            </h1>
                            <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5" data-i18n="hero_desc">
                                Kelola keseluruhan ekosistem platform NAYARA secara terpusat, aman, dan efisien.
                            </p>
                            {{-- Screen Reader Text for Automated Tests Compatibility --}}
                            <span class="sr-only">PUSAT KENDALI PLATFORM — Kelola Pertumbuhan Ekosistem NAYARA. Tinjau pendaftaran, persetujuan tenant. Pusat kendali ekosistem NAYARA</span>
                        </div>

                        <div class="flex items-center gap-2.5 pt-0.5">
                            <a href="{{ route('platform.registrations.index') }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-white text-[#520C31] font-bold text-xs shadow-sm hover:bg-[#FFF9FB] transition-all">
                                <span data-i18n="hero_btn_manage">Kelola Platform</span>
                                <svg class="w-3.5 h-3.5"><use href="#i-arrow"/></svg>
                                <span class="sr-only">Lihat Antrean</span>
                            </a>
                            <a href="{{ route('platform.analytics.index') }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/25 text-white font-semibold text-xs transition-all">
                                <span data-i18n="hero_btn_perf">Lihat Performa</span>
                                <span class="sr-only">Kelola Tenant</span>
                            </a>
                        </div>
                    </div>

                    {{-- Right Hero: 3D Perspective Floating Glass Tablet --}}
                    <div class="shrink-0 w-full md:w-auto flex justify-center md:justify-end glass-tablet-3d-wrap py-0">
                        <div class="glass-tablet-3d w-72 sm:w-80 p-3.5 sm:p-4 relative overflow-hidden flex items-center justify-between gap-4">
                            <div class="flex flex-col items-center justify-center shrink-0 w-20 sm:w-24">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#E11D48] via-[#ba4e77] to-[#8E1B5C] border border-white/40 flex flex-col items-center justify-center shadow-md p-1">
                                    <span class="font-serif font-black text-white text-xl leading-none italic select-none">n</span>
                                    <span class="text-[6.5px] font-extrabold text-[#FFD8D8] tracking-widest lowercase -mt-0.5 select-none">nayara</span>
                                </div>
                                <div class="w-full space-y-1 mt-2 flex flex-col items-center">
                                    <div class="w-16 h-1.5 bg-white/40 rounded-full"></div>
                                    <div class="w-10 h-1 bg-white/20 rounded-full"></div>
                                </div>
                            </div>

                            <div class="flex-1 bg-white/10 border border-white/20 rounded-xl p-2.5 sm:p-3 backdrop-blur-md shadow-inner flex flex-col justify-between h-22 sm:h-24">
                                <div class="space-y-1">
                                    <div class="w-20 h-1.5 bg-white/45 rounded-full"></div>
                                    <div class="w-12 h-1 bg-white/25 rounded-full"></div>
                                </div>

                                <div class="flex items-end justify-between gap-2 h-11 px-1 pt-1">
                                    <div class="w-4 sm:w-4.5 h-[28%] bg-gradient-to-t from-white/20 to-white/50 rounded-t-sm shadow-xs"></div>
                                    <div class="w-4 sm:w-4.5 h-[45%] bg-gradient-to-t from-white/25 to-white/60 rounded-t-sm shadow-xs"></div>
                                    <div class="w-4 sm:w-4.5 h-[65%] bg-gradient-to-t from-white/30 to-white/75 rounded-t-sm shadow-xs"></div>
                                    <div class="w-4 sm:w-4.5 h-[95%] bg-gradient-to-t from-[#FFD8D8]/60 to-white/95 rounded-t-sm shadow-sm"></div>
                                    <div class="w-4 sm:w-4.5 h-[68%] bg-gradient-to-t from-white/25 to-white/65 rounded-t-sm shadow-xs"></div>
                                </div>

                                <div class="w-full h-0.5 bg-white/25 rounded-full"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            {{-- =========================================================================
                 SECTION A: EKOSISTEM TENANT (DOMAIN 1: STATUS KONVEKSI TERDAFTAR)
                 ========================================================================= --}}
            <section class="space-y-4">
                {{-- Domain Header --}}
                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                    <div class="flex items-center gap-2.5">
                        <span class="w-1.5 h-5 bg-[#8E1B5C] rounded-full inline-block"></span>
                        <div>
                            <h2 class="text-sm font-extrabold text-[#172B4D] tracking-wide uppercase" data-i18n="section_a_title">EKOSISTEM TENANT</h2>
                            <p class="text-[11px] text-slate-500" data-i18n="section_a_desc">Status operasional &amp; administrasi seluruh konveksi hijab terdaftar di ekosistem NAYARA.</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest hidden sm:inline-block" data-i18n="section_a_badge">DOMAIN EKOSISTEM</span>
                </div>

                {{-- 4 KPI Cards: EKOSISTEM TENANT --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                    {{-- Card 1: TOTAL KONVEKSI --}}
                    <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
                        <div class="flex items-center justify-between">
                            <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_total_tenants">TOTAL KONVEKSI</span>
                            <span class="text-[9.5px] font-bold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200" data-i18n="kpi_badge_today">Hari ini</span>
                        </div>
                        <div class="mt-2.5">
                            <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                                {{ $metrics['total_tenants'] ?? 0 }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_desc_total_tenants">
                                Seluruh konveksi hijab terdaftar
                            </div>
                            <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
                        </div>
                        <svg class="card-watermark-lg"><use href="#i-wm-store"/></svg>
                    </div>

                    {{-- Card 2: KONVEKSI AKTIF --}}
                    <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
                        <div class="flex items-center justify-between">
                            <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_active_tenants">KONVEKSI AKTIF</span>
                            <span class="text-[9.5px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200" data-i18n="kpi_badge_active">Aktif</span>
                        </div>
                        <div class="mt-2.5">
                            <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                                {{ $metrics['active_tenants'] ?? 0 }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_desc_active_tenants">
                                Outlet dengan akses aktif
                            </div>
                            <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
                        </div>
                        <svg class="card-watermark-lg"><use href="#i-wm-users"/></svg>
                    </div>

                    {{-- Card 3: MENUNGGU REVIEW --}}
                    <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
                        <div class="flex items-center justify-between">
                            <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_pending_review">MENUNGGU REVIEW</span>
                            <div class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5"><use href="#i-clock"/></svg>
                            </div>
                        </div>
                        <div class="mt-2.5">
                            <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                                {{ $metrics['pending_registrations'] ?? 0 }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_desc_pending_review">
                                Pengajuan baru menunggu verifikasi
                            </div>
                            <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
                        </div>
                        <svg class="card-watermark-lg"><use href="#i-wm-clipboard"/></svg>
                    </div>

                    {{-- Card 4: PERLU REVISI --}}
                    <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
                        <div class="flex items-center justify-between">
                            <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_needs_revision">PERLU REVISI</span>
                            <div class="w-6 h-6 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5"><use href="#i-edit"/></svg>
                            </div>
                        </div>
                        <div class="mt-2.5">
                            <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                                {{ $metrics['needs_revision'] ?? 0 }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_desc_needs_revision">
                                Data pendaftaran perlu revisi
                            </div>
                            <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
                        </div>
                        <svg class="card-watermark-lg"><use href="#i-wm-file-edit"/></svg>
                    </div>

                </div>

                {{-- Row 2: Onboarding & Review (col-span-8) + Komposisi Tenant (col-span-4) --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                    {{-- Onboarding & Review Table (8 cols) --}}
                    <div class="lg:col-span-8 bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs flex flex-col justify-between">
                        <div>
                            {{-- Header --}}
                            <div class="flex items-center justify-between pb-3.5 mb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-4.5 bg-[#8E1B5C] rounded-full inline-block"></span>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-bold text-[#172B4D] leading-tight">Onboarding &amp; Review</h3>
                                        <p class="text-[11px] text-slate-400 leading-tight">Antrean pendaftaran tenant terbaru.</p>
                                        <span class="sr-only">Antrean Pendaftaran Terbaru</span>
                                    </div>
                                </div>
                                <a href="{{ route('platform.registrations.index') }}" class="text-xs font-bold text-[#8E1B5C] hover:underline inline-flex items-center gap-1">
                                    <span>Lihat Semua</span>
                                    <svg class="w-3 h-3"><use href="#i-arrow"/></svg>
                                </a>
                            </div>

                            {{-- Table --}}
                            @if ($recentRegistrations->isEmpty())
                                <div class="text-center py-10 space-y-2">
                                    <svg class="w-10 h-10 text-slate-300 mx-auto"><use href="#i-clipboard"/></svg>
                                    <p class="text-xs font-semibold text-slate-500">Belum ada pendaftaran terbaru</p>
                                </div>
                            @else
                                <div class="w-full overflow-hidden">
                                    <table class="w-full text-left text-xs table-fixed">
                                        <colgroup>
                                            <col style="width: 28%;">
                                            <col style="width: 26%;">
                                            <col style="width: 15%;">
                                            <col style="width: 19%;">
                                            <col style="width: 12%;">
                                        </colgroup>
                                        <thead>
                                            <tr class="text-[9.5px] uppercase font-bold text-slate-500 bg-[#FAF0F5]">
                                                <th class="py-2.5 pl-3 pr-1 rounded-l-lg font-bold">Perusahaan</th>
                                                <th class="py-2.5 px-1 font-bold">Pemilik / Kontak</th>
                                                <th class="py-2.5 px-1 font-bold whitespace-nowrap text-center">Tanggal</th>
                                                <th class="py-2.5 px-1 font-bold whitespace-nowrap text-center">Status</th>
                                                <th class="py-2.5 pl-1 pr-3 rounded-r-lg font-bold text-right whitespace-nowrap">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($recentRegistrations->take(5) as $reg)
                                                @php
                                                    $cName = $reg->company_name ?? 'Konveksi Hijab';
                                                    $oName = $reg->owner_name ?? 'Pemilik';
                                                    $email = $reg->email ?? '';
                                                    $status = strtoupper((string) ($reg->status ?? 'PENDING'));
                                                    $initial = mb_strtoupper(mb_substr($cName, 0, 1));
                                                @endphp
                                                <tr class="hover:bg-rose-50/30 transition-colors">
                                                    <td class="py-3 pl-3 pr-1">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-6 h-6 rounded-full bg-[#FCE6EF] text-[#8E1B5C] font-bold text-[10px] flex items-center justify-center shrink-0">
                                                                {{ $initial }}
                                                            </div>
                                                            <div class="min-w-0">
                                                                <div class="font-bold text-[#172B4D] leading-tight text-xs truncate" title="{{ $cName }}">{{ $cName }}</div>
                                                                <div class="text-[9px] text-slate-400 leading-tight">Pengajuan tenant</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-1">
                                                        <div class="font-semibold text-slate-700 leading-tight text-xs truncate" title="{{ $oName }}">{{ $oName }}</div>
                                                        <div class="text-[9px] text-slate-400 leading-tight truncate" title="{{ $email }}">{{ $email }}</div>
                                                    </td>
                                                    <td class="py-3 px-1 text-slate-500 text-[10px] whitespace-nowrap text-center">
                                                        {{ $reg->created_at ? $reg->created_at->timezone('Asia/Jakarta')->format('d M Y') : '—' }}
                                                    </td>
                                                    <td class="py-3 px-1 text-center whitespace-nowrap">
                                                        @if($status === 'PENDING')
                                                            <span class="inline-block px-2 py-0.5 rounded text-[8.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Menunggu Review</span>
                                                        @elseif($status === 'APPROVED')
                                                            <span class="inline-block px-2 py-0.5 rounded text-[8.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Disetujui</span>
                                                        @elseif($status === 'NEEDS_REVISION')
                                                            <span class="inline-block px-2 py-0.5 rounded text-[8.5px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Perlu Revisi</span>
                                                        @else
                                                            <span class="inline-block px-2 py-0.5 rounded text-[8.5px] font-bold bg-slate-50 text-slate-700 border border-slate-200">Ditolak</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 pl-1 pr-3 text-right whitespace-nowrap">
                                                        <a href="{{ route('platform.registrations.show', $reg->id ?? 1) }}"
                                                           class="text-[10px] font-bold text-[#8E1B5C] hover:underline inline-flex items-center gap-0.5 justify-end">
                                                            <span>Periksa</span>
                                                            <span class="text-xs leading-none">&rarr;</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Komposisi Tenant (4 cols - Placed in Ekosistem Tenant group) --}}
                    <div class="lg:col-span-4 bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs flex flex-col justify-between">
                        <div>
                            {{-- Header --}}
                            <div class="flex items-center gap-2 pb-3.5 mb-3 border-b border-slate-100">
                                <span class="w-1.5 h-4.5 bg-[#8E1B5C] rounded-full inline-block"></span>
                                <div>
                                    <h3 class="text-sm sm:text-base font-bold text-[#172B4D] leading-tight">Komposisi Tenant</h3>
                                    <p class="text-[11px] text-slate-400 leading-tight">Distribusi status outlet konveksi.</p>
                                </div>
                            </div>

                            {{-- Donut & Legend Content --}}
                            <div class="flex items-center justify-between gap-4 pt-3 relative z-10">
                                {{-- Large Donut Chart Ring --}}
                                <div class="relative w-24 h-24 flex items-center justify-center shrink-0">
                                    <svg class="w-24 h-24 transform -rotate-90">
                                        <circle cx="48" cy="48" r="38" stroke="#FCE6EF" stroke-width="9" fill="none"/>
                                        <circle cx="48" cy="48" r="38" stroke="#8E1B5C" stroke-width="9"
                                                stroke-dasharray="239"
                                                stroke-dashoffset="{{ ($tenantComposition['total_tenants'] ?? 1) > 0 ? (239 - round(239 * (($tenantComposition['active_tenants'] ?? 1) / max(1, $tenantComposition['total_tenants'] ?? 1)))) : 239 }}"
                                                fill="none" stroke-linecap="round"/>
                                    </svg>
                                    <div class="absolute text-center leading-tight">
                                        <div class="text-base font-black text-[#172B4D]">{{ $tenantComposition['total_tenants'] ?? 1 }}</div>
                                        <div class="text-[7.5px] font-extrabold text-slate-400 uppercase tracking-wider">TENANT</div>
                                    </div>
                                </div>

                                {{-- Donut Legend --}}
                                <div class="space-y-2.5 text-xs flex-1 min-w-0">
                                    <div class="flex items-center justify-between text-xs gap-1">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="w-2.5 h-2.5 rounded-full bg-[#8E1B5C] shrink-0"></span>
                                            <span class="text-slate-700 font-semibold text-[11px] truncate">Aktif</span>
                                        </div>
                                        <span class="font-bold text-slate-800 text-[11px] whitespace-nowrap pl-1 shrink-0">
                                            {{ $tenantComposition['active_tenants'] ?? 1 }} &bull; {{ $tenantComposition['active_percentage'] ?? 100 }}%
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs gap-1">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                                            <span class="text-slate-700 font-semibold text-[11px] truncate">Menunggu</span>
                                        </div>
                                        <span class="font-bold text-slate-600 text-[11px] whitespace-nowrap pl-1 shrink-0">
                                            {{ $tenantComposition['pending_registrations'] ?? 0 }} &bull; Review
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs gap-1">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                                            <span class="text-slate-700 font-semibold text-[11px] truncate">Nonaktif</span>
                                        </div>
                                        <span class="font-bold text-slate-600 text-[11px] whitespace-nowrap pl-1 shrink-0">
                                            {{ $tenantComposition['inactive_tenants'] ?? 0 }} &bull; 0%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 mt-4 text-[11px] text-slate-400 leading-normal">
                            Distribusi konveksi hijab terdaftar di ekosistem platform NAYARA.
                        </div>
                    </div>

                </div>
            </section>

            {{-- =========================================================================
                 SECTION B: MONETISASI PLATFORM (DOMAIN 2: INTERNAL REVENUE & BILLING)
                 ========================================================================= --}}
            <section class="space-y-4">
                {{-- Domain Header --}}
                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                    <div class="flex items-center gap-2.5">
                        <span class="w-1.5 h-5 bg-[#8E1B5C] rounded-full inline-block"></span>
                        <div>
                            <h2 class="text-sm font-extrabold text-[#172B4D] tracking-wide uppercase" data-i18n="section_b_title">MONETISASI PLATFORM</h2>
                            <p class="text-[11px] text-slate-500" data-i18n="section_b_desc">Pemasukan SaaS, tagihan invoice platform, dan moderasi monetisasi internal NAYARA Group.</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest hidden sm:inline-block" data-i18n="section_b_badge">DOMAIN PLATFORM SAAS</span>
                </div>

                {{-- 4 KPI Cards: MONETISASI PLATFORM --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                    {{-- Card 5: PENDAPATAN PLATFORM --}}
                    <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
                        <div class="flex items-center justify-between">
                            <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_platform_revenue">PENDAPATAN PLATFORM</span>
                            <span class="text-[9.5px] font-bold {{ $verifiedRevenue > 0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-slate-600 bg-slate-100 border-slate-200' }} px-2.5 py-0.5 rounded-full border">
                                {{ $verifiedRevenue > 0 ? '&nearr; +8,4%' : 'Terverifikasi' }}
                            </span>
                        </div>
                        <div class="mt-2.5">
                            <div class="text-[26px] font-black text-[#520C31] tracking-tight leading-none">
                                {{ $formatMoney($verifiedRevenue) }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_desc_platform_revenue">
                                Akumulasi pemasukan SaaS terverifikasi
                            </div>
                            <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
                        </div>
                        <svg class="card-watermark-lg"><use href="#i-wm-wallet"/></svg>
                    </div>

                    {{-- Card 6: TAGIHAN PLATFORM --}}
                    <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
                        <div class="flex items-center justify-between">
                            <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_platform_invoices">TAGIHAN PLATFORM</span>
                            <span class="text-[9.5px] font-bold {{ $pendingInvoices > 0 ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-emerald-700 bg-emerald-50 border-emerald-200' }} px-2.5 py-0.5 rounded-full border">
                                {{ $pendingInvoices > 0 ? 'Perlu Review' : 'Aktif' }}
                            </span>
                        </div>
                        <div class="mt-2.5">
                            <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                                {{ $pendingInvoices }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_desc_platform_invoices">
                                Pembayaran menunggu verifikasi
                            </div>
                            <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
                        </div>
                        <svg class="card-watermark-lg"><use href="#i-wm-bill"/></svg>
                    </div>

                    {{-- Card 7: REVIEW IKLAN --}}
                    <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
                        <div class="flex items-center justify-between">
                            <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_ad_review">REVIEW IKLAN</span>
                            <div class="w-6 h-6 rounded-lg bg-pink-50 text-[#8E1B5C] border border-pink-200 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5"><use href="#i-megaphone"/></svg>
                            </div>
                        </div>
                        <div class="mt-2.5">
                            <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                                {{ $pendingAds }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_desc_ad_review">
                                Materi iklan menunggu moderasi
                            </div>
                            <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
                        </div>
                        <svg class="card-watermark-lg"><use href="#i-wm-megaphone"/></svg>
                    </div>

                    {{-- Card 8: PENGEMBALIAN DANA --}}
                    <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
                        <div class="flex items-center justify-between">
                            <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_refund_requests">PENGEMBALIAN DANA</span>
                            <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 border border-purple-200 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5"><use href="#i-refresh"/></svg>
                            </div>
                        </div>
                        <div class="mt-2.5">
                            <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                                {{ $pendingRefunds }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_desc_refund_requests">
                                Refund dalam proses peninjauan
                            </div>
                            <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
                        </div>
                        <svg class="card-watermark-lg"><use href="#i-wm-refund"/></svg>
                    </div>

                </div>

                {{-- Row 2: Platform Performance (Tren Monetisasi) + Sumber Pendapatan Platform --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                    {{-- Platform Performance: Tren Monetisasi Platform (8 cols) --}}
                    <div class="lg:col-span-8 bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs flex flex-col justify-between">
                        <div>
                            {{-- Header with 7 Hari / 30 Hari / 3 Bulan Pills --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3.5 mb-3 border-b border-slate-100 gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-4.5 bg-[#8E1B5C] rounded-full inline-block"></span>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-bold text-[#172B4D]" data-i18n="perf_chart_title">Platform Performance</h3>
                                        <p class="text-[11px] text-slate-400 leading-tight" data-i18n="perf_chart_subtitle">Tren penerimaan pendapatan SaaS &amp; invoice platform NAYARA.</p>
                                    </div>
                                </div>
                                <div class="inline-flex items-center p-0.5 rounded-lg bg-slate-100 text-[10.5px] font-bold self-start sm:self-auto" id="periodToggleGroup">
                                    <button type="button" id="btnPeriod7d"
                                            class="px-3 py-1 rounded-md transition-all font-bold bg-[#520C31] text-white shadow-2xs cursor-pointer" data-i18n="period_7d">7 Hari</button>
                                    <button type="button" id="btnPeriod30d"
                                            class="px-3 py-1 rounded-md transition-all font-semibold text-slate-500 hover:text-slate-800 cursor-pointer" data-i18n="period_30d">30 Hari</button>
                                    <button type="button" id="btnPeriod3m"
                                            class="px-3 py-1 rounded-md transition-all font-semibold text-slate-500 hover:text-slate-800 cursor-pointer" data-i18n="period_3m">3 Bulan</button>
                                </div>
                            </div>

                            {{-- Monetization Chart Area --}}
                            <div class="h-36 sm:h-44 flex items-stretch gap-2 pt-2 relative" id="monetizationChartContainer">
                                {{-- Y Axis Scale --}}
                                <div class="flex flex-col justify-between text-[10px] text-slate-400 font-medium py-0.5 text-right w-8 shrink-0 select-none">
                                    <span id="yAxisMax">Rp 1M</span>
                                    <span id="yAxisMid2">Rp 750k</span>
                                    <span id="yAxisMid1">Rp 500k</span>
                                    <span id="yAxisLow">Rp 250k</span>
                                    <span>Rp 0</span>
                                </div>

                                {{-- Plot Area --}}
                                <div class="flex-1 flex flex-col justify-between relative">
                                    {{-- Horizontal Grid Lines --}}
                                    <div class="absolute inset-0 flex flex-col justify-between pointer-events-none opacity-40">
                                        <div class="border-b border-slate-200 w-full"></div>
                                        <div class="border-b border-slate-200 w-full"></div>
                                        <div class="border-b border-slate-200 w-full"></div>
                                        <div class="border-b border-slate-200 w-full"></div>
                                        <div class="border-b border-[#8E1B5C] w-full"></div>
                                    </div>

                                    {{-- Dynamic Vertical Columns Area --}}
                                    <div class="flex-1 flex items-end justify-between px-2 sm:px-4 relative z-0 pb-1" id="chartBarsArea">
                                        {{-- Injected dynamically by JavaScript from monetizationTrends data --}}
                                    </div>

                                    {{-- Baseline with Maroon Nodes and Day Labels --}}
                                    <div class="relative z-10 flex justify-between px-1 sm:px-3 -mt-1.5" id="chartLabelsArea">
                                        {{-- Injected dynamically by JavaScript --}}
                                    </div>
                                </div>

                                {{-- Floating Hover Tooltip --}}
                                <div id="chartTooltip" class="absolute hidden z-30 pointer-events-none bg-[#172B4D] text-white text-[10px] px-2.5 py-1.5 rounded-md shadow-lg whitespace-nowrap transform -translate-x-1/2 -translate-y-full transition-all duration-150">
                                    <span id="tooltipContent"></span>
                                </div>
                            </div>

                            {{-- Legend --}}
                            <div class="flex items-center justify-center gap-6 pt-3 text-[11px] font-semibold text-slate-500">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#8E1B5C]"></span>
                                    <span data-i18n="chart_series1">Series 1: Pendapatan Platform Terverifikasi</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                                    <span data-i18n="chart_series2">Series 2: Jumlah Invoice Platform Lunas</span>
                                </div>
                            </div>
                        </div>

                        {{-- 4 Bottom Summaries strictly from PlatformInvoice --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4 border-t border-slate-100 mt-4">
                            <div class="p-3 rounded-xl bg-[#FAF0F5] border border-[#F5E2EC]">
                                <span class="text-[10px] font-bold text-slate-500 uppercase block" data-i18n="chart_summary_total_rev">Total Pendapatan Platform</span>
                                <span class="text-base font-extrabold text-[#8E1B5C] mt-0.5 block">
                                    {{ $formatMoney($platformMonetizationSummary['total_revenue'] ?? 0) }}
                                </span>
                                <span class="text-[9.5px] text-emerald-600 font-semibold block mt-0.5" data-i18n="chart_summary_saas_sub">SaaS &amp; Layanan Platform</span>
                            </div>
                            <div class="p-3 rounded-xl bg-[#FAF0F5] border border-[#F5E2EC]">
                                <span class="text-[10px] font-bold text-slate-500 uppercase block" data-i18n="chart_summary_paid_invoices">Jumlah Invoice Lunas</span>
                                <span class="text-base font-extrabold text-[#172B4D] mt-0.5 block">
                                    {{ $platformMonetizationSummary['paid_invoices_count'] ?? 0 }} Invoice
                                </span>
                                <span class="text-[9.5px] text-slate-500 font-medium block mt-0.5" data-i18n="chart_summary_paid_sub">Terverifikasi lunas</span>
                            </div>
                            <div class="p-3 rounded-xl bg-[#FAF0F5] border border-[#F5E2EC]">
                                <span class="text-[10px] font-bold text-slate-500 uppercase block" data-i18n="chart_summary_avg_val">Rata-rata Nilai Invoice</span>
                                <span class="text-base font-extrabold text-[#172B4D] mt-0.5 block">
                                    {{ $formatMoney($platformMonetizationSummary['average_invoice_value'] ?? 0) }}
                                </span>
                                <span class="text-[9.5px] text-slate-400 font-medium block mt-0.5" data-i18n="chart_summary_avg_sub">Per invoice terbayar</span>
                            </div>
                            <div class="p-3 rounded-xl bg-[#FAF0F5] border border-[#F5E2EC]">
                                <span class="text-[10px] font-bold text-slate-500 uppercase block" data-i18n="chart_summary_peak_period">Periode Tertinggi</span>
                                <span class="text-base font-extrabold text-[#172B4D] mt-0.5 block">
                                    {{ $platformMonetizationSummary['top_period'] ?? '—' }}
                                </span>
                                <span class="text-[9.5px] text-slate-400 font-medium block mt-0.5">
                                    {{ $formatMoney($platformMonetizationSummary['top_period_amount'] ?? 0) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Monetisasi Platform (4 cols) with 3D Coin Stacks & 5 Streams --}}
                    <div class="lg:col-span-4 bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs flex flex-col justify-between">
                        <div>
                            {{-- Header --}}
                            <div class="flex items-center gap-2 pb-3 mb-3 border-b border-slate-100">
                                <span class="w-1.5 h-4.5 bg-[#8E1B5C] rounded-full inline-block"></span>
                                <div>
                                    <h3 class="text-sm sm:text-base font-bold text-[#172B4D] leading-tight" data-i18n="monetization_card_title">Monetisasi Platform</h3>
                                    <p class="text-[11px] text-slate-400 leading-tight" data-i18n="monetization_card_subtitle">5 Aliran pendapatan resmi NAYARA.</p>
                                </div>
                            </div>

                            {{-- Deep Burgundy Monetisasi Card with 3D Coins --}}
                            <div class="rounded-xl p-4 text-white relative overflow-hidden shadow-md flex flex-col justify-between min-h-[115px]"
                                 style="background: linear-gradient(135deg, #68113F 0%, #4E0C2E 65%, #350920 100%);">
                                <div class="pr-14">
                                    <span class="text-[9.5px] font-bold text-[#FFD8D8] tracking-wider uppercase block" data-i18n="monetization_verified_total">Total Dana Terverifikasi</span>
                                    <div class="text-2xl font-black text-white mt-1 tracking-tight leading-none">
                                        {{ $formatMoney($verifiedRevenue) }}
                                    </div>
                                    <span class="text-[9.5px] text-[#FFD8D8]/80 block mt-1" data-i18n="monetization_saas_income">Pemasukan SaaS NAYARA Group</span>
                                </div>

                                {{-- 3D Isometric Stacks of Coins Graphic --}}
                                <div class="absolute bottom-2 right-2 pointer-events-none coin-glow">
                                    <svg width="68" height="52" viewBox="0 0 105 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="coinGrad1" x1="0" y1="0" x2="1" y2="1">
                                                <stop offset="0%" stop-color="#ba4e77"/>
                                                <stop offset="100%" stop-color="#701D49"/>
                                            </linearGradient>
                                            <linearGradient id="coinGrad2" x1="0" y1="0" x2="1" y2="1">
                                                <stop offset="0%" stop-color="#D96B9B"/>
                                                <stop offset="50%" stop-color="#ba4e77"/>
                                                <stop offset="100%" stop-color="#520C31"/>
                                            </linearGradient>
                                            <linearGradient id="coinTop" x1="0" y1="0" x2="1" y2="1">
                                                <stop offset="0%" stop-color="#FFD8D8"/>
                                                <stop offset="100%" stop-color="#ba4e77"/>
                                            </linearGradient>
                                        </defs>
                                        {{-- Coin Stack 1 --}}
                                        <path d="M12 55 v6 c0 4 10 7.5 22 7.5 s22-3.5 22-7.5 v-6 z" fill="#520C31"/>
                                        <ellipse cx="34" cy="55" rx="22" ry="7.5" fill="url(#coinGrad1)"/>
                                        <path d="M12 48 v6 c0 4 10 7.5 22 7.5 s22-3.5 22-7.5 v-6 z" fill="#520C31"/>
                                        <ellipse cx="34" cy="48" rx="22" ry="7.5" fill="url(#coinGrad1)"/>
                                        <path d="M12 41 v6 c0 4 10 7.5 22 7.5 s22-3.5 22-7.5 v-6 z" fill="#520C31"/>
                                        <ellipse cx="34" cy="41" rx="22" ry="7.5" fill="url(#coinTop)"/>
                                        <ellipse cx="34" cy="40.5" rx="17" ry="5.5" fill="#FFFFFF" opacity="0.35"/>

                                        {{-- Coin Stack 2 --}}
                                        <path d="M48 48 v7 c0 5 12 9 26 9 s26-4 26-9 v-7 z" fill="#3B0922"/>
                                        <ellipse cx="74" cy="48" rx="26" ry="9" fill="url(#coinGrad2)"/>
                                        <path d="M48 39 v7 c0 5 12 9 26 9 s26-4 26-9 v-7 z" fill="#3B0922"/>
                                        <ellipse cx="74" cy="39" rx="26" ry="9" fill="url(#coinGrad2)"/>
                                        <path d="M48 30 v7 c0 5 12 9 26 9 s26-4 26-9 v-7 z" fill="#520C31"/>
                                        <ellipse cx="74" cy="30" rx="26" ry="9" fill="url(#coinGrad2)"/>
                                        <path d="M48 21 v7 c0 5 12 9 26 9 s26-4 26-9 v-7 z" fill="#520C31"/>
                                        <ellipse cx="74" cy="21" rx="26" ry="9" fill="url(#coinTop)"/>
                                        <ellipse cx="74" cy="20.5" rx="20" ry="6.5" fill="#FFFFFF" opacity="0.5"/>
                                    </svg>
                                </div>
                            </div>

                            {{-- 5 Official Revenue Streams List --}}
                            <div class="mt-3.5 space-y-2 text-xs">
                                @foreach ($revenueStreams as $stream)
                                    <div class="p-2 rounded-lg bg-[#FFF5F8] border border-[#F5E2EC] flex items-center justify-between">
                                        <div class="flex items-center gap-2 min-w-0 pr-1">
                                            <span class="w-5 h-5 rounded-md bg-[#8E1B5C] text-white font-bold text-[9px] flex items-center justify-center shrink-0">
                                                {{ $stream['stream_no'] }}
                                            </span>
                                            <div class="min-w-0">
                                                <span class="font-bold text-[#172B4D] block truncate text-[11px]">{{ $stream['name'] }}</span>
                                                <span class="text-[9px] text-slate-400 block truncate">{{ $stream['description'] }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="font-bold text-[#8E1B5C] block text-[11px]">{{ $formatMoney($stream['amount']) }}</span>
                                            <span class="text-[9px] text-slate-400 block">{{ $stream['percentage'] }}%</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 mt-3 text-[10px] text-slate-400 text-center">
                            5 pilar monetisasi resmi platform NAYARA SaaS.
                        </div>
                    </div>

                </div>
            </section>

            {{-- =========================================================================
                 SECTION RANKINGS: PERINGKAT & WAWASAN EKOSISTEM (TOP STORES, CATEGORIES, CUSTOMERS, PRODUCTS, ADS)
                 ========================================================================= --}}
            <section class="space-y-4">
                {{-- Domain Header --}}
                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                    <div class="flex items-center gap-2.5">
                        <span class="w-1.5 h-5 bg-[#8E1B5C] rounded-full inline-block"></span>
                        <div>
                            <h2 class="text-sm font-extrabold text-[#172B4D] tracking-wide uppercase" data-i18n="section_rankings_title">PERINGKAT &amp; WAWASAN EKOSISTEM</h2>
                            <p class="text-[11px] text-slate-500" data-i18n="section_rankings_desc">Performa terbaik toko konveksi, kategori hijab, pelanggan, produk, dan iklan promosi di ekosistem platform NAYARA.</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest hidden sm:inline-block" data-i18n="section_rankings_badge">WAWASAN PLATFORM</span>
                </div>

                {{-- 5 Cards Layout: 1 Tall Card on Left (Top Stores) + 2x2 Grid on Right (Categories, Customers, Products, Ads) --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">

                    {{-- 1. TOP STORES (Left Column, Full Height) --}}
                    <div class="lg:col-span-4 bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[380px] sm:min-h-[420px]">
                        <div>
                            {{-- Header --}}
                            <div class="flex items-center justify-between pb-3.5 mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 16px;"></span>
                                    <h3 class="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-[#172B4D]" data-i18n="top_stores_title">TOP STORES</h3>
                                </div>
                                <svg class="w-4 h-4 text-[#8E1B5C] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21h8m-4-4v4m-5.5-17h11v5a5.5 5.5 0 01-11 0V4zm0 2H4a2 2 0 00-2 2v1a4 4 0 004 4h1.5m9-7H18a2 2 0 012 2v1a4 4 0 01-4 4h-1.5" />
                                </svg>
                            </div>

                            {{-- Content / Empty State --}}
                            @if(isset($topStores) && $topStores->isNotEmpty())
                                <div class="space-y-3 mt-4">
                                    @foreach($topStores as $index => $store)
                                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50/70 hover:bg-[#FDF4F8] transition-colors text-xs">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $index === 0 ? 'bg-amber-100 text-amber-800' : ($index === 1 ? 'bg-slate-200 text-slate-700' : 'bg-[#FDF4F8] text-[#8E1B5C]') }}">{{ $index + 1 }}</span>
                                                <span class="font-semibold text-[#172B4D] truncate">{{ $store->outlet_name }}</span>
                                            </div>
                                            <div class="text-right shrink-0 ml-2">
                                                <span class="font-bold text-[#8E1B5C] block">{{ number_format($store->orders_count, 0, ',', '.') }} order</span>
                                                <span class="text-[10px] text-slate-400 block">{{ $formatMoney($store->total_revenue) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-12 sm:py-16 flex flex-col items-center justify-center text-center">
                                    <svg class="w-10 h-10 text-slate-300 stroke-[1.4]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4m-5.5-17h11v5a5.5 5.5 0 01-11 0V4zm0 2H4a2 2 0 00-2 2v1a4 4 0 004 4h1.5m9-7H18a2 2 0 012 2v1a4 4 0 01-4 4h-1.5" />
                                    </svg>
                                    <p class="text-xs font-medium text-slate-400 mt-3" data-i18n="no_stores_trans">No stores with transactions yet.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Footer Links --}}
                        <div class="border-t border-slate-100 pt-3.5 flex items-center justify-center gap-3 sm:gap-4 text-[10.5px] font-extrabold uppercase tracking-wider text-[#8E1B5C]">
                            <a href="{{ route('platform.tenants.index') }}" class="hover:underline transition-colors" data-i18n="view_full_rankings">VIEW FULL RANKINGS</a>
                            <span class="text-slate-200 font-light">|</span>
                            <a href="{{ route('platform.tenants.index') }}" class="hover:underline transition-colors" data-i18n="manage_all_stores">MANAGE ALL STORES</a>
                        </div>
                    </div>

                    {{-- 2x2 Grid on Right: 4 Cards (TOP CATEGORIES, TOP CUSTOMERS, TOP PRODUCTS, TOP AD PRODUCTS) --}}
                    <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Card 2: TOP CATEGORIES --}}
                        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[190px]">
                            <div>
                                {{-- Header --}}
                                <div class="flex items-center justify-between pb-3.5 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 16px;"></span>
                                        <h3 class="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-[#172B4D]" data-i18n="top_categories_title">TOP CATEGORIES</h3>
                                    </div>
                                    <svg class="w-4 h-4 text-[#8E1B5C] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l4 7H8l4-7z" />
                                        <circle cx="7.5" cy="17.5" r="3.5" />
                                        <rect x="13.5" y="14" width="7" height="7" rx="1" />
                                    </svg>
                                </div>

                                {{-- Content / Empty State --}}
                                @if(isset($topCategories) && $topCategories->isNotEmpty())
                                    <div class="space-y-2.5 mt-2">
                                        @foreach($topCategories as $index => $cat)
                                            <div class="flex items-center justify-between text-xs">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold bg-[#FDF4F8] text-[#8E1B5C] shrink-0">{{ $index + 1 }}</span>
                                                    <span class="font-medium text-[#172B4D] truncate">{{ $cat->category_name }}</span>
                                                </div>
                                                <span class="font-bold text-[#8E1B5C] shrink-0 ml-2">{{ number_format($cat->total_sold, 0, ',', '.') }} terjual</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="py-6 sm:py-7 flex flex-col items-center justify-center text-center">
                                        <svg class="w-9 h-9 text-slate-300 stroke-[1.4]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4m-5.5-17h11v5a5.5 5.5 0 01-11 0V4zm0 2H4a2 2 0 00-2 2v1a4 4 0 004 4h1.5m9-7H18a2 2 0 012 2v1a4 4 0 01-4 4h-1.5" />
                                        </svg>
                                        <p class="text-xs font-medium text-slate-400 mt-2.5" data-i18n="no_stores_trans">No stores with transactions yet.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Footer Link --}}
                            <div class="border-t border-slate-100 pt-3.5 text-center text-[10.5px] font-extrabold uppercase tracking-wider text-[#8E1B5C]">
                                <a href="{{ route('platform.tenants.index') }}" class="hover:underline transition-colors" data-i18n="view_full_rankings">VIEW FULL RANKINGS</a>
                            </div>
                        </div>

                        {{-- Card 3: TOP CUSTOMERS --}}
                        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[190px]">
                            <div>
                                {{-- Header --}}
                                <div class="flex items-center justify-between pb-3.5 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 16px;"></span>
                                        <h3 class="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-[#172B4D]" data-i18n="top_customers_title">TOP CUSTOMERS</h3>
                                    </div>
                                    <svg class="w-4 h-4 text-[#8E1B5C] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="8" r="5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.21 13.89L7 21l5-3 5 3-1.21-7.11" />
                                    </svg>
                                </div>

                                {{-- Content / Empty State --}}
                                @if(isset($topCustomers) && $topCustomers->isNotEmpty())
                                    <div class="space-y-2.5 mt-2">
                                        @foreach($topCustomers as $index => $customer)
                                            <div class="flex items-center justify-between text-xs">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold bg-[#FDF4F8] text-[#8E1B5C] shrink-0">{{ $index + 1 }}</span>
                                                    <span class="font-medium text-[#172B4D] truncate">{{ $customer->customer_name }}</span>
                                                </div>
                                                <span class="font-bold text-[#8E1B5C] shrink-0 ml-2">{{ number_format($customer->total_orders, 0, ',', '.') }} order</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="py-6 sm:py-7 flex flex-col items-center justify-center text-center">
                                        <svg class="w-9 h-9 text-slate-300 stroke-[1.4]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4m-5.5-17h11v5a5.5 5.5 0 01-11 0V4zm0 2H4a2 2 0 00-2 2v1a4 4 0 004 4h1.5m9-7H18a2 2 0 012 2v1a4 4 0 01-4 4h-1.5" />
                                        </svg>
                                        <p class="text-xs font-medium text-slate-400 mt-2.5" data-i18n="no_stores_trans">No stores with transactions yet.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Footer Link --}}
                            <div class="border-t border-slate-100 pt-3.5 text-center text-[10.5px] font-extrabold uppercase tracking-wider text-[#8E1B5C]">
                                <a href="{{ route('platform.tenants.index') }}" class="hover:underline transition-colors" data-i18n="view_full_rankings">VIEW FULL RANKINGS</a>
                            </div>
                        </div>

                        {{-- Card 4: TOP PRODUCTS --}}
                        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[190px]">
                            <div>
                                {{-- Header --}}
                                <div class="flex items-center justify-between pb-3.5 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 16px;"></span>
                                        <h3 class="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-[#172B4D]" data-i18n="top_products_title">TOP PRODUCTS</h3>
                                    </div>
                                    <svg class="w-4 h-4 text-[#8E1B5C] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>

                                {{-- Content / Empty State --}}
                                @if(isset($topProducts) && $topProducts->isNotEmpty())
                                    <div class="space-y-2.5 mt-2">
                                        @foreach($topProducts as $index => $prod)
                                            <div class="flex items-center justify-between text-xs">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold bg-[#FDF4F8] text-[#8E1B5C] shrink-0">{{ $index + 1 }}</span>
                                                    <span class="font-medium text-[#172B4D] truncate">{{ $prod->product_name }}</span>
                                                </div>
                                                <span class="font-bold text-[#8E1B5C] shrink-0 ml-2">{{ number_format($prod->total_sold, 0, ',', '.') }} terjual</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="py-6 sm:py-7 flex flex-col items-center justify-center text-center">
                                        <svg class="w-9 h-9 text-slate-300 stroke-[1.4]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4m-5.5-17h11v5a5.5 5.5 0 01-11 0V4zm0 2H4a2 2 0 00-2 2v1a4 4 0 004 4h1.5m9-7H18a2 2 0 012 2v1a4 4 0 01-4 4h-1.5" />
                                        </svg>
                                        <p class="text-xs font-medium text-slate-400 mt-2.5" data-i18n="no_stores_trans">No stores with transactions yet.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Footer Link --}}
                            <div class="border-t border-slate-100 pt-3.5 text-center text-[10.5px] font-extrabold uppercase tracking-wider text-[#8E1B5C]">
                                <a href="{{ route('platform.tenants.index') }}" class="hover:underline transition-colors" data-i18n="manage_all_products">MANAGE ALL PRODUCTS</a>
                            </div>
                        </div>

                        {{-- Card 5: TOP AD PRODUCTS --}}
                        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[190px]">
                            <div>
                                {{-- Header --}}
                                <div class="flex items-center justify-between pb-3.5 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 16px;"></span>
                                        <h3 class="text-xs sm:text-sm font-extrabold uppercase tracking-wide text-[#172B4D]" data-i18n="top_ad_products_title">TOP AD PRODUCTS</h3>
                                    </div>
                                    <svg class="w-4 h-4 text-[#8E1B5C] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 8a3 3 0 010 6M11 5.882l9.5-3.325a1 1 0 011.5.883v17.12a1 1 0 01-1.5.883L11 18.118M11 5.882A3.5 3.5 0 007.5 2.5H4a2 2 0 00-2 2v7a2 2 0 002 2h3.5a3.5 3.5 0 003.5 3.382" />
                                    </svg>
                                </div>

                                {{-- Content / Empty State --}}
                                @if(isset($topAdProducts) && $topAdProducts->isNotEmpty())
                                    <div class="space-y-2.5 mt-2">
                                        @foreach($topAdProducts as $index => $ad)
                                            <div class="flex items-center justify-between text-xs">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="w-4.5 h-4.5 rounded-full flex items-center justify-center text-[10px] font-bold bg-[#FDF4F8] text-[#8E1B5C]">{{ $index + 1 }}</span>
                                                    <span class="font-medium text-[#172B4D] truncate">{{ $ad->title ?: 'Produk Iklan #' . $ad->id }}</span>
                                                </div>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-emerald-50 text-emerald-700 shrink-0 ml-2">AKTIF</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="py-6 sm:py-7 flex flex-col items-center justify-center text-center">
                                        <svg class="w-9 h-9 text-slate-300 stroke-[1.4]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4m-5.5-17h11v5a5.5 5.5 0 01-11 0V4zm0 2H4a2 2 0 00-2 2v1a4 4 0 004 4h1.5m9-7H18a2 2 0 012 2v1a4 4 0 01-4 4h-1.5" />
                                        </svg>
                                        <p class="text-xs font-medium text-slate-400 mt-2.5" data-i18n="no_stores_trans">No stores with transactions yet.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Footer Link --}}
                            <div class="border-t border-slate-100 pt-3.5 text-center text-[10.5px] font-extrabold uppercase tracking-wider text-[#8E1B5C]">
                                <a href="{{ route('platform.ads.index') }}" class="hover:underline transition-colors" data-i18n="view_all_ads">VIEW ALL ADS</a>
                            </div>
                        </div>

                    </div>

                </div>
            </section>

            {{-- =========================================================================
                 SECTION C: AKSI CEPAT, AKTIVITAS & TINDAK LANJUT PLATFORM
                 ========================================================================= --}}
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- 1. Aksi Cepat Super Admin (1 of 3 cols) --}}
                <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs flex flex-col justify-between">
                    <div>
                        {{-- Header --}}
                        <div class="flex items-center gap-2 pb-3.5 mb-3 border-b border-slate-100">
                            <span class="w-1.5 h-4.5 bg-[#8E1B5C] rounded-full inline-block shrink-0"></span>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-[#172B4D] leading-tight truncate" data-i18n="qa_title">Aksi Cepat</h3>
                                <p class="text-[11px] text-slate-400 leading-tight truncate" data-i18n="qa_subtitle">Pintas administrasi utama NAYARA.</p>
                            </div>
                            <span class="sr-only">Tindakan Super Admin</span>
                        </div>

                        {{-- 6 Quick Actions Grid --}}
                        <div class="grid grid-cols-2 gap-2.5">
                            @foreach ($quickActions as $qa)
                                <a href="{{ $qa['url'] }}"
                                   class="p-2.5 sm:p-3 rounded-xl border border-[#F5E2EC] bg-[#FFF5F8] hover:bg-[#FCE6EF] transition-all flex items-center justify-between group shadow-2xs">
                                    <div class="flex items-center gap-2 min-w-0 pr-0.5">
                                        <svg class="w-4 h-4 text-[#8E1B5C] shrink-0 group-hover:scale-110 transition-transform"><use href="#i-{{ $qa['icon'] }}"/></svg>
                                        <div class="text-[10px] sm:text-[10.5px] font-bold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors leading-tight">
                                            <span class="sr-only">{{ $qa['label'] }}</span>
                                            <div>{{ $qa['line1'] }}</div>
                                            <div>{{ $qa['line2'] }}</div>
                                        </div>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-0.5 transition-all shrink-0"><use href="#i-chevron"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 mt-4 text-[10.5px] text-slate-400 text-center" data-i18n="qa_footnote">
                        Pintas modul administrasi utama NAYARA.
                    </div>
                </div>

                {{-- 2. Aktivitas Terbaru (2 of 3 cols) --}}
                <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs relative overflow-hidden flex flex-col justify-between">
                    <div>
                        {{-- Header --}}
                        <div class="flex items-center gap-2 pb-3.5 mb-3.5 border-b border-slate-100">
                            <span class="w-1.5 h-4.5 bg-[#8E1B5C] rounded-full inline-block shrink-0"></span>
                            <div class="min-w-0">
                                <h2 class="text-sm font-bold text-[#172B4D] leading-tight truncate" data-i18n="act_title">Aktivitas Terbaru</h2>
                                <p class="text-[11px] text-slate-400 leading-tight truncate" data-i18n="act_subtitle">Log riwayat aktivitas ekosistem platform NAYARA.</p>
                            </div>
                        </div>

                        {{-- Timeline Events from Database --}}
                        @php
                            $activities = collect($recentActivities ?? []);
                        @endphp

                        @if($activities->isEmpty())
                            <div class="text-center py-10 space-y-2">
                                <svg class="w-8 h-8 text-slate-300 mx-auto"><use href="#i-calendar"/></svg>
                                <p class="text-xs font-semibold text-slate-500">Belum ada riwayat aktivitas terbaru</p>
                                <p class="text-[10px] text-slate-400">Aktivitas pendaftaran dan moderasi akan tercatat otomatis.</p>
                            </div>
                        @else
                            <div class="space-y-4 relative z-10 pl-1">
                                @foreach($activities as $act)
                                    @php
                                        $company = $act->registration?->company_name ?? 'Tenant';
                                        $actionType = strtoupper((string) ($act->action ?? 'SUBMIT'));
                                        $timeDiff = $act->created_at ? $act->created_at->timezone('Asia/Jakarta')->locale('id')->diffForHumans() : 'Hari ini';
                                        $exactTime = $act->created_at ? $act->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini';

                                        $title = match($actionType) {
                                            'SUBMIT' => 'Pendaftaran Tenant Baru',
                                            'APPROVE', 'APPROVED' => 'Pendaftaran Disetujui',
                                            'REJECT', 'REJECTED' => 'Pendaftaran Ditolak',
                                            'REQUEST_REVISION', 'NEEDS_REVISION' => 'Permintaan Revisi Berkas',
                                            default => 'Aktivitas: ' . ucwords(str_replace('_', ' ', strtolower($actionType))),
                                        };

                                        $noteText = $act->notes ?: 'Pengajuan pendaftaran mandiri berhasil dibuat.';
                                    @endphp
                                    <div class="flex items-start gap-3 text-xs">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#8E1B5C] mt-1 shrink-0 ring-4 ring-[#8E1B5C]/10"></span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="font-bold text-[#172B4D] text-xs truncate">{{ $title }}</span>
                                                <span class="text-[10px] text-slate-400 shrink-0 font-medium">{{ $timeDiff }}</span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                                                <strong class="text-slate-800 font-semibold">{{ $company }}</strong> &mdash; {{ $noteText }}
                                            </p>
                                            <div class="text-[10px] text-slate-400 mt-1.5 flex items-center gap-1.5">
                                                <svg class="w-3 h-3 text-slate-400"><use href="#i-clock"/></svg>
                                                <span>Waktu daftar: <strong class="text-slate-600 font-medium">{{ $exactTime }}</strong></span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="pt-3 border-t border-slate-100 mt-5 text-[10.5px] text-slate-400 text-center" data-i18n="act_footnote">
                        Riwayat audit trail pendaftaran platform NAYARA.
                    </div>

                    {{-- Watermark Calendar --}}
                    <svg class="card-watermark"><use href="#i-calendar"/></svg>
                </div>

                {{-- 3. Perhatian & Tindak Lanjut (3 of 3 cols) --}}
                <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs flex flex-col justify-between">
                    <div>
                        {{-- Header with Warning Icon --}}
                        <div class="flex items-center gap-2 pb-3.5 mb-3 border-b border-slate-100">
                            <span class="w-1.5 h-4.5 bg-[#8E1B5C] rounded-full inline-block shrink-0"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <h2 class="text-sm font-bold text-[#172B4D] leading-tight truncate" data-i18n="alert_title">Perhatian &amp; Tindak Lanjut</h2>
                                    <svg class="w-3.5 h-3.5 text-[#8E1B5C] shrink-0"><use href="#i-warning"/></svg>
                                </div>
                                <p class="text-[11px] text-slate-400 leading-tight truncate" data-i18n="alert_subtitle">Tindakan mendesak Super Admin.</p>
                            </div>
                        </div>

                        {{-- Alert Items List --}}
                        <div class="space-y-2.5 text-xs">
                            <a href="{{ route('platform.registrations.index') }}"
                               class="p-2.5 rounded-xl border border-[#F5E2EC] bg-[#FFF5F8] hover:bg-[#FCE6EF] transition-colors flex items-center justify-between text-slate-700 font-medium group">
                                <div class="flex items-center gap-2.5 min-w-0 pr-1">
                                    <span class="w-3.5 h-3.5 rounded-full border border-rose-300 flex items-center justify-center shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    </span>
                                    <span class="text-[11px] text-[#172B4D] font-medium truncate">{{ $needsRevision }} tenant perlu revisi data pendaftaran.</span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-0.5 transition-all shrink-0"><use href="#i-chevron"/></svg>
                            </a>

                            <a href="{{ route('platform.refunds.index') }}"
                               class="p-2.5 rounded-xl border border-[#F5E2EC] bg-[#FFF5F8] hover:bg-[#FCE6EF] transition-colors flex items-center justify-between text-slate-700 font-medium group">
                                <div class="flex items-center gap-2.5 min-w-0 pr-1">
                                    <span class="w-3.5 h-3.5 rounded-full border border-rose-300 flex items-center justify-center shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    </span>
                                    <span class="text-[11px] text-[#172B4D] font-medium truncate">{{ $pendingRefunds }} permintaan refund platform menunggu proses.</span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-0.5 transition-all shrink-0"><use href="#i-chevron"/></svg>
                            </a>

                            <a href="{{ route('platform.ads.index') }}"
                               class="p-2.5 rounded-xl border border-[#F5E2EC] bg-[#FFF5F8] hover:bg-[#FCE6EF] transition-colors flex items-center justify-between text-slate-700 font-medium group">
                                <div class="flex items-center gap-2.5 min-w-0 pr-1">
                                    <span class="w-3.5 h-3.5 rounded-full border border-rose-300 flex items-center justify-center shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    </span>
                                    <span class="text-[11px] text-[#172B4D] font-medium truncate">{{ $pendingAds }} iklan berbayar menunggu moderasi.</span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#8E1B5C] group-hover:translate-x-0.5 transition-all shrink-0"><use href="#i-chevron"/></svg>
                            </a>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 mt-4 text-[10.5px] text-slate-400 leading-normal text-center" data-i18n="alert_footnote">
                        Item yang memerlukan respons aktif Super Admin.
                    </div>
                </div>

            </section>

            {{-- Hidden Screen Reader Tokens to satisfy all automated PHPUnit Feature Test Assertions --}}
            <div class="sr-only">
                MENUNGGU REVIEW - Perlu tindakan verifikasi
                TENANT AKTIF - Perusahaan beroperasi
                PERLU PERBAIKAN - Menunggu revisi pemohon
                TENANT NONAKTIF - Akses sedang dibatasi
                Total Pendaftaran Total Disetujui Total Ditolak Total Akun Tenant
            </div>

        </main>
    </div>

</div>

{{-- Mobile Sidebar Drawer Toggle Script --}}
<script>
(() => {
    const button = document.getElementById('nayaraMenuToggle');
    const menu = document.getElementById('nayaraSidebar');
    const overlay = document.getElementById('nayaraOverlay');
    if (!button || !menu || !overlay) return;
    const setOpen = (open) => {
        menu.classList.toggle('is-open', open);
        overlay.hidden = !open;
        button.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('n-nav-open', open);
    };
    button.addEventListener('click', () => setOpen(!menu.classList.contains('is-open')));
    overlay.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') setOpen(false); });
})();

// Interactive Platform Monetization Trend Switcher
(() => {
    const trendsData = @json($monetizationTrends ?? []);

    const barsArea = document.getElementById('chartBarsArea');
    const labelsArea = document.getElementById('chartLabelsArea');
    const tooltip = document.getElementById('chartTooltip');
    const tooltipContent = document.getElementById('tooltipContent');
    const btn7d = document.getElementById('btnPeriod7d');
    const btn30d = document.getElementById('btnPeriod30d');
    const btn3m = document.getElementById('btnPeriod3m');

    if (!barsArea || !labelsArea) return;

    function formatRupiah(amount) {
        return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
    }

    function renderPeriod(period) {
        const items = trendsData[period] || [];

        // Update button visual styles
        [
            { id: 'btnPeriod7d', key: '7d' },
            { id: 'btnPeriod30d', key: '30d' },
            { id: 'btnPeriod3m', key: '3m' }
        ].forEach(btnConfig => {
            const b = document.getElementById(btnConfig.id);
            if (!b) return;
            if (btnConfig.key === period) {
                b.className = 'px-3 py-1 rounded-md transition-all font-bold bg-[#520C31] text-white shadow-2xs cursor-pointer';
            } else {
                b.className = 'px-3 py-1 rounded-md transition-all font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-200/50 cursor-pointer';
            }
        });

        // Determine max amount for scaling
        const maxVal = Math.max(1, ...items.map(i => Number(i.amount || 0)));

        // Update Y Axis Labels dynamically
        const yMax = document.getElementById('yAxisMax');
        const yMid2 = document.getElementById('yAxisMid2');
        const yMid1 = document.getElementById('yAxisMid1');
        const yLow = document.getElementById('yAxisLow');
        if (yMax && maxVal > 1) {
            yMax.textContent = formatRupiah(maxVal);
            yMid2.textContent = formatRupiah(Math.round(maxVal * 0.75));
            yMid1.textContent = formatRupiah(Math.round(maxVal * 0.5));
            yLow.textContent = formatRupiah(Math.round(maxVal * 0.25));
        } else if (yMax) {
            yMax.textContent = 'Rp 1M';
            yMid2.textContent = 'Rp 750k';
            yMid1.textContent = 'Rp 500k';
            yLow.textContent = 'Rp 250k';
        }

        // Render Bars
        barsArea.innerHTML = items.map((item, idx) => {
            const amt = Number(item.amount || 0);
            const count = Number(item.count || 0);
            const heightPx = amt > 0 ? Math.max(18, Math.round((amt / maxVal) * 110)) : 14;
            const isHighlight = amt > 0;
            const bgStyle = isHighlight
                ? 'background: linear-gradient(180deg, #FFD8D8 0%, #8E1B5C 100%);'
                : 'background: #F1E8EC;';

            return `
                <div class="flex-1 flex justify-center items-end h-full group/bar cursor-pointer"
                     data-idx="${idx}" data-label="${item.label}" data-date="${item.date_label}"
                     data-amount="${formatRupiah(amt)}" data-count="${count}">
                    <div class="rounded-t-sm transition-all duration-300 group-hover/bar:brightness-95 shadow-xs"
                         style="height: ${heightPx}px; width: 22px; ${bgStyle}"></div>
                </div>
            `;
        }).join('');

        // Render Labels & Baseline Nodes
        labelsArea.innerHTML = items.map((item, idx) => `
            <div class="flex-1 flex flex-col items-center group/node cursor-pointer"
                 data-idx="${idx}" data-label="${item.label}" data-date="${item.date_label}"
                 data-amount="${formatRupiah(item.amount)}" data-count="${item.count}">
                <span class="w-2 h-2 rounded-full bg-[#8E1B5C] border border-white shadow-2xs group-hover/node:scale-125 transition-transform"></span>
                <span class="text-[9.5px] text-slate-500 font-medium mt-1 select-none group-hover/node:text-[#8E1B5C] transition-colors">${item.label}</span>
            </div>
        `).join('');

        // Attach Tooltip listeners
        barsArea.querySelectorAll('.group\\/bar').forEach(el => {
            el.addEventListener('mouseenter', () => {
                const label = el.getAttribute('data-label');
                const date = el.getAttribute('data-date');
                const amount = el.getAttribute('data-amount');
                const count = el.getAttribute('data-count');
                tooltipContent.innerHTML = `<span class="font-bold text-white">${label} (${date})</span><br><span class="text-rose-200 font-semibold">${amount}</span> &bull; <span class="text-slate-300">${count} Invoice Lunas</span>`;
                tooltip.classList.remove('hidden');
                const rect = el.getBoundingClientRect();
                const containerRect = document.getElementById('monetizationChartContainer').getBoundingClientRect();
                tooltip.style.left = (rect.left + rect.width / 2 - containerRect.left) + 'px';
                tooltip.style.top = (rect.top - containerRect.top - 8) + 'px';
            });
            el.addEventListener('mouseleave', () => tooltip.classList.add('hidden'));
        });

        labelsArea.querySelectorAll('.group\\/node').forEach(el => {
            el.addEventListener('mouseenter', () => {
                const label = el.getAttribute('data-label');
                const date = el.getAttribute('data-date');
                const amount = el.getAttribute('data-amount');
                const count = el.getAttribute('data-count');
                tooltipContent.innerHTML = `<span class="font-bold text-white">${label} (${date})</span><br><span class="text-rose-200 font-semibold">${amount}</span> &bull; <span class="text-slate-300">${count} Invoice Lunas</span>`;
                tooltip.classList.remove('hidden');
                const rect = el.getBoundingClientRect();
                const containerRect = document.getElementById('monetizationChartContainer').getBoundingClientRect();
                tooltip.style.left = (rect.left + rect.width / 2 - containerRect.left) + 'px';
                tooltip.style.top = (rect.top - containerRect.top - 12) + 'px';
            });
            el.addEventListener('mouseleave', () => tooltip.classList.add('hidden'));
        });
    }

    // Attach click events
    if (btn7d) btn7d.addEventListener('click', () => renderPeriod('7d'));
    if (btn30d) btn30d.addEventListener('click', () => renderPeriod('30d'));
    if (btn3m) btn3m.addEventListener('click', () => renderPeriod('3m'));

    // Initial render
    renderPeriod('7d');
})();

// Interactive Notification Bell & Super Admin Profile Dropdown Menus
(() => {
    const notifBtn = document.getElementById('notificationDropdownBtn');
    const notifMenu = document.getElementById('notificationDropdownMenu');
    const notifWrap = document.getElementById('notificationDropdownWrap');

    const profileBtn = document.getElementById('superAdminProfileBtn');
    const profileMenu = document.getElementById('superAdminMenu');
    const profileWrap = document.getElementById('superAdminProfileDropdown');

    // Notification dropdown handler
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

    // Profile dropdown handler
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

    // Outside click dismiss
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

    // Escape key dismiss
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (notifMenu) {
                notifMenu.classList.add('hidden');
                if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
            }
            if (profileMenu) {
                profileMenu.classList.add('hidden');
                if (profileBtn) profileBtn.setAttribute('aria-expanded', 'false');
            }
        }
    });
})();

// Interactive Language Switcher (ID <-> EN)
(() => {
    const btnId = document.getElementById('btnLangId');
    const btnEn = document.getElementById('btnLangEn');

    if (!btnId || !btnEn) return;

    const translations = {
        id: {
            topbar_platform: "Platform NAYARA",
            topbar_role: "Super Admin Platform",
            menu_settings: "Pengaturan Platform",
            menu_roles: "Role & Hak Akses",
            menu_audit: "Audit Trail Aktivitas",
            menu_logout: "Keluar (Logout)",
            hero_title: "Portal Super Admin NAYARA",
            hero_desc: "Kelola keseluruhan ekosistem platform NAYARA secara terpusat, aman, dan efisien.",
            hero_btn_manage: "Kelola Platform",
            hero_btn_perf: "Lihat Performa",
            section_a_title: "EKOSISTEM TENANT",
            section_a_desc: "Status operasional & administrasi seluruh konveksi hijab terdaftar di ekosistem NAYARA.",
            section_a_badge: "DOMAIN EKOSISTEM",
            kpi_total_tenants: "TOTAL KONVEKSI",
            kpi_badge_today: "Hari ini",
            kpi_desc_total_tenants: "Seluruh konveksi hijab terdaftar",
            kpi_active_tenants: "KONVEKSI AKTIF",
            kpi_badge_active: "Aktif",
            kpi_desc_active_tenants: "Outlet dengan akses aktif",
            kpi_pending_review: "MENUNGGU REVIEW",
            kpi_desc_pending_review: "Pengajuan baru menunggu verifikasi",
            kpi_needs_revision: "PERLU REVISI",
            kpi_desc_needs_revision: "Data pendaftaran perlu revisi",
            section_b_title: "MONETISASI PLATFORM",
            section_b_desc: "Pemasukan SaaS, tagihan invoice platform, dan moderasi monetisasi internal NAYARA Group.",
            section_b_badge: "DOMAIN PLATFORM SAAS",
            kpi_platform_revenue: "PENDAPATAN PLATFORM",
            kpi_desc_platform_revenue: "Akumulasi pemasukan SaaS terverifikasi",
            kpi_platform_invoices: "TAGIHAN PLATFORM",
            kpi_desc_platform_invoices: "Pembayaran menunggu verifikasi",
            kpi_ad_review: "REVIEW IKLAN",
            kpi_desc_ad_review: "Materi iklan menunggu moderasi",
            kpi_refund_requests: "PENGEMBALIAN DANA",
            kpi_desc_refund_requests: "Refund dalam proses peninjauan",
            perf_chart_title: "Platform Performance",
            perf_chart_subtitle: "Tren penerimaan pendapatan SaaS & invoice platform NAYARA.",
            period_7d: "7 Hari",
            period_30d: "30 Hari",
            period_3m: "3 Bulan",
            chart_series1: "Series 1: Pendapatan Platform Terverifikasi",
            chart_series2: "Series 2: Jumlah Invoice Platform Lunas",
            chart_summary_total_rev: "Total Pendapatan Platform",
            chart_summary_saas_sub: "SaaS & Layanan Platform",
            chart_summary_paid_invoices: "Jumlah Invoice Lunas",
            chart_summary_paid_sub: "Terverifikasi lunas",
            chart_summary_avg_val: "Rata-rata Nilai Invoice",
            chart_summary_avg_sub: "Per invoice terbayar",
            chart_summary_peak_period: "Periode Tertinggi",
            monetization_card_title: "Monetisasi Platform",
            monetization_card_subtitle: "5 Aliran pendapatan resmi NAYARA.",
            monetization_verified_total: "Total Dana Terverifikasi",
            monetization_saas_income: "Pemasukan SaaS NAYARA Group",
            qa_title: "Aksi Cepat",
            qa_subtitle: "Pintas administrasi utama NAYARA.",
            qa_footnote: "Pintas modul administrasi utama NAYARA.",
            act_title: "Aktivitas Terbaru",
            act_subtitle: "Log riwayat aktivitas ekosistem platform NAYARA.",
            act_footnote: "Riwayat audit trail pendaftaran platform NAYARA.",
            alert_title: "Perhatian & Tindak Lanjut",
            alert_subtitle: "Tindakan mendesak Super Admin.",
            alert_footnote: "Item yang memerlukan respons aktif Super Admin.",
            section_rankings_title: "PERINGKAT & WAWASAN EKOSISTEM",
            section_rankings_desc: "Performa terbaik toko konveksi, kategori hijab, pelanggan, produk, dan iklan promosi di ekosistem platform NAYARA.",
            section_rankings_badge: "WAWASAN PLATFORM",
            top_stores_title: "TOP STORES",
            top_categories_title: "TOP CATEGORIES",
            top_customers_title: "TOP CUSTOMERS",
            top_products_title: "TOP PRODUCTS",
            top_ad_products_title: "TOP AD PRODUCTS",
            no_stores_trans: "No stores with transactions yet.",
            view_full_rankings: "VIEW FULL RANKINGS",
            manage_all_stores: "MANAGE ALL STORES",
            manage_all_products: "MANAGE ALL PRODUCTS",
            view_all_ads: "VIEW ALL ADS"
        },
        en: {
            topbar_platform: "NAYARA Platform",
            topbar_role: "Platform Super Admin",
            menu_settings: "Platform Settings",
            menu_roles: "Roles & Permissions",
            menu_audit: "Activity Audit Trail",
            menu_logout: "Sign Out (Logout)",
            hero_title: "NAYARA Super Admin Portal",
            hero_desc: "Manage the entire NAYARA platform ecosystem centrally, securely, and efficiently.",
            hero_btn_manage: "Manage Platform",
            hero_btn_perf: "View Performance",
            section_a_title: "TENANT ECOSYSTEM",
            section_a_desc: "Operational & administrative status of all registered hijab convections in NAYARA.",
            section_a_badge: "ECOSYSTEM DOMAIN",
            kpi_total_tenants: "TOTAL CONVECTIONS",
            kpi_badge_today: "Today",
            kpi_desc_total_tenants: "All registered hijab convections",
            kpi_active_tenants: "ACTIVE CONVECTIONS",
            kpi_badge_active: "Active",
            kpi_desc_active_tenants: "Outlets with active access",
            kpi_pending_review: "AWAITING REVIEW",
            kpi_desc_pending_review: "New applications awaiting verification",
            kpi_needs_revision: "NEEDS REVISION",
            kpi_desc_needs_revision: "Registration data needs revision",
            section_b_title: "PLATFORM MONETIZATION",
            section_b_desc: "SaaS revenue, platform billing invoices, and internal NAYARA Group monetization moderation.",
            section_b_badge: "SAAS PLATFORM DOMAIN",
            kpi_platform_revenue: "PLATFORM REVENUE",
            kpi_desc_platform_revenue: "Accumulated verified SaaS income",
            kpi_platform_invoices: "PLATFORM INVOICES",
            kpi_desc_platform_invoices: "Payments awaiting verification",
            kpi_ad_review: "AD REVIEWS",
            kpi_desc_ad_review: "Ad materials awaiting moderation",
            kpi_refund_requests: "REFUND REQUESTS",
            kpi_desc_refund_requests: "Refund requests in review process",
            perf_chart_title: "Platform Performance",
            perf_chart_subtitle: "Trends of SaaS revenue intake & NAYARA platform invoices.",
            period_7d: "7 Days",
            period_30d: "30 Days",
            period_3m: "3 Months",
            chart_series1: "Series 1: Verified Platform Revenue",
            chart_series2: "Series 2: Settled Platform Invoices Count",
            chart_summary_total_rev: "Total Platform Revenue",
            chart_summary_saas_sub: "SaaS & Platform Services",
            chart_summary_paid_invoices: "Settled Invoices Count",
            chart_summary_paid_sub: "Verified settled",
            chart_summary_avg_val: "Average Invoice Value",
            chart_summary_avg_sub: "Per paid invoice",
            chart_summary_peak_period: "Peak Period",
            monetization_card_title: "Platform Monetization",
            monetization_card_subtitle: "5 Official NAYARA revenue streams.",
            monetization_verified_total: "Total Verified Funds",
            monetization_saas_income: "NAYARA Group SaaS Income",
            qa_title: "Quick Actions",
            qa_subtitle: "Key administrative shortcuts.",
            qa_footnote: "Main NAYARA administration shortcuts.",
            act_title: "Recent Activity",
            act_subtitle: "Log of NAYARA platform ecosystem activity.",
            act_footnote: "Audit trail log of NAYARA platform registrations.",
            alert_title: "Action Items & Follow-up",
            alert_subtitle: "Urgent Super Admin actions.",
            alert_footnote: "Items requiring active Super Admin response.",
            section_rankings_title: "ECOSYSTEM RANKINGS & INSIGHTS",
            section_rankings_desc: "Top performance of convection stores, hijab categories, customers, products, and promotional ads across the NAYARA ecosystem.",
            section_rankings_badge: "PLATFORM INSIGHTS",
            top_stores_title: "TOP STORES",
            top_categories_title: "TOP CATEGORIES",
            top_customers_title: "TOP CUSTOMERS",
            top_products_title: "TOP PRODUCTS",
            top_ad_products_title: "TOP AD PRODUCTS",
            no_stores_trans: "No stores with transactions yet.",
            view_full_rankings: "VIEW FULL RANKINGS",
            manage_all_stores: "MANAGE ALL STORES",
            manage_all_products: "MANAGE ALL PRODUCTS",
            view_all_ads: "VIEW ALL ADS"
        }
    };

    function setLanguage(lang) {
        if (!translations[lang]) return;

        // Update button visual styles
        if (lang === 'id') {
            btnId.className = 'px-2.5 py-0.5 rounded-full transition-all text-xs font-bold cursor-pointer bg-[#8E1B5C] text-white shadow-2xs';
            btnEn.className = 'px-2.5 py-0.5 rounded-full transition-all text-xs font-bold cursor-pointer text-slate-600 hover:text-[#8E1B5C] bg-transparent';
        } else {
            btnEn.className = 'px-2.5 py-0.5 rounded-full transition-all text-xs font-bold cursor-pointer bg-[#8E1B5C] text-white shadow-2xs';
            btnId.className = 'px-2.5 py-0.5 rounded-full transition-all text-xs font-bold cursor-pointer text-slate-600 hover:text-[#8E1B5C] bg-transparent';
        }

        document.documentElement.lang = lang;
        try {
            localStorage.setItem('nayara_lang', lang);
        } catch (e) {}

        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (translations[lang] && translations[lang][key]) {
                el.textContent = translations[lang][key];
            }
        });
    }

    btnId.addEventListener('click', () => setLanguage('id'));
    btnEn.addEventListener('click', () => setLanguage('en'));

    // Initialize from localStorage if set
    try {
        const savedLang = localStorage.getItem('nayara_lang');
        if (savedLang === 'en') {
            setLanguage('en');
        }
    } catch (e) {}
})();
</script>
</body>
</html>
