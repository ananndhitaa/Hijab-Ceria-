@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-12">

    {{-- Top Bar & Back --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('owner.complaints.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-[#8E1B5C] transition">
            &larr; Kembali ke Daftar Keluhan
        </a>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500">Status:</span>
            <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $complaint->statusBadgeClass() }}">
                {{ $complaint->statusLabel() }}
            </span>
        </div>
    </div>

    {{-- Flash Alerts --}}
    @if(session('success'))
        <div class="w-full bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 flex items-center gap-3 text-emerald-800 text-xs sm:text-sm font-medium shadow-xs">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="flex-1">{{ session('success') }}</span>
        </div>
    @endif

    {{-- 1. Main Complaint Info Box --}}
    <div class="bg-white rounded-2xl border border-slate-200/90 p-6 sm:p-7 shadow-xs space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 pb-3.5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-lg text-xs font-black bg-[#F7E6EE] text-[#8E1B5C] font-mono border border-[#E1CFD9]">
                    #{{ $complaint->complaint_code }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                    {{ $complaint->categoryLabel() }}
                </span>
            </div>
            <span class="text-xs text-slate-400">
                Diajukan pada {{ $complaint->created_at ? $complaint->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}
            </span>
        </div>

        <div>
            <h1 class="text-lg sm:text-xl font-extrabold text-[#172B4D] leading-snug">
                {{ $complaint->title }}
            </h1>
        </div>

        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/70 text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">
            {{ $complaint->description }}
        </div>

        @if($complaint->attachment_path)
            <div class="p-3.5 rounded-xl border border-slate-200 bg-white flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </div>
                    <div class="min-w-0">
                        <span class="text-xs font-bold text-[#172B4D] block truncate">Lampiran Dokumen Bukti</span>
                        <span class="text-[10px] text-slate-400 block truncate">{{ basename($complaint->attachment_path) }}</span>
                    </div>
                </div>
                <a href="{{ asset('storage/' . $complaint->attachment_path) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-[#172B4D] font-bold text-xs transition shrink-0">
                    <span>Lihat / Unduh File</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
        @endif
    </div>

    {{-- 2. Conversation / Replies History --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm sm:text-base font-extrabold text-[#172B4D] flex items-center gap-2">
                <span>Percakapan dengan Super Admin</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-[#F7E6EE] text-[#8E1B5C]">
                    {{ $complaint->replies->count() }} Tanggapan
                </span>
            </h2>
        </div>

        @if($complaint->replies->isEmpty())
            <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-8 text-center space-y-2">
                <div class="w-10 h-10 rounded-full bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center mx-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-xs font-bold text-slate-700">Menunggu Balasan dari Tim Super Admin</p>
                <p class="text-[11px] text-slate-400">Tim platform NAYARA sedang meninjau pengaduan Anda dan akan segera memberikan tanggapan.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($complaint->replies as $reply)
                    @if($reply->is_from_super_admin)
                        {{-- Super Admin Reply --}}
                        <div class="p-5 rounded-2xl border border-rose-200/80 bg-[#FFF9FB] space-y-2.5 shadow-xs">
                            <div class="flex items-center justify-between pb-2 border-b border-rose-100">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-[#8E1B5C] text-white font-bold text-[10px] flex items-center justify-center">
                                        SA
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold text-[#8E1B5C]">Super Admin Platform NAYARA</span>
                                        <span class="text-[10px] text-slate-400 block">{{ $reply->user?->name ?? 'Tim Support' }}</span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-slate-400">
                                    {{ $reply->created_at ? $reply->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}
                                </span>
                            </div>

                            <div class="text-xs sm:text-sm text-[#172B4D] leading-relaxed whitespace-pre-wrap">
                                {{ $reply->message }}
                            </div>

                            @if($reply->attachment_path)
                                <div class="pt-2">
                                    <a href="{{ asset('storage/' . $reply->attachment_path) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-[#E1CFD9] text-[#8E1B5C] text-xs font-bold hover:bg-rose-50 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span>Unduh Lampiran Resmi</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        {{-- Owner Reply --}}
                        <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-2.5 shadow-xs">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-slate-700 text-white font-bold text-[10px] flex items-center justify-center">
                                        OW
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold text-[#172B4D]">{{ $reply->user?->name ?? 'Owner Toko' }} (Anda)</span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-slate-400">
                                    {{ $reply->created_at ? $reply->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}
                                </span>
                            </div>

                            <div class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">
                                {{ $reply->message }}
                            </div>

                            @if($reply->attachment_path)
                                <div class="pt-2">
                                    <a href="{{ asset('storage/' . $reply->attachment_path) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-100 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span>Unduh Lampiran Anda</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- 3. Owner Reply Form (Hanya jika belum ditutup) --}}
    @if($complaint->status !== 'CLOSED')
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-extrabold text-[#172B4D] flex items-center gap-2">
                <span>Kirim Tanggapan atau Informasi Tambahan</span>
            </h3>

            <form action="{{ route('owner.complaints.reply', $complaint) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <textarea name="message"
                              rows="4"
                              required
                              placeholder="Tuliskan balasan, klarifikasi kendala, atau tanggapan Anda untuk tim Super Admin..."
                              class="w-full p-3.5 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/20 outline-hidden text-[#172B4D] leading-relaxed transition"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">
                        Lampiran File Tambahan (Opsional)
                    </label>
                    <input type="file"
                           name="attachment"
                           accept=".jpg,.jpeg,.png,.pdf"
                           class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#F7E6EE] file:text-[#8E1B5C] hover:file:bg-[#FFD8D8] cursor-pointer">
                    <span class="text-[10px] text-slate-400 mt-1 block">Format: JPG, PNG, atau PDF (Maks. 5MB)</span>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold transition shadow-xs hover:shadow-md cursor-pointer">
                        Kirim Balasan
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="p-4 rounded-xl bg-slate-100 border border-slate-200 text-center text-xs text-slate-500">
            Tiket pengaduan ini telah resmi ditutup. Jika Anda memiliki pertanyaan baru, silakan ajukan pengaduan baru melalui menu Bantuan.
        </div>
    @endif

</div>
@endsection

