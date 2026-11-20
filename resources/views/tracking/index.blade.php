<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lacak Pesanan — NAYARA</title>
    <meta name="description" content="Lacak status dan progres pesanan hijab NAYARA menggunakan token tracking.">

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
<body class="h-full bg-[#1A1816] text-white antialiased overflow-x-hidden selection:bg-[#8E1B5C] selection:text-white">

    {{-- Full Viewport Container --}}
    <div class="relative min-h-screen flex flex-col justify-between overflow-hidden">

        {{-- Photographic Background Layer with Controlled Blur & Scale --}}
        <div class="absolute inset-0 bg-cover bg-center pointer-events-none"
             style="background-image: url('{{ asset('images/nayara-landing-bg.png') }}'); filter: blur(6px); transform: scale(1.04);"
             aria-hidden="true">
        </div>

        {{-- Dark Neutral Translucent Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-b from-[#1C1816]/65 via-[#241F1C]/55 to-[#161311]/75 pointer-events-none"
             aria-hidden="true">
        </div>

        {{-- Top Spacer (No Navbar / No Repeated Header) --}}
        <div class="relative z-10 w-full h-6 sm:h-10 shrink-0"></div>

        {{-- Centered Frosted Glass Tracking Card --}}
        <main class="relative z-10 flex-1 flex items-center justify-center p-4 sm:p-6">
            <div class="w-full max-w-md rounded-2xl p-6 sm:p-8 text-center space-y-5 text-[#172B4D]"
                 style="background: rgba(255, 249, 251, 0.78); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.55); box-shadow: 0 18px 45px rgba(40, 29, 35, 0.14);">

                {{-- Tracking Icon Badge --}}
                <div class="w-12 h-12 bg-[#FFD8D8]/90 text-[#8E1B5C] rounded-2xl flex items-center justify-center mx-auto shadow-xs border border-white/60">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                {{-- Title & Short Copy --}}
                <div class="space-y-1.5">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-[#172B4D] tracking-tight leading-tight">
                        Lacak Pesanan Anda
                    </h1>
                    <p class="text-xs sm:text-sm text-[#64748B] leading-relaxed">
                        Masukkan token atau tautan pelacakan pesanan.
                    </p>
                </div>

                {{-- Form Submission with Dual Format Normalization --}}
                <form id="trackForm"
                      onsubmit="handleTrackSubmit(event)"
                      class="space-y-3.5 pt-1">
                    <div>
                        <input id="tokenInput"
                               type="text"
                               placeholder="Token atau tautan tracking"
                               required
                               autocomplete="off"
                               autofocus
                               style="background: rgba(255, 255, 255, 0.65);"
                               class="w-full h-11 px-4 text-xs sm:text-sm text-center border border-[#FFD8D8]/80 rounded-xl text-[#172B4D] font-mono placeholder:font-sans placeholder:text-[#64748B]/70 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C] focus:border-transparent transition">
                    </div>

                    <div id="trackError" class="hidden text-xs text-red-600 font-medium bg-red-50/90 border border-red-200/80 rounded-lg p-2 text-center"></div>

                    <button type="submit"
                            class="w-full h-11 bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white font-semibold text-xs sm:text-sm rounded-xl shadow-xs transition-all duration-200 cursor-pointer flex items-center justify-center gap-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#8E1B5C]">
                        <span>Lacak Pesanan</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </button>
                </form>

                <script>
                    function handleTrackSubmit(e) {
                        e.preventDefault();
                        const input = document.getElementById('tokenInput');
                        const errEl = document.getElementById('trackError');
                        let val = input.value.trim();

                        if (!val) return;

                        // Match 64-hex token from full URL (/track/{token}) or standalone token
                        const match = val.match(/(?:\/track\/|^)([a-f0-9]{64})(?:[\/?#]|$)/i);
                        if (match) {
                            errEl.classList.add('hidden');
                            window.location.href = '/track/' + match[1].toLowerCase();
                            return;
                        }

                        errEl.textContent = 'Token atau tautan pelacakan tidak valid.';
                        errEl.classList.remove('hidden');
                    }
                </script>

                {{-- Secondary Links & Ghost Back Action --}}
                <div class="pt-3 border-t border-[#FFD8D8]/60 space-y-2 text-xs text-[#64748B]">
                    <p>
                        Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-[#8E1B5C] hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#8E1B5C] rounded-sm">Masuk</a>
                    </p>
                    <div class="pt-1">
                        <a href="{{ url('/') }}"
                           class="inline-flex items-center justify-center gap-1.5 text-sm font-medium text-[#64748B] hover:text-[#8E1B5C] transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#8E1B5C] rounded-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                            </svg>
                            <span>Kembali ke beranda</span>
                        </a>
                    </div>
                </div>

            </div>
        </main>

        {{-- Minimal Footer --}}
        <footer class="relative z-10 w-full py-4 px-4 sm:px-6 text-center text-xs text-white/50 shrink-0">
            &copy; 2026 NAYARA &middot; Production &amp; MRP
        </footer>

    </div>

</body>
</html>
