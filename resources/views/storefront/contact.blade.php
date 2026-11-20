@extends('layouts.storefront', [
    'title' => 'Hubungi Kami — NAYARA Customer Care',
    'description' => 'Layanan bantuan dan kontak resmi platform ekosistem konveksi hijab NAYARA. Hubungi kami via WhatsApp, email, atau formulir konsultasi.'
])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-3 sm:pt-4 pb-14 sm:pb-20 space-y-8 sm:space-y-10">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-[#64748B] font-medium" aria-label="Breadcrumb">
        <a href="{{ route('landing') }}" class="hover:text-[#8E1B5C] flex items-center gap-1.5 transition-colors">
            <svg class="w-3.5 h-3.5 text-[#888185]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            <span>Beranda</span>
        </a>
        <svg class="w-3.5 h-3.5 text-[#C4B7BE]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
        </svg>
        <span class="text-[#8E1B5C] font-semibold">Hubungi Kami</span>
    </nav>

    {{-- Hero --}}
    <div class="text-center max-w-3xl mx-auto space-y-3">
        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#FCEAEF] text-[#8E1B5C] border border-[#8E1B5C]/25 text-xs font-bold uppercase tracking-wider">
            <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
            </svg>
            <span>Layanan Pelanggan Resmi</span>
        </span>
        <h1 class="text-3xl sm:text-4xl font-bold text-[#1E1B1D] font-serif tracking-tight">
            Kami Siap Membantu Anda
        </h1>
        <p class="text-xs sm:text-sm text-[#665D62] leading-relaxed">
            Punya pertanyaan mengenai koleksi hijab, konsultasi pemesanan maklon brand, atau butuh bantuan pelacakan pengiriman? Hubungi tim NAYARA sekarang.
        </p>
    </div>

    {{-- Contact Info Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="p-6 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs text-center space-y-3 group hover:border-[#8E1B5C]/40 transition-all">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shadow-2xs group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-[#1E1B1D]">WhatsApp Customer Care</h3>
            <p class="text-xs text-[#665D62]">Respon cepat untuk konsultasi order, sampel kain, dan katalog produk.</p>
            <a href="https://wa.me/6281298765432?text={{ rawurlencode('Halo Customer Care NAYARA, saya membutuhkan informasi seputar produk hijab dan pemesanan konveksi.') }}"
               target="_blank" rel="noopener noreferrer"
               class="inline-block px-5 py-2 rounded-xl bg-[#18af7b] hover:bg-[#128a61] text-white text-xs font-bold transition-colors">
                Chat via WhatsApp &rarr;
            </a>
        </div>

        <div class="p-6 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs text-center space-y-3 group hover:border-[#8E1B5C]/40 transition-all">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center font-bold shadow-2xs group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-[#1E1B1D]">Email Dukungan Resmi</h3>
            <p class="text-xs text-[#665D62]">Kemitraan konveksi, verifikasi administrasi, dan penawaran brand.</p>
            <a href="mailto:support@nayara.id" class="text-xs font-bold text-[#8E1B5C] hover:underline block pt-2">
                support@nayara.id
            </a>
        </div>

        <div class="p-6 bg-white rounded-3xl border border-[#EFE5EA] shadow-xs text-center space-y-3 group hover:border-[#8E1B5C]/40 transition-all">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-[#FCEAEF] text-[#8E1B5C] flex items-center justify-center font-bold shadow-2xs group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-[#1E1B1D]">Jam Operasional</h3>
            <p class="text-xs text-[#665D62]">Senin – Sabtu: 08.00 – 17.00 WIB<br>Minggu &amp; Libur Nasional: Pelayanan terbatas</p>
            <span class="inline-block px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-[10px] font-semibold">
                Indonesia (WIB)
            </span>
        </div>
    </div>

    {{-- Interactive Inquiry Form (Submits to WhatsApp or Feedback) --}}
    <div class="max-w-2xl mx-auto bg-white rounded-3xl border border-[#E1CFD9] p-6 sm:p-10 shadow-xs space-y-6"
         x-data="{
             name: '',
             phone: '',
             category: 'Pemesanan Produk & Varian',
             message: '',
             submitted: false,
             send() {
                 if(!this.name || !this.message) return;
                 let text = `Halo NAYARA, saya ${this.name} (${this.phone || '-'}).\nKategori: ${this.category}\nPesan: ${this.message}`;
                 let url = `https://wa.me/6281298765432?text=${encodeURIComponent(text)}`;
                 window.open(url, '_blank');
                 this.submitted = true;
             }
         }">
        <div class="space-y-1 text-center sm:text-left border-b border-[#EFE5EA] pb-4">
            <h2 class="text-xl sm:text-2xl font-bold text-[#1E1B1D] font-serif">Kirimkan Pesan atau Konsultasi</h2>
            <p class="text-xs text-[#665D62]">Isi formulir di bawah ini untuk langsung terhubung dengan petugas layanan pelanggan kami.</p>
        </div>

        <form @submit.prevent="send()" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-[#1E1B1D] mb-1">Nama Lengkap *</label>
                <input type="text" x-model="name" required placeholder="Contoh: Siti Rahmawati"
                       class="w-full px-4 py-2.5 rounded-xl border border-[#E1CFD9] bg-[#FFF9FB] text-xs text-[#1E1B1D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-[#1E1B1D] mb-1">Nomor WhatsApp *</label>
                    <input type="tel" x-model="phone" required placeholder="Contoh: 081234567890"
                           class="w-full px-4 py-2.5 rounded-xl border border-[#E1CFD9] bg-[#FFF9FB] text-xs text-[#1E1B1D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#1E1B1D] mb-1">Kategori Pertanyaan</label>
                    <select x-model="category"
                            class="w-full px-4 py-2.5 rounded-xl border border-[#E1CFD9] bg-[#FFF9FB] text-xs text-[#1E1B1D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C]">
                        <option value="Pemesanan Produk & Varian">Pemesanan Produk &amp; Varian</option>
                        <option value="Konsultasi Grosir & Seragam">Konsultasi Grosir &amp; Seragam</option>
                        <option value="Bantuan Lacak Resi Pesanan">Bantuan Lacak Resi Pesanan</option>
                        <option value="Klaim Garansi Retur 7 Hari">Klaim Garansi Retur 7 Hari</option>
                        <option value="Pendaftaran Mitra Konveksi Baru">Pendaftaran Mitra Konveksi Baru</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#1E1B1D] mb-1">Pesan / Pertanyaan Anda *</label>
                <textarea x-model="message" rows="4" required placeholder="Tuliskan detail pertanyaan atau pesanan Anda..."
                          class="w-full px-4 py-2.5 rounded-xl border border-[#E1CFD9] bg-[#FFF9FB] text-xs text-[#1E1B1D] focus:outline-none focus:ring-2 focus:ring-[#8E1B5C]/30 focus:border-[#8E1B5C]"></textarea>
            </div>

            <button type="submit"
                    class="w-full py-3.5 bg-[#8E1B5C] hover:bg-[#701D49] text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span>Kirimkan Pesan via WhatsApp CS</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>

        <div x-show="submitted" x-cloak class="p-4 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            <span>Pesan Anda telah diteruskan ke WhatsApp Customer Care Resmi NAYARA. Tim kami akan segera merespons chat Anda.</span>
        </div>
    </div>
</div>
@endsection

