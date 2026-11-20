<x-app-layout pageTitle="Edit Pengeluaran Bisnis" breadcrumb="Keuangan > Biaya & Pengeluaran > Edit">
    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-5"
         x-data="{ expenseType: '{{ old('expense_type', $expense->expense_type->value) }}' }">

        {{-- ── Header & Navigation ────────────────────────────────────────── --}}
        <x-feature-header
            title="Edit Pengeluaran Bisnis"
            subtitle="Perbarui rincian pengeluaran operasional atau pemasaran bisnis."
            icon="finance"
            badge="PENGELUARAN BISNIS"
        >
            <x-slot:actions>
                <a href="{{ route('admin.expenses.show', $expense) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Lihat detail pengeluaran">
                    <svg class="w-3.5 h-3.5 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Detail</span>
                </a>
                <a href="{{ route('admin.expenses.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Kembali ke daftar pengeluaran">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── Validation Errors Alert ────────────────────────────────────── --}}
        <x-admin.form-errors />

        {{-- ── Form Card ─────────────────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-7 shadow-card">
            @if(!empty($isIsolationEnabled))
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-900 space-y-1.5 mb-6">
                    <div class="flex items-center gap-2 font-bold text-amber-950">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Perlindungan Audit Trail & Batas Cut-Off Keuangan</span>
                    </div>
                    <p class="leading-relaxed">
                        Setiap koreksi data pengeluaran dicatat permanen dalam riwayat audit (audit trail) bersama identitas admin dan waktu perubahan.
                    </p>
                    <p class="leading-relaxed text-amber-800">
                        <strong>Batas Cut-off Kas:</strong> Tanggal pengeluaran tidak boleh berpindah melintasi batas cut-off saldo kas awal (sebelum cut-off ke setelah cut-off, atau sebaliknya).
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.expenses.update', $expense) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

                    {{-- Expense Date --}}
                    <div>
                        <label for="expense_date" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Tanggal Pengeluaran <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="expense_date"
                               name="expense_date"
                               value="{{ old('expense_date', $expense->expense_date ? $expense->expense_date->format('Y-m-d') : '') }}"
                               class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('expense_date') border-red-500 @enderror"
                               required>
                        @error('expense_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Expense Type --}}
                    <div>
                        <label for="expense_type" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Jenis Pengeluaran <span class="text-red-500">*</span>
                        </label>
                        <select id="expense_type"
                                name="expense_type"
                                x-model="expenseType"
                                class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('expense_type') border-red-500 @enderror"
                                required>
                            @foreach($expenseTypes as $type)
                                <option value="{{ $type->value }}" {{ old('expense_type', $expense->expense_type->value) === $type->value ? 'selected' : '' }}>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('expense_type')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Related Order (Full width) --}}
                    <div class="md:col-span-2">
                        <label for="order_id" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Pesanan Terkait <span class="text-[#64748B] text-[11px] font-normal">(Opsional)</span>
                        </label>
                        <select id="order_id"
                                name="order_id"
                                class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('order_id') border-red-500 @enderror">
                            <option value="">-- Pengeluaran Umum (Tidak terkait Order tertentu) --</option>
                            @foreach($orders as $order)
                                <option value="{{ $order->id }}" {{ (string) old('order_id', (string) $expense->order_id) === (string) $order->id ? 'selected' : '' }}>
                                    {{ $order->order_no }} — {{ $order->customer->customer_name ?? 'Customer' }} ({{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-[#64748B] mt-1">Pilih jika pengeluaran ini secara khusus diatribusikan ke satu Sales Order tertentu.</p>
                        @error('order_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Campaign Name (Marketing Only, Full width) --}}
                    <div x-show="expenseType === 'MARKETING'" x-transition class="md:col-span-2 space-y-1">
                        <label for="campaign_name" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Nama Kampanye / Channel Iklan <span class="text-[#64748B] text-[11px] font-normal">(Opsional untuk Marketing)</span>
                        </label>
                        <input type="text"
                               id="campaign_name"
                               name="campaign_name"
                               value="{{ old('campaign_name', $expense->campaign_name) }}"
                               placeholder="Contoh: Meta Ads Agustus, TikTok Live Event, Promo 8.8"
                               class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('campaign_name') border-red-500 @enderror">
                        <p class="text-[11px] text-[#64748B]">Digunakan untuk melacak efektivitas pemasaran.</p>
                        @error('campaign_name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Amount (Full width) --}}
                    <div class="md:col-span-2">
                        <label for="amount" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Nominal Biaya (Rp) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs font-bold text-[#64748B]">Rp</span>
                            <input type="number"
                                   id="amount"
                                   name="amount"
                                   step="0.01"
                                   min="0.01"
                                   value="{{ old('amount', $expense->amount) }}"
                                   placeholder="Contoh: 1500000"
                                   class="w-full h-10 pl-9 pr-3 text-xs font-mono font-semibold border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('amount') border-red-500 @enderror"
                                   required>
                        </div>
                        @error('amount')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description (Full width textarea) --}}
                    <div class="md:col-span-2">
                        <label for="description" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Deskripsi / Keterangan Pengeluaran <span class="text-red-500">*</span>
                        </label>
                        <textarea id="description"
                                  name="description"
                                  rows="4"
                                  placeholder="Tujuan pengeluaran, nomor bukti, atau keterangan tambahan"
                                  class="w-full min-h-[110px] p-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('description') border-red-500 @enderror"
                                  required>{{ old('description', $expense->description) }}</textarea>
                        @error('description')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @if(!empty($isIsolationEnabled))
                        {{-- Amendment Reason (Audit Trail, Full width textarea) --}}
                        <div class="md:col-span-2">
                            <label for="amendment_reason" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                                Alasan Koreksi / Perubahan Pengeluaran <span class="text-red-500">*</span>
                            </label>
                            <textarea id="amendment_reason"
                                      name="amendment_reason"
                                      rows="3"
                                      minlength="10"
                                      maxlength="500"
                                      placeholder="Contoh: Koreksi penyesuaian nominal faktur operasional berdasarkan kwitansi fisik kas kecil."
                                      class="w-full min-h-[85px] p-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('amendment_reason') border-red-500 @enderror"
                                      required>{{ old('amendment_reason') }}</textarea>
                            <div class="flex items-center justify-between mt-1 text-[11px] text-[#64748B]">
                                <span>Wajib diisi minimal 10 karakter untuk pertanggungjawaban audit koreksi finansial.</span>
                                <span>10 - 500 karakter</span>
                            </div>
                            @error('amendment_reason')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                </div>

                {{-- Action Footer --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#FFD8D8]">
                    <a href="{{ route('admin.expenses.show', $expense) }}"
                       class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Perbarui Pengeluaran</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
