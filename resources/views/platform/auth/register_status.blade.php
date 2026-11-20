<!DOCTYPE html>
<html lang="id" class="h-full" style="background: linear-gradient(175deg, #7E2248 0%, #681B3F 28%, #541432 68%, #460E28 100%); background-attachment: fixed; min-height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cek Status Pendaftaran Konveksi Hijab — NAYARA SaaS ERP</title>

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
<body class="text-slate-800 antialiased py-5 sm:py-9 px-3.5 sm:px-6 lg:px-8 flex flex-col justify-start items-center min-h-screen relative selection:bg-[#F7E6EE] selection:text-[#8E1B5C] w-full max-w-full overflow-x-hidden">

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

    <div class="max-w-2xl w-full mx-auto space-y-4 sm:space-y-5 relative z-10">

        {{-- ── 1. TOP NAVIGATION BAR (SAMPING KIRI & SAMPING KANAN DENGAN TOMBOL REAL ELEGAN) ── --}}
        <div class="flex items-center justify-between gap-2.5">
            {{-- Tombol Sisi Kiri: Kembali ke Pendaftaran --}}
            <a href="{{ route('tenant.register.create') }}"
               class="inline-flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 rounded-xl text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/25 transition-all duration-200 shadow-xs group cursor-pointer backdrop-blur-sm whitespace-nowrap">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                <span class="hidden sm:inline">Kembali ke Pendaftaran</span>
                <span class="sm:hidden">Kembali</span>
            </a>

            {{-- Tombol Sisi Kanan: Halaman Login ERP --}}
            <a href="{{ route('login') }}"
               class="inline-flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 rounded-xl text-xs font-bold text-[#8E1B5C] bg-[#FCE6EF] hover:bg-white hover:text-[#68113F] border border-[#FFD8D8] transition-all duration-200 shadow-xs group cursor-pointer whitespace-nowrap">
                <span class="hidden sm:inline">Halaman Login ERP</span>
                <span class="sm:hidden">Login ERP</span>
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </a>
        </div>

        {{-- ── 2. BRAND HEADER: LOGO & IDENTITY ── --}}
        <div class="flex items-center justify-center gap-2.5 sm:gap-3 pt-0.5 text-white">
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

        {{-- ── 3. FLASH NOTIFICATIONS ── --}}
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-300/40 text-emerald-100 text-xs flex items-center gap-2.5 backdrop-blur-sm shadow-sm">
                <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </div>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-500/15 border border-rose-300/40 text-rose-100 text-xs flex items-center gap-2.5 backdrop-blur-sm shadow-sm">
                <div class="w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        @endif

        {{-- ── 4. FLOATING WHITE CARD: FORMULIR PELACAKAN STATUS ── --}}
        <div class="bg-white rounded-[24px] sm:rounded-[28px] border border-white/90 shadow-2xl p-5 sm:p-7.5 relative overflow-hidden space-y-5">

            {{-- Soft Pink Floral Watermark in Top-Right Corner --}}
            <div class="pointer-events-none absolute top-0 right-0 overflow-hidden w-40 h-40 sm:w-52 sm:h-52 z-0 select-none opacity-80">
                <svg class="w-full h-full text-[#F7E6EE] translate-x-8 -translate-y-8" viewBox="0 0 200 200" fill="currentColor">
                    <path d="M100 0 C120 40 160 80 200 100 C160 120 120 160 100 200 C80 160 40 120 0 100 C40 80 80 40 100 0 Z" fill="currentColor" fill-opacity="0.5"/>
                    <path d="M130 30 C145 60 175 90 205 105 C175 120 145 150 130 180 C115 150 85 120 55 105 C85 90 115 60 130 30 Z" fill="currentColor" fill-opacity="0.3"/>
                    <circle cx="160" cy="40" r="28" fill="currentColor" fill-opacity="0.25"/>
                    <circle cx="130" cy="70" r="18" fill="currentColor" fill-opacity="0.2"/>
                </svg>
            </div>

            {{-- ── A. HEADER CARD: HIERARKI VISUAL PROPORSIONAL ── --}}
            <div class="space-y-2 relative z-10">
                <h1 class="text-xl sm:text-[23px] font-extrabold text-[#50162B] tracking-tight leading-snug">
                    Cek Status Pendaftaran Konveksi Hijab
                </h1>

                <div class="flex items-center pt-0.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#FCE6EF] text-[#8E1B5C] border border-[#FFD8D8]">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                        <span>Pelacakan Verifikasi Outlet</span>
                        <span class="sr-only">Pelacakan Status Pengajuan</span>
                    </span>
                </div>

                <p class="text-xs sm:text-[12.5px] text-slate-500 leading-relaxed font-normal pt-0.5">
                    Masukkan email pemilik dan nomor WhatsApp yang terdaftar untuk melacak status verifikasi konveksi Anda.
                </p>
            </div>

            {{-- ── B. FORMULIR PENELUSURAN STATUS ── --}}
            <form method="GET" action="{{ route('tenant.register.status') }}" class="space-y-4 relative z-10 pt-1">

                {{-- Section Pill Header --}}
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#8E1B5C]"></span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold uppercase tracking-wider bg-[#FCE6EF] text-[#8E1B5C]">
                        DATA IDENTITAS PEMILIK (OWNER)
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    {{-- Input Email --}}
                    <div class="space-y-1">
                        <label for="email" class="block text-xs font-bold text-[#172B4D]">
                            Alamat Email Pemilik <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                            </div>
                            <input type="email"
                                   name="email"
                                   id="email"
                                   value="{{ request('email') }}"
                                   placeholder="Contoh: owner@konveksihijab.com"
                                   required
                                   class="w-full pl-10 pr-4 h-11 text-xs sm:text-[13px] rounded-xl border border-slate-200 bg-white text-[#172B4D] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C] transition-colors shadow-2xs">
                        </div>
                        <p class="text-[10.5px] text-slate-400">Email yang didaftarkan sebagai akun Owner.</p>
                    </div>

                    {{-- Input WhatsApp --}}
                    <div class="space-y-1">
                        <label for="phone_wa" class="block text-xs font-bold text-[#172B4D]">
                            Nomor WhatsApp / HP <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                                </svg>
                            </div>
                            <input type="text"
                                   name="phone_wa"
                                   id="phone_wa"
                                   value="{{ request('phone_wa') }}"
                                   placeholder="Contoh: 081234567890"
                                   required
                                   class="w-full pl-10 pr-4 h-11 text-xs sm:text-[13px] rounded-xl border border-slate-200 bg-white text-[#172B4D] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C] transition-colors shadow-2xs">
                        </div>
                        <p class="text-[10.5px] text-slate-400">Nomor WhatsApp aktif untuk konfirmasi pendaftaran.</p>
                    </div>
                </div>

                {{-- Primary Action Button: Full-width, Solid Burgundy, Grounded & Balanced --}}
                <div class="pt-2">
                    <button type="submit"
                            class="w-full h-11.5 sm:h-12 px-6 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white font-bold text-xs sm:text-sm shadow-md shadow-[#8E1B5C]/25 hover:shadow-lg hover:shadow-[#8E1B5C]/30 transition-all flex items-center justify-center gap-2 cursor-pointer group">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                        <span>Lacak Status Pendaftaran Sekarang</span>
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </button>
                </div>
            </form>

        </div>

        {{-- ── 5. HASIL PENELUSURAN STATUS (JIKA DICARI) ── --}}
        @if(request()->filled('email') && request()->filled('phone_wa'))
            @if($registration)
                <div class="bg-white rounded-[24px] sm:rounded-[28px] border border-white/90 shadow-2xl overflow-hidden space-y-0">

                    {{-- Status Banner Header --}}
                    <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4
                        @if($registration->isPending()) bg-amber-50/70 border-amber-200/60
                        @elseif($registration->isApproved()) bg-emerald-50/70 border-emerald-200/60
                        @elseif($registration->isNeedsRevision()) bg-yellow-50/80 border-yellow-300/70
                        @elseif($registration->isRejected()) bg-rose-50/70 border-rose-200/60
                        @endif">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base sm:text-lg font-bold text-slate-800">{{ $registration->company_name }}</h2>
                                @if($registration->isPending())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                        PENDING VERIFIKASI
                                    </span>
                                @elseif($registration->isApproved())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                                        <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        DISETUJUI &amp; AKTIF
                                    </span>
                                @elseif($registration->isNeedsRevision())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-yellow-200 text-yellow-900 border border-yellow-400 shadow-2xs">
                                        <svg class="w-3 h-3 text-yellow-800" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        PERLU PERBAIKAN DATA
                                    </span>
                                @elseif($registration->isRejected())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-rose-100 text-rose-800 border border-rose-300 shadow-2xs">
                                        <svg class="w-3 h-3 text-rose-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        DITOLAK
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500">Pemohon: <span class="font-semibold text-slate-700">{{ $registration->owner_name }}</span> ({{ $registration->email }})</p>
                        </div>

                        <div class="text-xs text-slate-500 shrink-0">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white/70 border border-slate-200">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Terdaftar: {{ \App\Support\DateTimeFormatter::formatDateTime($registration->created_at) }}
                            </span>
                        </div>
                    </div>

                    {{-- Detail Card Body --}}
                    <div class="p-5 sm:p-7 space-y-6">

                        {{-- If Approved --}}
                        @if($registration->isApproved())
                            <div class="p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 space-y-3.5 shadow-2xs">
                                <div class="flex items-center gap-2 font-bold text-sm text-emerald-950">
                                    <div class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                    </div>
                                    <span>Selamat! Outlet Konveksi Anda Telah Aktif</span>
                                </div>
                                <p class="text-xs leading-relaxed text-emerald-800">
                                    Pengajuan konveksi Anda telah disetujui oleh Super Admin. Outlet mandiri dan akun <strong>OWNER</strong> Anda telah siap digunakan. Silakan masuk ke dashboard ERP untuk memulai pencatatan keuangan, master kain, dan produksi hijab.
                                </p>
                                <div class="pt-1">
                                    <a href="{{ route('login') }}"
                                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/25 transition-all group cursor-pointer">
                                        <span>Masuk ke ERP Sekarang</span>
                                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endif

                        {{-- If Pending --}}
                        @if($registration->isPending())
                            <div class="p-5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 space-y-3 shadow-2xs">
                                <div class="flex items-center gap-2 font-bold text-sm text-amber-950">
                                    <div class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <span>Pengajuan Dalam Antrean Verifikasi</span>
                                </div>
                                <p class="text-xs leading-relaxed text-amber-800">
                                    Berkas pengajuan konveksi hijab Anda sedang diperiksa oleh tim Super Admin NAYARA. Proses verifikasi biasanya membutuhkan waktu 1-24 jam kerja. Setelah disetujui, akun Owner konveksi Anda akan aktif secara otomatis.
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1 text-[11.5px]">
                                    <div class="p-2.5 rounded-xl bg-white/80 border border-amber-200/80 flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px] flex items-center justify-center shrink-0">✓</span>
                                        <span class="font-medium text-slate-700">Formulir Diterima</span>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/80 border border-amber-200/80 flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px] flex items-center justify-center shrink-0 animate-pulse">•</span>
                                        <span class="font-semibold text-amber-900">Review Verifikasi</span>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/80 border border-amber-200/80 flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-400 font-bold text-[10px] flex items-center justify-center shrink-0">3</span>
                                        <span class="font-medium text-slate-500">Aktivasi ERP</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- If Needs Revision --}}
                        @if($registration->isNeedsRevision())
                            <div class="p-5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 space-y-3 shadow-2xs">
                                <div class="flex items-center gap-2 font-bold text-sm text-amber-950">
                                    <div class="w-6 h-6 rounded-full bg-yellow-500 text-white flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                        </svg>
                                    </div>
                                    <span>Catatan Perbaikan dari Super Admin:</span>
                                </div>
                                <div class="text-xs font-medium p-3.5 bg-white rounded-xl border border-amber-300 text-amber-950 leading-relaxed shadow-2xs">
                                    {{ $registration->review_notes ?? 'Harap periksa dan lengkapi kembali data konveksi Anda.' }}
                                </div>
                            </div>

                            {{-- Resubmission Form --}}
                            <div class="p-5 sm:p-6 rounded-2xl bg-[#FFF9FB] border border-[#FFD8D8] space-y-4">
                                <div>
                                    <h3 class="text-sm font-bold text-[#172B4D]">Formulir Kirim Ulang Perbaikan Data</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Perbarui informasi yang diminta sebelum mengajukan verifikasi ulang.</p>
                                </div>

                                <form method="POST" action="{{ route('tenant.register.resubmit', $registration->id) }}" class="space-y-3.5">
                                    @csrf

                                    <div>
                                        <label for="company_name" class="block text-xs font-bold text-[#172B4D] mb-1">Nama Perusahaan / Konveksi</label>
                                        <input type="text"
                                               name="company_name"
                                               id="company_name"
                                               value="{{ old('company_name', $registration->company_name) }}"
                                               required
                                               class="w-full px-3.5 py-2 text-xs sm:text-[13px] rounded-xl border border-slate-200 bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C]">
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label for="owner_name" class="block text-xs font-bold text-[#172B4D] mb-1">Nama Lengkap Pemilik</label>
                                            <input type="text"
                                                   name="owner_name"
                                                   id="owner_name"
                                                   value="{{ old('owner_name', $registration->owner_name) }}"
                                                   required
                                                   class="w-full px-3.5 py-2 text-xs sm:text-[13px] rounded-xl border border-slate-200 bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C]">
                                        </div>
                                        <div>
                                            <label for="phone_wa" class="block text-xs font-bold text-[#172B4D] mb-1">Nomor WhatsApp</label>
                                            <input type="text"
                                                   name="phone_wa"
                                                   id="phone_wa"
                                                   value="{{ old('phone_wa', $registration->phone_wa) }}"
                                                   required
                                                   class="w-full px-3.5 py-2 text-xs sm:text-[13px] rounded-xl border border-slate-200 bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C]">
                                        </div>
                                    </div>

                                    <div>
                                        <label for="address" class="block text-xs font-bold text-[#172B4D] mb-1">Alamat Workshop</label>
                                        <textarea name="address"
                                                  id="address"
                                                  rows="2"
                                                  class="w-full px-3.5 py-2 text-xs sm:text-[13px] rounded-xl border border-slate-200 bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C]">{{ old('address', $registration->address) }}</textarea>
                                    </div>

                                    <div>
                                        <label for="notes" class="block text-xs font-bold text-[#172B4D] mb-1">Catatan Tambahan untuk Verifikator</label>
                                        <textarea name="notes"
                                                  id="notes"
                                                  rows="2"
                                                  class="w-full px-3.5 py-2 text-xs sm:text-[13px] rounded-xl border border-slate-200 bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C]">{{ old('notes', $registration->notes) }}</textarea>
                                    </div>

                                    <div class="pt-2 flex justify-end">
                                        <button type="submit"
                                                class="px-5 py-2.5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white font-bold text-xs shadow-md shadow-[#8E1B5C]/25 transition-all flex items-center gap-1.5 cursor-pointer group">
                                            <span>Kirim Ulang Data Perbaikan</span>
                                            <svg class="w-4 h-4 shrink-0 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                            </svg>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        {{-- If Rejected --}}
                        @if($registration->isRejected())
                            <div class="p-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 space-y-3 shadow-2xs">
                                <div class="flex items-center gap-2 font-bold text-sm text-rose-950">
                                    <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>
                                    <span>Alasan Penolakan Pengajuan:</span>
                                </div>
                                <div class="text-xs font-medium p-3.5 bg-white rounded-xl border border-rose-200 text-rose-950 leading-relaxed shadow-2xs">
                                    {{ $registration->review_notes ?? 'Pengajuan belum memenuhi kriteria kualifikasi konveksi mandiri NAYARA.' }}
                                </div>
                                <div class="pt-1">
                                    <a href="{{ route('tenant.register.create') }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm transition-all">
                                        <span>Ajukan Pendaftaran Baru</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                    </a>
                                </div>
                            </div>
                        @endif

                        {{-- Timeline History --}}
                        <div class="pt-4 border-t border-slate-100">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Riwayat Pengajuan &amp; Verifikasi
                            </h4>
                            <div class="space-y-4 pl-2">
                                @forelse($registration->histories->sortByDesc('created_at') as $h)
                                    <div class="flex items-start gap-3 text-xs relative">
                                        <div class="w-2.5 h-2.5 rounded-full bg-[#8E1B5C] mt-1 shrink-0 ring-4 ring-[#FCE6EF]"></div>
                                        <div class="flex-1 bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-bold text-slate-800">{{ $h->action }}</span>
                                                <span class="text-[11px] text-slate-400 font-medium">{{ \App\Support\DateTimeFormatter::formatDateTime($h->created_at) }}</span>
                                            </div>
                                            <p class="text-slate-600 mt-1 leading-relaxed">{{ $h->notes }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400">Belum ada catatan riwayat tambahan.</p>
                                @endforelse
                            </div>
                        </div>

                    </div>

                </div>
            @else
                <div class="bg-white rounded-[24px] sm:rounded-[28px] border border-white/90 shadow-2xl p-6 sm:p-8 text-center space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-800">Data Pendaftaran Tidak Ditemukan</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                            Pastikan alamat email (<span class="font-semibold text-slate-700">{{ request('email') }}</span>) dan nomor WhatsApp (<span class="font-semibold text-slate-700">{{ request('phone_wa') }}</span>) yang Anda masukkan sesuai dengan yang Anda daftarkan sebelumnya.
                        </p>
                    </div>
                    <div class="pt-2 flex flex-wrap items-center justify-center gap-2.5">
                        <a href="{{ route('tenant.register.create') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white font-bold text-xs shadow-md shadow-[#8E1B5C]/25 transition-all">
                            <span>Daftarkan Konveksi Sekarang</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </a>
                    </div>
                </div>
            @endif
        @endif

        {{-- Subtle Footer Copyright --}}
        <div class="text-center text-[11px] text-[#F7E6EE]/70 pt-2 pb-6">
            &copy; 2026 NAYARA Group. Sistem Perencanaan &amp; Operasional Produksi Hijab.
        </div>

    </div>

</body>
</html>
