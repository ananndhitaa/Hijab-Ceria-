@extends('layouts.app')

@section('content')
<div x-data="{
    drawerOpen: false,
    selectedProduct: null,
    activeDrawerTab: 'variants',
    viewMode: 'grid',
    openDrawer(product, defaultTab = 'variants') {
        this.selectedProduct = product;
        this.activeDrawerTab = defaultTab;
        this.drawerOpen = true;
    },
    closeDrawer() {
        this.drawerOpen = false;
    },
    openVariantPhotoModal(v) {
        window.dispatchEvent(new CustomEvent('open-variant-photo-modal', { detail: v }));
    },
    switchView(mode) {
        this.viewMode = mode;
        if (mode === 'list') {
            this.$nextTick(() => {
                if (this.$refs.tableWrapper) {
                    this.$refs.tableWrapper.scrollLeft = 0;
                }
            });
        }
    },
    init() {
        this.$nextTick(() => {
            if (this.$refs.tableWrapper) {
                this.$refs.tableWrapper.scrollLeft = 0;
            }
        });
    }
}" class="space-y-6">

    {{-- ── 1. GLOBAL OWNER FEATURE HEADER (FIXED DESIGN SYSTEM) ────────────── --}}
    <x-feature-header
        title="Manajemen Etalase Publik"
        subtitle="Kelola produk, foto, varian, dan publikasi katalog toko."
    >
        <x-slot:badge>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                EXECUTIVE MONITORING
            </span>
        </x-slot:badge>
        <x-slot:iconSlot>
            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        </x-slot:iconSlot>
        <x-slot:actions>
            <a href="{{ route('owner.subscriptions.index') }}"
               class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <span>Kelola Langganan</span>
            </a>
            <a href="{{ route('owner.subscriptions.index') }}"
               class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#68113F] transition shadow-xs">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
                </svg>
                <span>Tambah Kuota / Upgrade Premium</span>
            </a>
        </x-slot:actions>
    </x-feature-header>

    {{-- Over Quota Warning (Section L: Policy Gap Handling) --}}
    @if($isOverQuota)
    <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-5 shadow-sm space-y-2">
        <div class="flex items-center gap-2 text-amber-900 font-bold text-base">
            <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <span>PERINGATAN: Kuota Etalase Publik Terlampaui (Section L)</span>
        </div>
        <p class="text-xs text-amber-800 leading-relaxed">
            Paket Premium Anda telah berakhir atau kuota listing Anda saat ini ({{ $capacity }} slot) lebih kecil dari jumlah produk yang masih aktif di etalase ({{ $publishedCount }} produk).
            <strong>Data produk internal ERP Anda tetap aman dan tidak dihapus.</strong> Anda tidak dapat menerbitkan produk baru sampai produk yang aktif disesuaikan atau kuota slot ditambah.
            Silakan nonaktifkan (Tarik dari Etalase) produk pilihan Anda hingga memenuhi kuota yang berlaku.
        </p>
    </div>
    @endif

    {{-- ── 2. SUMMARY METRICS — 4 KARTU SEIMBANG ────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        {{-- Card 1: Total Kapasitas Slot --}}
        <div class="bg-white rounded-2xl border border-border-c p-5 flex flex-col justify-between shadow-card hover:shadow-card-hover transition-all min-h-[128px]">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-text-sec uppercase tracking-wider">Total Kapasitas Slot</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#172B4D] mt-1">{{ $capacity }}</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/>
                    </svg>
                </div>
            </div>
            <p class="text-[11px] text-text-sec mt-3 pt-2.5 border-t border-slate-100">
                5 Gratis &bull; {{ max(0, $capacity - 5 - ($hasPremium ? 10 : 0)) }} Permanen {{ $hasPremium ? '+ 10 Premium' : '' }}
            </p>
        </div>

        {{-- Card 2: Produk Aktif Terbit --}}
        <div class="bg-white rounded-2xl border border-border-c p-5 flex flex-col justify-between shadow-card hover:shadow-card-hover transition-all min-h-[128px]">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-text-sec uppercase tracking-wider">Produk Aktif Terbit</p>
                    <p class="text-2xl sm:text-3xl font-extrabold {{ $isOverQuota ? 'text-red-600' : 'text-[#8E1B5C]' }} mt-1">{{ $publishedCount }}</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
                    </svg>
                </div>
            </div>
            <p class="text-[11px] text-text-sec mt-3 pt-2.5 border-t border-slate-100">
                Mengonsumsi slot etalase
            </p>
        </div>

        {{-- Card 3: Sisa Slot Tersedia --}}
        <div class="bg-white rounded-2xl border border-border-c p-5 flex flex-col justify-between shadow-card hover:shadow-card-hover transition-all min-h-[128px]">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-text-sec uppercase tracking-wider">Sisa Slot Tersedia</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-emerald-600 mt-1">{{ $remainingSlots }}</p>
                </div>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-[11px] text-text-sec mt-3 pt-2.5 border-t border-slate-100">
                Slot siap digunakan
            </p>
        </div>

        {{-- Card 4: Status Langganan --}}
        <div class="bg-white rounded-2xl border border-border-c p-5 flex flex-col justify-between shadow-card hover:shadow-card-hover transition-all min-h-[128px]">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-text-sec uppercase tracking-wider">Status Langganan</p>
                    <div class="mt-2">
                        @if($hasPremium)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            PREMIUM (+10 Slot)
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C]"></span>
                            FREE TIER (5 Slot)
                        </span>
                        @endif
                    </div>
                </div>
                <div class="w-9 h-9 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 18h18M4 14l3-7 5 5 5-5 3 7H4z"/>
                    </svg>
                </div>
            </div>
            <a href="{{ route('owner.subscriptions.index') }}" class="text-[11px] text-[#8E1B5C] font-semibold hover:underline mt-3 pt-2.5 border-t border-slate-100 block">
                Kelola Langganan &rarr;
            </a>
        </div>
    </div>

    {{-- ── 3. TOOLBAR KATALOG ───────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-border-c p-3 sm:p-4 shadow-card flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3.5">
        {{-- Status Filter Tabs --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
            <a href="{{ route('owner.publications.index', array_filter(['search' => $search])) }}"
               class="px-3.5 py-2 text-xs font-semibold rounded-xl transition-all whitespace-nowrap {{ empty($statusFilter) ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#F7E6EE] text-[#8E1B5C] hover:bg-[#FFD8D8] border border-transparent' }}">
                Semua ({{ $totalProducts }})
            </a>
            <a href="{{ route('owner.publications.index', array_filter(['filter' => 'published', 'search' => $search])) }}"
               class="px-3.5 py-2 text-xs font-semibold rounded-xl transition-all whitespace-nowrap {{ $statusFilter === 'published' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#F7E6EE] text-[#8E1B5C] hover:bg-[#FFD8D8] border border-transparent' }}">
                Terbit Publik ({{ $publishedCount }})
            </a>
            <a href="{{ route('owner.publications.index', array_filter(['filter' => 'draft', 'search' => $search])) }}"
               class="px-3.5 py-2 text-xs font-semibold rounded-xl transition-all whitespace-nowrap {{ $statusFilter === 'draft' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#F7E6EE] text-slate-700 hover:bg-[#FFD8D8] border border-transparent' }}">
                Internal / Draft ({{ $draftCount }})
            </a>
        </div>

        {{-- Search Input, Filter Button & Grid/List Toggle --}}
        <div class="flex items-center gap-2.5 flex-1 lg:max-w-md justify-end">
            <form action="{{ route('owner.publications.index') }}" method="GET" class="flex-1 flex items-center gap-2 relative">
                @if($statusFilter)
                    <input type="hidden" name="filter" value="{{ $statusFilter }}">
                @endif
                <div class="relative w-full">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nama produk atau SKU..."
                           class="w-full pl-9 pr-8 py-2 text-xs border border-slate-200 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] shadow-xs transition-all">
                    @if($search)
                        <a href="{{ route('owner.publications.index', array_filter(['filter' => $statusFilter])) }}"
                           class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold"
                           title="Hapus pencarian">✕</a>
                    @endif
                </div>
                <button type="submit" class="px-3 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl inline-flex items-center gap-1.5 shadow-xs transition-all shrink-0">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
                    </svg>
                    <span>Filter</span>
                </button>
            </form>

            {{-- Grid / List View Toggle --}}
            <div class="flex items-center bg-slate-100 p-0.5 rounded-xl border border-slate-200 shrink-0">
                <button type="button"
                        @click="switchView('grid')"
                        :class="viewMode === 'grid' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="p-1.5 rounded-lg transition-all"
                        title="Tampilan Grid">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                    </svg>
                </button>
                <button type="button"
                        @click="switchView('list')"
                        :class="viewMode === 'list' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="p-1.5 rounded-lg transition-all"
                        title="Tampilan List">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ── 4. PRODUCT CATALOG — GRID VIEW ──────────────────────────────────── --}}
    <div x-show="viewMode === 'grid'"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        @if($products->isEmpty())
            <div class="card p-12 text-center bg-white border border-border-c rounded-2xl shadow-card">
                <div class="w-14 h-14 rounded-2xl bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-[#172B4D]">Belum Ada Produk Ditemukan</h3>
                <p class="text-xs text-text-sec mt-1 max-w-sm mx-auto">
                    @if($search || $statusFilter)
                        Tidak ada produk yang sesuai dengan pencarian atau filter yang dipilih.
                    @else
                        Mulai tambahkan produk di modul Produksi atau Gudang untuk ditampilkan di etalase toko.
                    @endif
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($products as $product)
                @php
                    $variantCount = $product->variants->count();
                    $priceSummary = $product->getPriceSummary();
                    $productPayload = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->product_code ?: $product->code,
                        'public_category' => $product->public_category ?: 'Tanpa Kategori',
                        'public_price' => $product->public_price ? (float)$product->public_price : null,
                        'public_price_formatted' => $priceSummary['formatted'],
                        'price_formatted' => $priceSummary['formatted'],
                        'price_label' => $priceSummary['label'],
                        'has_price' => $priceSummary['has_price'],
                        'is_range' => $priceSummary['is_range'],
                        'min_price' => $priceSummary['min_price'],
                        'max_price' => $priceSummary['max_price'],
                        'public_description' => $product->public_description ?: 'Belum ada deskripsi publik.',
                        'is_published' => (bool)$product->is_published,
                        'featured_image_url' => $product->featured_image ? asset('storage/' . $product->featured_image) : null,
                        'variant_count' => $variantCount,
                        'variants' => $product->variants->map(function($v) {
                            return [
                                'id' => $v->id,
                                'variant_name' => $v->variant_name,
                                'sku' => $v->sku,
                                'image_url' => $v->image_url,
                                'base_selling_price' => $v->base_selling_price !== null ? (float)$v->base_selling_price : null,
                                'base_selling_price_formatted' => $v->base_selling_price !== null && (float)$v->base_selling_price > 0 ? 'Rp ' . number_format((float)$v->base_selling_price, 0, ',', '.') : 'Harga belum diatur',
                                'active' => (bool)$v->active,
                            ];
                        })->values(),
                    ];
                @endphp
                <div class="bg-white rounded-2xl border border-border-c shadow-card hover:shadow-card-hover transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                    {{-- 1. Area Foto (16:10 Ratio) --}}
                    <div class="aspect-[16/10] relative w-full overflow-hidden bg-[#F7E6EE] flex items-center justify-center">
                        @if($product->featured_image)
                            <img src="{{ asset('storage/' . $product->featured_image) }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            {{-- Elegant Soft Blush Minimalist Placeholder (NO BRAND LOGO) --}}
                            <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-[#FFF9FB] to-[#F7E6EE] p-4 text-center select-none">
                                <div class="w-11 h-11 rounded-xl bg-white/90 border border-[#E1CFD9] text-[#8E1B5C] flex items-center justify-center shadow-xs mb-1.5">
                                    <svg class="w-5 h-5 text-[#8E1B5C]/70" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                </div>
                                <span class="text-xs font-semibold text-[#8E1B5C]/80">Belum Ada Foto</span>
                                <span class="text-[10px] text-text-sec mt-0.5">Unggah foto via Edit Etalase</span>
                            </div>
                        @endif

                        {{-- Badge Status Publikasi (Pojok Kiri Atas) --}}
                        <div class="absolute top-3 left-3 z-10">
                            @if($product->is_published)
                                <span class="bg-emerald-600 text-white text-[11px] font-bold px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1.5 backdrop-blur-xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                    <span>Terbit Publik</span>
                                </span>
                            @else
                                <span class="bg-slate-700/85 text-white text-[11px] font-bold px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1.5 backdrop-blur-xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                    <span>Internal / Draft</span>
                                </span>
                            @endif
                        </div>

                        {{-- Menu 3-Titik (Pojok Kanan Atas) --}}
                        <div class="absolute top-3 right-3 z-10" x-data="{ menuOpen: false }">
                            <button type="button"
                                    @click="menuOpen = !menuOpen"
                                    @click.away="menuOpen = false"
                                    class="w-7 h-7 rounded-lg bg-white/90 hover:bg-white text-slate-700 hover:text-slate-900 backdrop-blur-xs flex items-center justify-center shadow-xs transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="5" r="1.75"/>
                                    <circle cx="12" cy="12" r="1.75"/>
                                    <circle cx="12" cy="19" r="1.75"/>
                                </svg>
                            </button>

                            <div x-show="menuOpen"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-1 w-44 bg-white rounded-xl shadow-xl border border-slate-100 py-1.5 z-20 text-xs"
                                 x-cloak>
                                <button type="button"
                                        @click="openDrawer({{ json_encode($productPayload) }}, 'variants'); menuOpen = false"
                                        class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-[#F7E6EE] hover:text-[#8E1B5C] flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/>
                                    </svg>
                                    <span>Kelola Varian</span>
                                </button>
                                <button type="button"
                                        @click="$dispatch('open-showcase-modal-{{ $product->id }}'); menuOpen = false"
                                        class="w-full text-left px-3 py-1.5 text-slate-700 hover:bg-[#F7E6EE] hover:text-[#8E1B5C] flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                    </svg>
                                    <span>Edit Produk &amp; Etalase</span>
                                </button>
                                <a href="{{ route('storefront.product', $product->id) }}"
                                   target="_blank"
                                   class="px-3 py-1.5 text-slate-700 hover:bg-[#F7E6EE] hover:text-[#8E1B5C] flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                    </svg>
                                    <span>{{ $product->is_published ? 'Lihat di Storefront' : 'Lihat Pratinjau' }}</span>
                                </a>
                                <div class="border-t border-slate-100 my-1"></div>
                                @if($product->is_published)
                                    <form action="{{ route('owner.publications.unpublish', $product->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                onclick="return confirm('Tarik produk {{ $product->name }} dari etalase publik?')"
                                                class="w-full text-left px-3 py-1.5 text-red-700 hover:bg-red-50 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>Tarik dari Etalase</span>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('owner.publications.publish', $product->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="w-full text-left px-3 py-1.5 text-[#8E1B5C] hover:bg-[#F7E6EE] flex items-center gap-2 {{ ($remainingSlots <= 0 || $isOverQuota) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                {{ ($remainingSlots <= 0 || $isOverQuota) ? 'disabled' : '' }}>
                                            <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>Terbitkan Publik</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- Badge Varian (Pojok Kiri Bawah Foto) --}}
                        <div class="absolute bottom-3 left-3 z-10">
                            <span class="bg-black/65 text-white text-[11px] font-semibold px-2.5 py-1 rounded-lg backdrop-blur-xs flex items-center gap-1.5 shadow-xs">
                                <svg class="w-3.5 h-3.5 text-white/90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/>
                                </svg>
                                <span>{{ $variantCount }} Varian</span>
                            </span>
                        </div>
                    </div>

                    {{-- 2. Area Informasi Produk --}}
                    <div class="p-4 space-y-2 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-[#172B4D] text-sm leading-snug truncate group-hover:text-[#8E1B5C] transition-colors" title="{{ $product->name }}">
                                {{ $product->name }}
                            </h3>
                            <div class="flex items-center justify-between gap-2 mt-1">
                                <p class="text-xs text-text-sec font-mono">SKU: {{ $product->product_code ?: $product->code }}</p>
                                @if($product->public_category)
                                    <span class="text-[10px] font-semibold text-[#8E1B5C] bg-[#F7E6EE] border border-[#FFD8D8] px-2 py-0.5 rounded-md truncate max-w-[130px]">
                                        {{ $product->public_category }}
                                    </span>
                                @else
                                    <span class="text-[10px] text-text-sec italic">Tanpa Kategori</span>
                                @endif
                            </div>
                        </div>

                        {{-- Harga Publik / Varian --}}
                        <div class="pt-1">
                            @php $summary = $product->getPriceSummary(); @endphp
                            @if($summary['has_price'])
                                <p class="text-base font-extrabold text-[#8E1B5C]">
                                    {{ $summary['formatted'] }}
                                </p>
                            @else
                                <span class="inline-block text-[11px] font-semibold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-md border border-slate-200">
                                    Harga belum diatur
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- 3. Area Aksi di Bagian Bawah --}}
                    <div class="p-4 pt-2.5 border-t border-slate-100 flex items-center gap-2">
                        {{-- Tombol Kelola Varian -> Slide-Over Drawer --}}
                        <button type="button"
                                @click="openDrawer({{ json_encode($productPayload) }}, 'variants')"
                                class="flex-1 py-2 px-2.5 border border-slate-200 hover:border-[#8E1B5C] hover:text-[#8E1B5C] bg-white text-slate-700 text-xs font-semibold rounded-xl inline-flex items-center justify-center gap-1.5 transition-all shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/>
                            </svg>
                            <span>Kelola Varian ({{ $variantCount }})</span>
                        </button>

                        {{-- Tombol Edit Etalase / Terbitkan Publik --}}
                        @if($product->is_published)
                            <button type="button"
                                    @click="$dispatch('open-showcase-modal-{{ $product->id }}')"
                                    class="flex-1 py-2 px-2.5 border border-slate-200 hover:border-[#8E1B5C] bg-[#FFF9FB] hover:bg-[#F7E6EE] text-[#8E1B5C] text-xs font-semibold rounded-xl inline-flex items-center justify-center gap-1.5 transition-all shadow-xs">
                                <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                </svg>
                                <span>Edit Etalase</span>
                            </button>
                        @else
                            <form action="{{ route('owner.publications.publish', $product->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                        class="w-full py-2 px-2.5 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold rounded-xl inline-flex items-center justify-center gap-1.5 transition-all shadow-xs {{ ($remainingSlots <= 0 || $isOverQuota) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                        {{ ($remainingSlots <= 0 || $isOverQuota) ? 'disabled' : '' }}
                                        title="{{ ($remainingSlots <= 0 || $isOverQuota) ? 'Kuota slot publik penuh' : 'Terbitkan ke etalase publik' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                    </svg>
                                    <span>Terbitkan Publik</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── 5. PRODUCT CATALOG — LIST VIEW ──────────────────────────────────── --}}
    <div x-show="viewMode === 'list'"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="bg-white rounded-2xl border border-border-c shadow-card overflow-hidden w-full">
        <div x-ref="tableWrapper" class="overflow-x-auto w-full scroll-smooth">
            <table class="w-full text-left border-collapse table-fixed min-w-[800px] lg:min-w-full">
                <colgroup>
                    <col style="width: 27%;">
                    <col style="width: 14%;">
                    <col style="width: 13%;">
                    <col style="width: 10%;">
                    <col style="width: 14%;">
                    <col style="width: 22%;">
                </colgroup>
                <thead>
                    <tr class="bg-[#F7E4EC] border-b border-[#EAD0DC] text-xs font-bold text-[#53677F] uppercase tracking-wider">
                        <th class="py-3.5" style="padding-left: 24px !important; padding-right: 12px !important; width: 27%;">FOTO &amp; PRODUK</th>
                        <th class="py-3.5" style="padding-left: 12px !important; padding-right: 12px !important; width: 14%;">KATEGORI</th>
                        <th class="py-3.5" style="padding-left: 12px !important; padding-right: 12px !important; width: 13%;">HARGA PUBLIK</th>
                        <th class="py-3.5" style="padding-left: 12px !important; padding-right: 12px !important; width: 10%;">VARIAN</th>
                        <th class="py-3.5" style="padding-left: 12px !important; padding-right: 12px !important; width: 14%;">STATUS PUBLIKASI</th>
                        <th class="py-3.5" style="padding-left: 12px !important; padding-right: 24px !important; width: 22%; text-align: right !important;">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($products as $product)
                    @php
                        $variantCount = $product->variants->count();
                        $productPayload = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'code' => $product->product_code ?: $product->code,
                            'public_category' => $product->public_category ?: 'Tanpa Kategori',
                            'public_price' => $product->public_price ? (float)$product->public_price : null,
                            'public_price_formatted' => $product->public_price ? 'Rp ' . number_format($product->public_price, 0, ',', '.') : '-',
                            'public_description' => $product->public_description ?: 'Belum ada deskripsi publik.',
                            'is_published' => (bool)$product->is_published,
                            'featured_image_url' => $product->featured_image ? asset('storage/' . $product->featured_image) : null,
                            'variant_count' => $variantCount,
                            'variants' => $product->variants->map(function($v) {
                                return [
                                    'id' => $v->id,
                                    'variant_name' => $v->variant_name,
                                    'sku' => $v->sku,
                                    'base_selling_price' => (float)$v->base_selling_price,
                                    'base_selling_price_formatted' => 'Rp ' . number_format($v->base_selling_price, 0, ',', '.'),
                                    'active' => (bool)$v->active,
                                ];
                            })->values(),
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition-colors {{ $product->is_published ? 'bg-emerald-50/15' : '' }}">
                        <td class="py-4" style="padding-left: 24px; padding-right: 12px;">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-gradient-to-br from-[#FFF9FB] to-[#F7E6EE] border border-[#FFD8D8] overflow-hidden shrink-0 flex flex-col items-center justify-center shadow-xs text-center p-1">
                                    @if($product->featured_image)
                                        <img src="{{ asset('storage/' . $product->featured_image) }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-5 h-5 text-[#8E1B5C]/70" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                        <span class="text-[9px] font-semibold text-[#8E1B5C]/80 leading-none mt-1">Belum Ada Foto</span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-sm text-[#172B4D] leading-snug truncate" title="{{ $product->name }}">{{ $product->name }}</p>
                                    <p class="text-xs text-text-sec mt-0.5 font-mono">SKU: {{ $product->product_code ?: $product->code }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4" style="padding-left: 12px; padding-right: 12px;">
                            @if($product->public_category)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] truncate max-w-[130px]">
                                    {{ $product->public_category }}
                                </span>
                            @else
                                <span class="text-xs text-text-sec italic">Tanpa Kategori</span>
                            @endif
                        </td>
                        <td class="py-4" style="padding-left: 12px; padding-right: 12px;">
                            @php $summary = $product->getPriceSummary(); @endphp
                            @if($summary['has_price'])
                                <span class="font-extrabold text-[#8E1B5C] text-sm sm:text-base">{{ $summary['formatted'] }}</span>
                            @else
                                <span class="inline-block text-[10.5px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200">
                                    Harga belum diatur
                                </span>
                            @endif
                        </td>
                        <td class="py-4" style="padding-left: 12px; padding-right: 12px;">
                            <button type="button"
                                    @click="openDrawer({{ json_encode($productPayload) }}, 'variants')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-[#F7E6EE] hover:text-[#8E1B5C] text-slate-700 transition-colors shadow-xs border border-slate-200">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/>
                                </svg>
                                <span>{{ $variantCount }} Varian</span>
                            </button>
                        </td>
                        <td class="py-4" style="padding-left: 12px; padding-right: 12px;">
                            @if($product->is_published)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Terbit Publik</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200 whitespace-nowrap">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    <span>Internal / Draft</span>
                                </span>
                            @endif
                        </td>
                        <td class="py-4 whitespace-nowrap" style="padding-left: 12px; padding-right: 24px; text-align: right;">
                            <div class="inline-flex items-center gap-2 justify-end">
                                @if($product->is_published)
                                    <form action="{{ route('owner.publications.unpublish', $product->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="h-8 px-2.5 bg-[#FFF0F2] hover:bg-[#FFE4E6] text-[#B91C1C] border border-[#FECDD3] text-xs font-semibold rounded-lg transition-colors shadow-xs"
                                                onclick="return confirm('Tarik produk {{ $product->name }} dari etalase publik?')">
                                            <span class="2xl:hidden">Tarik</span><span class="hidden 2xl:inline">Tarik dari Etalase</span>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('owner.publications.publish', $product->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="h-8 px-2.5 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold rounded-lg transition-colors shadow-xs {{ ($remainingSlots <= 0 || $isOverQuota) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                {{ ($remainingSlots <= 0 || $isOverQuota) ? 'disabled' : '' }}
                                                title="{{ ($remainingSlots <= 0 || $isOverQuota) ? 'Kuota slot publik penuh' : 'Terbitkan ke etalase publik' }}">
                                            <span class="2xl:hidden">Terbitkan</span><span class="hidden 2xl:inline">Terbitkan Publik</span>
                                        </button>
                                    </form>
                                @endif

                                <button type="button"
                                        @click="$dispatch('open-showcase-modal-{{ $product->id }}')"
                                        class="h-8 px-2.5 border border-slate-200 hover:border-[#8E1B5C] hover:text-[#8E1B5C] bg-[#FFF9FB] hover:bg-[#F7E6EE] text-slate-700 text-xs font-semibold rounded-lg transition-colors inline-flex items-center gap-1.5 shadow-xs">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                    <span class="2xl:hidden">Edit</span><span class="hidden 2xl:inline">Edit Etalase</span>
                                </button>

                                <a href="{{ route('storefront.product', $product->id) }}"
                                   target="_blank"
                                   class="h-8 px-2.5 border border-slate-200 hover:border-[#8E1B5C] hover:text-[#8E1B5C] bg-white text-slate-600 text-xs font-semibold rounded-lg transition-colors inline-flex items-center gap-1.5 shadow-xs"
                                   title="{{ $product->is_published ? 'Buka di Storefront' : 'Lihat Pratinjau Detail Produk' }}">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                    </svg>
                                    <span class="2xl:hidden">Lihat</span><span class="hidden 2xl:inline">Lihat Produk</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-text-sec">
                            <div class="max-w-xs mx-auto space-y-2">
                                <div class="w-12 h-12 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-[#172B4D]">Belum Ada Produk Ditemukan</p>
                                <p class="text-xs text-text-sec">Tidak ada produk yang sesuai dengan kriteria yang dipilih.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── 6. PAGINATION ────────────────────────────────────────────────────── --}}
    @if($products->hasPages())
    <div class="p-4 bg-white rounded-2xl border border-border-c shadow-card">
        {{ $products->links() }}
    </div>
    @endif

    {{-- ── 7. SLIDE-OVER DRAWER: "Detail Produk & Varian" ────────────────────── --}}
    <div x-show="drawerOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden"
         aria-labelledby="slide-over-title"
         role="dialog"
         aria-modal="true">

        {{-- Backdrop --}}
        <div x-show="drawerOpen"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeDrawer()"
             class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity"></div>

        <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
            {{-- Drawer Panel --}}
            <div x-show="drawerOpen"
                 x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-full sm:max-w-[520px] md:max-w-[540px] bg-white shadow-2xl flex flex-col h-full">

                {{-- Drawer Header --}}
                <div class="p-5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white sticky top-0 z-10">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/>
                            </svg>
                        </div>
                        <h2 class="text-base font-bold text-[#172B4D]" id="slide-over-title">Detail Produk &amp; Varian</h2>
                    </div>
                    <button type="button"
                            @click="closeDrawer()"
                            class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Drawer Scrollable Content --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-5">
                    <template x-if="selectedProduct">
                        <div>
                            {{-- Mini Hero Card --}}
                            <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/90 flex items-start justify-between gap-3.5 shadow-2xs">
                                <div class="flex items-start gap-3.5 min-w-0">
                                    <div class="w-20 h-20 rounded-xl bg-white border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center shadow-xs">
                                        <template x-if="selectedProduct.featured_image_url">
                                            <img :src="selectedProduct.featured_image_url" :alt="selectedProduct.name" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!selectedProduct.featured_image_url">
                                            <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-[#FFF9FB] to-[#F7E6EE] p-2 text-center select-none">
                                                <svg class="w-6 h-6 text-[#8E1B5C]/70" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                                <span class="text-[10px] font-semibold text-[#8E1B5C]/80 mt-1 leading-none">Belum Ada Foto</span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="min-w-0 space-y-1">
                                        <h3 class="font-bold text-base text-[#172B4D] truncate leading-tight" x-text="selectedProduct.name"></h3>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <template x-if="selectedProduct.is_published">
                                                <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>Terbit Publik</span>
                                                </span>
                                            </template>
                                            <template x-if="!selectedProduct.is_published">
                                                <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                    <span>Internal / Draft</span>
                                                </span>
                                            </template>
                                            <span class="text-xs text-text-sec font-mono" x-text="'SKU: ' + selectedProduct.code"></span>
                                        </div>
                                        <div class="flex items-center gap-2 pt-0.5">
                                            <span class="text-[11px] font-semibold text-[#8E1B5C] bg-[#F7E6EE] border border-[#FFD8D8] px-2 py-0.5 rounded-md inline-block truncate max-w-[160px]"
                                                  x-text="selectedProduct.public_category || 'Tanpa Kategori'"></span>
                                            <span class="text-sm font-extrabold text-[#8E1B5C]" x-text="selectedProduct.public_price_formatted"></span>
                                        </div>
                                    </div>
                                </div>
                                <template x-if="selectedProduct.is_published">
                                    <a :href="'/storefront/product/' + selectedProduct.id"
                                       target="_blank"
                                       class="p-2 text-slate-400 hover:text-[#8E1B5C] hover:bg-white rounded-xl transition-colors border border-transparent hover:border-slate-200 shadow-xs shrink-0"
                                       title="Lihat di Storefront">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                        </svg>
                                    </a>
                                </template>
                            </div>

                            {{-- 3 Navigation Tabs (Pill style matching mockup) --}}
                            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mt-4">
                                <button type="button"
                                        @click="activeDrawerTab = 'info'"
                                        :class="activeDrawerTab === 'info' ? 'bg-[#8E1B5C] text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                                        class="py-1.5 px-4 text-xs rounded-xl transition-all">
                                    Informasi
                                </button>
                                <button type="button"
                                        @click="activeDrawerTab = 'variants'"
                                        :class="activeDrawerTab === 'variants' ? 'bg-[#8E1B5C] text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                                        class="py-1.5 px-4 text-xs rounded-xl transition-all inline-flex items-center gap-1.5">
                                    <span>Varian</span>
                                    <span :class="activeDrawerTab === 'variants' ? 'bg-white/20 text-white' : 'bg-[#F7E6EE] text-[#8E1B5C]'"
                                          class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                                          x-text="selectedProduct.variant_count"></span>
                                </button>
                                <button type="button"
                                        @click="activeDrawerTab = 'etalase'"
                                        :class="activeDrawerTab === 'etalase' ? 'bg-[#8E1B5C] text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                                        class="py-1.5 px-4 text-xs rounded-xl transition-all">
                                    Etalase
                                </button>
                            </div>

                            {{-- Tab 1: Informasi --}}
                            <div x-show="activeDrawerTab === 'info'" class="py-4 space-y-3">
                                <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-2.5 text-xs">
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span class="text-text-sec font-medium">Nama Produk</span>
                                        <span class="font-bold text-[#172B4D]" x-text="selectedProduct.name"></span>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span class="text-text-sec font-medium">Kode SKU</span>
                                        <span class="font-mono font-bold text-[#172B4D]" x-text="selectedProduct.code"></span>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span class="text-text-sec font-medium">Kategori Etalase</span>
                                        <span class="font-semibold text-[#8E1B5C]" x-text="selectedProduct.public_category || 'Tanpa Kategori'"></span>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span class="text-text-sec font-medium">Harga Etalase Publik</span>
                                        <span class="font-extrabold text-[#8E1B5C]" x-text="selectedProduct.public_price_formatted"></span>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-100">
                                        <span class="text-text-sec font-medium">Total Varian Tersedia</span>
                                        <span class="font-bold text-[#172B4D]" x-text="selectedProduct.variant_count + ' Varian'"></span>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <span class="text-text-sec font-medium">Status Etalase</span>
                                        <span class="font-bold" :class="selectedProduct.is_published ? 'text-emerald-600' : 'text-slate-500'"
                                              x-text="selectedProduct.is_published ? 'Terbit di Toko Publik' : 'Draft Internal ERP'"></span>
                                    </div>
                                </div>

                                <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-1.5 text-xs">
                                    <span class="text-text-sec font-semibold block">Deskripsi Publik Produk:</span>
                                    <p class="text-slate-700 leading-relaxed italic" x-text="selectedProduct.public_description"></p>
                                </div>
                            </div>

                            {{-- Tab 2: Varian List --}}
                            <div x-show="activeDrawerTab === 'variants'" class="py-4 space-y-2.5">
                                <template x-if="selectedProduct.variants && selectedProduct.variants.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="(v, index) in selectedProduct.variants" :key="v.id">
                                            <div class="p-3 bg-white hover:bg-slate-50/80 rounded-xl border border-slate-200/90 flex items-center justify-between gap-3 transition-colors">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    {{-- Variant Avatar Swatch --}}
                                                    <template x-if="v.image_url">
                                                        <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0 border border-[#E1CFD9] bg-white shadow-2xs">
                                                            <img :src="v.image_url" :alt="v.variant_name" class="w-full h-full object-cover">
                                                        </div>
                                                    </template>
                                                    <template x-if="!v.image_url">
                                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#FFF9FB] to-[#F7E6EE] border border-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0 font-bold text-xs shadow-xs">
                                                            <span x-text="v.variant_name.substring(0, 2).toUpperCase()"></span>
                                                        </div>
                                                    </template>
                                                    <div class="min-w-0">
                                                        <p class="font-bold text-xs text-[#172B4D] truncate" x-text="v.variant_name"></p>
                                                        <p class="text-[11px] text-text-sec font-mono mt-0.5" x-text="'SKU: ' + v.sku"></p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2.5 shrink-0">
                                                    <div class="text-right">
                                                        <p class="font-bold text-xs text-[#8E1B5C]" x-text="v.base_selling_price_formatted"></p>
                                                        <template x-if="v.active">
                                                             <span class="inline-block mt-0.5 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                                                 Aktif
                                                             </span>
                                                        </template>
                                                        <template x-if="!v.active">
                                                             <span class="inline-block mt-0.5 text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">
                                                                 Nonaktif
                                                             </span>
                                                        </template>
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <a :href="'{{ url('storefront/product') }}/' + selectedProduct.id + '?variant=' + v.id"
                                                           target="_blank"
                                                           class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-[#8E1B5C] bg-[#F7E6EE] hover:bg-[#8E1B5C] hover:text-white rounded-lg border border-[#FFD8D8] transition-colors shadow-2xs"
                                                           title="Buka varian ini di halaman katalog">
                                                            <span>Lihat Varian</span>
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                                            </svg>
                                                        </a>
                                                        <button type="button"
                                                                @click="openVariantPhotoModal(v)"
                                                                class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold text-slate-600 bg-white hover:bg-[#F7E6EE] hover:text-[#8E1B5C] rounded-lg border border-slate-200 transition-colors shadow-2xs"
                                                                title="Ubah Foto Varian">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                                                            </svg>
                                                            <span>Foto</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="!selectedProduct.variants || selectedProduct.variants.length === 0">
                                    <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                        <div class="w-12 h-12 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center mx-auto mb-2">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-700">Belum ada varian untuk produk ini</p>
                                        <p class="text-[11px] text-text-sec mt-1">Tambahkan varian di modul Produk Admin untuk mengaktifkan manajemen varian.</p>
                                    </div>
                                </template>
                            </div>

                            {{-- Tab 3: Etalase --}}
                            <div x-show="activeDrawerTab === 'etalase'" class="py-4 space-y-4 text-xs">
                                <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3.5 shadow-2xs">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                        <span class="font-bold text-[#172B4D]">Status Etalase Toko</span>
                                        <template x-if="selectedProduct.is_published">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>✓ Terbit Publik</span>
                                            </span>
                                        </template>
                                        <template x-if="!selectedProduct.is_published">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                <span>Internal / Draft</span>
                                            </span>
                                        </template>
                                    </div>

                                    {{-- Photo Preview --}}
                                    <div>
                                        <span class="text-text-sec font-semibold block mb-1.5">Foto Utama Etalase:</span>
                                        <div class="aspect-[16/10] w-full rounded-xl overflow-hidden bg-gradient-to-br from-[#FFF9FB] to-[#F7E6EE] border border-[#FFD8D8] flex items-center justify-center relative">
                                            <template x-if="selectedProduct.featured_image_url">
                                                <img :src="selectedProduct.featured_image_url" :alt="selectedProduct.name" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!selectedProduct.featured_image_url">
                                                <div class="flex flex-col items-center justify-center p-4 text-center select-none">
                                                    <svg class="w-8 h-8 text-[#8E1B5C]/70" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                    </svg>
                                                    <span class="text-xs font-semibold text-[#8E1B5C]/80 mt-1.5">Belum Ada Foto Etalase</span>
                                                    <span class="text-[10px] text-text-sec mt-0.5">Unggah foto via formulir Edit Etalase</span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 pt-1">
                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                            <span class="text-text-sec text-[11px] block">Kategori Publik</span>
                                            <span class="font-bold text-[#8E1B5C] text-xs mt-0.5 block truncate" x-text="selectedProduct.public_category || 'Tanpa Kategori'"></span>
                                        </div>
                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                            <span class="text-text-sec text-[11px] block">Harga Publik</span>
                                            <span class="font-bold text-[#8E1B5C] text-xs mt-0.5 block" x-text="selectedProduct.public_price_formatted"></span>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                        <span class="text-text-sec text-[11px] block mb-1">Deskripsi Produk di Etalase:</span>
                                        <p class="text-slate-700 italic leading-relaxed text-[11px]" x-text="selectedProduct.public_description"></p>
                                    </div>

                                    <div class="pt-2">
                                        <button type="button"
                                                @click="$dispatch('open-showcase-modal-' + selectedProduct.id); closeDrawer()"
                                                class="w-full py-2.5 px-4 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold rounded-xl text-center transition-colors shadow-xs inline-flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                            </svg>
                                            <span>Buka Form Edit Etalase</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Drawer Footer: Owner Actions --}}
                <div class="p-4 border-t border-slate-100 bg-white shrink-0 sticky bottom-0 z-10 flex items-center gap-2.5">
                    <button type="button"
                            @click="$dispatch('open-showcase-modal-' + selectedProduct.id); closeDrawer()"
                            class="flex-1 py-2.5 px-3 border border-[#FFD8D8] hover:border-[#8E1B5C] bg-[#FFF9FB] hover:bg-[#F7E6EE] text-[#8E1B5C] text-xs font-bold rounded-xl inline-flex items-center justify-center gap-1.5 transition-all shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                        </svg>
                        <span>Edit Produk &amp; Etalase</span>
                    </button>
                    <a :href="'{{ url('storefront/product') }}/' + selectedProduct.id"
                       target="_blank"
                       class="flex-1 py-2.5 px-3 bg-[#8E1B5C] hover:bg-[#701D49] text-white text-xs font-bold rounded-xl inline-flex items-center justify-center gap-1.5 transition-all shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                        <span>Lihat Produk</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 8. MODAL EDIT ETALASE (Rendered for each product, triggered cleanly) --}}
    @foreach($products as $product)
    <div x-data="{
        open: false,
        imagePreview: '{{ $product->featured_image ? asset('storage/' . $product->featured_image) : '' }}',
        hasExistingImage: {{ $product->featured_image ? 'true' : 'false' }},
        removeImage: false,
        fileChosen(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file foto maksimal 2MB.');
                event.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreview = e.target.result;
                this.removeImage = false;
            };
            reader.readAsDataURL(file);
        },
        clearImage() {
            this.imagePreview = '';
            this.removeImage = true;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },
        resetForm() {
            this.imagePreview = '{{ $product->featured_image ? asset('storage/' . $product->featured_image) : '' }}';
            this.removeImage = false;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        }
    }"
    @open-showcase-modal-{{ $product->id }}.window="open = true">

        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto"
             x-cloak>
            <div @click.away="open = false; resetForm()"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 text-left space-y-5 shadow-2xl border border-border-c my-8">

                <div class="flex items-start justify-between pb-3 border-b border-border-c">
                    <div>
                        <h3 class="text-base font-bold text-[#172B4D]">Edit Informasi Etalase Publik</h3>
                        <p class="text-xs text-text-sec mt-0.5">{{ $product->name }} &bull; SKU: {{ $product->product_code ?: $product->code }}</p>
                    </div>
                    <button type="button" @click="open = false; resetForm()" class="text-text-sec hover:text-text-main p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('owner.publications.update-showcase', $product->id) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">

                    {{-- Photo Upload & Preview --}}
                    <div>
                        <label class="form-label font-semibold text-[#172B4D]">Foto Produk untuk Etalase Publik</label>
                        <div class="flex items-start gap-4 p-3.5 bg-[#FFF9FB] border border-[#E1CFD9] rounded-2xl">
                            <div class="w-24 h-24 rounded-xl bg-white border border-[#E1CFD9] overflow-hidden shrink-0 flex items-center justify-center relative shadow-inner">
                                <template x-if="imagePreview">
                                    <img :src="imagePreview" alt="Preview Foto" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!imagePreview">
                                    <div class="text-center p-2 text-text-sec">
                                        <svg class="w-7 h-7 mx-auto text-[#8E1B5C]/40 mb-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                        <span class="text-[10px] leading-tight block">Tanpa Foto</span>
                                    </div>
                                </template>
                            </div>

                            <div class="flex-1 space-y-2 min-w-0">
                                <div>
                                    <input type="file"
                                           name="featured_image"
                                           x-ref="fileInput"
                                           @change="fileChosen"
                                           accept="image/jpeg,image/png,image/webp,image/jpg"
                                           class="block w-full text-xs text-[#64748B] file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#F7E6EE] file:text-[#8E1B5C] hover:file:bg-[#FFD8D8] cursor-pointer">
                                    <p class="text-[11px] text-[#64748B] mt-1">Format: JPG, JPEG, PNG, WEBP. Maks 2MB.</p>
                                </div>

                                <template x-if="imagePreview">
                                    <div class="flex items-center gap-2 pt-1">
                                        <button type="button"
                                                @click="clearImage"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg border border-red-200 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Hapus Foto</span>
                                        </button>
                                        <span x-show="removeImage" class="text-[11px] text-amber-700 font-medium">Foto akan dihapus saat disimpan</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Public Category --}}
                    <div>
                        <label class="block text-xs font-bold text-[#172B4D] mb-1">Kategori Publik</label>
                        <input type="text"
                               name="public_category"
                               value="{{ old('public_category', $product->public_category) }}"
                               placeholder="Contoh: Pashmina, Bergo, Segi Empat, Khimar"
                               class="w-full px-3 py-2 text-xs border border-border-c rounded-xl bg-card text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                        <p class="text-[11px] text-text-sec mt-1">Kategori yang ditampilkan kepada pengunjung di katalog toko.</p>
                    </div>

                    {{-- Public Price --}}
                    <div>
                        <label class="block text-xs font-bold text-[#172B4D] mb-1">Harga Publik (Rp)</label>
                        <input type="number"
                               step="500"
                               name="public_price"
                               value="{{ old('public_price', $product->public_price ? (int)$product->public_price : '') }}"
                               placeholder="Contoh: 45000"
                               class="w-full px-3 py-2 text-xs border border-border-c rounded-xl bg-card text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                        <p class="text-[11px] text-text-sec mt-1">Harga jual atau estimasi grosir untuk konsumen di etalase toko.</p>
                    </div>

                    {{-- Public Description --}}
                    <div>
                        <label class="block text-xs font-bold text-[#172B4D] mb-1">Deskripsi Publik untuk Konsumen</label>
                        <textarea name="public_description"
                                  rows="3"
                                  class="w-full px-3 py-2 text-xs border border-border-c rounded-xl bg-card text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                  placeholder="Jelaskan bahan kain, ukuran, keunggulan jilbab, atau instruksi pemesanan...">{{ old('public_description', $product->public_description) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-c">
                        <button type="button" @click="open = false; resetForm()" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-xs">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-xs font-bold text-white transition shadow-xs">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    {{-- ── 9. MODAL UBAH FOTO VARIAN ── --}}
    <div x-data="{
        open: false,
        selectedVariant: null,
        imagePreview: '',
        removeImage: false,
        init() {
            window.addEventListener('open-variant-photo-modal', (e) => {
                this.selectedVariant = e.detail;
                this.imagePreview = this.selectedVariant.image_url || '';
                this.removeImage = false;
                this.open = true;
            });
        },
        fileChosen(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file foto maksimal 2MB.');
                event.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreview = e.target.result;
                this.removeImage = false;
            };
            reader.readAsDataURL(file);
        },
        clearImage() {
            this.imagePreview = '';
            this.removeImage = true;
            if (this.$refs.variantFileInput) {
                this.$refs.variantFileInput.value = '';
            }
        }
    }"
    @keydown.escape.window="open = false">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
             x-cloak>
            <div @click.away="open = false"
                 class="bg-white rounded-3xl max-w-md w-full p-6 text-left space-y-4 shadow-2xl border border-slate-200">
                <template x-if="selectedVariant">
                    <div>
                        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h3 class="text-sm font-bold text-[#172B4D]">Ubah Foto Varian</h3>
                                <p class="text-xs text-[#8E1B5C] font-semibold mt-0.5" x-text="selectedVariant.variant_name + ' (SKU: ' + selectedVariant.sku + ')'"></p>
                            </div>
                            <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 p-1 font-bold">
                                ✕
                            </button>
                        </div>

                        <form :action="'{{ url('owner/publications/variants') }}/' + selectedVariant.id + '/image'"
                              method="POST"
                              enctype="multipart/form-data"
                              class="space-y-4 pt-3">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">

                            <div class="flex items-center gap-4 p-3 bg-[#FFF9FB] rounded-2xl border border-[#FFD8D8]">
                                <div class="w-20 h-20 rounded-xl bg-white border border-[#FFD8D8] overflow-hidden shrink-0 flex items-center justify-center">
                                    <template x-if="imagePreview">
                                        <img :src="imagePreview" alt="Preview Varian" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!imagePreview">
                                        <span class="text-[10px] text-slate-400 text-center p-1 font-medium">Belum Ada Foto</span>
                                    </template>
                                </div>
                                <div class="flex-1 space-y-2">
                                    <input type="file"
                                           name="image"
                                           x-ref="variantFileInput"
                                           @change="fileChosen"
                                           accept="image/jpeg,image/png,image/webp,image/jpg"
                                           class="block w-full text-xs text-[#64748B] file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-[#F7E6EE] file:text-[#8E1B5C] hover:file:bg-[#FFD8D8] cursor-pointer">
                                    <template x-if="imagePreview">
                                        <button type="button"
                                                @click="clearImage"
                                                class="text-[11px] font-semibold text-red-600 hover:underline block">
                                            Hapus Foto
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2.5 pt-2">
                                <button type="button" @click="open = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-xs">Batal</button>
                                <button type="submit" class="px-5 py-2 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-xs font-bold text-white transition shadow-xs">Simpan Foto Varian</button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
