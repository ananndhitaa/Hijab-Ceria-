@extends('layouts.app')

@section('content')
<div class="w-full max-w-none mx-0 space-y-6">

    <x-feature-header
        title="Buat Materi Iklan Baru"
        subtitle="Materi iklan akan ditinjau oleh tim Super Admin NAYARA dalam waktu 12 jam. Setelah disetujui, Anda dapat memilih jadwal tayang dan anggaran.">
        <x-slot:badge>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                EXECUTIVE MONITORING
            </span>
        </x-slot:badge>
        <x-slot:iconSlot>
            <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px] text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
        </x-slot:iconSlot>
        <x-slot:actions>
            <a href="{{ route('owner.ads.index') }}" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#F7E6EE] hover:text-[#8E1B5C] transition shadow-xs">
                &larr; Kembali ke Daftar Iklan
            </a>
        </x-slot:actions>
    </x-feature-header>

    <div class="bg-white rounded-2xl border border-border-c p-6 sm:p-8 shadow-card space-y-6">

        <form action="{{ route('owner.ads.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5"
              x-data="{
                  adType: '{{ old('ad_type', 'SHOP_AD') }}',
                  bannerPreview: null,
                  previewFile(event) {
                      const file = event.target.files[0];
                      if (file) {
                          this.bannerPreview = URL.createObjectURL(file);
                      }
                  }
              }">
            @csrf

            <div class="space-y-2">
                <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">Tipe Layanan Iklan <span class="text-rose-600">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="p-4 rounded-xl border cursor-pointer transition-all flex items-start gap-3"
                           :class="adType === 'SHOP_AD' ? 'border-[#8E1B5C] bg-[#FFFBFD] ring-1 ring-[#8E1B5C]/30 shadow-2xs' : 'border-slate-200 bg-white hover:border-slate-300'">
                        <input type="radio" name="ad_type" value="SHOP_AD" x-model="adType" class="sr-only">
                        <div class="w-9 h-9 rounded-xl bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009 9.35c.695 0 1.345-.236 1.868-.636.523.4 1.173.636 1.868.636s1.345-.236 1.868-.636c.523.4 1.173.636 1.868.636a3.001 3.001 0 003.75.615z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-[#172B4D]">Iklan Toko (Center Highlight)</p>
                            <p class="text-xs text-[#64748B] mt-0.5">Banner berputar 7 detik di posisi utama homepage showcase (Maksimal 5 toko).</p>
                        </div>
                    </label>

                    <label class="p-4 rounded-xl border cursor-pointer transition-all flex items-start gap-3"
                           :class="adType === 'PRODUCT_AD' ? 'border-[#8E1B5C] bg-[#FFFBFD] ring-1 ring-[#8E1B5C]/30 shadow-2xs' : 'border-slate-200 bg-white hover:border-slate-300'">
                        <input type="radio" name="ad_type" value="PRODUCT_AD" x-model="adType" class="sr-only">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-[#172B4D]">Iklan Produk (Etalase Unggulan)</p>
                            <p class="text-xs text-[#64748B] mt-0.5">Slot kartu produk sponsor di bagian atas katalog publik (Maksimal 6 produk).</p>
                        </div>
                    </label>
                </div>

                {{-- Product Select (Only for PRODUCT_AD) --}}
                <div x-show="adType === 'PRODUCT_AD'" x-cloak class="mt-4 space-y-1.5">
                    <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">Pilih Produk Publik yang Diiklankan <span class="text-rose-600">*</span></label>
                    <select name="product_id" :required="adType === 'PRODUCT_AD'" class="w-full h-11 px-3 text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
                        <option value="">-- Pilih Produk Terbit di Etalase --</option>
                        @foreach($publishedProducts as $prod)
                        <option value="{{ $prod->id }}" {{ old('product_id') == $prod->id ? 'selected' : '' }}>
                            {{ $prod->name }} (Harga: Rp {{ number_format($prod->public_price ?? 0, 0, ',', '.') }})
                        </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-[#64748B]">Hanya produk yang sudah diterbitkan ke etalase publik yang dapat diiklankan.</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">Judul Iklan / Kampanye <span class="text-rose-600">*</span></label>
                <input type="text" name="title" required maxlength="150" value="{{ old('title') }}" placeholder="Contoh: Produsen Hijab Voal & Pashmina Grosir Terpercaya" class="w-full h-11 px-3.5 text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">Tagline / Pesan Promosi</label>
                <input type="text" name="tagline" maxlength="255" value="{{ old('tagline') }}" placeholder="Contoh: Bahan Premium, Jahitan Rapi, Siap Kirim Seluruh Indonesia" class="w-full h-11 px-3.5 text-sm border border-slate-200 rounded-xl bg-white text-[#172B4D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C] transition-all">
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-[#172B4D] uppercase tracking-wider">Unggah Gambar Banner Iklan <span class="text-rose-600">*</span></label>
                <input type="file" name="banner_file" required accept="image/jpeg,image/png,image/webp" @change="previewFile" class="w-full text-xs text-[#64748B] file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border file:border-[#FFD8D8] file:text-xs file:font-semibold file:bg-[#FFF9FB] file:text-[#8E1B5C] hover:file:bg-[#F7E6EE] file:cursor-pointer cursor-pointer">
                <p class="text-xs text-[#64748B]">Format: JPG, PNG, atau WEBP. Maksimal 2MB. Resolusi ideal: 1200 &times; 500 px (Iklan Toko) atau 800 &times; 800 px (Iklan Produk).</p>

                <div x-show="bannerPreview" class="mt-2 rounded-xl overflow-hidden border border-slate-200 aspect-video max-h-56 bg-slate-50 flex items-center justify-center">
                    <img :src="bannerPreview" alt="Pratinjau Banner" class="w-full h-full object-cover">
                </div>
            </div>

            <div class="pt-5 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('owner.ads.index') }}" class="h-10 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-[#172B4D] text-xs sm:text-sm font-semibold transition-colors flex items-center">
                    Batal
                </a>
                <button type="submit" class="h-10 px-5 rounded-xl bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs sm:text-sm font-semibold transition-colors shadow-xs">
                    Simpan & Ajukan Peninjauan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
