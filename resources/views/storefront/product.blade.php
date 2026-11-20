@extends('layouts.storefront', [
    'title' => $product->name . ' — ' . $outlet->name . ' di NAYARA Platform',
    'description' => Str::limit(strip_tags($product->public_description ?? $product->name), 160)
])

@section('content')
@php
    $summary = $product->getPriceSummary();
    $defaultProductImageUrl = $product->featured_image ? asset('storage/' . $product->featured_image) : null;
    $variantsPayload = $product->variants->map(function ($v) use ($defaultProductImageUrl) {
        $img = $v->image_url;
        return [
            'id'              => (int) $v->id,
            'name'            => (string) $v->variant_name,
            'sku'             => (string) $v->sku,
            'image_url'       => $img,
            'effective_image' => $img ?: $defaultProductImageUrl,
            'price'           => $v->base_selling_price !== null ? (float) $v->base_selling_price : null,
            'price_formatted' => $v->base_selling_price !== null && (float) $v->base_selling_price > 0
                ? 'Rp ' . number_format((float) $v->base_selling_price, 0, ',', '.')
                : null,
            'active'          => (bool) $v->active,
        ];
    })->values();
@endphp

<div x-data="{
    variants: {{ Js::from($variantsPayload) }},
    selectedVariantId: null,
    defaultImageUrl: '{{ $defaultProductImageUrl }}',
    defaultPriceFormatted: '{{ $summary['has_price'] ? $summary['formatted'] : 'Harga belum diatur' }}',
    defaultSku: '{{ $product->product_code ?: $product->code ?: ('PROD-' . $product->id) }}',
    rawPhone: '{{ $rawPhone ?? '' }}',
    isPlatformCs: {{ (isset($isPlatformCs) && $isPlatformCs) ? 'true' : 'false' }},
    productName: '{{ addslashes($product->name) }}',
    outletName: '{{ addslashes($outlet->name) }}',
    initialParam: '{{ $initialVariantParam ?? '' }}',

    get currentVariant() {
        if (!this.selectedVariantId) return null;
        return this.variants.find(v => v.id == this.selectedVariantId) || null;
    },

    get currentImageUrl() {
        if (this.currentVariant && this.currentVariant.image_url) {
            return this.currentVariant.image_url;
        }
        return this.defaultImageUrl;
    },

    get currentPriceFormatted() {
        if (this.currentVariant && this.currentVariant.price_formatted) {
            return this.currentVariant.price_formatted;
        }
        return this.defaultPriceFormatted;
    },

    get currentSku() {
        return this.currentVariant ? this.currentVariant.sku : this.defaultSku;
    },

    get currentWhatsappUrl() {
        if (!this.rawPhone) return null;
        let msg = '';
        if (this.currentVariant) {
            let priceText = this.currentVariant.price_formatted ? ` seharga ${this.currentVariant.price_formatted}` : '';
            msg = `Halo ${this.outletName}, saya tertarik memesan produk '${this.productName}' varian '${this.currentVariant.name}'${priceText} di NAYARA. Mohon informasi kuota dan pemesanan konveksi (minimal 10 pcs).`;
        } else {
            msg = `Halo ${this.outletName}, saya tertarik memesan produk '${this.productName}' di NAYARA. Mohon informasi kuota dan pemesanan konveksi (minimal 10 pcs).`;
        }
        return `https://wa.me/${this.rawPhone}?text=${encodeURIComponent(msg)}`;
    },

    selectVariant(v) {
        this.selectedVariantId = v.id;
    },

    init() {
        if (this.variants.length > 0) {
            // Check if initialParam matches an id or sku
            if (this.initialParam) {
                let match = this.variants.find(v => v.id == this.initialParam || v.sku.toLowerCase() === this.initialParam.toLowerCase());
                if (match) {
                    this.selectedVariantId = match.id;
                    return;
                }
            }
            // Default to first active variant or first in array
            let firstActive = this.variants.find(v => v.active);
            this.selectedVariantId = firstActive ? firstActive.id : this.variants[0].id;
        }
    }
}" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-3 sm:pt-4 pb-12 sm:pb-16">

    {{-- Owner / Admin Draft Preview Alert --}}
    @if(isset($isDraftPreview) && $isDraftPreview)
    <div class="mb-5 p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
            <span class="text-xs sm:text-sm font-semibold">
                <strong>Mode Pratinjau Owner:</strong> Produk ini saat ini berstatus <em>Draft (Internal)</em> dan belum diterbitkan ke publik.
            </span>
        </div>
        <a href="{{ route('owner.publications.index') }}" class="text-xs font-bold text-[#8E1B5C] hover:underline shrink-0">
            &larr; Kembali ke Etalase Owner
        </a>
    </div>
    @endif

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-[#64748B] mb-5 font-medium" aria-label="Breadcrumb">
        <a href="{{ route('landing') }}" class="hover:text-[#8E1B5C] flex items-center gap-1 transition-colors">
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
        <a href="{{ route('storefront.shop', $outlet->id) }}" class="hover:text-[#8E1B5C] transition-colors truncate max-w-[140px]">{{ $outlet->name }}</a>
        <svg class="w-3.5 h-3.5 text-[#C4B7BE]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
        </svg>
        <span class="text-[#8E1B5C] font-semibold truncate max-w-xs">{{ $product->name }}</span>
    </nav>

    {{-- Product Main Card --}}
    <div class="bg-white rounded-3xl border border-[#E1CFD9] p-6 sm:p-10 shadow-xs mb-14">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-14 items-start">
            {{-- Left Column: Sticky Gallery, Variant Thumbnails & Trust Badges --}}
            <div class="lg:sticky lg:top-24 space-y-4">
                {{-- Product Image with Variant Photo Switching --}}
                <div class="aspect-square bg-gradient-to-br from-[#F7E6EE] to-[#FFF9FB] rounded-3xl overflow-hidden flex items-center justify-center border border-[#E1CFD9] shadow-inner relative group">
                    <template x-if="currentImageUrl">
                        <img :src="currentImageUrl"
                             :key="currentImageUrl"
                             :alt="currentVariant ? (productName + ' - ' + currentVariant.name) : productName"
                             class="w-full h-full object-cover group-hover:scale-105 transition-all duration-300">
                    </template>
                    <template x-if="!currentImageUrl">
                        <div class="w-full h-full flex flex-col items-center justify-center p-6 text-center select-none bg-gradient-to-br from-[#FFF9FB] to-[#F7E6EE]">
                            <div class="w-20 h-20 rounded-2xl bg-white border border-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shadow-xs mb-3">
                                <svg class="w-10 h-10 text-[#8E1B5C]/80" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                            </div>
                            <span class="text-sm font-bold text-[#172B4D] line-clamp-1">{{ $product->name }}</span>
                            <span class="text-xs text-[#8E1B5C] font-semibold mt-0.5">Katalog Konveksi</span>
                            <span class="text-[11px] text-[#64748B] mt-1">Foto belum diunggah</span>
                        </div>
                    </template>

                    {{-- Image Top-Left Quality Badge --}}
                    <div class="absolute top-4 left-4 z-10">
                        <span class="px-3 py-1 bg-white/90 backdrop-blur-md text-[#8E1B5C] border border-[#E1CFD9] rounded-full text-[10px] font-bold tracking-widest uppercase shadow-2xs">
                            Original Convection
                        </span>
                    </div>

                    {{-- Image Center-Edge Variant Photo Indicator Pill --}}
                    <template x-if="currentVariant && currentVariant.image_url">
                        <div class="absolute bottom-3.5 inset-x-0 flex justify-center z-10 pointer-events-none">
                            <span class="px-3.5 py-1.5 bg-[#8E1B5C]/90 text-white rounded-full text-[11px] font-bold tracking-wide shadow-md backdrop-blur-md flex items-center gap-1.5 pointer-events-auto">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                                </svg>
                                <span x-text="'Foto: ' + currentVariant.name"></span>
                            </span>
                        </div>
                    </template>
                </div>

                {{-- Interactive Variant Photo Thumbnails Strip (Eliminates awkward blank space) --}}
                <template x-if="variants.length > 1">
                    <div class="p-3.5 bg-gradient-to-r from-[#FFF9FB] to-white rounded-2xl border border-[#E1CFD9] shadow-2xs">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                                </svg>
                                <span>Galeri Foto Varian</span>
                            </span>
                            <span class="text-[10px] text-[#8E1B5C] font-semibold">Klik untuk melihat foto</span>
                        </div>
                        <div class="flex items-center gap-2.5 overflow-x-auto pb-1">
                            <template x-for="v in variants" :key="'gallery-thumb-' + v.id">
                                <button type="button"
                                        @click="selectVariant(v)"
                                        :class="selectedVariantId === v.id
                                            ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/30 shadow-xs scale-105'
                                            : 'border-[#E1CFD9] opacity-75 hover:opacity-100 hover:border-[#8E1B5C]/50'"
                                        class="relative w-16 h-16 rounded-xl overflow-hidden border-2 shrink-0 transition-all cursor-pointer bg-white group focus:outline-none">
                                    <template x-if="v.image_url">
                                        <img :src="v.image_url" :alt="v.name" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!v.image_url">
                                        <div class="w-full h-full bg-[#F7E6EE] flex items-center justify-center text-[#8E1B5C] font-bold text-xs" x-text="v.name.substring(0, 2).toUpperCase()"></div>
                                    </template>
                                    <template x-if="selectedVariantId === v.id">
                                        <span class="absolute bottom-0 inset-x-0 bg-[#8E1B5C] text-white text-[8px] font-bold text-center py-0.5 leading-none tracking-wider">AKTIF</span>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Trust & Quality Convection Highlights --}}
                <div class="grid grid-cols-2 gap-2.5 pt-1">
                    <a href="{{ route('storefront.about') }}" class="p-3 bg-gradient-to-br from-[#FFF9FB] to-white rounded-xl border border-[#E1CFD9]/80 flex items-start gap-2.5 shadow-2xs hover:border-[#8E1B5C]/40 hover:shadow-xs transition-all block group">
                        <div class="w-8 h-8 rounded-lg bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center shrink-0 font-bold group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors">Jahitan Butik Rapi</h4>
                            <p class="text-[10px] text-[#64748B] mt-0.5 leading-tight">Double-stitch &amp; QC ketat terjamin</p>
                        </div>
                    </a>
                    <a href="{{ route('storefront.returns') }}" class="p-3 bg-gradient-to-br from-[#FFF9FB] to-white rounded-xl border border-[#E1CFD9]/80 flex items-start gap-2.5 shadow-2xs hover:border-[#8E1B5C]/40 hover:shadow-xs transition-all block group">
                        <div class="w-8 h-8 rounded-lg bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center shrink-0 font-bold group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors">Garansi Retur 7 Hari</h4>
                            <p class="text-[10px] text-[#64748B] mt-0.5 leading-tight">Jaminan ganti jika ada cacat bahan</p>
                        </div>
                    </a>
                    <a href="{{ route('storefront.how-to-order') }}" class="p-3 bg-gradient-to-br from-[#FFF9FB] to-white rounded-xl border border-[#E1CFD9]/80 flex items-start gap-2.5 shadow-2xs hover:border-[#8E1B5C]/40 hover:shadow-xs transition-all block group">
                        <div class="w-8 h-8 rounded-lg bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center shrink-0 font-bold group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors">Minimal Order 10 Pcs</h4>
                            <p class="text-[10px] text-[#64748B] mt-0.5 leading-tight">Konveksi tangan pertama &amp; partai</p>
                        </div>
                    </a>
                    <a href="{{ route('storefront.help') }}" class="p-3 bg-gradient-to-br from-[#FFF9FB] to-white rounded-xl border border-[#E1CFD9]/80 flex items-start gap-2.5 shadow-2xs hover:border-[#8E1B5C]/40 hover:shadow-xs transition-all block group">
                        <div class="w-8 h-8 rounded-lg bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center shrink-0 font-bold group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25H4.875c-.621 0-1.125.504-1.125 1.125v6.75m10.5-7.875v7.875m0 0h3.75"/></svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-[#172B4D] group-hover:text-[#8E1B5C] transition-colors">Pengiriman Cepat</h4>
                            <p class="text-[10px] text-[#64748B] mt-0.5 leading-tight">Packing aman ke seluruh Indonesia</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Right Column: Product Metadata, Dynamic Variants, Specifications & Actions --}}
            <div class="space-y-6">
                <div>
                    <div class="flex items-center gap-2 flex-wrap mb-2.5">
                        <a href="{{ route('storefront.shop', $outlet->id) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#F7E6EE] hover:bg-[#8E1B5C] text-[#8E1B5C] hover:text-white rounded-full text-xs font-bold border border-[#E1CFD9] transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.25A2.25 2.25 0 010 18.75V10.5m18 10.5h3.75A2.25 2.25 0 0024 18.75V10.5M3 10.5l9-7.5 9 7.5"/></svg>
                            <span>{{ $outlet->name }} ({{ $outlet->city ?? 'Indonesia' }})</span>
                        </a>
                        @if($product->public_category)
                        <span class="inline-flex items-center text-xs font-semibold text-[#8E1B5C] bg-[#FFF9FB] border border-[#E1CFD9] px-2.5 py-1 rounded-full">
                            Kategori: {{ $product->public_category }}
                        </span>
                        @endif
                    </div>

                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#172B4D] font-serif leading-tight">
                        {{ $product->name }}
                    </h1>
                </div>

                {{-- Dynamic Price & Selected Variant Display --}}
                <div class="p-5 sm:p-6 bg-gradient-to-br from-[#FFF9FB] via-[#FCEAEF]/40 to-[#F7E6EE]/60 rounded-2xl border border-[#E1CFD9] shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between mb-1.5 gap-2 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#8E1B5C] animate-pulse"></span>
                            <p class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                                <template x-if="currentVariant">
                                    <span>HARGA KONVEKSI (VARIAN: <span class="text-[#8E1B5C] font-extrabold" x-text="currentVariant.name"></span>):</span>
                                </template>
                                <template x-if="!currentVariant">
                                    <span>{{ $summary['label'] }}:</span>
                                </template>
                            </p>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-700 bg-white border border-emerald-200 px-2.5 py-0.5 rounded-full shadow-2xs flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Tangan Pertama Pabrik</span>
                        </span>
                    </div>

                    <div class="flex items-baseline gap-3 my-1">
                        <p class="text-3xl sm:text-4xl font-black text-[#8E1B5C] tracking-tight" x-text="currentPriceFormatted">
                            @if($summary['has_price'])
                                {{ $summary['formatted'] }}
                            @else
                                <span class="text-lg font-semibold text-[#64748B] italic">Harga belum diatur</span>
                            @endif
                        </p>
                        <span class="text-xs font-medium text-slate-500">/ pcs (tangan pertama pabrik)</span>
                    </div>

                    <p class="text-xs text-[#64748B] mt-2 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        <span>Tanpa potongan komisi marketplace — harga transparan langsung dari pabrik konveksi.</span>
                    </p>
                </div>

                {{-- Interactive Variants Selection (Depends on Variant) --}}
                @if($product->variants->isNotEmpty())
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-[#64748B]">PILIHAN VARIAN &amp; HARGA MASTER</h2>
                            <span class="text-[11px] font-semibold text-[#8E1B5C] bg-[#F7E6EE] px-2 py-0.5 rounded-full">
                                <span x-text="variants.length"></span> Pilihan
                            </span>
                        </div>
                        <span class="text-[11px] text-slate-500 font-normal">Klik varian untuk memilih</span>
                    </div>

                    {{-- Responsive Symmetrical Grid for 2, 3, or multiple variants --}}
                    <div :class="variants.length === 2 ? 'grid grid-cols-1 sm:grid-cols-2 gap-3' : (variants.length === 3 ? 'grid grid-cols-1 sm:grid-cols-3 gap-3' : 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3')"
                         class="grid gap-3">
                        <template x-for="v in variants" :key="v.id">
                            <button type="button"
                                    @click="selectVariant(v)"
                                    :class="selectedVariantId === v.id
                                        ? 'border-[#8E1B5C] bg-[#FFF9FB] ring-2 ring-[#8E1B5C]/30 shadow-sm -translate-y-0.5'
                                        : 'border-[#E1CFD9] bg-white hover:border-[#8E1B5C]/50 hover:bg-[#FFF9FB]/50'"
                                    class="p-3.5 rounded-2xl border text-left flex flex-col justify-between transition-all duration-200 cursor-pointer group focus:outline-none relative">

                                {{-- Selected Checkmark Badge (Corner) --}}
                                <template x-if="selectedVariantId === v.id">
                                    <span class="absolute top-2.5 right-2.5 w-5 h-5 rounded-full bg-[#8E1B5C] text-white flex items-center justify-center shadow-xs">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                    </span>
                                </template>

                                <div class="flex items-center gap-3 w-full pr-5">
                                    {{-- Variant Thumbnail / Swatch --}}
                                    <template x-if="v.image_url">
                                        <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 border border-[#E1CFD9] bg-white shadow-2xs">
                                            <img :src="v.image_url" :alt="v.name" class="w-full h-full object-cover">
                                        </div>
                                    </template>
                                    <template x-if="!v.image_url">
                                        <div class="w-12 h-12 rounded-xl shrink-0 border border-[#FFD8D8] bg-gradient-to-br from-[#FFF9FB] to-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center font-bold text-xs shadow-2xs">
                                            <span x-text="v.name.substring(0, 2).toUpperCase()"></span>
                                        </div>
                                    </template>

                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-bold leading-snug group-hover:text-[#8E1B5C] transition-colors line-clamp-2 block"
                                              :class="selectedVariantId === v.id ? 'text-[#8E1B5C]' : 'text-[#172B4D]'"
                                              x-text="v.name"></span>
                                        <span class="text-[10px] text-[#64748B] block mt-0.5"
                                              x-text="selectedVariantId === v.id ? 'Varian aktif' : 'Tersedia'"></span>
                                    </div>
                                </div>

                                <div class="text-xs font-mono font-extrabold mt-3 pt-2 border-t border-slate-100 flex items-center justify-between w-full">
                                    <span :class="selectedVariantId === v.id ? 'text-[#8E1B5C]' : 'text-[#172B4D]'"
                                          x-text="v.price_formatted || 'Hubungi pabrik'"></span>
                                    <span class="text-[10px] font-sans font-semibold"
                                          :class="selectedVariantId === v.id ? 'text-[#8E1B5C]' : 'text-slate-400 group-hover:text-[#8E1B5C] transition-colors'">
                                        <span x-text="selectedVariantId === v.id ? 'Terpilih' : 'Pilih &rarr;'"></span>
                                    </span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
                @endif

                {{-- Product Specifications & Characteristics --}}
                <div class="space-y-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#64748B]">Spesifikasi &amp; Detail Kain</h2>
                    <div class="grid grid-cols-2 gap-2.5 text-xs">
                        <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/80 flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-semibold text-[#64748B] uppercase block">Material Kain</span>
                                <span class="font-bold text-[#172B4D] truncate block">{{ $product->public_category ?? 'Viscose / Voal Premium' }}</span>
                            </div>
                        </div>
                        <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/80 flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-semibold text-[#64748B] uppercase block">Dimensi Ukuran</span>
                                <span class="font-bold text-[#172B4D] truncate block">180 × 60 cm (Standar Pas)</span>
                            </div>
                        </div>
                        <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/80 flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-semibold text-[#64748B] uppercase block">Finishing Tepi</span>
                                <span class="font-bold text-[#172B4D] truncate block">Jahit Double Stitch Rapi</span>
                            </div>
                        </div>
                        <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/80 flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-semibold text-[#64748B] uppercase block">Karakteristik</span>
                                <span class="font-bold text-[#172B4D] truncate block">Adem, Flowy, Mudah Dibentuk</span>
                            </div>
                        </div>
                    </div>

                    {{-- Description Text Box --}}
                    <div class="p-4 bg-[#FFF9FB] rounded-2xl border border-[#E1CFD9]/70 text-xs sm:text-sm text-[#172B4D] leading-relaxed whitespace-pre-line">
                        {{ $product->public_description ?? $product->description ?? 'Produk busana muslim dan hijab berkualitas tinggi dari mitra konveksi terverifikasi NAYARA. Hubungi pabrik untuk ketersediaan warna, bahan, ukuran khusus, minimal pemesanan (MOQ), dan sampel produksi.' }}
                    </div>
                </div>

                {{-- Dynamic WhatsApp CTA Link (updates with selected variant) --}}
                <div class="pt-1">
                    <template x-if="currentWhatsappUrl">
                        <div class="p-5 bg-gradient-to-r from-emerald-500/10 via-[#FFF9FB] to-white rounded-2xl border border-emerald-200 shadow-2xs space-y-3">
                            <div class="flex items-center justify-between text-xs flex-wrap gap-2">
                                <span class="font-bold text-emerald-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                    Admin Konveksi Aktif Siap Melayani
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#FCEAEF] text-[#8E1B5C] font-bold text-[11px] border border-[#F2CFDC]">
                                    <svg class="w-3 h-3 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                    <span>Minimal 10 Pcs</span>
                                </span>
                            </div>

                            <a href="{{ $whatsappUrl }}" :href="currentWhatsappUrl" target="_blank" rel="noopener noreferrer"
                               class="w-full py-4 bg-gradient-to-r from-[#18af7b] to-[#128a61] hover:from-[#159a6d] hover:to-[#0f7753] text-white font-extrabold text-sm sm:text-base rounded-2xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-3 group">
                                <svg class="w-5 h-5 shrink-0 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.668-.699c.969.586 1.761.88 2.79.88h.003c3.181 0 5.769-2.587 5.77-5.767 0-1.542-.6-2.991-1.69-4.081-1.09-1.09-2.539-1.684-4.081-1.684zm0-2.172c4.385 0 7.947 3.562 7.948 7.948 0 2.124-.827 4.121-2.33 5.624-1.503 1.503-3.5 2.33-5.623 2.33-.001 0-.002 0-.003 0-1.282 0-2.533-.338-3.633-.978l-4.39 1.15 1.171-4.275c-.71-1.15-1.085-2.477-1.084-3.851.002-4.386 3.564-7.948 7.944-7.948z"/>
                                </svg>
                                <span>
                                    <template x-if="currentVariant">
                                        <span>Pesan Varian <span x-text="currentVariant.name"></span> via WhatsApp</span>
                                    </template>
                                    <template x-if="!currentVariant">
                                        <span>Pesan / Konsultasi via WhatsApp</span>
                                    </template>
                                </span>
                                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </a>

                            <p class="text-[11px] text-center text-[#64748B]">
                                <template x-if="!isPlatformCs">
                                    <span>Pesan langsung terhubung dengan admin resmi {{ $outlet->name }}. Transaksi aman dan transparan.</span>
                                </template>
                                <template x-if="isPlatformCs">
                                    <span>Terhubung dengan Layanan Pelanggan Resmi NAYARA untuk konfirmasi pesanan ke {{ $outlet->name }}.</span>
                                </template>
                            </p>
                        </div>
                    </template>

                    <template x-if="!currentWhatsappUrl">
                        <div class="p-4 bg-amber-50 text-amber-900 text-xs rounded-xl border border-amber-200">
                            Kontak WhatsApp toko ini belum tersedia. Silakan hubungi customer service resmi platform NAYARA.
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Related Products --}}
    @if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
    <div>
        <div class="mb-6 flex items-end justify-between">
            <div>
                <span class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider block mb-1">Rekomendasi Pilihan</span>
                <h2 class="text-xl sm:text-2xl font-bold text-[#172B4D] font-serif">Koleksi Hijab Pilihan Lainnya</h2>
            </div>
            <a href="{{ route('storefront.shop', $outlet->id) }}" class="text-xs font-bold text-[#8E1B5C] hover:underline flex items-center gap-1">
                <span>Lihat Semua Produk Toko</span>
                <span>&rarr;</span>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
            @foreach($relatedProducts as $rel)
            <a href="{{ route('storefront.product', $rel->id) }}"
               class="group bg-white p-3.5 rounded-2xl border border-[#E1CFD9] shadow-xs hover:shadow-md hover:border-[#8E1B5C]/50 hover:-translate-y-1 transition-all block flex flex-col justify-between">
                <div>
                    <div class="aspect-square bg-gradient-to-br from-[#FFF9FB] to-[#F7E6EE] rounded-xl overflow-hidden mb-3 flex items-center justify-center border border-[#E1CFD9]/50 relative">
                        @if($rel->featured_image)
                        <img src="{{ asset('storage/' . $rel->featured_image) }}" alt="{{ $rel->name }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                        <svg class="w-10 h-10 text-[#8E1B5C]/30" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                        @endif

                        @if($rel->public_category)
                        <span class="absolute top-2 left-2 px-2 py-0.5 bg-white/90 backdrop-blur-xs text-[#8E1B5C] text-[9px] font-bold rounded-full border border-[#E1CFD9]">
                            {{ $rel->public_category }}
                        </span>
                        @endif
                    </div>
                    <h3 class="text-xs sm:text-sm font-bold text-[#172B4D] line-clamp-2 group-hover:text-[#8E1B5C] transition-colors leading-snug">
                        {{ $rel->name }}
                    </h3>
                </div>
                <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-xs sm:text-sm font-extrabold text-[#8E1B5C]">
                        @if($rel->public_price)
                        Rp {{ number_format($rel->public_price, 0, ',', '.') }}
                        @else
                        Hubungi Pabrik
                        @endif
                    </p>
                    <span class="text-[10px] text-slate-400 group-hover:text-[#8E1B5C] font-semibold transition-colors">Detail &rarr;</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
