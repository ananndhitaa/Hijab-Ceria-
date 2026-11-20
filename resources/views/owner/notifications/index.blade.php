@extends('layouts.app')

@section('content')
<style>
    main { min-width: 0 !important; max-width: 100% !important; overflow-x: hidden; }
</style>

@php
if (!function_exists('getNotificationMeta')) {
    function getNotificationMeta(?string $type): array {
        $t = strtoupper($type ?? '');
        if (str_starts_with($t, 'AD_')) {
            return [
                'key' => 'ads',
                'label' => 'Iklan & Moderasi',
                'badge_bg' => 'bg-[#F7E6EE]',
                'badge_text' => 'text-[#8E1B5C]',
                'badge_border' => 'border-[#FFD8D8]',
                'icon_bg' => 'bg-[#F7E6EE]',
                'icon_text' => 'text-[#8E1B5C]',
                'icon_border' => 'border-[#FFD8D8]',
            ];
        } elseif (str_starts_with($t, 'BILLING_') || str_starts_with($t, 'INVOICE_') || str_starts_with($t, 'SUBSCRIPTION_') || str_starts_with($t, 'REFUND_')) {
            return [
                'key' => 'billing',
                'label' => 'Langganan & Tagihan',
                'badge_bg' => 'bg-emerald-50',
                'badge_text' => 'text-emerald-700',
                'badge_border' => 'border-emerald-200',
                'icon_bg' => 'bg-emerald-50',
                'icon_text' => 'text-emerald-600',
                'icon_border' => 'border-emerald-200',
            ];
        } elseif (str_starts_with($t, 'PRODUCT_') || str_starts_with($t, 'CATALOG_') || str_starts_with($t, 'SHOWCASE_') || str_starts_with($t, 'PUBLICATION_')) {
            return [
                'key' => 'showcase',
                'label' => 'Etalase Produk',
                'badge_bg' => 'bg-purple-50',
                'badge_text' => 'text-purple-700',
                'badge_border' => 'border-purple-200',
                'icon_bg' => 'bg-purple-50',
                'icon_text' => 'text-purple-600',
                'icon_border' => 'border-purple-200',
            ];
        } elseif (str_starts_with($t, 'SERVICE_')) {
            return [
                'key' => 'service',
                'label' => 'Layanan Platform',
                'badge_bg' => 'bg-sky-50',
                'badge_text' => 'text-sky-700',
                'badge_border' => 'border-sky-200',
                'icon_bg' => 'bg-sky-50',
                'icon_text' => 'text-sky-600',
                'icon_border' => 'border-sky-200',
            ];
        } else {
            return [
                'key' => 'system',
                'label' => 'Sistem & Kebijakan',
                'badge_bg' => 'bg-amber-50',
                'badge_text' => 'text-amber-800',
                'badge_border' => 'border-amber-200',
                'icon_bg' => 'bg-amber-50',
                'icon_text' => 'text-amber-700',
                'icon_border' => 'border-amber-200',
            ];
        }
    }
}
@endphp

