@extends('platform.layouts.app', ['title' => 'Antrean Pendaftaran Tenant'])

@section('content')
<div class="space-y-6">

    {{-- 1. HERO BANNER: Dark Burgundy Luxury + 3D Glass Graphic (Konsisten dengan Dashboard Platform) --}}
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
                        <svg class="w-4 h-4"><use href="#i-clipboard"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15" data-i18n="reg_badge">
                        PLATFORM OTORITAS &rsaquo; PENDAFTARAN TENANT
                    </span>
                </div>

                <div>
                    <h1 class="text-lg sm:text-xl md:text-[22px] font-extrabold text-white tracking-tight leading-tight" data-i18n="reg_title">
                        Antrean Pendaftaran Tenant
                    </h1>
                    <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5" data-i18n="reg_subtitle">
                        Tinjau pendaftaran tenant baru dan kelola status verifikasi.
                    </p>
                </div>
            </div>

            {{-- Right Hero: 3D Perspective Floating Glass Tablet --}}
            <div class="shrink-0 w-full md:w-auto flex justify-center md:justify-end glass-tablet-3d-wrap py-0">
                <div class="glass-tablet-3d w-72 sm:w-80 p-3.5 sm:p-4 relative overflow-hidden flex items-center justify-between gap-4">
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

                    <div class="flex-1 bg-white/10 border border-white/20 rounded-xl p-2.5 sm:p-3 backdrop-blur-md shadow-inner flex flex-col justify-between h-22 sm:h-24">
                        <div class="space-y-1">
                            <div class="w-20 h-1.5 bg-white/45 rounded-full"></div>
                            <div class="w-12 h-1 bg-white/25 rounded-full"></div>
                        </div>

                        <div class="flex items-end justify-between gap-2 h-11 px-1 pt-1">
                            <div class="w-4 sm:w-4.5 h-[28%] bg-gradient-to-t from-white/20 to-white/50 rounded-t-sm shadow-xs"></div>
                            <div class="w-4 sm:w-4.5 h-[45%] bg-gradient-to-t from-white/25 to-white/60 rounded-t-sm shadow-xs"></div>
                            <div class="w-4 sm:w-4.5 h-[65%] bg-gradient-to-t from-white/30 to-white/75 rounded-t-sm shadow-xs"></div>
                            <div class="w-4 sm:w-4.5 h-[95%] bg-gradient-to-t from-[#FFD8D8]/60 to-white/95 rounded-t-sm shadow-sm"></div>
                            <div class="w-4 sm:w-4.5 h-[68%] bg-gradient-to-t from-white/25 to-white/65 rounded-t-sm shadow-xs"></div>
                        </div>

                        <div class="w-full h-0.5 bg-white/25 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. 4 Kartu Statistik: Sesuai Referensi 1 (Menunggu Review, Perlu Revisi, Disetujui, Ditolak) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Menunggu Review --}}
        <a href="{{ route('platform.registrations.index', ['tab' => 'pending']) }}"
           class="kpi-card bg-white rounded-2xl border {{ $tab === 'pending' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-[#F0E4EA]' }} p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_pending">MENUNGGU REVIEW</span>
                <span class="w-6 h-6 rounded-full bg-amber-50 text-amber-500 border border-amber-200 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5"><use href="#i-clock"/></svg>
                </span>
            </div>
            <div class="mt-2.5 relative z-10">
                <div class="text-[28px] font-black text-amber-500 tracking-tight leading-none">{{ number_format($counts['pending'] ?? 0, 0, ',', '.') }}</div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_pending_sub">Pengajuan baru perlu verifikasi</div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg" aria-hidden="true"><use href="#i-wm-clipboard"/></svg>
        </a>

        {{-- Card 2: Perlu Revisi --}}
        <a href="{{ route('platform.registrations.index', ['tab' => 'needs_revision']) }}"
           class="kpi-card bg-white rounded-2xl border {{ $tab === 'needs_revision' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-[#F0E4EA]' }} p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_revision">PERLU REVISI</span>
                <span class="w-6 h-6 rounded-full bg-rose-50 text-rose-500 border border-rose-200 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2.5 relative z-10">
                <div class="text-[28px] font-black text-rose-600 tracking-tight leading-none">{{ number_format($counts['needs_revision'] ?? 0, 0, ',', '.') }}</div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_revision_sub">Data pendaftaran perlu revisi</div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg" aria-hidden="true"><use href="#i-wm-file-edit"/></svg>
        </a>

        {{-- Card 3: Disetujui --}}
        <a href="{{ route('platform.registrations.index', ['tab' => 'approved']) }}"
           class="kpi-card bg-white rounded-2xl border {{ $tab === 'approved' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-[#F0E4EA]' }} p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_approved">DISETUJUI</span>
                <span class="w-6 h-6 rounded-full bg-emerald-50 text-emerald-500 border border-emerald-200 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5"><use href="#i-check"/></svg>
                </span>
            </div>
            <div class="mt-2.5 relative z-10">
                <div class="text-[28px] font-black text-emerald-600 tracking-tight leading-none">{{ number_format($counts['approved'] ?? 0, 0, ',', '.') }}</div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_approved_sub">Tenant aktif di ekosistem</div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg" aria-hidden="true"><use href="#i-wm-store"/></svg>
        </a>

        {{-- Card 4: Ditolak --}}
        <a href="{{ route('platform.registrations.index', ['tab' => 'rejected']) }}"
           class="kpi-card bg-white rounded-2xl border {{ $tab === 'rejected' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-[#F0E4EA]' }} p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider" data-i18n="kpi_rejected">DITOLAK</span>
                <span class="w-6 h-6 rounded-full bg-red-50 text-red-500 border border-red-200 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5"><use href="#i-cross"/></svg>
                </span>
            </div>
            <div class="mt-2.5 relative z-10">
                <div class="text-[28px] font-black text-red-600 tracking-tight leading-none">{{ number_format($counts['rejected'] ?? 0, 0, ',', '.') }}</div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="kpi_rejected_sub">Pendaftaran tidak memenuhi syarat</div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg" aria-hidden="true"><use href="#i-wm-ban"/></svg>
        </a>
    </div>

    {{-- 3. Filter Status Tabs Bar (Pills matching Image 4) --}}
    @php
        $tabs = [
            'pending' => [
                'label' => 'Menunggu Review',
                'count' => $counts['pending'] ?? 0,
                'icon'  => 'clock',
            ],
            'needs_revision' => [
                'label' => 'Perlu Revisi',
                'count' => $counts['needs_revision'] ?? 0,
                'icon'  => 'clipboard',
            ],
            'approved' => [
                'label' => 'Disetujui',
                'count' => $counts['approved'] ?? 0,
                'icon'  => 'check',
            ],
            'rejected' => [
                'label' => 'Ditolak',
                'count' => $counts['rejected'] ?? 0,
                'icon'  => 'cross',
            ],
            'all' => [
                'label' => 'Semua Pendaftaran',
                'count' => $counts['all'] ?? 0,
                'icon'  => 'grid',
            ],
        ];
    @endphp

    <div class="flex items-center gap-2 sm:gap-2.5 overflow-x-auto pb-1" aria-label="Filter status pendaftaran tenant">
        @foreach($tabs as $key => $tabItem)
            @php
                $isActive = ($tab === $key);
                $tabParams = ['tab' => $key];
                if (!empty($search)) {
                    $tabParams['search'] = $search;
                }
                $tabUrl = route('platform.registrations.index', $tabParams);
            @endphp
            <a href="{{ $tabUrl }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl transition-all shrink-0 {{ $isActive ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-white text-slate-600 hover:text-[#8E1B5C] hover:border-[#8E1B5C] border border-[#F0E4EA] shadow-2xs' }}"
               @if($isActive) aria-current="page" @endif>
                <svg class="w-3.5 h-3.5"><use href="#i-{{ $tabItem['icon'] }}"/></svg>
                <span data-i18n="tab_{{ $key }}">{{ $tabItem['label'] }}</span>
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-extrabold rounded-full {{ $isActive ? 'bg-white/20 text-white' : 'bg-[#FAF0F5] text-[#8E1B5C]' }}">
                    {{ number_format($tabItem['count'], 0, ',', '.') }}
                </span>
            </a>
        @endforeach
    </div>

    {{-- 4. Main Table Card (Full Width: 100% Spanning) --}}
    <div class="w-full bg-white rounded-2xl border border-[#F0E4EA] shadow-xs overflow-hidden">
        {{-- Card Header: Title & Subtitle + Optional Quick Search --}}
        <div class="px-5 py-4 border-b border-[#F0E4EA] bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-1.5 h-5 rounded-full bg-[#8E1B5C] shrink-0"></span>
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-[#172B4D] leading-tight" data-i18n="table_title">Daftar Pendaftaran Masuk</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        @if($registrations->total() > 0)
                            <span data-i18n="showing_prefix">Menampilkan</span> {{ $registrations->firstItem() ?? 1 }}&ndash;{{ $registrations->lastItem() ?? $registrations->count() }} <span data-i18n="showing_from">dari</span> {{ $registrations->total() }} <span data-i18n="showing_registrations">pendaftaran</span>
                        @else
                            <span data-i18n="showing_zero">Menampilkan 0 pendaftaran</span>
                        @endif
                        @if(!empty($search))
                            &bull; Filter: &ldquo;<strong>{{ $search }}</strong>&rdquo;
                            <a href="{{ route('platform.registrations.index', ['tab' => $tab]) }}" class="ml-1 text-[#8E1B5C] font-semibold hover:underline" data-i18n="btn_reset_search">(Reset Pencarian)</a>
                        @endif
                    </p>
                </div>
            </div>

            {{-- Search Box --}}
            <form method="GET" action="{{ route('platform.registrations.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="relative">
                    <input type="text" name="search" value="{{ $search ?? '' }}"
                           placeholder="Cari konveksi, PIC, email..."
                           data-i18n-placeholder="search_placeholder"
                           class="w-48 sm:w-64 bg-[#FFF9FB]/70 border border-[#F0E4EA] rounded-xl text-xs text-[#172B4D] pl-8 pr-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] transition-all">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </span>
                </div>
                <button type="submit" class="px-3.5 py-1.5 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer" data-i18n="btn_search">
                    Cari
                </button>
            </form>
        </div>

        @if($registrations->isEmpty())
            {{-- Empty State: Preserving all required test hooks --}}
            <div class="p-10 text-center relative overflow-hidden">
                <div class="w-14 h-14 rounded-full bg-[#FAF0F5] text-[#8E1B5C] mx-auto flex items-center justify-center mb-3 relative shadow-2xs border border-[#F0D5E2]">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 13.5h6m-6 3h4" />
                    </svg>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-[#172B4D] mb-1" data-i18n="empty_title">Belum ada pendaftaran baru</h2>
                <p class="text-xs text-[#64748B] max-w-sm mx-auto mb-4" data-i18n="empty_desc_{{ !empty($search) ? 'search' : ($tab === 'all' ? 'all' : 'category') }}">
                    @if(!empty($search))
                        Tidak ditemukan pendaftaran yang cocok dengan kata kunci &ldquo;{{ $search }}&rdquo;.
                    @elseif($tab === 'all')
                        Belum ada pendaftaran tenant.
                    @else
                        Belum ada pengajuan pada kategori ini.
                    @endif
                </p>
                @if(!empty($search))
                    <a href="{{ route('platform.registrations.index', ['tab' => $tab]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#8E1B5C] text-white text-xs font-semibold rounded-lg hover:bg-[#68113F] transition shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span data-i18n="btn_reset_search">Reset Pencarian</span>
                    </a>
                @endif
                <svg class="n-reg-empty-wave absolute bottom-0 left-0 w-full h-8 text-[#FFD8D8]/30 pointer-events-none" viewBox="0 0 1200 48" preserveAspectRatio="none" fill="currentColor">
                    <path d="M0,24 C150,40 350,8 500,24 C650,40 850,8 1000,24 C1100,32 1160,28 1200,24 L1200,48 L0,48 Z"></path>
                </svg>
            </div>
        @else
            {{-- Table with Registrations matching Image 4 --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#FAF0F5] text-slate-500 text-[10px] uppercase font-bold tracking-wider border-b border-[#F0E4EA]">
                            <th class="py-3 px-3.5 pl-5 w-14 text-center" data-i18n="th_no">No / ID</th>
                            <th class="py-3 px-3.5" data-i18n="th_garment">Nama Konveksi</th>
                            <th class="py-3 px-3.5" data-i18n="th_owner">Pemilik / PIC</th>
                            <th class="py-3 px-3.5" data-i18n="th_date">Tanggal Pengajuan</th>
                            <th class="py-3 px-3.5 text-center w-36" data-i18n="th_status">Status</th>
                            <th class="py-3 px-3.5 pr-5 text-center w-28" data-i18n="th_action">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0E4EA]/70">
                        @foreach($registrations as $reg)
                            <tr class="hover:bg-[#FFF9FB]/60 transition-colors">
                                {{-- No (#1, #2, etc.) --}}
                                <td class="py-3.5 px-3.5 pl-5 font-mono text-slate-400 text-xs font-semibold text-center">
                                    #{{ $registrations->firstItem() ? ($registrations->firstItem() + $loop->index) : $loop->iteration }}
                                </td>

                                {{-- Nama Konveksi with Rounded Square Avatar Badge --}}
                                <td class="py-3.5 px-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-[#FAF0F5] text-[#8E1B5C] border border-[#F0D5E2] flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($reg->company_name ?? 'K', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-[#172B4D] text-xs sm:text-[13px] block leading-tight">{{ $reg->company_name }}</span>
                                            @if($reg->outlet)
                                                <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-[#FAF0F5] text-[#8E1B5C]">Outlet: {{ $reg->outlet->outlet_code }}</span>
                                            @elseif($reg->outlet_id)
                                                <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-[#FAF0F5] text-[#8E1B5C]">Outlet ID: {{ $reg->outlet_id }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Pemilik / Kontak --}}
                                <td class="py-3.5 px-3.5">
                                    <span class="font-bold text-[#172B4D] text-xs block leading-tight">{{ $reg->owner_name }}</span>
                                    <span class="text-slate-500 text-[11px] block mt-0.5 leading-tight">{{ $reg->email }} &bull; {{ $reg->phone_wa }}</span>
                                </td>

                                {{-- Tanggal Pengajuan (Converted to Asia/Jakarta WIB) --}}
                                <td class="py-3.5 px-3.5 text-xs text-[#172B4D]">
                                    <span class="block font-medium">{{ \Carbon\Carbon::parse($reg->created_at)->timezone('Asia/Jakarta')->isoFormat('DD MMM Y,') }}</span>
                                    <span class="text-slate-400 text-[10.5px] block">{{ \Carbon\Carbon::parse($reg->created_at)->timezone('Asia/Jakarta')->format('H:i') }} WIB</span>
                                </td>

                                {{-- Status Badge (Matching Image 4) --}}
                                <td class="py-3.5 px-3.5 text-center">
                                    @php
                                        $badgeCls = match($reg->status) {
                                            'PENDING'        => 'bg-[#FFFBEB] text-[#B45309] border-[#FDE68A]',
                                            'NEEDS_REVISION' => 'bg-[#FFF1F2] text-[#BE123C] border-[#FECDD3]',
                                            'APPROVED'       => 'bg-[#ECFDF5] text-[#047857] border-[#A7F3D0]',
                                            'REJECTED'       => 'bg-[#FEF2F2] text-[#B91C1C] border-[#FECACA]',
                                            default          => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };
                                        $statusText = method_exists($reg, 'statusLabel') ? $reg->statusLabel() : match($reg->status ?? '') {
                                            'PENDING' => 'Menunggu Review',
                                            'NEEDS_REVISION' => 'Perlu Revisi',
                                            'APPROVED' => 'Disetujui',
                                            'REJECTED' => 'Ditolak',
                                            default => ($reg->status ?? '-')
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-semibold border {{ $badgeCls }}"
                                          data-i18n="status_{{ strtolower($reg->status ?? 'pending') }}">
                                        {{ $statusText }}
                                    </span>
                                </td>

                                {{-- Aksi: Lihat Detail Button (Solid Magenta) --}}
                                <td class="py-3.5 px-3.5 pr-5 text-center">
                                    <a href="{{ route('platform.registrations.show', $reg->id) }}" class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-[11px] font-bold rounded-lg transition-all shadow-xs">
                                        <span data-i18n="btn_view_detail">Lihat Detail</span>
                                        <span class="text-xs">&rsaquo;</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($registrations->hasPages())
                <div class="p-3.5 border-t border-[#F0E4EA] bg-[#FFF9FB]/50">
                    {{ $registrations->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
