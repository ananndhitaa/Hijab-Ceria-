@extends('layouts.storefront', [
    'title' => 'NAYARA — Pusat Hijab Berkualitas & Platform Promosi Konveksi Mandiri',
    'description' => 'Temukan beragam pilihan hijab berkualitas dari pabrik konveksi terpercaya di ekosistem NAYARA.'
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-3 pb-8 sm:pt-4 sm:pb-12 space-y-10 sm:space-y-14">

    {{-- =========================================================================
         1. HERO SECTION (INTERACTIVE SLIDER / CAROUSEL WITH ARCH "SETENGAH LINGKARAN")
         ========================================================================= --}}
    <section x-data="{
        currentSlide: 0,
        totalSlides: 3,
        timer: null,
        init() {
            this.startAutoplay();
        },
        startAutoplay() {
            this.stopAutoplay();
            this.timer = setInterval(() => {
                this.next(false);
            }, 4000);
        },
        stopAutoplay() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        next(userInteracted = true) {
            this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
            if (userInteracted) this.startAutoplay();
        },
        prev() {
            this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
            this.startAutoplay();
        },
        goTo(idx) {
            this.currentSlide = idx;
            this.startAutoplay();
        }
    }"
    class="relative bg-gradient-to-r from-[#FAF2F5] via-[#FCEAEF] to-[#F8F0F5] rounded-3xl lg:rounded-[36px] border border-[#EFE5EA] overflow-hidden shadow-[0_2px_16px_rgba(142,27,92,0.04)]">
        
        {{-- Floating Navigation Arrows (Active & Clickable) --}}
        <button type="button"
                @click="prev()"
                class="hidden sm:flex absolute left-4 top-1/2 -translate-y-1/2 z-30 w-11 h-11 rounded-full bg-white/95 hover:bg-white text-[#520C31] shadow-lg border border-[#EFE5EA] items-center justify-center transition-all hover:scale-110 active:scale-95 cursor-pointer"
                aria-label="Slide Sebelumnya">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        </button>
        <button type="button"
                @click="next()"
                class="hidden sm:flex absolute right-4 top-1/2 -translate-y-1/2 z-30 w-11 h-11 rounded-full bg-white/95 hover:bg-white text-[#520C31] shadow-lg border border-[#EFE5EA] items-center justify-center transition-all hover:scale-110 active:scale-95 cursor-pointer"
                aria-label="Slide Berikutnya">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </button>

        <div class="max-w-7xl mx-auto px-6 sm:px-10 lg:px-14 py-6 sm:py-8 lg:py-10">
            
            {{-- CSS Grid Stacked Slides Container: Prevents Vertical Jumping & Guarantees Silky Smooth Fade/Slide --}}
            <div class="relative min-h-[430px] sm:min-h-[460px] lg:min-h-[480px] grid grid-cols-1 items-center">

                {{-- SLIDE 1: Modest Hijab, Elegantly Yours --}}
                <div x-show="currentSlide === 0"
                     x-transition:enter="transition-all ease-out duration-700"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition-all ease-in duration-500"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 -translate-x-4"
                     class="col-start-1 row-start-1 w-full grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                    
                    {{-- Left Text Area --}}
                    <div class="lg:col-span-7 space-y-5 sm:space-y-6 text-left relative z-10">
                        <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/95 border border-[#8E1B5C]/25 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-[#8E1B5C]"></span>
                            <span class="text-[11px] sm:text-xs font-bold text-[#8E1B5C] tracking-wide uppercase">NAYARA Signature Collection</span>
                            <span class="text-[#D4B5C3] font-light">&bull;</span>
                            <span class="text-[11px] sm:text-xs font-medium text-[#554C51]">Kurasi Kualitas Terbaik</span>
                        </div>

                        <h1 class="text-3xl sm:text-5xl lg:text-[48px] font-bold text-[#1E1B1D] font-serif tracking-tight leading-[1.15]">
                            Modest Hijab, <br>
                            <span class="text-[#8E1B5C] italic">Elegantly Yours</span>
                        </h1>

                        <p class="text-xs sm:text-sm lg:text-base text-[#554C51] leading-relaxed max-w-lg">
                            Hadirkan pesona anggun melalui kurasi hijab pilihan berbahan premium. Didesain lembut, jatuh sempurna, dan memberikan kenyamanan istimewa untuk setiap momen berharga Anda.
                        </p>

                        <div class="flex flex-wrap items-center gap-3.5 pt-1">
                            <a href="#produk-unggulan" class="inline-flex items-center gap-2.5 px-6 sm:px-7 py-3 sm:py-3.5 rounded-full bg-[#520C31] hover:bg-[#701D49] text-white text-xs sm:text-sm font-semibold transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25c-.67 0-1.19-.578-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
                                </svg>
                                <span>Pilih Produk Brand</span>
                            </a>
                            <a href="#kategori" class="inline-flex items-center justify-center px-6 sm:px-7 py-3 sm:py-3.5 rounded-full border-2 border-[#520C31] bg-white/80 hover:bg-white text-[#520C31] text-xs sm:text-sm font-semibold transition-all shadow-xs hover:shadow-md hover:-translate-y-0.5">
                                Lihat Koleksi
                            </a>
                        </div>
                    </div>

                    {{-- Right Hero Area: Proportional Arch Dome --}}
                    <div class="lg:col-span-5 flex justify-center lg:justify-end items-end relative">
                        <div class="relative w-[260px] sm:w-[300px] lg:w-[340px] flex items-end justify-center -mb-8 sm:-mb-12 lg:-mb-14">
                            <div class="absolute inset-x-0 bottom-0 mx-auto w-full h-[350px] sm:h-[420px] lg:h-[470px] rounded-t-full bg-gradient-to-b from-[#D4B5C3] via-[#DECAD6] to-[#EBDDE4] border-t-[5px] border-x-2 border-white/90 shadow-2xl"></div>
                            <div class="relative z-10 w-[92%] h-[330px] sm:h-[400px] lg:h-[450px] rounded-t-full overflow-hidden border-4 border-white shadow-inner bg-gradient-to-b from-[#F2DFE7] to-[#FCEAEF] flex items-end justify-center">
                                <img src="{{ $heroSlides['slide1']['image_url'] ?? asset('images/storefront/hero-model.jpg') }}" alt="{{ $heroSlides['slide1']['alt'] ?? 'Model Hijab NAYARA - Modest Fashion' }}" class="w-full h-full object-cover object-[center_12%] hover:scale-105 transition-transform duration-700 select-none">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SLIDE 2: Koleksi Segi Empat & Motif --}}
                <div x-show="currentSlide === 1"
                     x-transition:enter="transition-all ease-out duration-700"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition-all ease-in duration-500"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 -translate-x-4"
                     x-cloak
                     class="col-start-1 row-start-1 w-full grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                    
                    {{-- Left Text Area --}}
                    <div class="lg:col-span-7 space-y-5 sm:space-y-6 text-left relative z-10">
                        <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/95 border border-[#8E1B5C]/25 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-[#8E1B5C]"></span>
                            <span class="text-[11px] sm:text-xs font-bold text-[#8E1B5C] tracking-wide uppercase">Motif Eksklusif &amp; Segi Empat</span>
                            <span class="text-[#D4B5C3] font-light">&bull;</span>
                            <span class="text-[11px] sm:text-xs font-medium text-[#554C51]">Finishing Kelim Presisi</span>
                        </div>

                        <h1 class="text-3xl sm:text-5xl lg:text-[48px] font-bold text-[#1E1B1D] font-serif tracking-tight leading-[1.15]">
                            Pesona Corak, <br>
                            <span class="text-[#8E1B5C] italic">Keanggunan Abadi</span>
                        </h1>

                        <p class="text-xs sm:text-sm lg:text-base text-[#554C51] leading-relaxed max-w-lg">
                            Eksplorasi motif floral modern dan seri segi empat Malaysia dengan detail jahitan kelim halus presisi. Perpaduan sempurna antara estetika anggun dan ketahanan warna berkualitas tinggi.
                        </p>

                        <div class="flex flex-wrap items-center gap-3.5 pt-1">
                            <a href="{{ route('landing', ['category' => 'Hijab Motif']) }}" class="inline-flex items-center gap-2.5 px-6 sm:px-7 py-3 sm:py-3.5 rounded-full bg-[#520C31] hover:bg-[#701D49] text-white text-xs sm:text-sm font-semibold transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5">
                                <span>Lihat Koleksi Motif &rarr;</span>
                            </a>
                            <a href="{{ route('landing', ['category' => 'Hijab Segi Empat']) }}" class="inline-flex items-center justify-center px-6 sm:px-7 py-3 sm:py-3.5 rounded-full border-2 border-[#520C31] bg-white/80 hover:bg-white text-[#520C31] text-xs sm:text-sm font-semibold transition-all shadow-xs hover:shadow-md hover:-translate-y-0.5">
                                Hijab Segi Empat
                            </a>
                        </div>
                    </div>

                    {{-- Right Hero Area: Proportional Arch Dome --}}
                    <div class="lg:col-span-5 flex justify-center lg:justify-end items-end relative">
                        <div class="relative w-[260px] sm:w-[300px] lg:w-[340px] flex items-end justify-center -mb-8 sm:-mb-12 lg:-mb-14">
                            <div class="absolute inset-x-0 bottom-0 mx-auto w-full h-[350px] sm:h-[420px] lg:h-[470px] rounded-t-full bg-gradient-to-b from-[#E2CAD5] via-[#E8D4DD] to-[#F5E8EE] border-t-[5px] border-x-2 border-white/90 shadow-2xl"></div>
                            <div class="relative z-10 w-[92%] h-[330px] sm:h-[400px] lg:h-[450px] rounded-t-full overflow-hidden border-4 border-white shadow-inner bg-gradient-to-b from-[#F4E2EA] to-[#FCEAEF] flex items-end justify-center">
                                <img src="{{ $heroSlides['slide2']['image_url'] ?? asset('storage/products/hijab-segiempat-malaysia.jpg') }}" alt="{{ $heroSlides['slide2']['alt'] ?? 'Koleksi Hijab Segi Empat & Motif NAYARA' }}" class="w-full h-full object-cover object-[center_12%] hover:scale-105 transition-transform duration-700 select-none">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SLIDE 3: Pashmina Silk & Viscose Harian --}}
                <div x-show="currentSlide === 2"
                     x-transition:enter="transition-all ease-out duration-700"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition-all ease-in duration-500"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 -translate-x-4"
                     x-cloak
                     class="col-start-1 row-start-1 w-full grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                    
                    {{-- Left Text Area --}}
                    <div class="lg:col-span-7 space-y-5 sm:space-y-6 text-left relative z-10">
                        <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/95 border border-[#8E1B5C]/25 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-[#8E1B5C]"></span>
                            <span class="text-[11px] sm:text-xs font-bold text-[#8E1B5C] tracking-wide uppercase">Premium Viscose &amp; Pashmina</span>
                            <span class="text-[#D4B5C3] font-light">&bull;</span>
                            <span class="text-[11px] sm:text-xs font-medium text-[#554C51]">Tekstur Lembut &amp; Ringan</span>
                        </div>

                        <h1 class="text-3xl sm:text-5xl lg:text-[48px] font-bold text-[#1E1B1D] font-serif tracking-tight leading-[1.15]">
                            Sentuhan Mewah, <br>
                            <span class="text-[#8E1B5C] italic">Nyaman Sepanjang Hari</span>
                        </h1>

                        <p class="text-xs sm:text-sm lg:text-base text-[#554C51] leading-relaxed max-w-lg">
                            Serat viscose linen alami pilihan yang breathable, adem, dan fleksibel mengikuti bentuk wajah. Sempurna untuk menunjang produktivitas profesional maupun kebersamaan santai.
                        </p>

                        <div class="flex flex-wrap items-center gap-3.5 pt-1">
                            <a href="{{ route('landing', ['category' => 'Hijab Viscose']) }}" class="inline-flex items-center gap-2.5 px-6 sm:px-7 py-3 sm:py-3.5 rounded-full bg-[#520C31] hover:bg-[#701D49] text-white text-xs sm:text-sm font-semibold transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5">
                                <span>Koleksi Viscose &rarr;</span>
                            </a>
                            <a href="{{ route('landing', ['category' => 'Pashmina']) }}" class="inline-flex items-center justify-center px-6 sm:px-7 py-3 sm:py-3.5 rounded-full border-2 border-[#520C31] bg-white/80 hover:bg-white text-[#520C31] text-xs sm:text-sm font-semibold transition-all shadow-xs hover:shadow-md hover:-translate-y-0.5">
                                Koleksi Pashmina
                            </a>
                        </div>
                    </div>

                    {{-- Right Hero Area: Proportional Arch Dome --}}
                    <div class="lg:col-span-5 flex justify-center lg:justify-end items-end relative">
                        <div class="relative w-[260px] sm:w-[300px] lg:w-[340px] flex items-end justify-center -mb-8 sm:-mb-12 lg:-mb-14">
                            <div class="absolute inset-x-0 bottom-0 mx-auto w-full h-[350px] sm:h-[420px] lg:h-[470px] rounded-t-full bg-gradient-to-b from-[#CDB4C2] via-[#DFC6D3] to-[#EFE1EA] border-t-[5px] border-x-2 border-white/90 shadow-2xl"></div>
                            <div class="relative z-10 w-[92%] h-[330px] sm:h-[400px] lg:h-[450px] rounded-t-full overflow-hidden border-4 border-white shadow-inner bg-gradient-to-b from-[#EFD7E2] to-[#FCEAEF] flex items-end justify-center">
                                <img src="{{ $heroSlides['slide3']['image_url'] ?? asset('storage/products/variants/hijab-viscose-nude.jpg') }}" alt="{{ $heroSlides['slide3']['alt'] ?? 'Koleksi Hijab Viscose Linen NAYARA' }}" class="w-full h-full object-cover object-[center_12%] hover:scale-105 transition-transform duration-700 select-none">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Slider Indicator Dots (Active & Clickable with Smooth Width Transition) --}}
            <div class="flex items-center justify-center gap-2 pt-6 relative z-20">
                <button type="button"
                        @click="goTo(0)"
                        class="h-2 rounded-full transition-all duration-500 cursor-pointer"
                        :class="currentSlide === 0 ? 'w-8 bg-[#8E1B5C]' : 'w-2.5 bg-[#D9C4CF] hover:bg-[#B399A8]'"
                        aria-label="Slide 1: Modest Hijab"></button>
                <button type="button"
                        @click="goTo(1)"
                        class="h-2 rounded-full transition-all duration-500 cursor-pointer"
                        :class="currentSlide === 1 ? 'w-8 bg-[#8E1B5C]' : 'w-2.5 bg-[#D9C4CF] hover:bg-[#B399A8]'"
                        aria-label="Slide 2: Hijab Segi Empat & Motif"></button>
                <button type="button"
                        @click="goTo(2)"
                        class="h-2 rounded-full transition-all duration-500 cursor-pointer"
                        :class="currentSlide === 2 ? 'w-8 bg-[#8E1B5C]' : 'w-2.5 bg-[#D9C4CF] hover:bg-[#B399A8]'"
                        aria-label="Slide 3: Pashmina & Viscose"></button>
            </div>
        </div>

    </section>

    {{-- =========================================================================
         2. PILIH KOLEKSI HIJAB FAVORITMU ("SHOP BY CATEGORY" - 6 CIRCULAR ITEMS)
         ========================================================================= --}}
    <section id="kategori" class="space-y-6 sm:space-y-8 pt-2">
        <div class="text-center space-y-1">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#1E1B1D] font-serif">Pilih Koleksi Hijab Favoritmu</h2>
            <p class="text-xs sm:text-sm text-[#665D62]">Kategori model hijab terlengkap dari tangan pertama konveksi mandiri</p>
        </div>

        @php
            $displayCategories = $categoryShowcases ?? collect([
                ['name' => 'Hijab Polos', 'filter' => 'Hijab Polos', 'desc' => 'Voal & Paris', 'image_url' => asset('images/storefront/cat-hijab-polos.jpg'), 'is_real_product_image' => false, 'product_name' => null],
                ['name' => 'Hijab Motif', 'filter' => 'Hijab Motif', 'desc' => 'Printing Floral', 'image_url' => asset('images/storefront/cat-hijab-motif.jpg'), 'is_real_product_image' => false, 'product_name' => null],
                ['name' => 'Hijab Viscose', 'filter' => 'Hijab Viscose', 'desc' => 'Linen & Silk Touch', 'image_url' => asset('images/storefront/cat-hijab-crinkle.jpg'), 'is_real_product_image' => false, 'product_name' => null],
                ['name' => 'Hijab Segi Empat', 'filter' => 'Hijab Segi Empat', 'desc' => 'Square Basic', 'image_url' => asset('images/storefront/cat-hijab-segiempat.jpg'), 'is_real_product_image' => false, 'product_name' => null],
                ['name' => 'Pashmina', 'filter' => 'Pashmina', 'desc' => 'Silk & Shawl', 'image_url' => asset('images/storefront/cat-hijab-pashmina.jpg'), 'is_real_product_image' => false, 'product_name' => null],
                ['name' => 'Hijab Instan', 'filter' => 'Hijab Instan', 'desc' => 'Bergo & Daily', 'image_url' => asset('images/storefront/cat-hijab-instan.jpg'), 'is_real_product_image' => false, 'product_name' => null],
            ]);
        @endphp

        <div class="grid grid-cols-3 sm:grid-cols-6 gap-4 sm:gap-6 lg:gap-8 justify-items-center">
            @foreach($displayCategories as $cat)
                @php $isActive = ($category === $cat['filter']); @endphp
                <a href="{{ route('landing', ['category' => $cat['filter']]) }}"
                   class="group flex flex-col items-center"
                   title="{{ !empty($cat['is_real_product_image']) ? 'Foto produk riil konveksi: ' . $cat['product_name'] : $cat['name'] }}">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 lg:w-28 lg:h-28 rounded-full overflow-hidden border-2 {{ $isActive ? 'border-[#8E1B5C] ring-4 ring-[#8E1B5C]/20 bg-[#FCEAEF]' : 'border-[#EFE5EA] bg-white group-hover:border-[#8E1B5C]' }} p-1 transition-all duration-300 group-hover:scale-105 shadow-xs flex items-center justify-center">
                        <div class="w-full h-full rounded-full overflow-hidden bg-[#FAF2F6] flex items-center justify-center relative">
                            @if(!empty($cat['image_url']))
                                <img src="{{ $cat['image_url'] }}" alt="{{ $cat['name'] }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            @else
                                <svg class="w-7 h-7 sm:w-8 sm:h-8 text-[#8E1B5C]/60" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                </svg>
                            @endif
                        </div>
                    </div>
                    <span class="mt-2.5 text-xs sm:text-sm font-semibold {{ $isActive ? 'text-[#8E1B5C] font-bold' : 'text-[#1E1B1D]' }} group-hover:text-[#8E1B5C] text-center transition-colors">
                        {{ $cat['name'] }}
                    </span>
                    <span class="text-[10px] text-[#888185] mt-0.5 text-center hidden sm:block">
                        {{ $cat['desc'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- =========================================================================
         3. PRODUK UNGGULAN NAYARA ("OUR MOST LOVED PIECES" - DATA AKTUAL DATABASE)
         ========================================================================= --}}
    <section id="produk-unggulan" class="space-y-6 sm:space-y-8">
        
        <div class="text-center space-y-1">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#1E1B1D] font-serif">Produk Unggulan NAYARA</h2>
            <p class="text-xs sm:text-sm text-[#665D62]">Koleksi hijab terlaris dan pilihan terbaik langsung dari mitra konveksi resmi</p>
        </div>

        {{-- A. TINGKAT 1: PRODUK IKLAN BERBAYAR AKTIF (Prioritas Bidding Tertinggi) --}}
        @if($featuredAds->isNotEmpty())
        <div id="iklan-sponsor" class="space-y-3 bg-[#FFFDF5] p-5 rounded-3xl border-2 border-amber-300 shadow-xs">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-400 text-amber-950 font-extrabold text-[10px] tracking-wider uppercase shadow-2xs">
                        IKLAN SPONSORED
                    </span>
                    <span class="text-xs sm:text-sm font-bold text-[#520C31]">Prioritas Penawaran Iklan Mitra Konveksi</span>
                </div>
                <span class="text-[10.5px] text-[#888185]">Penempatan Promosi Resmi</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5">
                @foreach($featuredAds as $ad)
                @php $adProduct = $ad->product; @endphp
                <div class="bg-white rounded-2xl border border-amber-200 p-3 sm:p-4 shadow-xs hover:shadow-md transition-all flex flex-col justify-between group relative">
                    {{-- Sponsored Badge --}}
                    <span class="absolute top-3 left-3 px-2 py-0.5 rounded bg-amber-500 text-white font-extrabold text-[9.5px] uppercase tracking-wider z-10 shadow-2xs">
                        Iklan
                    </span>

                    <div>
                        <div class="w-full aspect-[3/4] rounded-xl bg-[#FAF2F6] overflow-hidden mb-3 relative flex items-center justify-center border border-[#EFE5EA]">
                            @if($adProduct && $adProduct->featured_image)
                                <img src="{{ asset('storage/' . $adProduct->featured_image) }}" alt="{{ $adProduct->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center p-3 text-center bg-[#FCEAEF]/40">
                                    <svg class="w-8 h-8 text-[#8E1B5C]/70" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                    </svg>
                                    <span class="text-[10px] font-semibold text-[#8E1B5C] mt-1">Foto Konveksi</span>
                                </div>
                            @endif
                        </div>

                        <p class="text-[10.5px] font-bold text-[#8E1B5C] truncate flex items-center gap-1">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.25A2.25 2.25 0 010 18.75V10.5m18 10.5h3.75A2.25 2.25 0 0024 18.75V10.5M3 10.5l9-7.5 9 7.5"/></svg>
                            <span class="truncate">{{ $ad->outlet?->name }}</span>
                        </p>
                        <h3 class="text-xs sm:text-sm font-semibold text-[#1E1B1D] truncate mt-0.5" title="{{ $adProduct?->name }}">
                            {{ $adProduct?->name }}
                        </h3>

                        <div class="flex items-center text-amber-500 text-xs mt-1">
                            <div class="flex items-center gap-0.5">
                                @for($s=0;$s<5;$s++)
                                <svg class="w-2.5 h-2.5 fill-amber-400 text-amber-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                            <span class="text-[10px] text-[#665D62] ml-1">Sponsor Resmi</span>
                        </div>

                        <p class="text-xs sm:text-sm font-bold text-[#8E1B5C] mt-1 font-mono">
                            @if($adProduct && $adProduct->public_price)
                                Rp {{ number_format((float)$adProduct->public_price, 0, ',', '.') }}
                            @else
                                <span class="text-xs font-medium text-[#665D62]">Hubungi Konveksi</span>
                            @endif
                        </p>
                    </div>

                    @if($adProduct)
                    <a href="{{ route('storefront.product', $adProduct->id) }}" class="mt-3 w-full py-2 rounded-xl bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs font-semibold text-center transition-colors">
                        Detail &amp; Pesan WA
                    </a>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- B. TINGKAT 2: PRODUK ORGANIK AKTUAL DATABASE (NO DUMMY DATA) --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-[#EFE5EA] pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-5 bg-[#8E1B5C] rounded-full"></span>
                    <h3 class="text-sm sm:text-base font-bold text-[#1E1B1D]">Katalog Etalase Publik Mitra Konveksi</h3>
                </div>
                <span class="text-xs font-semibold text-[#8E1B5C] bg-[#FCEAEF] px-3 py-1 rounded-full">
                    {{ $products->total() }} Produk Terdaftar
                </span>
            </div>

            @if($products->isEmpty())
            <div class="bg-white rounded-3xl border border-[#EFE5EA] p-10 text-center space-y-3">
                <div class="w-12 h-12 mx-auto rounded-full bg-[#FCEAEF] flex items-center justify-center text-[#8E1B5C]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                    </svg>
                </div>
                <h4 class="text-base font-bold text-[#1E1B1D]">Belum Ada Produk Etalase untuk Kategori Ini</h4>
                <p class="text-xs sm:text-sm text-[#665D62] max-w-md mx-auto">
                    Mitra konveksi belum mempublikasikan produk untuk kategori ini. Pilih kategori lain atau klik Tampilkan Semua Produk.
                </p>
                <a href="{{ route('landing') }}" class="inline-block mt-2 px-5 py-2.5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#701D49] transition-colors">
                    Tampilkan Semua Hijab
                </a>
            </div>
            @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5">
                @foreach($products as $product)
                @php $summary = $product->getPriceSummary(); @endphp
                <div class="bg-white rounded-2xl sm:rounded-3xl border border-[#EFE5EA] p-3.5 sm:p-4 shadow-xs hover:shadow-lg transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        {{-- Product Image: Real photo from storage or Clean Aesthetic Placeholder --}}
                        <div class="w-full aspect-[3/4] rounded-xl sm:rounded-2xl bg-[#FAF2F6] overflow-hidden mb-3 relative flex items-center justify-center border border-[#EFE5EA]">
                            @if($product->featured_image)
                                <img src="{{ asset('storage/' . $product->featured_image) }}" alt="{{ $product->product_name ?? $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center p-4 text-center bg-gradient-to-br from-[#FAF2F6] to-[#FCEAEF]">
                                    <div class="w-12 h-12 rounded-full bg-white text-[#8E1B5C] flex items-center justify-center shadow-xs mb-2">
                                        <svg class="w-6 h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold text-[#1E1B1D] line-clamp-1">{{ $product->product_name ?? $product->name }}</span>
                                    <span class="text-[9.5px] text-[#8E1B5C] font-semibold mt-0.5">Katalog Konveksi</span>
                                </div>
                            @endif

                            {{-- Floating Tag (New Arrival / Bestseller Style) --}}
                            @if($product->public_category)
                            <span class="absolute top-2.5 left-2.5 px-2.5 py-0.5 rounded-md bg-white/95 backdrop-blur text-[9.5px] font-bold text-[#8E1B5C] shadow-2xs border border-[#EFE5EA]">
                                {{ $product->public_category }}
                            </span>
                            @endif

                            {{-- Wishlist Heart Button --}}
                            <button type="button"
                                    @click.stop="toggleWishlist({
                                        id: {{ $product->id }},
                                        name: '{{ addslashes($product->product_name ?? $product->name) }}',
                                        price: '{{ $summary['has_price'] ? $summary['formatted'] : 'Hubungi Pabrik' }}',
                                        url: '{{ route('storefront.product', $product->id) }}',
                                        image: '{{ $product->featured_image ? asset('storage/' . $product->featured_image) : '' }}',
                                        category: '{{ addslashes($product->public_category ?? '') }}'
                                    })"
                                    class="absolute bottom-2.5 right-2.5 p-1.5 rounded-full bg-white/90 shadow-xs hover:scale-110 transition-all z-10"
                                    :class="isWishlisted({{ $product->id }}) ? 'text-[#8E1B5C] ring-2 ring-[#8E1B5C]/30' : 'text-[#665D62] hover:text-[#8E1B5C]'"
                                    :title="isWishlisted({{ $product->id }}) ? 'Hapus dari Favorit' : 'Simpan ke Favorit'">
                                <svg class="w-4 h-4 transition-colors"
                                     :class="isWishlisted({{ $product->id }}) ? 'fill-[#8E1B5C] text-[#8E1B5C]' : 'fill-none'"
                                     stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Outlet / Konveksi Attribution --}}
                        <p class="text-[11px] font-bold text-[#8E1B5C] truncate flex items-center gap-1">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.25A2.25 2.25 0 010 18.75V10.5m18 10.5h3.75A2.25 2.25 0 0024 18.75V10.5M3 10.5l9-7.5 9 7.5"/></svg>
                            <span class="truncate">{{ $product->outlet?->name ?? 'Konveksi Hijab Mandiri' }}</span>
                        </p>

                        {{-- Actual Product Name from Database --}}
                        <h4 class="text-xs sm:text-sm font-semibold text-[#1E1B1D] mt-1 line-clamp-2 leading-snug group-hover:text-[#8E1B5C] transition-colors" title="{{ $product->product_name ?? $product->name }}">
                            {{ $product->product_name ?? $product->name }}
                        </h4>

                        {{-- Rating Stars --}}
                        <div class="flex items-center text-amber-400 text-xs mt-1">
                            <div class="flex items-center gap-0.5">
                                @for($s=0;$s<5;$s++)
                                <svg class="w-2.5 h-2.5 fill-amber-400 text-amber-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                            <span class="text-[10px] text-[#888185] ml-1">(5.0)</span>
                        </div>

                        @if($product->variants->count() > 1)
                        <div class="mt-1">
                            <span class="text-[9.5px] font-semibold text-[#8E1B5C] bg-[#FCEAEF] border border-[#FFD8D8] px-2 py-0.5 rounded-md inline-block">
                                {{ $product->variants->count() }} Pilihan Varian
                            </span>
                        </div>
                        @endif

                        {{-- Actual Price from Database (Dynamic Single Source of Truth) --}}
                        @php $summary = $product->getPriceSummary(); @endphp
                        <div class="mt-2 pt-2 border-t border-[#EFE5EA] flex items-center justify-between">
                            <div>
                                <span class="text-[9.5px] text-[#888185] block">{{ $summary['label'] }}:</span>
                                <span class="text-xs sm:text-sm font-extrabold text-[#1E1B1D] font-mono">
                                    @if($summary['has_price'])
                                        {{ $summary['formatted'] }}
                                    @else
                                        <span class="text-xs font-semibold text-slate-400 italic">Harga belum diatur</span>
                                    @endif
                                </span>
                            </div>
                            <span class="text-[9.5px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full font-semibold">
                                Tangan Pertama
                            </span>
                        </div>
                    </div>

                    {{-- Action Button --}}
                    <div class="mt-3 pt-2">
                        <a href="{{ route('storefront.product', $product->id) }}" class="w-full py-2 px-3 rounded-xl bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs font-semibold flex items-center justify-center gap-1.5 transition-all shadow-xs hover:shadow-md">
                            <span>Detail &amp; Pesan WA</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="pt-4">
                {{ $products->links() }}
            </div>
            @endif
        </div>

    </section>

    {{-- =========================================================================
         4. STATS BAR (DARK BURGUNDY STRIP MATCHING LUNA GRACE)
         ========================================================================= --}}
    <section class="rounded-3xl bg-[#520C31] text-white py-10 px-6 sm:px-12 shadow-lg border border-[#3D0723]">
        <div class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center divide-y md:divide-y-0 md:divide-x divide-white/15">
            <div class="pt-4 md:pt-0">
                <p class="text-2xl sm:text-4xl font-bold font-serif tracking-tight text-[#FFD8D8]">40K+</p>
                <p class="text-xs sm:text-sm text-white/80 font-medium mt-1">Pelanggan Puas</p>
            </div>
            <div class="pt-4 md:pt-0">
                <p class="text-2xl sm:text-4xl font-bold font-serif tracking-tight text-[#FFD8D8]">600+</p>
                <p class="text-xs sm:text-sm text-white/80 font-medium mt-1">Koleksi Hijab Elegan</p>
            </div>
            <div class="pt-4 md:pt-0">
                <p class="text-2xl sm:text-4xl font-bold font-serif tracking-tight text-[#FFD8D8]">35+</p>
                <p class="text-xs sm:text-sm text-white/80 font-medium mt-1">Mitra Pabrik Konveksi</p>
            </div>
            <div class="pt-4 md:pt-0">
                <p class="text-2xl sm:text-4xl font-bold font-serif tracking-tight text-[#FFD8D8]">{{ number_format($metrics['avg_rating'] ?? 4.9, 1) }}/5</p>
                <p class="text-xs sm:text-sm text-white/80 font-medium mt-1">Rating Kepuasan Konsumen</p>
            </div>
        </div>
    </section>

    {{-- =========================================================================
         5. TESTIMONIALS ("LOVED BY HIJABERS EVERYWHERE" - REAL CUSTOMER REVIEWS)
         ========================================================================= --}}
    <section class="space-y-6 sm:space-y-8">
        <div class="text-center space-y-1">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#1E1B1D] font-serif">Loved by Hijabers Everywhere</h2>
            <p class="text-xs sm:text-sm text-[#665D62]">Pengalaman nyata dari pelanggan dan reseller hijab di seluruh Indonesia</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($reviews as $rev)
                <div class="bg-white rounded-3xl border border-[#EFE5EA] p-6 shadow-xs hover:shadow-md transition-all space-y-3.5 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset($rev->avatar_path ?: 'images/storefront/user-aisyah.jpg') }}" alt="{{ $rev->customer_name }}" class="w-10 h-10 rounded-full object-cover border border-[#D9C4CF]">
                            <div>
                                <h4 class="text-xs sm:text-sm font-bold text-[#1E1B1D]">{{ $rev->customer_name }}</h4>
                                <p class="text-[10px] text-[#665D62]">{{ $rev->customer_city ?: 'Indonesia' }} &bull; {{ $rev->customer_role ?: 'Pelanggan Terverifikasi' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center text-amber-400 text-xs">
                            <div class="flex items-center gap-0.5">
                                @for($i = 1; $i <= ($rev->rating ?: 5); $i++)
                                <svg class="w-3 h-3 fill-amber-400 text-amber-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                            <span class="ml-1.5 text-[11px] font-bold text-[#1E1B1D]">{{ $rev->rating }}.0</span>
                        </div>
                        <p class="text-xs sm:text-sm text-[#4A4447] leading-relaxed italic">
                            "{{ $rev->review_text }}"
                        </p>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-3xl border border-[#EFE5EA] p-6 shadow-xs space-y-3.5">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/storefront/user-aisyah.jpg') }}" alt="Raisya Putri" class="w-10 h-10 rounded-full object-cover border border-[#D9C4CF]">
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-[#1E1B1D]">Raisya Putri (Toko Raisya Hijab)</h4>
                            <p class="text-[10px] text-[#665D62]">Bandung &bull; Reseller &amp; Mitra Konveksi</p>
                        </div>
                    </div>
                    <div class="flex items-center text-amber-400 text-xs">
                        <div class="flex items-center gap-0.5">
                            @for($s=0;$s<5;$s++)
                            <svg class="w-3 h-3 fill-amber-400 text-amber-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <span class="ml-1.5 text-[11px] font-bold text-[#1E1B1D]">5.0</span>
                    </div>
                    <p class="text-xs sm:text-sm text-[#4A4447] leading-relaxed italic">
                        "Kualitas jahitan Hijab Segi Empat Malaysia dan Oval sangat rapi, tepi jahit halus dan presisi. Bahan jatuh dan nyaman dipakai seharian."
                    </p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- =========================================================================
         6. PROMOSI KONVEKSI & PASANG IKLAN SPONSORED (REFINED LUXURY BANNER)
         ========================================================================= --}}
    <section id="promo-iklan" class="rounded-3xl bg-gradient-to-r from-[#FAF2F6] via-[#FFF3F7] to-[#FCEAEF] border border-[#EBD7E3] p-6 sm:p-9 flex flex-col md:flex-row items-center justify-between gap-6 shadow-[0_2px_12px_rgba(82,12,49,0.04)]">
        <div class="space-y-2 text-center md:text-left">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-[#8E1B5C] text-[10.5px] font-bold uppercase tracking-wider border border-[#8E1B5C]/25 shadow-2xs">
                <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                <span>Promosi Mitra Konveksi</span>
            </span>
            <h3 class="text-lg sm:text-2xl font-bold text-[#520C31] font-serif leading-tight">
                Ingin Produk Konveksi Anda Tampil di Urutan Teratas?
            </h3>
            <p class="text-xs sm:text-sm text-[#665D62] max-w-xl leading-relaxed">
                Tingkatkan pesanan konveksi dan kemitraan brand dengan penempatan <strong>Sponsored Resmi</strong> di etalase nasional NAYARA. Kelola promosi dan slot iklan langsung dari portal Owner Anda.
            </p>
        </div>
        <div class="shrink-0 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('login') }}" class="px-5 py-2.5 sm:py-3 rounded-xl bg-[#520C31] hover:bg-[#68113F] text-white text-xs sm:text-sm font-semibold transition-all shadow-sm hover:shadow-md transform hover:-translate-y-0.5 cursor-pointer">
                Masuk Portal Owner &rarr;
            </a>
            <a href="{{ route('tenant.register.create') }}" class="px-5 py-2.5 sm:py-3 rounded-xl border-2 border-[#520C31] bg-white hover:bg-[#520C31] hover:text-white text-[#520C31] text-xs sm:text-sm font-semibold transition-all shadow-2xs cursor-pointer">
                Daftar Konveksi Baru
            </a>
        </div>
    </section>

</div>
@endsection
