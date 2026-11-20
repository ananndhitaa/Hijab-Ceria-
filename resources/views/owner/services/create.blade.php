@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <x-feature-header
        title="Ajukan Permintaan Layanan &amp; Pelatihan"
        subtitle="Tim teknis &amp; operasional NAYARA akan meninjau kebutuhan toko Anda dan memberikan estimasi waktu serta penawaran ruang lingkup.">
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
            <a href="{{ route('owner.services.index') }}" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#F7E6EE] hover:text-[#8E1B5C] transition shadow-xs">
                &larr; Kembali ke Layanan
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
        <form action="{{ route('owner.services.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                    Tipe Layanan yang Dibutuhkan <span class="text-rose-600">*</span>
                </label>
                <select name="service_type" required class="w-full h-11 px-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
                    <option value="SETUP_ONBOARDING" {{ old('service_type') == 'SETUP_ONBOARDING' ? 'selected' : '' }}>Setup Awal &amp; Migrasi Master Data Konveksi</option>
                    <option value="TRAINING_STAFF" {{ old('service_type') == 'TRAINING_STAFF' ? 'selected' : '' }}>Pelatihan Intensif Staf (Admin, Gudang, Produksi)</option>
                    <option value="CUSTOM_CONSULTATION" {{ old('service_type') == 'CUSTOM_CONSULTATION' ? 'selected' : '' }}>Konsultasi Efisiensi Produksi &amp; Alur MRP Hijab</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                    Rincian Ruang Lingkup &amp; Kebutuhan Khusus <span class="text-rose-600">*</span>
                </label>
                <textarea name="scope_details" rows="5" required maxlength="2000" placeholder="Jelaskan jumlah staf, kendala operasional saat ini, atau kebutuhan migrasi data konveksi toko Anda..." class="w-full p-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all leading-relaxed">{{ old('scope_details') }}</textarea>
                <p class="text-[11px] text-[#64748B]">Maksimal 2.000 karakter. Tim Super Admin akan mempelajari rincian sebelum menyusun jadwal &amp; estimasi.</p>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                    Perkiraan Tanggal yang Diinginkan <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <input type="date" name="preferred_date" min="{{ now()->addDay()->toDateString() }}" value="{{ old('preferred_date') }}" class="w-full h-11 px-3.5 text-xs sm:text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
                <p class="text-[11px] text-[#64748B]">Pilih tanggal jika memiliki target waktu tertentu. Jadwal final akan disepakati bersama.</p>
            </div>

            <div class="pt-5 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('owner.services.index') }}" class="h-10 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-[#172B4D] text-xs font-semibold transition-colors flex items-center">
                    Batal
                </a>
                <button type="submit" class="h-10 px-5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold transition-colors shadow-xs">
                    Kirim Permintaan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
