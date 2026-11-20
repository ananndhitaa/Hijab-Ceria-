<x-guest-layout title="Lupa Password - NAYARA">
    <style>
        /* Split-screen responsive rule: Keep side-by-side (kiri-kanan) on split screens >= 560px */
        @media (min-width: 560px) {
            .nayara-auth-split {
                flex-direction: row !important;
                align-items: stretch !important;
            }
            .nayara-auth-brand {
                width: 44% !important;
                min-width: 230px !important;
                max-width: 440px !important;
                flex-shrink: 0 !important;
            }
            .nayara-auth-form {
                flex: 1 1 0% !important;
                min-width: 0 !important;
            }
        }

        /* Mobile optimization (< 560px): Streamline top hero on mobile so the form is immediately accessible */
        @media (max-width: 559px) {
            .nayara-auth-brand-details {
                display: none !important;
            }
        }
    </style>

    <div class="min-h-screen flex items-center justify-center py-4 px-3 sm:p-6 lg:p-8 bg-[#FFFCFB]">
        <div class="w-full max-w-5xl bg-white rounded-2xl sm:rounded-3xl shadow-card overflow-hidden border border-[#FFD8D8] flex flex-col nayara-auth-split p-2.5 sm:p-3.5 md:p-4 gap-3 sm:gap-4">

            {{-- Left Panel: Brand & Internal Reset Guidance --}}
            <div class="w-full nayara-auth-brand bg-gradient-to-br from-[#8E1B5C] via-[#A6266E] to-[#6B1242] p-5 sm:p-6 md:p-8 lg:p-11 text-white flex flex-col justify-between relative overflow-hidden rounded-xl sm:rounded-2xl shrink-0">
                <div class="relative z-10 space-y-4 sm:space-y-5 md:space-y-6">
                    {{-- Logo & Brand: NAYARA GROUP Platform Portal --}}
                    <div class="flex items-center gap-2.5 sm:gap-3.5">
                        <img src="{{ asset('images/nayara-logo.jpg') }}" alt="NAYARA GROUP" class="w-9 h-9 sm:w-10 sm:h-10 md:w-11 md:h-11 rounded-xl object-contain shadow-xs shrink-0 border border-white/20">
                        <div class="space-y-0.5">
                            <h2 class="font-bold text-sm sm:text-base tracking-wide text-white leading-none">NAYARA GROUP</h2>
                            <p class="text-[8.5px] sm:text-[9px] text-[#FFD8D8]/80 tracking-widest font-semibold uppercase leading-none">SAAS PLATFORM &amp; MRP ECOSYSTEM</p>
                        </div>
                    </div>

                    {{-- Context Explanation --}}
                    <div class="pt-1 space-y-1.5 sm:space-y-2">
                        <h2 class="text-lg sm:text-xl md:text-2xl font-bold leading-snug text-white">
                            Reset Akses Pengguna
                        </h2>
                        <p class="text-[11px] sm:text-xs md:text-sm text-[#FFD8D8]/90 leading-relaxed font-normal">
                            Sebagai sistem ERP internal, perubahan kredensial akun diverifikasi dan dikelola langsung oleh Administrator demi menjaga keamanan data operasional.
                        </p>
                    </div>

                    {{-- 3-Step Clear Flow --}}
                    <div class="nayara-auth-brand-details pt-1 space-y-2.5 sm:space-y-3">
                        <div class="flex items-start gap-2.5 text-xs text-[#FFD8D8]/90">
                            <span class="w-5 h-5 rounded-md bg-white/10 text-[#FFD8D8] font-semibold text-[11px] flex items-center justify-center shrink-0 mt-0.5">1</span>
                            <div>
                                <p class="font-medium text-white text-xs">Kirim Pengajuan</p>
                                <p class="text-[10.5px] sm:text-[11px] text-[#FFD8D8]/70 mt-0.5 leading-normal">Kirimkan alamat email akun yang terdaftar pada sistem.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 text-xs text-[#FFD8D8]/90">
                            <span class="w-5 h-5 rounded-md bg-white/10 text-[#FFD8D8] font-semibold text-[11px] flex items-center justify-center shrink-0 mt-0.5">2</span>
                            <div>
                                <p class="font-medium text-white text-xs">Verifikasi Admin</p>
                                <p class="text-[10.5px] sm:text-[11px] text-[#FFD8D8]/70 mt-0.5 leading-normal">Administrator meninjau dan memproses permintaan Anda.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 text-xs text-[#FFD8D8]/90">
                            <span class="w-5 h-5 rounded-md bg-white/10 text-[#FFD8D8] font-semibold text-[11px] flex items-center justify-center shrink-0 mt-0.5">3</span>
                            <div>
                                <p class="font-medium text-white text-xs">Pemberian Password Baru</p>
                                <p class="text-[10.5px] sm:text-[11px] text-[#FFD8D8]/70 mt-0.5 leading-normal">Hubungi Admin untuk menerima kredensial baru.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Left Footer --}}
                <div class="nayara-auth-brand-details mt-4 sm:mt-6 md:mt-8 pt-3 sm:pt-4 border-t border-white/10 text-[10.5px] sm:text-[11px] text-[#FFD8D8]/70 font-normal relative z-10">
                    <p>&copy; {{ date('Y') }} NAYARA. Sistem Produksi &amp; MRP.</p>
                </div>
            </div>

            {{-- Right Form Panel --}}
            <div class="w-full nayara-auth-form p-5 sm:p-6 md:p-8 lg:p-12 flex flex-col justify-center bg-white rounded-xl sm:rounded-2xl">
                <div class="w-full max-w-[400px] mx-auto">

                    {{-- Back Link --}}
                    <div class="mb-5">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-[#64748B] hover:text-[#8E1B5C] transition-colors group">
                            <svg class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span>Kembali ke halaman Masuk</span>
                        </a>
                    </div>

                    {{-- Header --}}
                    <div class="space-y-1 mb-6">
                        <h1 class="text-2xl font-bold text-[#172B4D] tracking-tight">Lupa Password</h1>
                        <p class="text-xs sm:text-sm text-[#64748B] leading-relaxed">
                            Masukkan email akun Anda untuk mengirim permintaan reset password ke Administrator.
                        </p>
                    </div>

                    {{-- Status / Success Alert --}}
                    @if(session('status'))
                        <div class="mb-5 p-3.5 bg-[#F0FDF4] border border-[#BBF7D0] rounded-xl text-xs text-[#166534] flex items-start gap-2.5 shadow-xs">
                            <svg class="w-4 h-4 text-[#16A34A] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="space-y-0.5 leading-relaxed">
                                <p class="font-semibold text-[#14532D]">Permintaan Terkirim</p>
                                <p class="text-[#166534]">{{ session('status') }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Validation Error Alert --}}
                    @if($errors->any())
                        <div class="mb-5 p-3.5 bg-[#FEF2F2] border border-[#FECACA] rounded-xl text-xs text-[#991B1B] flex items-start gap-2.5 shadow-xs">
                            <svg class="w-4 h-4 text-[#DC2626] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="space-y-0.5 leading-relaxed">
                                @foreach($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Form --}}
                    <form method="POST" action="{{ route('password.request.submit') }}" class="space-y-4">
                        @csrf

                        <div class="space-y-1.5">
                            <label for="email" class="block text-xs font-semibold text-[#172B4D]">Email Akun</label>
                            <input id="email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="nama@perusahaan.com"
                                   required
                                   autofocus
                                   class="w-full h-11 px-3.5 text-xs sm:text-sm border @error('email') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/50 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                            @error('email')
                                <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Subtle Info Box --}}
                        <div class="p-3 bg-[#FFD8D8] border border-[#FFD8D8]/80 rounded-lg text-xs text-[#64748B] flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-[#8E1B5C] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="leading-relaxed text-[11px] sm:text-xs">
                                Permintaan perubahan password akan ditinjau oleh Administrator. Hubungi Admin untuk mendapatkan password baru setelah permintaan diproses.
                            </p>
                        </div>

                        <div class="pt-1">
                            <button type="submit"
                                    class="w-full h-11 bg-[#8E1B5C] hover:bg-[#A6266E] active:bg-[#6B1242] text-white font-semibold text-xs sm:text-sm rounded-lg shadow-xs transition-colors duration-150 cursor-pointer flex items-center justify-center">
                                Kirim Permintaan Reset
                            </button>
                        </div>
                    </form>

                    {{-- Footer Help Link --}}
                    <div class="mt-6 pt-5 border-t border-[#FFD8D8] text-center">
                        <p class="text-xs text-[#64748B]">
                            Sudah ingat kata sandi akun?
                            <a href="{{ route('login') }}" class="text-[#8E1B5C] font-semibold hover:text-[#A6266E] transition-colors ml-1">
                                Masuk sekarang
                            </a>
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-guest-layout>
