<!DOCTYPE html>
<html lang="id" class="h-full" style="background: linear-gradient(175deg, #7E2248 0%, #681B3F 28%, #541432 68%, #460E28 100%); background-attachment: fixed; min-height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil Dikirim — NAYARA SaaS ERP</title>

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
                        primary: {
                            DEFAULT: '#8E1B5C',
                            dark:    '#68113F',
                            deep:    '#50162B',
                            medium:  '#701D49',
                            active:  '#7F345C',
                            light:   '#F7E6EE',
                        },
                        nayara: {
                            deep:            '#4E0C2E',
                            burgundy:        '#68113F',
                            primary:         '#8E1B5C',
                            'primary-medium':'#701D49',
                            pink:            '#D96B9B',
                            blush:           '#F7E6EE',
                            border:          '#E1CFD9',
                            background:      '#FFF9FB',
                            white:           '#FFFFFF',
                        },
                        'soft-pink': '#F7E6EE',
                        'border-c':  '#E1CFD9',
                        'text-main': '#172B4D',
                        'text-sec':  '#64748B',
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    }
                }
            }
        }
    </script>

    <style>
        html {
            background: #460E28;
            background: linear-gradient(175deg, #7E2248 0%, #681B3F 28%, #541432 68%, #460E28 100%);
            background-attachment: fixed;
            min-height: 100%;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.28) transparent;
            overflow-x: hidden;
        }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100%;
            background: transparent;
            overflow-x: hidden;
        }
        /* Custom Dark Burgundy Scrollbar - Eliminates the white vertical track entirely */
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.42);
        }
    </style>
