@extends('platform.layouts.app', ['title' => 'Detail Pendaftaran — ' . $registration->company_name])

@section('content')
<div x-data="{
    approveModal: false,
    revisionModal: false,
    rejectModal: false
}" class="space-y-6">

    {{-- 1. Hero Header Banner: Dark Burgundy Luxury (Konsisten dengan Dashboard Platform & Antrean) --}}
    <section class="rounded-2xl px-4 py-2.5 sm:px-5 sm:py-3 text-white relative overflow-hidden shadow-lg border border-white/10"
             style="background: linear-gradient(135deg, #691B3D 0%, #4A132E 55%, #350920 100%);">

        {{-- Ambient wave circles behind --}}
        <div class="absolute -right-16 -top-24 w-96 h-96 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
        <div class="absolute right-48 -bottom-20 w-80 h-80 rounded-full bg-[#8E1B5C]/20 blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3 relative z-10 min-w-0">
                {{-- Clipboard Icon in Rounded Square Container --}}
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/10 border border-white/20 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <svg class="w-4.5 h-4.5 sm:w-5 sm:h-5"><use href="#i-clipboard"/></svg>
                </div>

                <div class="space-y-0.5 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15">
                            <span data-i18n="show_badge">PLATFORM OTORITAS &rsaquo; PENDAFTARAN #{{ $registration->id }}</span>
                        </span>
                        @if($registration->status === 'PENDING')
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-500/20 text-amber-200 border border-amber-400/30" data-i18n="status_pending">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                <span>Menunggu Review</span>
                            </span>
                        @elseif($registration->status === 'APPROVED')
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30" data-i18n="status_approved">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span>Disetujui</span>
                            </span>
                        @elseif($registration->status === 'NEEDS_REVISION')
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-500/20 text-rose-200 border border-rose-400/30" data-i18n="status_needs_revision">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                <span>Perlu Revisi</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-red-500/20 text-red-200 border border-red-400/30" data-i18n="status_rejected">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                <span>Ditolak</span>
                            </span>
                        @endif
                    </div>

                    <h1 class="text-base sm:text-lg font-black text-white tracking-tight leading-snug truncate">
                        {{ $registration->company_name }}
                    </h1>

                    <p class="text-[11px] text-[#F7E6EE]/90 leading-tight font-normal truncate">
                        <span data-i18n="submitted_on">Diajukan pada</span> {{ \App\Support\DateTimeFormatter::formatLongDateTime($registration->created_at) }} {{ $registration->created_at ? '(' . $registration->created_at->diffForHumans() . ')' : '' }}
                    </p>
                </div>
            </div>

            {{-- Right Side Actions: Back Button & Compact 3D Tablet Graphic --}}
            <div class="flex items-center gap-3 relative z-10 shrink-0">
                <a href="{{ route('platform.registrations.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-[#FFF9FB] text-[#520C31] text-xs font-bold rounded-xl transition-all shadow-xs">
                    <span>&larr;</span>
                    <span data-i18n="btn_back_queue">Kembali ke Antrean</span>
                </a>

                <div class="hidden xl:flex items-center justify-end shrink-0 pointer-events-none select-none pl-1 glass-tablet-3d-wrap">
                    <div class="glass-tablet-3d w-44 p-2.5 relative overflow-hidden flex items-center justify-between gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#E11D48] via-[#ba4e77] to-[#8E1B5C] border border-white/40 flex flex-col items-center justify-center shadow-xs p-0.5 shrink-0">
                            <span class="font-serif font-black text-white text-sm leading-none italic select-none">n</span>
                        </div>
                        <div class="flex-1 bg-white/10 border border-white/20 rounded-lg p-1.5 backdrop-blur-md flex flex-col justify-between h-14">
                            <div class="w-12 h-1 bg-white/40 rounded-full"></div>
                            <div class="flex items-end justify-between gap-1 h-7">
                                <div class="w-2.5 h-[40%] bg-white/30 rounded-t-xs"></div>
                                <div class="w-2.5 h-[65%] bg-white/45 rounded-t-xs"></div>
                                <div class="w-2.5 h-[90%] bg-[#FFD8D8]/70 rounded-t-xs"></div>
                                <div class="w-2.5 h-[50%] bg-white/35 rounded-t-xs"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Main Registration Detail Card --}}
    <div class="bg-white rounded-2xl border border-[#F0E4EA] shadow-xs overflow-hidden">
        {{-- Card Header: Clean Title with Burgundy Indicator --}}
        <div class="px-6 py-4.5 border-b border-[#F0E4EA] bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-1.5 h-5 rounded-full bg-[#8E1B5C] shrink-0"></span>
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-[#172B4D] leading-tight" data-i18n="detail_card_title">
                        Berkas Verifikasi Pendaftaran Tenant
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5" data-i18n="detail_card_subtitle">
                        Periksa kelengkapan identitas pemilik dan informasi operasional konveksi sebelum memberikan keputusan.
                    </p>
                </div>
            </div>

            @if($registration->isApproved() && $registration->outlet)
                <div class="p-2.5 px-3.5 bg-[#ECFDF5] rounded-xl border border-[#A7F3D0] text-xs text-[#065F46] flex items-center gap-2.5 shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="leading-tight">
                        <span class="font-bold block">Outlet Aktif: {{ $registration->outlet->outlet_code }}</span>
                        <a href="{{ route('platform.tenants.show', $registration->outlet) }}" class="text-emerald-700 hover:underline font-semibold text-[11px]">
                            Lihat di Direktori Tenant &rarr;
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Applicant Details Grid: 2 Columns of Information Tiles --}}
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Column 1: Identitas Pemilik & Akun --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-[#FAF0F5] text-[#8E1B5C] border border-[#F0D5E2] flex items-center justify-center shrink-0 shadow-2xs">
                        <svg class="w-3.5 h-3.5"><use href="#i-users"/></svg>
                    </div>
                    <h3 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider" data-i18n="sec_owner_identity">
                        Identitas Pemilik &amp; Akun
                    </h3>
                </div>

                <div class="space-y-3">
                    {{-- Nama Pemilik --}}
                    <div class="p-3.5 rounded-xl bg-[#FFF9FB]/60 border border-[#F0E4EA]">
                        <span class="text-slate-400 block text-[10.5px] font-bold uppercase tracking-wider" data-i18n="label_owner_name">Nama Pemilik (Owner)</span>
                        <span class="text-sm font-bold text-[#172B4D] mt-0.5 block">{{ $registration->owner_name }}</span>
                    </div>

                    {{-- Email Bisnis --}}
                    <div class="p-3.5 rounded-xl bg-[#FFF9FB]/60 border border-[#F0E4EA]">
                        <span class="text-slate-400 block text-[10.5px] font-bold uppercase tracking-wider" data-i18n="label_business_email">Email Bisnis / Akun</span>
                        <span class="text-sm font-bold text-[#172B4D] mt-0.5 block font-mono">{{ $registration->email }}</span>
                    </div>

                    {{-- WhatsApp --}}
                    <div class="p-3.5 rounded-xl bg-[#FFF9FB]/60 border border-[#F0E4EA]">
                        <span class="text-slate-400 block text-[10.5px] font-bold uppercase tracking-wider" data-i18n="label_whatsapp">Nomor WhatsApp / Telepon</span>
                        <span class="text-sm font-bold text-[#172B4D] mt-0.5 block">{{ $registration->phone_wa }}</span>
                    </div>
                </div>
            </div>

            {{-- Column 2: Informasi Usaha Konveksi --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-[#FAF0F5] text-[#8E1B5C] border border-[#F0D5E2] flex items-center justify-center shrink-0 shadow-2xs">
                        <svg class="w-3.5 h-3.5"><use href="#i-building"/></svg>
                    </div>
                    <h3 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider" data-i18n="sec_business_info">
                        Informasi Usaha Konveksi
                    </h3>
                </div>

                <div class="space-y-3">
                    {{-- Nama Konveksi --}}
                    <div class="p-3.5 rounded-xl bg-[#FFF9FB]/60 border border-[#F0E4EA]">
                        <span class="text-slate-400 block text-[10.5px] font-bold uppercase tracking-wider" data-i18n="label_company_name">Nama Perusahaan / Konveksi</span>
                        <span class="text-sm font-bold text-[#172B4D] mt-0.5 block">{{ $registration->company_name }}</span>
                    </div>

                    {{-- Alamat Workshop --}}
                    <div class="p-3.5 rounded-xl bg-[#FFF9FB]/60 border border-[#F0E4EA]">
                        <span class="text-slate-400 block text-[10.5px] font-bold uppercase tracking-wider" data-i18n="label_address">Alamat Workshop / Workshop</span>
                        <span class="text-xs font-medium text-slate-700 mt-0.5 block leading-relaxed">{{ $registration->address ?: 'Tidak dicantumkan' }}</span>
                    </div>

                    {{-- Catatan Pengajuan --}}
                    <div class="p-3.5 rounded-xl bg-[#FFF9FB]/60 border border-[#F0E4EA]">
                        <span class="text-slate-400 block text-[10.5px] font-bold uppercase tracking-wider" data-i18n="label_notes">Catatan Pengajuan</span>
                        <span class="text-xs font-medium text-slate-700 mt-0.5 block">{{ $registration->notes ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Special Notes for NEEDS_REVISION or REJECTED --}}
        @if($registration->isNeedsRevision() && $registration->revision_notes)
            <div class="mx-6 mb-6 p-4 rounded-xl bg-[#FFFBEB] border border-[#FDE68A] text-[#92400E] text-xs">
                <p class="font-bold flex items-center gap-1.5 mb-1 text-[#B45309]">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span data-i18n="note_revision_title">Catatan Perbaikan / Revisi yang Diminta:</span>
                </p>
                <p class="text-slate-700 whitespace-pre-line pl-5.5">{{ $registration->revision_notes }}</p>
            </div>
        @endif

        @if($registration->isRejected() && $registration->rejection_reason)
            <div class="mx-6 mb-6 p-4 rounded-xl bg-[#FEF2F2] border border-[#FECACA] text-[#991B1B] text-xs">
                <p class="font-bold flex items-center gap-1.5 mb-1 text-[#B91C1C]">
                    <svg class="w-4 h-4 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span data-i18n="note_rejection_title">Alasan Penolakan:</span>
                </p>
                <p class="text-slate-700 whitespace-pre-line pl-5.5">{{ $registration->rejection_reason }}</p>
            </div>
        @endif

        {{-- Action Buttons Toolbar --}}
        @if(!$registration->isApproved())
            <div class="p-5 sm:p-6 bg-[#FFF9FB]/70 border-t border-[#F0E4EA] flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <button type="button" @click="rejectModal = true" class="px-4 py-2.5 bg-white border border-[#FECDD3] text-[#BE123C] hover:bg-[#FFF1F2] hover:border-[#FDA4AF] rounded-xl text-xs font-bold transition-all shadow-2xs flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5"><use href="#i-cross"/></svg>
                        <span data-i18n="btn_reject">Tolak Pendaftaran</span>
                    </button>
                    <button type="button" @click="revisionModal = true" class="px-4 py-2.5 bg-white border border-[#FDE68A] text-[#B45309] hover:bg-[#FFFBEB] hover:border-[#FCD34D] rounded-xl text-xs font-bold transition-all shadow-2xs flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span data-i18n="btn_request_revision">Minta Perbaikan / Revisi</span>
                    </button>
                </div>
                <div>
                    <button type="button" @click="approveModal = true" class="px-5 py-2.5 bg-[#047857] hover:bg-[#065F46] text-white rounded-xl text-xs font-bold transition-all shadow-sm hover:shadow flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4"><use href="#i-check"/></svg>
                        <span data-i18n="btn_approve_activate">Setujui &amp; Aktivasi Tenant (Free ERP)</span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- 3. Audit Timeline --}}
    <div class="bg-white rounded-2xl border border-[#F0E4EA] shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-[#F0E4EA] bg-white flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-1.5 h-5 rounded-full bg-[#8E1B5C] shrink-0"></span>
                <div>
                    <h3 class="text-sm font-bold text-[#172B4D] leading-tight" data-i18n="audit_trail_title">
                        Riwayat Keputusan &amp; Jejak Audit
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5" data-i18n="audit_trail_subtitle">
                        Catatan kronologis tindakan dan status verifikasi dari pendaftaran ini.
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6">
            @if($registration->histories->isEmpty())
                <p class="text-xs text-slate-400" data-i18n="audit_empty">Belum ada riwayat tercatat.</p>
            @else
                <div class="relative border-l-2 border-[#F0E4EA] ml-3 space-y-6 py-2">
                    @foreach($registration->histories as $history)
                        <div class="relative pl-6">
                            <span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full border-2 border-white bg-[#8E1B5C] shadow-2xs"></span>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="font-bold text-[#172B4D]">{{ $history->actionLabel() }}</span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-slate-500 text-[11px]">{{ \App\Support\DateTimeFormatter::formatDateTime($history->created_at) }}</span>
                                @if($history->actor)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#FAF0F5] text-[#8E1B5C] border border-[#F0D5E2]">
                                        Oleh: {{ $history->actor->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Pemohon
                                    </span>
                                @endif
                            </div>
                            @if($history->notes)
                                <p class="text-xs text-slate-700 mt-2 bg-[#FAF0F5]/50 p-3 rounded-xl border border-[#F0E4EA] leading-relaxed">{{ $history->notes }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Modal: Setujui Pendaftaran --}}
    <div x-show="approveModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
         x-cloak>
        <div @click.away="approveModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-[#F0E4EA] space-y-4">
            <div class="w-11 h-11 rounded-xl bg-[#ECFDF5] text-emerald-600 border border-[#A7F3D0] flex items-center justify-center shadow-2xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div>
                <h4 class="text-base font-bold text-[#172B4D]">Setujui Pendaftaran Tenant?</h4>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Tindakan ini akan mengaktifkan <strong>{{ $registration->company_name }}</strong> sebagai tenant baru yang sah. Sistem akan membuat outlet baru dan mengaktifkan akun Owner (<span class="font-mono text-[#8E1B5C]">{{ $registration->email }}</span>).
                </p>
            </div>
            <form method="POST" action="{{ route('platform.registrations.approve', $registration) }}">
                @csrf
                <div class="flex justify-end gap-2.5 pt-3 border-t border-[#F0E4EA]">
                    <button type="button" @click="approveModal = false" class="px-4 py-2 border border-[#F0E4EA] text-slate-600 text-xs font-semibold rounded-xl hover:bg-[#FAF0F5] transition cursor-pointer">Batal</button>
                    <button type="submit" class="px-4.5 py-2 bg-[#047857] hover:bg-[#065F46] text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">Ya, Setujui &amp; Aktifkan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Minta Perbaikan --}}
    <div x-show="revisionModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
         x-cloak>
        <div @click.away="revisionModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-[#F0E4EA] space-y-4">
            <div class="w-11 h-11 rounded-xl bg-[#FFFBEB] text-amber-600 border border-[#FDE68A] flex items-center justify-center shadow-2xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h4 class="text-base font-bold text-[#172B4D]">Minta Perbaikan / Revisi Data</h4>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Tuliskan instruksi perbaikan yang harus diperbaiki oleh pemohon pendaftaran.</p>
            </div>
            <form method="POST" action="{{ route('platform.registrations.request-revision', $registration) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-[#172B4D] mb-1">Catatan Perbaikan <span class="text-rose-500">*</span></label>
                    <textarea name="revision_notes" rows="3" required minlength="10" maxlength="1000" class="w-full text-xs rounded-xl border border-[#F0E4EA] p-3 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C]" placeholder="Jelaskan bagian data yang perlu diperbaiki (min. 10 karakter)..."></textarea>
                </div>
                <div class="flex justify-end gap-2.5 pt-2 border-t border-[#F0E4EA]">
                    <button type="button" @click="revisionModal = false" class="px-4 py-2 border border-[#F0E4EA] text-slate-600 text-xs font-semibold rounded-xl hover:bg-[#FAF0F5] transition cursor-pointer">Batal</button>
                    <button type="submit" class="px-4.5 py-2 bg-[#D97706] hover:bg-[#B45309] text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">Kirim Permintaan Revisi</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Tolak Pendaftaran --}}
    <div x-show="rejectModal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
         x-cloak>
        <div @click.away="rejectModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-[#F0E4EA] space-y-4">
            <div class="w-11 h-11 rounded-xl bg-[#FEF2F2] text-red-600 border border-[#FECACA] flex items-center justify-center shadow-2xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <div>
                <h4 class="text-base font-bold text-[#172B4D]">Tolak Pendaftaran</h4>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Tuliskan alasan resmi penolakan pengajuan tenant ini.</p>
            </div>
            <form method="POST" action="{{ route('platform.registrations.reject', $registration) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-[#172B4D] mb-1">Alasan Penolakan <span class="text-rose-500">*</span></label>
                    <textarea name="rejection_reason" rows="3" required minlength="10" maxlength="1000" class="w-full text-xs rounded-xl border border-[#F0E4EA] p-3 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C]" placeholder="Alasan penolakan pendaftaran (min. 10 karakter)..."></textarea>
                </div>
                <div class="flex justify-end gap-2.5 pt-2 border-t border-[#F0E4EA]">
                    <button type="button" @click="rejectModal = false" class="px-4 py-2 border border-[#F0E4EA] text-slate-600 text-xs font-semibold rounded-xl hover:bg-[#FAF0F5] transition cursor-pointer">Batal</button>
                    <button type="submit" class="px-4.5 py-2 bg-[#BE123C] hover:bg-[#9F1239] text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">Tolak Pendaftaran</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
