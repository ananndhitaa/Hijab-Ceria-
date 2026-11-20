@extends('platform.layouts.app', ['title' => 'Moderasi & Peninjauan Materi Iklan'])

@section('content')
<div class="space-y-6">

    {{-- 1. HERO BANNER: Dark Burgundy Luxury + 3D Glass Tablet with "ADS" Screen --}}
    <section class="rounded-2xl px-5 py-3.5 sm:px-6 sm:py-4 text-white relative overflow-hidden shadow-lg border border-white/10"
             style="background: linear-gradient(135deg, #691B3D 0%, #4A132E 55%, #350920 100%);">

        {{-- Ambient wave circles behind --}}
        <div class="absolute -right-16 -top-24 w-96 h-96 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
        <div class="absolute right-48 -bottom-20 w-80 h-80 rounded-full bg-[#8E1B5C]/20 blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-4">
            {{-- Left Hero Copy --}}
            <div class="space-y-2 max-w-xl">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-xs">
                        <svg class="w-4 h-4"><use href="#i-megaphone"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15" data-i18n="ad_hero_badge">
                        REVIEW IKLAN
                    </span>
                </div>

                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-tight" data-i18n="ad_hero_title">
                        Kelola &amp; Tinjau Iklan dengan Mudah
                    </h1>
                    <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5" data-i18n="ad_hero_subtitle">
                        Pastikan setiap iklan sesuai dengan kebijakan NAYARA untuk pengalaman pengguna yang lebih baik.
                    </p>
                </div>

                <div class="pt-0.5">
                    <button type="button" onclick="document.getElementById('adGuidelinesModal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white text-[#8E1B5C] hover:bg-[#FFF5F8] text-xs font-bold transition shadow-xs cursor-pointer group">
                        <svg class="w-3.5 h-3.5 text-[#8E1B5C]"><use href="#i-clipboard"/></svg>
                        <span data-i18n="ad_btn_guide">Lihat Panduan Review</span>
                        <span class="text-xs group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </button>
                </div>
            </div>

            {{-- Right Hero: 3D Perspective Floating Glass Tablet with "ADS" Board (Sesuaikan instruksi user) --}}
            <div class="shrink-0 w-full md:w-auto flex justify-center md:justify-end glass-tablet-3d-wrap py-0">
                <div class="glass-tablet-3d w-72 sm:w-80 p-3.5 sm:p-4 relative overflow-hidden flex items-center justify-between gap-4">
                    {{-- Left: Nayara Icon Badge --}}
                    <div class="flex flex-col items-center justify-center shrink-0 w-20 sm:w-24">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#E11D48] via-[#ba4e77] to-[#8E1B5C] border border-white/40 flex flex-col items-center justify-center shadow-md p-1">
                            <span class="font-serif font-black text-white text-xl leading-none italic select-none">n</span>
                            <span class="text-[6.5px] font-extrabold text-[#FFD8D8] tracking-widest lowercase -mt-0.5 select-none">nayara</span>
                        </div>
                        <div class="w-full space-y-1 mt-2 flex flex-col items-center">
                            <div class="w-16 h-1.5 bg-white/40 rounded-full"></div>
                            <div class="w-10 h-1 bg-white/20 rounded-full"></div>
                        </div>
                    </div>

                    {{-- Right Screen: "ADS" Header + Marketing Performance Bars --}}
                    <div class="flex-1 bg-white/10 border border-white/20 rounded-xl p-2.5 sm:p-3 backdrop-blur-md shadow-inner flex flex-col justify-between h-22 sm:h-24">
                        <div class="flex items-center justify-between gap-1.5">
                            <span class="text-xs sm:text-[13px] font-black tracking-widest text-white px-2 py-0.5 rounded-md bg-white/25 border border-white/35 shadow-xs select-none">ADS</span>
                            <div class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-[8.5px] font-extrabold text-[#FFD8D8] uppercase tracking-wider">Campaign</span>
                            </div>
                        </div>

                        <div class="flex items-end justify-between gap-2 h-10 px-1 pt-1">
                            <div class="w-4 sm:w-4.5 h-[35%] bg-gradient-to-t from-white/20 to-white/50 rounded-t-sm shadow-xs"></div>
                            <div class="w-4 sm:w-4.5 h-[55%] bg-gradient-to-t from-white/25 to-white/60 rounded-t-sm shadow-xs"></div>
                            <div class="w-4 sm:w-4.5 h-[75%] bg-gradient-to-t from-white/30 to-white/75 rounded-t-sm shadow-xs"></div>
                            <div class="w-4 sm:w-4.5 h-[100%] bg-gradient-to-t from-[#FFD8D8]/60 to-white/95 rounded-t-sm shadow-sm"></div>
                            <div class="w-4 sm:w-4.5 h-[65%] bg-gradient-to-t from-white/25 to-white/65 rounded-t-sm shadow-xs"></div>
                        </div>

                        <div class="w-full h-0.5 bg-white/25 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. 5 KPI CARDS: Matching Image 1 (Perlu Review, Disetujui, Sedang Tayang, Ditolak, Total Iklan) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        {{-- Card 1: Perlu Review --}}
        <a href="{{ route('platform.ads.index', ['status' => 'PENDING_REVIEW']) }}"
           class="kpi-card bg-white rounded-2xl border {{ $status === 'PENDING_REVIEW' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-[#F0E4EA]' }} p-4 pb-4.5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[135px]">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_ad_review">PERLU REVIEW</span>
                <span class="w-6 h-6 rounded-full bg-amber-50 text-amber-500 border border-amber-200 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5"><use href="#i-clock"/></svg>
                </span>
            </div>
            <div class="mt-2 relative z-10">
                <div class="text-[26px] sm:text-[28px] font-black text-amber-500 tracking-tight leading-none">{{ number_format($counts['pending'] ?? 0, 0, ',', '.') }}</div>
                <div class="text-[10.5px] text-slate-500 font-medium leading-normal mt-1" data-i18n="kpi_ad_review_sub">Menunggu verifikasi iklan masuk</div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-2.5"></div>
            </div>
            <svg class="card-watermark-lg" aria-hidden="true"><use href="#i-wm-clipboard"/></svg>
        </a>

        {{-- Card 2: Disetujui --}}
        <a href="{{ route('platform.ads.index', ['status' => 'APPROVED']) }}"
           class="kpi-card bg-white rounded-2xl border {{ $status === 'APPROVED' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-[#F0E4EA]' }} p-4 pb-4.5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[135px]">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_ad_approved">DISETUJUI</span>
                <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-500 border border-blue-200 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5"><use href="#i-check"/></svg>
                </span>
            </div>
            <div class="mt-2 relative z-10">
                <div class="text-[26px] sm:text-[28px] font-black text-blue-600 tracking-tight leading-none">{{ number_format($counts['approved'] ?? 0, 0, ',', '.') }}</div>
                <div class="text-[10.5px] text-slate-500 font-medium leading-normal mt-1" data-i18n="kpi_ad_approved_sub">Iklan siap tayang</div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-2.5"></div>
            </div>
            <svg class="card-watermark-lg" aria-hidden="true"><use href="#i-wm-file-edit"/></svg>
        </a>

        {{-- Card 3: Sedang Tayang --}}
        <a href="{{ route('platform.ads.index', ['status' => 'ACTIVE']) }}"
           class="kpi-card bg-white rounded-2xl border {{ $status === 'ACTIVE' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-[#F0E4EA]' }} p-4 pb-4.5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[135px]">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_ad_active">SEDANG TAYANG</span>
                <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-500 border border-emerald-200 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </span>
            </div>
            <div class="mt-2 relative z-10">
                <div class="text-[26px] sm:text-[28px] font-black text-emerald-600 tracking-tight leading-none">{{ number_format($counts['active'] ?? 0, 0, ',', '.') }}</div>
                <div class="text-[10.5px] text-slate-500 font-medium leading-normal mt-1" data-i18n="kpi_ad_active_sub">Iklan aktif di platform</div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-2.5"></div>
            </div>
            <svg class="card-watermark-lg" aria-hidden="true"><use href="#i-wm-store"/></svg>
        </a>

        {{-- Card 4: Ditolak --}}
        <a href="{{ route('platform.ads.index', ['status' => 'REJECTED']) }}"
           class="kpi-card bg-white rounded-2xl border {{ $status === 'REJECTED' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-[#F0E4EA]' }} p-4 pb-4.5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[135px]">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_ad_rejected">DITOLAK</span>
                <span class="w-6 h-6 rounded-full bg-rose-50 text-rose-500 border border-rose-200 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5"><use href="#i-cross"/></svg>
                </span>
            </div>
            <div class="mt-2 relative z-10">
                <div class="text-[26px] sm:text-[28px] font-black text-rose-600 tracking-tight leading-none">{{ number_format($counts['rejected'] ?? 0, 0, ',', '.') }}</div>
                <div class="text-[10.5px] text-slate-500 font-medium leading-normal mt-1" data-i18n="kpi_ad_rejected_sub">Tidak sesuai kebijakan</div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-2.5"></div>
            </div>
            <svg class="card-watermark-lg" aria-hidden="true"><use href="#i-wm-ban"/></svg>
        </a>

        {{-- Card 5: Total Iklan --}}
        <a href="{{ route('platform.ads.index', ['status' => 'all']) }}"
           class="kpi-card bg-white rounded-2xl border {{ ($status === 'all' || empty($status)) ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-[#F0E4EA]' }} p-4 pb-4.5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[135px] col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_ad_total">TOTAL IKLAN</span>
                <span class="w-6 h-6 rounded-full bg-purple-50 text-[#8E1B5C] border border-[#F0D5E2] flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5"><use href="#i-grid"/></svg>
                </span>
            </div>
            <div class="mt-2 relative z-10">
                <div class="text-[26px] sm:text-[28px] font-black text-[#172B4D] tracking-tight leading-none">{{ number_format($counts['all'] ?? 0, 0, ',', '.') }}</div>
                <div class="text-[10.5px] text-slate-500 font-medium leading-normal mt-1" data-i18n="kpi_ad_total_sub">Seluruh pengajuan iklan</div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-2.5"></div>
            </div>
            <svg class="card-watermark-lg" aria-hidden="true"><use href="#i-wm-store"/></svg>
        </a>
    </div>

    {{-- 3. FILTER STATUS TABS (Pills Matching Image 1) --}}
    @php
        $tabs = [
            'PENDING_REVIEW' => ['label' => 'Perlu Review', 'count' => $counts['pending'] ?? 0, 'key' => 'pending'],
            'APPROVED'       => ['label' => 'Disetujui',     'count' => $counts['approved'] ?? 0, 'key' => 'approved'],
            'ACTIVE'         => ['label' => 'Sedang Tayang', 'count' => $counts['active'] ?? 0, 'key' => 'active'],
            'REJECTED'       => ['label' => 'Ditolak',       'count' => $counts['rejected'] ?? 0, 'key' => 'rejected'],
            'all'            => ['label' => 'Semua Status',  'count' => $counts['all'] ?? 0, 'key' => 'all'],
        ];
    @endphp

    <div class="flex items-center gap-2 overflow-x-auto pb-1" aria-label="Filter status kampanye iklan">
        @foreach($tabs as $tabVal => $t)
            @php
                $isActive = ($status === $tabVal || ($tabVal === 'all' && empty($status)));
                $params = array_filter([
                    'status'   => $tabVal === 'all' ? 'all' : $tabVal,
                    'ad_type'  => $adType,
                    'outlet_id'=> $outletId,
                    'search'   => $search,
                ]);
            @endphp
            <a href="{{ route('platform.ads.index', $params) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl transition-all shrink-0 {{ $isActive ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-white text-slate-600 hover:text-[#8E1B5C] hover:border-[#8E1B5C] border border-[#F0E4EA] shadow-2xs' }}"
               @if($isActive) aria-current="page" @endif>
                <span data-i18n="tab_ad_{{ $t['key'] }}">{{ $t['label'] }}</span>
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-extrabold rounded-full {{ $isActive ? 'bg-white/20 text-white' : 'bg-[#FAF0F5] text-[#8E1B5C]' }}">
                    {{ number_format($t['count'], 0, ',', '.') }}
                </span>
            </a>
        @endforeach
    </div>

    {{-- 4. FILTER BAR: Horizontal Filter Matching Image 1 --}}
    <div class="bg-white rounded-2xl border border-[#F0E4EA] p-3 sm:p-3.5 shadow-2xs">
        <form method="GET" action="{{ route('platform.ads.index') }}" class="flex flex-wrap items-center gap-2.5 sm:gap-3">
            @if(!empty($status) && $status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif

            {{-- Search Input --}}
            <div class="relative flex-1 min-w-[200px]">
                <input type="text"
                       name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="Cari nama iklan, tenant, atau produk..."
                       data-i18n-placeholder="ad_filter_search_ph"
                       class="w-full bg-[#FFF9FB]/70 border border-[#F0E4EA] rounded-xl text-xs text-[#172B4D] pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] transition-all">
                <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
            </div>

            {{-- Filter Jenis Iklan --}}
            <div class="relative min-w-[150px]">
                <select name="ad_type" class="w-full appearance-none bg-[#FFF9FB]/70 hover:bg-[#FFF5F8] border border-[#F0E4EA] rounded-xl text-xs font-semibold text-[#172B4D] pl-3 pr-8 py-2 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] cursor-pointer shadow-2xs transition-all">
                    <option value="" data-i18n="ad_filter_type_all">Semua Jenis Iklan</option>
                    <option value="SHOP_AD" {{ $adType === 'SHOP_AD' ? 'selected' : '' }} data-i18n="ad_filter_type_shop">Iklan Toko (Highlight)</option>
                    <option value="PRODUCT_AD" {{ $adType === 'PRODUCT_AD' ? 'selected' : '' }} data-i18n="ad_filter_type_product">Iklan Produk (Etalase)</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>

            {{-- Filter Tenant --}}
            <div class="relative min-w-[150px]">
                <select name="outlet_id" class="w-full appearance-none bg-[#FFF9FB]/70 hover:bg-[#FFF5F8] border border-[#F0E4EA] rounded-xl text-xs font-semibold text-[#172B4D] pl-3 pr-8 py-2 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] cursor-pointer shadow-2xs transition-all">
                    <option value="" data-i18n="ad_filter_tenant_all">Semua Tenant</option>
                    @foreach($outlets as $out)
                        <option value="{{ $out->id }}" {{ ($outletId ?? '') == $out->id ? 'selected' : '' }}>{{ $out->outlet_name }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>

            {{-- Submit Filter Button --}}
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold rounded-xl transition-all shadow-xs cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                <span data-i18n="ad_filter_btn">Filter</span>
            </button>

            @if(!empty($search) || !empty($adType) || !empty($outletId))
                <a href="{{ route('platform.ads.index', array_filter(['status' => $status])) }}" class="inline-flex items-center gap-1 px-3 py-2 border border-[#F0E4EA] bg-white text-slate-500 hover:text-[#8E1B5C] text-xs font-semibold rounded-xl transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Reset</span>
                </a>
            @endif
        </form>
    </div>

    {{-- 5. MAIN 2-COLUMN SPLIT LAYOUT (Matching Image 1) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        {{-- LEFT COLUMN: Main Table Card (8 of 12 on lg, 9 of 12 on xl) --}}
        <div class="lg:col-span-8 xl:col-span-9 bg-white rounded-2xl border border-[#F0E4EA] shadow-xs overflow-hidden">
            @if($campaigns->isEmpty())
                {{-- Empty State: Matching luxury standard --}}
                <div class="p-12 text-center relative overflow-hidden">
                    <div class="w-14 h-14 rounded-full bg-[#FAF0F5] text-[#8E1B5C] mx-auto flex items-center justify-center mb-3 relative shadow-2xs border border-[#F0D5E2]">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.388-.497-.674-1.047-.847-1.637m0 0A7.5 7.5 0 0112 4.5v0a7.5 7.5 0 017.5 7.5v.75m-7.5-8.25V3m0 0a1.5 1.5 0 013 0v.75m-3-.75a1.5 1.5 0 00-3 0v.75m1.5 11.25a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h2 class="text-sm sm:text-base font-bold text-[#172B4D] mb-1" data-i18n="empty_ad_title">Belum ada materi iklan</h2>
                    <p class="text-xs text-[#64748B] max-w-sm mx-auto mb-4" data-i18n="empty_ad_desc">
                        @if(!empty($search))
                            Tidak ditemukan materi iklan yang cocok dengan kata kunci &ldquo;{{ $search }}&rdquo;.
                        @else
                            Tidak ada materi iklan yang sesuai dengan status atau filter saat ini.
                        @endif
                    </p>
                    @if(!empty($search) || !empty($adType) || !empty($outletId) || (!empty($status) && $status !== 'PENDING_REVIEW'))
                        <a href="{{ route('platform.ads.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[#8E1B5C] text-white text-xs font-semibold rounded-xl hover:bg-[#68113F] transition shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Tampilkan Semua Status</span>
                        </a>
                    @endif
                    <svg class="n-reg-empty-wave absolute bottom-0 left-0 w-full h-8 text-[#FFD8D8]/30 pointer-events-none" viewBox="0 0 1200 48" preserveAspectRatio="none" fill="currentColor">
                        <path d="M0,24 C150,40 350,8 500,24 C650,40 850,8 1000,24 C1100,32 1160,28 1200,24 L1200,48 L0,48 Z"></path>
                    </svg>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-[#FAF0F5] text-slate-500 text-[10px] uppercase font-bold tracking-wider border-b border-[#F0E4EA]">
                                <th class="py-2.5 px-1.5 pl-3 w-6 text-center">
                                    <input type="checkbox" class="rounded border-slate-300 text-[#8E1B5C] focus:ring-[#8E1B5C]/20 cursor-pointer">
                                </th>
                                <th class="py-2.5 px-2" data-i18n="th_ad_material">Materi Iklan</th>
                                <th class="py-2.5 px-1.5" data-i18n="th_ad_tenant">Tenant</th>
                                <th class="py-2.5 px-1 text-center w-16" data-i18n="th_ad_type">Jenis Iklan</th>
                                <th class="py-2.5 px-1.5 text-center whitespace-nowrap w-24" data-i18n="th_ad_date">Tanggal Pengajuan</th>
                                <th class="py-2.5 px-1.5 text-center whitespace-nowrap w-24" data-i18n="th_ad_status">Status</th>
                                <th class="py-2.5 px-1.5 pr-3 text-center whitespace-nowrap w-24" data-i18n="th_ad_action">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F0E4EA]/70">
                            @foreach($campaigns as $camp)
                                <tr class="hover:bg-[#FFF9FB]/60 transition-colors">
                                    {{-- Checkbox --}}
                                    <td class="py-2.5 px-1.5 pl-3 text-center">
                                        <input type="checkbox" name="selected_ads[]" value="{{ $camp->id }}" class="rounded border-slate-300 text-[#8E1B5C] focus:ring-[#8E1B5C]/20 cursor-pointer">
                                    </td>

                                    {{-- Materi Iklan: Thumbnail + Title + Excerpt --}}
                                    <td class="py-2.5 px-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-10 h-8 rounded-lg bg-[#FAF0F5] border border-[#F0D5E2] overflow-hidden shrink-0 flex items-center justify-center shadow-2xs">
                                                @if($camp->banner_path)
                                                    <img src="{{ asset('storage/' . $camp->banner_path) }}" alt="{{ $camp->title }}" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-[#FFF5F8] to-[#FAF0F5] p-0.5 text-center">
                                                        <span class="text-[7.5px] font-black text-[#8E1B5C] leading-none">IKLAN</span>
                                                        <span class="text-[5.5px] text-[#D96B9B] uppercase font-bold leading-none mt-0.5">Materi</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="min-w-0 max-w-[130px] sm:max-w-[160px]">
                                                <a href="{{ route('platform.ads.show', $camp->id) }}" class="font-bold text-[#172B4D] hover:text-[#8E1B5C] text-xs leading-tight block truncate transition-colors">
                                                    {{ $camp->title }}
                                                </a>
                                                <p class="text-slate-400 text-[10px] truncate mt-0.5 leading-tight">
                                                    {{ $camp->tagline ?: ($camp->product?->name ?? 'Promo koleksi hijab...') }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Tenant / Outlet --}}
                                    <td class="py-2.5 px-1.5">
                                        <div class="flex items-center gap-1 min-w-0 max-w-[100px]">
                                            <span class="w-4.5 h-4.5 rounded bg-[#FAF0F5] text-[#8E1B5C] border border-[#F0D5E2] flex items-center justify-center shrink-0 text-[10px]">
                                                🏬
                                            </span>
                                            <span class="font-bold text-[#172B4D] text-[11.5px] leading-tight truncate">
                                                {{ $camp->outlet?->outlet_name ?? $camp->outlet?->name ?? 'NAYARA Store' }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Jenis Iklan Badge --}}
                                    <td class="py-2.5 px-1 text-center whitespace-nowrap">
                                        @if($camp->ad_type === 'PRODUCT_AD')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-[#FAF0F5] text-[#8E1B5C] border border-[#F0D5E2]">
                                                Produk
                                            </span>
                                        @elseif($camp->ad_type === 'SHOP_AD')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                Promosi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                Kampanye
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Tanggal Pengajuan in Asia/Jakarta WIB (Matching Image 1: Date on top, time below) --}}
                                    <td class="py-2.5 px-1.5 text-center whitespace-nowrap text-xs text-[#172B4D]">
                                        <span class="block font-medium text-[11px]">
                                            {{ \Carbon\Carbon::parse($camp->created_at)->timezone('Asia/Jakarta')->isoFormat('D MMM Y') }}
                                        </span>
                                        <span class="text-slate-400 text-[10px] block leading-tight">
                                            {{ \Carbon\Carbon::parse($camp->created_at)->timezone('Asia/Jakarta')->format('H:i') }}
                                        </span>
                                    </td>

                                    {{-- Status Badge (Matching Image 1) --}}
                                    <td class="py-2.5 px-1.5 text-center whitespace-nowrap">
                                        @php
                                            $st = $camp->status;
                                            $badgeCls = match($st) {
                                                'PENDING_REVIEW'  => 'bg-[#FFFBEB] text-[#B45309] border-[#FDE68A]',
                                                'APPROVED'        => 'bg-[#ECFDF5] text-[#047857] border-[#A7F3D0]',
                                                'ACTIVE'          => 'bg-[#F0FDF4] text-[#16A34A] border-[#86EFAC]',
                                                'NEEDS_REVISION'  => 'bg-[#FFF1F2] text-[#BE123C] border-[#FECDD3]',
                                                'REJECTED'        => 'bg-[#FEF2F2] text-[#B91C1C] border-[#FECACA]',
                                                default           => 'bg-slate-100 text-slate-700 border-slate-200',
                                            };
                                            $statusText = match($st) {
                                                'PENDING_REVIEW'  => 'Perlu Review',
                                                'APPROVED'        => 'Disetujui',
                                                'ACTIVE'          => 'Sedang Tayang',
                                                'NEEDS_REVISION'  => 'Perlu Revisi',
                                                'REJECTED'        => 'Ditolak',
                                                default           => $st,
                                            };
                                        @endphp
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $badgeCls }}">
                                            @if($st === 'PENDING_REVIEW')
                                                <svg class="w-2.5 h-2.5 text-[#B45309]"><use href="#i-clock"/></svg>
                                            @elseif($st === 'ACTIVE')
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            @endif
                                            <span>{{ $statusText }}</span>
                                        </span>
                                    </td>

                                    {{-- Aksi: 3 Circular/Square Action Icons (View, Quick Approve, Quick Reject) Matching Image 1 --}}
                                    <td class="py-2.5 px-1.5 pr-3 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center justify-center gap-1">
                                            {{-- 1. Eye Button: View / Tinjau --}}
                                            <a href="{{ route('platform.ads.show', $camp->id) }}"
                                               title="Tinjau Materi Lengkap"
                                               class="w-6 h-6 rounded-md bg-[#FAF0F5] text-[#8E1B5C] hover:bg-[#8E1B5C] hover:text-white border border-[#F0D5E2] flex items-center justify-center transition-all shadow-2xs">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            </a>

                                            {{-- 2. Check Button: Quick Approve --}}
                                            @if($camp->status === 'PENDING_REVIEW')
                                                <form method="POST" action="{{ route('platform.ads.review', $camp->id) }}" class="inline" onsubmit="return confirm('Setujui materi iklan &ldquo;{{ addslashes($camp->title) }}&rdquo;?')">
                                                    @csrf
                                                    <input type="hidden" name="decision" value="APPROVE">
                                                    <button type="submit"
                                                            title="Setujui Materi Iklan"
                                                            class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200 flex items-center justify-center transition-all shadow-2xs cursor-pointer">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- 3. Cross Button: Quick Reject / Review Notes --}}
                                            <a href="{{ route('platform.ads.show', $camp->id) }}"
                                               title="Tolak atau Minta Revisi Materi"
                                               class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200 flex items-center justify-center transition-all shadow-2xs">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($campaigns->hasPages())
                    <div class="p-3 border-t border-[#F0E4EA] bg-[#FFF9FB]/50">
                        {{ $campaigns->links() }}
                    </div>
                @endif
            @endif
        </div>

        {{-- RIGHT COLUMN: 2 Side Cards Matching Image 1 (Prioritas Moderasi & Aktivitas Terbaru) (4 of 12 on lg, 3 of 12 on xl) --}}
        <div class="lg:col-span-4 xl:col-span-3 space-y-4">

            {{-- 1. Card: Prioritas Moderasi --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 shadow-2xs hover:shadow-xs transition-shadow space-y-3">
                <div class="flex items-center gap-2 pb-2.5 border-b border-[#F0E4EA]/70">
                    <span class="w-6 h-6 rounded-lg bg-[#FAF0F5] text-[#8E1B5C] border border-[#F0D5E2] flex items-center justify-center shrink-0 shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                        </svg>
                    </span>
                    <h3 class="text-xs sm:text-[13px] font-bold text-[#172B4D] tracking-tight leading-tight" data-i18n="card_priority_ad_title">
                        Prioritas Moderasi
                    </h3>
                </div>

                <div class="space-y-2 pt-0.5 text-xs text-[#172B4D]">
                    {{-- Row 1: Tinggi (Fire icon) --}}
                    <div class="flex items-center justify-between gap-2.5 py-2 px-2.5 rounded-xl bg-[#FFF5F8]/70 border border-[#F0E4EA]/80">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-6 h-6 rounded-lg bg-rose-50 text-rose-500 border border-rose-200 flex items-center justify-center shrink-0 text-xs">
                                🔥
                            </span>
                            <div class="min-w-0">
                                <span class="font-bold text-[#172B4D] text-xs block leading-tight" data-i18n="prio_high_label">Tinggi</span>
                                <span class="text-[10.5px] text-slate-500 truncate block mt-0.5" data-i18n="prio_high_sub">Iklan dengan sponsor aktif</span>
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10.5px] font-extrabold rounded-full bg-rose-100 text-rose-700 shrink-0">
                            {{ $priorities['high'] ?? 0 }}
                        </span>
                    </div>

                    {{-- Row 2: Sedang (Fast Forward / Double Chevron) --}}
                    <div class="flex items-center justify-between gap-2.5 py-2 px-2.5 rounded-xl bg-white border border-[#F0E4EA]/80">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-6 h-6 rounded-lg bg-amber-50 text-amber-500 border border-amber-200 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                            </span>
                            <div class="min-w-0">
                                <span class="font-bold text-[#172B4D] text-xs block leading-tight" data-i18n="prio_med_label">Sedang</span>
                                <span class="text-[10.5px] text-slate-500 truncate block mt-0.5" data-i18n="prio_med_sub">Iklan promosi &amp; kampanye tenant</span>
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10.5px] font-extrabold rounded-full bg-amber-100 text-amber-800 shrink-0">
                            {{ $priorities['medium'] ?? 0 }}
                        </span>
                    </div>

                    {{-- Row 3: Normal (Clock icon) --}}
                    <div class="flex items-center justify-between gap-2.5 py-2 px-2.5 rounded-xl bg-white border border-[#F0E4EA]/80">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-6 h-6 rounded-lg bg-slate-50 text-slate-500 border border-slate-200 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5"><use href="#i-clock"/></svg>
                            </span>
                            <div class="min-w-0">
                                <span class="font-bold text-[#172B4D] text-xs block leading-tight" data-i18n="prio_norm_label">Normal</span>
                                <span class="text-[10.5px] text-slate-500 truncate block mt-0.5" data-i18n="prio_norm_sub">Pengajuan iklan umum</span>
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10.5px] font-extrabold rounded-full bg-slate-100 text-slate-700 shrink-0">
                            {{ $priorities['normal'] ?? 0 }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- 2. Card: Aktivitas Terbaru (Matching Image 1) --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 shadow-2xs hover:shadow-xs transition-shadow space-y-3">
                <div class="flex items-center gap-2 pb-2.5 border-b border-[#F0E4EA]/70">
                    <span class="w-6 h-6 rounded-lg bg-[#FAF0F5] text-[#8E1B5C] border border-[#F0D5E2] flex items-center justify-center shrink-0 shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <h3 class="text-xs sm:text-[13px] font-bold text-[#172B4D] tracking-tight leading-tight" data-i18n="card_recent_ad_title">
                        Aktivitas Terbaru
                    </h3>
                </div>

                @if(!empty($recentActivities) && $recentActivities->isNotEmpty())
                    <div class="space-y-2.5 pt-0.5">
                        @foreach($recentActivities as $act)
                            @php
                                $isApproved = ($act->status === 'APPROVED' || $act->status === 'ACTIVE');
                                $isPending  = ($act->status === 'PENDING_REVIEW');
                                $isRejected = ($act->status === 'REJECTED');
                                $actTime = $act->reviewed_at ?? $act->updated_at ?? $act->created_at;
                            @endphp
                            <div class="flex items-start gap-2.5 py-1 text-xs">
                                @if($isApproved)
                                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3"><use href="#i-check"/></svg>
                                    </span>
                                @elseif($isPending)
                                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3"><use href="#i-clock"/></svg>
                                    </span>
                                @elseif($isRejected)
                                    <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3"><use href="#i-cross"/></svg>
                                    </span>
                                @else
                                    <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3"><use href="#i-activity"/></svg>
                                    </span>
                                @endif

                                <div class="min-w-0 flex-1 leading-tight">
                                    <p class="font-bold text-[#172B4D] text-[11.5px]">
                                        @if($isApproved)
                                            Iklan disetujui
                                        @elseif($isPending)
                                            Menunggu review
                                        @elseif($isRejected)
                                            Iklan ditolak
                                        @else
                                            Pembaruan iklan
                                        @endif
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 truncate">
                                        {{ $act->outlet?->outlet_name ?? 'NAYARA Store' }} &bull; {{ $actTime ? \Carbon\Carbon::parse($actTime)->timezone('Asia/Jakarta')->diffForHumans() : 'Baru saja' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-4 text-center text-slate-400 text-xs">
                        <svg class="w-6 h-6 mx-auto mb-1 text-slate-300"><use href="#i-history"/></svg>
                        <p class="text-[11px]">Belum ada aktivitas moderasi tercatat.</p>
                    </div>
                @endif

                <div class="pt-2 border-t border-[#F0E4EA]/70 text-center">
                    <a href="{{ route('platform.audit.index') }}" class="text-xs font-bold text-[#8E1B5C] hover:underline inline-flex items-center gap-1" data-i18n="link_all_activity">
                        <span>Lihat Semua Aktivitas</span>
                        <span class="text-xs">&rarr;</span>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

{{-- MODAL: Panduan Review Materi Iklan --}}
<div id="adGuidelinesModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 hidden" role="dialog" aria-modal="true">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-[#F0E4EA] space-y-4 relative">
        <div class="flex items-center justify-between pb-3 border-b border-[#F0E4EA]">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-[#FAF0F5] text-[#8E1B5C] border border-[#F0D5E2] flex items-center justify-center">
                    <svg class="w-4 h-4"><use href="#i-clipboard"/></svg>
                </div>
                <h3 class="text-sm font-bold text-[#172B4D]">Panduan &amp; Kebijakan Moderasi Iklan</h3>
            </div>
            <button type="button" onclick="document.getElementById('adGuidelinesModal').classList.add('hidden')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer">
                <svg class="w-4 h-4"><use href="#i-cross"/></svg>
            </button>
        </div>

        <div class="space-y-3 text-xs text-[#172B4D] max-h-96 overflow-y-auto pr-1">
            <div class="p-3 rounded-xl bg-[#FFF9FB] border border-[#F0E4EA]/80 space-y-1">
                <p class="font-bold text-[#8E1B5C]">1. Kesesuaian Etika &amp; Syariat</p>
                <p class="text-slate-600 text-[11px] leading-relaxed">Materi iklan wajib mempromosikan produk hijab atau busana muslim yang sopan, tidak vulgar, dan tidak melanggar norma hukum maupun syariat Islam.</p>
            </div>
            <div class="p-3 rounded-xl bg-[#FFF9FB] border border-[#F0E4EA]/80 space-y-1">
                <p class="font-bold text-[#8E1B5C]">2. Standar Visual &amp; Resolusi Gambar</p>
                <p class="text-slate-600 text-[11px] leading-relaxed">Rasio banner minimal 1200&times;630 piksel, teks tidak menutupi lebih dari 20% gambar, tajam, dan tidak buram/pecah saat ditayangkan pada resolusi tinggi.</p>
            </div>
            <div class="p-3 rounded-xl bg-[#FFF9FB] border border-[#F0E4EA]/80 space-y-1">
                <p class="font-bold text-[#8E1B5C]">3. Orisinalitas &amp; Hak Cipta</p>
                <p class="text-slate-600 text-[11px] leading-relaxed">Tenant harus memiliki hak atas foto produk dan model yang digunakan. Penggunaan materi berhak cipta tanpa izin dapat mengakibatkan penolakan materi.</p>
            </div>
            <div class="p-3 rounded-xl bg-[#FFF9FB] border border-[#F0E4EA]/80 space-y-1">
                <p class="font-bold text-[#8E1B5C]">4. SLA Moderasi Maksimal 1&times;24 Jam</p>
                <p class="text-slate-600 text-[11px] leading-relaxed">Super Admin wajib memberikan status keputusan (Setujui, Minta Revisi, atau Tolak) selambat-lambatnya 1&times;24 jam sejak pengajuan masuk.</p>
            </div>
        </div>

        <div class="pt-2 text-right">
            <button type="button" onclick="document.getElementById('adGuidelinesModal').classList.add('hidden')" class="px-4 py-2 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">
                Saya Mengerti
            </button>
        </div>
    </div>
</div>
@endsection
