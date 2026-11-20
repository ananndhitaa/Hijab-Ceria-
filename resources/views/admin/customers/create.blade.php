<x-app-layout pageTitle="Tambah Customer Baru">
    <div class="max-w-4xl mx-auto space-y-5">

        {{-- Section A: Header Card --}}
        <x-feature-header
            title="Tambah Customer Baru"
            subtitle="Masukkan data profil customer toko atau reseller."
            icon="customer"
        >
            <x-slot:actions>
                <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center justify-center gap-2 h-11 sm:h-12 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali ke Daftar</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Errors Summary --}}
        <x-admin.form-errors />

        {{-- Section B: Main Customer Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-6 shadow-card">
            <form method="POST" action="{{ route('admin.customers.store') }}" class="space-y-5">
                @csrf

                {{-- Informasi Customer Header --}}
                <div class="border-b border-[#FFD8D8] pb-3">
                    <h2 class="text-sm font-bold text-[#172B4D]">Informasi Customer</h2>
                    <p class="text-xs text-[#64748B] mt-0.5">Data utama customer yang digunakan untuk transaksi dan pengiriman.</p>
                </div>

                {{-- Nama Customer --}}
                <div class="space-y-1.5">
                    <label for="customer_name" class="block text-xs font-semibold text-[#172B4D]">
                        Nama Customer <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="customer_name"
                           id="customer_name"
                           value="{{ old('customer_name') }}"
                           placeholder="Contoh: Toko Aisyah Hijab"
                           required
                           class="w-full h-11 px-3 text-xs border @error('customer_name') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                    @error('customer_name')
                        <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nomor Telepon / WhatsApp --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="phone" class="block text-xs font-semibold text-[#172B4D]">
                            Nomor Telepon / WhatsApp
                        </label>
                        <input type="text"
                               name="phone"
                               id="phone"
                               value="{{ old('phone') }}"
                               placeholder="Contoh: 081234567890"
                               class="w-full h-11 px-3 text-xs border @error('phone') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                        @error('phone')
                            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="first_contact_date" class="block text-xs font-semibold text-[#172B4D]">
                            Tanggal Masuk / Pertama Tanya
                        </label>
                        <input type="date"
                               name="first_contact_date"
                               id="first_contact_date"
                               value="{{ old('first_contact_date', date('Y-m-d')) }}"
                               class="w-full h-11 px-3 text-xs border @error('first_contact_date') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                        @error('first_contact_date')
                            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Catatan / Kebutuhan Prospek --}}
                <div class="space-y-1.5">
                    <label for="notes" class="block text-xs font-semibold text-[#172B4D]">
                        Catatan Prospek / Pertanyaan Customer (Opsional)
                    </label>
                    <input type="text"
                           name="notes"
                           id="notes"
                           value="{{ old('notes') }}"
                           placeholder="Contoh: Tanya harga Voal Navy 20 pcs via WhatsApp"
                           class="w-full h-11 px-3 text-xs border @error('notes') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                    @error('notes')
                        <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Alamat Pengiriman / Toko --}}
                <div class="space-y-1.5">
                    <label for="address" class="block text-xs font-semibold text-[#172B4D]">
                        Alamat Pengiriman / Toko
                    </label>
                    <textarea name="address"
                              id="address"
                              rows="3"
                              placeholder="Alamat lengkap lokasi pengiriman atau toko customer..."
                              class="w-full min-h-[90px] p-3 text-xs border @error('address') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all resize-y">{{ old('address') }}</textarea>
                    @error('address')
                        <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Section C: Optional Account Linking Subsection --}}
                <div class="pt-4 border-t border-[#FFD8D8] space-y-3">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-semibold text-[#172B4D]">Akun Login Customer</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#FFFCFB] border border-[#FFD8D8] text-[#64748B]">
                                Opsional
                            </span>
                        </div>
                        <p class="text-[11px] text-[#64748B]">Hubungkan hanya jika customer sudah memiliki akun login dengan role Customer.</p>
                    </div>

                    <div class="space-y-1.5">
                        <select name="user_id"
                                id="user_id"
                                class="w-full h-11 px-3 text-xs border @error('user_id') border-red-500 @else border-[#FFD8D8] @enderror rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                            <option value="">-- Belum Terhubung (Customer Biasa) --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Section D: Form Action Buttons --}}
                <div class="pt-4 border-t border-[#FFD8D8] flex items-center justify-end gap-3">
                    <a href="{{ route('admin.customers.index') }}"
                       class="inline-flex items-center justify-center h-11 px-5 rounded-lg border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 h-11 px-6 rounded-lg bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Simpan Customer</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
