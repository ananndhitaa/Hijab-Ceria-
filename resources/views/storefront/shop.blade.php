@extends('layouts.storefront', [
    'title' => $outlet->name . ' — Profil & Etalase Konveksi Hijab NAYARA',
    'description' => 'Koleksi busana muslim dan hijab premium langsung dari ' . $outlet->name . '. Hubungi langsung via WhatsApp tanpa perantara komisi.'
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-3 sm:pt-4 pb-12 sm:pb-16">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-[#64748B] mb-5 font-medium" aria-label="Breadcrumb">
        <a href="{{ route('landing') }}" class="hover:text-[#8E1B5C] flex items-center gap-1.5 transition-colors">
            <svg class="w-3.5 h-3.5 text-[#888185]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            <span>Beranda</span>
        </a>
        <svg class="w-3.5 h-3.5 text-[#C4B7BE]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
        </svg>
        <a href="{{ route('storefront.shops') }}" class="hover:text-[#8E1B5C] transition-colors">Direktori Pabrik</a>
        <svg class="w-3.5 h-3.5 text-[#C4B7BE]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
        </svg>
        <span class="text-[#8E1B5C] font-semibold">{{ $outlet->name }}</span>
    </nav>

    {{-- Shop Profile Card --}}
    <div class="bg-white rounded-3xl border border-[#E1CFD9] p-6 sm:p-8 shadow-xs mb-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            @if(!empty($outlet->logo_path) && file_exists(public_path('storage/' . $outlet->logo_path)))
                <img src="{{ asset('storage/' . $outlet->logo_path) }}" alt="{{ $outlet->name }}" class="w-20 h-20 rounded-2xl object-cover border border-[#E1CFD9] shadow-xs shrink-0">
            @else
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#F7E6EE] to-[#FFF9FB] text-[#8E1B5C] flex items-center justify-center font-extrabold text-3xl border border-[#E1CFD9] shadow-xs shrink-0 font-serif">
                    {{ strtoupper(substr($outlet->name, 0, 1)) }}
                </div>
            @endif
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-block px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-bold tracking-wide">
                        MITRA RESMI TERVERIFIKASI
                    </span>
                    <span class="inline-block px-2.5 py-0.5 bg-[#F7E6EE] text-[#8E1B5C] border border-[#E1CFD9] rounded-full text-[10px] font-bold tracking-wide">
                        PRODUSEN MANDIRI
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#172B4D] font-serif leading-tight">
                    {{ $outlet->name }}
                </h1>
                <p class="text-xs sm:text-sm text-[#64748B] flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#8E1B5C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    <span>{{ $outlet->address ?? 'Indonesia' }} {{ $outlet->city ? '— ' . $outlet->city : '' }}</span>
                </p>
            </div>
        </div>

        @if($whatsappUrl)
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
           class="w-full sm:w-auto px-6 py-3 bg-[#18af7b] hover:bg-[#128a61] text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs transition-all flex items-center justify-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.668-.699c.969.586 1.761.88 2.79.88h.003c3.181 0 5.769-2.587 5.77-5.767 0-1.542-.6-2.991-1.69-4.081-1.09-1.09-2.539-1.684-4.081-1.684zm0-2.172c4.385 0 7.947 3.562 7.948 7.948 0 2.124-.827 4.121-2.33 5.624-1.503 1.503-3.5 2.33-5.623 2.33-.001 0-.002 0-.003 0-1.282 0-2.533-.338-3.633-.978l-4.39 1.15 1.171-4.275c-.71-1.15-1.085-2.477-1.084-3.851.002-4.386 3.564-7.948 7.944-7.948z"/>
            </svg>
            <span>Hubungi via WhatsApp</span>
        </a>
        @endif
    </div>

    {{-- Shop Products Grid Header --}}
    <div class="mb-6 flex items-baseline justify-between">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-[#172B4D] font-serif">Koleksi Produk {{ $outlet->name }}</h2>
            <p class="text-xs text-[#64748B] mt-0.5">Menampilkan {{ $products->total() }} produk aktif di etalase publik</p>
        </div>
    </div>

    @if($products->isEmpty())
    <div class="bg-white p-12 text-center rounded-3xl border border-[#E1CFD9] shadow-xs">
        <div class="w-16 h-16 rounded-2xl bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
        </div>
        <h3 class="text-base font-bold text-[#172B4D]">Belum ada produk aktif</h3>
        <p class="text-xs text-[#64748B] mt-1">Konveksi ini belum mempublikasikan produk ke etalase publik.</p>
        <a href="{{ route('landing') }}" class="inline-block mt-4 px-5 py-2.5 bg-[#8E1B5C] text-white text-xs font-bold rounded-xl hover:bg-[#701D49]">
            Jelajahi Katalog Lainnya
        </a>
    </div>
    @else
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 sm:gap-6">
        @foreach($products as $product)
        <div class="group bg-white rounded-2xl border border-[#E1CFD9] overflow-hidden shadow-xs hover:shadow-md hover:border-[#8E1B5C]/40 transition-all flex flex-col justify-between">
            <div>
                <div class="w-full aspect-square bg-[#F7E6EE] relative overflow-hidden flex items-center justify-center">
                    @if($product->featured_image)
                    <img src="{{ asset('storage/' . $product->featured_image) }}" alt="{{ $product->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    @else
                    <svg class="w-12 h-12 text-[#8E1B5C]/30" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    @endif
                </div>
                <div class="p-4 space-y-1">
                    <h3 class="text-xs sm:text-sm font-bold text-[#172B4D] line-clamp-2 group-hover:text-[#8E1B5C] transition-colors leading-snug">
                        {{ $product->name }}
                    </h3>
                    <p class="text-sm sm:text-base font-extrabold text-[#8E1B5C]">
                        @php $summary = $product->getPriceSummary(); @endphp
                        @if($summary['has_price'])
                            {{ $summary['formatted'] }}
                        @else
                            <span class="text-xs text-[#64748B] font-medium italic">Harga belum diatur</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="p-4 pt-0">
                <a href="{{ route('storefront.product', $product->id) }}"
                   class="block w-full py-2 bg-[#F7E6EE] hover:bg-[#8E1B5C] text-[#8E1B5C] hover:text-white text-xs font-bold rounded-xl text-center transition-colors">
                    Lihat Detail
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
    @endif
</div>
@endsection
