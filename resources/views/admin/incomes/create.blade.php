<x-app-layout pageTitle="Catat Pemasukan Bisnis" breadcrumb="Keuangan > Catatan Keuangan > Tambah Pemasukan">
    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-5">

        {{-- ── Header & Navigation ────────────────────────────────────────── --}}
        <x-feature-header
            title="Catat Pemasukan Kas"
            subtitle="Input arus kas masuk non-invoice seperti modal tambahan atau pendapatan non-penjualan."
            icon="finance"
            badge="PEMASUKAN BISNIS"
        >
            <x-slot:actions>
                <a href="{{ route('admin.financial-records.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Kembali">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── Non-Invoice Distinction Info ────────────────────────────────── --}}
        <div class="bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl p-4 shadow-xs text-xs text-[#64748B] flex items-start gap-3">
            <svg class="w-5 h-5 text-[#8E1B5C] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="space-y-0.5">
                <span class="font-bold text-[#172B4D] block">Ketentuan Arus Kas Masuk:</span>
                <p class="leading-relaxed">
                    Pembayaran customer dari pesanan tercatat otomatis melalui modul <strong>Invoice &amp; Tagihan</strong>. Form ini khusus untuk kas masuk non-invoice dan <strong>tidak memengaruhi</strong> nilai Total Penjualan pesanan.
                </p>
            </div>
        </div>

        {{-- ── Form Card ─────────────────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-7 shadow-card">
            <form method="POST" action="{{ route('admin.incomes.store') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

                    {{-- Income Date --}}
                    <div>
                        <label for="income_date" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Tanggal Pemasukan <span class="text-rose-600">*</span>
                        </label>
                        <input type="date"
                               id="income_date"
                               name="income_date"
                               value="{{ old('income_date', date('Y-m-d')) }}"
                               class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('income_date') border-rose-500 @enderror"
                               required>
                        @error('income_date')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label for="category" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Kategori Pemasukan <span class="text-rose-600">*</span>
                        </label>
                        <select id="category"
                                name="category"
                                class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('category') border-rose-500 @enderror cursor-pointer"
                                required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Amount --}}
                    <div class="md:col-span-2">
                        <label for="amount" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Nominal (Rp) <span class="text-rose-600">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-semibold text-[#64748B] pointer-events-none">
                                Rp
                            </span>
                            <input type="number"
                                   id="amount"
                                   name="amount"
                                   value="{{ old('amount') }}"
                                   step="any"
                                   min="1"
                                   placeholder="0"
                                   class="w-full h-10 pl-9 pr-3 text-xs font-mono font-bold border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('amount') border-rose-500 @enderror"
                                   required>
                        </div>
                        @error('amount')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                            Keterangan <span class="text-[#64748B] text-[11px] font-normal">(Opsional)</span>
                        </label>
                        <textarea id="notes"
                                  name="notes"
                                  rows="3"
                                  placeholder="Catatan sumber pemasukan kas..."
                                  class="w-full p-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('notes') border-rose-500 @enderror">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-4 border-t border-[#FFD8D8] flex items-center justify-end gap-2.5">
                    <a href="{{ route('admin.financial-records.index') }}"
                       class="h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer inline-flex items-center">
                        Batal
                    </a>
                    <button type="submit"
                            class="h-9 px-5 rounded-lg bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span>Simpan Pemasukan</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
