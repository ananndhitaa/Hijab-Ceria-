@extends('layouts.storefront', [
    'title' => 'Tentang NAYARA — Ekosistem Manufaktur & Modest Fashion',
    'description' => 'Mengenal NAYARA sebagai ekosistem digital manufaktur hijab tangan pertama yang memberdayakan konveksi mandiri Indonesia.'
])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-3 sm:pt-4 pb-14 sm:pb-20 space-y-8 sm:space-y-12">
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
        <span class="text-[#8E1B5C] font-semibold">Tentang Kami</span>
    </nav>

    {{-- Hero Section --}}
    <div class="relative bg-gradient-to-r from-[#FAF2F5] via-[#FCEAEF] to-[#F8F0F5] rounded-3xl p-8 sm:p-14 border border-[#EFE5EA] shadow-xs overflow-hidden">
        <div class="max-w-3xl space-y-4">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white text-[#8E1B5C] border border-[#8E1B5C]/25 text-xs font-bold uppercase tracking-wider shadow-2xs">
                <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C]"></span>
                <span>Tentang NAYARA</span>
            </span>
            <h1 class="text-3xl sm:text-5xl font-bold text-[#1E1B1D] font-serif tracking-tight leading-tight">
                Menghubungkan Keanggunan Hijab Langsung dari <span class="text-[#8E1B5C] italic">Tangan Pertama</span>
            </h1>
            <p class="text-sm sm:text-base text-[#554C51] leading-relaxed">
                NAYARA adalah ekosistem manufaktur terpadu yang menghubungkan pemilik brand hijab langsung dengan pabrik konveksi mandiri terverifikasi. Kami memadukan standar produksi butik modern dengan transparansi harga tangan pertama tanpa potongan perantara.
            </p>
        </div>
    </div>

    {{-- Visi & Misi --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="p-8 rounded-3xl bg-white border border-[#EFE5EA] shadow-xs space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shadow-2xs">
                <svg class="w-6 h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59"/>
                </svg>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-[#1E1B1D] font-serif">Visi Kami</h2>
            <p class="text-xs sm:text-sm text-[#665D62] leading-relaxed">
                Menjadi mitra manufaktur modest fashion terdepan di Indonesia yang membantu pemilik brand meluncurkan dan membesarkan lini produk hijab berkualitas tanpa kendala operasional pabrik.
            </p>
        </div>

        <div class="p-8 rounded-3xl bg-white border border-[#EFE5EA] shadow-xs space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shadow-2xs">
                <svg class="w-6 h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-[#1E1B1D] font-serif">Misi Kami</h2>
            <ul class="text-xs sm:text-sm text-[#665D62] space-y-3 leading-relaxed">
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-2 shrink-0"></span>
                    <span><strong>Standar Kualitas Butik:</strong> Pengendalian mutu ketat pada setiap kelim, jahitan tepi, dan konsistensi warna kain sebelum pengiriman.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-2 shrink-0"></span>
                    <span><strong>Efisiensi Tanpa Perantara:</strong> Menghubungkan pemilik label fashion langsung ke pabrik konveksi untuk margin bisnis optimal.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-2 shrink-0"></span>
                    <span><strong>Integrasi Sistem SaaS ERP:</strong> Memberikan keteraturan manajemen bahan baku, kapasitas produksi, dan ketepatan jadwal rilis produk.</span>
                </li>
            </ul>
        </div>
    </div>

    {{-- 4 Pilar Utama --}}
    <div class="space-y-6">
        <div class="text-center space-y-1">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#1E1B1D] font-serif">Mengapa Memilih NAYARA?</h2>
            <p class="text-xs sm:text-sm text-[#665D62]">Standar mutu dan komitmen kami kepada mitra pemilik brand hijab</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="p-6 bg-white rounded-2xl border border-[#EFE5EA] shadow-xs space-y-3 text-center hover:border-[#8E1B5C]/30 transition-colors">
                <div class="w-12 h-12 mx-auto rounded-xl bg-[#FFF9FB] border border-[#E1CFD9] text-[#8E1B5C] flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-[#1E1B1D]">Produksi Tangan Pertama</h3>
                <p class="text-xs text-[#665D62] leading-relaxed">Dikelola langsung oleh jaringan mitra konveksi resmi tanpa markup pihak ketiga.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-[#EFE5EA] shadow-xs space-y-3 text-center hover:border-[#8E1B5C]/30 transition-colors">
                <div class="w-12 h-12 mx-auto rounded-xl bg-[#FFF9FB] border border-[#E1CFD9] text-[#8E1B5C] flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-[#1E1B1D]">Garansi Retur 7 Hari</h3>
                <p class="text-xs text-[#665D62] leading-relaxed">Jaminan penggantian jika ditemukan cacat jahitan, cacat motif printing, atau kerusakan bahan.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-[#EFE5EA] shadow-xs space-y-3 text-center hover:border-[#8E1B5C]/30 transition-colors">
                <div class="w-12 h-12 mx-auto rounded-xl bg-[#FFF9FB] border border-[#E1CFD9] text-[#8E1B5C] flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-[#1E1B1D]">Minimal Order Ramah (10 Pcs)</h3>
                <p class="text-xs text-[#665D62] leading-relaxed">Mulai dari batch 10 pcs untuk validasi pasar hingga ribuan pcs untuk produksi maklon skala besar.</p>
            </div>

            <div class="p-6 bg-white rounded-2xl border border-[#EFE5EA] shadow-xs space-y-3 text-center hover:border-[#8E1B5C]/30 transition-colors">
                <div class="w-12 h-12 mx-auto rounded-xl bg-[#FFF9FB] border border-[#E1CFD9] text-[#8E1B5C] flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-[#1E1B1D]">Konsultasi Langsung WhatsApp</h3>
                <p class="text-xs text-[#665D62] leading-relaxed">Komunikasi cepat dengan admin konveksi resmi untuk proofing bahan dan koordinasi pesanan.</p>
            </div>
        </div>
    </div>

    {{-- CTA --}}
    <div class="p-8 sm:p-12 rounded-3xl bg-[#520C31] text-white flex flex-col sm:flex-row items-center justify-between gap-6 shadow-md">
        <div class="space-y-2 text-center sm:text-left">
            <h3 class="text-xl sm:text-2xl font-bold font-serif">Kembangkan Koleksi Brand Hijab Anda</h3>
            <p class="text-xs sm:text-sm text-white/75">Konsultasikan kebutuhan produksi dan dapatkan penawaran tangan pertama terbaik.</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('landing') }}#produk-unggulan" class="px-6 py-3 bg-white text-[#520C31] text-xs sm:text-sm font-bold rounded-full hover:bg-[#FCEAEF] transition-all shadow-sm">
                Lihat Katalog Produk &rarr;
            </a>
            <a href="https://wa.me/6281234567890?text=Halo%20Admin%20NAYARA,%20saya%20pemilik%20brand%20dan%20ingin%20konsultasi%20produksi%20hijab." target="_blank" rel="noopener noreferrer" class="px-6 py-3 bg-[#8E1B5C] text-white text-xs sm:text-sm font-bold rounded-full hover:bg-[#701D49] border border-white/20 transition-all shadow-sm">
                Konsultasi WhatsApp
            </a>
        </div>
    </div>
</div>
@endsection
