@extends('platform.layouts.app', ['title' => 'Manajemen Permintaan Setup & Training'])

@section('content')
<style>
    .services-pagination nav p.text-sm {
        display: none !important;
    }
</style>
<div class="space-y-6">

    {{-- 1. HERO BANNER: Dark Burgundy Luxury + 3D Glass Tablet with Training Graphic (Identik Gambar 1) --}}
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
                            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15">
                        PLATFORM OTORITAS &rsaquo; SETUP &amp; TRAINING
                    </span>
                </div>

                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-tight">
                        Manajemen Permintaan Setup &amp; Training
                    </h1>
                    <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5">
                        Kelola permintaan implementasi awal, migrasi master data, dan pelatihan staf konveksi dari para mitra toko.
                    </p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2.5 pt-1">
                    <a href="#daftar-permintaan" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white text-[#8E1B5C] hover:bg-[#FFF5F8] text-xs font-bold transition shadow-xs">
                        <span>Kelola Permintaan</span>
                        <span class="text-xs">&rarr;</span>
                    </a>
                    <button type="button" onclick="document.getElementById('serviceGuideModal').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white/15 hover:bg-white/25 text-white border border-white/20 text-xs font-bold transition cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-white/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Panduan Layanan</span>
                    </button>
                </div>
            </div>

            {{-- Right Hero: 3D Perspective Floating Glass Tablet with Training Graphic (Identik Gambar 1) --}}
            <div class="shrink-0 w-full md:w-auto flex justify-center md:justify-end glass-tablet-3d-wrap py-0 relative">
                {{-- Floating 3D Glass Pill 1: Settings / Gear (Top Left) --}}
                <div class="absolute -top-2.5 -left-2.5 sm:-top-3 sm:-left-3 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </div>

                {{-- Floating 3D Glass Pill 2: Checklist / Notebook (Left) --}}
                <div class="absolute top-8 -left-3 sm:top-9 sm:-left-3.5 w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>

                {{-- Floating 3D Glass Pill 3: Calendar / Schedule (Bottom Right) --}}
                <div class="absolute -bottom-2.5 -right-2 sm:-bottom-3 sm:-right-2 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>

                {{-- Floating 3D Glass Pill 4: Analytics Bar Chart (Top Right) --}}
                <div class="absolute -top-2.5 -right-1.5 sm:-top-3 sm:-right-2 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 20V10m-6 10V4m-6 16v-6" />
                    </svg>
                </div>

                {{-- Main 3D Tablet --}}
                <div class="glass-tablet-3d w-72 sm:w-84 p-3.5 sm:p-4 relative overflow-hidden flex items-center justify-between gap-3.5">
                    {{-- Left: Nayara Icon Badge --}}
                    <div class="flex flex-col items-center justify-center shrink-0 w-16 sm:w-20">
                        <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-[#E11D48] via-[#ba4e77] to-[#8E1B5C] border border-white/40 flex flex-col items-center justify-center shadow-md p-1">
                            <span class="font-serif font-black text-white text-lg sm:text-xl leading-none italic select-none">n</span>
                            <span class="text-[6px] sm:text-[6.5px] font-extrabold text-[#FFD8D8] tracking-widest lowercase -mt-0.5 select-none">nayara</span>
                        </div>
                        <div class="w-full space-y-1 mt-2 flex flex-col items-center">
                            <div class="w-14 h-1.5 bg-white/40 rounded-full"></div>
                            <div class="w-9 h-1 bg-white/20 rounded-full"></div>
                        </div>
                    </div>

                    {{-- Right Screen: Vector Interactive Training & Video Module --}}
                    <div class="flex-1 bg-white/10 border border-white/20 rounded-xl p-2.5 sm:p-3 backdrop-blur-md shadow-inner flex flex-col justify-between h-24 sm:h-28 relative overflow-hidden">
                        {{-- Screen Header --}}
                        <div class="flex items-center justify-between gap-1.5 z-10">
                            <span class="text-[9px] sm:text-[9.5px] font-black tracking-widest text-white px-2 py-0.5 rounded-md bg-white/25 border border-white/35 shadow-xs select-none">
                                TRAINING
                            </span>
                            <div class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-[8px] font-extrabold text-[#FFD8D8] uppercase tracking-wider">ONLINE</span>
                            </div>
                        </div>

                        {{-- Screen Center: Training Video Presentation Silhouette --}}
                        <div class="relative w-full my-auto flex items-center justify-center gap-2 py-1">
                            {{-- Tutor Avatar Silhouette --}}
                            <div class="w-9 h-9 rounded-full bg-white/20 border border-white/30 flex items-center justify-center relative shrink-0">
                                <svg class="w-5 h-5 text-white/90" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                {{-- Headset Indicator --}}
                                <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-rose-400 border border-white"></span>
                            </div>

                            {{-- Video Play Icon & Module Lines --}}
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-4 h-4 rounded-full bg-white text-[#8E1B5C] flex items-center justify-center shadow-xs">
                                        <svg class="w-2.5 h-2.5 fill-current ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </span>
                                    <span class="text-[9px] font-bold text-white leading-none">Modul ERP</span>
                                </div>
                                <div class="w-full bg-white/20 rounded-full h-1 overflow-hidden">
                                    <div class="bg-emerald-400 h-1 rounded-full w-2/3"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Screen Footer: Status note --}}
                        <div class="flex items-center justify-between text-[7.5px] sm:text-[8px] text-[#FFD8D8] pt-0.5 z-10 border-t border-white/10">
                            <span class="font-bold text-emerald-300">Live Mentoring</span>
                            <span class="text-white/75">Konveksi Mitra</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. FOUR KPI CARDS (Identik Gambar 1) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Permintaan Baru --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[124px] relative overflow-hidden group">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">PERMINTAAN BARU</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-500 border border-rose-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <span class="text-3xl font-extrabold text-[#172B4D] tracking-tight block">{{ $metrics['new'] }}</span>
                <span class="text-xs text-slate-500 mt-1 block">Permintaan layanan menunggu verifikasi awal</span>
            </div>
        </div>

        {{-- Card 2: Dijadwalkan --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[124px] relative overflow-hidden group">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">DIJADWALKAN</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-500 border border-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <span class="text-3xl font-extrabold text-[#172B4D] tracking-tight block">{{ $metrics['scheduled'] }}</span>
                <span class="text-xs text-slate-500 mt-1 block">Layanan sudah dijadwalkan (setup / training)</span>
            </div>
        </div>

        {{-- Card 3: Sedang Diproses --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[124px] relative overflow-hidden group">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">SEDANG DIPROSES</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-500 border border-blue-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <span class="text-3xl font-extrabold text-[#172B4D] tracking-tight block">{{ $metrics['in_progress'] }}</span>
                <span class="text-xs text-slate-500 mt-1 block">Implementasi, migrasi data, atau pelatihan berjalan</span>
            </div>
        </div>

        {{-- Card 4: Selesai Training --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between min-h-[124px] relative overflow-hidden group">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">SELESAI TRAINING</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-500 border border-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2.5">
                <span class="text-3xl font-extrabold text-[#172B4D] tracking-tight block">{{ $metrics['completed'] }}</span>
                <span class="text-xs text-slate-500 mt-1 block">Layanan sudah selesai dan ditutup</span>
            </div>
        </div>
    </div>

    {{-- 3. MAIN SECTION: TABLE ON LEFT (8 COLS) + SIDEBAR WIDGETS ON RIGHT (4 COLS) --}}
    <div id="daftar-permintaan" class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        {{-- LEFT COLUMN: TABLE (8 cols on lg, 9 cols on xl/2xl) --}}
        <div class="lg:col-span-8 xl:col-span-9 bg-white rounded-2xl border border-[#F0E4EA] p-4 sm:px-4 sm:py-4.5 shadow-2xs flex flex-col justify-between">
            <div>
                {{-- Section Header --}}
                <div class="flex items-center gap-2 pb-3 mb-3.5 border-b border-slate-100">
                    <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0" style="height: 16px;"></span>
                    <div>
                        <h2 class="text-sm sm:text-base font-extrabold text-[#172B4D] leading-tight">Daftar Permintaan Layanan</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Kelola dan pantau seluruh permintaan setup, migrasi data, dan pelatihan dari tenant.</p>
                    </div>
                </div>

                {{-- Filters Bar (Identik Gambar 1) --}}
                <form method="GET" action="{{ route('platform.services.index') }}" class="mb-3.5 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <div class="relative flex-1 min-w-0">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                            </svg>
                        </span>
                        <input type="text" name="search" value="{{ $search ?? '' }}"
                               placeholder="Cari toko / outlet, ID permintaan, atau tipe layanan..."
                               class="w-full pl-8 pr-2.5 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] text-[#172B4D] placeholder-slate-400 bg-white">
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <select name="service_type" class="text-xs py-1.5 px-2 rounded-xl border border-slate-200 text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] bg-white">
                            <option value="">Semua Tipe Layanan</option>
                            <option value="SETUP_ONBOARDING" {{ ($serviceType ?? '') === 'SETUP_ONBOARDING' ? 'selected' : '' }}>Setup &amp; Implementasi</option>
                            <option value="DATA_MIGRATION" {{ ($serviceType ?? '') === 'DATA_MIGRATION' ? 'selected' : '' }}>Migrasi Master Data</option>
                            <option value="TRAINING_STAFF" {{ ($serviceType ?? '') === 'TRAINING_STAFF' ? 'selected' : '' }}>Pelatihan Staf</option>
                            <option value="CUSTOM_CONSULTATION" {{ ($serviceType ?? '') === 'CUSTOM_CONSULTATION' ? 'selected' : '' }}>Konsultasi Khusus</option>
                        </select>

                        <select name="status" class="text-xs py-1.5 px-2 rounded-xl border border-slate-200 text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] bg-white">
                            <option value="">Semua Status</option>
                            <option value="NEW" {{ ($status ?? '') === 'NEW' ? 'selected' : '' }}>Permintaan Baru</option>
                            <option value="SCHEDULED" {{ ($status ?? '') === 'SCHEDULED' ? 'selected' : '' }}>Dijadwalkan</option>
                            <option value="IN_PROGRESS" {{ ($status ?? '') === 'IN_PROGRESS' ? 'selected' : '' }}>Sedang Diproses</option>
                            <option value="COMPLETED" {{ ($status ?? '') === 'COMPLETED' ? 'selected' : '' }}>Selesai Training</option>
                        </select>

                        <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold text-white transition shadow-xs flex items-center gap-1.5 cursor-pointer shrink-0"
                                style="background: linear-gradient(135deg, #ba4e77, #8E1B5C);">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            <span>Filter</span>
                        </button>
                    </div>
                </form>

                {{-- Table (Identik Kolom & Gaya Gambar 1) --}}
                <div class="overflow-x-auto rounded-xl border border-slate-100">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-[#FDF4F8] text-[#8E1B5C] font-extrabold text-[10px] uppercase tracking-wider border-b border-[#F0E4EA]">
                                <th class="py-2.5 px-2">Toko / Outlet</th>
                                <th class="py-2.5 px-1.5">Tipe Layanan</th>
                                <th class="py-2.5 px-1.5">Tanggal Diharapkan</th>
                                <th class="py-2.5 px-1.5">Estimasi Penawaran</th>
                                <th class="py-2.5 px-1.5">Status</th>
                                <th class="py-2.5 px-1.5">Diajukan Pada</th>
                                <th class="py-2.5 px-1 text-center w-10">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($requests as $req)
                                <tr class="hover:bg-[#FFF9FB] transition-colors">
                                    <td class="py-2.5 px-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-6 h-6 rounded-lg bg-rose-50 text-rose-500 border border-rose-100 flex items-center justify-center shrink-0">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1-5h16l1 5v1a3 3 0 01-3 3 3 3 0 01-3-3 3 3 0 01-3 3 3 3 0 01-3-3 3 3 0 01-3 3 3 3 0 01-3-3V9zm2 4v8h14v-8"/>
                                                </svg>
                                            </span>
                                            <div class="min-w-0">
                                                <span class="font-bold text-[#172B4D] block truncate max-w-[110px]">{{ $req->outlet?->outlet_name ?? 'Outlet #' . $req->outlet_id }}</span>
                                                <span class="text-[9.5px] text-slate-400 block truncate max-w-[110px]">{{ $req->outlet?->outlet_code ?? 'Pusat' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-1.5">
                                        @php
                                            $typeLabels = [
                                                'SETUP_ONBOARDING' => ['Setup & Implementasi', 'bg-rose-50 text-rose-700 border-rose-100'],
                                                'DATA_MIGRATION' => ['Migrasi Master Data', 'bg-blue-50 text-blue-700 border-blue-100'],
                                                'TRAINING_STAFF' => ['Pelatihan Staf', 'bg-purple-50 text-purple-700 border-purple-100'],
                                                'CUSTOM_CONSULTATION' => ['Konsultasi Khusus', 'bg-amber-50 text-amber-700 border-amber-100'],
                                            ];
                                            $typeInfo = $typeLabels[$req->service_type] ?? [$req->service_type, 'bg-slate-50 text-slate-700 border-slate-100'];
                                        @endphp
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold border {{ $typeInfo[1] }} whitespace-nowrap">
                                            {{ $typeInfo[0] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-1.5 text-slate-600 text-[10.5px] whitespace-nowrap">
                                        {{ $req->preferred_date ? $req->preferred_date->format('d M Y') : 'Fleksibel' }}
                                    </td>
                                    <td class="py-2.5 px-1.5 font-bold text-[#172B4D] text-[11px] whitespace-nowrap">
                                        @if($req->custom_quote_amount)
                                            Rp {{ number_format($req->custom_quote_amount, 0, ',', '.') }}
                                        @else
                                            <span class="text-slate-400 font-normal italic text-[10px]">Belum Dibuat</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-1.5 whitespace-nowrap">
                                        @if($req->status === 'COMPLETED')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai Training
                                            </span>
                                        @elseif($req->status === 'IN_PROGRESS')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Sedang Diproses
                                            </span>
                                        @elseif(in_array($req->status, ['SCOPED', 'SCHEDULED']))
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Dijadwalkan
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Permintaan Baru
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-1.5 text-slate-500 text-[10.5px] whitespace-nowrap">
                                        {{ $req->created_at->format('d M Y, H:i') }}
                                    </td>
                                    <td class="py-2.5 px-1 text-center whitespace-nowrap w-10">
                                        <a href="{{ route('platform.services.show', $req->id) }}" 
                                           title="Kelola Permintaan"
                                           class="w-7 h-7 rounded-lg bg-slate-50 hover:bg-[#8E1B5C] hover:text-white text-slate-400 hover:border-[#8E1B5C] border border-slate-200 inline-flex items-center justify-center transition shadow-2xs select-none">
                                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                                                <circle cx="4" cy="10" r="1.8"/>
                                                <circle cx="10" cy="10" r="1.8"/>
                                                <circle cx="16" cy="10" r="1.8"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-slate-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-10 h-10 text-slate-300 stroke-[1.4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>
                                            </svg>
                                            <p class="text-xs font-medium text-slate-400 mt-2.5">Belum ada permintaan layanan yang masuk.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Table Pagination (Identik Gambar 1: Menampilkan 1-5 dari 25 permintaan) --}}
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 mt-4">
                <span>
                    @if($requests->total() > 0)
                        Menampilkan {{ $requests->firstItem() }} &ndash; {{ $requests->lastItem() }} dari {{ $requests->total() }} permintaan
                    @else
                        Menampilkan 0 dari 0 permintaan
                    @endif
                </span>
                <div class="services-pagination">
                    {{ $requests->links() }}
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: 2 SIDEBAR WIDGETS (4 cols on lg, 3 cols on xl/2xl) --}}
        <div class="lg:col-span-4 xl:col-span-3 space-y-5">

            {{-- Widget 1: Status Layanan (Donut Chart & Distribution) --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs flex flex-col justify-between">
                <div>
                    {{-- Widget Header --}}
                    <div class="flex items-center gap-2 pb-3.5 mb-3 border-b border-slate-100">
                        <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xs sm:text-sm font-extrabold text-[#172B4D]">Status Layanan</h3>
                            <p class="text-[11px] text-slate-400">Distribusi status seluruh permintaan setup &amp; training.</p>
                        </div>
                    </div>

                    {{-- Donut Chart Visual & Legend --}}
                    <div class="flex flex-col items-center justify-center py-2">
                        {{-- SVG Circular Donut Chart --}}
                        <div class="relative w-36 h-36 flex items-center justify-center">
                            @php
                                $total = max(1, $metrics['total']);
                                $newDash = ($metrics['new'] / $total) * 100;
                                $schedDash = ($metrics['scheduled'] / $total) * 100;
                                $inProgDash = ($metrics['in_progress'] / $total) * 100;
                                $compDash = ($metrics['completed'] / $total) * 100;
                            @endphp
                            <svg class="w-36 h-36 -rotate-90 transform" viewBox="0 0 36 36">
                                {{-- Background Track --}}
                                <path class="text-slate-100" stroke-width="4" stroke="currentColor" fill="none"
                                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />

                                @if($metrics['total'] > 0)
                                    {{-- Selesai Training Segment (Emerald) --}}
                                    <path class="text-emerald-500" stroke-dasharray="{{ $compDash }}, 100" stroke-width="4" stroke="currentColor" fill="none"
                                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />

                                    {{-- Sedang Diproses Segment (Blue) --}}
                                    <path class="text-blue-500" stroke-dasharray="{{ $inProgDash }}, 100" stroke-dashoffset="-{{ $compDash }}" stroke-width="4" stroke="currentColor" fill="none"
                                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />

                                    {{-- Dijadwalkan Segment (Amber) --}}
                                    <path class="text-amber-500" stroke-dasharray="{{ $schedDash }}, 100" stroke-dashoffset="-{{ $compDash + $inProgDash }}" stroke-width="4" stroke="currentColor" fill="none"
                                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />

                                    {{-- Permintaan Baru Segment (Rose) --}}
                                    <path class="text-rose-500" stroke-dasharray="{{ $newDash }}, 100" stroke-dashoffset="-{{ $compDash + $inProgDash + $schedDash }}" stroke-width="4" stroke="currentColor" fill="none"
                                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                @endif
                            </svg>

                            {{-- Center Text --}}
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center select-none">
                                <span class="text-2xl font-black text-[#172B4D] leading-none">{{ $metrics['total'] }}</span>
                                <span class="text-[9.5px] text-slate-400 mt-0.5">Total Permintaan</span>
                            </div>
                        </div>

                        {{-- Legend Breakdown (Identik Gambar 1) --}}
                        <div class="w-full space-y-2 mt-4 pt-3 border-t border-slate-100 text-xs">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                                    <span class="text-slate-600">Permintaan Baru</span>
                                </div>
                                <div class="font-bold text-[#172B4D]">
                                    <span>{{ $metrics['new'] }}</span>
                                    <span class="text-[10px] text-slate-400 ml-1">({{ $metrics['new_pct'] }}%)</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                                    <span class="text-slate-600">Dijadwalkan</span>
                                </div>
                                <div class="font-bold text-[#172B4D]">
                                    <span>{{ $metrics['scheduled'] }}</span>
                                    <span class="text-[10px] text-slate-400 ml-1">({{ $metrics['scheduled_pct'] }}%)</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0"></span>
                                    <span class="text-slate-600">Sedang Diproses</span>
                                </div>
                                <div class="font-bold text-[#172B4D]">
                                    <span>{{ $metrics['in_progress'] }}</span>
                                    <span class="text-[10px] text-slate-400 ml-1">({{ $metrics['in_progress_pct'] }}%)</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span class="text-slate-600">Selesai Training</span>
                                </div>
                                <div class="font-bold text-[#172B4D]">
                                    <span>{{ $metrics['completed'] }}</span>
                                    <span class="text-[10px] text-slate-400 ml-1">({{ $metrics['completed_pct'] }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Widget 2: Aktivitas Terbaru (Identik Gambar 1) --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 sm:p-6 shadow-2xs flex flex-col justify-between">
                <div>
                    {{-- Widget Header --}}
                    <div class="flex items-center gap-2 pb-3.5 mb-3 border-b border-slate-100">
                        <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xs sm:text-sm font-extrabold text-[#172B4D]">Aktivitas Terbaru</h3>
                            <p class="text-[11px] text-slate-400">Update aktivitas terkini terkait permintaan layanan.</p>
                        </div>
                    </div>

                    {{-- Activity List (Identik Gambar 1) --}}
                    <div class="space-y-3 py-1">
                        @forelse($recentActivities as $act)
                            @php
                                $typeLabels = [
                                    'SETUP_ONBOARDING' => 'Setup & Implementasi',
                                    'DATA_MIGRATION' => 'Migrasi Master Data',
                                    'TRAINING_STAFF' => 'Pelatihan Staf',
                                    'CUSTOM_CONSULTATION' => 'Konsultasi Khusus',
                                ];
                                $serviceName = $typeLabels[$act->service_type] ?? $act->service_type;

                                if ($act->status === 'COMPLETED') {
                                    $actTitle = 'Training selesai';
                                    $actBadge = 'bg-emerald-50 text-emerald-500 border-emerald-100';
                                    $actIcon = 'check';
                                } elseif ($act->status === 'IN_PROGRESS') {
                                    $actTitle = 'Proses implementasi dimulai';
                                    $actBadge = 'bg-blue-50 text-blue-500 border-blue-100';
                                    $actIcon = 'refresh';
                                } elseif (in_array($act->status, ['SCOPED', 'SCHEDULED'])) {
                                    $actTitle = 'Jadwal training ditetapkan';
                                    $actBadge = 'bg-amber-50 text-amber-500 border-amber-100';
                                    $actIcon = 'calendar';
                                } else {
                                    $actTitle = 'Permintaan baru diterima';
                                    $actBadge = 'bg-rose-50 text-rose-500 border-rose-100';
                                    $actIcon = 'mail';
                                }

                                $diff = $act->updated_at->diffInMinutes();
                                if ($diff < 60) {
                                    $timeAgo = max(1, $diff) . ' mnt lalu';
                                } elseif ($diff < 1440) {
                                    $timeAgo = floor($diff / 60) . ' jam lalu';
                                } else {
                                    $timeAgo = floor($diff / 1440) . ' hari lalu';
                                }
                            @endphp
                            <div class="flex items-start gap-2.5 text-xs">
                                <span class="w-6 h-6 rounded-full {{ $actBadge }} border flex items-center justify-center shrink-0 mt-0.5">
                                    @if($actIcon === 'check')
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    @elseif($actIcon === 'refresh')
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    @elseif($actIcon === 'calendar')
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                                    @else
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <span class="font-bold text-[#172B4D] block truncate">{{ $actTitle }}</span>
                                    <span class="text-[11px] text-slate-500 block truncate">{{ $act->outlet?->outlet_name ?? 'Mitra Toko' }} &bull; {{ $serviceName }}</span>
                                </div>
                                <span class="text-[10px] text-slate-400 shrink-0 whitespace-nowrap">{{ $timeAgo }}</span>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-slate-400">
                                Belum ada aktivitas layanan terbaru.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Footer Link --}}
                <div class="pt-3 border-t border-slate-100 text-center">
                    <a href="#daftar-permintaan" class="text-xs font-bold text-[#8E1B5C] hover:underline inline-flex items-center gap-1">
                        <span>Lihat Semua Aktivitas</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

{{-- Guidelines Modal --}}
<div id="serviceGuideModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-extrabold text-[#172B4D]">Panduan Layanan Setup &amp; Training</h3>
            <button type="button" onclick="document.getElementById('serviceGuideModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
        </div>
        <div class="py-4 space-y-3 text-xs text-slate-600 leading-relaxed">
            <p><strong>1. Setup &amp; Implementasi:</strong> Pendampingan konfigurasi awal ERP, penetapan kode toko, master bahan baku, dan konfigurasi BOM template.</p>
            <p><strong>2. Migrasi Master Data:</strong> Pemindahan data katalog produk, data pelanggan, dan saldo awal stok dari format Excel atau sistem lama.</p>
            <p><strong>3. Pelatihan Staf:</strong> Sesi pelatihan modul cutting, sewing, dan invoicing untuk staf konveksi mitra toko.</p>
        </div>
        <div class="pt-3 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="document.getElementById('serviceGuideModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>
@endsection
