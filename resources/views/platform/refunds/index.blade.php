@extends('platform.layouts.app', ['title' => 'Pencairan & Audit Refund Platform'])

@section('content')
<div class="space-y-6">

    {{-- 1. HERO BANNER: Dark Burgundy Luxury + 3D Glass Tablet with "REFUNDS" Screen --}}
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
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15">
                        PLATFORM OTORITAS
                    </span>
                </div>

                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-tight">
                        Pencairan &amp; Audit Refund Platform
                    </h1>
                    <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5">
                        Kelola pengembalian dana (refund) dan pastikan pencairan sesuai aturan Section U (Target SLA Operasional: 7 hari kerja).
                    </p>
                </div>

                <div class="pt-0.5">
                    <button type="button" onclick="document.getElementById('refundSlaModal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white text-[#8E1B5C] hover:bg-[#FFF5F8] text-xs font-bold transition shadow-xs cursor-pointer group">
                        <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Audit &amp; Settlement Kas</span>
                        <span class="text-xs group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </button>
                </div>
            </div>

            {{-- Right Hero: 3D Perspective Floating Glass Tablet with "REFUNDS" Board --}}
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

                    {{-- Right Screen: "REFUNDS" Header + Financial Stack Bars --}}
                    <div class="flex-1 bg-white/10 border border-white/20 rounded-xl p-2.5 sm:p-3 backdrop-blur-md shadow-inner flex flex-col justify-between h-22 sm:h-24">
                        <div class="flex items-center justify-between gap-1.5">
                            <span class="text-xs sm:text-[13px] font-black tracking-widest text-white px-2 py-0.5 rounded-md bg-white/25 border border-white/35 shadow-xs select-none">REFUNDS</span>
                            <div class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-[8.5px] font-extrabold text-[#FFD8D8] uppercase tracking-wider">Settlement</span>
                            </div>
                        </div>

                        <div class="flex items-end justify-between gap-2 h-10 px-1 pt-1">
                            <div class="w-4 sm:w-4.5 h-[30%] bg-gradient-to-t from-white/20 to-white/50 rounded-t-sm shadow-xs"></div>
                            <div class="w-4 sm:w-4.5 h-[50%] bg-gradient-to-t from-white/25 to-white/60 rounded-t-sm shadow-xs"></div>
                            <div class="w-4 sm:w-4.5 h-[70%] bg-gradient-to-t from-white/30 to-white/75 rounded-t-sm shadow-xs"></div>
                            <div class="w-4 sm:w-4.5 h-[95%] bg-gradient-to-t from-[#FFD8D8]/60 to-white/95 rounded-t-sm shadow-sm"></div>
                            <div class="w-4 sm:w-4.5 h-[60%] bg-gradient-to-t from-white/25 to-white/65 rounded-t-sm shadow-xs"></div>
                        </div>

                        <div class="w-full h-0.5 bg-white/25 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. 4 KARTU STATISTIK REFUND: Sesuai Gambar 2 (Rapi, Proporsional, Ikon & Font Presisi) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Permintaan Masuk --}}
        <a href="{{ route('platform.refunds.index', array_filter(['status' => 'REQUESTED', 'search' => $search, 'period' => $period])) }}"
           class="bg-white rounded-2xl border {{ $status === 'REQUESTED' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-amber-200/80 hover:border-amber-300' }} p-4.5 sm:p-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[142px] group">
            {{-- Top Row: Icon + Title --}}
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-amber-50 border border-amber-200/90 text-amber-600 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3"/>
                    </svg>
                </span>
                <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-slate-700">PERMINTAAN MASUK</span>
            </div>

            {{-- Middle Content: Number & Subtitle --}}
            <div class="mt-2.5 relative z-10 pr-14">
                <div class="text-[28px] sm:text-[30px] font-black text-amber-500 tracking-tight leading-none">
                    {{ number_format($stats['requested'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5">
                    Perlu persetujuan awal
                </div>
            </div>

            {{-- Right Watermark: Envelope Line-Art (Non-overlapping) --}}
            <div class="absolute right-3.5 top-1/2 -translate-y-1/2 w-16 h-16 pointer-events-none select-none flex items-center justify-center opacity-20 group-hover:opacity-30 group-hover:scale-105 transition-all text-amber-500">
                <svg class="w-full h-full" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
            </div>
        </a>

        {{-- Card 2: Disetujui (Antre Transfer) --}}
        <a href="{{ route('platform.refunds.index', array_filter(['status' => 'APPROVED', 'search' => $search, 'period' => $period])) }}"
           class="bg-white rounded-2xl border {{ $status === 'APPROVED' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-blue-200/80 hover:border-blue-300' }} p-4.5 sm:p-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[142px] group">
            {{-- Top Row: Icon + Title --}}
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-blue-50 border border-blue-200/90 text-blue-600 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <rect x="2" y="5" width="20" height="14" rx="2.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M2 10h20M6 15h4"/>
                    </svg>
                </span>
                <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-slate-700">DISETUJUI (ANTRE TRANSFER)</span>
            </div>

            {{-- Middle Content: Number & Subtitle --}}
            <div class="mt-2.5 relative z-10 pr-14">
                <div class="text-[28px] sm:text-[30px] font-black text-blue-600 tracking-tight leading-none">
                    {{ number_format($stats['approved'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5">
                    Menunggu eksekusi transfer bank
                </div>
            </div>

            {{-- Right Watermark: Bank Building Line-Art (Non-overlapping) --}}
            <div class="absolute right-3.5 top-1/2 -translate-y-1/2 w-16 h-16 pointer-events-none select-none flex items-center justify-center opacity-20 group-hover:opacity-30 group-hover:scale-105 transition-all text-blue-500">
                <svg class="w-full h-full" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3L2 8h20L12 3zM4 10v7m5-7v7m6-7v7m5-7v7M2 19h20v2H2v-2z"/>
                </svg>
            </div>
        </a>

        {{-- Card 3: Selesai Ditransfer --}}
        <a href="{{ route('platform.refunds.index', array_filter(['status' => 'COMPLETED', 'search' => $search, 'period' => $period])) }}"
           class="bg-white rounded-2xl border {{ $status === 'COMPLETED' ? 'border-[#8E1B5C] ring-2 ring-[#8E1B5C]/15 shadow-sm' : 'border-emerald-200/80 hover:border-emerald-300' }} p-4.5 sm:p-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[142px] group">
            {{-- Top Row: Icon + Title --}}
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-200/90 text-emerald-600 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </span>
                <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-slate-700">SELESAI DITRANSFER</span>
            </div>

            {{-- Middle Content: Number & Subtitle --}}
            <div class="mt-2.5 relative z-10 pr-14">
                <div class="text-[28px] sm:text-[30px] font-black text-emerald-600 tracking-tight leading-none">
                    {{ number_format($stats['completed'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5">
                    Bukti transfer tercatat
                </div>
            </div>

            {{-- Right Watermark: Receipt Line-Art (Non-overlapping) --}}
            <div class="absolute right-3.5 top-1/2 -translate-y-1/2 w-16 h-16 pointer-events-none select-none flex items-center justify-center opacity-20 group-hover:opacity-30 group-hover:scale-105 transition-all text-emerald-500">
                <svg class="w-full h-full" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M19.5 21l-3-1.5-3 1.5-3-1.5-3 1.5-3-1.5V4.5A2.25 2.25 0 016.75 2.25h10.5A2.25 2.25 0 0119.5 4.5v16.5z"/>
                </svg>
            </div>
        </a>

        {{-- Card 4: Total Dana Di-Refund --}}
        <div class="bg-white rounded-2xl border border-rose-200/80 p-4.5 sm:p-5 relative overflow-hidden shadow-2xs flex flex-col justify-between min-h-[142px] group">
            {{-- Top Row: Icon + Title --}}
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-rose-50 border border-rose-200/90 text-rose-600 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-slate-700">TOTAL DANA DI-REFUND</span>
            </div>

            {{-- Middle Content: Number & Subtitle --}}
            <div class="mt-2.5 relative z-10 pr-14">
                <div class="text-[22px] sm:text-[24px] font-black text-rose-600 tracking-tight leading-none">
                    Rp {{ number_format($stats['total_refunded'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5">
                    Dana platform yang dikembalikan
                </div>
            </div>

            {{-- Right Watermark: Coin Stack Line-Art (Non-overlapping) --}}
            <div class="absolute right-3.5 top-1/2 -translate-y-1/2 w-16 h-16 pointer-events-none select-none flex items-center justify-center opacity-20 group-hover:opacity-30 group-hover:scale-105 transition-all text-rose-500">
                <svg class="w-full h-full" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24">
                    <ellipse cx="12" cy="6" rx="8" ry="3"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6v6c0 1.66 3.58 3 8 3s8-1.34 8-3V6"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 12v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- 3. STATUS TABS (PILLS): Matching Image 2 --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
        {{-- Semua Refund --}}
        <a href="{{ route('platform.refunds.index', array_filter(['search' => $search, 'period' => $period])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition-all shrink-0 focus:outline-none {{ empty($status) ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-white border border-[#E1CFD9] text-slate-600 hover:text-[#8E1B5C] hover:border-[#8E1B5C]' }}">
            <span>Semua Refund</span>
            <span class="px-2 py-0.5 rounded-full text-[10.5px] font-black {{ empty($status) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ number_format($stats['all'] ?? 0, 0, ',', '.') }}
            </span>
        </a>

        {{-- Menunggu Persetujuan --}}
        <a href="{{ route('platform.refunds.index', array_filter(['status' => 'REQUESTED', 'search' => $search, 'period' => $period])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition-all shrink-0 focus:outline-none {{ $status === 'REQUESTED' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-white border border-[#E1CFD9] text-slate-600 hover:text-[#8E1B5C] hover:border-[#8E1B5C]' }}">
            <span>Menunggu Persetujuan</span>
            <span class="px-2 py-0.5 rounded-full text-[10.5px] font-black {{ $status === 'REQUESTED' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ number_format($stats['requested'] ?? 0, 0, ',', '.') }}
            </span>
        </a>

        {{-- Disetujui (Antre Transfer) --}}
        <a href="{{ route('platform.refunds.index', array_filter(['status' => 'APPROVED', 'search' => $search, 'period' => $period])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition-all shrink-0 focus:outline-none {{ $status === 'APPROVED' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-white border border-[#E1CFD9] text-slate-600 hover:text-[#8E1B5C] hover:border-[#8E1B5C]' }}">
            <span>Disetujui (Antre Transfer)</span>
            <span class="px-2 py-0.5 rounded-full text-[10.5px] font-black {{ $status === 'APPROVED' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ number_format($stats['approved'] ?? 0, 0, ',', '.') }}
            </span>
        </a>

        {{-- Selesai Ditransfer --}}
        <a href="{{ route('platform.refunds.index', array_filter(['status' => 'COMPLETED', 'search' => $search, 'period' => $period])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition-all shrink-0 focus:outline-none {{ $status === 'COMPLETED' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-white border border-[#E1CFD9] text-slate-600 hover:text-[#8E1B5C] hover:border-[#8E1B5C]' }}">
            <span>Selesai Ditransfer</span>
            <span class="px-2 py-0.5 rounded-full text-[10.5px] font-black {{ $status === 'COMPLETED' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ number_format($stats['completed'] ?? 0, 0, ',', '.') }}
            </span>
        </a>

        {{-- Ditolak --}}
        <a href="{{ route('platform.refunds.index', array_filter(['status' => 'REJECTED', 'search' => $search, 'period' => $period])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition-all shrink-0 focus:outline-none {{ $status === 'REJECTED' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-white border border-[#E1CFD9] text-slate-600 hover:text-[#8E1B5C] hover:border-[#8E1B5C]' }}">
            <span>Ditolak</span>
            <span class="px-2 py-0.5 rounded-full text-[10.5px] font-black {{ $status === 'REJECTED' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                {{ number_format($stats['rejected'] ?? 0, 0, ',', '.') }}
            </span>
        </a>
    </div>

    {{-- 4. FILTER TOOLBAR: Input + Status Dropdown + Period Dropdown + Filter Button --}}
    <div class="bg-white rounded-2xl border border-[#FFD8D8] p-3 sm:p-4 shadow-xs">
        <form method="GET" action="{{ route('platform.refunds.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            {{-- Search Input --}}
            <div class="sm:col-span-5 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/>
                    </svg>
                </div>
                <input type="text"
                       name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="Cari ID refund, outlet, rekening, atau nama penerima..."
                       class="w-full pl-9 pr-4 py-2 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl text-[#172B4D] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] transition">
            </div>

            {{-- Status Filter --}}
            <div class="sm:col-span-3">
                <select name="status"
                        class="w-full px-3 py-2 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] transition">
                    <option value="">Semua Status Refund</option>
                    <option value="REQUESTED" {{ ($status ?? '') === 'REQUESTED' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                    <option value="APPROVED" {{ ($status ?? '') === 'APPROVED' ? 'selected' : '' }}>Disetujui (Antre Transfer)</option>
                    <option value="COMPLETED" {{ ($status ?? '') === 'COMPLETED' ? 'selected' : '' }}>Selesai Ditransfer</option>
                    <option value="REJECTED" {{ ($status ?? '') === 'REJECTED' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>

            {{-- Periode Filter --}}
            <div class="sm:col-span-2">
                <select name="period"
                        class="w-full px-3 py-2 text-xs bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] transition">
                    <option value="">Semua Periode</option>
                    <option value="7d" {{ ($period ?? '') === '7d' ? 'selected' : '' }}>7 Hari Terakhir</option>
                    <option value="30d" {{ ($period ?? '') === '30d' ? 'selected' : '' }}>30 Hari Terakhir</option>
                    <option value="90d" {{ ($period ?? '') === '90d' ? 'selected' : '' }}>90 Hari Terakhir</option>
                </select>
            </div>

            {{-- Buttons --}}
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-[#8E1B5C] hover:bg-[#701648] text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Filter</span>
                </button>
                @if(!empty($search) || !empty($status) || !empty($period))
                    <a href="{{ route('platform.refunds.index') }}"
                       title="Reset Filter"
                       class="p-2 border border-[#FFD8D8] bg-[#FFFCFB] text-slate-500 hover:text-[#8E1B5C] text-xs font-semibold rounded-xl transition shrink-0"
                       aria-label="Reset Filter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- 5. MAIN CONTENT CARD: Table or Empty State (Matching Image 2) --}}
    <div class="bg-white rounded-2xl border border-[#FFD8D8] shadow-xs overflow-hidden">
        @if($refunds->isEmpty())
            {{-- EMPTY STATE: Matches Image 2 Illustration & Typography --}}
            <div class="py-16 px-6 text-center">
                {{-- Luxury Central Illustration --}}
                <div class="relative w-40 h-40 mx-auto flex items-center justify-center mb-2">
                    {{-- Soft Blush Glow --}}
                    <div class="absolute inset-0 rounded-full bg-gradient-to-b from-[#FFD8D8]/60 to-[#F7E6EE]/30 blur-xl"></div>

                    {{-- Circular Soft Background Badge --}}
                    <div class="relative w-32 h-32 rounded-full bg-gradient-to-b from-[#FFF5F8] to-[#FCE7F0] border border-[#FFD8D8]/70 flex items-center justify-center shadow-xs">
                        {{-- Refund Document SVG Illustration --}}
                        <div class="relative z-10 flex flex-col items-center">
                            {{-- Receipt / Document --}}
                            <div class="w-16 h-20 rounded-lg bg-white border border-[#E1CFD9] shadow-sm p-1.5 flex flex-col items-center justify-between relative overflow-hidden">
                                {{-- Top Document Ribbon --}}
                                <div class="w-full bg-[#8E1B5C] py-0.5 rounded-xs text-[6.5px] font-black text-white text-center tracking-widest uppercase">
                                    REFUND
                                </div>

                                {{-- Circular Arrows Icon --}}
                                <div class="w-6 h-6 rounded-full bg-[#FFF5F8] text-[#8E1B5C] flex items-center justify-center my-auto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>

                                {{-- Dashed lines --}}
                                <div class="w-full space-y-1">
                                    <div class="w-10 h-0.5 bg-slate-200 rounded-full mx-auto"></div>
                                    <div class="w-7 h-0.5 bg-slate-200 rounded-full mx-auto"></div>
                                </div>
                            </div>

                            {{-- Coin Stacks at Bottom --}}
                            <div class="absolute -bottom-1 -left-1 flex items-end">
                                <div class="w-5 h-2.5 rounded-full bg-amber-400 border border-amber-500/80 shadow-xs"></div>
                                <div class="w-5 h-2.5 rounded-full bg-amber-300 border border-amber-500/80 -ml-2 -mb-1 shadow-xs"></div>
                            </div>
                            <div class="absolute -bottom-1 -right-1 flex items-end">
                                <div class="w-5 h-2.5 rounded-full bg-amber-400 border border-amber-500/80 shadow-xs"></div>
                            </div>
                        </div>

                        {{-- Leaf / Plant Decorative Accents on Sides --}}
                        <div class="absolute -left-2 top-8 w-6 h-12 text-[#8E1B5C]/30 -rotate-12 pointer-events-none">
                            <svg viewBox="0 0 24 48" fill="currentColor">
                                <path d="M12 40 C6 30 2 20 6 10 C10 18 16 26 12 40 Z"/>
                                <path d="M12 40 C18 32 22 24 18 14 C14 22 10 30 12 40 Z"/>
                            </svg>
                        </div>
                        <div class="absolute -right-2 top-8 w-6 h-12 text-[#8E1B5C]/30 rotate-12 pointer-events-none">
                            <svg viewBox="0 0 24 48" fill="currentColor">
                                <path d="M12 40 C6 30 2 20 6 10 C10 18 16 26 12 40 Z"/>
                                <path d="M12 40 C18 32 22 24 18 14 C14 22 10 30 12 40 Z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Copy --}}
                <h2 class="text-base font-bold text-[#172B4D] mt-3">Belum ada pengajuan refund</h2>
                <p class="text-xs text-[#64748B] max-w-sm mx-auto mt-1">Tidak ada data pengembalian dana pada periode yang dipilih.</p>

                {{-- Reload / Reset Button matching Image 2 --}}
                <div class="mt-4">
                    <a href="{{ route('platform.refunds.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-2 rounded-full border border-[#8E1B5C] text-[#8E1B5C] bg-white hover:bg-[#FFF5F8] text-xs font-bold transition shadow-xs">
                        <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Muat Ulang Data</span>
                    </a>
                </div>
            </div>
        @else
            {{-- Table Count Header --}}
            <div class="px-5 py-3.5 border-b border-[#FFD8D8] bg-[#FFFCFB] flex items-center justify-between text-xs text-[#64748B]">
                <div>
                    Menampilkan <strong>{{ $refunds->firstItem() ?? 1 }}&ndash;{{ $refunds->lastItem() ?? $refunds->count() }}</strong> dari <strong>{{ $refunds->total() }}</strong> pengajuan refund
                    @if(!empty($search))
                        &bull; Filter: &ldquo;<strong>{{ $search }}</strong>&rdquo;
                    @endif
                </div>
                @if(!empty($search) || !empty($status) || !empty($period))
                    <a href="{{ route('platform.refunds.index') }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#8E1B5C] hover:underline">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Hapus Filter</span>
                    </a>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#FFFCFB] text-[#64748B] uppercase text-[10px] tracking-wider border-b border-[#FFD8D8]">
                            <th class="p-3 pl-5 w-28">NO REF</th>
                            <th class="p-3">Toko / Outlet</th>
                            <th class="p-3">Nominal Refund</th>
                            <th class="p-3">Tipe</th>
                            <th class="p-3">Rekening Tujuan</th>
                            <th class="p-3 w-36">Status</th>
                            <th class="p-3">Waktu Diajukan</th>
                            <th class="p-3 pr-5 text-right w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @foreach($refunds as $ref)
                            <tr class="hover:bg-[#FFFCFB] transition-colors {{ $ref->status === 'REQUESTED' ? 'bg-amber-50/20' : '' }}">
                                <td class="p-3 pl-5 font-mono text-slate-500 text-[11px] font-bold">
                                    REF-{{ str_pad($ref->id, 5, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="p-3 font-semibold text-[#172B4D]">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-[#FFF5F8] border border-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0 text-xs font-bold">
                                            {{ strtoupper(substr($ref->outlet?->outlet_name ?? $ref->outlet?->name ?? 'N', 0, 1)) }}
                                        </div>
                                        <span>{{ $ref->outlet?->outlet_name ?? $ref->outlet?->name }}</span>
                                    </div>
                                </td>
                                <td class="p-3 font-extrabold text-rose-600 font-mono text-xs sm:text-sm">
                                    Rp {{ number_format($ref->amount, 0, ',', '.') }}
                                </td>
                                <td class="p-3">
                                    @if($ref->refund_type === 'FULL_100_PERCENT')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#FFD8D8] text-[#8E1B5C]">
                                            100% Penuh
                                        </span>
                                    @elseif($ref->refund_type === 'PROPORTIONAL')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            Proporsional
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700">
                                            {{ $ref->refund_type }}
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-xs text-[#172B4D]">
                                    <div class="font-semibold">{{ $ref->destination_bank_name }} &bull; {{ $ref->destination_account_number }}</div>
                                    <div class="text-[#64748B] text-[10.5px]">a.n. {{ $ref->destination_account_name }}</div>
                                </td>
                                <td class="p-3">
                                    @if($ref->status === 'COMPLETED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10.5px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            <span>Selesai</span>
                                        </span>
                                    @elseif($ref->status === 'APPROVED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10.5px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                            <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
                                            <span>Disetujui</span>
                                        </span>
                                    @elseif($ref->status === 'REQUESTED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10.5px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            <svg class="w-3 h-3 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                            <span>Menunggu</span>
                                        </span>
                                    @elseif($ref->status === 'REJECTED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10.5px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                            <svg class="w-3 h-3 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                            <span>Ditolak</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10.5px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ $ref->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-xs text-[#64748B]">
                                    {{ $ref->created_at ? $ref->created_at->format('d M Y H:i') . ' WIB' : '-' }}
                                </td>
                                <td class="p-3 pr-5 text-right">
                                    <a href="{{ route('platform.refunds.show', $ref->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#8E1B5C] text-white text-xs font-semibold rounded-lg hover:bg-[#701648] transition shadow-xs">
                                        <span>Proses Refund</span>
                                        <span aria-hidden="true">&rarr;</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($refunds->hasPages())
                <div class="p-4 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                    {{ $refunds->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

{{-- 6. MODAL: AUDIT & SETTLEMENT KAS (SECTION U SLA POLICY) --}}
<div id="refundSlaModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="document.getElementById('refundSlaModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-100">
            {{-- Modal Header --}}
            <div class="px-6 py-4.5 bg-gradient-to-r from-[#691B3D] to-[#4A132E] text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-white/10 border border-white/20 flex items-center justify-center text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white tracking-tight" id="modal-title">Kebijakan Section U &amp; Settlement Kas</h3>
                        <p class="text-[11px] text-[#FFD8D8]">Standar Operasional Audit &amp; Pencairan Refund Platform NAYARA</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('refundSlaModal').classList.add('hidden')"
                        class="w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-4 text-xs text-slate-600 leading-relaxed max-h-[70vh] overflow-y-auto">
                {{-- Rule 1 --}}
                <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200/80">
                    <div class="flex items-start gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">1</span>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs">Pembatalan Sebelum Tayang (100% Refund)</h4>
                            <p class="mt-0.5 text-slate-600">Kampanye iklan yang dibatalkan oleh tenant <strong>sebelum tanggal &amp; jam tayang dimulai</strong> berhak mendapatkan pengembalian dana 100% penuh.</p>
                        </div>
                    </div>
                </div>

                {{-- Rule 2 --}}
                <div class="p-3.5 rounded-xl bg-rose-50/70 border border-rose-200/80">
                    <div class="flex items-start gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-800 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">2</span>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs">Pembatalan Setelah Tayang (0% Refund)</h4>
                            <p class="mt-0.5 text-slate-600">Sesuai aturan Section U, kampanye yang telah <strong>mulai tayang (aktif)</strong> tidak berhak atas pengembalian dana karena kuota slot dan impresi publik telah dialokasikan.</p>
                        </div>
                    </div>
                </div>

                {{-- Rule 3 --}}
                <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80">
                    <div class="flex items-start gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">3</span>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs">Kompensasi Downtime Gangguan Sistem</h4>
                            <p class="mt-0.5 text-slate-600">Jika terjadi kegagalan infrastruktur server / maintenance darurat yang menyebabkan iklan tidak tayang, sistem menghitung kompensasi proporsional berdasarkan durasi downtime.</p>
                        </div>
                    </div>
                </div>

                {{-- Rule 4 --}}
                <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200/80">
                    <div class="flex items-start gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">4</span>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs">Target SLA &amp; Settlement Bukti Kas</h4>
                            <p class="mt-0.5 text-slate-600">Target operasional settlement transfer perbankan maksimal <strong>7 hari kerja</strong>. Super Admin wajib melampirkan file bukti transfer bank sah (JPG/PNG/PDF) sebelum status diubah menjadi SELESAI.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end">
                <button type="button" onclick="document.getElementById('refundSlaModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-[#8E1B5C] hover:bg-[#701648] text-white text-xs font-bold transition shadow-xs cursor-pointer">
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
