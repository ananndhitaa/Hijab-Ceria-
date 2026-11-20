<x-app-layout pageTitle="Edit Karyawan: {{ $employee->name }}" breadcrumb="Manajemen > Karyawan > Edit">
    <div class="max-w-3xl mx-auto space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            :title="'Edit Karyawan: ' . $employee->name"
            subtitle="Perbarui data profil, peran operasional, atau status keaktifan akun karyawan."
            icon="users"
            :badge="$employee->role->label() . ' • ' . $employee->status->label()"
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

        {{-- Warning if role change affects historical production batches --}}
        @if($hasAssignedBatches)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-900 flex items-start gap-3 shadow-2xs">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="space-y-1">
                    <span class="font-bold">Perhatian Riwayat Operasional:</span>
                    <p class="text-[11.5px] leading-relaxed">
                        Karyawan ini telah tercatat sebagai PIC pada batch produksi historis. Mengubah peran ke Admin atau Gudang tidak akan menghapus atribusi batch masa lalu, namun karyawan ini tidak akan tersedia lagi untuk penugasan batch produksi baru.
                    </p>
                </div>
            </div>
        @endif

        {{-- Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-6 sm:p-7 shadow-card">
            <form method="POST" action="{{ route('owner.employees.update', $employee) }}" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Nama Karyawan --}}
                <div>
                    <label for="name" class="block text-xs font-bold text-[#172B4D] mb-1.5">
                        Nama Karyawan <span class="text-rose-600">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $employee->name) }}"
                           required
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
                           value="{{ old('email', $employee->email) }}"
                           required
                           class="w-full h-10 px-3.5 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('email') border-rose-500 @enderror">
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
                            @foreach($roles as $roleItem)
                                <option value="{{ $roleItem->value }}" @selected(old('role', $employee->role->value) === $roleItem->value)>
                                    {{ $roleItem->label() }}
                                </option>
                            @endforeach
                        </select>
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
                                <option value="{{ $statusItem->value }}" @selected(old('status', $employee->status->value) === $statusItem->value)>
                                    {{ $statusItem->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Password Reset (Optional) --}}
                <div class="pt-4 border-t border-[#FFD8D8] space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-[#172B4D]">Ganti Password (Opsional)</h4>
                        <p class="text-[11px] text-[#64748B]">Kosongkan kedua bidang berikut jika tidak ingin mengubah kata sandi karyawan.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-medium text-[#172B4D] mb-1.5">
                                Password Baru
                            </label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   placeholder="Minimal 8 karakter"
                                   class="w-full h-10 px-3.5 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('password') border-rose-500 @enderror">
                            @error('password')
                                <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-medium text-[#172B4D] mb-1.5">
                                Konfirmasi Password Baru
                            </label>
                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   placeholder="Ulangi password baru"
                                   class="w-full h-10 px-3.5 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-4 border-t border-[#FFD8D8] flex items-center justify-between">
                    <div>
                        <span class="text-[11px] text-[#64748B]">
                            Akun dibuat: {{ \App\Support\DateTimeFormatter::formatDateTime($employee->created_at) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('owner.employees.index') }}"
                           class="inline-flex items-center justify-center h-10 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] transition">
                            Batal
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-1.5 h-10 px-6 rounded-xl bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white text-xs font-bold transition shadow-xs cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
