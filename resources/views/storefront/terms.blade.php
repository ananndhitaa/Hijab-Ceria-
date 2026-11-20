@extends('layouts.storefront', [
    'title' => 'Syarat & Ketentuan Layanan — NAYARA',
    'description' => 'Syarat dan ketentuan umum penggunaan platform ekosistem konveksi hijab NAYARA.'
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
        <span class="text-[#8E1B5C] font-semibold">Syarat &amp; Ketentuan</span>
    </nav>

    {{-- Hero Header --}}
    <div class="text-center max-w-2xl mx-auto space-y-3">
        <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-[#FCEAEF] text-[#8E1B5C] border border-[#8E1B5C]/20 text-[11px] font-bold uppercase tracking-wider">
            <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <span>Legalitas &amp; Ketentuan Layanan</span>
        </span>
        <h1 class="text-2xl sm:text-4xl font-bold text-[#1E1B1D] font-serif tracking-tight">
            Syarat &amp; Ketentuan Layanan NAYARA
        </h1>
        <p class="text-xs sm:text-sm text-[#665D62] leading-relaxed">
            Pedoman resmi operasional manufaktur, alur transaksi tangan pertama, dan perlindungan kemitraan bagi pemilik brand serta konveksi mitra.
        </p>
    </div>

    {{-- Legal Tab Switcher Pills --}}
    <div class="flex items-center justify-center gap-2 flex-wrap text-xs font-semibold">
        <span class="px-4 py-2 rounded-full bg-[#8E1B5C] text-white shadow-xs">
            Syarat &amp; Ketentuan
        </span>
        <a href="{{ route('storefront.privacy') }}" class="px-4 py-2 rounded-full bg-white text-[#554C51] border border-[#E8D6DF] hover:border-[#8E1B5C] hover:text-[#8E1B5C] transition-all">
            Kebijakan Privasi
        </a>
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
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Ketentuan Umum &amp; Definisi</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Platform NAYARA beroperasi sebagai sistem manajemen manufaktur dan etalase terpadu bagi mitra konveksi hijab mandiri. Layanan ini dirancang khusus untuk memfasilitasi kebutuhan pemilik label fashion, butik, dan pelaku bisnis busana muslim dalam memproduksi produk hijab berkualitas tanpa harus mendirikan pabrik sendiri.
            </p>
        </div>

        {{-- Clause 2 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">02</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Pemesanan &amp; Transaksi Tangan Pertama</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Seluruh produk dikerjakan langsung oleh pabrik konveksi mitra resmi dengan kuantitas minimal pesanan (<em class="text-[#8E1B5C] font-semibold">Minimum Order Quantity</em>) <strong>10 pcs</strong> per order (bebas campur warna dan motif). Pemesanan dilakukan melalui kanal komunikasi WhatsApp resmi yang terverifikasi tanpa biaya komisi perantara.
            </p>
        </div>

        {{-- Clause 3 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">03</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Pembayaran &amp; Verifikasi Invoice</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Pembayaran pesanan dilakukan melalui rekening perbankan resmi yang tercantum pada lembar tagihan (invoice) konveksi. Proses potong kain, jahit kelim, atau produksi massal dimulai setelah bukti pembayaran terverifikasi sah oleh tim administrasi.
            </p>
        </div>

        {{-- Clause 4 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">04</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Pengiriman &amp; Pelacakan Ekspedisi</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Pengiriman batch pesanan menggunakan jasa ekspedisi kargo atau kurir reguler berizin resmi di Indonesia. Nomor resi otomatis dibagikan kepada pemesan dan dapat dilacak langsung melalui portal Lacak Pesanan NAYARA.
            </p>
        </div>

        {{-- Clause 5 --}}
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs hover:border-[#8E1B5C]/30 transition-all space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] font-bold text-xs flex items-center justify-center shrink-0">05</span>
                <h2 class="text-base sm:text-lg font-bold text-[#1E1B1D] font-serif">Hak Kekayaan Intelektual &amp; Desain Brand</h2>
            </div>
            <p class="text-xs sm:text-sm text-[#554C51] leading-relaxed">
                Hak kepemilikan atas merk, label woven, dan desain hijab kustom milik pemesan tetap menjadi hak eksklusif brand yang bersangkutan. Logo, platform SaaS ERP, dan sistem tata kelola NAYARA dilindungi undang-undang hak cipta Republik Indonesia.
            </p>
        </div>
    </div>

    {{-- Help & Contact Banner --}}
    <div class="p-6 sm:p-8 bg-gradient-to-r from-[#FAF2F5] via-[#FFF3F7] to-[#FCEAEF] rounded-3xl border border-[#EBD7E3] flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xs">
        <div>
            <h4 class="text-sm font-bold text-[#520C31]">Ada Pertanyaan Terkait Ketentuan Layanan?</h4>
            <p class="text-xs text-[#665D62] mt-0.5">Tim Customer Care NAYARA siap memberikan klarifikasi detail mengenai prosedur dan legalitas pesanan.</p>
        </div>
        <a href="{{ route('storefront.contact') }}" class="px-6 py-2.5 bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs font-bold rounded-xl shadow-xs transition-colors shrink-0">
            Hubungi Customer Care &rarr;
        </a>
    </div>
</div>
@endsection
