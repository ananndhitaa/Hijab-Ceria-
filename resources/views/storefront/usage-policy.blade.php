@extends('layouts.storefront', [
    'title' => 'Kebijakan Penggunaan — NAYARA',
    'description' => 'Panduan dan etika penggunaan ekosistem platform konveksi modest fashion NAYARA.'
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
        <span class="text-[#8E1B5C] font-semibold">Kebijakan Penggunaan</span>
    </nav>

    {{-- Hero Header --}}
    <div class="text-center max-w-2xl mx-auto space-y-3">
        <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-[#FCEAEF] text-[#8E1B5C] border border-[#8E1B5C]/20 text-[11px] font-bold uppercase tracking-wider">
            <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286zm0 13.036h.008v.008H12v-.008z"/>
            </svg>
            <span>Standar Komunitas &amp; Ekosistem</span>
        </span>
        <h1 class="text-2xl sm:text-4xl font-bold text-[#1E1B1D] font-serif tracking-tight">
            Kebijakan Penggunaan Ekosistem NAYARA
        </h1>
        <p class="text-xs sm:text-sm text-[#665D62] leading-relaxed">
            Pedoman etika operasional dan standar profesional bagi seluruh mitra industri konveksi dan pemilik brand modest fashion.
        </p>
    </div>

    {{-- Legal Tab Switcher Pills --}}
    <div class="flex items-center justify-center gap-2 flex-wrap text-xs font-semibold">
        <a href="{{ route('storefront.terms') }}" class="px-4 py-2 rounded-full bg-white text-[#554C51] border border-[#E8D6DF] hover:border-[#8E1B5C] hover:text-[#8E1B5C] transition-all">
            Syarat &amp; Ketentuan
        </a>
        <a href="{{ route('storefront.privacy') }}" class="px-4 py-2 rounded-full bg-white text-[#554C51] border border-[#E8D6DF] hover:border-[#8E1B5C] hover:text-[#8E1B5C] transition-all">
            Kebijakan Privasi
        </a>
        <span class="px-4 py-2 rounded-full bg-[#8E1B5C] text-white shadow-xs">
            Kebijakan Penggunaan
        </span>
    </div>

    {{-- Card-based Structured Legal Clauses --}}
    <div class="space-y-4 sm:space-y-5">
        {{-- Clause 1 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">01</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Prinsip Integritas &amp; Kemitraan Adil</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Seluruh pihak yang tergabung dalam ekosistem NAYARA wajib menjunjung tinggi etika transaksi yang transparan, jujur, dan profesional. Platform ini menghubungkan pemilik brand langsung dengan pabrik konveksi mandiri untuk saling mendukung pertumbuhan ekonomi kreatif lokal.
            </p>
        </div>

        {{-- Clause 2 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">02</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Standar Mutu Produksi Mitra Konveksi</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Setiap pabrik konveksi wajib menerapkan prosedur kendali mutu (<em class="text-[#8E1B5C] font-semibold">Quality Control</em>) sebelum barang dikemas, menggunakan bahan baku sesuai spesifikasi yang disetujui, serta memberikan dokumentasi foto produk yang riil dan akurat.
            </p>
        </div>

        {{-- Clause 3 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">03</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Tindakan Terlarang</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Penyalahgunaan berikut dapat berakibat sanksi penonaktifan akun secara permanen:
            </p>
            <ul class="text-xs sm:text-sm text-[#554C51] space-y-2 leading-relaxed">
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-2 shrink-0"></span>
                    <span>Manipulasi bukti transfer pembayaran atau penipuan finansial dalam bentuk apapun.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-2 shrink-0"></span>
                    <span>Pencurian atau duplikasi desain hijab eksklusif milik brand lain tanpa izin resmi.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-2 shrink-0"></span>
                    <span>Penyalahgunaan data kontak mitra untuk spam, penipuan, atau peretasan sistem SaaS ERP.</span>
                </li>
            </ul>
        </div>

        {{-- Clause 4 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">04</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Mediasi &amp; Penyelesaian Sengketa</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Jika terdapat kendala ketidaksesuaian produksi atau pengiriman antara pemesan dan pabrik konveksi, NAYARA memfasilitasi jalur mediasi resmi melalui Customer Care untuk mencari solusi penyelesaian yang adil dan transparan bagi kedua belah pihak.
            </p>
        </div>
    </div>

    {{-- Help & Contact Banner --}}
    <div class="p-6 sm:p-8 bg-gradient-to-r from-[#FAF2F5] via-[#FFF3F7] to-[#FCEAEF] rounded-3xl border border-[#EBD7E3] flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xs">
        <div>
            <h4 class="text-sm font-bold text-[#520C31]">Ingin Melaporkan Pelanggaran Ekosistem?</h4>
            <p class="text-xs text-[#665D62] mt-0.5">Layanan pengaduan NAYARA siap menindaklanjuti laporan demi menjaga kenyamanan ekosistem bersama.</p>
        </div>
        <a href="{{ route('storefront.contact') }}" class="px-6 py-2.5 bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs font-bold rounded-xl shadow-xs transition-colors shrink-0">
            Laporkan Pelanggaran &rarr;
        </a>
    </div>
</div>
@endsection
