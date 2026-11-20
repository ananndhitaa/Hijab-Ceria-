@extends('layouts.app')

@section('content')
<style>
    main { min-width: 0 !important; max-width: 100% !important; overflow-x: hidden; }
</style>

<div x-data="{
    viewMode: 'list',
    createModalOpen: false,
    modalMode: 'create',
    formAction: '{{ route('owner.ads.store') }}',
    adType: 'SHOP_AD',
    title: '',
    tagline: '',
    productId: '',
    bannerPreview: null,
    fileName: '',
    fileSize: '',
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
    openCreateModal(defaultType = 'SHOP_AD') {
        this.modalMode = 'create';
        this.formAction = '{{ route('owner.ads.store') }}';
        this.adType = defaultType;
        this.title = '';
        this.tagline = '';
        this.productId = '';
        this.bannerPreview = null;
        this.fileName = '';
        this.fileSize = '';
        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        this.createModalOpen = true;
    },
    openEditModal(camp) {
        this.modalMode = 'edit';
        this.formAction = '/owner/ads/' + camp.id + '/revise';
        this.adType = camp.ad_type;
        this.title = camp.title;
        this.tagline = camp.tagline || '';
        this.productId = camp.product_id || '';
        this.bannerPreview = camp.banner_url || null;
        this.fileName = camp.banner_path ? 'Gambar tersimpan saat ini' : '';
        this.fileSize = '';
        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        this.createModalOpen = true;
    },
    previewFile(event) {
        const file = event.target.files[0];
        if (file) {
            this.fileName = file.name;
            this.fileSize = (file.size / 1024).toFixed(1) + ' KB';
            this.bannerPreview = URL.createObjectURL(file);
        }
    },
    removeFile() {
        this.bannerPreview = null;
        this.fileName = '';
        this.fileSize = '';
        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
    }
}" class="space-y-6 sm:space-y-7 w-full min-w-0 max-w-full pb-10">

    {{-- ── 1. GLOBAL FEATURE HEADER WITH EXECUTIVE MONITORING BADGE ──────── --}}
    <x-feature-header
        title="Manajemen Iklan & Promosi Toko"
        subtitle="Kelola materi promosi toko dan produk unggulan di etalase NAYARA.">
        <x-slot:badge>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                EXECUTIVE MONITORING
            </span>
        </x-slot:badge>
        <x-slot:iconSlot>
            <div class="w-10 h-10 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px] text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
            </div>
        </x-slot:iconSlot>
        <x-slot:actions>
            <button type="button"
                    @click="openCreateModal('SHOP_AD')"
                    class="inline-flex items-center justify-center gap-2 h-10 px-4 sm:px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#68113F] transition shadow-xs">
                <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Buat Iklan Baru</span>
            </button>
        </x-slot:actions>
    </x-feature-header>

    {{-- ── FLASH ALERTS ─────────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="w-full bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 flex items-center gap-3 text-emerald-800 text-xs sm:text-sm font-medium shadow-xs">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="flex-1">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div class="w-full bg-amber-50 border border-amber-200 rounded-xl p-3.5 flex items-center gap-3 text-amber-800 text-xs sm:text-sm font-medium shadow-xs">
            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span class="flex-1">{{ session('warning') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="w-full bg-rose-50 border border-rose-200 rounded-xl p-3.5 flex items-center gap-3 text-rose-800 text-xs sm:text-sm font-medium shadow-xs">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span class="flex-1">{{ session('error') }}</span>
        </div>
    @endif

    {{-- ── 2. RINGKASAN PERFORMA (UNIFIED EXECUTIVE METRIC STRIP) ──────────── --}}
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs divide-y sm:divide-y-0 sm:divide-x divide-slate-100 grid grid-cols-2 lg:grid-cols-4 w-full">
        {{-- Metric 1: Total Kampanye Iklan --}}
        <div class="p-4 sm:p-5 flex flex-col justify-between">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Kampanye Iklan</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold text-[#172B4D] tabular-nums tracking-tight">{{ $totalCount }}</span>
            </div>
            <span class="text-[11px] text-slate-400 mt-1 truncate">{{ $shopAdsCount }} Toko &bull; {{ $productAdsCount }} Produk</span>
        </div>

        {{-- Metric 2: Sedang Tayang --}}
        <div class="p-4 sm:p-5 flex flex-col justify-between">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Sedang Tayang</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold {{ $activeCount > 0 ? 'text-emerald-600' : 'text-[#172B4D]' }} tabular-nums tracking-tight">{{ $activeCount }}</span>
                @if($activeCount > 0)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700">Live</span>
                @endif
            </div>
            <span class="text-[11px] text-slate-400 mt-1 truncate">Aktif di etalase publik</span>
        </div>

        {{-- Metric 3: Menunggu Moderasi / Jadwal --}}
        <div class="p-4 sm:p-5 flex flex-col justify-between">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Menunggu Moderasi / Jadwal</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold text-[#172B4D] tabular-nums tracking-tight">{{ $pendingCount }}</span>
            </div>
            <span class="text-[11px] text-slate-400 mt-1 truncate">Antrean review &amp; jadwal</span>
        </div>

        {{-- Metric 4: Total Anggaran Promosi --}}
        <div class="p-4 sm:p-5 flex flex-col justify-between">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Anggaran Promosi</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-extrabold text-[#8E1B5C] tabular-nums tracking-tight">Rp {{ number_format($totalInvested, 0, ',', '.') }}</span>
            </div>
            <span class="text-[11px] text-slate-400 mt-1 truncate">Alokasi terverifikasi</span>
        </div>
    </div>

    {{-- ── 3. DUA PANEL LAYANAN IKLAN (RINGKAS & ELEGAN) ────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
        {{-- Panel 1: Iklan Toko (Banner Utama) --}}
        <div class="bg-white rounded-xl border border-slate-200/90 border-l-4 border-l-[#8E1B5C] p-4 sm:p-4.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E1B5C] bg-[#F7E6EE] px-2 py-0.5 rounded border border-[#FFD8D8]">
                        BANNER UTAMA
                    </span>
                    <span class="text-xs text-slate-500">Kapasitas: <strong class="text-slate-800">Maks. 5 Toko</strong></span>
                </div>
                <h4 class="text-sm font-bold text-[#172B4D] mt-1">Iklan Toko — Homepage Showcase</h4>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">Rotasi banner 7 detik di posisi teratas homepage untuk eksposur maksimal toko.</p>
            </div>
            <button type="button"
                    @click="openCreateModal('SHOP_AD')"
                    class="shrink-0 h-9 px-4 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold inline-flex items-center gap-1.5 transition shadow-xs justify-center">
                <span>Pasang Iklan Toko</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </button>
        </div>

        {{-- Panel 2: Iklan Produk (Etalase Unggulan) --}}
        <div class="bg-white rounded-xl border border-slate-200/90 border-l-4 border-l-amber-500 p-4 sm:p-4.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                        ETALASE UNGGULAN
                    </span>
                    <span class="text-xs text-slate-500">Kapasitas: <strong class="text-slate-800">Maks. 6 Produk</strong></span>
                </div>
                <h4 class="text-sm font-bold text-[#172B4D] mt-1">Iklan Produk — Sponsor Katalog</h4>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">Kartu sponsor di posisi prioritas katalog publik untuk mendorong penjualan langsung.</p>
            </div>
            <button type="button"
                    @click="openCreateModal('PRODUCT_AD')"
                    class="shrink-0 h-9 px-4 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold inline-flex items-center gap-1.5 transition shadow-xs justify-center">
                <span>Pasang Iklan Produk</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </button>
        </div>
    </div>

    {{-- ── 4. CAMPAIGN WORKSPACE (AREA UTAMA PENGELOLAAN IKLAN) ──────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-card overflow-hidden w-full">
        {{-- Toolbar Rapi Satu Baris --}}
        <div class="px-5 py-3.5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            {{-- Filter Tabs --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                <a href="{{ route('owner.ads.index') }}"
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all whitespace-nowrap {{ empty($typeFilter) ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#F7E6EE] text-[#8E1B5C] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                    Semua Tipe ({{ $totalCount }})
                </a>
                <a href="{{ route('owner.ads.index', ['type' => 'SHOP_AD']) }}"
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all whitespace-nowrap {{ $typeFilter === 'SHOP_AD' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#F7E6EE] text-[#8E1B5C] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                    Iklan Toko (Banner) ({{ $shopAdsCount }})
                </a>
                <a href="{{ route('owner.ads.index', ['type' => 'PRODUCT_AD']) }}"
                   class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all whitespace-nowrap {{ $typeFilter === 'PRODUCT_AD' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#F7E6EE] text-[#8E1B5C] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                    Iklan Produk ({{ $productAdsCount }})
                </a>
            </div>

            {{-- View Mode Toggle --}}
            <div class="flex items-center bg-slate-100 p-0.5 rounded-lg border border-slate-200 shrink-0 self-end sm:self-auto">
                <button type="button"
                        @click="switchView('list')"
                        :class="viewMode === 'list' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="p-1.5 rounded-md transition-all"
                        title="Tampilan Tabel">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
                <button type="button"
                        @click="switchView('grid')"
                        :class="viewMode === 'grid' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="p-1.5 rounded-md transition-all"
                        title="Tampilan Grid">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── 5. EMPTY STATE (COMPACT & ELEGAN DI DALAM WORKSPACE) ─────────── --}}
        @if($campaigns->isEmpty())
            <div class="py-12 px-6 text-center">
                <div class="w-11 h-11 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center mx-auto mb-2.5 shadow-2xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Belum Ada Kampanye Iklan</h3>
                <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">Mulai promosikan toko atau produk unggulan Anda.</p>
                <div class="mt-3.5">
                    <button type="button"
                            @click="openCreateModal('SHOP_AD')"
                            class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold transition shadow-xs">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span>Buat Iklan Pertama</span>
                    </button>
                </div>
            </div>
        @else
            {{-- ── 6. LIST VIEW (TABEL 100% FULL-WIDTH) ─────────────────────── --}}
            <div x-show="viewMode === 'list'"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="w-full">
                <div x-ref="tableWrapper" class="overflow-x-auto w-full max-w-full min-w-0">
                    <table class="w-full text-left border-collapse min-w-[760px]">
                        <thead>
                            <tr class="bg-[#F7E4EC] border-b border-[#EAD0DC] text-[11px] font-bold text-[#53677F] uppercase tracking-wider">
                                <th class="py-3 px-5 w-4/12">Materi Iklan</th>
                                <th class="py-3 px-4 w-2/12">Tipe Iklan</th>
                                <th class="py-3 px-4 w-2/12">Jadwal Tayang</th>
                                <th class="py-3 px-4 w-2/12">Anggaran / Bid</th>
                                <th class="py-3 px-4 w-1/12">Status</th>
                                <th class="py-3 px-5 w-1/12 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                            @foreach($campaigns as $camp)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-[#F7E6EE] border border-[#FFD8D8] overflow-hidden shrink-0 flex items-center justify-center shadow-2xs">
                                            @if($camp->banner_path)
                                                <img src="{{ asset('storage/' . $camp->banner_path) }}" alt="{{ $camp->title }}" class="w-full h-full object-cover">
                                            @else
                                                <svg class="w-5 h-5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-sm text-[#172B4D] truncate max-w-xs">{{ $camp->title }}</p>
                                            <p class="text-xs text-[#64748B] truncate max-w-xs mt-0.5">{{ $camp->tagline ?: 'Tidak ada tagline promosi' }}</p>
                                            @if($camp->product)
                                                <span class="inline-flex items-center gap-1 text-[11px] text-[#8E1B5C] font-semibold mt-0.5">
                                                    <span>Produk:</span>
                                                    <span class="truncate max-w-[180px]">{{ $camp->product->name }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($camp->isShopAd())
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C]"></span>
                                            <span>Iklan Toko (Banner)</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>Iklan Produk (Etalase)</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-xs text-[#64748B]">
                                    @if($camp->starts_at && $camp->ends_at)
                                        <p class="font-semibold text-[#172B4D]">{{ $camp->starts_at->format('d M Y') }} &ndash; {{ $camp->ends_at->format('d M Y') }}</p>
                                        <p class="text-[11px] text-[#64748B] mt-0.5">{{ $camp->duration_days }} Hari ({{ $camp->starts_at->format('H:i') }} WIB)</p>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-slate-400 italic text-[11px]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Belum dipesan</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($camp->total_budget)
                                        <p class="font-bold text-sm text-[#8E1B5C] tabular-nums">Rp {{ number_format($camp->total_budget, 0, ',', '.') }}</p>
                                        <p class="text-[11px] text-[#64748B] mt-0.5">Bid: Rp {{ number_format($camp->effective_bid_per_day, 0, ',', '.') }}/hari</p>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($camp->status === 'ACTIVE')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>Sedang Tayang</span>
                                        </span>
                                    @elseif($camp->status === 'SCHEDULED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                            <span>Terjadwal</span>
                                        </span>
                                    @elseif($camp->status === 'PENDING_REVIEW')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>Menunggu Review</span>
                                        </span>
                                    @elseif($camp->status === 'APPROVED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Disetujui</span>
                                        </span>
                                    @elseif($camp->status === 'NEEDS_REVISION')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span>Perlu Revisi</span>
                                        </span>
                                    @elseif($camp->status === 'PAYMENT_PENDING')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>Menunggu Pembayaran</span>
                                        </span>
                                    @elseif($camp->status === 'COMPLETED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            <span>Selesai</span>
                                        </span>
                                    @elseif($camp->status === 'SUSPENDED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span>Ditangguhkan</span>
                                        </span>
                                    @elseif($camp->status === 'CANCELLED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            <span>Dibatalkan</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            {{ $camp->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if(in_array($camp->status, ['DRAFT', 'NEEDS_REVISION']))
                                        <button type="button"
                                                @click="openEditModal({
                                                    id: {{ $camp->id }},
                                                    ad_type: '{{ $camp->ad_type }}',
                                                    title: '{{ addslashes($camp->title) }}',
                                                    tagline: '{{ addslashes($camp->tagline ?? '') }}',
                                                    product_id: '{{ $camp->product_id }}',
                                                    banner_path: '{{ $camp->banner_path }}',
                                                    banner_url: '{{ $camp->banner_path ? asset('storage/' . $camp->banner_path) : '' }}'
                                                })"
                                                class="h-8 px-3 rounded-lg text-xs font-semibold border border-[#FFD8D8] bg-[#FFF9FB] hover:bg-[#F7E6EE] text-[#8E1B5C] transition-colors shadow-2xs">
                                            Edit
                                        </button>
                                        @endif
                                        <a href="{{ route('owner.ads.show', $camp->id) }}"
                                           class="inline-flex items-center gap-1 h-8 px-3 rounded-lg text-xs font-semibold border border-slate-200 bg-white hover:bg-slate-50 text-[#172B4D] hover:text-[#8E1B5C] transition-colors shadow-2xs">
                                            <span>Detail</span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($campaigns->hasPages())
                <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                    {{ $campaigns->links() }}
                </div>
                @endif
            </div>

            {{-- ── 7. GRID VIEW (TAMPILAN KARTU) ────────────────────────────── --}}
            <div x-show="viewMode === 'grid'"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="p-4 sm:p-5 w-full">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 w-full">
                    @foreach($campaigns as $camp)
                    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs hover:border-[#8E1B5C]/30 transition-all flex flex-col justify-between overflow-hidden">
                        <div>
                            {{-- Banner Thumbnail with Overlay Badges --}}
                            <div class="relative aspect-video bg-slate-100 overflow-hidden border-b border-slate-100">
                                @if($camp->banner_path)
                                    <img src="{{ asset('storage/' . $camp->banner_path) }}" alt="{{ $camp->title }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-[#F7E6EE] flex items-center justify-center text-[#8E1B5C]">
                                        <svg class="w-8 h-8 opacity-60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                    </div>
                                @endif

                                <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5">
                                    @if($camp->isShopAd())
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#8E1B5C] text-white shadow-xs">
                                            IKLAN TOKO
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-600 text-white shadow-xs">
                                            IKLAN PRODUK
                                        </span>
                                    @endif
                                </div>

                                <div class="absolute top-2.5 right-2.5">
                                    @if($camp->status === 'ACTIVE')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white shadow-xs">
                                            SEDANG TAYANG
                                        </span>
                                    @elseif($camp->status === 'SCHEDULED')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-600 text-white shadow-xs">
                                            TERJADWAL
                                        </span>
                                    @elseif($camp->status === 'PENDING_REVIEW')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-600 text-white shadow-xs">
                                            REVIEW
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-900/80 text-white shadow-xs">
                                            {{ $camp->status }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Card Body --}}
                            <div class="p-4 space-y-2.5">
                                <div>
                                    <h4 class="font-bold text-sm text-[#172B4D] line-clamp-1">{{ $camp->title }}</h4>
                                    <p class="text-xs text-[#64748B] line-clamp-2 mt-0.5">{{ $camp->tagline ?: 'Tidak ada tagline promosi' }}</p>
                                </div>

                                @if($camp->product)
                                <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase text-[#8E1B5C] bg-[#F7E6EE] px-1.5 py-0.5 rounded">Produk</span>
                                    <span class="text-xs font-semibold text-[#172B4D] truncate">{{ $camp->product->name }}</span>
                                </div>
                                @endif

                                <div class="space-y-1 text-xs text-[#64748B] pt-2 border-t border-slate-100">
                                    <div class="flex items-center justify-between">
                                        <span>Jadwal:</span>
                                        <strong class="text-[#172B4D]">
                                            @if($camp->starts_at && $camp->ends_at)
                                                {{ $camp->starts_at->format('d M') }} &ndash; {{ $camp->ends_at->format('d M Y') }}
                                            @else
                                                Belum dipesan
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Anggaran:</span>
                                        <strong class="text-[#8E1B5C] tabular-nums">
                                            @if($camp->total_budget)
                                                Rp {{ number_format($camp->total_budget, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-50/60 border-t border-slate-100 flex items-center justify-between gap-2">
                            @if(in_array($camp->status, ['DRAFT', 'NEEDS_REVISION']))
                            <button type="button"
                                    @click="openEditModal({
                                        id: {{ $camp->id }},
                                        ad_type: '{{ $camp->ad_type }}',
                                        title: '{{ addslashes($camp->title) }}',
                                        tagline: '{{ addslashes($camp->tagline ?? '') }}',
                                        product_id: '{{ $camp->product_id }}',
                                        banner_path: '{{ $camp->banner_path }}',
                                        banner_url: '{{ $camp->banner_path ? asset('storage/' . $camp->banner_path) : '' }}'
                                    })"
                                    class="h-8 px-3 rounded-lg border border-[#FFD8D8] bg-white hover:bg-[#F7E6EE] text-[#8E1B5C] text-xs font-semibold transition-colors shadow-2xs">
                                Edit Materi
                            </button>
                            @endif
                            <a href="{{ route('owner.ads.show', $camp->id) }}"
                               class="flex-1 h-8 px-3 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-[#172B4D] hover:text-[#8E1B5C] text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors shadow-2xs">
                                <span>Detail Kampanye</span>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($campaigns->hasPages())
                <div class="mt-4 p-3.5 border-t border-slate-100 bg-slate-50/50">
                    {{ $campaigns->links() }}
                </div>
                @endif
            </div>
        @endif
    </div>

    {{-- ── 8. MODAL BUAT & EDIT IKLAN (FLAWLESS FLEX ARCHITECTURE) ──────────── --}}
    <div x-show="createModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/50 backdrop-blur-xs"
         @keydown.escape.window="createModalOpen = false">

        <div @click.away="createModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl w-full max-w-[720px] max-h-[90vh] sm:max-h-[85vh] border border-[#E1CFD9] shadow-2xl flex flex-col overflow-hidden">

            {{-- Modal Header (FIXED TOP, flex-shrink-0) --}}
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between flex-shrink-0 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-[#172B4D]" x-text="modalMode === 'edit' ? 'Edit Materi Iklan' : 'Buat Materi Iklan Baru'"></h3>
                        <p class="text-[11px] text-[#64748B]">Materi akan dimoderasi oleh tim Super Admin NAYARA.</p>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form Wrapping Body AND Footer with flex column --}}
            <form :action="formAction" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                <template x-if="modalMode === 'edit'">
                    <input type="hidden" name="revision_type" value="CREATIVE">
                </template>

                {{-- Modal Form Body (THE ONLY SCROLLING AREA, flex-1 min-h-0 overflow-y-auto) --}}
                <div class="flex-1 min-h-0 overflow-y-auto p-5 sm:p-6 space-y-4">
                    {{-- Field 1: Tipe Layanan Iklan --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">Tipe Layanan Iklan <span class="text-rose-600">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="p-3.5 rounded-xl border cursor-pointer transition-all flex items-start gap-3"
                                   :class="adType === 'SHOP_AD' ? 'border-[#8E1B5C] bg-[#FFFBFD] ring-1 ring-[#8E1B5C]/20 shadow-2xs' : 'border-slate-200 bg-white hover:border-slate-300'">
                                <input type="radio" name="ad_type" value="SHOP_AD" x-model="adType" class="sr-only">
                                <div class="w-7 h-7 rounded-lg bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009 9.35c.695 0 1.345-.236 1.868-.636.523.4 1.173.636 1.868.636s1.345-.236 1.868-.636c.523.4 1.173.636 1.868.636a3.001 3.001 0 003.75.615z" /></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-[#172B4D]">Iklan Toko (Banner)</p>
                                    <p class="text-[11px] text-[#64748B] mt-0.5 leading-snug">Rotasi banner 7 detik di homepage showcase (Maks. 5 toko).</p>
                                </div>
                            </label>

                            <label class="p-3.5 rounded-xl border cursor-pointer transition-all flex items-start gap-3"
                                   :class="adType === 'PRODUCT_AD' ? 'border-amber-500 bg-amber-50/20 ring-1 ring-amber-500/20 shadow-2xs' : 'border-slate-200 bg-white hover:border-slate-300'">
                                <input type="radio" name="ad_type" value="PRODUCT_AD" x-model="adType" class="sr-only">
                                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-[#172B4D]">Iklan Produk (Etalase)</p>
                                    <p class="text-[11px] text-[#64748B] mt-0.5 leading-snug">Slot kartu sponsor teratas katalog produk (Maks. 6 produk).</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Field 2: Pilih Produk Publik (HANYA MUNCUL JIKA PRODUCT_AD) --}}
                    <div x-show="adType === 'PRODUCT_AD'" x-cloak class="space-y-1.5">
                        <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                            Pilih Produk Publik yang Diiklankan <span class="text-rose-600">*</span>
                        </label>
                        <select name="product_id"
                                x-model="productId"
                                :required="adType === 'PRODUCT_AD'"
                                class="w-full h-10 px-3 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
                            <option value="">-- Pilih Produk Terbit di Etalase --</option>
                            @foreach($publishedProducts as $prod)
                                <option value="{{ $prod->id }}">
                                    {{ $prod->name }} (Harga: Rp {{ number_format($prod->public_price ?? 0, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-[#64748B]">Hanya produk berstatus aktif terbit di etalase toko yang dapat diiklankan.</p>
                    </div>

                    {{-- Field 3: Judul Iklan --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                            Judul Iklan / Kampanye <span class="text-rose-600">*</span>
                        </label>
                        <input type="text"
                               name="title"
                               x-model="title"
                               required
                               maxlength="150"
                               placeholder="Contoh: Produsen Hijab Voal & Pashmina Grosir Premium"
                               class="w-full h-10 px-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
                    </div>

                    {{-- Field 4: Tagline --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                            Tagline / Pesan Promosi
                        </label>
                        <input type="text"
                               name="tagline"
                               x-model="tagline"
                               maxlength="255"
                               placeholder="Contoh: Bahan Premium Grade A, Jahitan Standar Butik, Siap Kirim Seluruh Indonesia"
                               class="w-full h-10 px-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
                    </div>

                    {{-- Field 5: Upload Materi Iklan Dirancang Khusus (Custom Dropzone) --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                            Materi Banner Iklan <span class="text-rose-600" x-show="modalMode === 'create'">*</span>
                        </label>
                        <input type="file"
                               name="banner_file"
                               x-ref="fileInput"
                               :required="modalMode === 'create' && !bannerPreview"
                               accept="image/jpeg,image/png,image/webp"
                               @change="previewFile"
                               class="sr-only">

                        {{-- State A: Belum ada gambar dipilih --}}
                        <div x-show="!bannerPreview"
                             @click="$refs.fileInput.click()"
                             class="border-2 border-dashed border-slate-200 hover:border-[#8E1B5C] bg-[#FFFBFD]/40 hover:bg-[#FFFBFD] rounded-xl p-5 text-center cursor-pointer transition-colors group">
                            <div class="w-10 h-10 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center mx-auto mb-2 group-hover:scale-105 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                            </div>
                            <p class="text-xs font-semibold text-[#172B4D]">
                                <span class="text-[#8E1B5C] underline font-bold">Pilih file gambar</span> atau drag &amp; drop ke sini
                            </p>
                            <p class="text-[11px] text-[#64748B] mt-1">
                                Format: JPG, PNG, WEBP (Maks. 2MB). Ideal: 1200&times;500 px (Iklan Toko) / 800&times;800 px (Iklan Produk)
                            </p>
                        </div>

                        {{-- State B: Gambar telah dipilih / ada pratinjau --}}
                        <div x-show="bannerPreview" x-cloak class="p-3 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-16 h-12 rounded-lg overflow-hidden border border-slate-200 bg-white shrink-0 flex items-center justify-center">
                                    <img :src="bannerPreview" alt="Pratinjau" class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-[#172B4D] truncate" x-text="fileName || 'banner-iklan.jpg'"></p>
                                    <p class="text-[11px] text-[#64748B]" x-text="fileSize || 'Gambar terpilih'"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" @click="$refs.fileInput.click()" class="text-xs font-semibold text-[#8E1B5C] hover:underline px-2 py-1">
                                    Ganti
                                </button>
                                <span class="text-slate-300">|</span>
                                <button type="button" @click="removeFile()" class="text-xs font-semibold text-rose-600 hover:underline px-2 py-1">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer (FIXED BOTTOM, flex-shrink-0) --}}
                <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/80 flex items-center justify-end gap-2.5 flex-shrink-0">
                    <button type="button"
                            @click="createModalOpen = false"
                            class="h-9 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-[#172B4D] text-xs font-semibold transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="h-9 px-5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold transition-colors shadow-xs">
                        Simpan &amp; Ajukan Peninjauan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
