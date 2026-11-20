<x-app-layout pageTitle="Catat Pembayaran Supplier" breadcrumb="Keuangan > Catatan Keuangan > Pembayaran PO">
    <div class="max-w-3xl mx-auto space-y-4 sm:space-y-5">

        {{-- Header Card --}}
        <x-feature-header
            title="Pembayaran Supplier"
            subtitle="Catat transaksi pengeluaran kas untuk pelunasan atau cicilan pembelian bahan baku ke supplier."
            icon="purchase"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        PEMBAYARAN PO
                    </span>
                    <span class="font-mono text-xs px-2.5 py-0.5 rounded-md bg-[#FFD8D8] text-[#8E1B5C] font-semibold border border-[#FFD8D8]">
                        PO #{{ $purchase->purchase_no }}
                    </span>
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('admin.financial-records.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- PO Overview & Tagihan --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            {{-- 1. PO Info (Merged Persetujuan + Supplier) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card flex flex-col justify-between">
                <div class="space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono font-bold text-sm text-[#8E1B5C]">{{ $purchase->purchase_no }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Disetujui
                        </span>
                    </div>
                    <p class="text-sm font-semibold text-[#172B4D] truncate" title="{{ $purchase->supplier?->supplier_name }}">
                        {{ $purchase->supplier?->supplier_name ?? '-' }}
                        <span class="text-[#64748B] font-normal text-xs">· {{ $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : '-' }}</span>
                    </p>
                </div>
                <div class="pt-2 mt-2 border-t border-[#FFD8D8]/60 text-[11px] text-[#64748B]">
                    Disetujui oleh <span class="font-medium text-[#172B4D]">{{ $purchase->approvedByUser?->name ?? 'Admin' }}</span>
                    @if($purchase->approved_at)
                        <span>· {{ $purchase->approved_at->format('d/m/Y H:i') }}</span>
                    @endif
                </div>
            </div>

            {{-- 2. Tagihan PO --}}
            @php
                $isPartial = bccomp((string) $purchase->paid_amount, '0.00', 2) > 0;
            @endphp
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card flex flex-col justify-between">
                @if(! $isPartial)
                    {{-- First Payment: Hanya Tagihan PO --}}
                    <div>
                        <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Tagihan PO</span>
                        <div class="text-xl sm:text-2xl font-bold font-mono text-[#8E1B5C] mt-1">
                            Rp {{ number_format((float) $purchase->total_amount_string, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="pt-2 mt-2 border-t border-[#FFD8D8]/60 text-[11px] text-[#64748B]">
                        Pelunasan penuh tagihan
                    </div>
                @else
                    {{-- Partial Payment: Tagihan, Dibayar, Sisa --}}
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Tagihan PO</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            Dibayar Sebagian
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 pt-2 mt-1 border-t border-[#FFD8D8]/60">
                        <div>
                            <span class="text-[10px] text-[#64748B] uppercase font-semibold block">Tagihan</span>
                            <span class="text-xs font-mono font-semibold text-[#172B4D] block truncate">
                                Rp {{ number_format((float) $purchase->total_amount_string, 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] text-emerald-700 uppercase font-semibold block">Dibayar</span>
                            <span class="text-xs font-mono font-semibold text-emerald-700 block truncate">
                                Rp {{ number_format((float) $purchase->paid_amount, 0, ',', '.') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] text-rose-600 uppercase font-semibold block">Sisa</span>
                            <span class="text-xs font-mono font-bold text-rose-600 block truncate">
                                Rp {{ number_format((float) $outstanding, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Interactive Form Wrapper with Alpine.js --}}
        <div x-data="{
                paymentDate: '{{ old('payment_date', date('Y-m-d')) }}',
                openingDate: '{{ $openingDate }}',
                outstanding: {{ (float) $outstanding }},
                currentCash: {{ (float) ($kasTersedia ?? 0) }},
                get isHistorical() {
                    return this.openingDate && this.paymentDate < this.openingDate;
                },
                get maxAllowed() {
                    if (this.isHistorical) {
                        return this.outstanding;
                    }
                    return Math.max(0, Math.min(this.outstanding, this.currentCash));
                }
             }"
             class="space-y-4">

            {{-- Historical Payment Notice (Phase 8) --}}
            <div x-show="isHistorical"
                 x-cloak
                 class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-900 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="space-y-0.5">
                    <span class="font-bold block">Pembayaran Historis (Sebelum Cut-Off Saldo Awal):</span>
                    <p class="leading-relaxed">
                        Pembayaran ini merupakan transaksi historis sebelum saldo awal ({{ $openingRecord?->opening_date?->format('d/m/Y') }}).
                        Pembayaran akan memperbarui status pembayaran PO, tetapi tidak mengubah Kas Tersedia saat ini.
                    </p>
                </div>
            </div>

            {{-- Operational Cash Guard Warning (Only when Insufficient) --}}
            <div x-show="!isHistorical && currentCash < outstanding"
                 x-cloak
                 class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 text-xs text-amber-900 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="space-y-0.5">
                    <span class="font-bold block">Kas tidak mencukupi untuk melunasi tagihan ini.</span>
                    <p class="text-[11px] text-amber-800 leading-normal">
                        Kas tersedia: Rp {{ number_format((float) $kasTersedia, 0, ',', '.') }} · Sisa tagihan: Rp {{ number_format((float) $outstanding, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            {{-- Form Card --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-7 shadow-card">
                <form method="POST" action="{{ route('admin.purchases.payments.store', $purchase) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Nominal Pembayaran --}}
                        <div>
                            <label for="amount" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                                Nominal Pembayaran (Rp) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-bold text-[#64748B]">Rp</span>
                                <input type="number"
                                       id="amount"
                                       name="amount"
                                       step="0.01"
                                       min="0.01"
                                       :max="maxAllowed"
                                       value="{{ old('amount', (float) $maxPayment) }}"
                                       class="w-full h-10 pl-9 pr-3 text-xs font-mono font-semibold border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('amount') border-red-500 @enderror"
                                       required>
                            </div>
                            <template x-if="isHistorical">
                                <p class="text-[11px] text-blue-700 mt-1">
                                    Transaksi historis, tidak dibatasi kas saat ini.
                                </p>
                            </template>
                            @error('amount')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tanggal Pembayaran --}}
                        <div>
                            <label for="payment_date" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                                Tanggal Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="payment_date"
                                   name="payment_date"
                                   x-model="paymentDate"
                                   value="{{ old('payment_date', date('Y-m-d')) }}"
                                   class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] @error('payment_date') border-red-500 @enderror"
                                   required>
                            @error('payment_date')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Metode Pembayaran --}}
                        <div class="md:col-span-2">
                            <label for="payment_method" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                                Metode Pembayaran
                            </label>
                            <select id="payment_method"
                                    name="payment_method"
                                    class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                                <option value="TRANSFER_BCA" {{ old('payment_method') === 'TRANSFER_BCA' ? 'selected' : '' }}>Transfer Bank BCA</option>
                                <option value="TRANSFER_MANDIRI" {{ old('payment_method') === 'TRANSFER_MANDIRI' ? 'selected' : '' }}>Transfer Bank Mandiri</option>
                                <option value="TRANSFER_BRI" {{ old('payment_method') === 'TRANSFER_BRI' ? 'selected' : '' }}>Transfer Bank BRI</option>
                                <option value="TUNAI" {{ old('payment_method') === 'TUNAI' ? 'selected' : '' }}>Tunai / Kas Kecil</option>
                                <option value="LAINNYA" {{ old('payment_method') === 'LAINNYA' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>

                        {{-- Bukti Transfer --}}
                        <div class="md:col-span-2">
                            <label for="transfer_proof" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                                Bukti Transfer <span class="text-red-500">*</span>
                            </label>
                            <input type="file"
                                   id="transfer_proof"
                                   name="transfer_proof"
                                   accept=".jpg,.jpeg,.png,.pdf"
                                   class="block w-full text-xs text-[#172B4D] file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#FFD8D8] file:text-[#8E1B5C] hover:file:bg-[#FFD8D8] border border-[#FFD8D8] rounded-lg cursor-pointer bg-white focus:outline-none @error('transfer_proof') border-red-500 @enderror"
                                   required>
                            <p class="text-[11px] text-[#64748B] mt-1">
                                JPG, PNG, atau PDF.
                            </p>
                            @error('transfer_proof')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Catatan --}}
                        <div class="md:col-span-2">
                            <label for="notes" class="block text-xs font-semibold text-[#172B4D] mb-1.5">
                                Catatan / Keterangan <span class="text-[#64748B] text-[11px] font-normal">(Opsional)</span>
                            </label>
                            <textarea id="notes"
                                      name="notes"
                                      rows="2"
                                      placeholder="Keterangan pembayaran supplier..."
                                      class="w-full p-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Action Footer --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#FFD8D8]">
                        <a href="{{ route('admin.financial-records.index') }}"
                           class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                            Batal
                        </a>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Simpan Pembayaran Supplier</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
