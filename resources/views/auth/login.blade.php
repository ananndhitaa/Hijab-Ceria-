<x-guest-layout title="Masuk ke Akun Anda - NAYARA">
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

    <div class="min-h-screen flex items-center justify-center py-4 px-3 sm:p-6 lg:p-8 bg-[#FFF9FB]">
        <div class="w-full max-w-5xl bg-white rounded-2xl sm:rounded-3xl shadow-xl overflow-hidden border border-[#E1CFD9] flex flex-col nayara-auth-split p-2.5 sm:p-3.5 md:p-4 gap-3 sm:gap-4">

            {{-- Left Brand Panel --}}
            <div class="w-full nayara-auth-brand rounded-xl sm:rounded-2xl p-5 sm:p-6 md:p-8 lg:p-11 text-white flex flex-col justify-between relative overflow-hidden shadow-md shrink-0"
                 style="background: linear-gradient(166deg, #691b3d 0%, #4a132e 49%, #260b20 100%);">
                <div class="relative z-10 space-y-4 sm:space-y-5 md:space-y-6">
                    {{-- Logo & Brand: NAYARA GROUP Platform Portal --}}
                    <div class="flex items-center gap-2.5 sm:gap-3.5">
                        <img src="{{ asset('images/nayara-logo.jpg') }}" alt="NAYARA GROUP" class="w-9 h-9 sm:w-10 sm:h-10 md:w-12 md:h-12 rounded-xl object-contain shadow-xs shrink-0 border border-white/20">
                        <div class="space-y-0.5">
                            <h2 class="font-bold text-sm sm:text-base md:text-lg tracking-wide text-white leading-none">NAYARA GROUP</h2>
                            <p class="text-[8.5px] sm:text-[9.5px] md:text-[10px] text-[#F7E6EE]/80 tracking-widest font-semibold uppercase leading-none">SAAS PLATFORM &amp; MRP ECOSYSTEM</p>
                        </div>
                    </div>

                    {{-- Title & Copy --}}
                    <div class="pt-1 sm:pt-2 md:pt-4 space-y-1.5 sm:space-y-2 md:space-y-3">
                        <h1 class="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold leading-tight text-white">
                            Sistem Perencanaan &amp; Hasil Produksi Hijab
                        </h1>
                        <p class="text-[11px] sm:text-xs md:text-sm text-[#F7E6EE]/90 leading-relaxed font-normal">
                            Kelola pesanan, kebutuhan bahan, produksi, QC, dan operasional bisnis dalam satu sistem terintegrasi.
                        </p>
                    </div>

                    {{-- Key Value Points --}}
                    <div class="nayara-auth-brand-details pt-1 sm:pt-2 space-y-2 sm:space-y-2.5">
                        <div class="flex items-center gap-2 sm:gap-2.5 text-[11px] sm:text-xs text-[#F7E6EE]/90">
                            <div class="w-4.5 h-4.5 sm:w-5 sm:h-5 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                                <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <span>Perencanaan kebutuhan bahan baku</span>
                        </div>
                        <div class="flex items-center gap-2 sm:gap-2.5 text-[11px] sm:text-xs text-[#F7E6EE]/90">
                            <div class="w-4.5 h-4.5 sm:w-5 sm:h-5 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                                <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <span>Monitoring alur produksi &amp; QC</span>
                        </div>
                        <div class="flex items-center gap-2 sm:gap-2.5 text-[11px] sm:text-xs text-[#F7E6EE]/90">
                            <div class="w-4.5 h-4.5 sm:w-5 sm:h-5 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                                <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <span>Kontrol pesanan &amp; penagihan</span>
                        </div>
                    </div>
                </div>

                {{-- Left Footer --}}
                <div class="nayara-auth-brand-details mt-4 sm:mt-6 md:mt-8 pt-3 sm:pt-4 border-t border-white/10 text-[10.5px] sm:text-xs text-[#F7E6EE]/70 font-normal relative z-10">
                    <p>&copy; {{ date('Y') }} NAYARA. Sistem Produksi &amp; MRP.</p>
                </div>
            </div>

            {{-- Right Login Form Panel --}}
            <div class="w-full nayara-auth-form p-5 sm:p-6 md:p-8 lg:p-12 flex flex-col justify-center bg-white rounded-xl sm:rounded-2xl">
                <div class="w-full max-w-[420px] mx-auto space-y-4 sm:space-y-5 md:space-y-6">

                    {{-- Header --}}
                    <div class="space-y-1">
                        <h2 class="text-xl sm:text-2xl font-bold text-[#172B4D]">Selamat Datang Kembali</h2>
                        <p class="text-xs sm:text-sm text-[#64748B]">Masuk untuk melanjutkan ke sistem operasional.</p>
                    </div>

                    {{-- Auth Error Alert --}}
                    @if($errors->any())
                        <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 shadow-xs">
                            <div class="flex items-center gap-2 font-semibold text-red-800 mb-1">
                                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Gagal Masuk</span>
                            </div>
                            <ul class="list-disc list-inside space-y-0.5 ml-6">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Form --}}
                    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                        @csrf

                        <div class="space-y-1.5">
                            <label for="email" class="block text-xs font-semibold text-[#172B4D]">Email</label>
                            <input id="email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email', app()->environment('local') ? 'admin@example.com' : '') }}"
                                   placeholder="nama@perusahaan.com"
                                   required
                                   autofocus
                                   class="w-full h-11 px-3.5 text-xs sm:text-sm border @error('email') border-red-500 @else border-[#E1CFD9] @enderror rounded-lg bg-[#FFF9FB] text-[#172B4D] placeholder:text-[#64748B]/50 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                            @error('email')
                                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="password" class="block text-xs font-semibold text-[#172B4D]">Password</label>
                            <input id="password"
                                   type="password"
                                   name="password"
                                   value="{{ app()->environment('local') ? 'password' : '' }}"
                                   placeholder="••••••••"
                                   required
                                   class="w-full h-11 px-3.5 text-xs sm:text-sm border @error('password') border-red-500 @else border-[#E1CFD9] @enderror rounded-lg bg-[#FFF9FB] text-[#172B4D] placeholder:text-[#64748B]/50 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                            @error('password')
                                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <label class="inline-flex items-center gap-2 text-[#64748B] cursor-pointer select-none">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-[#E1CFD9] text-[#68113F] focus:ring-[#68113F]">
                                <span>Ingat saya</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="text-[#68113F] hover:text-[#520C31] font-semibold transition-colors">
                                Lupa Password?
                            </a>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                    class="w-full h-11 bg-[#68113F] hover:bg-[#520C31] text-white font-semibold text-xs sm:text-sm rounded-lg shadow-xs transition-all duration-200 ease-out cursor-pointer flex items-center justify-center">
                                Masuk ke Sistem
                            </button>
                        </div>

                        <div class="pt-3 text-center text-xs text-[#64748B] border-t border-[#E1CFD9]/60">
                            Belum memiliki outlet konveksi?
                            <a href="{{ route('tenant.register.create') }}" class="font-bold text-[#68113F] hover:text-[#520C31] hover:underline">
                                Daftar Gratis
                            </a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</x-guest-layout>
