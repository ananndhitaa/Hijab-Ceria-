@extends('layouts.storefront', [
    'title' => 'Kebijakan Privasi — NAYARA',
    'description' => 'Kebijakan privasi dan perlindungan data pengguna di ekosistem platform konveksi NAYARA.'
])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-3 sm:pt-4 pb-14 sm:pb-20 space-y-8 sm:space-y-10">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-[#64748B] font-medium" aria-label="Breadcrumb">
        <a href="{{ route('landing') }}" class="hover:text-[#8E1B5C] flex items-center gap-1.5 transition-colors">
            <svg class="w-3.5 h-3.5 text-[#888185]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            <span>Beranda</span>
        </a>
        <svg class="w-3.5 h-3.5 text-[#C4B7BE]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
        </svg>
        <span class="text-[#8E1B5C] font-semibold">Kebijakan Privasi</span>
    </nav>

    {{-- Hero Header --}}
    <div class="text-center max-w-2xl mx-auto space-y-3">
        <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-[#FCEAEF] text-[#8E1B5C] border border-[#8E1B5C]/20 text-[11px] font-bold uppercase tracking-wider">
            <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
            <span>Keamanan Data &amp; Privasi</span>
        </span>
        <h1 class="text-2xl sm:text-4xl font-bold text-[#1E1B1D] font-serif tracking-tight">
            Kebijakan Privasi &amp; Perlindungan Data
        </h1>
        <p class="text-xs sm:text-sm text-[#665D62] leading-relaxed">
            Komitmen kami dalam mengelola dan menjaga keamanan informasi bisnis dan kontak Anda secara transparan dan berintegritas.
        </p>
    </div>

    {{-- Legal Tab Switcher Pills --}}
    <div class="flex items-center justify-center gap-2 flex-wrap text-xs font-semibold">
        <a href="{{ route('storefront.terms') }}" class="px-4 py-2 rounded-full bg-white text-[#554C51] border border-[#E8D6DF] hover:border-[#8E1B5C] hover:text-[#8E1B5C] transition-all">
            Syarat &amp; Ketentuan
        </a>
        <span class="px-4 py-2 rounded-full bg-[#8E1B5C] text-white shadow-xs">
            Kebijakan Privasi
        </span>
        <a href="{{ route('storefront.usage-policy') }}" class="px-4 py-2 rounded-full bg-white text-[#554C51] border border-[#E8D6DF] hover:border-[#8E1B5C] hover:text-[#8E1B5C] transition-all">
            Kebijakan Penggunaan
        </a>
    </div>

    {{-- Card-based Structured Legal Clauses --}}
    <div class="space-y-4 sm:space-y-5">
        {{-- Clause 1 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">01</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Komitmen Perlindungan Privasi</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                NAYARA menghargai dan menjamin kerahasiaan data setiap pemilik brand dan mitra konveksi. Kebijakan ini menetapkan pedoman pengumpulan, pemrosesan, dan penyimpanan data selama Anda menggunakan layanan platform.
            </p>
        </div>

        {{-- Clause 2 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">02</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Informasi yang Kami Kelola</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Data yang dihimpun hanya terbatas pada keperluan pemenuhan pesanan produksi dan pengiriman:
            </p>
            <ul class="text-xs sm:text-sm text-[#554C51] space-y-2 leading-relaxed">
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-2 shrink-0"></span>
                    <span>Identitas PIC/pemilik brand dan kontak resmi (nomor WhatsApp dan email).</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-2 shrink-0"></span>
                    <span>Alamat tujuan pengiriman kargo dan kode pos lokasi penerima.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-2 shrink-0"></span>
                    <span>Riwayat faktur pesanan, spesifikasi varian, dan bukti pembayaran yang diunggah.</span>
                </li>
            </ul>
        </div>

        {{-- Clause 3 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">03</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Tujuan Pemanfaatan Data</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Informasi digunakan secara ketat untuk verifikasi invoice, penjadwalan antrean potong jahit di pabrik mitra, penyampaian status resi pengiriman, serta pemrosesan klaim garansi retur cacat produksi.
            </p>
        </div>

        {{-- Clause 4 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">04</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Kerahasiaan &amp; Larangan Penjualan Data</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Platform NAYARA tidak pernah dan tidak akan menjual, menyewakan, maupun mendistribusikan data kontak atau daftar pesanan pengguna kepada pihak ketiga untuk kepentingan iklan eksternal, sesuai ketentuan perundang-undangan perlindungan data pribadi di Indonesia.
            </p>
        </div>
    </div>

    {{-- Help & Contact Banner --}}
    <div class="p-6 sm:p-8 bg-gradient-to-r from-[#FAF2F5] via-[#FFF3F7] to-[#FCEAEF] rounded-3xl border border-[#EBD7E3] flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xs">
        <div>
            <h4 class="text-sm font-bold text-[#520C31]">Ingin Mengajukan Pertanyaan Seputar Privasi Data?</h4>
            <p class="text-xs text-[#665D62] mt-0.5">Tim Perlindungan Data NAYARA siap membantu meninjau hak akses dan keamanan informasi Anda.</p>
        </div>
        <a href="{{ route('storefront.contact') }}" class="px-6 py-2.5 bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs font-bold rounded-xl shadow-xs transition-colors shrink-0">
            Hubungi Customer Care &rarr;
        </a>
    </div>
</div>
@endsection
