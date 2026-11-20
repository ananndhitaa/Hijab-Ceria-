@extends('platform.layouts.app', [
    'title' => "Detail Keluhan #{$complaint->complaint_code}",
    'headerTitle' => "Keluhan #{$complaint->complaint_code}",
    'breadcrumb' => "Layanan Tenant > Keluhan Owner > Detail #{$complaint->complaint_code}"
])

@section('content')
<div class="space-y-6">

    {{-- Flash Feedback --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs">&check;</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 cursor-pointer">&times;</button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Top Navigation & Action Strip --}}
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 px-5 rounded-2xl border border-[#F0E4EA] shadow-2xs">
        <a href="{{ route('platform.complaints.index') }}"
           class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-[#8E1B5C] transition-colors">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Daftar Keluhan</span>
        </a>

        <div class="flex items-center gap-3">
            <span class="text-xs text-slate-400 font-medium hidden sm:inline">Status Tiket Saat Ini:</span>
            <span class="px-3.5 py-1 rounded-full text-xs font-black border {{ $complaint->statusBadgeClass() }}">
                {{ $complaint->statusLabel() }}
            </span>
        </div>
    </div>

    {{-- Main 2-Column Content Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- ── LEFT COLUMN (COL 1-8): COMPLAINT & CONVERSATION THREAD ────────── --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- 1. Main Complaint Details Card (Clean Prose, No Awkward Box) --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-6 shadow-2xs space-y-4">
                {{-- Header Bar --}}
                <div class="flex flex-wrap items-center justify-between gap-2.5 pb-3.5 border-b border-slate-100">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-[#F7E6EE] text-[#8E1B5C] font-mono border border-[#E1CFD9]">
                            #{{ $complaint->complaint_code }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $complaint->categoryLabel() }}
                        </span>
                    </div>

                    <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5"><use href="#i-clock"/></svg>
                        <span>{{ $complaint->created_at ? $complaint->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}</span>
                        @if($complaint->created_at)
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-slate-500 font-medium">{{ $complaint->created_at->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>

                {{-- Author Identity Line --}}
                <div class="flex items-center gap-3 pt-1">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#8E1B5C] to-[#520C31] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                        {{ strtoupper(substr($complaint->user?->name ?? 'O', 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-extrabold text-[#172B4D]">{{ $complaint->user?->name ?? 'Owner Konveksi' }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#F7E6EE] text-[#8E1B5C]">Owner Pelapor</span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                            <svg class="w-3.5 h-3.5 text-[#8E1B5C] shrink-0"><use href="#i-building"/></svg>
                            <span class="font-medium text-[#172B4D]">{{ $complaint->outlet?->outlet_name ?? 'Tenant Konveksi' }}</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-slate-400">{{ $complaint->user?->email ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Complaint Subject --}}
                <div class="pt-1">
                    <h1 class="text-base sm:text-lg font-black text-[#172B4D] leading-snug">
                        {{ $complaint->title }}
                    </h1>
                </div>

                {{-- Complaint Description (Clean, Readable Body without indentation error) --}}
                <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/60 text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line font-normal text-left">{{ trim($complaint->description) }}</div>

                {{-- Attachment if any --}}
                @if($complaint->attachment_path)
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-white flex items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-rose-50 text-[#8E1B5C] border border-rose-200 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-xs font-bold text-[#172B4D] block truncate">Lampiran Dokumen / Bukti Keluhan</span>
                                <span class="text-[10px] text-slate-400 block truncate">{{ basename($complaint->attachment_path) }}</span>
                            </div>
                        </div>
                        <a href="{{ asset('storage/' . $complaint->attachment_path) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-[#8E1B5C] hover:text-white text-[#172B4D] font-bold text-xs transition-colors shrink-0">
                            <span>Unduh File</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                @endif
            </div>

            {{-- 2. Conversation / Replies History Thread --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0"></span>
                        <h2 class="text-sm sm:text-base font-extrabold text-[#172B4D]">
                            Riwayat Percakapan &amp; Tanggapan
                        </h2>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-[#F7E6EE] text-[#8E1B5C]">
                            {{ $complaint->replies->count() }} Balasan
                        </span>
                    </div>
                </div>

                @if($complaint->replies->isEmpty())
                    <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-8 text-center space-y-2">
                        <div class="w-10 h-10 rounded-full bg-[#F7E6EE] text-[#8E1B5C] flex items-center justify-center mx-auto">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <p class="text-xs font-bold text-slate-700">Belum Ada Tanggapan untuk Keluhan Ini</p>
                        <p class="text-[11px] text-slate-400">Gunakan formulir di bawah ini untuk mengirimkan tanggapan resmi kepada Owner konveksi.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($complaint->replies as $reply)
                            @if($reply->is_from_super_admin)
                                {{-- Super Admin Reply Card --}}
                                <div class="p-5 rounded-2xl border border-[#F0E4EA] bg-[#FFF9FB] space-y-3 shadow-2xs">
                                    <div class="flex items-center justify-between pb-2.5 border-b border-rose-100">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#8E1B5C] to-[#520C31] text-white font-black text-xs flex items-center justify-center shadow-xs shrink-0">
                                                SA
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-black text-[#8E1B5C]">Platform Super Admin</span>
                                                    <span class="px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-[#F7E6EE] text-[#8E1B5C] border border-[#E1CFD9]">Respon Resmi</span>
                                                </div>
                                                <span class="text-[10px] text-slate-400 block mt-0.5">{{ $reply->user?->name ?? 'Tim Layanan Platform NAYARA' }}</span>
                                            </div>
                                        </div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5"><use href="#i-clock"/></svg>
                                            <span>{{ $reply->created_at ? $reply->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}</span>
                                        </div>
                                    </div>

                                    <div class="text-xs sm:text-sm text-[#172B4D] leading-relaxed whitespace-pre-line text-left font-normal">{{ trim($reply->message) }}</div>

                                    @if($reply->attachment_path)
                                        <div class="pt-1">
                                            <a href="{{ asset('storage/' . $reply->attachment_path) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-[#E1CFD9] text-[#8E1B5C] text-xs font-bold hover:bg-rose-50 transition-colors shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                <span>Lampiran ({{ basename($reply->attachment_path) }})</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @else
                                {{-- Owner Reply Card --}}
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-3 shadow-2xs">
                                    <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-slate-700 text-white font-black text-xs flex items-center justify-center shadow-xs shrink-0">
                                                OW
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-black text-[#172B4D]">{{ $reply->user?->name ?? 'Owner Konveksi' }}</span>
                                                    <span class="px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-slate-100 text-slate-700 border border-slate-200">Owner Tenant</span>
                                                </div>
                                                <span class="text-[10px] text-slate-400 block mt-0.5">{{ $complaint->outlet?->outlet_name ?? 'Tenant' }}</span>
                                            </div>
                                        </div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5"><use href="#i-clock"/></svg>
                                            <span>{{ $reply->created_at ? $reply->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Hari ini' }}</span>
                                        </div>
                                    </div>

                                    <div class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line text-left font-normal">{{ trim($reply->message) }}</div>

                                    @if($reply->attachment_path)
                                        <div class="pt-1">
                                            <a href="{{ asset('storage/' . $reply->attachment_path) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-100 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                <span>Lampiran ({{ basename($reply->attachment_path) }})</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 3. Reply Editor Form Card (Polished & Modern) --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-6 shadow-2xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0"></span>
                    <h3 class="text-sm sm:text-base font-extrabold text-[#172B4D]">
                        Kirim Balasan Resmi Super Admin
                    </h3>
                </div>

                <form action="{{ route('platform.complaints.reply', $complaint) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label for="replyMessage" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                            Isi Tanggapan / Solusi <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="replyMessage"
                                  name="message"
                                  rows="4"
                                  required
                                  placeholder="Tuliskan balasan, solusi kendala, arahan, atau tindak lanjut resmi untuk Owner konveksi..."
                                  class="w-full p-3.5 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] outline-hidden text-[#172B4D] leading-relaxed transition-all"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                        {{-- Change Status Dropdown --}}
                        <div>
                            <label for="replyStatus" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                                Perbarui Status Tiket Setelah Balasan
                            </label>
                            <select id="replyStatus"
                                    name="status"
                                    class="w-full h-10 px-3 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] outline-hidden text-[#172B4D]">
                                <option value="WAITING_OWNER" {{ $complaint->status === 'WAITING_OWNER' ? 'selected' : '' }}>Menunggu Balasan Owner</option>
                                <option value="IN_PROGRESS" {{ $complaint->status === 'IN_PROGRESS' ? 'selected' : '' }}>Sedang Diproses</option>
                                <option value="RESOLVED" {{ $complaint->status === 'RESOLVED' ? 'selected' : '' }}>Tandai Selesai (Resolved)</option>
                                <option value="CLOSED" {{ $complaint->status === 'CLOSED' ? 'selected' : '' }}>Tutup Tiket (Closed)</option>
                            </select>
                        </div>

                        {{-- Styled File Attachment Input --}}
                        <div>
                            <label class="block text-xs font-bold text-[#172B4D] mb-1.5">
                                Unggah Dokumen / Panduan (Opsional)
                            </label>
                            <label class="flex items-center justify-between h-10 px-3 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-[#8E1B5C] cursor-pointer transition-all">
                                <span id="fileNameLabel" class="text-xs text-slate-500 truncate">Pilih file lampiran (JPG, PNG, PDF)...</span>
                                <span class="px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-[#F7E6EE] text-[#8E1B5C] shrink-0 ml-2">Pilih File</span>
                                <input type="file"
                                       id="replyAttachment"
                                       name="attachment"
                                       accept=".jpg,.jpeg,.png,.pdf"
                                       class="sr-only"
                                       onchange="document.getElementById('fileNameLabel').textContent = this.files[0] ? this.files[0].name : 'Pilih file lampiran (JPG, PNG, PDF)...'">
                            </label>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold transition-all shadow-xs hover:shadow-md cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <span>Kirim Balasan Resmi</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>

        {{-- ── RIGHT COLUMN (COL 9-12): TENANT & TICKET GOVERNANCE METADATA ──── --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- 1. Status Updater Quick Panel --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs space-y-3">
                <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                    <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0"></span>
                    <h3 class="text-xs font-extrabold text-[#172B4D] uppercase tracking-wider">
                        Ubah Status Tiket
                    </h3>
                </div>

                <form action="{{ route('platform.complaints.update-status', $complaint) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label for="directStatus" class="block text-[11px] font-bold text-slate-600 mb-1">
                            Pilih Status Baru
                        </label>
                        <select id="directStatus"
                                name="status"
                                class="w-full h-9 px-3 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] outline-hidden text-[#172B4D]">
                            <option value="NEW" {{ $complaint->status === 'NEW' ? 'selected' : '' }}>Baru Masuk</option>
                            <option value="IN_PROGRESS" {{ $complaint->status === 'IN_PROGRESS' ? 'selected' : '' }}>Sedang Diproses</option>
                            <option value="WAITING_OWNER" {{ $complaint->status === 'WAITING_OWNER' ? 'selected' : '' }}>Menunggu Balasan Owner</option>
                            <option value="RESOLVED" {{ $complaint->status === 'RESOLVED' ? 'selected' : '' }}>Selesai</option>
                            <option value="CLOSED" {{ $complaint->status === 'CLOSED' ? 'selected' : '' }}>Ditutup</option>
                        </select>
                    </div>

                    <div>
                        <label for="statusNotes" class="block text-[11px] font-bold text-slate-600 mb-1">
                            Catatan Perubahan (Opsional)
                        </label>
                        <input type="text"
                                id="statusNotes"
                                name="notes"
                                placeholder="Contoh: Menunggu investigasi teknis..."
                                class="w-full h-9 px-3 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] outline-hidden text-[#172B4D]">
                    </div>

                    <button type="submit"
                            class="w-full h-9 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold transition-all shadow-xs cursor-pointer">
                        Simpan Perubahan Status
                    </button>
                </form>
            </div>

            {{-- 2. Tenant Identity Card (Sesuai Aturan C.5 & F.8) --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs space-y-4">
                <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                    <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0"></span>
                    <h3 class="text-xs font-extrabold text-[#172B4D] uppercase tracking-wider">
                        Informasi Tenant &amp; Pemilik
                    </h3>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider block">Perusahaan Konveksi</span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <svg class="w-3.5 h-3.5 text-[#8E1B5C] shrink-0"><use href="#i-building"/></svg>
                            <span class="font-extrabold text-[#172B4D] text-sm">
                                {{ $complaint->outlet?->outlet_name ?? 'Tenant NAYARA' }}
                            </span>
                        </div>
                        <span class="text-[11px] text-slate-500 block mt-0.5">
                            Kode Outlet: #{{ $complaint->outlet_id }}
                        </span>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider block">Akun Owner Pelapor</span>
                        <span class="font-bold text-[#172B4D] block mt-0.5">
                            {{ $complaint->user?->name ?? 'Pemilik Toko' }}
                        </span>
                        <span class="text-[11px] text-slate-500 block">
                            {{ $complaint->user?->email ?? '-' }}
                        </span>
                        @if($complaint->user?->phone_wa)
                            <span class="text-[11px] text-slate-500 block">
                                WhatsApp: {{ $complaint->user->phone_wa }}
                            </span>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider block">Status Tenant Platform</span>
                        <span class="inline-flex items-center gap-1.5 mt-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Tenant Aktif
                        </span>
                    </div>
                </div>

                {{-- Privacy Boundary Alert (Aturan F.8) --}}
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] text-slate-600 leading-snug">
                    <strong class="text-slate-700">Batasan Privasi Multi-Tenant:</strong>
                    Super Admin hanya menangani pengaduan operasional &amp; akun. Akses ke data transaksi internal, BOM, stok, dan gaji karyawan tenant tetap diisolasi.
                </div>
            </div>

            {{-- 3. Ticket Timeline Metadata --}}
            <div class="bg-white rounded-2xl border border-[#F0E4EA] p-5 shadow-2xs space-y-3">
                <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                    <span class="w-1.5 h-4 bg-[#8E1B5C] rounded-full inline-block shrink-0"></span>
                    <h3 class="text-xs font-extrabold text-[#172B4D] uppercase tracking-wider">
                        Kronologi Tiket
                    </h3>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400 text-[11px]">Waktu Pengajuan:</span>
                        <span class="font-bold text-[#172B4D] text-[11px]">
                            {{ $complaint->created_at ? $complaint->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') : '-' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400 text-[11px]">Balasan Terakhir:</span>
                        <span class="font-bold text-[#172B4D] text-[11px]">
                            {{ $complaint->last_reply_at ? $complaint->last_reply_at->timezone('Asia/Jakarta')->format('d M Y, H:i') : 'Belum Ada' }}
                        </span>
                    </div>

                    @if($complaint->resolved_at)
                        <div class="flex items-center justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-400 text-[11px]">Waktu Selesai:</span>
                            <span class="font-bold text-emerald-700 text-[11px]">
                                {{ $complaint->resolved_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}
                            </span>
                        </div>
                    @endif

                    @if($complaint->closed_at)
                        <div class="flex items-center justify-between py-1">
                            <span class="text-slate-400 text-[11px]">Waktu Ditutup:</span>
                            <span class="font-bold text-slate-700 text-[11px]">
                                {{ $complaint->closed_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
