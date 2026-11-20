@extends('layouts.storefront', [
    'title' => 'Kebijakan Pengembalian Produk & Garansi — NAYARA',
    'description' => 'Ketentuan garansi 7 hari dan prosedur pengembalian produk cacat atau salah kirim di platform NAYARA.'
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
        <span class="text-[#8E1B5C] font-semibold">Pengembalian Produk</span>
    </nav>

    {{-- Hero --}}
    <div class="text-center max-w-3xl mx-auto space-y-3">
        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#FCEAEF] text-[#8E1B5C] border border-[#8E1B5C]/25 text-xs font-bold uppercase tracking-wider">
            <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
            <span>Standar Kualitas &amp; Garansi Konveksi</span>
        </span>
        <h1 class="text-3xl sm:text-4xl font-bold text-[#1E1B1D] font-serif tracking-tight">
            Kebijakan Garansi &amp; Pengembalian Produk
        </h1>
        <p class="text-xs sm:text-sm text-[#665D62] leading-relaxed max-w-2xl mx-auto">
            Jaminan perlindungan kualitas produksi untuk setiap batch pesanan brand Anda. Klaim retur akibat kelalaian produksi diproses transparan dalam 7 hari kerja.
        </p>
    </div>

    {{-- 3 Jaminan Utama (Professional SVG Icons, No AI Emojis) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Card 1 --}}
        <div class="p-6 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs space-y-3 hover:border-[#8E1B5C]/30 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center font-bold">
                <svg class="w-5 h-5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-[#1E1B1D]">Garansi Produksi 7 Hari</h3>
            <p class="text-xs text-[#665D62] leading-relaxed">
                Masa pengajuan klaim berlaku 7 hari kalender terhitung sejak paket pesanan diterima sesuai catatan ekspedisi.
            </p>
        </div>

        {{-- Card 2 --}}
        <div class="p-6 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs space-y-3 hover:border-[#8E1B5C]/30 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center font-bold">
                <svg class="w-5 h-5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25H4.875c-.621 0-1.125.504-1.125 1.125v6.75m10.5-7.875v7.875m0 0h3.75"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-[#1E1B1D]">Bebas Biaya Pengganti</h3>
            <p class="text-xs text-[#665D62] leading-relaxed">
                Jika terbukti kelalaian jahit atau ketidaksesuaian varian, ongkos kirim pengembalian dan pengiriman ulang ditanggung konveksi.
            </p>
        </div>

        {{-- Card 3 --}}
        <div class="p-6 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs space-y-3 hover:border-[#8E1B5C]/30 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center font-bold">
                <svg class="w-5 h-5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-[#1E1B1D]">Ganti Baru atau Refund</h3>
            <p class="text-xs text-[#665D62] leading-relaxed">
                Unit cacat langsung diganti unit baru spesifikasi serupa, atau pengembalian dana penuh jika stok bahan kain telah habis.
            </p>
        </div>
    </div>

    {{-- Kriteria Retur & Prosedur Klaim --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
        <div class="p-6 sm:p-8 rounded-3xl bg-white border border-[#EFE5EA] shadow-xs space-y-4">
            <h2 class="text-xl font-bold text-[#1E1B1D] font-serif">Kondisi yang Berhak Mendapat Garansi</h2>
            <ul class="text-xs text-[#665D62] space-y-3 leading-relaxed">
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-1.5 shrink-0"></span>
                    <span><strong>Cacat Jahitan:</strong> Jahitan pinggir lepas, kelim terurai, atau jahitan double-stitch tidak sempurna.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-1.5 shrink-0"></span>
                    <span><strong>Cacat Kain:</strong> Terdapat lubang, serat kain robek, atau noda pewarna permanen dari pabrik.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-1.5 shrink-0"></span>
                    <span><strong>Cacat Cetak Motif:</strong> Motif printing luntur, berbayang, atau tidak rata melebihi toleransi wajar.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C] mt-1.5 shrink-0"></span>
                    <span><strong>Ketidaksesuaian Pesanan:</strong> Varian warna, jenis model hijab, atau jumlah pcs tidak sesuai invoice.</span>
                </li>
            </ul>
        </div>

        <div class="p-6 sm:p-8 rounded-3xl bg-white border border-[#EFE5EA] shadow-xs space-y-4">
            <h2 class="text-xl font-bold text-[#1E1B1D] font-serif">Alur Pengajuan Retur</h2>
            <ol class="text-xs text-[#665D62] space-y-3 leading-relaxed">
                <li class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-[#FCEAEF] text-[#8E1B5C] font-bold text-[11px] flex items-center justify-center shrink-0">1</span>
                    <span><strong>Dokumentasi Unboxing:</strong> Rekam video singkat saat membuka kemasan paket sebagai konfirmasi kondisi awal.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-[#FCEAEF] text-[#8E1B5C] font-bold text-[11px] flex items-center justify-center shrink-0">2</span>
                    <span><strong>Konfirmasi via WhatsApp:</strong> Kirimkan bukti foto/video bagian cacat ke admin konveksi atau Customer Care NAYARA.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-[#FCEAEF] text-[#8E1B5C] font-bold text-[11px] flex items-center justify-center shrink-0">3</span>
                    <span><strong>Verifikasi Tim QC:</strong> Pengajuan ditinjau oleh tim kontrol kualitas dalam waktu maksimal 1x24 jam kerja.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-[#FCEAEF] text-[#8E1B5C] font-bold text-[11px] flex items-center justify-center shrink-0">4</span>
                    <span><strong>Pengiriman Pengganti:</strong> Produk pengganti segera dikirimkan ke alamat Anda dengan nomor resi baru.</span>
                </li>
            </ol>
        </div>
    </div>

    {{-- Help Banner --}}
    <div class="p-6 bg-gradient-to-r from-[#FAF2F5] via-[#FFF3F7] to-[#FCEAEF] rounded-3xl border border-[#EBD7E3] flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h4 class="text-sm font-bold text-[#520C31]">Butuh Bantuan Klaim Garansi?</h4>
            <p class="text-xs text-[#665D62]">Customer Care NAYARA siap mendampingi Anda hingga kendala produksi terselesaikan dengan baik.</p>
        </div>
        <a href="{{ route('storefront.contact') }}" class="px-6 py-2.5 bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs font-bold rounded-xl shadow-xs transition-colors shrink-0">
            Hubungi Customer Care &rarr;
        </a>
    </div>
</div>
@endsection
