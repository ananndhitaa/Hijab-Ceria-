@extends('platform.layouts.app', [
    'title' => 'Keluhan & Pusat Bantuan Owner',
    'headerTitle' => 'Keluhan & Pusat Bantuan Owner',
    'breadcrumb' => 'Layanan Tenant > Keluhan Owner'
])

@section('content')
<div class="space-y-6">

    {{-- Flash Message Feedback --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs">&check;</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 cursor-pointer">&times;</button>
        </div>
    @endif

    {{-- =========================================================================
         1. HERO BANNER: KELUHAN & PUSAT BANTUAN OWNER (Maroon Full-Width)
         ========================================================================= --}}
    <section class="rounded-2xl px-5 py-3.5 sm:px-6 sm:py-4 text-white relative overflow-hidden shadow-lg border border-white/10"
             style="background: linear-gradient(135deg, #691B3D 0%, #4A132E 55%, #350920 100%);">

        {{-- Ambient wave blur circles behind --}}
        <div class="absolute -right-16 -top-24 w-96 h-96 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
        <div class="absolute right-48 -bottom-20 w-80 h-80 rounded-full bg-[#8E1B5C]/20 blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-4">

            {{-- Left Hero Copy --}}
            <div class="space-y-2 max-w-xl">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-white/10 border border-white/20 flex items-center justify-center text-white shadow-xs">
                        <svg class="w-4 h-4 text-[#FFD8D8]"><use href="#i-message-square"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15" data-i18n="complaint_hero_badge">
                        LAYANAN TENANT &bull; SUPPORT DESK
                    </span>
                </div>

                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight leading-tight" data-i18n="complaint_hero_title">
                        Keluhan &amp; Pusat Bantuan Owner
                    </h1>
                    <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5" data-i18n="complaint_hero_subtitle">
                        Kelola pengaduan, pertanyaan, dan kendala yang disampaikan oleh pemilik perusahaan konveksi di ekosistem NAYARA.
                    </p>
                </div>

                {{-- Action Pills Underneath --}}
                <div class="flex flex-wrap items-center gap-2 pt-0.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 border border-white/20 text-white text-[11px] font-bold backdrop-blur-xs select-none shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span data-i18n="complaint_pill_active">Helpdesk Live</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 border border-white/20 text-white text-[11px] font-bold backdrop-blur-xs select-none shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-rose-200"><use href="#i-clock"/></svg>
                        <span data-i18n="complaint_pill_sla">SLA Respons 1x24 Jam</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 border border-white/20 text-white text-[11px] font-bold backdrop-blur-xs select-none shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-amber-200"><use href="#i-shield"/></svg>
                        <span data-i18n="complaint_pill_privacy">Privasi Tenant Terjaga</span>
                    </span>
                </div>
            </div>

            {{-- Right Hero: 3D Perspective Floating Glass Tablet --}}
            <div class="shrink-0 w-full md:w-auto flex justify-center md:justify-end glass-tablet-3d-wrap py-0 relative select-none" id="complaintsHero3dWrap">
                {{-- Floating Pill 1: Message Icon (Top Left) --}}
                <div class="absolute -top-2.5 -left-2.5 sm:-top-3 sm:-left-3 w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center justify-center text-white z-20 pointer-events-none">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#FFD8D8]"><use href="#i-message-square"/></svg>
                </div>

                {{-- Floating Pill 2: Live Status (Top Right) --}}
                <div class="absolute -top-2.5 -right-1.5 sm:-top-3 sm:-right-2 px-2.5 py-0.5 rounded-full bg-white/20 border border-white/35 backdrop-blur-md shadow-lg flex items-center gap-1 text-[9.5px] font-bold text-white z-20 pointer-events-none">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Direct Channel</span>
                </div>

                {{-- Floating Pill 3: Super Admin Desk (Bottom Right) --}}
                <div class="absolute -bottom-2.5 -right-2 sm:-bottom-3 sm:-right-2 px-2.5 py-0.5 rounded-full bg-white text-[#8E1B5C] border border-rose-200 shadow-md flex items-center gap-1 text-[9.5px] font-black z-20 pointer-events-none">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>NAYARA Care</span>
                </div>

                {{-- Main 3D Tablet --}}
                <div class="glass-tablet-3d w-72 sm:w-80 p-3.5 sm:p-4 relative overflow-hidden flex items-center justify-between gap-4 cursor-pointer" id="complaints3dCard">
                    {{-- Left: Customer Support Avatar --}}
                    <div class="flex flex-col items-center justify-center shrink-0 w-20 sm:w-24">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#E11D48] via-[#ba4e77] to-[#8E1B5C] border border-white/40 flex flex-col items-center justify-center shadow-md p-1 relative">
                            <svg class="w-6 h-6 text-white drop-shadow"><use href="#i-message-square"/></svg>
                        </div>
                        <div class="w-full space-y-1 mt-2 flex flex-col items-center">
                            <div class="w-16 h-1.5 bg-white/40 rounded-full"></div>
                            <div class="w-10 h-1 bg-white/20 rounded-full"></div>
                        </div>
                    </div>

                    {{-- Right: Status Check Panel in Glass Tablet --}}
                    <div class="flex-1 bg-white/10 border border-white/20 rounded-xl p-2.5 sm:p-3 backdrop-blur-md shadow-inner flex flex-col justify-between h-22 sm:h-24">
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-300">
                                <span class="w-3.5 h-3.5 rounded-full bg-emerald-500/30 flex items-center justify-center text-[9px]">&check;</span>
                                <span class="truncate">Owner Inquiries</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] text-rose-200">
                                <span class="w-3.5 h-3.5 rounded-full bg-rose-500/30 flex items-center justify-center text-[9px]">&check;</span>
                                <span class="truncate">Official Resolution</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-[10px] text-white/90">
                                <span class="w-3.5 h-3.5 rounded-full bg-white/20 flex items-center justify-center text-[9px]">&check;</span>
                                <span class="truncate">Audited Replies</span>
                            </div>
                        </div>
                        <div class="w-full h-0.5 bg-white/25 rounded-full"></div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- =========================================================================
         2. 4 TOP METRIC CARDS (Identik Font, Ikon, Ukuran & Watermark Halaman Dashboard & Audit)
         ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Card 1: KELUHAN BARU --}}
        <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider whitespace-nowrap" data-i18n="complaint_kpi_new_label">KELUHAN BARU</span>
                <div class="w-6 h-6 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5"><use href="#i-message-square"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ number_format($stats['new']) }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="complaint_kpi_new_desc">
                    Menunggu respon awal
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg"><use href="#i-message-square"/></svg>
        </div>

        {{-- Card 2: SEDANG DIPROSES --}}
        <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider whitespace-nowrap" data-i18n="complaint_kpi_progress_label">SEDANG DIPROSES</span>
                <div class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5"><use href="#i-clock"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ number_format($stats['in_progress']) }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="complaint_kpi_progress_desc">
                    Dalam penanganan teknis
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg"><use href="#i-clock"/></svg>
        </div>

        {{-- Card 3: MENUNGGU BALASAN --}}
        <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider whitespace-nowrap" data-i18n="complaint_kpi_waiting_label">MENUNGGU BALASAN</span>
                <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 border border-purple-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5"><use href="#i-refresh"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ number_format($stats['waiting_owner']) }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="complaint_kpi_waiting_desc">
                    Menunggu jawaban owner
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg"><use href="#i-refresh"/></svg>
        </div>

        {{-- Card 4: KELUHAN SELESAI --}}
        <div class="kpi-card bg-white rounded-2xl border border-[#F0E4EA] p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider whitespace-nowrap" data-i18n="complaint_kpi_resolved_label">KELUHAN SELESAI</span>
                <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5"><use href="#i-check"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ number_format($stats['resolved']) }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5" data-i18n="complaint_kpi_resolved_desc">
                    Tiket tuntas ditutup
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
            <svg class="card-watermark-lg"><use href="#i-check"/></svg>
        </div>

    </div>

    {{-- =========================================================================
         3. FILTER & PENCARIAN (Sesuai Aturan C.3)
         ========================================================================= --}}
    <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 sm:p-5 shadow-2xs">
        <form action="{{ route('platform.complaints.index') }}" method="GET" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">

                {{-- Search Box (ID keluhan, nama tenant, judul) --}}
                <div class="lg:col-span-4 relative">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari ID keluhan, nama tenant, judul..."
                           class="w-full h-10 pl-9 pr-3 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition-all outline-hidden text-[#172B4D]"
                           data-i18n-ph="complaint_search_placeholder">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                {{-- Filter Kategori --}}
                <div class="lg:col-span-3">
                    <select name="category"
                            onchange="this.form.submit()"
                            class="w-full h-10 px-3 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition-all outline-hidden text-[#172B4D] cursor-pointer">
                        <option value="all" {{ request('category') === 'all' || !request('category') ? 'selected' : '' }}>Semua Kategori</option>
                        <option value="ERP_SYSTEM" {{ request('category') === 'ERP_SYSTEM' ? 'selected' : '' }}>Kendala Sistem ERP</option>
                        <option value="ACCOUNT_ACCESS" {{ request('category') === 'ACCOUNT_ACCESS' ? 'selected' : '' }}>Akun &amp; Akses</option>
                        <option value="BILLING_PAYMENT" {{ request('category') === 'BILLING_PAYMENT' ? 'selected' : '' }}>Tagihan &amp; Pembayaran</option>
                        <option value="SUBSCRIPTION" {{ request('category') === 'SUBSCRIPTION' ? 'selected' : '' }}>Paket Langganan</option>
                        <option value="ADS_PROMOTION" {{ request('category') === 'ADS_PROMOTION' ? 'selected' : '' }}>Iklan &amp; Promosi</option>
                        <option value="SETUP_TRAINING" {{ request('category') === 'SETUP_TRAINING' ? 'selected' : '' }}>Setup &amp; Pelatihan</option>
                        <option value="OTHER" {{ request('category') === 'OTHER' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>

                {{-- Filter Status --}}
                <div class="lg:col-span-2">
                    <select name="status"
                            onchange="this.form.submit()"
                            class="w-full h-10 px-3 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition-all outline-hidden text-[#172B4D] cursor-pointer">
                        <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Semua Status</option>
                        <option value="NEW" {{ request('status') === 'NEW' ? 'selected' : '' }}>Baru Masuk</option>
                        <option value="IN_PROGRESS" {{ request('status') === 'IN_PROGRESS' ? 'selected' : '' }}>Sedang Diproses</option>
                        <option value="WAITING_OWNER" {{ request('status') === 'WAITING_OWNER' ? 'selected' : '' }}>Menunggu Balasan</option>
                        <option value="resolved_all" {{ request('status') === 'resolved_all' ? 'selected' : '' }}>Selesai / Ditutup</option>
                    </select>
                </div>

                {{-- Filter Periode --}}
                <div class="lg:col-span-2">
                    <select name="period"
                            onchange="this.form.submit()"
                            class="w-full h-10 px-3 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition-all outline-hidden text-[#172B4D] cursor-pointer">
                        <option value="all" {{ request('period') === 'all' || !request('period') ? 'selected' : '' }}>Semua Periode</option>
                        <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="week" {{ request('period') === 'week' ? 'selected' : '' }}>7 Hari Terakhir</option>
                        <option value="month" {{ request('period') === 'month' ? 'selected' : '' }}>30 Hari Terakhir</option>
                    </select>
                </div>

                {{-- Action Button / Reset --}}
                <div class="lg:col-span-1 flex items-center gap-1.5">
                    <button type="submit"
                            class="w-full h-10 px-3 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold transition-all shadow-xs flex items-center justify-center cursor-pointer">
                        <span>Cari</span>
                    </button>
                    @if(request()->hasAny(['search', 'category', 'status', 'period']))
                        <a href="{{ route('platform.complaints.index') }}"
                           title="Reset Filter"
                           class="h-10 px-2.5 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs flex items-center justify-center transition-all cursor-pointer">
                            &times;
                        </a>
                    @endif
                </div>

            </div>
        </form>
    </div>

    {{-- =========================================================================
         4. DAFTAR KELUHAN (Card-Based Modern Sesuai Aturan C.4)
         ========================================================================= --}}
    <div class="space-y-3.5">

        {{-- Section Title & Counter --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0"></span>
                <h2 class="text-sm sm:text-base font-extrabold text-[#172B4D]" data-i18n="complaint_list_heading">
                    Daftar Keluhan Owner
                </h2>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-[#F7E6EE] text-[#8E1B5C]">
                    {{ $complaints->total() }} Tiket
                </span>
            </div>
        </div>

        @if($complaints->isEmpty())
            {{-- Informative Empty State (Aturan A.7) --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-10 sm:p-14 text-center space-y-3 shadow-2xs">
                <div class="w-16 h-16 rounded-full bg-[#F7E6EE] text-[#8E1B5C] border border-[#E1CFD9] flex items-center justify-center mx-auto shadow-inner">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-extrabold text-[#172B4D]" data-i18n="complaint_empty_title">
                        Tidak Ada Keluhan Ditemukan
                    </h3>
                    <p class="text-xs text-slate-500 max-w-md mx-auto" data-i18n="complaint_empty_desc">
                        @if(request()->hasAny(['search', 'category', 'status', 'period']))
                            Tidak ada pengaduan yang sesuai dengan kriteria filter saat ini. Coba ubah kata kunci atau reset filter pencarian.
                        @else
                            Saat ini belum ada pengaduan atau pertanyaan yang diajukan oleh pemilik perusahaan konveksi di ekosistem platform NAYARA.
                        @endif
                    </p>
                </div>
                @if(request()->hasAny(['search', 'category', 'status', 'period']))
                    <div class="pt-2">
                        <a href="{{ route('platform.complaints.index') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all shadow-xs">
                            <span>Reset Semua Filter</span>
                        </a>
                    </div>
                @endif
            </div>
        @else
            {{-- Modern Cards Grid (Bukan tabel kaku, bebas box bersarang) --}}
            <div class="grid grid-cols-1 gap-3.5">
                @foreach($complaints as $complaint)
                    <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs hover:shadow-md hover:border-[#8E1B5C]/30 transition-all flex flex-col justify-between gap-3.5">

                        {{-- Card Top Row: Identity, Tenant, Status & Time --}}
                        <div class="flex flex-wrap items-center justify-between gap-2.5 pb-3 border-b border-slate-100">
                            <div class="flex flex-wrap items-center gap-2.5 min-w-0">
                                {{-- Ticket ID Badge --}}
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold font-mono bg-slate-100 text-[#172B4D] border border-slate-200">
                                    #{{ $complaint->complaint_code }}
                                </span>

                                {{-- Tenant Name --}}
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-[#172B4D] truncate max-w-[220px]" title="{{ $complaint->outlet?->outlet_name }}">
                                    <svg class="w-3.5 h-3.5 text-[#8E1B5C] shrink-0"><use href="#i-building"/></svg>
                                    <span class="truncate">{{ $complaint->outlet?->outlet_name ?? 'Tenant Konveksi' }}</span>
                                </span>

                                <span class="text-slate-300 hidden sm:inline">&bull;</span>

                                {{-- Owner Info --}}
                                <span class="inline-flex items-center gap-1 text-xs text-slate-500 truncate max-w-[180px]">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0"><use href="#i-users"/></svg>
                                    <span class="truncate font-medium">{{ $complaint->user?->name ?? 'Owner' }}</span>
                                </span>
                            </div>

                            <div class="flex items-center gap-2.5 shrink-0">
                                {{-- Status Pill --}}
                                <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold border {{ $complaint->statusBadgeClass() }}">
                                    {{ $complaint->statusLabel() }}
                                </span>

                                {{-- Submission Time --}}
                                <span class="text-[11px] text-slate-400 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5"><use href="#i-clock"/></svg>
                                    <span>{{ $complaint->created_at ? $complaint->created_at->diffForHumans() : '' }}</span>
                                </span>
                            </div>
                        </div>

                        {{-- Card Middle: Title & Description Excerpt --}}
                        <div class="space-y-1">
                            <h3 class="text-sm sm:text-base font-extrabold text-[#172B4D] hover:text-[#8E1B5C] transition-colors leading-snug">
                                <a href="{{ route('platform.complaints.show', $complaint) }}">
                                    {{ $complaint->title }}
                                </a>
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $complaint->description }}
                            </p>
                        </div>

                        {{-- Card Bottom Row: Category, Reply Count, Attachment, Action --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 pt-2.5 border-t border-slate-100">
                            <div class="flex flex-wrap items-center gap-2">
                                {{-- Category Pill --}}
                                <span class="px-2.5 py-0.5 rounded-md text-[10.5px] font-semibold bg-[#F7E6EE] text-[#8E1B5C] border border-[#E1CFD9]">
                                    {{ $complaint->categoryLabel() }}
                                </span>

                                {{-- Replies Count --}}
                                @php $replyCount = $complaint->replies->count(); @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10.5px] font-medium {{ $replyCount > 0 ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-slate-50 text-slate-500 border border-slate-200' }}">
                                    <svg class="w-3 h-3"><use href="#i-message-square"/></svg>
                                    <span>{{ $replyCount > 0 ? "{$replyCount} Tanggapan" : 'Belum Ada Balasan' }}</span>
                                </span>

                                {{-- Attachment indicator --}}
                                @if($complaint->attachment_path)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span>Lampiran</span>
                                    </span>
                                @endif
                            </div>

                            {{-- "Buka Keluhan" Action Button --}}
                            <div>
                                <a href="{{ route('platform.complaints.show', $complaint) }}"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white font-bold text-xs transition-all shadow-xs hover:shadow-md cursor-pointer">
                                    <span data-i18n="complaint_btn_detail">Buka Keluhan</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            {{-- Pagination Links --}}
            <div class="pt-2">
                {{ $complaints->links() }}
            </div>
        @endif

    </div>

</div>

{{-- 3D Interactive Tilt Script matching Dashboard & Settings --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const wrap = document.getElementById('complaintsHero3dWrap');
        const card = document.getElementById('complaints3dCard');
        if (!wrap || !card) return;

        wrap.addEventListener('mousemove', (e) => {
            const rect = wrap.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            const rotY = (x / (rect.width / 2)) * 14 - 10;
            const rotX = -(y / (rect.height / 2)) * 10 + 5;
            card.style.transform = `rotateY(${rotY}deg) rotateX(${rotX}deg) scale(1.02)`;
        });

        wrap.addEventListener('mouseleave', () => {
            card.style.transform = 'rotateY(-13deg) rotateX(7deg) rotateZ(-1deg) scale(1)';
        });
    });
</script>
@endsection

