@extends('layouts.storefront', [
    'title' => 'Pusat Bantuan & FAQ — NAYARA',
    'description' => 'Temukan jawaban lengkap seputar cara pemesanan hijab konveksi, pembayaran, pengiriman, dan layanan pelanggan resmi NAYARA.'
])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-3 sm:pt-4 pb-14 sm:pb-20 space-y-8 sm:space-y-10">
    
    {{-- Breadcrumb Navigation (Neat & Snug at top) --}}
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
        <span class="text-[#8E1B5C] font-semibold">Pusat Bantuan</span>
    </nav>

    {{-- Hero Section --}}
    <div class="relative bg-gradient-to-b from-[#FFF5F8] via-[#FAF0F4] to-[#FDFBFD] border border-[#EFE5EA] rounded-3xl p-6 sm:p-10 text-center relative overflow-hidden shadow-xs">
        <div class="absolute -top-12 -right-12 w-48 h-48 bg-[#FCEAEF] rounded-full blur-3xl opacity-60 pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-[#FCEAEF] rounded-full blur-3xl opacity-60 pointer-events-none"></div>
        
        <div class="relative max-w-2xl mx-auto space-y-3">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-white text-[#8E1B5C] border border-[#8E1B5C]/20 text-[11px] font-bold uppercase tracking-wider shadow-2xs">
                <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 10.5h2.25m-2.25 3h2.25m-2.25 3h2.25m3.75-9H6.75A2.25 2.25 0 004.5 6.75v10.5A2.25 2.25 0 006.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0017.25 4.5z"/>
                </svg>
                <span>Pusat Bantuan &amp; FAQ</span>
            </span>
            <h1 class="text-2xl sm:text-4xl font-bold text-[#1E1B1D] font-serif tracking-tight">
                Ada yang Bisa Kami Bantu?
            </h1>
            <p class="text-xs sm:text-sm text-[#665D62] leading-relaxed">
                Temukan panduan praktis dan jawaban atas pertanyaan seputar pemesanan produk hijab konveksi tangan pertama di platform resmi NAYARA.
            </p>
        </div>
    </div>

    {{-- Quick Action Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Cara Belanja --}}
        <a href="{{ route('storefront.how-to-order') }}" class="p-5 bg-white rounded-2xl border border-[#EFE5EA] hover:border-[#8E1B5C]/50 hover:shadow-md hover:-translate-y-0.5 transition-all flex items-start gap-3.5 group">
            <div class="w-11 h-11 rounded-2xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-[#8E1B5C] group-hover:text-white transition-all shadow-2xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25c-.67 0-1.19-.578-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Panduan Maklon</h3>
                    <span class="text-xs text-[#888185] group-hover:text-[#8E1B5C] group-hover:translate-x-0.5 transition-all">&rarr;</span>
                </div>
                <p class="text-[11px] text-[#64748B] mt-0.5 leading-snug">Produksi brand sendiri (Min. 10 pcs)</p>
            </div>
        </a>

        {{-- Card 2: Lacak Pesanan --}}
        <a href="{{ route('track.index') }}" class="p-5 bg-white rounded-2xl border border-[#EFE5EA] hover:border-[#8E1B5C]/50 hover:shadow-md hover:-translate-y-0.5 transition-all flex items-start gap-3.5 group">
            <div class="w-11 h-11 rounded-2xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-[#8E1B5C] group-hover:text-white transition-all shadow-2xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25H4.875c-.621 0-1.125.504-1.125 1.125v6.75m10.5-7.875v7.875m0 0h3.75"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Lacak Pesanan</h3>
                    <span class="text-xs text-[#888185] group-hover:text-[#8E1B5C] group-hover:translate-x-0.5 transition-all">&rarr;</span>
                </div>
                <p class="text-[11px] text-[#64748B] mt-0.5 leading-snug">Cek status nomor resi</p>
            </div>
        </a>

        {{-- Card 3: Pengembalian --}}
        <a href="{{ route('storefront.returns') }}" class="p-5 bg-white rounded-2xl border border-[#EFE5EA] hover:border-[#8E1B5C]/50 hover:shadow-md hover:-translate-y-0.5 transition-all flex items-start gap-3.5 group">
            <div class="w-11 h-11 rounded-2xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-[#8E1B5C] group-hover:text-white transition-all shadow-2xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Pengembalian</h3>
                    <span class="text-xs text-[#888185] group-hover:text-[#8E1B5C] group-hover:translate-x-0.5 transition-all">&rarr;</span>
                </div>
                <p class="text-[11px] text-[#64748B] mt-0.5 leading-snug">Garansi retur 7 hari</p>
            </div>
        </a>

        {{-- Card 4: Hubungi Kami --}}
        <a href="{{ route('storefront.contact') }}" class="p-5 bg-white rounded-2xl border border-[#EFE5EA] hover:border-[#8E1B5C]/50 hover:shadow-md hover:-translate-y-0.5 transition-all flex items-start gap-3.5 group">
            <div class="w-11 h-11 rounded-2xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-[#8E1B5C] group-hover:text-white transition-all shadow-2xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs sm:text-sm font-bold text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">Hubungi Kami</h3>
                    <span class="text-xs text-[#888185] group-hover:text-[#8E1B5C] group-hover:translate-x-0.5 transition-all">&rarr;</span>
                </div>
                <p class="text-[11px] text-[#64748B] mt-0.5 leading-snug">Customer support resmi</p>
            </div>
        </a>
    </div>

    {{-- Interactive Accordion FAQ Section --}}
    <div class="max-w-3xl mx-auto space-y-4" x-data="{ active: 1 }">
        <div class="text-center space-y-1 pb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E1B5C]">Frequently Asked Questions</span>
            <h2 class="text-2xl sm:text-3xl font-bold text-[#1E1B1D] font-serif">Pertanyaan Populer</h2>
            <p class="text-xs text-[#665D62]">Informasi penting seputar cara pemesanan, harga tangan pertama, retur, dan pengiriman produk.</p>
        </div>

        {{-- FAQ Item 1 --}}
        <div class="rounded-2xl border transition-all duration-200 overflow-hidden"
             :class="active === 1 ? 'border-[#8E1B5C] bg-[#FFF9FB] shadow-xs ring-1 ring-[#8E1B5C]/15' : 'border-[#EFE5EA] bg-white hover:border-[#8E1B5C]/40 shadow-2xs'">
            <button type="button" 
                    @click="active = (active === 1 ? null : 1)" 
                    class="w-full p-4 sm:p-5 flex items-center justify-between gap-3 text-left cursor-pointer group">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 transition-colors"
                          :class="active === 1 ? 'bg-[#8E1B5C] text-white' : 'bg-[#FCEAEF] text-[#8E1B5C] group-hover:bg-[#8E1B5C] group-hover:text-white'">
                        01
                    </span>
                    <span class="font-bold text-xs sm:text-sm text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">
                        Bagaimana cara memproduksi hijab untuk brand saya di NAYARA?
                    </span>
                </div>
                <span class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 transition-all duration-200"
                      :class="active === 1 ? 'bg-[#8E1B5C]/10 text-[#8E1B5C] rotate-180' : 'bg-[#F8F6F7] text-[#888185] group-hover:bg-[#FCEAEF] group-hover:text-[#8E1B5C]'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </span>
            </button>
            <div x-show="active === 1" x-collapse>
                <div class="px-5 pb-5 pt-1 text-xs sm:text-[13px] text-[#554C51] leading-relaxed pl-14 sm:pl-17 border-t border-[#F2E8ED]/70">
                    Pilih model hijab yang sesuai dengan identitas brand Anda di etalase NAYARA, tentukan varian warna atau motif yang diinginkan, kemudian klik tombol <strong class="text-[#1E1B1D]">"Pesan via WhatsApp"</strong>. Anda akan langsung terhubung ke admin resmi pabrik konveksi untuk konfirmasi kuota (minimal 10 pcs), penyematan label brand Anda, kesepakatan invoice, dan jadwal produksi tangan pertama yang aman.
                </div>
            </div>
        </div>

        {{-- FAQ Item 2 --}}
        <div class="rounded-2xl border transition-all duration-200 overflow-hidden"
             :class="active === 2 ? 'border-[#8E1B5C] bg-[#FFF9FB] shadow-xs ring-1 ring-[#8E1B5C]/15' : 'border-[#EFE5EA] bg-white hover:border-[#8E1B5C]/40 shadow-2xs'">
            <button type="button" 
                    @click="active = (active === 2 ? null : 2)" 
                    class="w-full p-4 sm:p-5 flex items-center justify-between gap-3 text-left cursor-pointer group">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 transition-colors"
                          :class="active === 2 ? 'bg-[#8E1B5C] text-white' : 'bg-[#FCEAEF] text-[#8E1B5C] group-hover:bg-[#8E1B5C] group-hover:text-white'">
                        02
                    </span>
                    <span class="font-bold text-xs sm:text-sm text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">
                        Apakah harga di NAYARA sudah merupakan harga tangan pertama?
                    </span>
                </div>
                <span class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 transition-all duration-200"
                      :class="active === 2 ? 'bg-[#8E1B5C]/10 text-[#8E1B5C] rotate-180' : 'bg-[#F8F6F7] text-[#888185] group-hover:bg-[#FCEAEF] group-hover:text-[#8E1B5C]'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </span>
            </button>
            <div x-show="active === 2" x-collapse>
                <div class="px-5 pb-5 pt-1 text-xs sm:text-[13px] text-[#554C51] leading-relaxed pl-14 sm:pl-17 border-t border-[#F2E8ED]/70">
                    <strong class="text-[#1E1B1D]">Ya, 100% harga tangan pertama!</strong> Seluruh produk yang ditampilkan di etalase NAYARA diproduksi langsung oleh produsen konveksi mandiri terverifikasi. Transaksi tidak dikenakan potongan komisi perantara sehingga pemilik brand, butik, dan pebisnis modest fashion mendapatkan harga modal tangan pertama yang paling menguntungkan untuk membangun lini produk brand sendiri tanpa harus mengelola operasional pabrik.
                </div>
            </div>
        </div>

        {{-- FAQ Item 3 --}}
        <div class="rounded-2xl border transition-all duration-200 overflow-hidden"
             :class="active === 3 ? 'border-[#8E1B5C] bg-[#FFF9FB] shadow-xs ring-1 ring-[#8E1B5C]/15' : 'border-[#EFE5EA] bg-white hover:border-[#8E1B5C]/40 shadow-2xs'">
            <button type="button" 
                    @click="active = (active === 3 ? null : 3)" 
                    class="w-full p-4 sm:p-5 flex items-center justify-between gap-3 text-left cursor-pointer group">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 transition-colors"
                          :class="active === 3 ? 'bg-[#8E1B5C] text-white' : 'bg-[#FCEAEF] text-[#8E1B5C] group-hover:bg-[#8E1B5C] group-hover:text-white'">
                        03
                    </span>
                    <span class="font-bold text-xs sm:text-sm text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">
                        Berapa minimal pemesanan (MOQ) produk hijab di NAYARA?
                    </span>
                </div>
                <span class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 transition-all duration-200"
                      :class="active === 3 ? 'bg-[#8E1B5C]/10 text-[#8E1B5C] rotate-180' : 'bg-[#F8F6F7] text-[#888185] group-hover:bg-[#FCEAEF] group-hover:text-[#8E1B5C]'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </span>
            </button>
            <div x-show="active === 3" x-collapse>
                <div class="px-5 pb-5 pt-1 text-xs sm:text-[13px] text-[#554C51] leading-relaxed pl-14 sm:pl-17 border-t border-[#F2E8ED]/70">
                    Sebagai platform manufaktur konveksi tangan pertama, <strong class="text-[#1E1B1D]">minimal pemesanan adalah 10 pcs</strong> per order (Anda bebas mencampur aneka pilihan varian motif dan warna yang tersedia). Untuk pesanan kustom kancing, bordir merk sendiri, atau motif printing eksklusif seragam, minimal pemesanan berkisar antara 20–50 pcs tergantung pabrik konveksi yang dipilih. Konsultasikan langsung via WhatsApp resmi toko.
                </div>
            </div>
        </div>

        {{-- FAQ Item 4 --}}
        <div class="rounded-2xl border transition-all duration-200 overflow-hidden"
             :class="active === 4 ? 'border-[#8E1B5C] bg-[#FFF9FB] shadow-xs ring-1 ring-[#8E1B5C]/15' : 'border-[#EFE5EA] bg-white hover:border-[#8E1B5C]/40 shadow-2xs'">
            <button type="button" 
                    @click="active = (active === 4 ? null : 4)" 
                    class="w-full p-4 sm:p-5 flex items-center justify-between gap-3 text-left cursor-pointer group">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 transition-colors"
                          :class="active === 4 ? 'bg-[#8E1B5C] text-white' : 'bg-[#FCEAEF] text-[#8E1B5C] group-hover:bg-[#8E1B5C] group-hover:text-white'">
                        04
                    </span>
                    <span class="font-bold text-xs sm:text-sm text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">
                        Bagaimana kebijakan garansi retur jika barang cacat?
                    </span>
                </div>
                <span class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 transition-all duration-200"
                      :class="active === 4 ? 'bg-[#8E1B5C]/10 text-[#8E1B5C] rotate-180' : 'bg-[#F8F6F7] text-[#888185] group-hover:bg-[#FCEAEF] group-hover:text-[#8E1B5C]'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </span>
            </button>
            <div x-show="active === 4" x-collapse>
                <div class="px-5 pb-5 pt-1 text-xs sm:text-[13px] text-[#554C51] leading-relaxed pl-14 sm:pl-17 border-t border-[#F2E8ED]/70">
                    Kami memberlakukan <strong class="text-[#1E1B1D]">Garansi Retur 7 Hari</strong> sejak pesanan diterima. Jika terdapat jahitan terurai, noda kain permanen, cacat cetak motif, atau barang yang diterima tidak sesuai varian yang dipesan, Anda berhak mendapatkan penggantian barang baru tanpa biaya tambahan. Lampirkan video unboxing sederhana saat mengajukan klaim.
                </div>
            </div>
        </div>

        {{-- FAQ Item 5 --}}
        <div class="rounded-2xl border transition-all duration-200 overflow-hidden"
             :class="active === 5 ? 'border-[#8E1B5C] bg-[#FFF9FB] shadow-xs ring-1 ring-[#8E1B5C]/15' : 'border-[#EFE5EA] bg-white hover:border-[#8E1B5C]/40 shadow-2xs'">
            <button type="button" 
                    @click="active = (active === 5 ? null : 5)" 
                    class="w-full p-4 sm:p-5 flex items-center justify-between gap-3 text-left cursor-pointer group">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 transition-colors"
                          :class="active === 5 ? 'bg-[#8E1B5C] text-white' : 'bg-[#FCEAEF] text-[#8E1B5C] group-hover:bg-[#8E1B5C] group-hover:text-white'">
                        05
                    </span>
                    <span class="font-bold text-xs sm:text-sm text-[#1E1B1D] group-hover:text-[#8E1B5C] transition-colors">
                        Bagaimana cara melacak pengiriman pesanan saya?
                    </span>
                </div>
                <span class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 transition-all duration-200"
                      :class="active === 5 ? 'bg-[#8E1B5C]/10 text-[#8E1B5C] rotate-180' : 'bg-[#F8F6F7] text-[#888185] group-hover:bg-[#FCEAEF] group-hover:text-[#8E1B5C]'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </span>
            </button>
            <div x-show="active === 5" x-collapse>
                <div class="px-5 pb-5 pt-1 text-xs sm:text-[13px] text-[#554C51] leading-relaxed pl-14 sm:pl-17 border-t border-[#F2E8ED]/70">
                    Setelah pesanan Anda diproses dan dikirim oleh mitra konveksi, admin akan memberikan nomor resi resmi ekspedisi (J&amp;T, JNE, SiCepat, dll). Anda dapat langsung membuka menu <a href="{{ route('track.index') }}" class="text-[#8E1B5C] font-bold underline hover:text-[#520C31] transition-colors">Lacak Pesanan</a> di NAYARA untuk melihat posisi paket secara real-time.
                </div>
            </div>
        </div>
    </div>

    {{-- Concierge Support Card --}}
    <div class="max-w-3xl mx-auto bg-gradient-to-br from-[#520C31] via-[#6B1442] to-[#8E1B5C] text-white rounded-3xl p-6 sm:p-8 shadow-md relative overflow-hidden flex flex-col sm:flex-row items-center justify-between gap-6">
        <div class="space-y-1.5 text-center sm:text-left">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-white text-[10px] font-bold uppercase tracking-wider backdrop-blur-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                <span>Layanan Bantuan NAYARA</span>
            </span>
            <h3 class="text-lg sm:text-xl font-bold font-serif text-white">Pertanyaan Anda Belum Terjawab?</h3>
            <p class="text-xs text-[#F8E7F0] max-w-md leading-relaxed">
                Tim Customer Care kami siap membantu Anda setiap Senin–Sabtu pukul 08.00–17.00 WIB untuk info produk, stok, dan pemesanan grosir.
            </p>
        </div>
        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
            <a href="{{ route('storefront.contact') }}" class="inline-flex items-center gap-2 px-4 sm:px-5 py-2.5 bg-white text-[#520C31] text-xs font-bold rounded-xl hover:bg-[#FCEAEF] transition-all shadow-sm">
                <span>Hubungi Support</span>
                <span>&rarr;</span>
            </a>
            <a href="https://wa.me/6281234567890?text=Halo%20Admin%20NAYARA,%20saya%20butuh%20bantuan%20seputar%20pemesanan%20hijab." target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-2.5 bg-[#25D366] text-white text-xs font-bold rounded-xl hover:bg-[#20ba59] transition-all shadow-sm">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.299.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.353.101.173.449.741.964 1.2.662.591 1.221.774 1.394.86.174.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.144.39-.086s1.011.477 1.184.564.289.13.332.202c.045.073.045.419-.099.824z"/></svg>
                <span>WhatsApp</span>
            </a>
        </div>
    </div>
</div>
@endsection
