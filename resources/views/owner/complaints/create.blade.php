@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <x-feature-header
        title="Ajukan Pengaduan / Keluhan Baru"
        subtitle="Sampaikan pertanyaan, kendala teknis, akun, pembayaran, atau fitur platform kepada Super Admin NAYARA.">
        <x-slot:badge>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                EXECUTIVE MONITORING
            </span>
        </x-slot:badge>
        <x-slot:iconSlot>
            <div class="w-10 h-10 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px] text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </div>
        </x-slot:iconSlot>
        <x-slot:actions>
            <a href="{{ route('owner.complaints.index') }}" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-slate-200 bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-slate-50 transition shadow-xs">
                &larr; Kembali ke Daftar Keluhan
            </a>
        </x-slot:actions>
    </x-feature-header>

    @if(isset($errors) && $errors->any())
        <div class="w-full bg-rose-50 border border-rose-200 rounded-xl p-3.5 flex items-center gap-3 text-rose-800 text-xs sm:text-sm font-medium shadow-xs">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span class="flex-1">{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200/90 p-6 sm:p-8 shadow-card space-y-6">
        <form action="{{ route('owner.complaints.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Kategori Keluhan --}}
            <div class="space-y-1.5">
                <label for="complaintCategory" class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                    Kategori Pengaduan <span class="text-rose-600">*</span>
                </label>
                <select id="complaintCategory"
                        name="category"
                        required
                        class="w-full h-11 px-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all cursor-pointer">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Judul Keluhan --}}
            <div class="space-y-1.5">
                <label for="complaintTitle" class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                    Judul Pengaduan <span class="text-rose-600">*</span>
                </label>
                <input type="text"
                       id="complaintTitle"
                       name="title"
                       value="{{ old('title') }}"
                       required
                       placeholder="Contoh: Kendala sinkronisasi stok kain katun setelah update..."
                       class="w-full h-11 px-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
            </div>

            {{-- Deskripsi Detail --}}
            <div class="space-y-1.5">
                <label for="complaintDescription" class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                    Uraian Kendala / Detail Pertanyaan <span class="text-rose-600">*</span>
                </label>
                <textarea id="complaintDescription"
                          name="description"
                          rows="6"
                          required
                          placeholder="Jelaskan secara rinci kendala yang dialami, langkah yang telah dilakukan, atau informasi yang dibutuhkan oleh perusahaan konveksi Anda..."
                          class="w-full p-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all leading-relaxed">{{ old('description') }}</textarea>
            </div>

            {{-- Lampiran / Bukti --}}
            <div class="space-y-1.5">
                <label for="complaintAttachment" class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                    Lampiran Bukti (Opsional)
                </label>
                <input type="file"
                       id="complaintAttachment"
                       name="attachment"
                       accept=".jpg,.jpeg,.png,.pdf"
                       class="w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#F7E6EE] file:text-[#8E1B5C] hover:file:bg-[#FFD8D8] cursor-pointer">
                <p class="text-[11px] text-slate-400">
                    Format file didukung: JPG, PNG, atau PDF (Ukuran maksimal 5MB).
                </p>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('owner.complaints.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-xs">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-bold transition shadow-xs hover:shadow-md cursor-pointer">
                    Kirim Pengaduan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

