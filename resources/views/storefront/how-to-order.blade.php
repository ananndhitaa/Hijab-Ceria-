@extends('layouts.storefront', [
    'title' => 'Panduan Maklon & Produksi Hijab untuk Brand Owner — NAYARA',
    'description' => 'Solusi produksi dan maklon hijab tangan pertama khusus bagi pemilik brand fashion yang tidak ingin repot mengurus operasional pabrik. Minimal order 10 pcs.'
])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-3 sm:pt-4 pb-14 sm:pb-20 space-y-8 sm:space-y-10">
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
        <a href="{{ route('storefront.help') }}" class="hover:text-[#8E1B5C] transition-colors">Bantuan</a>
        <svg class="w-3.5 h-3.5 text-[#C4B7BE]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
        </svg>
        <span class="text-[#8E1B5C] font-semibold">Panduan Maklon Brand</span>
    </nav>

    {{-- Hero Section --}}
    <div class="relative bg-gradient-to-b from-[#FFF5F8] via-[#FAF0F4] to-[#FDFBFD] border border-[#EFE5EA] rounded-3xl p-6 sm:p-10 text-center relative overflow-hidden shadow-xs">
        <div class="absolute -top-12 -right-12 w-48 h-48 bg-[#FCEAEF] rounded-full blur-3xl opacity-60 pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-[#FCEAEF] rounded-full blur-3xl opacity-60 pointer-events-none"></div>

        <div class="relative max-w-3xl mx-auto space-y-3">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-white text-[#8E1B5C] border border-[#8E1B5C]/20 text-[11px] font-bold uppercase tracking-wider shadow-2xs">
                <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                </svg>
                <span>Solusi Manufaktur &amp; Maklon Brand Hijab</span>
            </span>
            <h1 class="text-2xl sm:text-4xl font-bold text-[#1E1B1D] font-serif tracking-tight leading-snug">
                Punya Brand Hijab Sendiri Tanpa Repot Mengurus Produksi
            </h1>
            <p class="text-xs sm:text-sm text-[#665D62] leading-relaxed max-w-2xl mx-auto">
                Cara Mudah Belanja dan maklon hijab tangan pertama khusus bagi pemilik brand. Fokus penuh pada penjualan, pemasaran, dan pengembangan merk Anda—biarkan NAYARA yang menangani seluruh rantai konveksi standar butik mulai dari 10 pcs.
            </p>
        </div>
    </div>

    {{-- Step by Step 4 Cards for Brand Owners --}}
    <div class="space-y-4">
        <div class="text-center space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E1B5C]">Alur Kerja Praktis 4 Langkah</span>
            <h2 class="text-xl sm:text-2xl font-bold text-[#1E1B1D] font-serif">Cara Memproduksi Hijab Brand Anda</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            {{-- Step 1 --}}
            <div class="p-5 sm:p-6 bg-white rounded-3xl border border-[#EFE5EA] hover:border-[#8E1B5C]/40 shadow-xs hover:shadow-md transition-all space-y-3 text-center group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#FCEAEF] to-[#F7D8E2] text-[#8E1B5C] font-black text-sm flex items-center justify-center mx-auto shadow-2xs border border-[#F2CFDC] group-hover:bg-[#8E1B5C] group-hover:text-white transition-all">
                    01
                </div>
                <h3 class="text-sm sm:text-base font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Pilih Model Koleksi</h3>
                <p class="text-xs text-[#665D62] leading-relaxed">
                    Tentukan jenis produk untuk lini brand Anda: Segi Empat Voal, Pashmina Silk Mewah, Viscose Linen, Motif Printing, atau Hijab Instan.
                </p>
            </div>

            {{-- Step 2 --}}
            <div class="p-5 sm:p-6 bg-white rounded-3xl border border-[#EFE5EA] hover:border-[#8E1B5C]/40 shadow-xs hover:shadow-md transition-all space-y-3 text-center group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#FCEAEF] to-[#F7D8E2] text-[#8E1B5C] font-black text-sm flex items-center justify-center mx-auto shadow-2xs border border-[#F2CFDC] group-hover:bg-[#8E1B5C] group-hover:text-white transition-all">
                    02
                </div>
                <h3 class="text-sm sm:text-base font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Tentukan Varian &amp; Qty</h3>
                <p class="text-xs text-[#665D62] leading-relaxed">
                    Pilih palet warna dan corak kain. Minimal pemesanan hanya <strong class="text-[#8E1B5C]">10 pcs</strong> (bisa campur varian) untuk menguji pasar dengan modal terukur.
                </p>
            </div>

            {{-- Step 3 --}}
            <div class="p-5 sm:p-6 bg-white rounded-3xl border border-[#EFE5EA] hover:border-[#8E1B5C]/40 shadow-xs hover:shadow-md transition-all space-y-3 text-center group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#FCEAEF] to-[#F7D8E2] text-[#8E1B5C] font-black text-sm flex items-center justify-center mx-auto shadow-2xs border border-[#F2CFDC] group-hover:bg-[#8E1B5C] group-hover:text-white transition-all">
                    03
                </div>
                <h3 class="text-sm sm:text-base font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Konsultasi via WhatsApp</h3>
                <p class="text-xs text-[#665D62] leading-relaxed">
                    Diskusikan detail pesanan, kustom label merk brand, konfirmasi invoice pabrik, dan estimasi pengerjaan langsung ke admin konveksi.
                </p>
            </div>

            {{-- Step 4 --}}
            <div class="p-5 sm:p-6 bg-white rounded-3xl border border-[#EFE5EA] hover:border-[#8E1B5C]/40 shadow-xs hover:shadow-md transition-all space-y-3 text-center group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#FCEAEF] to-[#F7D8E2] text-[#8E1B5C] font-black text-sm flex items-center justify-center mx-auto shadow-2xs border border-[#F2CFDC] group-hover:bg-[#8E1B5C] group-hover:text-white transition-all">
                    04
                </div>
                <h3 class="text-sm sm:text-base font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Produksi &amp; Siap Jual</h3>
                <p class="text-xs text-[#665D62] leading-relaxed">
                    Pesanan diproduksi dengan QC ketat, dikemas berstandar butik siap jual, dan dikirim aman ke alamat Anda dengan nomor resi terdaftar.
                </p>
            </div>
        </div>
    </div>

    {{-- Skema Layanan Manufaktur untuk Brand Owner --}}
    <div class="space-y-4">
        <div class="text-center space-y-1">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E1B5C]">Skema Produksi Khusus Brand Owner</span>
            <h2 class="text-xl sm:text-2xl font-bold text-[#1E1B1D] font-serif">Solusi Manufaktur untuk Pemilik Brand Hijab</h2>
            <p class="text-xs text-[#665D62]">Tidak melayani order untuk diri sendiri. Khusus bagi pemilik label fashion yang ingin punya produk hijab tanpa repot memikirkan produksi.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
            {{-- Tier 1: Starter Batch (Minimal 10 pcs) --}}
            <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-[#FFF9FB] via-white to-[#FDF4F8] border border-[#E8D6DF] shadow-xs space-y-4 relative overflow-hidden flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#FCEAEF] text-[#8E1B5C] border border-[#8E1B5C]/20 rounded-full text-[11px] font-bold uppercase tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C]"></span>
                        <span>Starter Batch: Rilis Produk Brand (Min. 10 Pcs)</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold text-[#1E1B1D] font-serif">Peluncuran Koleksi Brand Baru</h3>
                    <p class="text-xs text-[#665D62] leading-relaxed">
                        Sangat ideal bagi pemilik brand pemula atau butik yang ingin meluncurkan varian hijab atas nama brand sendiri tanpa modal besar dan tanpa perlu mendirikan pabrik konveksi sendiri.
                    </p>

                    <div class="pt-2 space-y-2.5 text-xs text-[#554C51]">
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#8E1B5C]/10 text-[#8E1B5C] flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <span><strong>Khusus pemilik brand &amp; bisnis:</strong> Didedikasikan untuk lini produk brand Anda, bukan untuk pemakaian pribadi/eceran.</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#8E1B5C]/10 text-[#8E1B5C] flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <span><strong>Minimal pemesanan hanya 10 pcs:</strong> Batas kuota sangat ramah untuk validasi pasar dan launching koleksi awal tanpa risiko stok menumpuk.</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#8E1B5C]/10 text-[#8E1B5C] flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <span><strong>Bebas campur varian &amp; warna:</strong> Anda bebas mengombinasikan aneka corak dan warna kain dalam satu paket pesanan 10 pcs.</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#8E1B5C]/10 text-[#8E1B5C] flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <span><strong>Harga modal tangan pertama:</strong> Langsung dari pabrik konveksi mitra NAYARA untuk memaksimalkan margin keuntungan brand Anda.</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#8E1B5C]/10 text-[#8E1B5C] flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <span><strong>Garansi retur 7 hari:</strong> Jaminan ganti produk jika terdapat cacat jahitan atau noda bahan sebelum Anda jual ke konsumen.</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#F2E8ED]">
                    <a href="{{ route('landing') }}#produk-unggulan" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#8E1B5C] hover:text-[#520C31] transition-colors">
                        <span>Pilih Koleksi Produk Brand &rarr;</span>
                    </a>
                </div>
            </div>

            {{-- Tier 2: Scale-Up & Maklon Private Label (50+ / 100+ pcs) --}}
            <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-[#520C31] via-[#6B1442] to-[#3D0723] text-white shadow-md space-y-4 relative overflow-hidden flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/20 text-[#FFD8D8] rounded-full text-[11px] font-bold uppercase tracking-wide backdrop-blur-xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#FFD8D8]"></span>
                        <span>Scale-Up: Maklon Private Label (50+ Pcs)</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold font-serif text-white">Maklon Skala Penuh &amp; Label Eksklusif</h3>
                    <p class="text-xs text-white/80 leading-relaxed">
                        Solusi manufaktur industri lengkap bagi brand hijab yang sedang berkembang pesat (*scale-up*). Anda cukup fokus pada pemasaran dan penjualan, biarkan kami yang mengelola seluruh proses produksi massal.
                    </p>

                    <div class="pt-2 space-y-2.5 text-xs text-white/90">
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-white/20 text-[#FFD8D8] flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <span><strong>Kustom Private Label penuh:</strong> Sematkan merk Anda sendiri menggunakan woven label, sablon label, hangtag, dan packaging eksklusif.</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-white/20 text-[#FFD8D8] flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <span><strong>Harga maklon berjenjang:</strong> Semakin besar kuantitas pesanan (50, 100, hingga 500+ pcs), biaya modal produksi per pcs semakin hemat.</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-white/20 text-[#FFD8D8] flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <span><strong>Uji sampel &amp; proofing bahan:</strong> Dapatkan sampel uji kain, handfeel, dan proofing warna sebelum proses jahit massal dimulai.</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-white/20 text-[#FFD8D8] flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </span>
                            <span><strong>Kapasitas ribuan pcs/minggu:</strong> Didukung jaringan pabrik konveksi mandiri terintegrasi SaaS ERP NAYARA.</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-white/15">
                    <a href="https://wa.me/6281234567890?text=Halo%20Admin%20NAYARA,%20saya%20pemilik%20brand%20dan%20ingin%20konsultasi%20maklon%20private%20label%20hijab." target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#FFD8D8] hover:text-white transition-colors">
                        <span>Konsultasi Maklon Brand via WhatsApp &rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Action Buttons --}}
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4 sm:pt-6">
        <a href="{{ route('landing') }}#produk-unggulan" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-[#8E1B5C] hover:bg-[#701D49] text-white font-bold text-xs sm:text-sm rounded-full transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5">
            <span>Jelajahi Katalog Produk Brand</span>
            <span>&rarr;</span>
        </a>
        <a href="https://wa.me/6281234567890?text=Halo%20Admin%20NAYARA,%20saya%20pemilik%20brand%20dan%20ingin%20konsultasi%20produksi%20hijab." target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-white hover:bg-[#FFF9FB] text-[#8E1B5C] border border-[#E8D6DF] font-bold text-xs sm:text-sm rounded-full transition-all shadow-xs">
            <span>Konsultasi Maklon WhatsApp</span>
        </a>
    </div>
</div>
@endsection
