<x-app-layout pageTitle="Tambah Karyawan Baru" breadcrumb="Manajemen > Karyawan > Tambah">
    <div class="max-w-3xl mx-auto space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Tambah Karyawan Baru"
            subtitle="Buat akun karyawan internal baru untuk operasional NAYARA."
            icon="users"
            badge="Akun Karyawan Baru"
        >
            <x-slot:actions>
                <a href="{{ route('owner.employees.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-6 sm:p-7 shadow-card">
            <form method="POST" action="{{ route('owner.employees.store') }}" class="space-y-5">
                @csrf

                {{-- Nama Karyawan --}}
                <div>
                    <label for="name" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                        Nama Karyawan <span class="text-rose-600">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           placeholder="Contoh: Siti Rahma"
                           class="w-full h-10 px-3.5 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('name') border-rose-500 @enderror">
                    @error('name')
                        <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email / Username --}}
                <div>
                    <label for="email" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                        Email / Username Login <span class="text-rose-600">*</span>
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           placeholder="contoh: siti@nayara.local"
                           class="w-full h-10 px-3.5 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('email') border-rose-500 @enderror">
                    <p class="text-[11px] text-[#64748B] mt-1">Digunakan sebagai kredensial masuk (username) ke dalam sistem.</p>
                    @error('email')
                        <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Role Dropdown --}}
                    <div>
                        <label for="role" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                            Role Karyawan <span class="text-rose-600">*</span>
                        </label>
                        <select id="role"
                                name="role"
                                required
                                class="w-full h-10 px-3.5 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('role') border-rose-500 @enderror">
                            <option value="">-- Pilih Role --</option>
                            @foreach($roles as $roleItem)
                                <option value="{{ $roleItem->value }}" @selected(old('role') === $roleItem->value)>
                                    {{ $roleItem->label() }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-[#64748B] mt-1">Hanya role internal: Admin, Gudang, atau Produksi.</p>
                        @error('role')
                            <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status Dropdown --}}
                    <div>
                        <label for="status" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                            Status Akun <span class="text-rose-600">*</span>
                        </label>
                        <select id="status"
                                name="status"
                                required
                                class="w-full h-10 px-3.5 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('status') border-rose-500 @enderror">
                            @foreach($statuses as $statusItem)
                                <option value="{{ $statusItem->value }}" @selected(old('status', \App\Enums\UserStatus::ACTIVE->value) === $statusItem->value)>
                                    {{ $statusItem->label() }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-[#64748B] mt-1">Karyawan aktif dapat login dan dipilih dalam penugasan.</p>
                        @error('status')
                            <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Password Section --}}
                <div class="pt-2 border-t border-[#FFD8D8] space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                                Password Awal <span class="text-rose-600">*</span>
                            </label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   required
                                   placeholder="Minimal 8 karakter"
                                   class="w-full h-10 px-3.5 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('password') border-rose-500 @enderror">
                            @error('password')
                                <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                                Konfirmasi Password <span class="text-rose-600">*</span>
                            </label>
                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   required
                                   placeholder="Ulangi password awal"
                                   class="w-full h-10 px-3.5 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                        </div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="pt-4 border-t border-[#FFD8D8] flex items-center justify-end gap-3">
                    <a href="{{ route('owner.employees.index') }}"
                       class="inline-flex items-center justify-center h-10 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 h-10 px-6 rounded-xl bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white text-xs font-bold transition shadow-xs cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>Simpan Karyawan</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
