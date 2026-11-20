@extends('layouts.app')

@section('content')
<style>
    main { min-width: 0 !important; max-width: 100% !important; overflow-x: hidden; }
</style>

<div x-data="{
    createModalOpen: false,
    serviceType: 'SETUP_ONBOARDING',
    openModal(type = 'SETUP_ONBOARDING') {
        this.serviceType = type;
        this.createModalOpen = true;
    }
}" class="space-y-6 sm:space-y-7 w-full min-w-0 max-w-full pb-10">

    {{-- ── 1. GLOBAL FEATURE HEADER WITH EXECUTIVE MONITORING BADGE ──────── --}}
    <x-feature-header
        title="Layanan Implementasi, Setup & Pelatihan"
        subtitle="Dukungan implementasi ERP, pelatihan tim, dan konsultasi operasional NAYARA.">
        <x-slot:badge>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                EXECUTIVE MONITORING
            </span>
        </x-slot:badge>
        <x-slot:iconSlot>
            <div class="w-10 h-10 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px] text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
            </div>
        </x-slot:iconSlot>
        <x-slot:actions>
            <button type="button"
                    @click="openModal('SETUP_ONBOARDING')"
                    class="inline-flex items-center justify-center gap-2 h-10 px-4 sm:px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#68113F] transition shadow-xs">
                <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Ajukan Permintaan Layanan</span>
            </button>
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

    {{-- ── 2. SERVICE OVERVIEW (HORIZONTAL COMPACT EDITORIAL COMPOSITION) ──── --}}
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs divide-y md:divide-y-0 md:divide-x divide-slate-100 grid grid-cols-1 md:grid-cols-3 w-full">
        {{-- Cakupan 1: Implementasi & Setup --}}
        <div class="p-4 sm:p-5 flex items-start gap-3.5 hover:bg-slate-50/50 transition-colors">
            <div class="w-9 h-9 rounded-lg bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.058a4.5 4.5 0 005.158-5.158 4.5 4.5 0 00-5.158-5.158 4.5 4.5 0 00-5.158 5.158c.13.58.106 1.193-.058 1.743m4.05 4.05L11.42 15.17"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h4 class="text-sm font-bold text-[#172B4D]">Implementasi &amp; Setup</h4>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">Penyiapan awal sistem ERP konveksi.</p>
            </div>
        </div>

        {{-- Cakupan 2: Pelatihan Tim --}}
        <div class="p-4 sm:p-5 flex items-start gap-3.5 hover:bg-slate-50/50 transition-colors">
            <div class="w-9 h-9 rounded-lg bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h4 class="text-sm font-bold text-[#172B4D]">Pelatihan Tim</h4>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">Pelatihan staf administrasi dan produksi.</p>
            </div>
        </div>

        {{-- Cakupan 3: Konsultasi Operasional --}}
        <div class="p-4 sm:p-5 flex items-start gap-3.5 hover:bg-slate-50/50 transition-colors">
            <div class="w-9 h-9 rounded-lg bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h4 class="text-sm font-bold text-[#172B4D]">Konsultasi Operasional</h4>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">Pendampingan kebutuhan operasional toko.</p>
            </div>
        </div>
    </div>

    {{-- ── 3. POLICY NOTICE (COMPACT WARM IVORY ACCENT — SECTION 7) ────────── --}}
    <div class="p-4 sm:p-4.5 bg-[#FFFDF9] rounded-xl border border-amber-200/80 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div class="flex items-start gap-3 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
            </div>
            <div class="min-w-0 space-y-0.5">
                <div class="flex items-center gap-2 flex-wrap">
                    <h4 class="text-xs font-bold text-amber-950">Kebijakan Layanan Dalam Finalisasi</h4>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100/70 text-amber-900 border border-amber-200">
                        POLICY PENDING · SECTION 7
                    </span>
                </div>
                <p class="text-[11px] sm:text-xs text-amber-900/80 leading-relaxed">
                    Harga resmi dan checkout otomatis belum tersedia. Permintaan layanan diproses melalui konsultasi kebutuhan, estimasi individual, dan penjadwalan bersama tim Super Admin NAYARA.
                </p>
            </div>
        </div>
    </div>

    {{-- ── 4. REQUEST MANAGEMENT (AREA UTAMA DAFTAR PERMINTAAN) ─────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-card overflow-hidden w-full">
        {{-- Section Header --}}
        <div class="px-5 py-3.5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-[#172B4D] tracking-tight">Permintaan Layanan</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                        {{ $requests->total() }}
                    </span>
                </div>
                <p class="text-xs text-[#64748B] mt-0.5">Pantau pengajuan dan tindak lanjut layanan toko.</p>
            </div>
        </div>

        @if($requests->isEmpty())
            {{-- ── 5. EMPTY STATE (COMPACT & MENYATU DENGAN WORKSPACE) ───────────── --}}
            <div class="py-12 px-6 text-center">
                <div class="w-11 h-11 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center mx-auto mb-2.5 shadow-2xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                </div>
                <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Belum Ada Permintaan Layanan</h3>
                <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">Pengajuan layanan toko Anda akan ditampilkan di sini.</p>
            </div>
        @else
            {{-- ── 6. FULL-WIDTH DATA TABLE ─────────────────────────────────────── --}}
            <div class="overflow-x-auto w-full max-w-full min-w-0">
                <table class="w-full text-left border-collapse min-w-[760px]">
                    <thead>
                        <tr class="bg-[#F7E4EC] border-b border-[#EAD0DC] text-[11px] font-bold text-[#53677F] uppercase tracking-wider">
                            <th class="py-3 px-5 w-3/12">Tipe Layanan</th>
                            <th class="py-3 px-4 w-3/12">Ruang Lingkup / Kebutuhan</th>
                            <th class="py-3 px-4 w-2/12">Tanggal yang Diharapkan</th>
                            <th class="py-3 px-4 w-2/12">Estimasi Penawaran</th>
                            <th class="py-3 px-4 w-1/12">Status</th>
                            <th class="py-3 px-5 w-1/12">Catatan Super Admin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                        @foreach($requests as $req)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-5">
                                <div class="min-w-0">
                                    <p class="font-bold text-sm text-[#172B4D]">
                                        @if($req->service_type === 'SETUP_ONBOARDING')
                                            Setup Awal &amp; Migrasi Master Data
                                        @elseif($req->service_type === 'TRAINING_STAFF')
                                            Pelatihan Staf (Admin &amp; Produksi)
                                        @elseif($req->service_type === 'CUSTOM_CONSULTATION')
                                            Konsultasi Kustom Operasional MRP
                                        @else
                                            {{ $req->service_type }}
                                        @endif
                                    </p>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold text-slate-500 bg-slate-100 mt-1">
                                        ID #{{ $req->id }} &bull; {{ $req->created_at->format('d M Y') }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-[#64748B]">
                                <p class="line-clamp-2 max-w-xs leading-relaxed" title="{{ $req->scope_details }}">{{ $req->scope_details }}</p>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-[#64748B] whitespace-nowrap">
                                @if($req->preferred_date)
                                    <span class="font-semibold text-[#172B4D]">{{ $req->preferred_date->format('d M Y') }}</span>
                                @else
                                    <span class="text-slate-400 italic">Fleksibel</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs whitespace-nowrap">
                                @if($req->custom_quote_amount)
                                    <p class="font-bold text-sm text-[#8E1B5C] tabular-nums">Rp {{ number_format($req->custom_quote_amount, 0, ',', '.') }}</p>
                                    @php
                                        $inv = \App\Models\Platform\PlatformInvoice::where('service_type', \App\Models\Platform\PlatformInvoice::SERVICE_SETUP_TRAINING)
                                            ->where('metadata->service_request_id', $req->id)
                                            ->first();
                                    @endphp
                                    @if($inv)
                                        <div class="mt-1">
                                            <a href="{{ route('owner.subscription.invoice', $inv->id) }}" class="inline-flex items-center gap-1 text-[11px] text-[#8E1B5C] hover:underline font-bold">
                                                <span>{{ $inv->invoice_no }}</span>
                                                <span class="text-[10px] px-1.5 py-0.2 rounded bg-[#F7E6EE] text-[#8E1B5C] font-semibold">({{ $inv->status }})</span>
                                            </a>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic">Dalam Peninjauan</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($req->status === 'COMPLETED')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Selesai</span>
                                    </span>
                                @elseif(in_array($req->status, ['SCHEDULED', 'IN_PROGRESS']))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                                        <span>{{ $req->status === 'SCHEDULED' ? 'Terjadwal' : 'Berjalan' }}</span>
                                    </span>
                                @elseif($req->status === 'SCOPED')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C]"></span>
                                        <span>Penawaran Dibuat</span>
                                    </span>
                                @elseif(in_array($req->status, ['SUBMITTED', 'IN_REVIEW']))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>{{ $req->status === 'SUBMITTED' ? 'Diajukan' : 'Ditinjau' }}</span>
                                    </span>
                                @elseif($req->status === 'CANCELLED')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Dibatalkan</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $req->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-xs text-[#64748B] italic max-w-xs">
                                <p class="line-clamp-2" title="{{ $req->super_admin_notes ?? '-' }}">{{ $req->super_admin_notes ?: '-' }}</p>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
            <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                {{ $requests->links() }}
            </div>
            @endif
        @endif
    </div>

    {{-- ── 7. MODAL AJUKAN PERMINTAAN LAYANAN (3-TIER ARCHITECTURE) ─────────── --}}
    <div x-show="createModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/50 backdrop-blur-xs"
         @keydown.escape.window="createModalOpen = false">

        <div @click.away="createModalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl w-full max-w-[680px] max-h-[90vh] sm:max-h-[85vh] border border-[#E1CFD9] shadow-2xl flex flex-col overflow-hidden">

            {{-- Modal Header (FIXED TOP, flex-shrink-0) --}}
            <div class="px-6 py-4.5 border-b border-slate-100 flex items-center justify-between flex-shrink-0 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-[#172B4D]">Ajukan Permintaan Layanan</h3>
                        <p class="text-[11px] text-[#64748B]">Konsultasikan kebutuhan implementasi, pelatihan, atau alur konveksi toko Anda.</p>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form Wrapping Body AND Footer --}}
            <form action="{{ route('owner.services.store') }}" method="POST" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf

                {{-- Modal Form Body (THE ONLY SCROLLING AREA, flex-1 min-h-0 overflow-y-auto) --}}
                <div class="flex-1 min-h-0 overflow-y-auto p-5 sm:p-6 space-y-4">
                    {{-- Field 1: Tipe Layanan yang Dibutuhkan --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                            Tipe Layanan yang Dibutuhkan <span class="text-rose-600">*</span>
                        </label>
                        <select name="service_type"
                                x-model="serviceType"
                                required
                                class="w-full h-10 px-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
                            <option value="SETUP_ONBOARDING">Setup Awal &amp; Migrasi Master Data Konveksi</option>
                            <option value="TRAINING_STAFF">Pelatihan Intensif Staf (Admin, Gudang, Produksi)</option>
                            <option value="CUSTOM_CONSULTATION">Konsultasi Efisiensi Produksi &amp; Alur MRP Hijab</option>
                        </select>
                    </div>

                    {{-- Field 2: Rincian Ruang Lingkup & Kebutuhan --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                            Rincian Ruang Lingkup &amp; Kebutuhan Khusus <span class="text-rose-600">*</span>
                        </label>
                        <textarea name="scope_details"
                                  rows="4"
                                  required
                                  maxlength="2000"
                                  placeholder="Jelaskan jumlah staf, kendala operasional saat ini, atau kebutuhan migrasi data konveksi toko Anda..."
                                  class="w-full p-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all leading-relaxed">{{ old('scope_details') }}</textarea>
                        <p class="text-[11px] text-[#64748B]">Maksimal 2.000 karakter. Tim Super Admin akan mempelajari rincian sebelum menyusun jadwal &amp; estimasi.</p>
                    </div>

                    {{-- Field 3: Perkiraan Tanggal yang Diinginkan --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                            Perkiraan Tanggal yang Diinginkan <span class="text-slate-400 font-normal">(Opsional)</span>
                        </label>
                        <input type="date"
                               name="preferred_date"
                               min="{{ now()->addDay()->toDateString() }}"
                               value="{{ old('preferred_date') }}"
                               class="w-full h-10 px-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
                        <p class="text-[11px] text-[#64748B]">Pilih tanggal jika memiliki target waktu tertentu. Jadwal final akan disepakati bersama.</p>
                    </div>
                </div>

                {{-- Modal Footer (FIXED BOTTOM, flex-shrink-0) --}}
                <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/80 flex items-center justify-end gap-2.5 flex-shrink-0">
                    <button type="button"
                            @click="createModalOpen = false"
                            class="h-9 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-[#172B4D] text-xs font-semibold transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="h-9 px-5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold transition-colors shadow-xs">
                        Kirim Permintaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
