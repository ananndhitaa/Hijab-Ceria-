<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Hijab ERP' }}</title>
    <meta name="description" content="{{ $description ?? 'Sistem ERP Produksi Hijab - Manajemen Bahan, Produksi, dan Keuangan' }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#8E1B5C',
                            dark:    '#68113F',
                            deep:    '#520C31',
                            medium:  '#701D49',
                            active:  '#7F345C',
                            light:   '#F7E6EE',
                        },
                        maroon: {
                            DEFAULT: '#8E1B5C',
                            dark:    '#68113F',
                            deep:    '#520C31',
                            medium:  '#701D49',
                            active:  '#7F345C',
                            light:   '#F7E6EE',
                        },
                        nayara: {
                            deep:            '#520C31',
                            burgundy:        '#68113F',
                            primary:         '#8E1B5C',
                            'primary-medium':'#701D49',
                            'sidebar-active':'#7F345C',
                            pink:            '#D96B9B',
                            blush:           '#F7E6EE',
                            border:          '#E1CFD9',
                            background:      '#FFF9FB',
                            white:           '#FFFFFF',
                        },
                        accent: {
                            DEFAULT: '#ED3500',
                            hover:   '#D62F00',
                            light:   '#F7E6EE',
                        },
                        'soft-pink': '#F7E6EE',
                        'page-bg':   '#FFF9FB',
                        'card':      '#FFFFFF',
                        'border-c':  '#E1CFD9',
                        'text-main': '#172B4D',
                        'text-sec':  '#64748B',
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                    boxShadow: {
                        'card': '0 1px 3px rgba(142, 27, 92, 0.05), 0 1px 2px rgba(0, 0, 0, 0.04)',
                        'card-hover': '0 4px 6px rgba(142, 27, 92, 0.08), 0 2px 4px rgba(0, 0, 0, 0.06)',
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="{{ asset('css/nayara-ui.css') . '?v=20261105-maroon' }}">

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Global Styles -->
    <style>
        [x-cloak] { display: none !important; }
        html, body {
            min-height: 100%;
            height: auto;
            overflow-x: hidden;
            overflow-y: auto;
        }
        body { font-family: 'Inter', sans-serif; }

        @media print {
            html, body {
                height: auto !important;
                overflow: visible !important;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #FFFCFB; }
        ::-webkit-scrollbar-thumb { background: #FFD8D8; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #8E1B5C; }

        /* Badge utilities - NAYARA Global Design System */
        .badge { @apply inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap border transition-colors; }
        .badge-primary   { @apply bg-[#FFD8D8] text-[#8E1B5C] border-[#FFD8D8]; }
        .badge-success   { @apply bg-emerald-50 text-emerald-700 border-emerald-200; }
        .badge-warning   { @apply bg-amber-50 text-amber-700 border-amber-200; }
        .badge-danger    { @apply bg-red-50 text-red-700 border-red-200; }
        .badge-secondary { @apply bg-slate-50 text-slate-700 border-slate-200; }
        .badge-blue      { @apply bg-[#FFD8D8] text-[#8E1B5C] border-[#FFD8D8]; }
        .badge-purple    { @apply bg-[#FFD8D8] text-[#8E1B5C] border-[#FFD8D8]; }
        .badge-cyan      { @apply bg-[#FFD8D8] text-[#8E1B5C] border-[#FFD8D8]; }
        .badge-teal      { @apply bg-teal-50 text-teal-700 border-teal-200; }
        .badge-pink      { @apply bg-[#FFD8D8] text-[#8E1B5C] border-[#FFD8D8]; }
        .badge-indigo    { @apply bg-[#FFD8D8] text-[#8E1B5C] border-[#FFD8D8]; }
        .badge-amber     { @apply bg-amber-50 text-amber-700 border-amber-200; }
        .badge-orange    { @apply bg-[#FFD8D8] text-[#ED3500] border-[#FFD8D8]; }

        /* Button */
        .btn-primary {
            @apply inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg
                   hover:bg-primary-dark transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/50;
        }
        .btn-outline {
            @apply inline-flex items-center gap-2 px-4 py-2 border border-border-c text-text-main text-sm font-medium rounded-lg
                   hover:bg-soft-pink hover:border-primary/30 transition-all duration-200;
        }
        .btn-danger {
            @apply inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg
                   hover:bg-red-700 transition-all duration-200;
        }

        /* Form inputs */
        .form-input {
            @apply w-full px-3 py-2 text-sm border border-border-c rounded-lg bg-card text-text-main
                   focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                   placeholder-text-sec transition-all duration-200;
        }
        .form-label {
            @apply block text-sm font-medium text-text-main mb-1;
        }
        .form-error {
            @apply text-xs text-red-600 mt-1;
        }

        /* Card */
        .card {
            @apply bg-card border border-border-c rounded-xl shadow-card;
        }
        .card-header {
            @apply px-6 py-4 border-b border-border-c;
        }
        .card-body {
            @apply px-6 py-4;
        }

        /* Table */
        .data-table { @apply w-full text-sm; }
        .data-table thead tr { @apply bg-soft-pink; }
        .data-table thead th {
            @apply px-4 py-3 text-left text-xs font-semibold text-text-main uppercase tracking-wider;
        }
        .data-table tbody tr { @apply border-b border-border-c hover:bg-soft-pink/50 transition-colors; }
        .data-table tbody td { @apply px-4 py-3 text-text-main; }

        /* Sidebar active */
        .sidebar-link {
            @apply flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-text-sec
                   hover:bg-soft-pink hover:text-text-main transition-all duration-200 cursor-pointer;
        }
        .sidebar-link.active {
            @apply bg-primary text-white;
        }

        /* Toast */
        .toast {
            @apply fixed top-4 right-4 z-50 px-5 py-3 rounded-xl shadow-lg text-sm font-medium
                   flex items-center gap-2 transition-all duration-300;
        }
        .toast-success { @apply bg-emerald-600 text-white; }
        .toast-error   { @apply bg-red-600 text-white; }
        .toast-info    { @apply bg-primary text-white; }

        /* Animations */
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-in { animation: slideIn 0.3s ease-out; }
        .animate-fade-in  { animation: fadeIn 0.2s ease-out; }

        /* Number formatting helper */
        .currency::before { content: 'Rp '; }

        /* Numeric typography & Tabular alignment */
        .tabular-nums, .num-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
        }
    </style>
</head>
<body class="bg-page-bg text-text-main antialiased min-h-screen overflow-x-hidden">

    <div x-data="{ sidebarOpen: false }" class="min-h-screen w-full relative">

        {{-- Mobile backdrop --}}
        <div x-show="sidebarOpen"
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/50 z-30 lg:hidden"
             x-cloak></div>

        {{-- ── Sidebar ──────────────────────────────────────────── --}}
        @include('components.sidebar')

        {{-- ── Main Content ─────────────────────────────────────── --}}
        <div class="lg:ml-64 min-h-screen min-w-0 flex flex-col">

            {{-- Topbar --}}
            @php
                $hour = now('Asia/Jakarta')->hour;
                $greeting = match(true) {
                    $hour >= 5 && $hour < 11 => 'Selamat pagi',
                    $hour >= 11 && $hour < 15 => 'Selamat siang',
                    $hour >= 15 && $hour < 18 => 'Selamat sore',
                    default => 'Selamat malam',
                };
            @endphp
            <header class="sticky top-0 z-20 h-16 bg-card border-b border-border-c flex items-center justify-between px-6 flex-shrink-0">
                <div class="flex items-center gap-3">
                    {{-- Mobile menu toggle --}}
                    <button @click="sidebarOpen = !sidebarOpen"
                            class="lg:hidden p-2 rounded-lg hover:bg-soft-pink text-text-sec transition-colors"
                            aria-label="Toggle Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    {{-- Page title / Breadcrumb in Top Shell --}}
                    <div>
                        @if(isset($breadcrumb) && !str_contains(request()->route()?->getName() ?? '', 'dashboard') && !str_contains($pageTitle ?? '', 'Dashboard'))
                            <nav aria-label="Breadcrumb">
                                <p class="text-xs font-medium text-[#64748B]">{{ $breadcrumb }}</p>
                            </nav>
                        @else
                            <h1 class="text-lg sm:text-[22px] font-bold text-text-main tracking-tight leading-tight">{{ $pageTitle ?? 'Dashboard' }}</h1>
                            @isset($breadcrumb)
                                <p class="text-xs text-text-sec">{{ $breadcrumb }}</p>
                            @endisset
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Dynamic Greeting --}}
                    <span class="hidden md:inline-block text-xs sm:text-sm text-[#64748B] font-medium">
                        {{ $greeting }}
                    </span>

                    {{-- User dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-soft-pink transition-colors">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-semibold flex-shrink-0">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <p class="text-sm font-medium text-text-main leading-tight">{{ auth()->user()->name }}</p>
                            </div>
                            <svg class="w-4 h-4 text-text-sec" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-48 bg-white border border-border-c rounded-xl shadow-lg z-50 py-1">
                            <div class="px-4 py-3 border-b border-border-c">
                                <p class="text-sm font-medium text-text-main truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-text-sec truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main-content" class="flex-1 min-w-0 p-4 sm:p-6">
                <div class="max-w-7xl mx-auto animate-fade-in">
                    <x-admin.flash-message />
                    {{ $slot ?? '' }}
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

</body>
</html>
