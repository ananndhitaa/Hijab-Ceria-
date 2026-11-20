<x-app-layout pageTitle="Saldo Awal Kas Operasional" breadcrumb="Executive Monitoring > Saldo Awal Kas">
    <div class="w-full max-w-none mx-0 space-y-5 sm:space-y-6">

        {{-- ── 1. Page Header (Full Width Compact Professional) ───────────── --}}
        <x-feature-header
            title="Saldo Awal Kas Operasional"
            subtitle="Penetapan baseline likuiditas kas/bank awal satu kali untuk perusahaan konveksi ini."
            icon="finance"
            badge="EXECUTIVE MONITORING"
        >
            <x-slot:actions>
                <a href="{{ route('owner.dashboard') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        @if(session('success'))
            <div class="w-full bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center gap-3 text-emerald-800 text-xs font-medium shadow-xs">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($current)
            {{-- ── RECORD ALREADY EXISTS: FULL-WIDTH VIEW-ONLY PERMANENT SUMMARY ── --}}
            <div class="w-full bg-white border border-[#FFD8D8] rounded-2xl p-6 sm:p-8 shadow-card space-y-6">
                {{-- Status Banner & Timestamp --}}
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-[#FFD8D8] pb-5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-2xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                            TERDAFTAR &amp; PERMANEN
                        </span>
                        <span class="text-sm font-semibold text-[#172B4D]">Baseline Kas Konveksi Aktif</span>
                    </div>
                    <span class="text-xs text-[#64748B] flex items-center gap-1.5 font-medium">
                        <svg class="w-4 h-4 text-[#64748B]/70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Ditetapkan pada: <strong>{{ $current->created_at ? $current->created_at->format('d M Y, H:i') : '-' }} WIB</strong></span>
                    </span>
                </div>

                {{-- Full-Width Metrics Grid (3 Balanced Columns on Desktop) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    {{-- 1. Nominal Kas --}}
                    <div class="bg-[#FFFCFB] p-5 sm:p-6 rounded-xl border border-[#FFD8D8] shadow-xs flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-[#64748B] block uppercase tracking-wider">Nominal Saldo Awal Kas</span>
                            <span class="text-2xl sm:text-3xl font-extrabold font-mono text-[#8E1B5C] mt-2 block tracking-tight">
                                Rp {{ number_format((float) $current->amount, 0, ',', '.') }}
                            </span>
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-3 pt-2 border-t border-[#FFD8D8]/60">
                            Likuiditas kas fisik &amp; rekening bank operasional
                        </p>
                    </div>

                    {{-- 2. Tanggal Efektif --}}
                    <div class="bg-[#FFFCFB] p-5 sm:p-6 rounded-xl border border-[#FFD8D8] shadow-xs flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-[#64748B] block uppercase tracking-wider">Tanggal Efektif Saldo</span>
                            <span class="text-lg sm:text-xl font-bold text-[#172B4D] mt-2 block">
                                {{ $current->opening_date ? $current->opening_date->format('d F Y') : '-' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-3 pt-2 border-t border-[#FFD8D8]/60">
                            Titik awal perhitungan arus kas operasional
                        </p>
                    </div>

                    {{-- 3. Otoritas Penetapan --}}
                    <div class="bg-[#FFFCFB] p-5 sm:p-6 rounded-xl border border-[#FFD8D8] shadow-xs flex flex-col justify-between md:col-span-2 lg:col-span-1">
                        <div>
                            <span class="text-[11px] font-bold text-[#64748B] block uppercase tracking-wider">Ditetapkan Oleh</span>
                            <span class="text-sm font-bold text-[#172B4D] mt-2 block truncate">
                                {{ $current->createdByUser?->name ?? 'Owner Toko' }}
                            </span>
                            <span class="text-xs text-[#64748B] block truncate mt-0.5">
                                {{ $current->createdByUser?->email ?? '-' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-3 pt-2 border-t border-[#FFD8D8]/60">
                            Otoritas Pemilik Konveksi (Owner)
                        </p>
                    </div>
                </div>

                {{-- Catatan / Keterangan --}}
                @if($current->notes)
                    <div class="bg-[#FFFCFB] p-5 rounded-xl border border-[#FFD8D8] shadow-xs space-y-1.5">
                        <span class="text-[11px] font-bold text-[#64748B] block uppercase tracking-wider">Catatan / Keterangan Sumber Dana</span>
                        <p class="text-xs sm:text-sm text-[#172B4D] whitespace-pre-line leading-relaxed">{{ $current->notes }}</p>
                    </div>
                @endif

                {{-- Policy Notice Banner --}}
                <div class="bg-amber-50/80 border border-amber-200/90 rounded-xl p-4 sm:p-5 text-xs text-amber-900 flex items-start gap-3.5 shadow-2xs">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="space-y-1">
                        <p class="font-bold text-amber-950">Kebijakan Integritas &amp; Audit Keuangan Multi-Tenant:</p>
                        <p class="leading-relaxed">
                            Saldo awal kas bersifat permanen dan tidak dapat diubah atau di-reset melalui alur operasional. Seluruh penambahan modal, penerimaan piutang, dan pengeluaran beban berikutnya wajib dicatat melalui modul transaksi keuangan yang sah agar pembukuan laba-rugi serta cash flow tetap akurat dan terverifikasi.
                        </p>
                    </div>
                </div>
            </div>

        @elseif($hasFinancialHistory)
            {{-- ── FINANCIAL HISTORY EXISTS: FULL-WIDTH FAIL-CLOSED WARNING ── --}}
            <div class="w-full bg-white border border-red-200 rounded-2xl p-6 sm:p-8 shadow-card space-y-4">
                <div class="flex items-center gap-3 text-red-600">
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h2 class="text-base sm:text-lg font-bold text-red-700">Penetapan Saldo Awal Terkunci</h2>
                </div>
                <p class="text-xs sm:text-sm text-[#172B4D] leading-relaxed">
                    Outlet konveksi ini telah memiliki riwayat transaksi keuangan aktif (penerimaan kas, beban usaha, atau komitmen pembelian).
                    Sesuai prinsip kehati-hatian akuntansi, penetapan saldo awal kas mandiri tidak dapat dilakukan agar tidak mengacaukan pembukuan historis.
                </p>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-xs text-red-800">
                    Silakan hubungi tim audit / administrator sistem untuk prosedur rekonsiliasi migrasi saldo awal historis secara resmi.
                </div>
            </div>

        @else
            {{-- ── INITIAL ONBOARDING FORM (FULL WIDTH ONE-TIME SETUP) ── --}}
            <div class="w-full bg-[#FFFCFB] border border-[#FFD8D8] rounded-2xl p-5 sm:p-6 shadow-xs text-xs text-[#64748B] flex items-start gap-3.5">
                <svg class="w-5 h-5 text-[#8E1B5C] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="space-y-1.5">
                    <span class="font-bold text-[#172B4D] text-sm block">Ketentuan Mutlak Saldo Awal Kas (One-Time Setup):</span>
                    <p class="leading-relaxed">
                        1. Saldo awal kas hanya mewakili <strong>uang tunai atau saldo rekening bank operasional yang benar-benar likuid dan siap dibelanjakan</strong>.<br>
                        2. Mesin, aset fisik, dan persediaan kain awal <strong>TIDAK boleh</strong> dimasukkan ke dalam saldo awal kas operasional.<br>
                        3. Penetapan ini hanya dapat dilakukan <strong>SATU KALI seumur hidup</strong> untuk outlet ini. Setelah disimpan, angka ini terkunci permanen.
                    </p>
                </div>
            </div>

            <div class="w-full bg-white border border-[#FFD8D8] rounded-2xl p-6 sm:p-8 shadow-card">
                <form method="POST" action="{{ route('owner.opening-cash.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {{-- Nominal Saldo Awal --}}
                        <div class="space-y-1.5">
                            <label for="amount" class="block text-xs font-semibold text-[#172B4D]">
                                Nominal Saldo Awal Kas (Rp) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-2.5 text-xs font-bold text-[#64748B]">Rp</span>
                                <input type="number"
                                       id="amount"
                                       name="amount"
                                       step="0.01"
                                       min="0"
                                       value="{{ old('amount') }}"
                                       placeholder="Contoh: 50000000"
                                       class="w-full h-10 pl-10 pr-3.5 text-xs sm:text-sm font-mono font-semibold border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('amount') border-red-500 @enderror"
                                       required>
                            </div>
                            @error('amount')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-[11px] text-[#64748B]">Masukkan angka 0 jika operasional outlet dimulai dari saldo kas Rp0.</p>
                        </div>

                        {{-- Tanggal Posisi Kas --}}
                        <div class="space-y-1.5">
                            <label for="opening_date" class="block text-xs font-semibold text-[#172B4D]">
                                Tanggal Posisi Kas <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="opening_date"
                                   name="opening_date"
                                   max="{{ date('Y-m-d') }}"
                                   value="{{ old('opening_date', date('Y-m-d')) }}"
                                   class="w-full h-10 px-3.5 text-xs sm:text-sm border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('opening_date') border-red-500 @enderror"
                                   required>
                            @error('opening_date')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-[11px] text-[#64748B]">Tanggal posisi kas tidak boleh melebihi hari ini.</p>
                        </div>

                        {{-- Catatan / Keterangan (Span 2 Columns) --}}
                        <div class="space-y-1.5 md:col-span-2">
                            <label for="notes" class="block text-xs font-semibold text-[#172B4D]">
                                Catatan / Keterangan Sumber Dana <span class="text-[#64748B] text-[11px] font-normal">(Opsional)</span>
                            </label>
                            <textarea id="notes"
                                      name="notes"
                                      rows="3"
                                      placeholder="Contoh: Saldo pembukaan rekening BCA operasional NAYARA per awal bulan"
                                      class="w-full p-3.5 text-xs sm:text-sm border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Konfirmasi Owner (Span 2 Columns) --}}
                        <div class="pt-2 md:col-span-2">
                            <label class="flex items-start gap-3 cursor-pointer p-4 bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl">
                                <input type="checkbox"
                                       name="verified_by_owner"
                                       value="1"
                                       {{ old('verified_by_owner') ? 'checked' : '' }}
                                       class="mt-0.5 rounded border-[#FFD8D8] text-[#8E1B5C] focus:ring-[#8E1B5C] w-4 h-4 shrink-0"
                                       required>
                                <span class="text-xs text-[#172B4D] leading-relaxed">
                                    Saya selaku <strong>Owner</strong> menyatakan dan mengonfirmasi bahwa nominal kas di atas telah diverifikasi secara akurat sesuai saldo fisik atau rekening bank operasional yang nyata, serta memahami bahwa saldo awal ini <strong>tidak dapat diubah atau di-reset kembali</strong>.
                                </span>
                            </label>
                            @error('verified_by_owner')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Action Footer --}}
                    <div class="flex items-center justify-end gap-3 pt-5 border-t border-[#FFD8D8]">
                        <a href="{{ route('owner.dashboard') }}"
                           class="inline-flex items-center justify-center h-10 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                            Batal
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-1.5 h-10 px-6 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Tetapkan Saldo Awal Permanen</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif

    </div>
</x-app-layout>
