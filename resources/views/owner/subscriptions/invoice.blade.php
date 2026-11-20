@extends('layouts.app')

@section('content')
<div class="w-full max-w-none mx-0 space-y-6">

    <x-feature-header
        title="Invoice Tagihan Platform"
        :subtitle="'Nomor: ' . $invoice->invoice_no"
        icon="finance"
        badge="LANGGANAN & MONETISASI"
    >
        <x-slot:actions>
            <a href="{{ route('owner.subscriptions.index') }}" class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl border border-[#E8D4DD] bg-white text-xs sm:text-sm font-semibold text-[#520C31] hover:bg-[#FCEAEF] hover:border-[#8E1B5C] transition-all shadow-2xs hover:shadow-xs">
                <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                <span>Kembali ke Daftar Langganan</span>
            </a>
        </x-slot:actions>
    </x-feature-header>

    {{-- Invoice Main Card --}}
    <div class="card p-6 sm:p-8 space-y-6 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs">
        
        {{-- Header & Status --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-[#F2E6ED] pb-6 gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-[#8E1B5C] bg-[#FCEAEF] px-2.5 py-0.5 rounded-md border border-[#F0D5DF]">
                        INVOICE PEMBAYARAN PLATFORM NAYARA
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#1E1B1D] font-mono tracking-tight mt-1">{{ $invoice->invoice_no }}</h1>
                <p class="text-xs text-[#7A7176] flex items-center gap-1.5 pt-0.5">
                    <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Diterbitkan: <strong>{{ $invoice->created_at->timezone(config('app.display_timezone', 'Asia/Jakarta'))->translatedFormat('d M Y H:i') }} WIB</strong></span>
                </p>
            </div>
            <div>
                @if($invoice->status === 'VERIFIED')
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>LUNAS &amp; TERVERIFIKASI</span>
                </span>
                @elseif($invoice->status === 'PAYMENT_SUBMITTED')
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-extrabold bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs">
                    <svg class="w-4 h-4 text-amber-600 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21"/></svg>
                    <span>MENUNGGU VERIFIKASI SUPER ADMIN</span>
                </span>
                @elseif($invoice->status === 'UNPAID')
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-extrabold bg-rose-50 text-rose-700 border border-rose-300 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    <span>MENUNGGU PEMBAYARAN</span>
                </span>
                @elseif($invoice->status === 'EXPIRED')
                <span class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-extrabold bg-slate-100 text-slate-700 border border-slate-300">
                    <span>KEDALUWARSA</span>
                </span>
                @else
                <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-extrabold bg-slate-100 text-slate-700 border border-slate-300">
                    {{ $invoice->status }}
                </span>
                @endif
            </div>
        </div>

        {{-- Details Breakdown Box (Refined & Clean) --}}
        <div class="bg-gradient-to-br from-[#FFF9FB] via-[#FCEDF2] to-[#FAF0F4] p-5 sm:p-6 rounded-2xl border border-[#F2D6DF] space-y-3.5 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between text-sm gap-1">
                <span class="text-[#665D62] font-medium">Layanan / Item:</span>
                <span class="font-bold text-[#1E1B1D] text-right">
                    @if($invoice->service_type === 'PREMIUM')
                    Paket NAYARA Premium ({{ $invoice->package_duration_months }} Bulan)
                    @elseif($invoice->service_type === 'ANALYTICS_ADDON')
                    Financial Analytics Add-On ({{ $invoice->package_duration_months }} Bulan)
                    @elseif($invoice->service_type === 'LISTING_SLOTS')
                    Penambahan Slot Etalase Publik (+{{ $invoice->metadata['slots'] ?? 5 }} Slot Permanen)
                    @elseif($invoice->service_type === 'UPGRADE_PREMIUM')
                    Upgrade Prorata Add-on ke Paket Premium
                    @elseif($invoice->service_type === 'SHOP_AD')
                    Iklan Toko (Center Highlight {{ $invoice->metadata['duration_days'] ?? 1 }} Hari)
                    @elseif($invoice->service_type === 'PRODUCT_AD')
                    Iklan Produk (Etalase Unggulan {{ $invoice->metadata['duration_days'] ?? 1 }} Hari)
                    @else
                    {{ $invoice->service_type }}
                    @endif
                </span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between text-sm gap-1 pt-1">
                <span class="text-[#665D62] font-medium">Batas Waktu Pembayaran:</span>
                <span class="inline-flex items-center gap-1.5 font-bold text-rose-700 bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-200 self-start sm:self-auto text-xs sm:text-sm font-mono">
                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $invoice->expires_at->timezone(config('app.display_timezone', 'Asia/Jakarta'))->translatedFormat('d M Y H:i') }} WIB</span>
                </span>
            </div>

            <div class="flex justify-between items-center pt-4 border-t border-[#EBD0DB] text-base">
                <span class="font-bold text-[#1E1B1D]">Total Tagihan:</span>
                <span class="text-2xl sm:text-3xl font-extrabold text-[#520C31] font-mono tracking-tight">
                    Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- Bank Transfer Destination (Elevated with Copy Action) --}}
        <div x-data="{ copied: false, copyAccount() { navigator.clipboard.writeText('8830192830'); this.copied = true; setTimeout(() => this.copied = false, 2500); } }"
             class="border border-[#EFE5EA] rounded-2xl p-5 sm:p-6 space-y-3.5 bg-white shadow-2xs">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#7A7176]">Rekening Tujuan Transfer Resmi Platform</h3>
                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>Akun Resmi Terverifikasi</span>
                </span>
            </div>

            <div class="p-4 sm:p-5 bg-gradient-to-r from-[#FAF6F8] to-[#FCF4F7] rounded-xl border border-[#EAE0E5] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-[#005EAD] text-white flex items-center justify-center font-black text-sm tracking-tight shrink-0 shadow-xs">
                        BCA
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-[#665D62]">Bank Central Asia (BCA)</p>
                        <p class="text-xl sm:text-2xl font-mono font-black text-[#1E1B1D] tracking-wider">8830-1928-30</p>
                        <p class="text-xs font-medium text-[#7A7176]">a.n. <strong>PT NAYARA TEKNOLOGI NUSANTARA</strong></p>
                    </div>
                </div>

                <div class="shrink-0 flex items-center">
                    <button type="button"
                            @click="copyAccount()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-[#D9C4CF] bg-white hover:bg-[#FCEAEF] text-xs font-bold text-[#520C31] transition-all shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer">
                        <svg x-show="!copied" class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"/></svg>
                        <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="copied ? 'Tersalin!' : 'Salin No. Rekening'">Salin No. Rekening</span>
                    </button>
                </div>
            </div>

            <p class="text-[11px] text-[#7A7176] italic bg-[#FFFBFD] p-3 rounded-lg border border-[#F2E6ED]">
                *Catatan Keuangan: Dana pembayaran platform ini terpisah secara legal dan operasional dari pembukuan kas internal toko Anda.
            </p>
        </div>

        {{-- Payment Submission Form or Proof Display --}}
        @if($invoice->status === 'UNPAID')
        <form action="{{ route('owner.subscriptions.submit-proof', $invoice->id) }}"
              method="POST"
              enctype="multipart/form-data"
              x-data="{
                  fileName: '',
                  fileSize: '',
                  handleFileSelect(e) {
                      const file = e.target.files[0];
                      if (file) {
                          this.fileName = file.name;
                          this.fileSize = (file.size / 1024).toFixed(1) + ' KB';
                      } else {
                          this.fileName = '';
                          this.fileSize = '';
                      }
                  }
              }"
              class="space-y-5 pt-5 border-t border-[#F2E6ED]">
            @csrf
            
            <div class="space-y-1">
                <h3 class="text-base font-bold text-[#1E1B1D]">Konfirmasi &amp; Unggah Bukti Transfer</h3>
                <p class="text-xs text-[#665D62]">Silakan unggah bukti transfer perbankan agar tim Super Admin NAYARA segera memverifikasi layanan Anda.</p>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-[#1E1B1D]">Metode Pembayaran</label>
                <div class="relative max-w-md">
                    <select name="payment_method" class="w-full h-11 px-3.5 rounded-xl border border-[#D9C4CF] bg-white text-xs sm:text-sm font-medium text-[#1E1B1D] focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/20 transition-all appearance-none cursor-pointer">
                        <option value="BANK_TRANSFER">Transfer Bank (BCA / Mandiri / BNI / BRI)</option>
                        <option value="QRIS">QRIS / E-Wallet Resmi</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-[#7A7176]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </div>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-[#1E1B1D]">
                    Foto / Tangkapan Layar Bukti Transfer <span class="text-rose-600">*</span>
                </label>
                
                {{-- Custom Interactive Dropzone --}}
                <label class="relative flex flex-col items-center justify-center p-6 border-2 border-dashed border-[#D9C4CF] hover:border-[#8E1B5C] bg-[#FFFBFD] hover:bg-[#FDF2F5] rounded-2xl cursor-pointer transition-all group">
                    <input type="file"
                           name="proof_file"
                           required
                           accept=".jpg,.jpeg,.png,.pdf"
                           @change="handleFileSelect"
                           class="sr-only">
                    
                    <template x-if="!fileName">
                        <div class="text-center space-y-2">
                            <div class="w-12 h-12 rounded-full bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center mx-auto group-hover:scale-110 transition-transform shadow-2xs">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                            </div>
                            <div>
                                <p class="text-xs sm:text-sm font-bold text-[#520C31]">Klik untuk memilih file bukti transfer</p>
                                <p class="text-[11px] text-[#7A7176]">Mendukung file JPG, PNG, atau PDF (Maksimal 3 MB)</p>
                            </div>
                        </div>
                    </template>

                    <template x-if="fileName">
                        <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-emerald-200 shadow-2xs">
                            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div class="text-left">
                                <p class="text-xs font-bold text-[#1E1B1D] truncate max-w-xs" x-text="fileName"></p>
                                <p class="text-[10px] text-emerald-700 font-semibold" x-text="'File terpilih (' + fileSize + ') &bull; Klik untuk mengganti'"></p>
                            </div>
                        </div>
                    </template>
                </label>
            </div>

            <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-r from-[#520C31] to-[#701D49] hover:from-[#66103D] hover:to-[#8E1B5C] text-white text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                <span>Kirim Bukti Pembayaran</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </button>
        </form>
        @elseif($invoice->status === 'PAYMENT_SUBMITTED')
        <div class="p-5 bg-amber-50 rounded-2xl border border-amber-200 text-xs sm:text-sm text-amber-900 space-y-2">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                <p class="font-bold text-sm sm:text-base">Bukti Transfer Berhasil Dikirim!</p>
            </div>
            <p class="leading-relaxed">
                Waktu kirim: <strong>{{ $invoice->payment_submitted_at ? $invoice->payment_submitted_at->timezone(config('app.display_timezone', 'Asia/Jakarta'))->translatedFormat('d M Y H:i') : '-' }} WIB</strong>. Tim Super Admin NAYARA sedang memverifikasi penerimaan dana. Layanan akan aktif secara otomatis setelah diverifikasi.
            </p>
        </div>
        @elseif($invoice->status === 'VERIFIED')
        <div class="p-5 bg-emerald-50 rounded-2xl border border-emerald-200 text-xs sm:text-sm text-emerald-900 space-y-1.5">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="font-bold text-sm sm:text-base">✓ Pembayaran Berhasil Diverifikasi!</p>
            </div>
            <p class="leading-relaxed">
                Diverifikasi pada: <strong>{{ $invoice->verified_at ? $invoice->verified_at->timezone(config('app.display_timezone', 'Asia/Jakarta'))->translatedFormat('d M Y H:i') : '-' }} WIB</strong>. Hak layanan telah aktif di akun toko Anda.
            </p>
        </div>
        @endif

    </div>
</div>
@endsection
