<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NAYARA — Sistem Produksi & Perencanaan Bahan</title>
    <meta name="description" content="Sistem Produksi & Perencanaan Bahan NAYARA. Portal akses operasional dan pelacakan pesanan.">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#8E1B5C',
                            dark:    '#8E1B5C',
                            medium:  '#8E1B5C',
                            light:   '#FFD8D8',
                        },
                        'soft-pink': '#FFD8D8',
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-[#1A1816] text-white antialiased overflow-x-hidden selection:bg-[#8E1B5C] selection:text-white">

    {{-- Full-Screen Hero Viewport Container --}}
    <div class="relative min-h-screen flex flex-col justify-between overflow-hidden">

        {{-- Photographic Background Layer with Controlled Blur & Scale --}}
        <div class="absolute inset-0 bg-cover bg-center pointer-events-none"
             style="background-image: url('{{ asset('images/nayara-landing-bg.png') }}'); filter: blur(6px); transform: scale(1.04);"
             aria-hidden="true">
        </div>

        {{-- Restrained Dark Neutral Overlay Layer --}}
        <div class="absolute inset-0 bg-gradient-to-b from-[#1C1816]/65 via-[#241F1C]/55 to-[#161311]/75 pointer-events-none"
             aria-hidden="true">
        </div>

        {{-- ── 1. Navbar (Translucent Backdrop over Image) ──────────────── --}}
        <header class="relative z-10 w-full bg-black/20 backdrop-blur-md border-b border-white/10 shrink-0">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                {{-- Brand / Logo: NAYARA GROUP Platform --}}
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 group">
                    <img src="{{ asset('images/nayara-logo.jpg') }}" alt="NAYARA GROUP" class="w-9 h-9 rounded-lg object-contain shadow-xs shrink-0 border border-white/20 group-hover:scale-105 transition-transform">
                    <div class="space-y-0">
                        <span class="font-bold text-sm sm:text-base tracking-wide text-white leading-none block">NAYARA GROUP</span>
                        <span class="text-[10px] text-white/70 font-medium tracking-wider uppercase leading-none block">SaaS Platform &amp; MRP</span>
                    </div>
                </a>

                {{-- Top Actions --}}
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('storefront.index') }}"
                       class="inline-flex items-center justify-center h-9 px-3.5 rounded-lg border border-[#D96B9B]/50 bg-[#8E1B5C]/30 hover:bg-[#8E1B5C]/50 text-white text-xs font-semibold backdrop-blur-xs transition-all duration-200 cursor-pointer shadow-xs">
                        Katalog Publik
                    </a>
                    <a href="{{ route('track.index') }}"
                       class="inline-flex items-center justify-center h-9 px-3.5 rounded-lg border border-white/25 bg-white/5 hover:bg-white/15 hover:border-white/45 text-white/90 text-xs font-semibold backdrop-blur-xs transition-all duration-200 cursor-pointer shadow-xs">
                        Lacak Pesanan
                    </a>
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs font-semibold transition-all duration-200 cursor-pointer shadow-xs">
                        Masuk
                    </a>
                </div>
            </div>
        </header>

        {{-- ── 2. Centered Hero Content ────────────────────────────────── --}}
        <main class="relative z-10 flex-1 flex items-center justify-center px-4 sm:px-6 py-12">
            <div class="max-w-3xl mx-auto text-center space-y-6 sm:space-y-8">

                {{-- Main Title & Subtitle --}}
                <div class="space-y-3 sm:space-y-4">
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white tracking-tight leading-tight drop-shadow-sm">
                        Sistem Produksi &amp; Perencanaan Bahan
                    </h1>
                    <p class="text-sm sm:text-base md:text-lg text-white/85 max-w-xl mx-auto leading-relaxed drop-shadow-xs font-normal">
                        Pesanan, kebutuhan bahan, dan proses produksi dalam satu sistem.
                    </p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                    <a href="{{ route('storefront.index') }}"
                       class="inline-flex items-center justify-center gap-2 h-11 px-6 rounded-xl bg-gradient-to-r from-[#8E1B5C] to-[#701D49] hover:from-[#701D49] hover:to-[#520C31] text-white text-xs sm:text-sm font-semibold transition-all duration-200 shadow-md cursor-pointer border border-pink-400/30">
                        <span>Lihat Showcase &amp; Katalog Publik</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center gap-2 h-11 px-6 rounded-xl bg-white text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] text-xs sm:text-sm font-semibold transition-all duration-200 shadow-md cursor-pointer">
                        <span>Masuk Sistem</span>
                    </a>
                    <a href="{{ route('track.index') }}"
                       class="inline-flex items-center justify-center gap-2 h-11 px-6 rounded-xl border border-white/30 bg-black/20 hover:bg-white/15 hover:border-white/50 text-white text-xs sm:text-sm font-semibold backdrop-blur-xs transition-all duration-200 shadow-sm cursor-pointer">
                        <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Lacak Pesanan</span>
                    </a>
                </div>

                {{-- Optional Subtle Micro Detail --}}
                <div class="pt-2 text-[11px] text-white/60 font-medium tracking-wider uppercase">
                    NAYARA &middot; Production &amp; MRP
                </div>

            </div>
        </main>

        {{-- ── 3. Minimal Footer ─────────────────────────────────────────── --}}
        <footer class="relative z-10 w-full py-4 px-4 sm:px-6 text-center text-xs text-white/50 shrink-0">
            &copy; 2026 NAYARA &middot; Production &amp; MRP
        </footer>

    </div>

</body>
</html>
