@extends('platform.layouts.app', ['title' => 'Detail Tenant — ' . ($outlet->outlet_name ?? 'NAYARA')])

@section('content')
<div x-data="{ kelolaModal: false }" class="space-y-5">

    {{-- Top Back Navigation --}}
    <div class="-mt-1">
        <a href="{{ route('platform.tenants.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#8E1B5C] hover:text-[#68113F] transition-colors group">
            <svg class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span data-i18n="back_to_tenants">Kembali ke Direktori Tenant</span>
        </a>
    </div>

    {{-- 1. Header Profile Banner: Dark Burgundy Luxury (Konsisten dengan Tema Platform NAYARA) --}}
    <section class="rounded-2xl px-5 py-4 sm:px-6 sm:py-5 text-white relative overflow-hidden shadow-lg border border-white/10"
             style="background: linear-gradient(135deg, #691B3D 0%, #4A132E 55%, #350920 100%);">

        {{-- Ambient wave circles behind --}}
        <div class="absolute -right-16 -top-24 w-96 h-96 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
        <div class="absolute right-48 -bottom-20 w-80 h-80 rounded-full bg-[#8E1B5C]/20 blur-xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
            <div class="flex items-center gap-4 min-w-0">
                {{-- Outlet Monogram Avatar --}}
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-white/10 border border-white/20 text-white flex items-center justify-center font-black text-xl sm:text-2xl tracking-wider shadow-md shrink-0 backdrop-blur-xs">
                    {{ strtoupper(substr($outlet->outlet_name, 0, 2)) }}
                </div>

                <div class="space-y-1.5 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#FFD8D8] bg-white/10 px-2.5 py-0.5 rounded-full border border-white/15">
                            Informasi Perusahaan
                        </span>
                        @if($outlet->status === 'ACTIVE')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span>Aktif</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-200 border border-rose-400/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                <span>Nonaktif</span>
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-lg sm:text-xl font-black text-white tracking-tight leading-snug">
                            {{ $outlet->outlet_name }}
                        </h1>
                        <span class="font-mono text-xs font-bold text-white bg-white/15 px-2 py-0.5 rounded-lg border border-white/20">
                            {{ $outlet->outlet_code }}
                        </span>
                    </div>

                    <div class="flex items-center gap-4 text-xs text-[#F7E6EE]/80 flex-wrap pt-0.5">
                        <span class="flex items-center gap-1">
                            <span class="text-[#F7E6EE]/60">Pemilik:</span>
                            <strong class="text-white font-semibold">{{ $registration?->owner_name ?? '-' }}</strong>
                        </span>
                        <span class="text-white/30">•</span>
                        <span class="flex items-center gap-1">
                            <span class="text-[#F7E6EE]/60">Terdaftar:</span>
                            <span class="text-white font-medium">{{ $outlet->created_at ? $outlet->created_at->translatedFormat('d M Y') : '—' }}</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2.5 shrink-0 pt-2 md:pt-0">
                <a href="{{ url('/storefront/shops?search=' . urlencode($outlet->outlet_name)) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-bold rounded-xl transition shadow-2xs backdrop-blur-xs">
                    <svg class="w-3.5 h-3.5 text-white/90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>Etalase Publik</span>
                </a>
                <button type="button" @click="kelolaModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-100 text-[#520C31] text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-[#520C31]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
                    </svg>
                    <span>Kelola Tenant</span>
                </button>
            </div>
        </div>
    </section>

    {{-- 2. Ringkasan Metrik Operasional Tenant (Ringkas, Padat, Tanpa Redundansi) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        {{-- Card 1: Paket Langganan --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 shadow-2xs">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Paket SaaS</span>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-lg font-black text-[#8E1B5C] truncate">
                    {{ $subscription?->type === 'PREMIUM' ? 'SaaS Premium' : 'Free Tier' }}
                </span>
            </div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">
                {{ $subscription?->expires_at ? 'Hingga ' . $subscription->expires_at->format('d/m/Y') : 'Berlaku Tetap' }}
            </span>
        </div>

        {{-- Card 2: Status Akses --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 shadow-2xs">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Status Akses</span>
            <div class="mt-1 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full {{ $outlet->status === 'ACTIVE' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                <span class="text-lg font-black {{ $outlet->status === 'ACTIVE' ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $outlet->status === 'ACTIVE' ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">
                {{ $outlet->status === 'ACTIVE' ? 'Akses sistem berjalan normal' : 'Akses operasional login ditangguhkan' }}
            </span>
        </div>

        {{-- Card 3: Tagihan Platform --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 shadow-2xs">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Tagihan Platform</span>
            <div class="mt-1">
                <span class="text-lg font-black text-[#172B4D]">
                    {{ $invoicesCount }} Invoice
                </span>
            </div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">
                {{ $invoicesCount > 0 ? 'Total invoice diterbitkan' : 'Belum ada transaksi invoice' }}
            </span>
        </div>

        {{-- Card 4: Total Kontribusi --}}
        <div class="bg-white rounded-2xl border border-[#F0E4EA] p-4 shadow-2xs">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Pendapatan Terverifikasi</span>
            <div class="mt-1">
                <span class="text-lg font-black text-slate-900">
                    Rp {{ number_format($paidInvoicesSum, 0, ',', '.') }}
                </span>
            </div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">
                Total pembayaran sah ke platform
            </span>
        </div>
    </div>

    {{-- 3. Core Information Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        {{-- KOLOM KIRI (lg:col-span-5): Informasi Konveksi & Kontak --}}
        <div class="lg:col-span-5 space-y-5">
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs">
                <div class="flex items-center justify-between pb-3.5 border-b border-[#F0E4EA]">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-[#FFF0F5] text-[#8E1B5C] border border-[#F8D7E3] flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5"><use href="#i-building"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-[13px] font-bold text-[#172B4D]">Informasi Utama & Kontak Resmi</h3>
                            <p class="text-[10px] text-slate-400">Kontak & Lokasi konveksi terdaftar</p>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 text-xs">
                    {{-- Kode Tenant --}}
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Kode Tenant</span>
                        <span class="font-mono font-bold text-[#8E1B5C]">{{ $outlet->outlet_code }}</span>
                    </div>

                    {{-- Nama Konveksi / Outlet --}}
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Nama Konveksi / Outlet</span>
                        <span class="font-bold text-[#172B4D] truncate text-right">{{ $outlet->outlet_name }}</span>
                    </div>

                    {{-- Slug Publik --}}
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Slug Publik</span>
                        <span class="font-mono text-slate-600 truncate text-right">{{ $outlet->slug }}</span>
                    </div>

                    {{-- Pemilik / PIC --}}
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Pemilik / PIC</span>
                        <span class="font-semibold text-slate-800 text-right">{{ $registration?->owner_name ?? '-' }}</span>
                    </div>

                    {{-- WhatsApp / Kontak --}}
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">WhatsApp / Kontak</span>
                        @php
                            $phone = $outlet->phone_wa ?: ($registration?->phone_wa ?: null);
                        @endphp
                        @if($phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $phone) }}" target="_blank" class="font-semibold text-[#8E1B5C] hover:underline flex items-center gap-1">
                                <span>{{ $phone }}</span>
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </div>

                    {{-- Email --}}
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Email</span>
                        @php
                            $email = $outlet->email ?: ($registration?->email ?: null);
                        @endphp
                        @if($email)
                            <a href="mailto:{{ $email }}" class="text-slate-700 hover:text-[#8E1B5C] font-medium truncate text-right">{{ $email }}</a>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </div>

                    {{-- Alamat Workshop --}}
                    <div class="py-2.5 space-y-1">
                        <span class="text-slate-500 font-medium block">Alamat Workshop</span>
                        <p class="text-slate-700 leading-snug">
                            {{ $outlet->address ?: ($registration?->address ?: 'Kantor Pusat Konveksi NAYARA') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN (lg:col-span-7): Layanan & Langganan + Ringkasan Status --}}
        <div class="lg:col-span-7 space-y-5">

            {{-- Card 1: Layanan & Langganan --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs">
                <div class="flex items-center justify-between pb-3.5 border-b border-[#F0E4EA]">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-[#FFF0F5] text-[#8E1B5C] border border-[#F8D7E3] flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5"><use href="#i-credit-card"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-[13px] font-bold text-[#172B4D]">Layanan & Langganan</h3>
                            <p class="text-[10px] text-slate-400">Paket operasional SaaS NAYARA</p>
                        </div>
                    </div>
                    @if($invoicesCount > 0)
                        <a href="{{ route('platform.billing.index') }}" class="text-xs font-bold text-[#8E1B5C] hover:underline">
                            Lihat Invoice &rarr;
                        </a>
                    @endif
                </div>

                <div class="mt-3.5 rounded-xl border border-[#F0E4EA] overflow-hidden bg-white">
                    <table class="w-full text-left border-collapse table-fixed">
                        <thead>
                            <tr class="bg-[#FFF5F8] text-[#64748B] text-[10px] font-bold border-b border-[#F0E4EA] uppercase tracking-wider">
                                <th class="py-2.5 px-3 w-[28%]">LAYANAN</th>
                                <th class="py-2.5 px-3 w-[36%]">PAKET / DETAIL</th>
                                <th class="py-2.5 px-2 text-center w-[16%]">STATUS</th>
                                <th class="py-2.5 px-3 text-center w-[20%]">BERLAKU HINGGA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F0E4EA]/70 text-xs">
                            <tr class="hover:bg-[#FFF9FB]/60 transition-colors">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-800 truncate">
                                        {{ $subscription?->type === 'PREMIUM' ? 'SaaS Premium' : 'Free Tier Platform' }}
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-slate-500 text-[11px] leading-snug">
                                    {{ $subscription?->type === 'PREMIUM' ? 'Fitur lengkap ERP, slot produk ekspansi' : 'Paket Dasar ERP konveksi independen' }}
                                </td>
                                <td class="py-3 px-2 text-center">
                                    @if($outlet->status === 'ACTIVE')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Aktif</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span>Nonaktif</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center text-slate-600 text-[11px] font-medium">
                                    {{ $subscription?->expires_at ? $subscription->expires_at->format('d M Y') : 'Berlaku Tetap' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Card 2: Ringkasan Status & Tata Kelola (Status & Riwayat, Kelola Layanan) --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs">
                <div class="flex items-center justify-between pb-3.5 border-b border-[#F0E4EA]">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-[#FFF0F5] text-[#8E1B5C] border border-[#F8D7E3] flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5"><use href="#i-shield"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-[13px] font-bold text-[#172B4D]">Ringkasan Status & Riwayat</h3>
                            <p class="text-[10px] text-slate-400">Otoritas administratif Super Admin</p>
                        </div>
                    </div>
                    <button type="button" @click="kelolaModal = true" class="text-xs font-bold text-[#8E1B5C] hover:underline cursor-pointer">
                        Kelola Layanan &rarr;
                    </button>
                </div>

                <div class="mt-4 p-4 rounded-xl {{ $outlet->status === 'ACTIVE' ? 'bg-emerald-50/60 border border-emerald-200/70' : 'bg-rose-50/60 border border-rose-200/70' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-xs font-bold {{ $outlet->status === 'ACTIVE' ? 'text-emerald-900' : 'text-rose-900' }}">
                            {{ $outlet->status === 'ACTIVE' ? 'Akses Operasional Tenant Aktif' : 'Akses Operasional Tenant Ditangguhkan' }}
                        </span>
                        <p class="text-[11px] {{ $outlet->status === 'ACTIVE' ? 'text-emerald-700' : 'text-rose-700' }} leading-normal">
                            {{ $outlet->status === 'ACTIVE' ? 'Staf outlet dapat login dan menggunakan modul ERP NAYARA.' : 'Sesi login staf ditutup sementara. Data transaksi tetap tersimpan aman.' }}
                        </p>
                    </div>
                    <button type="button" @click="kelolaModal = true" class="px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer shrink-0 {{ $outlet->status === 'ACTIVE' ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                        {{ $outlet->status === 'ACTIVE' ? 'Suspend Akses' : 'Aktifkan Akses' }}
                    </button>
                </div>
            </div>

        </div>

    </div>

    {{-- Kelola Tenant Modal (Suspend / Reactivate Confirmation & Reason) --}}
    <div x-show="kelolaModal" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4" x-cloak>
        <div @click.away="kelolaModal = false" class="bg-white rounded-2xl border border-[#F8D7E3] shadow-xl max-w-md w-full p-5 space-y-3.5 relative animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl {{ $outlet->status === 'ACTIVE' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200' }} flex items-center justify-center shrink-0">
                    @if($outlet->status === 'ACTIVE')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 15V9m4 6V9"/>
                        </svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-[#172B4D] leading-tight">
                        {{ $outlet->status === 'ACTIVE' ? 'Suspend / Nonaktifkan Tenant' : 'Aktifkan Kembali Tenant' }}
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        {{ $outlet->outlet_name }} ({{ $outlet->outlet_code }})
                    </p>
                </div>
            </div>

            <p class="text-[11.5px] text-slate-600 leading-relaxed bg-[#FFF9FB] p-2.5 rounded-xl border border-[#F8D7E3]/60">
                @if($outlet->status === 'ACTIVE')
                    Tindakan ini akan menonaktifkan sesi login staf outlet ini ke ERP NAYARA. Seluruh data operasional, inventaris, dan keuangan tetap tersimpan aman.
                @else
                    Tindakan ini akan mengaktifkan kembali hak akses login ke ERP NAYARA untuk seluruh staf outlet ini.
                @endif
            </p>

            <form method="POST" action="{{ route('platform.tenants.toggle-status', $outlet) }}" class="space-y-3">
                @csrf
                @method('PATCH')

                <div class="space-y-1">
                    <label class="text-xs font-bold text-[#172B4D] block">
                        Alasan Administratif <span class="text-rose-500">*</span> (Wajib dicatat untuk Audit Log)
                    </label>
                    <textarea name="reason" rows="2.5" required placeholder="Contoh: Permintaan resmi pemilik outlet, investigasi administratif platform, atau reaktivasi berkala..." class="w-full bg-[#FFF9FB]/70 border border-[#F0E4EA] rounded-xl text-xs p-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] transition-all text-slate-700"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="kelolaModal = false" class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 text-xs font-semibold rounded-lg transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-3.5 py-1.5 {{ $outlet->status === 'ACTIVE' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white text-xs font-bold rounded-lg transition shadow-2xs cursor-pointer">
                        {{ $outlet->status === 'ACTIVE' ? 'Konfirmasi Suspend' : 'Konfirmasi Reaktivasi' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
