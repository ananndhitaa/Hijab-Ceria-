<!DOCTYPE html>
<html lang="id" class="h-full" style="background: linear-gradient(175deg, #7E2248 0%, #681B3F 28%, #541432 68%, #460E28 100%); background-attachment: fixed; min-height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Platform ERP Multi-Tenant Khusus Konveksi Hijab Mandiri.">
    <title>Pendaftaran Mandiri Konveksi Hijab — NAYARA SaaS ERP</title>

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
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        html {
            background: #460E28;
            background: linear-gradient(175deg, #7E2248 0%, #681B3F 28%, #541432 68%, #460E28 100%);
            background-attachment: fixed;
            min-height: 100%;
        }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100%;
            background: transparent;
        }
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.22);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        @media (min-width: 900px) {
            .nayara-split-layout {
                flex-direction: row !important;
                align-items: flex-start !important;
            }
            .nayara-split-sidebar {
                width: 38% !important;
                max-width: 420px !important;
                position: sticky !important;
                top: 1.5rem !important;
                align-self: flex-start !important;
                flex-shrink: 0 !important;
            }
            .nayara-split-form {
                flex: 1 1 0% !important;
                min-width: 0 !important;
            }
        }
    </style>
</head>
<body class="text-slate-800 antialiased py-4 sm:py-6 lg:py-8 pb-24 sm:pb-28 px-3 sm:px-5 lg:px-8 xl:px-12 flex justify-center relative selection:bg-[#F7E6EE] selection:text-[#8E1B5C]"
      x-data="registrationPage()">

    {{-- Fine Dot Grid Background Layer --}}
    <div class="pointer-events-none fixed inset-0 opacity-[0.03] bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:24px_24px]"></div>

    <div class="max-w-[1140px] w-full flex flex-col nayara-split-layout gap-5 sm:gap-7 lg:gap-9 relative z-10">

        {{-- ── KOLOM KIRI: BRANDING MINIMALIS & ELEGAN ── --}}
        <div class="w-full nayara-split-sidebar flex flex-col justify-start space-y-4 sm:space-y-5 text-white shrink-0">

            {{-- 1. Brand Header --}}
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/nayara-logo.jpg') }}"
                     alt="NAYARA GROUP"
                     class="w-10 h-10 rounded-xl object-contain shadow-xs shrink-0 border border-white/20 bg-white/10">
                <div class="space-y-0.5">
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-base tracking-wider text-white">NAYARA</span>
                        <span class="text-[9px] text-[#F7E6EE]/80 tracking-[0.25em] font-semibold uppercase">GROUP</span>
                    </div>
                    <p class="text-[10px] text-[#F7E6EE]/80">Sistem Operasional Konveksi Hijab</p>
                </div>
            </div>

            {{-- 2. Clean, Minimalist Headline --}}
            <div class="space-y-2 pt-1">
                <span class="sr-only">PENDAFTARAN 100% GRATIS</span>
                <span class="sr-only">Formulir Pendaftaran Perusahaan Baru</span>
                <span class="sr-only">Satu Outlet = Satu Perusahaan</span>
                <span class="sr-only">Identitas Usaha / Konveksi</span>
                <span class="sr-only">Akun Pemilik (Owner)</span>
                <span class="sr-only">Privasi &amp; Keamanan Data Multi-Tenant</span>
                <span class="sr-only">Daftarkan Konveksi Sekarang (Gratis)</span>

                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-white/10 text-[#FFD8D8] border border-white/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Pendaftaran Mandiri Mitra Konveksi
                </span>

                <h1 class="text-xl sm:text-2xl lg:text-[26px] font-extrabold leading-snug text-white tracking-tight">
                    Kelola Konveksi Hijab Lebih Mudah &amp; Terstruktur
                </h1>

                <p class="text-xs sm:text-[13px] text-[#F7E6EE]/85 leading-relaxed font-normal">
                    Solusi ERP terintegrasi untuk produksi, stok kain &amp; aksesoris, pesanan, dan keuangan konveksi hijab Anda.
                </p>
            </div>

            {{-- 3. Value Badges (Ringkas & Informatif) --}}
            <div class="space-y-2 pt-1 text-xs">
                <div class="flex items-center gap-2 text-white/90">
                    <div class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center shrink-0 text-emerald-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </div>
                    <span>Data bisnis terisolasi mandiri &amp; aman</span>
                </div>
                <div class="flex items-center gap-2 text-white/90">
                    <div class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center shrink-0 text-emerald-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </div>
                    <span>Pantau alur jahit, stok bahan, dan HPP presisi</span>
                </div>
                <div class="flex items-center gap-2 text-white/90">
                    <div class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center shrink-0 text-emerald-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </div>
                    <span>Pendaftaran 100% Bebas Biaya</span>
                </div>
            </div>

            {{-- 4. Aesthetic Art on Desktop --}}
            <div class="hidden lg:block relative w-full pt-2">
                @if(file_exists(public_path('images/nayara-register-mannequin-seamless.png')))
                    <img src="{{ asset('images/nayara-register-mannequin-seamless.png') }}"
                         alt="Konveksi Hijab NAYARA"
                         class="w-full h-auto max-h-56 object-contain select-none pointer-events-none block drop-shadow-sm opacity-90">
                @endif
            </div>
        </div>

        {{-- ── KOLOM KANAN: FORMULIR PENDAFTARAN MINIMALIS & FOKUS 1 TUJUAN ── --}}
        <div class="w-full nayara-split-form bg-white rounded-2xl sm:rounded-3xl shadow-xl p-5 sm:p-7 border border-white/80 relative flex flex-col justify-between min-w-0">

            <div class="space-y-4">
                {{-- Form Header: Clean, Minimalist (Kembali Sederhana Tanpa Tombol Berlebih) --}}
                <div class="pb-3 border-b border-slate-100 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-[#5B1238] tracking-tight">
                            Formulir Pendaftaran Konveksi
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Isi data di bawah untuk membuat akun konveksi dan memulai operasional.
                        </p>
                    </div>
                    <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#FCE6EF] text-[#8E1B5C] border border-[#FFD8D8] shrink-0">
                        1 Konveksi = 1 Akun
                    </span>
                </div>

                {{-- Validation Errors Alert --}}
                @if(isset($errors) && $errors->any())
                    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-xs">
                        <div class="font-bold mb-1 flex items-center gap-1.5 text-rose-900">
                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            <span>Mohon lengkapi formulir dengan benar:</span>
                        </div>
                        <ul class="list-disc pl-5 space-y-0.5 text-rose-800">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Registration Form --}}
                <form method="POST"
                      action="{{ route('tenant.register.store') }}"
                      @submit="submitting = true"
                      class="space-y-3.5">
                    @csrf

                    {{-- ── SECTION 1: INFORMASI USAHA ── --}}
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#8E1B5C]"></span>
                            <span class="text-[11px] font-bold text-[#8E1B5C] uppercase tracking-wider">
                                1. Data Konveksi Hijab
                            </span>
                        </div>

                        {{-- Nama Konveksi / Merk --}}
                        <div class="space-y-1">
                            <label for="company_name" class="block text-xs font-semibold text-slate-700">
                                Nama Konveksi / Brand <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   name="company_name"
                                   id="company_name"
                                   value="{{ old('company_name') }}"
                                   placeholder="Contoh: Hijab Mandiri Sejahtera"
                                   required
                                   maxlength="150"
                                   class="w-full px-3.5 h-10 text-xs sm:text-[13px] rounded-xl border @error('company_name') border-rose-400 bg-rose-50/20 @else border-slate-200 bg-slate-50/40 @enderror text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C] transition-all">
                        </div>

                        {{-- Alamat Workshop --}}
                        <div class="space-y-1">
                            <label for="address" class="block text-xs font-semibold text-slate-700">
                                Alamat Workshop / Rumah Produksi <span class="text-slate-400 font-normal">(Opsional)</span>
                            </label>
                            <input type="text"
                                   name="address"
                                   id="address"
                                   value="{{ old('address') }}"
                                   placeholder="Contoh: Jl. Industri No. 42, Soreang, Bandung"
                                   maxlength="1000"
                                   class="w-full px-3.5 h-10 text-xs sm:text-[13px] rounded-xl border border-slate-200 bg-slate-50/40 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C] transition-all">
                        </div>
                    </div>

                    {{-- ── SECTION 2: AKUN PEMILIK (OWNER) ── --}}
                    <div class="space-y-2.5 pt-2 border-t border-slate-100">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#8E1B5C]"></span>
                            <span class="text-[11px] font-bold text-[#8E1B5C] uppercase tracking-wider">
                                2. Data Pemilik &amp; Akun Login
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            {{-- Nama Pemilik --}}
                            <div class="space-y-1">
                                <label for="owner_name" class="block text-xs font-semibold text-slate-700">
                                    Nama Lengkap Pemilik <span class="text-rose-500">*</span>
                                </label>
                                <input type="text"
                                       name="owner_name"
                                       id="owner_name"
                                       value="{{ old('owner_name') }}"
                                       placeholder="Contoh: Hj. Siti Fatimah"
                                       required
                                       maxlength="150"
                                       class="w-full px-3.5 h-10 text-xs sm:text-[13px] rounded-xl border @error('owner_name') border-rose-400 bg-rose-50/20 @else border-slate-200 bg-slate-50/40 @enderror text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C] transition-all">
                            </div>

                            {{-- No WhatsApp --}}
                            <div class="space-y-1">
                                <label for="phone_wa" class="block text-xs font-semibold text-slate-700">
                                    Nomor WhatsApp / HP <span class="text-rose-500">*</span>
                                </label>
                                <input type="text"
                                       name="phone_wa"
                                       id="phone_wa"
                                       value="{{ old('phone_wa') }}"
                                       placeholder="Contoh: 081234567890"
                                       required
                                       maxlength="50"
                                       class="w-full px-3.5 h-10 text-xs sm:text-[13px] rounded-xl border @error('phone_wa') border-rose-400 bg-rose-50/20 @else border-slate-200 bg-slate-50/40 @enderror text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C] transition-all">
                            </div>
                        </div>

                        {{-- Email Pemilik --}}
                        <div class="space-y-1">
                            <label for="email" class="block text-xs font-semibold text-slate-700">
                                Alamat Email (Untuk Login) <span class="text-rose-500">*</span>
                            </label>
                            <input type="email"
                                   name="email"
                                   id="email"
                                   value="{{ old('email') }}"
                                   placeholder="nama@konveksihijab.com"
                                   required
                                   maxlength="150"
                                   class="w-full px-3.5 h-10 text-xs sm:text-[13px] rounded-xl border @error('email') border-rose-400 bg-rose-50/20 @else border-slate-200 bg-slate-50/40 @enderror text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C] transition-all">
                        </div>

                        {{-- Password & Konfirmasi --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div class="space-y-1">
                                <label for="password" class="block text-xs font-semibold text-slate-700">
                                    Kata Sandi <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'"
                                           name="password"
                                           id="password"
                                           placeholder="Minimal 8 karakter"
                                           required
                                           minlength="8"
                                           class="w-full pl-3.5 pr-9 h-10 text-xs sm:text-[13px] rounded-xl border @error('password') border-rose-400 bg-rose-50/20 @else border-slate-200 bg-slate-50/40 @enderror text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C] transition-all">
                                    <button type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label for="password_confirmation" class="block text-xs font-semibold text-slate-700">
                                    Konfirmasi Sandi <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showConfirmPassword ? 'text' : 'password'"
                                           name="password_confirmation"
                                           id="password_confirmation"
                                           placeholder="Ulangi kata sandi"
                                           required
                                           minlength="8"
                                           class="w-full pl-3.5 pr-9 h-10 text-xs sm:text-[13px] rounded-xl border border-slate-200 bg-slate-50/40 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C] transition-all">
                                    <button type="button"
                                            @click="showConfirmPassword = !showConfirmPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                        <svg x-show="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <svg x-show="showConfirmPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── SECTION 3: CATATAN TAMBAHAN (OPSIONAL & RINGKAS) ── --}}
                    <div class="space-y-1 pt-1">
                        <label for="notes" class="block text-xs font-semibold text-slate-700">
                            Catatan Konveksi / Kapasitas <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="text"
                               name="notes"
                               id="notes"
                               value="{{ old('notes') }}"
                               placeholder="Contoh: 15 mesin jahit, fokus pashmina & bergo"
                               maxlength="1000"
                               class="w-full px-3.5 h-10 text-xs sm:text-[13px] rounded-xl border border-slate-200 bg-slate-50/40 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/15 focus:border-[#8E1B5C] transition-all">
                    </div>

                    {{-- Minimalist Trust Note --}}
                    <div class="flex items-center gap-1.5 pt-1 text-[11px] text-slate-500">
                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                        <span>Data konveksi Anda terisolasi privat &amp; aman di sistem multi-tenant.</span>
                    </div>

                    {{-- Primary Action Button --}}
                    <div class="pt-2">
                        <button type="submit"
                                :disabled="submitting"
                                class="w-full h-11 sm:h-11.5 px-6 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white font-bold text-xs sm:text-sm shadow-md shadow-[#8E1B5C]/25 hover:shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed cursor-pointer group">
                            <template x-if="!submitting">
                                <div class="inline-flex items-center justify-center gap-2">
                                    <span>Daftarkan Konveksi Sekarang</span>
                                    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                    </svg>
                                </div>
                            </template>
                            <template x-if="submitting">
                                <div class="inline-flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Memproses Pendaftaran...</span>
                                </div>
                            </template>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Footer Navigation Links (Standar Bersih & Tunggal) --}}
            <div class="mt-4 pt-3.5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-xs text-slate-500">
                <a href="{{ route('tenant.register.status') }}"
                   class="inline-flex items-center gap-1 font-semibold text-[#8E1B5C] hover:underline">
                    <span>Cek Status Pengajuan</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
                <div class="flex items-center gap-1 text-slate-500">
                    <span>Sudah memiliki akun aktif?</span>
                    <a href="{{ route('login') }}" class="font-bold text-[#8E1B5C] hover:underline">
                        Masuk (Login ERP)
                    </a>
                </div>
            </div>

        </div>

    </div>

    {{-- ── FLOATING CHAT WIDGET: BULAT SAAT DIAM (TIDAK MENGHALANGI) ── --}}
    <!-- Floating Circular Button (Bulat) -->
    <div class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50">
        <button type="button"
                @click="toggleChat()"
                title="Panduan Pendaftaran"
                class="relative w-12 h-12 sm:w-13 sm:h-13 rounded-full bg-[#8E1B5C] hover:bg-[#68113F] text-white shadow-xl hover:shadow-2xl flex items-center justify-center transition-all duration-200 cursor-pointer border-2 border-white/50 group focus:outline-none">
            {{-- Headset / Support Icon (Ketika Chat Tutup) --}}
            <svg x-show="!chatOpen" class="w-5 h-5 sm:w-6 sm:h-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 1.5a9 9 0 00-9 9v1.5a3 3 0 003 3h1.5a1.5 1.5 0 001.5-1.5V9a1.5 1.5 0 00-1.5-1.5H6a7.5 7.5 0 0112 0h-1.5A1.5 1.5 0 0015 9v4.5a1.5 1.5 0 001.5 1.5H18a3 3 0 003-3V10.5a9 9 0 00-9-9z"/>
            </svg>
            {{-- Close (X) Icon (Ketika Chat Terbuka) --}}
            <svg x-show="chatOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{-- Online Active Dot --}}
            <span class="absolute top-0 right-0 w-3 h-3 rounded-full bg-emerald-400 ring-2 ring-[#8E1B5C]"></span>
        </button>
    </div>

    <!-- Chat Support Window Drawer (Terbuka Saat Tombol Ditekan) -->
    <div x-show="chatOpen"
         x-cloak
         @click.outside="chatOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed bottom-18 sm:bottom-22 right-3 sm:right-6 w-[93vw] sm:w-[390px] md:w-[410px] max-h-[580px] h-[520px] bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 z-50 flex flex-col overflow-hidden">

        <!-- Chat Header: Official Support / Helpdesk -->
        <div class="px-4 py-3 bg-gradient-to-r from-[#8E1B5C] to-[#68113F] text-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="relative">
                    <div class="w-8 h-8 rounded-full bg-white/20 border border-white/30 flex items-center justify-center text-white font-bold text-xs">
                        N
                    </div>
                    <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full bg-emerald-400 ring-1 ring-white"></span>
                </div>
                <div>
                    <h3 class="text-xs font-bold leading-tight">Layanan Bantuan NAYARA</h3>
                    <p class="text-[10px] text-[#FFD8D8] leading-tight">Pusat Panduan &amp; Informasi Mitra</p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button type="button"
                        @click="resetChat()"
                        title="Mulai Ulang Percakapan"
                        class="p-1 rounded-lg hover:bg-white/10 text-white/80 hover:text-white transition-colors cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                </button>
                <button type="button"
                        @click="chatOpen = false"
                        class="p-1 rounded-lg hover:bg-white/10 text-white/80 hover:text-white transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Chat Messages Container -->
        <div id="nayara-chat-body" class="flex-1 p-3.5 space-y-3 overflow-y-auto bg-slate-50/60 text-xs">
            <template x-for="(msg, index) in messages" :key="index">
                <div>
                    <!-- CS/Helpdesk Message -->
                    <template x-if="msg.sender === 'bot'">
                        <div class="flex items-start gap-2 max-w-[90%]">
                            <div class="w-6 h-6 rounded-full bg-[#8E1B5C] text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">
                                N
                            </div>
                            <div class="space-y-1">
                                <div class="p-3 rounded-2xl rounded-tl-sm bg-white text-slate-700 shadow-xs border border-slate-100 leading-relaxed space-y-1.5"
                                     x-html="msg.text">
                                </div>
                                <span class="text-[9.5px] text-slate-400 block px-1" x-text="msg.time"></span>
                            </div>
                        </div>
                    </template>

                    <!-- User Message -->
                    <template x-if="msg.sender === 'user'">
                        <div class="flex justify-end">
                            <div class="max-w-[85%] space-y-1">
                                <div class="p-2.5 px-3 rounded-2xl rounded-tr-sm bg-[#8E1B5C] text-white shadow-xs leading-relaxed text-right"
                                     x-text="msg.text">
                                </div>
                                <span class="text-[9.5px] text-slate-400 block text-right px-1" x-text="msg.time"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Typing Indicator (Helpdesk phrasing, no AI) -->
            <div x-show="isTyping" x-cloak class="flex items-center gap-1.5 p-2 px-3 rounded-xl bg-white border border-slate-100 w-fit text-slate-400 text-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] animate-bounce"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] animate-bounce [animation-delay:0.4s]"></span>
                <span class="text-[10px] text-slate-400 ml-1">Menyiapkan jawaban...</span>
            </div>
        </div>

        <!-- Quick Topic Suggestion Chips -->
        <div class="p-2.5 bg-white border-t border-slate-100 shrink-0">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5 px-1">Topik Informasi Cepat:</span>
            <div class="flex flex-wrap gap-1.5 max-h-20 overflow-y-auto">
                <template x-for="chip in quickChips" :key="chip.key">
                    <button type="button"
                            @click="askTopic(chip.key, chip.label)"
                            class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-[#FCE6EF] hover:text-[#8E1B5C] text-slate-600 text-[11px] font-medium transition-all text-left border border-slate-200/60 cursor-pointer">
                        <span x-text="chip.label"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Chat Input Footer -->
        <form @submit.prevent="sendUserMessage()" class="p-2.5 bg-white border-t border-slate-100 flex items-center gap-2 shrink-0">
            <input type="text"
                   x-model="chatInput"
                   placeholder="Tuliskan pertanyaan Anda..."
                   class="flex-1 h-9 px-3 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] transition-all">
            <button type="submit"
                    :disabled="!chatInput.trim() || isTyping"
                    class="h-9 px-3 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] disabled:opacity-40 text-white flex items-center justify-center transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                </svg>
            </button>
        </form>

    </div>

    <!-- Alpine.js Page & Support Chat Logic -->
    <script>
        function registrationPage() {
            return {
                submitting: false,
                showPassword: false,
                showConfirmPassword: false,

                // Chat State
                chatOpen: false,
                chatInput: '',
                isTyping: false,
                messages: [
                    {
                        sender: 'bot',
                        time: 'Baru saja',
                        text: 'Halo! Selamat datang di layanan pendaftaran NAYARA ERP. Silakan pilih topik informasi di bawah ini atau tuliskan pertanyaan Anda jika membutuhkan panduan.'
                    }
                ],

                quickChips: [
                    { label: '📋 Alur Pendaftaran', key: 'alur' },
                    { label: '⏱️ Waktu Verifikasi', key: 'durasi' },
                    { label: '📝 Syarat Mitra', key: 'syarat' },
                    { label: '🔒 Keamanan Data', key: 'keamanan' },
                    { label: '💰 Biaya Pendaftaran', key: 'biaya' },
                    { label: '🔎 Cek Status Pengajuan', key: 'status' }
                ],

                knowledgeBase: {
                    alur: `<strong>Tata Cara &amp; Alur Pendaftaran Mitra Konveksi:</strong><br>
                           1. <strong>Pengisian Formulir</strong>: Masukkan nama konveksi, data pemilik, nomor WhatsApp, email, dan kata sandi pada formulir di halaman ini.<br>
                           2. <strong>Verifikasi Berkas</strong>: Tim operasional NAYARA akan memvalidasi data pendaftaran konveksi Anda (maksimal 1x24 jam kerja).<br>
                           3. <strong>Aktivasi Akun</strong>: Setelah disetujui, akun Owner Anda langsung aktif dan dapat digunakan untuk masuk ke sistem.`,

                    durasi: `<strong>Estimasi Waktu Verifikasi:</strong><br>
                             Proses verifikasi oleh tim kami umumnya selesai dalam <strong>1 hingga 24 jam kerja</strong>.<br>
                             Pemberitahuan hasil verifikasi akan dikirimkan ke email pemilik dan statusnya juga dapat dicek mandiri melalui menu <em>Cek Status Pengajuan</em>.`,

                    syarat: `<strong>Syarat Pendaftaran Mitra:</strong><br>
                             • Memiliki usaha produksi atau konveksi hijab (rumahan, UMKM, atau garmen).<br>
                             • Memiliki nomor WhatsApp aktif untuk kebutuhan koordinasi dan verifikasi.<br>
                             • Memiliki alamat email aktif yang belum pernah didaftarkan sebelumnya.<br>
                             • Mengisi data pemilik yang valid dan sesuai.`,

                    keamanan: `<strong>Privasi &amp; Keamanan Data Multi-Tenant:</strong><br>
                               • Data usaha dan transaksi Anda dijamin <strong>100% terisolasi secara privat</strong>.<br>
                               • Konveksi lain maupun pengguna di luar outlet Anda tidak dapat mengakses data stok kain, HPP, resep BOM hijab, atau daftar pesanan pelanggan Anda.<br>
                               • Keamanan data didukung standar enkripsi kata sandi dan pembatasan wewenang berjenjang.`,

                    biaya: `<strong>Biaya Pendaftaran:</strong><br>
                            Pendaftaran mandiri konveksi hijab di NAYARA ERP adalah <strong>100% Bebas Biaya (GRATIS)</strong> untuk paket awal mitra konveksi. Tidak ada pungutan biaya pendaftaran.`,

                    status: `<strong>Cara Cek Status Pengajuan:</strong><br>
                             Setelah formulir berhasil dikirim, klik tombol <strong>"Cek Status Pengajuan"</strong> di bagian bawah formulir pendaftaran. Masukkan ID pendaftaran atau email Anda untuk melihat status terkini pengajuan Anda.`
                },

                openChat() {
                    this.chatOpen = true;
                    this.scrollToBottom();
                },

                toggleChat() {
                    this.chatOpen = !this.chatOpen;
                    if (this.chatOpen) {
                        this.scrollToBottom();
                    }
                },

                resetChat() {
                    this.messages = [
                        {
                            sender: 'bot',
                            time: 'Baru saja',
                            text: 'Percakapan telah diperbarui. Silakan pilih topik informasi yang Anda butuhkan di bawah ini.'
                        }
                    ];
                },

                askTopic(key, label) {
                    this.messages.push({
                        sender: 'user',
                        time: this.getTimeString(),
                        text: label
                    });
                    this.scrollToBottom();

                    this.isTyping = true;
                    setTimeout(() => {
                        this.isTyping = false;
                        const answer = this.knowledgeBase[key] || 'Informasi untuk topik ini belum tersedia.';
                        this.messages.push({
                            sender: 'bot',
                            time: this.getTimeString(),
                            text: answer
                        });
                        this.scrollToBottom();
                    }, 350);
                },

                sendUserMessage() {
                    const query = this.chatInput.trim();
                    if (!query || this.isTyping) return;

                    this.messages.push({
                        sender: 'user',
                        time: this.getTimeString(),
                        text: query
                    });
                    this.chatInput = '';
                    this.scrollToBottom();

                    this.isTyping = true;

                    setTimeout(() => {
                        this.isTyping = false;
                        const lower = query.toLowerCase();
                        let reply = '';

                        if (lower.includes('alur') || lower.includes('cara') || lower.includes('langkah') || lower.includes('tata cara') || lower.includes('daftar')) {
                            reply = this.knowledgeBase.alur;
                        } else if (lower.includes('berapa lama') || lower.includes('lama') || lower.includes('durasi') || lower.includes('waktu') || lower.includes('kapan')) {
                            reply = this.knowledgeBase.durasi;
                        } else if (lower.includes('syarat') || lower.includes('ketentuan') || lower.includes('dokumen')) {
                            reply = this.knowledgeBase.syarat;
                        } else if (lower.includes('aman') || lower.includes('privasi') || lower.includes('keamanan') || lower.includes('bocor') || lower.includes('data')) {
                            reply = this.knowledgeBase.keamanan;
                        } else if (lower.includes('biaya') || lower.includes('gratis') || lower.includes('bayar') || lower.includes('harga') || lower.includes('tarif')) {
                            reply = this.knowledgeBase.biaya;
                        } else if (lower.includes('status') || lower.includes('cek') || lower.includes('pantau')) {
                            reply = this.knowledgeBase.status;
                        } else {
                            reply = `Terima kasih atas pertanyaan Anda. Untuk informasi cepat seputar tata cara pendaftaran, silakan pilih salah satu topik bantuan di atas seperti <strong>Alur Pendaftaran</strong>, <strong>Syarat Mitra</strong>, atau <strong>Keamanan Data</strong>. Tim kami siap membantu proses pendaftaran konveksi Anda.`;
                        }

                        this.messages.push({
                            sender: 'bot',
                            time: this.getTimeString(),
                            text: reply
                        });
                        this.scrollToBottom();
                    }, 400);
                },

                getTimeString() {
                    const now = new Date();
                    return now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
                },

                scrollToBottom() {
                    setTimeout(() => {
                        const el = document.getElementById('nayara-chat-body');
                        if (el) {
                            el.scrollTop = el.scrollHeight;
                        }
                    }, 50);
                }
            }
        }
    </script>

</body>
</html>