<div class="space-y-6 sm:space-y-7 w-full min-w-0 max-w-full pb-10">

    {{-- ── 1. GLOBAL FEATURE HEADER WITH EXECUTIVE MONITORING BADGE ──────── --}}
    <x-feature-header
        title="Pusat Notifikasi Platform NAYARA"
        subtitle="Ringkasan pemberitahuan resmi mengenai aktivitas, iklan, moderasi, tagihan, dan layanan toko.">
        <x-slot:badge>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                EXECUTIVE MONITORING
            </span>
        </x-slot:badge>
        <x-slot:iconSlot>
            <div class="w-10 h-10 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
            </div>
        </x-slot:iconSlot>
        <x-slot:actions>
            <form action="{{ route('owner.notifications.read-all') }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                <button type="submit"
                        @if($unreadCount === 0) disabled @endif
                        :disabled="submitting || {{ $unreadCount === 0 ? 'true' : 'false' }}"
                        class="inline-flex items-center justify-center gap-2 h-10 px-4 sm:px-5 rounded-xl text-xs sm:text-sm font-semibold transition shadow-xs {{ $unreadCount > 0 ? 'bg-[#8E1B5C] text-white hover:bg-[#68113F] cursor-pointer' : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-70' }}">
                    <template x-if="!submitting">
                        <div class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 12.75l4 4 6.5-10" />
                            </svg>
                            <span>Tandai Semua Telah Dibaca</span>
                        </div>
                    </template>
                    <template x-if="submitting">
                        <div class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Memproses...</span>
                        </div>
                    </template>
                </button>
            </form>
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

    @if(session('error') || (isset($errors) && $errors->any()))
        <div class="w-full bg-rose-50 border border-rose-200 rounded-xl p-3.5 flex items-center gap-3 text-rose-800 text-xs sm:text-sm font-medium shadow-xs">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span class="flex-1">{{ session('error') ?: (isset($errors) ? $errors->first() : '') }}</span>
        </div>
    @endif

    {{-- ── 2. NOTIFICATION SUMMARY (EXECUTIVE METRIC STRIP) ────────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs divide-y sm:divide-y-0 sm:divide-x divide-slate-100 grid grid-cols-1 sm:grid-cols-3 w-full">
        {{-- Metric 1: Total Notifikasi --}}
        <div class="p-4 sm:p-5 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Notifikasi</span>
                <div class="mt-1.5 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-[#172B4D] tabular-nums tracking-tight">{{ number_format($totalCount, 0, ',', '.') }}</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1 truncate">Akumulasi pemberitahuan resmi</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
            </div>
        </div>

        {{-- Metric 2: Belum Dibaca --}}
        <div class="p-4 sm:p-5 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Belum Dibaca</span>
                <div class="mt-1.5 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold {{ $unreadCount > 0 ? 'text-[#8E1B5C]' : 'text-[#172B4D]' }} tabular-nums tracking-tight">{{ number_format($unreadCount, 0, ',', '.') }}</span>
                    @if($unreadCount > 0)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                            Perlu Tindak Lanjut
                        </span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-400 mt-1 truncate">{{ $unreadCount > 0 ? 'Pesan aktif memerlukan atensi' : 'Semua pesan sudah dibaca' }}</p>
            </div>
            <div class="w-9 h-9 rounded-lg {{ $unreadCount > 0 ? 'bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]' : 'bg-slate-100 text-slate-400 border border-slate-200' }} flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
            </div>
        </div>

        {{-- Metric 3: Sudah Dibaca --}}
        <div class="p-4 sm:p-5 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Sudah Dibaca</span>
                <div class="mt-1.5 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-[#172B4D] tabular-nums tracking-tight">{{ number_format($readCount, 0, ',', '.') }}</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1 truncate">Riwayat pesan telah ditinjau</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0" />
                </svg>
            </div>
        </div>
    </div>

    {{-- ── 3. FILTER TOOLBAR (STATUS & KATEGORI NOTIFIKASI) ──────────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs space-y-3.5 w-full">
        {{-- Status Filter Tabs --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-bold text-slate-500 mr-1 sm:mr-2">Status:</span>
                {{-- Tab 1: Semua --}}
                <a href="{{ route('owner.notifications.index', ['status' => 'all', 'category' => $category]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'all' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#F7E6EE] text-[#8E1B5C] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                    <span>Semua</span>
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] {{ $status === 'all' ? 'bg-white/20 text-white' : 'bg-[#8E1B5C]/10 text-[#8E1B5C]' }}">
                        {{ $totalCount }}
                    </span>
                </a>

                {{-- Tab 2: Belum Dibaca --}}
                <a href="{{ route('owner.notifications.index', ['status' => 'unread', 'category' => $category]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'unread' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#F7E6EE] text-[#8E1B5C] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                    <span>Belum Dibaca</span>
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] {{ $status === 'unread' ? 'bg-white/20 text-white' : 'bg-[#8E1B5C]/10 text-[#8E1B5C]' }}">
                        {{ $unreadCount }}
                    </span>
                </a>

                {{-- Tab 3: Sudah Dibaca --}}
                <a href="{{ route('owner.notifications.index', ['status' => 'read', 'category' => $category]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'read' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#F7E6EE] text-[#8E1B5C] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                    <span>Sudah Dibaca</span>
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] {{ $status === 'read' ? 'bg-white/20 text-white' : 'bg-[#8E1B5C]/10 text-[#8E1B5C]' }}">
                        {{ $readCount }}
                    </span>
                </a>
            </div>

            @if($status !== 'all' || $category !== 'all')
                <div>
                    <a href="{{ route('owner.notifications.index') }}" class="text-xs text-slate-500 hover:text-[#8E1B5C] font-semibold underline inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Reset Filter</span>
                    </a>
                </div>
            @endif
        </div>

        {{-- Category Filter Pills (Scrollable horizontally on mobile) --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none flex-nowrap sm:flex-wrap">
            <span class="text-xs font-bold text-slate-500 mr-1 sm:mr-2 shrink-0">Kategori:</span>

            @php
                $categories = [
                    'all' => 'Semua Kategori',
                    'ads' => 'Iklan & Moderasi',
                    'billing' => 'Langganan & Tagihan',
                    'showcase' => 'Etalase Produk',
                    'service' => 'Layanan Platform',
                    'system' => 'Sistem',
                ];
            @endphp

            @foreach($categories as $catKey => $catLabel)
                <a href="{{ route('owner.notifications.index', ['status' => $status, 'category' => $catKey]) }}"
                   class="shrink-0 px-2.5 py-1 rounded-lg text-xs font-semibold transition {{ $category === $catKey ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#F7E6EE] text-[#8E1B5C] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                    {{ $catLabel }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ── 4. NOTIFICATION INBOX (AREA UTAMA DAFTAR NOTIFIKASI) ─────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-card overflow-hidden w-full">
        {{-- Section Header --}}
        <div class="px-5 py-3.5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-[#172B4D] tracking-tight">Kotak Masuk Notifikasi</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                    {{ $notifications->total() }}
                </span>
            </div>
            <span class="text-xs text-slate-400">Diurutkan berdasarkan waktu terbaru</span>
        </div>

        @if($notifications->isEmpty())
            {{-- ── 5. EMPTY STATE (PREMIUM & MENYATU DENGAN WORKSPACE) ─────────── --}}
            <div class="py-14 px-6 text-center">
                <div class="w-12 h-12 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center mx-auto mb-3 shadow-2xs">
                    <svg class="w-6 h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Semua Notifikasi Terkendali</h3>
                <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto leading-relaxed">Belum ada pemberitahuan baru. Informasi aktivitas platform akan muncul di sini.</p>
            </div>
        @else
            @php
                $grouped = $notifications->getCollection()->groupBy(function($item) {
                    if ($item->created_at->isToday()) {
                        return 'Hari Ini';
                    } elseif ($item->created_at->isYesterday()) {
                        return 'Kemarin';
                    } else {
                        return 'Sebelumnya';
                    }
                });
            @endphp

            <div class="divide-y divide-slate-100">
                @foreach($grouped as $groupLabel => $items)
                    {{-- Group Header --}}
                    <div class="px-5 py-2.5 bg-slate-50/80 border-b border-t border-slate-100/80 flex items-center justify-between">
                        <span class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">{{ $groupLabel }}</span>
                        <span class="text-[11px] text-slate-400 font-semibold">{{ count($items) }} Notifikasi</span>
                    </div>

                    {{-- Group Notification Items --}}
                    @foreach($items as $notif)
                        @php
                            $meta = getNotificationMeta($notif->type);
                        @endphp
                        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 {{ $notif->is_read ? 'bg-white hover:bg-slate-50/60' : 'bg-[#FDF6F9] hover:bg-[#FCEFF5]/80' }} transition-colors">
                            <div class="flex items-start gap-3.5 min-w-0 flex-1">
                                {{-- Category Icon Container --}}
                                <div class="w-10 h-10 rounded-xl {{ $meta['icon_bg'] }} {{ $meta['icon_text'] }} border {{ $meta['icon_border'] }} flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                                    @if($meta['key'] === 'ads')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                        </svg>
                                    @elseif($meta['key'] === 'billing')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                        </svg>
                                    @elseif($meta['key'] === 'showcase')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.651V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009 9.75c.697 0 1.348-.238 1.867-.64a3.001 3.001 0 004.266 0c.52.402 1.17.64 1.867.64a2.993 2.993 0 002.49-1.015 3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l3.189 3.19a3.004 3.004 0 01-.621 4.72" />
                                        </svg>
                                    @elseif($meta['key'] === 'service')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.058a4.5 4.5 0 005.158-5.158 4.5 4.5 0 00-5.158-5.158 4.5 4.5 0 00-5.158 5.158c.13.58.106 1.193-.058 1.743m4.05 4.05L11.42 15.17" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                        </svg>
                                    @endif
                                </div>

                                {{-- Content Body --}}
                                <div class="space-y-1 min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $meta['badge_bg'] }} {{ $meta['badge_text'] }} border {{ $meta['badge_border'] }}">
                                            {{ $meta['label'] }}
                                        </span>
                                        <h4 class="text-sm font-bold text-[#172B4D] truncate">
                                            {{ $notif->title }}
                                        </h4>
                                        @if(!$notif->is_read)
                                            <span class="w-2 h-2 rounded-full bg-[#8E1B5C] ring-2 ring-white shrink-0 inline-block" title="Belum dibaca"></span>
                                        @endif
                                    </div>
                                    <p class="text-xs sm:text-sm text-[#64748B] leading-relaxed">
                                        {{ $notif->message }}
                                    </p>
                                    <div class="flex items-center gap-2 text-[11px] text-slate-400 font-medium pt-0.5">
                                        <span>{{ $notif->created_at->diffForHumans() }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $notif->created_at->format('d M Y, H:i') }} WIB</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="shrink-0 flex items-center gap-2 self-start sm:self-center pl-13 sm:pl-0">
                                @if(!$notif->is_read)
                                    <form action="{{ route('owner.notifications.read', $notif->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 h-8.5 px-3.5 rounded-lg {{ $notif->action_url ? 'bg-[#8E1B5C] hover:bg-[#68113F] text-white shadow-xs' : 'bg-white hover:bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]' }} text-xs font-semibold transition-colors cursor-pointer">
                                            @if($notif->action_url)
                                                <span>Buka &amp; Baca</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                            @else
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                                <span>Tandai Dibaca</span>
                                            @endif
                                        </button>
                                    </form>
                                @else
                                    @if($notif->action_url)
                                        <a href="{{ $notif->action_url }}"
                                           class="inline-flex items-center gap-1.5 h-8.5 px-3.5 rounded-lg bg-white hover:bg-slate-50 border border-slate-200 text-[#172B4D] text-xs font-semibold transition-colors">
                                            <span>Lihat Detail</span>
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                        </a>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-400 font-medium px-2 py-1">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            <span>Sudah dibaca</span>
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>

            @if($notifications->hasPages())
                <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                    {{ $notifications->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
