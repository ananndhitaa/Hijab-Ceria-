<x-app-layout pageTitle="Detail Karyawan: {{ $employee->name }}" breadcrumb="Manajemen > Karyawan > {{ $employee->name }}">
    <div class="max-w-4xl mx-auto space-y-5" x-data="{ showResetModal: {{ $errors->has('password') ? 'true' : 'false' }} }">

        {{-- Feature Header --}}
        <x-feature-header
            :title="$employee->name"
            :subtitle="'Profil akun internal dan rangkuman riwayat operasional ' . $employee->role->label() . ' NAYARA.'"
            icon="users"
            :badge="$employee->role->label() . ' • ' . $employee->status->label()"
        >
            <x-slot:actions>
                <div class="flex items-center gap-2">
                    <a href="{{ route('owner.employees.edit', $employee) }}"
                       class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-neutral-50 transition shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-[#64748B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                        <span>Edit Karyawan</span>
                    </a>
                    <button type="button"
                            @click="showResetModal = true"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl border border-amber-200 bg-amber-50 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition shadow-2xs cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                        </svg>
                        <span>Reset Password</span>
                    </button>
                    <a href="{{ route('owner.employees.index') }}"
                       class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </x-slot:actions>
        </x-feature-header>

        {{-- Profile Details Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- Main Details Card --}}
            <div class="md:col-span-2 bg-white border border-[#FFD8D8] rounded-2xl p-6 shadow-card space-y-4">
                <h3 class="text-sm font-bold text-[#172B4D] border-b border-[#FFD8D8] pb-3">Informasi Akun Karyawan</h3>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-[#64748B] block">Nama Lengkap</span>
                        <span class="font-bold text-[#172B4D] text-sm mt-0.5 block">{{ $employee->name }}</span>
                    </div>

                    <div>
                        <span class="text-[#64748B] block">Email / Username</span>
                        <span class="font-mono text-[#64748B] text-xs mt-0.5 block">{{ $employee->email }}</span>
                    </div>

                    <div>
                        <span class="text-[#64748B] block">Role Sistem</span>
                        <div class="mt-1">
                            @if($employee->isAdmin())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#8E1B5C]/10 text-[#8E1B5C] border border-[#8E1B5C]/20">
                                    Admin
                                </span>
                            @elseif($employee->isGudang())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                    Gudang
                                </span>
                            @elseif($employee->isProduksi())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    Produksi
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="text-[#64748B] block">Status Keaktifan</span>
                        <div class="mt-1">
                            @if($employee->isActive())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 text-neutral-600 border border-neutral-300">
                                    Nonaktif
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="text-[#64748B] block">Tanggal Dibuat</span>
                        <span class="font-medium text-[#172B4D] mt-0.5 block">
                            {{ \App\Support\DateTimeFormatter::formatDateTime($employee->created_at) }}
                        </span>
                    </div>

                    <div>
                        <span class="text-[#64748B] block">Terakhir Diperbarui</span>
                        <span class="font-medium text-[#172B4D] mt-0.5 block">
                            {{ \App\Support\DateTimeFormatter::formatDateTime($employee->updated_at) }}
                        </span>
                    </div>
                </div>

                {{-- Status Toggle Action --}}
                <div class="pt-4 border-t border-[#FFD8D8] flex items-center justify-between">
                    <span class="text-[11px] text-[#64748B]">
                        {{ $employee->isActive() ? 'Akun dapat masuk ke sistem dan menerima penugasan.' : 'Akun dinonaktifkan dan tidak dapat masuk ke sistem.' }}
                    </span>

                    <form method="POST" action="{{ route('owner.employees.toggle-status', $employee) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                onclick="return confirm('Apakah Anda yakin ingin {{ $employee->isActive() ? 'menonaktifkan' : 'mengaktifkan' }} akun ini?')"
                                class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer {{ $employee->isActive() ? 'text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200' }}">
                            {{ $employee->isActive() ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Operational Summary Card --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-6 shadow-card space-y-4">
                <h3 class="text-sm font-bold text-[#172B4D] border-b border-[#FFD8D8] pb-3">Ringkasan Operasional</h3>

                @if($employee->isProduksi())
                    <div class="space-y-3">
                        <div class="p-3 bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl text-xs space-y-1">
                            <span class="text-[#64748B] text-[11px]">Batch sebagai PIC</span>
                            <p class="text-xl font-bold text-[#172B4D]">{{ number_format($stats['assigned_batches_count'] ?? 0, 0, ',', '.') }}</p>
                            <span class="text-[10.5px] text-[#64748B]">Total batch ditugaskan</span>
                        </div>

                        <div class="p-3 bg-emerald-50/50 border border-emerald-200/60 rounded-xl text-xs space-y-1">
                            <span class="text-emerald-800 text-[11px]">Batch Selesai</span>
                            <p class="text-xl font-bold text-emerald-700">{{ number_format($stats['completed_batches_count'] ?? 0, 0, ',', '.') }}</p>
                            <span class="text-[10.5px] text-emerald-600">Batch status Selesai</span>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('owner.employee-performance.production-detail', ['user' => $employee->id]) }}"
                               class="w-full inline-flex items-center justify-center gap-1.5 h-9 rounded-xl bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white text-xs font-semibold transition shadow-xs cursor-pointer">
                                <span>Lihat Performa Produksi</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @elseif($employee->isAdmin())
                    <div class="space-y-3">
                        <div class="p-3 bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl text-xs space-y-1">
                            <span class="text-[#64748B] text-[11px]">Customer Ditangani</span>
                            <p class="text-xl font-bold text-[#172B4D]">{{ number_format($stats['handled_customers_count'] ?? 0, 0, ',', '.') }}</p>
                            <span class="text-[10.5px] text-[#64748B]">Prospek & Customer WhatsApp</span>
                        </div>

                        <div class="p-3 bg-emerald-50/50 border border-emerald-200/60 rounded-xl text-xs space-y-1">
                            <span class="text-emerald-800 text-[11px]">Pesanan Dibuat</span>
                            <p class="text-xl font-bold text-emerald-700">{{ number_format($stats['orders_count'] ?? 0, 0, ',', '.') }}</p>
                            <span class="text-[10.5px] text-emerald-600">Total Sales Order terbit</span>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('owner.employee-performance.admin-detail', ['admin' => $employee->id]) }}"
                               class="w-full inline-flex items-center justify-center gap-1.5 h-9 rounded-xl bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white text-xs font-semibold transition shadow-xs cursor-pointer">
                                <span>Lihat Performa Admin</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl text-xs text-[#64748B] text-center space-y-1">
                        <p class="font-medium text-[#172B4D]">Operasional Gudang</p>
                        <p class="text-[11.5px]">Bertanggung jawab atas penerimaan bahan baku, alokasi material batch, dan audit persediaan.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Modal Reset Password --}}
        <div x-show="showResetModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4"
             @click.self="showResetModal = false">
            <div class="bg-white rounded-2xl border border-[#FFD8D8] shadow-xl w-full max-w-md p-5 space-y-4"
                 @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-[#FFD8D8]">
                    <div>
                        <h3 class="text-sm font-bold text-[#172B4D]">Reset Password</h3>
                        <p class="text-xs text-[#64748B] mt-0.5">{{ $employee->name }} · {{ $employee->role->label() }}</p>
                    </div>
                    <button type="button" @click="showResetModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                </div>

                <form method="POST" action="{{ route('owner.employees.reset-password', $employee) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="modal_password" class="block text-xs font-semibold text-[#172B4D] mb-1">
                            Password Baru <span class="text-rose-600">*</span>
                        </label>
                        <input type="password"
                               id="modal_password"
                               name="password"
                               placeholder="Minimal 8 karakter"
                               required
                               class="w-full h-9 px-3 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('password') border-rose-500 @enderror">
                        @error('password')
                            <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="modal_password_confirmation" class="block text-xs font-semibold text-[#172B4D] mb-1">
                            Konfirmasi Password <span class="text-rose-600">*</span>
                        </label>
                        <input type="password"
                               id="modal_password_confirmation"
                               name="password_confirmation"
                               placeholder="Ulangi password baru"
                               required
                               class="w-full h-9 px-3 text-xs border border-[#FFD8D8] rounded-xl bg-[#FFFCFB] text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#FFD8D8]">
                        <button type="button"
                                @click="showResetModal = false"
                                class="inline-flex items-center justify-center h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-neutral-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-xl bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white text-xs font-bold transition shadow-xs cursor-pointer">
                            Simpan Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
