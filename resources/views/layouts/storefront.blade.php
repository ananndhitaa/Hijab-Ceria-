<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'NAYARA — Pusat Hijab Berkualitas & Platform Promosi Konveksi Mandiri' }}</title>
    <meta name="description" content="{{ $description ?? 'Koleksi busana muslim dan hijab berkualitas tinggi dari produsen konveksi mandiri Indonesia. Pesan langsung tangan pertama via WhatsApp tanpa komisi.' }}">

    <!-- Google Fonts: Inter & Playfair Display -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Vite / Production Fallback) -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                            serif: ['Playfair Display', 'serif'],
                        },
                        colors: {
                            nayara: {
                                900: '#520C31',
                                800: '#701D49',
                                700: '#8E1B5C',
                                600: '#A12768',
                                500: '#B63378',
                                100: '#FCEAEF',
                                50:  '#FFF9FB',
                            }
                        }
                    }
                }
            }
        </script>
    @endif

    <!-- Alpine.js Collapse Plugin + CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }

        /* Sembunyikan scrollbar browser namun tetap bisa di-scroll dengan mulus */
        html::-webkit-scrollbar,
        body::-webkit-scrollbar,
        *::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        html, body {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
            overflow-x: clip !important;
        }
    </style>
    @stack('styles')
</head>
<body x-data="{
    mobileSidebarOpen: false,
    categoryDropdownOpen: false,
    categoryMobileOpen: true,
    wishlistModalOpen: false,
    toastMessage: '',
    showToast: false,
    wishlist: (function() {
        try {
            return JSON.parse(localStorage.getItem('nayara_wishlist') || '[]');
        } catch(e) {
            return [];
        }
    })(),
    toggleWishlist(prod) {
        let idx = this.wishlist.findIndex(item => item.id == prod.id);
        if (idx > -1) {
            this.wishlist.splice(idx, 1);
            this.notify('Produk dihapus dari favorit');
        } else {
            this.wishlist.push(prod);
            this.notify('Produk ditambahkan ke favorit');
        }
        try {
            localStorage.setItem('nayara_wishlist', JSON.stringify(this.wishlist));
        } catch(e) {}
    },
    removeFromWishlist(id) {
        this.wishlist = this.wishlist.filter(item => item.id != id);
        try {
            localStorage.setItem('nayara_wishlist', JSON.stringify(this.wishlist));
        } catch(e) {}
        this.notify('Produk dihapus dari favorit');
    },
    isWishlisted(id) {
        return this.wishlist.some(item => item.id == id);
    },
    notify(msg) {
        this.toastMessage = msg;
        this.showToast = true;
        setTimeout(() => { this.showToast = false; }, 3000);
    }
}" class="bg-white text-[#1E1B1D] antialiased min-h-screen flex flex-col font-sans selection:bg-[#FCEAEF] selection:text-[#8E1B5C]">

    {{-- Main Navigation Bar with NAYARA Official Logo (Sticky On Scroll & Sleek) --}}
    <header class="sticky top-0 z-50 w-full bg-white/98 backdrop-blur-md border-b border-[#EFE5EA] shadow-xs transition-shadow duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-18 flex items-center justify-between gap-3 lg:gap-5">
            
            {{-- Brand Logo & Mobile Menu Toggle --}}
            <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                <button type="button"
                        @click="mobileSidebarOpen = true"
                        class="md:hidden p-2 -ml-2 rounded-xl text-[#520C31] hover:bg-[#FCEAEF] transition-colors"
                        aria-label="Buka Menu Navigasi & Kategori Hijab">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>

                <a href="{{ route('landing') }}" class="flex items-center gap-2.5 sm:gap-3 shrink-0 group">
                    <img src="{{ asset('images/nayara-logo.jpg') }}" alt="NAYARA" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl object-contain shadow-xs border border-[#E1CFD9] group-hover:scale-105 transition-transform">
                    <div class="space-y-0.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-[#1E1B1D] font-serif leading-none block">
                            NAYARA
                        </span>
                        <span class="text-[9px] sm:text-[9.5px] font-semibold text-[#8E1B5C] tracking-wide block">
                            Pusat Hijab Berkualitas
                        </span>
                    </div>
                </a>
            </div>

            {{-- Main Navigation Links (Strictly Single-line with whitespace-nowrap) --}}
            <nav class="hidden md:flex items-center gap-4 lg:gap-6 xl:gap-7 text-xs lg:text-[13px] font-medium text-[#4A4447] whitespace-nowrap">
                <a href="{{ route('landing') }}" class="relative py-2 shrink-0 inline-flex items-center transition-colors {{ request()->routeIs('landing') && !request('category') ? 'text-[#8E1B5C] font-bold' : 'hover:text-[#8E1B5C]' }}">
                    <span>Beranda</span>
                    @if(request()->routeIs('landing') && !request('category'))
                        <span class="absolute bottom-0 inset-x-0 h-0.5 bg-[#8E1B5C] rounded-full"></span>
                    @endif
                </a>
                
                {{-- Dropdown: Koleksi Hijab (Hover + Click Toggleable) --}}
                <div class="relative group shrink-0" @click.outside="categoryDropdownOpen = false">
                    <button type="button"
                            @click="categoryDropdownOpen = !categoryDropdownOpen"
                            class="relative py-2 shrink-0 inline-flex items-center gap-1 hover:text-[#8E1B5C] transition-colors cursor-pointer font-medium text-xs lg:text-[13px] whitespace-nowrap {{ request('category') ? 'text-[#8E1B5C] font-bold' : 'text-[#4A4447]' }}"
                            :aria-expanded="categoryDropdownOpen.toString()">
                        <span>Koleksi Hijab</span>
                        <svg class="w-3.5 h-3.5 text-[#665D62] transition-transform duration-200"
                             :class="categoryDropdownOpen ? 'rotate-180 text-[#8E1B5C]' : 'group-hover:rotate-180'"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                        @if(request('category'))
                            <span class="absolute bottom-0 inset-x-0 h-0.5 bg-[#8E1B5C] rounded-full"></span>
                        @endif
                    </button>
                    <div class="absolute left-0 top-full pt-1 w-52 z-50 transition-all duration-150"
                         :class="categoryDropdownOpen ? 'block' : 'hidden group-hover:block'">
                        <div class="bg-white rounded-2xl shadow-xl border border-[#EFE5EA] py-2 text-xs font-medium text-[#1E1B1D] divide-y divide-[#F6EDF2]">
                            <div class="px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-[#8E1B5C] bg-[#FCEAEF]/50">
                                Kategori Hijab
                            </div>
                            <div class="py-1">
                                <a href="{{ route('landing', ['category' => 'Hijab Polos']) }}" class="flex items-center justify-between px-4 py-2 hover:bg-[#FCEAEF] hover:text-[#8E1B5C] transition-colors">
                                    <span>Hijab Polos</span>
                                    <span class="text-[10px] text-[#888185]">Voal &amp; Paris</span>
                                </a>
                                <a href="{{ route('landing', ['category' => 'Hijab Motif']) }}" class="flex items-center justify-between px-4 py-2 hover:bg-[#FCEAEF] hover:text-[#8E1B5C] transition-colors">
                                    <span>Hijab Motif</span>
                                    <span class="text-[10px] text-[#888185]">Floral Print</span>
                                </a>
                                <a href="{{ route('landing', ['category' => 'Hijab Viscose']) }}" class="flex items-center justify-between px-4 py-2 hover:bg-[#FCEAEF] hover:text-[#8E1B5C] transition-colors">
                                    <span>Hijab Viscose</span>
                                    <span class="text-[10px] text-[#888185]">Linen &amp; Silk</span>
                                </a>
                                <a href="{{ route('landing', ['category' => 'Hijab Segi Empat']) }}" class="flex items-center justify-between px-4 py-2 hover:bg-[#FCEAEF] hover:text-[#8E1B5C] transition-colors">
                                    <span>Hijab Segi Empat</span>
                                    <span class="text-[10px] text-[#888185]">Square Basic</span>
                                </a>
                                <a href="{{ route('landing', ['category' => 'Pashmina']) }}" class="flex items-center justify-between px-4 py-2 hover:bg-[#FCEAEF] hover:text-[#8E1B5C] transition-colors">
                                    <span>Pashmina</span>
                                    <span class="text-[10px] text-[#888185]">Silk &amp; Flow</span>
                                </a>
                                <a href="{{ route('landing', ['category' => 'Hijab Instan']) }}" class="flex items-center justify-between px-4 py-2 hover:bg-[#FCEAEF] hover:text-[#8E1B5C] transition-colors">
                                    <span>Hijab Instan</span>
                                    <span class="text-[10px] text-[#888185]">Bergo Daily</span>
                                </a>
                            </div>
                            <div class="p-1.5">
                                <a href="{{ route('landing') }}" class="block text-center py-1.5 text-[11px] font-bold text-[#8E1B5C] hover:bg-[#FCEAEF] rounded-lg transition-colors">
                                    Lihat Semua Kategori &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('landing') }}#produk-unggulan" class="relative py-2 shrink-0 inline-flex items-center hover:text-[#8E1B5C] transition-colors">
                    <span>Produk Terbaru</span>
                </a>
                <a href="{{ route('landing') }}#promo-iklan" class="relative py-2 shrink-0 inline-flex items-center hover:text-[#8E1B5C] transition-colors">
                    <span>Promo &amp; Iklan</span>
                </a>
                <a href="{{ route('storefront.shops') }}" class="relative py-2 shrink-0 inline-flex items-center transition-colors {{ request()->routeIs('storefront.shops*') ? 'text-[#8E1B5C] font-bold' : 'hover:text-[#8E1B5C]' }}">
                    <span>Direktori Pabrik</span>
                    @if(request()->routeIs('storefront.shops*'))
                        <span class="absolute bottom-0 inset-x-0 h-0.5 bg-[#8E1B5C] rounded-full"></span>
                    @endif
                </a>
                <a href="{{ route('storefront.about') }}" class="relative py-2 shrink-0 inline-flex items-center transition-colors {{ request()->routeIs('storefront.about*') ? 'text-[#8E1B5C] font-bold' : 'hover:text-[#8E1B5C]' }}">
                    <span>Tentang Kami</span>
                    @if(request()->routeIs('storefront.about*'))
                        <span class="absolute bottom-0 inset-x-0 h-0.5 bg-[#8E1B5C] rounded-full"></span>
                    @endif
                </a>
            </nav>

            {{-- Right Search Bar & Actions --}}
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                
                {{-- Compact Search Input --}}
                <form action="{{ route('landing') }}" method="GET" class="hidden lg:flex items-center relative w-44 xl:w-56 shrink-0">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari hijab, motif, warna..."
                           class="w-full h-9 sm:h-10 pl-8 sm:pl-9 pr-3 rounded-full bg-[#F8F6F7] border border-[#EFE5EA] text-xs text-[#1E1B1D] placeholder-[#888185] focus:outline-none focus:ring-1 focus:ring-[#8E1B5C] focus:bg-white transition-all">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#888185] absolute left-2.5 sm:left-3 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4.5 4.5" stroke-linecap="round"/>
                    </svg>
                </form>

                {{-- Wishlist Heart Icon (Interactive Slide-over) --}}
                <button type="button" @click="wishlistModalOpen = true" class="p-2 text-[#4A4447] hover:text-[#8E1B5C] transition-colors relative cursor-pointer" title="Koleksi Favorit">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                    </svg>
                    <span x-show="wishlist.length > 0" x-text="wishlist.length" class="absolute top-1 right-1 w-4 h-4 rounded-full bg-[#8E1B5C] text-white text-[9px] font-bold flex items-center justify-center"></span>
                </button>

                {{-- User Icon / Portal ERP --}}
                <a href="{{ route('login') }}" class="p-2 text-[#4A4447] hover:text-[#8E1B5C] transition-colors" title="Masuk Akun / Portal ERP">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    {{-- Mobile Slide-Over Sidebar Drawer with Collapsible Kategori --}}
    <div x-show="mobileSidebarOpen" class="fixed inset-0 z-50 md:hidden" x-cloak>
        {{-- Backdrop Overlay --}}
        <div x-show="mobileSidebarOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileSidebarOpen = false"
             class="fixed inset-0 bg-black/60 backdrop-blur-xs"></div>

        {{-- Slide Drawer Panel --}}
        <div x-show="mobileSidebarOpen"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="relative mr-16 flex w-full max-w-xs flex-1 flex-col bg-white h-full shadow-2xl">
            
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-[#EFE5EA] bg-[#FFF9FB]">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/nayara-logo.jpg') }}" alt="NAYARA" class="w-9 h-9 rounded-xl object-contain border border-[#E1CFD9]">
                    <div>
                        <span class="text-lg font-black tracking-tight text-[#1E1B1D] font-serif block leading-none">NAYARA</span>
                        <span class="text-[9px] font-semibold text-[#8E1B5C] block">Pusat Hijab Berkualitas</span>
                    </div>
                </div>
                <button type="button" @click="mobileSidebarOpen = false" class="p-2 text-[#665D62] hover:text-[#520C31] rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Nav links & Collapsible Category Section --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <nav class="space-y-1">
                    <a href="{{ route('landing') }}" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('landing') && !request('category') ? 'bg-[#FCEAEF] text-[#8E1B5C]' : 'text-[#1E1B1D] hover:bg-[#FAF4F7]' }}">
                        <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Beranda</span>
                    </a>
                </nav>

                {{-- COLLAPSIBLE KATEGORI ACCORDION --}}
                <div class="rounded-2xl border border-[#EFE5EA] bg-[#FFFDFC] overflow-hidden">
                    <button type="button"
                            @click="categoryMobileOpen = !categoryMobileOpen"
                            class="w-full flex items-center justify-between p-3.5 text-xs font-bold uppercase tracking-wider text-[#520C31] hover:bg-[#FCEAEF]/40 transition-colors select-none">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                            <span>Kategori Hijab</span>
                        </div>
                        <svg class="w-4 h-4 text-[#8E1B5C] transition-transform duration-200"
                             :class="categoryMobileOpen ? 'rotate-180' : 'rotate-0'"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="categoryMobileOpen"
                         x-transition:enter="transition-all ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition-all ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="px-2 pb-2 space-y-1 border-t border-[#F2E6ED] pt-1.5">
                        @php
                            $mobileCategories = [
                                ['name' => 'Hijab Polos', 'filter' => 'Hijab Polos', 'sub' => 'Voal & Paris'],
                                ['name' => 'Hijab Motif', 'filter' => 'Hijab Motif', 'sub' => 'Floral Print'],
                                ['name' => 'Hijab Viscose', 'filter' => 'Hijab Viscose', 'sub' => 'Linen & Silk'],
                                ['name' => 'Hijab Segi Empat', 'filter' => 'Hijab Segi Empat', 'sub' => 'Square Basic'],
                                ['name' => 'Pashmina', 'filter' => 'Pashmina', 'sub' => 'Silk & Shawl'],
                                ['name' => 'Hijab Instan', 'filter' => 'Hijab Instan', 'sub' => 'Bergo Daily'],
                            ];
                        @endphp
                        @foreach($mobileCategories as $mCat)
                            <a href="{{ route('landing', ['category' => $mCat['filter']]) }}"
                               @click="mobileSidebarOpen = false"
                               class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium {{ request('category') === $mCat['filter'] ? 'bg-[#FCEAEF] text-[#8E1B5C] font-bold' : 'text-[#4A4447] hover:bg-[#FAF4F7] hover:text-[#8E1B5C]' }} transition-colors">
                                <span>{{ $mCat['name'] }}</span>
                                <span class="text-[10px] text-[#888185]">{{ $mCat['sub'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Other links --}}
                <nav class="space-y-1 pt-2">
                    <a href="{{ route('landing') }}#produk-unggulan" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-[#1E1B1D] hover:bg-[#FAF4F7]">
                        <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span>Produk Terbaru</span>
                    </a>
                    <a href="{{ route('landing') }}#promo-iklan" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-[#1E1B1D] hover:bg-[#FAF4F7]">
                        <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span>Promo &amp; Iklan</span>
                    </a>
                    <a href="{{ route('storefront.shops') }}" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-[#1E1B1D] hover:bg-[#FAF4F7]">
                        <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Direktori Pabrik</span>
                    </a>
                    <a href="{{ route('storefront.about') }}" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-[#1E1B1D] hover:bg-[#FAF4F7]">
                        <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Tentang Kami</span>
                    </a>
                    <a href="{{ route('storefront.help') }}" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-[#1E1B1D] hover:bg-[#FAF4F7]">
                        <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Pusat Bantuan</span>
                    </a>
                </nav>
            </div>

            {{-- Footer Action --}}
            <div class="p-4 border-t border-[#EFE5EA] bg-[#FFF9FB]">
                <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl bg-[#520C31] text-white text-xs font-bold hover:bg-[#701D49] transition-colors shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    <span>Masuk Akun / Portal ERP</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Wishlist Slide-Over Drawer --}}
    <div x-show="wishlistModalOpen" class="fixed inset-0 z-50" x-cloak>
        <div x-show="wishlistModalOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="wishlistModalOpen = false"
             class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

        <div x-show="wishlistModalOpen"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed inset-y-0 right-0 max-w-sm w-full bg-white shadow-2xl flex flex-col z-10">
            
            <div class="p-5 border-b border-[#EFE5EA] flex items-center justify-between bg-[#FFF9FB]">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#8E1B5C]" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                    <h3 class="text-sm font-bold text-[#1E1B1D]">Koleksi Favorit Saya</h3>
                    <span class="px-2 py-0.5 rounded-full bg-[#8E1B5C] text-white text-[10px] font-bold" x-text="wishlist.length"></span>
                </div>
                <button type="button" @click="wishlistModalOpen = false" class="p-1.5 rounded-lg text-[#64748B] hover:text-[#1E1B1D] hover:bg-slate-100 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <template x-if="wishlist.length === 0">
                    <div class="text-center py-16 space-y-3">
                        <div class="w-16 h-16 mx-auto rounded-full bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center">
                            <svg class="w-8 h-8 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                        </div>
                        <h4 class="text-sm font-bold text-[#1E1B1D]">Belum Ada Produk Favorit</h4>
                        <p class="text-xs text-[#64748B] max-w-xs mx-auto">
                            Simpan hijab pilihan Anda dengan mengklik tombol ikon hati pada kartu produk.
                        </p>
                        <a href="{{ route('landing') }}#produk-unggulan" @click="wishlistModalOpen = false" class="inline-block px-5 py-2.5 rounded-xl bg-[#8E1B5C] text-white text-xs font-bold hover:bg-[#701D49]">
                            Jelajahi Katalog
                        </a>
                    </div>
                </template>

                <template x-for="item in wishlist" :key="item.id">
                    <div class="p-3 bg-white rounded-2xl border border-[#EFE5EA] flex items-center justify-between gap-3 shadow-2xs hover:border-[#8E1B5C]/40 transition-all">
                        <div class="flex items-center gap-3 min-w-0">
                            <template x-if="item.image">
                                <img :src="item.image" :alt="item.name" class="w-14 h-14 rounded-xl object-cover shrink-0 border border-[#EFE5EA]">
                            </template>
                            <template x-if="!item.image">
                                <div class="w-14 h-14 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center font-bold text-xs shrink-0">HJB</div>
                            </template>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-[#1E1B1D] truncate" x-text="item.name"></h4>
                                <p class="text-[11px] font-extrabold text-[#8E1B5C] mt-0.5" x-text="item.price || 'Hubungi Toko'"></p>
                                <a :href="'{{ url('storefront/product') }}/' + item.id" class="text-[10px] text-[#8E1B5C] font-semibold hover:underline mt-1 inline-block">
                                    Lihat Detail &rarr;
                                </a>
                            </div>
                        </div>
                        <button type="button" @click="removeFromWishlist(item.id)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors" title="Hapus dari Favorit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Quick Floating Notification Toast --}}
    <div x-show="showToast"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         x-cloak
         class="fixed bottom-6 right-6 z-50 bg-[#1E1B1D] text-white px-5 py-3 rounded-2xl shadow-xl flex items-center gap-2.5 text-xs font-semibold border border-white/10">
        <span class="w-2 h-2 rounded-full bg-[#8E1B5C] animate-pulse"></span>
        <span x-text="toastMessage"></span>
    </div>

    {{-- 3. Dynamic Page Content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- 4. Value Propositions Bar (Luxury Trust & Service Showcase with Clickable Guides) --}}
    <section class="border-y border-[#EFE5EA] bg-gradient-to-b from-[#FDF8FA] via-white to-[#FDF8FA] py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                
                {{-- Item 1: Pengiriman Cepat --}}
                <a href="{{ route('storefront.help') }}" class="group p-4 sm:p-5 rounded-2xl bg-white border border-[#EFE5EA] hover:border-[#8E1B5C]/35 shadow-xs hover:shadow-md transition-all duration-300 hover:-translate-y-1 flex items-center gap-4 block">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#FCEAEF] to-[#F5D5E2] text-[#8E1B5C] flex items-center justify-center shrink-0 shadow-xs border border-[#EED7E1] group-hover:scale-105 group-hover:bg-[#8E1B5C] group-hover:text-white transition-all duration-300">
                        <svg class="w-6 h-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25h1.875c.621 0 1.125.504 1.125 1.125v4.5H14.25V7.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Pengiriman Cepat</h4>
                        <p class="text-xs text-[#7A7176] mt-0.5">Ke Seluruh Indonesia &rarr;</p>
                    </div>
                </a>

                {{-- Item 2: Transaksi Aman --}}
                <a href="{{ route('storefront.terms') }}" class="group p-4 sm:p-5 rounded-2xl bg-white border border-[#EFE5EA] hover:border-[#8E1B5C]/35 shadow-xs hover:shadow-md transition-all duration-300 hover:-translate-y-1 flex items-center gap-4 block">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#FCEAEF] to-[#F5D5E2] text-[#8E1B5C] flex items-center justify-center shrink-0 shadow-xs border border-[#EED7E1] group-hover:scale-105 group-hover:bg-[#8E1B5C] group-hover:text-white transition-all duration-300">
                        <svg class="w-6 h-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Transaksi Aman</h4>
                        <p class="text-xs text-[#7A7176] mt-0.5">Terjamin &amp; Terpercaya &rarr;</p>
                    </div>
                </a>

                {{-- Item 3: Mudah Dikembalikan --}}
                <a href="{{ route('storefront.returns') }}" class="group p-4 sm:p-5 rounded-2xl bg-white border border-[#EFE5EA] hover:border-[#8E1B5C]/35 shadow-xs hover:shadow-md transition-all duration-300 hover:-translate-y-1 flex items-center gap-4 block">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#FCEAEF] to-[#F5D5E2] text-[#8E1B5C] flex items-center justify-center shrink-0 shadow-xs border border-[#EED7E1] group-hover:scale-105 group-hover:bg-[#8E1B5C] group-hover:text-white transition-all duration-300">
                        <svg class="w-6 h-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Mudah Dikembalikan</h4>
                        <p class="text-xs text-[#7A7176] mt-0.5">Sesuai Garansi 7 Hari &rarr;</p>
                    </div>
                </a>

                {{-- Item 4: Layanan Pelanggan --}}
                <a href="{{ route('storefront.contact') }}" class="group p-4 sm:p-5 rounded-2xl bg-white border border-[#EFE5EA] hover:border-[#8E1B5C]/35 shadow-xs hover:shadow-md transition-all duration-300 hover:-translate-y-1 flex items-center gap-4 block">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#FCEAEF] to-[#F5D5E2] text-[#8E1B5C] flex items-center justify-center shrink-0 shadow-xs border border-[#EED7E1] group-hover:scale-105 group-hover:bg-[#8E1B5C] group-hover:text-white transition-all duration-300">
                        <svg class="w-6 h-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Layanan Pelanggan</h4>
                        <p class="text-xs text-[#7A7176] mt-0.5">Siap Membantu Anda &rarr;</p>
                    </div>
                </a>

            </div>
        </div>
    </section>

    {{-- 5. Comprehensive Footer (Matching User Screenshot with Burgundy Background) --}}
    <footer class="bg-[#520C31] text-white pt-12 pb-8 border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-8 pb-10 border-b border-white/15">
                
                {{-- Col 1 & 2: Brand Identity with NAYARA Logo --}}
                <div class="space-y-4 md:col-span-2">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/nayara-logo.jpg') }}" alt="NAYARA" class="w-10 h-10 rounded-xl object-contain shadow-xs border border-white/20">
                        <div class="space-y-0.5">
                            <span class="text-xl font-bold tracking-tight text-white font-serif block">NAYARA</span>
                            <span class="text-[9.5px] font-semibold text-[#FFD8D8] tracking-wider uppercase block">Platform SaaS Konveksi Hijab</span>
                        </div>
                    </div>
                    <p class="text-xs text-white/70 leading-relaxed max-w-sm">
                        Pusat hijab berkualitas dengan beragam pilihan gaya dan motif. Menghubungkan pembeli grosir dan retail langsung ke tangan pertama konveksi mandiri Indonesia.
                    </p>
                    <p class="text-[11px] text-white/50">
                        &copy; {{ now()->year }} NAYARA. Semua Hak Dilindungi.
                    </p>
                </div>

                {{-- Col 3: Jelajahi --}}
                <div class="space-y-2.5">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white">Jelajahi</h4>
                    <ul class="space-y-2 text-xs text-white/75">
                        <li><a href="{{ route('landing') }}" class="hover:text-white transition-colors">Beranda</a></li>
                        <li><a href="{{ route('landing') }}#kategori" class="hover:text-white transition-colors">Koleksi Hijab</a></li>
                        <li><a href="{{ route('landing') }}#produk-unggulan" class="hover:text-white transition-colors">Produk Terbaru</a></li>
                        <li><a href="{{ route('landing') }}#promo-iklan" class="hover:text-white transition-colors">Promo &amp; Iklan</a></li>
                    </ul>
                </div>

                {{-- Col 4: Bantuan --}}
                <div class="space-y-2.5">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white">Bantuan</h4>
                    <ul class="space-y-2 text-xs text-white/75">
                        <li><a href="{{ route('storefront.help') }}" class="hover:text-white transition-colors">Pusat Bantuan</a></li>
                        <li><a href="{{ route('storefront.how-to-order') }}" class="hover:text-white transition-colors">Cara Belanja</a></li>
                        <li><a href="{{ route('storefront.returns') }}" class="hover:text-white transition-colors">Pengembalian Produk</a></li>
                        <li><a href="{{ route('storefront.terms') }}" class="hover:text-white transition-colors">Syarat &amp; Ketentuan</a></li>
                    </ul>
                </div>

                {{-- Col 5: Tentang NAYARA --}}
                <div class="space-y-2.5">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white">Tentang NAYARA</h4>
                    <ul class="space-y-2 text-xs text-white/75">
                        <li><a href="{{ route('storefront.about') }}" class="hover:text-white transition-colors">Tentang Kami</a></li>
                        <li><a href="{{ route('storefront.privacy') }}" class="hover:text-white transition-colors">Kebijakan Privasi</a></li>
                        <li><a href="{{ route('storefront.usage-policy') }}" class="hover:text-white transition-colors">Kebijakan Penggunaan</a></li>
                        <li><a href="{{ route('storefront.contact') }}" class="hover:text-white transition-colors">Hubungi Kami</a></li>
                    </ul>
                </div>

                {{-- Col 6: Ikuti Kami & Pembayaran (Clean Vector SVGs) --}}
                <div class="space-y-4">
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-white">Ikuti Kami</h4>
                        <div class="flex items-center gap-2.5 text-white">
                            {{-- Instagram --}}
                            <a href="https://instagram.com" target="_blank" rel="noopener" class="w-8 h-8 rounded-full bg-white/10 hover:bg-[#8E1B5C] flex items-center justify-center transition-all shadow-xs" title="Instagram">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            {{-- TikTok --}}
                            <a href="https://tiktok.com" target="_blank" rel="noopener" class="w-8 h-8 rounded-full bg-white/10 hover:bg-[#8E1B5C] flex items-center justify-center transition-all shadow-xs" title="TikTok">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.97-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 2.89 3.5 2.76 1.35-.04 2.56-.88 3.05-2.12.22-.55.28-1.15.28-1.74V.02h-.03z"/></svg>
                            </a>
                            {{-- YouTube --}}
                            <a href="https://youtube.com" target="_blank" rel="noopener" class="w-8 h-8 rounded-full bg-white/10 hover:bg-[#8E1B5C] flex items-center justify-center transition-all shadow-xs" title="YouTube">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                            {{-- Facebook --}}
                            <a href="https://facebook.com" target="_blank" rel="noopener" class="w-8 h-8 rounded-full bg-white/10 hover:bg-[#8E1B5C] flex items-center justify-center transition-all shadow-xs" title="Facebook">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-white">Metode Pembayaran</h4>
                        <div class="flex flex-wrap items-center gap-2">
                            {{-- VISA --}}
                            <div class="h-8 px-2.5 bg-white rounded-md flex items-center justify-center shadow-xs border border-white/20 hover:scale-105 transition-transform" title="VISA">
                                <svg class="h-3.5 w-auto" viewBox="0 0 64 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M24.5 1.5L16.2 18.5H11.2L6.8 5.4C6.5 4.3 6.3 3.9 5.4 3.4C3.9 2.6 1.8 1.9 0 1.5L0.2 0.7H8.8C10 0.7 11 1.5 11.3 2.8L13.5 13.2L18.8 0.7H24.5ZM45.8 12.6C45.8 7.8 39.1 7.5 39.2 5.4C39.2 4.7 39.9 4 41.3 3.8C42 3.7 43.8 3.6 45.9 4.6L46.7 0.9C45.6 0.5 44.1 0.1 42.2 0.1C37.2 0.1 33.6 2.8 33.6 6.6C33.6 9.4 36.1 11 38 12C39.9 12.9 40.6 13.6 40.6 14.5C40.6 15.8 39 16.5 37.6 16.5C35.1 16.5 33.6 15.8 32.4 15.3L31.5 19.1C32.8 19.7 35.1 20.1 37.4 20.1C42.8 20.1 45.8 17.4 45.8 12.6ZM59.3 18.5H64L59.9 0.7H55.6C54.6 0.7 53.8 1.3 53.4 2.2L45.6 18.5H51.2L52.3 15.5H58.6L59.3 18.5ZM53.8 11.8L56.5 4.3L58.1 11.8H53.8ZM33.8 0.7L29.3 18.5H24L28.5 0.7H33.8Z" fill="#1A1F71"/>
                                    <path d="M5.4 3.4C6.3 3.9 6.5 4.3 6.8 5.4L11.2 18.5H6.2L0.2 0.7L0 1.5C1.8 1.9 3.9 2.6 5.4 3.4Z" fill="#F7B600"/>
                                </svg>
                            </div>

                            {{-- Mastercard --}}
                            <div class="h-8 px-2.5 bg-white rounded-md flex items-center justify-center shadow-xs border border-white/20 hover:scale-105 transition-transform" title="Mastercard">
                                <svg class="h-4 w-auto" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="10" cy="10" r="9" fill="#EB001B"/>
                                    <circle cx="22" cy="10" r="9" fill="#F79E1B"/>
                                    <path d="M16 3.65A8.98 8.98 0 0 0 12.5 10 8.98 8.98 0 0 0 16 16.35 8.98 8.98 0 0 0 19.5 10 8.98 8.98 0 0 0 16 3.65Z" fill="#FF5F00"/>
                                </svg>
                            </div>

                            {{-- BCA --}}
                            <div class="h-8 px-2.5 bg-white rounded-md flex items-center justify-center gap-1 shadow-xs border border-white/20 hover:scale-105 transition-transform" title="Bank Central Asia (BCA)">
                                <div class="w-3.5 h-3.5 rounded-full bg-[#005EAD] flex items-center justify-center text-white text-[7.5px] font-black">B</div>
                                <span class="text-[11.5px] font-black tracking-tight text-[#005EAD] font-sans">BCA</span>
                            </div>

                            {{-- BNI --}}
                            <div class="h-8 px-2.5 bg-white rounded-md flex items-center justify-center gap-1 shadow-xs border border-white/20 hover:scale-105 transition-transform" title="Bank Negara Indonesia (BNI)">
                                <span class="text-[11.5px] font-black tracking-tight text-[#005B60] font-sans">BNI</span>
                                <span class="text-[9.5px] font-bold text-[#F36F21]">46</span>
                            </div>

                            {{-- DANA --}}
                            <div class="h-8 px-2.5 bg-white rounded-md flex items-center justify-center gap-1 shadow-xs border border-white/20 hover:scale-105 transition-transform" title="DANA Digital Wallet">
                                <div class="w-3.5 h-3.5 rounded bg-[#108EE9] flex items-center justify-center text-white text-[7.5px] font-black">D</div>
                                <span class="text-[11px] font-black tracking-wide text-[#108EE9] font-sans">DANA</span>
                            </div>

                            {{-- QRIS --}}
                            <div class="h-8 px-2.5 bg-white rounded-md flex items-center justify-center gap-0.5 shadow-xs border border-white/20 hover:scale-105 transition-transform" title="QRIS Standar Pembayaran Nasional">
                                <span class="text-[11px] font-black tracking-tight text-[#ED1C24] font-sans">QR</span><span class="text-[11px] font-black tracking-tight text-[#1E1B1D] font-sans">IS</span>
                            </div>

                            {{-- Bank Mandiri --}}
                            <div class="h-8 px-2.5 bg-white rounded-md flex items-center justify-center gap-1 shadow-xs border border-white/20 hover:scale-105 transition-transform" title="Bank Mandiri">
                                <span class="text-[11px] font-black tracking-tight text-[#003D79] font-sans">mandiri</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-[#F4A21D] inline-block -mt-1"></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- 6. ERP Staff Accessibility & Ecosystem Portal Bar --}}
            <div class="mt-8 pt-6 border-t border-white/10 flex flex-col lg:flex-row items-center justify-between gap-4 text-xs">
                {{-- Left Side: System Info with Clean Status Tag --}}
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2.5 text-white/70">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-white text-[11px] font-semibold shadow-xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span>NAYARA</span>
                        <span class="text-white/40">&bull;</span>
                        <span class="text-[#FFD8D8]">Production &amp; MRP</span>
                    </div>
                    <span class="text-white/85 font-medium">Sistem Produksi &amp; Perencanaan Bahan:</span>
                    <span class="text-white/60">Pesanan, kebutuhan bahan, dan proses produksi dalam satu sistem.</span>
                </div>

                {{-- Right Side: Polished Frosted Pill Portal Links --}}
                <div class="flex flex-wrap items-center justify-center gap-2 shrink-0 font-medium text-xs">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white/10 hover:bg-white/20 border border-white/15 text-white transition-all hover:scale-105 shadow-xs">
                        <svg class="w-3.5 h-3.5 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                        </svg>
                        <span>Masuk Sistem</span>
                    </a>
                    <a href="{{ route('track.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white/10 hover:bg-white/20 border border-white/15 text-white transition-all hover:scale-105 shadow-xs">
                        <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                        <span>Lacak Pesanan</span>
                    </a>
                    <a href="{{ route('tenant.register.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-[#8E1B5C] to-[#A12768] hover:from-[#A12768] hover:to-[#B63378] text-white font-semibold transition-all hover:scale-105 shadow-xs border border-white/20">
                        <span>Daftar Konveksi</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
