@extends('platform.layouts.app')

@section('content')
<div class="w-full max-w-none mx-0 space-y-6">

    <x-feature-header
        title="Detail Invoice"
        :subtitle="$invoice->invoice_no"
        icon="finance"
        badge="TAGIHAN PLATFORM"
    >
        <x-slot:actions>
            <a href="{{ route('platform.billing.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-bold rounded-xl transition backdrop-blur-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Daftar Tagihan</span>
            </a>
        </x-slot:actions>
    </x-feature-header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        {{-- Left: Invoice Details --}}
        <div class="lg:col-span-5">
            <div class="bg-white rounded-2xl border border-[#F0E4EA] shadow-2xs overflow-hidden">

                {{-- Card Header --}}
                <div class="flex items-center gap-2 px-5 pt-5 pb-3.5 border-b border-[#F0E4EA]">
                    <div class="w-7 h-7 rounded-lg bg-[#FFF0F5] text-[#8E1B5C] border border-[#F8D7E3] flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5"><use href="#i-credit-card"/></svg>
                    </div>
                    <h2 class="text-xs sm:text-[13px] font-bold text-[#172B4D]">Rincian Invoice</h2>
                </div>

                {{-- Invoice Data --}}
                <div class="divide-y divide-slate-100 text-xs px-5">
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Nomor Invoice</span>
                        <span class="font-mono font-bold text-[#8E1B5C]">{{ $invoice->invoice_no }}</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Outlet</span>
                        <span class="font-bold text-[#172B4D] truncate text-right">{{ $invoice->outlet?->name }}</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Pembuat</span>
                        <span class="text-slate-700 text-right truncate">{{ $invoice->creator?->name }}</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Layanan</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#FFF0F5] text-[#8E1B5C] border border-[#F8D7E3]">
                            {{ $invoice->service_type }}
                        </span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Durasi</span>
                        <span class="text-slate-700">{{ $invoice->package_duration_months ? $invoice->package_duration_months . ' Bulan' : '-' }}</span>
                    </div>

                    {{-- Total --}}
                    <div class="py-3.5 flex items-center justify-between gap-3">
                        <span class="text-sm font-bold text-[#172B4D]">Total Tagihan</span>
                        <span class="text-xl font-extrabold text-[#8E1B5C]">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
                    </div>

                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Dibuat</span>
                        <span class="text-slate-500">{{ $invoice->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <span class="text-slate-500 font-medium">Bukti Diunggah</span>
                        <span class="text-slate-500">{{ $invoice->payment_submitted_at ? $invoice->payment_submitted_at->format('d M Y H:i') : 'Belum' }}</span>
                    </div>

                    @if($invoice->verified_at)
                    <div class="py-2.5 flex items-center justify-between gap-3 bg-emerald-50/60 -mx-5 px-5 rounded-b-xl">
                        <span class="text-emerald-700 font-semibold text-[11px]">Diverifikasi</span>
                        <span class="text-emerald-700 font-semibold text-[11px]">{{ $invoice->verifier?->name }} &mdash; {{ $invoice->verified_at->format('d M Y H:i') }}</span>
                    </div>
                    @endif
                </div>

                {{-- Actions --}}
                @if(in_array($invoice->status, ['PAYMENT_SUBMITTED', 'UNPAID']))
                <div class="px-5 pb-5 pt-4 border-t border-[#F0E4EA] space-y-2.5">
                    <form action="{{ route('platform.billing.verify', $invoice->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer" onclick="return confirm('Verifikasi bahwa dana Rp {{ number_format($invoice->amount, 0, ',', '.') }} telah masuk ke rekening resmi?')">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>Verifikasi Pembayaran</span>
                        </button>
                    </form>

                    <div x-data="{ open: false }">
                        <button type="button" @click="open = true" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 text-xs font-bold rounded-xl transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Tolak Bukti Transfer</span>
                        </button>

                        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4" x-cloak>
                            <div @click.away="open = false" class="bg-white rounded-2xl border border-[#F8D7E3] shadow-xl max-w-sm w-full p-5 space-y-3.5 relative animate-in fade-in zoom-in-95 duration-150">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-[#172B4D]">Tolak Bukti Pembayaran</h3>
                                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $invoice->invoice_no }}</p>
                                    </div>
                                </div>

                                <form action="{{ route('platform.billing.reject', $invoice->id) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-[#172B4D] block">Alasan Penolakan <span class="text-rose-500">*</span></label>
                                        <textarea name="notes" rows="2" required placeholder="Mutasi belum masuk, nominal tidak sesuai..." class="w-full bg-[#FFF9FB]/70 border border-[#F0E4EA] rounded-xl text-xs p-2.5 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/20 focus:border-[#8E1B5C] transition-all text-slate-700"></textarea>
                                    </div>
                                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                        <button type="button" @click="open = false" class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 text-xs font-semibold rounded-lg transition cursor-pointer">Batal</button>
                                        <button type="submit" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg transition shadow-2xs cursor-pointer">Tolak Bukti</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Right: Payment Proof Viewer --}}
        <div class="lg:col-span-7">
            <div class="bg-white rounded-2xl border border-[#F0E4EA] shadow-2xs overflow-hidden">

                {{-- Card Header --}}
                <div class="flex items-center gap-2 px-5 pt-5 pb-3.5 border-b border-[#F0E4EA]">
                    <div class="w-7 h-7 rounded-lg bg-[#FFF0F5] text-[#8E1B5C] border border-[#F8D7E3] flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    </div>
                    <h2 class="text-xs sm:text-[13px] font-bold text-[#172B4D]">Bukti Transfer</h2>
                </div>

                @if($invoice->payment_proof_path)
                <div class="p-5">
                    <div class="border border-[#F0E4EA] rounded-xl p-3 bg-[#FFF9FB] flex flex-col items-center justify-center min-h-[320px]">
                        @if(str_ends_with(strtolower($invoice->payment_proof_path), '.pdf'))
                        <div class="text-center p-8 space-y-3">
                            <div class="w-14 h-14 rounded-2xl bg-[#FFF0F5] text-[#8E1B5C] border border-[#F8D7E3] flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <p class="text-xs font-semibold text-[#172B4D]">Dokumen Bukti Transfer (PDF)</p>
                            <a href="{{ asset('storage/' . $invoice->payment_proof_path) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold rounded-xl transition shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>Buka PDF</span>
                            </a>
                        </div>
                        @else
                        <img src="{{ asset('storage/' . $invoice->payment_proof_path) }}" alt="Bukti Transfer" class="max-h-[420px] w-auto object-contain rounded-lg shadow-sm">
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-400 text-center mt-3">Pastikan nama pengirim, tanggal, dan nominal cocok dengan mutasi bank.</p>
                </div>
                @else
                <div class="p-12 flex flex-col items-center justify-center text-center min-h-[320px]">
                    <div class="w-14 h-14 rounded-2xl bg-[#FFF0F5] text-[#8E1B5C]/40 border border-[#F8D7E3] flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-xs text-slate-500 font-medium">Pemilik toko belum mengunggah bukti transfer.</p>
                </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