</head>
<body class="text-slate-800 antialiased py-6 sm:py-10 px-3 sm:px-6 lg:px-8 flex flex-col justify-start items-center min-h-screen relative selection:bg-[#F7E6EE] selection:text-[#8E1B5C] w-full max-w-full overflow-x-hidden">

    {{-- Subtle Ambient Geometric Batik Pattern in Top-Left --}}
    <div class="pointer-events-none fixed top-0 left-0 w-80 h-80 sm:w-96 sm:h-96 opacity-[0.06] select-none z-0">
        <svg class="w-full h-full text-white" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
            <polygon points="100,10 120,60 170,40 145,90 190,120 135,130 140,185 95,150 55,185 65,130 10,120 55,90 30,40 80,60" />
            <polygon points="100,25 115,65 155,50 135,90 170,115 125,122 130,165 95,138 65,165 72,122 30,115 65,90 45,50 85,65" opacity="0.6"/>
            <circle cx="100" cy="100" r="70" stroke-dasharray="3 3"/>
            <circle cx="100" cy="100" r="45"/>
        </svg>
    </div>

    {{-- Fine Dot Grid Background Layer --}}
    <div class="pointer-events-none fixed inset-0 opacity-[0.03] bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:24px_24px]"></div>

    <div class="max-w-xl w-full mx-auto space-y-5 relative z-10">

        {{-- ── 1. BRAND HEADER: LOGO & IDENTITY ── --}}
        <div class="flex items-center justify-center gap-2.5 sm:gap-3 pt-1 text-white">
            <div class="flex items-center gap-2 sm:gap-2.5">
                <img src="{{ asset('images/nayara-logo.jpg') }}"
                     alt="NAYARA GROUP"
                     class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl object-contain shadow-xs shrink-0 border border-white/20 bg-white/10">
                <div class="space-y-0.5 text-left">
                    <h2 class="font-extrabold text-sm sm:text-base tracking-wider text-white leading-none">NAYARA</h2>
                    <p class="text-[8px] sm:text-[9px] text-[#F7E6EE]/80 tracking-[0.25em] font-semibold uppercase leading-none">GROUP</p>
                </div>
            </div>

            <div class="h-5 sm:h-6 w-[1px] bg-white/20"></div>

            <div class="space-y-0.5 text-left">
                <h3 class="font-bold text-xs sm:text-[13px] text-white leading-tight">NAYARA ERP</h3>
                <p class="text-[9px] sm:text-[10px] text-[#F7E6EE]/80 leading-tight">Sistem Operasional Konveksi Hijab</p>
            </div>
        </div>

        {{-- ── 2. SUCCESS FLOATING CARD ── --}}
        <div class="bg-white rounded-[24px] sm:rounded-[28px] border border-white/90 shadow-2xl p-6 sm:p-9 text-center relative overflow-hidden space-y-6">

            {{-- Soft Pink Floral Watermark in Top-Right Corner --}}
            <div class="pointer-events-none absolute top-0 right-0 overflow-hidden w-40 h-40 sm:w-48 sm:h-48 z-0 select-none opacity-80">
                <svg class="w-full h-full text-[#F7E6EE] translate-x-8 -translate-y-8" viewBox="0 0 200 200" fill="currentColor">
                    <path d="M100 0 C120 40 160 80 200 100 C160 120 120 160 100 200 C80 160 40 120 0 100 C40 80 80 40 100 0 Z" fill="currentColor" fill-opacity="0.5"/>
                    <path d="M130 30 C145 60 175 90 205 105 C175 120 145 150 130 180 C115 150 85 120 55 105 C85 90 115 60 130 30 Z" fill="currentColor" fill-opacity="0.3"/>
                    <circle cx="160" cy="40" r="28" fill="currentColor" fill-opacity="0.25"/>
                    <circle cx="130" cy="70" r="18" fill="currentColor" fill-opacity="0.2"/>
                </svg>
            </div>

            {{-- Success Icon Container --}}
            <div class="relative z-10">
                <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-600 border border-emerald-300 flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </div>

                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 mb-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Pendaftaran Berhasil Diterima</span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#172B4D] tracking-tight">Terima Kasih!</h1>
                <p class="text-xs sm:text-[13px] text-slate-500 mt-1.5 leading-relaxed max-w-md mx-auto">
                    Pengajuan pendaftaran konveksi hijab Anda telah masuk ke dalam antrean verifikasi Super Admin platform NAYARA ERP.
                </p>
            </div>

            {{-- Summary Details --}}
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 border border-slate-200/80 text-left space-y-2.5 text-xs relative z-10">
                @if(session('company_name'))
                    <div class="flex justify-between items-center py-1 border-b border-slate-200">
                        <span class="text-slate-500 font-medium">Nama Konveksi / Usaha:</span>
                        <span class="font-bold text-[#172B4D]">{{ session('company_name') }}</span>
                    </div>
                @endif
                @if(session('email'))
                    <div class="flex justify-between items-center py-1 border-b border-slate-200">
                        <span class="text-slate-500 font-medium">Email Pemilik (Owner):</span>
                        <span class="font-semibold text-[#172B4D]">{{ session('email') }}</span>
                    </div>
                @endif
                <div class="flex justify-between items-center py-1">
                    <span class="text-slate-500 font-medium">Status Awal:</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                        PENDING (MENUNGGU VERIFIKASI)
                    </span>
                </div>
            </div>

            {{-- What Happens Next --}}
            <div class="text-left p-4 sm:p-5 rounded-2xl bg-[#FFFBF7] border border-[#F3DFC9] text-xs text-[#172B4D] space-y-2 relative z-10">
                <p class="font-bold text-[#6B3B18] flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-700 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    Tahapan Selanjutnya:
                </p>
                <ol class="list-decimal pl-5 space-y-1 text-[#7A4B29] leading-relaxed">
                    <li>Tim Super Admin akan memeriksa kelengkapan identitas konveksi hijab Anda.</li>
                    <li>Setelah disetujui, Outlet independen dan akun <strong>OWNER</strong> Anda langsung aktif.</li>
                    <li>Anda dapat melacak status pengajuan kapan saja atau masuk ke ERP setelah verifikasi selesai.</li>
                </ol>
            </div>

            {{-- Action Buttons --}}
            <div class="pt-2 flex flex-col sm:flex-row gap-3 relative z-10">
                <a href="{{ route('tenant.register.status') }}"
                   class="flex-1 h-11.5 px-5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white font-bold text-xs sm:text-[13px] shadow-md shadow-[#8E1B5C]/25 hover:shadow-lg transition-all flex items-center justify-center gap-2 group cursor-pointer">
                    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <span>Cek Status Pengajuan</span>
                </a>

                <a href="{{ route('login') }}"
                   class="flex-1 h-11.5 px-5 rounded-xl bg-[#FCE6EF] hover:bg-white text-[#8E1B5C] hover:text-[#68113F] border border-[#FFD8D8] font-bold text-xs sm:text-[13px] shadow-xs transition-all flex items-center justify-center gap-2 group cursor-pointer">
                    <span>Halaman Login ERP</span>
                    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            </div>

        </div>

        {{-- Subtle Footer Copyright --}}
        <div class="text-center text-[11px] text-[#F7E6EE]/70 pt-1 pb-6">
            &copy; 2026 NAYARA Group. Sistem Perencanaan &amp; Operasional Produksi Hijab.
        </div>

    </div>

</body>
</html>
