@php
    $tasks = $platformActionableTasks ?? \App\Services\Platform\PlatformNotificationService::getActionableTasks();
    $unreadNotifCount = (int) ($tasks['total'] ?? 0);
@endphp

<div class="relative" id="notificationDropdownWrap">
    <button type="button" 
            id="notificationDropdownBtn"
            class="relative p-2 rounded-xl hover:bg-rose-50 text-[#172B4D] transition-colors group cursor-pointer" 
            title="Notifikasi & Tugas Super Admin ({{ $unreadNotifCount }} item memerlukan tindakan)"
            aria-expanded="false"
            aria-haspopup="true">
        <svg class="w-5 h-5 group-hover:scale-105 transition-transform"><use href="#i-bell"/></svg>
        @if($unreadNotifCount > 0)
            <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-[#E11D48] text-white text-[9.5px] font-black flex items-center justify-center shadow-xs">
                {{ $unreadNotifCount }}
            </span>
        @endif
    </button>

    {{-- Popover Dropdown Menu --}}
    <div id="notificationDropdownMenu" 
         class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-[#F0E4EA] py-0 z-50 hidden transition-all duration-150 overflow-hidden"
         role="menu">
        {{-- Dropdown Header --}}
        <div class="px-4 py-3 bg-[#FFF9FB] border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#8E1B5C] text-white flex items-center justify-center text-xs shadow-xs">
                    <svg class="w-4 h-4"><use href="#i-bell"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-[#172B4D]" data-i18n="notif_title">Notifikasi &amp; Tugas Super Admin</h4>
                    <p class="text-[10px] text-slate-400" data-i18n="notif_subtitle">Pemberitahuan tugas yang perlu dikerjakan</p>
                </div>
            </div>
            @if($unreadNotifCount > 0)
                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                    {{ $unreadNotifCount }} <span data-i18n="notif_tasks_incoming">Tugas Masuk</span>
                </span>
            @else
                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200" data-i18n="notif_uptodate">
                    Up to Date
                </span>
            @endif
        </div>

        {{-- Actionable Tasks List --}}
        <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 p-2 space-y-1">
            {{-- 1. TAGIHAN PLATFORM (Verifikasi Pembayaran) --}}
            @if(($tasks['pending_invoices'] ?? 0) > 0)
                <div class="p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-200/80 hover:bg-emerald-50 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-black uppercase tracking-wider bg-emerald-600 text-white" data-i18n="notif_badge_billing">Tagihan Platform</span>
                            <h5 class="text-xs font-bold text-[#172B4D] mt-1 truncate" data-i18n="notif_invoices_title">{{ $tasks['pending_invoices'] }} Invoice Menunggu Verifikasi</h5>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug" data-i18n="notif_invoices_desc">Bukti pembayaran langganan SaaS siap diverifikasi.</p>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <a href="{{ route('platform.billing.index') }}" 
                           class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:underline">
                            <span data-i18n="notif_invoices_action">Verifikasi Sekarang</span>
                            <svg class="w-3 h-3"><use href="#i-arrow"/></svg>
                        </a>
                    </div>
                </div>
            @endif

            {{-- 2. REVIEW PENDAFTARAN --}}
            @if(($tasks['pending_registrations'] ?? 0) > 0)
                <div class="p-2.5 rounded-xl bg-amber-50/60 border border-amber-200/80 hover:bg-amber-50 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-black uppercase tracking-wider bg-amber-500 text-white" data-i18n="notif_badge_review">Review Pendaftaran</span>
                            <h5 class="text-xs font-bold text-[#172B4D] mt-1 truncate" data-i18n="notif_pending_title">{{ $tasks['pending_registrations'] }} Pengajuan Baru Menunggu Review</h5>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug" data-i18n="notif_pending_desc">Calon tenant mengajukan pendaftaran mandiri dan menunggu verifikasi.</p>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <a href="{{ route('platform.registrations.index') }}" 
                           class="inline-flex items-center gap-1 text-[11px] font-bold text-[#8E1B5C] hover:underline">
                            <span data-i18n="notif_pending_action">Tinjau Antrean Pendaftaran</span>
                            <svg class="w-3 h-3"><use href="#i-arrow"/></svg>
                        </a>
                    </div>
                </div>
            @endif

            {{-- 3. PERLU REVISI BERKAS --}}
            @if(($tasks['needs_revision'] ?? 0) > 0)
                <div class="p-2.5 rounded-xl bg-rose-50/60 border border-rose-200/80 hover:bg-rose-50 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-black uppercase tracking-wider bg-rose-600 text-white" data-i18n="notif_badge_revision">Perlu Revisi</span>
                            <h5 class="text-xs font-bold text-[#172B4D] mt-1 truncate" data-i18n="notif_revision_title">{{ $tasks['needs_revision'] }} Berkas Memerlukan Perbaikan</h5>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug" data-i18n="notif_revision_desc">Data pendaftaran telah dikembalikan untuk diperbaiki oleh pemohon.</p>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <a href="{{ route('platform.registrations.index') }}" 
                           class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 hover:underline">
                            <span data-i18n="notif_revision_action">Lihat Status Berkas</span>
                            <svg class="w-3 h-3"><use href="#i-arrow"/></svg>
                        </a>
                    </div>
                </div>
            @endif

            {{-- 4. MODERASI IKLAN --}}
            @if(($tasks['pending_ads'] ?? 0) > 0)
                <div class="p-2.5 rounded-xl bg-purple-50/60 border border-purple-200/80 hover:bg-purple-50 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-black uppercase tracking-wider bg-purple-600 text-white" data-i18n="notif_badge_ads">Review Iklan</span>
                            <h5 class="text-xs font-bold text-[#172B4D] mt-1 truncate" data-i18n="notif_ads_title">{{ $tasks['pending_ads'] }} Kampanye Iklan Menunggu Review</h5>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug" data-i18n="notif_ads_desc">Materi promosi tenant siap diverifikasi sebelum tayang di etalase.</p>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <a href="{{ route('platform.ads.index') }}" 
                           class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-700 hover:underline">
                            <span data-i18n="notif_ads_action">Moderasi Iklan Sekarang</span>
                            <svg class="w-3 h-3"><use href="#i-arrow"/></svg>
                        </a>
                    </div>
                </div>
            @endif

            {{-- 5. PENGEMBALIAN DANA (REFUNDS) --}}
            @if(($tasks['pending_refunds'] ?? 0) > 0)
                <div class="p-2.5 rounded-xl bg-orange-50/60 border border-orange-200/80 hover:bg-orange-50 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-black uppercase tracking-wider bg-orange-600 text-white" data-i18n="notif_badge_refunds">Pengembalian Dana</span>
                            <h5 class="text-xs font-bold text-[#172B4D] mt-1 truncate" data-i18n="notif_refunds_title">{{ $tasks['pending_refunds'] }} Pengajuan Refund Menunggu Pencairan</h5>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug" data-i18n="notif_refunds_desc">Permintaan pencairan dana tenant siap diproses.</p>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <a href="{{ route('platform.refunds.index') }}" 
                           class="inline-flex items-center gap-1 text-[11px] font-bold text-orange-700 hover:underline">
                            <span data-i18n="notif_refunds_action">Proses Refund Sekarang</span>
                            <svg class="w-3 h-3"><use href="#i-arrow"/></svg>
                        </a>
                    </div>
                </div>
            @endif

            {{-- 6. KELUHAN TENANT --}}
            @if(($tasks['pending_complaints'] ?? 0) > 0)
                <div class="p-2.5 rounded-xl bg-sky-50/60 border border-sky-200/80 hover:bg-sky-50 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="inline-block px-1.5 py-0.5 rounded text-[8.5px] font-black uppercase tracking-wider bg-sky-600 text-white" data-i18n="notif_badge_complaints">Keluhan Tenant</span>
                            <h5 class="text-xs font-bold text-[#172B4D] mt-1 truncate" data-i18n="notif_complaints_title">{{ $tasks['pending_complaints'] }} Keluhan Owner Butuh Tindakan</h5>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug" data-i18n="notif_complaints_desc">Tiket keluhan operasional atau kendala fitur memerlukan respon Super Admin.</p>
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <a href="{{ route('platform.complaints.index') }}" 
                           class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-700 hover:underline">
                            <span data-i18n="notif_complaints_action">Tanggapi Keluhan</span>
                            <svg class="w-3 h-3"><use href="#i-arrow"/></svg>
                        </a>
                    </div>
                </div>
            @endif

            {{-- 7. EMPTY STATE (Ketika semua tugas bersih / up to date) --}}
            @if($unreadNotifCount === 0)
                <div class="py-6 text-center text-slate-400 space-y-1">
                    <svg class="w-7 h-7 text-emerald-500 mx-auto"><use href="#i-check"/></svg>
                    <p class="text-xs font-bold text-[#172B4D]" data-i18n="notif_empty_title">Tidak Ada Tugas Menunggu</p>
                    <p class="text-[10.5px]" data-i18n="notif_empty_desc">Semua pengajuan pendaftaran dan tagihan telah bersih diproses.</p>
                </div>
            @endif
        </div>

        {{-- Footer Link --}}
        <div class="p-2.5 bg-[#FAF0F5] border-t border-slate-100 text-center text-xs">
            <a href="{{ route('platform.notifications.index') }}" class="text-xs font-bold text-[#8E1B5C] hover:underline inline-flex items-center justify-center gap-1">
                <span data-i18n="notif_full_center">Buka Pusat Notifikasi Lengkap</span>
                <span class="text-xs">&rarr;</span>
            </a>
        </div>
    </div>
</div>

