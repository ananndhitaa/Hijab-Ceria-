@extends('layouts.app')

@section('content')
<style>
    main { min-width: 0 !important; max-width: 100% !important; overflow-x: hidden; }
</style>

<div class="space-y-6 sm:space-y-7 w-full min-w-0 max-w-full pb-10">

    {{-- ── 1. GLOBAL FEATURE HEADER WITH EXECUTIVE MONITORING BADGE ──────── --}}
    <x-feature-header
        title="Keluhan & Pusat Bantuan"
        subtitle="Sampaikan kendala sistem, pertanyaan akun, tagihan, atau langganan langsung ke tim Super Admin NAYARA.">
        <x-slot:badge>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                EXECUTIVE MONITORING
            </span>
        </x-slot:badge>
        <x-slot:iconSlot>
            <div class="w-10 h-10 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px] text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
            </div>
        </x-slot:iconSlot>
        <x-slot:actions>
            <a href="{{ route('owner.complaints.create') }}"
               class="inline-flex items-center justify-center gap-2 h-10 px-4 sm:px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#68113F] transition shadow-xs cursor-pointer">
                <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Buat Keluhan Baru</span>
            </a>
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

    {{-- ── 2. METRIC STATS CARDS ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total --}}
        <div class="kpi-card bg-white rounded-2xl border border-slate-200/90 p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider whitespace-nowrap">TOTAL TIKET</span>
                <div class="w-6 h-6 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ number_format($stats['total']) }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5">
                    Seluruh keluhan diajukan
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
        </div>

        {{-- Diproses --}}
        <div class="kpi-card bg-white rounded-2xl border border-slate-200/90 p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider whitespace-nowrap">SEDANG DIPROSES</span>
                <div class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ number_format($stats['in_progress']) }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5">
                    Dalam penanganan teknis
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
        </div>

        {{-- Menunggu Balasan Owner --}}
        <div class="kpi-card bg-white rounded-2xl border border-slate-200/90 p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider whitespace-nowrap">PERLU RESPON ANDA</span>
                <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 border border-purple-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ number_format($stats['waiting_owner']) }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5">
                    Menunggu jawaban Anda
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
        </div>

        {{-- Selesai --}}
        <div class="kpi-card bg-white rounded-2xl border border-slate-200/90 p-5 pb-5 relative overflow-hidden shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between min-h-[142px]">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-extrabold uppercase text-slate-800 tracking-wider whitespace-nowrap">KELUHAN SELESAI</span>
                <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <div class="mt-2.5">
                <div class="text-[28px] font-black text-[#520C31] tracking-tight leading-none">
                    {{ number_format($stats['resolved']) }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium leading-normal mt-1.5">
                    Tiket tuntas diselesaikan
                </div>
                <div class="w-3/5 h-0.5 bg-slate-200/90 rounded-full mt-3"></div>
            </div>
        </div>
    </div>

    {{-- ── 3. FILTER & PENCARIAN ─────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs">
        <form action="{{ route('owner.complaints.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full relative">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cari ID tiket atau judul pengaduan..."
                       class="w-full h-10 pl-9 pr-3 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] outline-hidden text-[#172B4D]">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="w-full sm:w-48">
                <select name="category" onchange="this.form.submit()" class="w-full h-10 px-3 text-xs rounded-xl border border-slate-200 bg-slate-50/50 text-[#172B4D]">
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

            <div class="w-full sm:w-44">
                <select name="status" onchange="this.form.submit()" class="w-full h-10 px-3 text-xs rounded-xl border border-slate-200 bg-slate-50/50 text-[#172B4D]">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Semua Status</option>
                    <option value="NEW" {{ request('status') === 'NEW' ? 'selected' : '' }}>Baru Masuk</option>
                    <option value="IN_PROGRESS" {{ request('status') === 'IN_PROGRESS' ? 'selected' : '' }}>Sedang Diproses</option>
                    <option value="WAITING_OWNER" {{ request('status') === 'WAITING_OWNER' ? 'selected' : '' }}>Menunggu Balasan</option>
                    <option value="resolved_all" {{ request('status') === 'resolved_all' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="h-10 px-4 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold transition shadow-xs">
                    Cari
                </button>
                @if(request()->hasAny(['search', 'category', 'status']))
                    <a href="{{ route('owner.complaints.index') }}" class="h-10 px-3 rounded-xl border border-slate-200 bg-slate-100 text-slate-600 text-xs flex items-center justify-center hover:bg-slate-200 transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ── 4. DAFTAR KELUHAN ──────────────────────────────────────────────────── --}}
    @if($complaints->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200/90 p-10 sm:p-14 text-center space-y-4 shadow-xs">
            <div class="w-14 h-14 rounded-full bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center mx-auto">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-extrabold text-[#172B4D]">Belum Ada Keluhan yang Diajukan</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Jika Anda menemukan kendala teknis, pertanyaan operasional, atau bantuan pembayaran, klik tombol di bawah untuk membuat pengaduan resmi.
                </p>
            </div>
            <div class="pt-2">
                <a href="{{ route('owner.complaints.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#8E1B5C] text-white text-xs font-bold hover:bg-[#68113F] transition shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>Buat Pengaduan Sekarang</span>
                </a>
            </div>
        </div>
    @else
        <div class="space-y-3.5">
            @foreach($complaints as $complaint)
                <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs hover:shadow-md hover:border-[#8E1B5C]/30 transition flex flex-col justify-between gap-3.5">
                    {{-- Top Row --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-slate-100">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-black bg-slate-100 text-[#172B4D] font-mono border border-slate-200">
                                #{{ $complaint->complaint_code }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-[#F7E6EE] text-[#8E1B5C]">
                                {{ $complaint->categoryLabel() }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold border {{ $complaint->statusBadgeClass() }}">
                                {{ $complaint->statusLabel() }}
                            </span>
                        </div>
                        <span class="text-[11px] text-slate-400">
                            {{ $complaint->created_at ? $complaint->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}
                        </span>
                    </div>

                    {{-- Middle Body --}}
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-[#172B4D] hover:text-[#8E1B5C] transition">
                            <a href="{{ route('owner.complaints.show', $complaint) }}">
                                {{ $complaint->title }}
                            </a>
                        </h3>
                        <p class="text-xs text-slate-600 line-clamp-2 mt-1 leading-relaxed">
                            {{ $complaint->description }}
                        </p>
                    </div>

                    {{-- Bottom Footer --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2.5 border-t border-slate-100">
                        <div class="flex flex-wrap items-center gap-2">
                            @php $replyCount = $complaint->replies->count(); @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10.5px] font-medium {{ $replyCount > 0 ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-slate-50 text-slate-500 border border-slate-200' }}">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <span>{{ $replyCount > 0 ? "{$replyCount} Tanggapan" : 'Menunggu Tanggapan Super Admin' }}</span>
                            </span>
                            @if($complaint->attachment_path)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span>Lampiran</span>
                                </span>
                            @endif
                        </div>

                        <div>
                            <a href="{{ route('owner.complaints.show', $complaint) }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold transition shadow-xs">
                                <span>Buka Percakapan</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="pt-2">
                {{ $complaints->links() }}
            </div>
        </div>
    @endif

</div>
@endsection
