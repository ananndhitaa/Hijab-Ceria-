@extends('layouts.storefront', [
    'title' => 'Direktori Pabrik & Konveksi Hijab — NAYARA Platform',
    'description' => 'Temukan produsen dan mitra konveksi hijab terverifikasi di Indonesia untuk pesanan maklon, seragam, dan brand independen.'
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-3 sm:pt-4 pb-12 sm:pb-16 space-y-8">
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
        <span class="text-[#8E1B5C] font-semibold">Direktori Pabrik</span>
    </nav>

    {{-- Hero Header with Integrated Search --}}
    <div class="bg-gradient-to-br from-[#FFF9FB] via-white to-[#FCEAEF]/50 rounded-3xl border border-[#E1CFD9] p-6 sm:p-8 lg:p-10 shadow-xs relative overflow-hidden">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#FCEAEF] text-[#8E1B5C] border border-[#E1CFD9] text-[11px] font-bold tracking-wider uppercase mb-3">
                <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.25A2.25 2.25 0 010 18.75V10.5m18 10.5h3.75A2.25 2.25 0 0024 18.75V10.5M3 10.5l9-7.5 9 7.5"/>
                </svg>
                <span>Direktori Konveksi</span>
            </span>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#172B4D] font-serif leading-tight">
                Pabrik &amp; Konveksi Hijab
            </h1>
            <p class="text-xs sm:text-sm text-[#64748B] mt-2 max-w-2xl leading-relaxed">
                Temukan mitra produsen hijab terverifikasi untuk pesanan maklon, seragam komunitas, dan pasokan brand tangan pertama tanpa komisi perantara.
            </p>
        </div>

        {{-- Integrated Search Bar --}}
        <div class="mt-6 max-w-xl">
            <form action="{{ route('storefront.shops') }}" method="GET" class="flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4 text-[#888185]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Cari nama pabrik atau kota..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#E1CFD9] bg-white text-xs sm:text-sm text-[#172B4D] placeholder-[#888185] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] shadow-2xs">
                </div>
                <button type="submit"
                        class="px-5 py-2.5 bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs transition-all shrink-0">
                    <span>Cari</span>
                </button>
                @if(!empty($search))
                <a href="{{ route('storefront.shops') }}"
                   class="px-3.5 py-2.5 border border-[#E1CFD9] hover:bg-white text-[#64748B] text-xs font-semibold rounded-xl transition-all shrink-0">
                    Reset
                </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Main Content Grid: Directory Cards (Left) & B2B Assurance Panel (Right) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {{-- Left: Factory Directory Cards --}}
        <div class="lg:col-span-8 space-y-6">
            <div class="flex items-center justify-between text-xs text-[#64748B] pb-1 border-b border-slate-100">
                <span class="font-bold uppercase tracking-wider text-[#172B4D]">
                    Mitra Konveksi Aktif
                </span>
                <span>Menampilkan {{ $shops->total() }} Konveksi</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                @forelse($shops as $shop)
                <div class="group bg-white rounded-3xl border border-[#E1CFD9] p-5 sm:p-6 shadow-xs hover:shadow-md hover:border-[#8E1B5C]/50 transition-all duration-200 flex flex-col justify-between">
                    <div>
                        {{-- Factory Top Info --}}
                        <div class="flex items-start gap-3.5 mb-3.5">
                            @if(!empty($shop->logo_path) && file_exists(public_path('storage/' . $shop->logo_path)))
                                <img src="{{ asset('storage/' . $shop->logo_path) }}" alt="{{ $shop->name }}" class="w-13 h-13 rounded-2xl object-cover border border-[#E1CFD9] group-hover:scale-105 transition-transform shrink-0 shadow-2xs">
                            @else
                                <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-[#F7E6EE] to-[#FFF9FB] text-[#8E1B5C] flex items-center justify-center font-extrabold text-xl border border-[#E1CFD9] group-hover:scale-105 transition-transform shrink-0 shadow-2xs font-serif">
                                    {{ strtoupper(substr($shop->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-bold">
                                        <svg class="w-2.5 h-2.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        <span>Terverifikasi</span>
                                    </span>
                                </div>
                                <h2 class="text-base font-bold text-[#172B4D] truncate group-hover:text-[#8E1B5C] transition-colors leading-snug">
                                    {{ $shop->name }}
                                </h2>
                                <p class="text-xs text-[#64748B] flex items-center gap-1 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-[#8E1B5C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                    </svg>
                                    <span class="truncate">{{ $shop->city ?? 'Indonesia' }}</span>
                                </p>
                            </div>
                        </div>

                        {{-- Factory Bio / Address --}}
                        <p class="text-xs text-[#64748B] line-clamp-2 leading-relaxed mb-4">
                            {{ $shop->address ?? 'Pusat produksi dan manufaktur busana muslim dengan QC butik dan jahitan rapi.' }}
                        </p>

                        {{-- Capability Pills --}}
                        <div class="flex items-center gap-2 flex-wrap text-[11px] mb-4">
                            <span class="px-2.5 py-0.5 rounded-md bg-[#FCEAEF] text-[#8E1B5C] font-semibold border border-[#F2CFDC]">
                                Min. 10 Pcs
                            </span>
                            <span class="px-2.5 py-0.5 rounded-md bg-slate-50 text-slate-700 font-semibold border border-slate-200">
                                Tangan Pertama
                            </span>
                            <span class="px-2.5 py-0.5 rounded-md bg-white text-[#8E1B5C] font-bold border border-[#E1CFD9]">
                                {{ $shop->products_count }} Model Hijab
                            </span>
                        </div>

                        {{-- Real Sample Product Previews (if available) --}}
                        @if(isset($shop->products) && $shop->products->isNotEmpty())
                        <div class="pt-3 border-t border-slate-100 mb-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#64748B] block mb-2">
                                Contoh Koleksi Pabrik:
                            </span>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach($shop->products->take(4) as $preview)
                                <a href="{{ route('storefront.product', $preview->id) }}" class="group/prod block aspect-square rounded-xl bg-[#FFF9FB] border border-[#E1CFD9] overflow-hidden relative shadow-2xs hover:border-[#8E1B5C]/60 transition-all" title="{{ $preview->name }}">
                                    @if($preview->featured_image)
                                    <img src="{{ asset('storage/' . $preview->featured_image) }}" alt="{{ $preview->name }}" class="w-full h-full object-cover group-hover/prod:scale-105 transition-transform duration-200">
                                    @else
                                    <div class="w-full h-full flex items-center justify-center text-[9px] text-[#8E1B5C] font-bold bg-[#FCEAEF]/70">
                                        Hijab
                                    </div>
                                    @endif
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Card Action --}}
                    <div class="pt-2">
                        <a href="{{ route('storefront.shop', $shop->id) }}"
                           class="w-full py-2.5 px-4 bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs font-bold rounded-xl text-center transition-all shadow-xs flex items-center justify-center gap-2 group/btn">
                            <span>Lihat Koleksi &amp; Hubungi</span>
                            <span class="group-hover/btn:translate-x-1 transition-transform">&rarr;</span>
                        </a>
                    </div>
                </div>
                @empty
                <div class="col-span-full bg-white p-12 text-center rounded-3xl border border-[#E1CFD9] shadow-xs">
                    <div class="w-14 h-14 rounded-2xl bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.25A2.25 2.25 0 010 18.75V10.5m18 10.5h3.75A2.25 2.25 0 0024 18.75V10.5M3 10.5l9-7.5 9 7.5"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-[#172B4D]">Belum ada konveksi ditemukan</h3>
                    <p class="text-xs text-[#64748B] mt-1">Coba gunakan kata kunci pencarian yang lain.</p>
                    <a href="{{ route('storefront.shops') }}" class="inline-block mt-4 px-4 py-2 bg-[#8E1B5C] text-white text-xs font-bold rounded-xl hover:bg-[#701D49]">
                        Tampilkan Semua Pabrik
                    </a>
                </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($shops->hasPages())
            <div class="pt-4">
                {{ $shops->links() }}
            </div>
            @endif
        </div>

        {{-- Right: Informative Sidebar & Manufacturer Assurance --}}
        <div class="lg:col-span-4 space-y-5">
            {{-- Quality & Standards Card --}}
            <div class="p-6 bg-gradient-to-br from-[#FFF9FB] to-white rounded-3xl border border-[#E1CFD9] shadow-xs space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#8E1B5C]"></span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#172B4D]">Standar Manufaktur NAYARA</h3>
                </div>

                <div class="space-y-3.5 text-xs text-[#64748B]">
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0 font-bold mt-0.5">
                            <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <strong class="text-[#172B4D] block">Harga Tangan Pertama</strong>
                            <p class="text-[11px] leading-relaxed mt-0.5">Pemesanan langsung ke pabrik konveksi tanpa potongan komisi perantara.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0 font-bold mt-0.5">
                            <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        </div>
                        <div>
                            <strong class="text-[#172B4D] block">Minimal Order 10 Pcs</strong>
                            <p class="text-[11px] leading-relaxed mt-0.5">Bebas memilih aneka varian motif dan warna dalam satu pesanan awal.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0 font-bold mt-0.5">
                            <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                        </div>
                        <div>
                            <strong class="text-[#172B4D] block">Kualitas Jahitan Butik</strong>
                            <p class="text-[11px] leading-relaxed mt-0.5">Standar kelim rapi, double stitch, dan garansi ganti jika ada cacat bahan.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Join as Convection Partner Card --}}
            <div class="p-6 bg-gradient-to-br from-[#520C31] to-[#701D49] rounded-3xl text-white shadow-sm space-y-3">
                <span class="inline-block px-2.5 py-0.5 rounded-full bg-white/15 text-[#FFD8D8] text-[10px] font-bold uppercase tracking-wider backdrop-blur-xs">
                    Kemitraan Pabrik
                </span>
                <h4 class="text-sm font-bold leading-snug">
                    Miliki Usaha Konveksi Hijab?
                </h4>
                <p class="text-xs text-white/80 leading-relaxed">
                    Daftarkan konveksi Anda di platform NAYARA untuk menjangkau pemilik brand dan mengelola produksi terpadu.
                </p>
                <div class="pt-1">
                    <a href="{{ url('/register-tenant') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-white text-[#8E1B5C] text-xs font-bold rounded-xl hover:bg-[#FCEAEF] transition-all shadow-xs">
                        <span>Daftar Konveksi</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            {{-- Concierge Support Link --}}
            <div class="p-5 bg-white rounded-3xl border border-[#E1CFD9] shadow-2xs text-xs space-y-2">
                <span class="font-bold text-[#172B4D] block">Butuh Rekomendasi Konveksi?</span>
                <p class="text-[#64748B] leading-relaxed text-[11px]">
                    Tim Customer Care NAYARA siap membantu menghubungkan kebutuhan produksi brand Anda dengan mitra yang tepat.
                </p>
                <a href="{{ route('storefront.contact') }}" class="text-[#8E1B5C] font-bold hover:underline inline-flex items-center gap-1 text-xs pt-1">
                    <span>Konsultasi dengan Kami</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
