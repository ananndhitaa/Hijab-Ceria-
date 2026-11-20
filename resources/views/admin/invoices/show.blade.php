<x-app-layout pageTitle="Detail Invoice" breadcrumb="Keuangan > Invoice > Detail">
    <div class="max-w-5xl mx-auto space-y-4 sm:space-y-5"
         x-data="{ showPaymentModal: false, showCancelModal: false, cancelPaymentAmount: '', cancelPaymentRoute: '', maxOutstanding: {{ $outstandingBalance }} }">


        {{-- ── 1. Page Header (Concise Top Identity) ───────────────────────── --}}
        <x-feature-header
            :title="$invoice->invoice_no"
            icon="finance"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        INVOICE PENJUALAN
                    </span>
                    <span class="badge {{ $invoice->status->badgeClass() }} text-xs px-2.5 py-0.5">
                        {{ $invoice->status->label() }}
                    </span>
                </div>
            </x-slot:badge>
            <p class="text-xs text-[#64748B] flex items-center gap-1.5 font-medium mt-1">
                <span>{{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : '-' }}</span>
                <span class="text-[#FFD8D8]">&middot;</span>
                <span>Terkait Sales Order:</span>
                <a href="{{ route('admin.orders.show', $invoice->order) }}" class="font-mono font-semibold text-[#8E1B5C] hover:underline">
                    {{ $invoice->order->order_no }}
                </a>
            </p>
            <x-slot:actions>
                <a href="{{ route('admin.invoices.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Kembali ke daftar invoice">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>

                <a href="{{ route('admin.invoices.export-csv', $invoice) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Unduh data invoice format CSV">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    <span>Unduh CSV</span>
                </a>

                <a href="{{ route('admin.invoices.print', $invoice) }}"
                   target="_blank"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#8E1B5C] bg-white text-xs sm:text-sm font-semibold text-[#8E1B5C] hover:bg-[#8E1B5C] hover:text-white transition-all duration-200 cursor-pointer shadow-xs"
                   title="Cetak faktur invoice atau simpan ke format PDF">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.05-.37-2.14-.37-3.254 0-4.694 3.806-8.5 8.5-8.5s8.5 3.806 8.5 8.5c0 1.114-.13 2.204-.37 3.254M17 17v4.5a.75.75 0 01-.75.75H7.75a.75.75 0 01-.75-.75V17m10 0H7m10 0v-4.5a.75.75 0 00-.75-.75H7.75a.75.75 0 00-.75.75V17m2-8.5h.01"/>
                    </svg>
                    <span>Cetak Invoice</span>
                </a>

                @if($invoice->status !== \App\Enums\InvoiceStatus::CANCELLED && bccomp($outstandingBalance, '0.00', 2) > 0)
                    <button type="button"
                            @click="showPaymentModal = true"
                            class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Catat Pembayaran</span>
                    </button>
                @endif
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Summary Metrics (3 Compact Balanced Cards) ──────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Total Tagihan</span>
                <span class="text-xl font-extrabold text-[#172B4D] mt-1 block">
                    {{ \App\Support\MoneyFormatter::rupiah($invoice->total_amount) }}
                </span>
            </div>

            <div class="bg-white border border-emerald-200/80 rounded-xl p-4 shadow-card bg-emerald-50/20">
                <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Terbayar</span>
                <span class="text-xl font-extrabold text-emerald-700 mt-1 block">
                    {{ \App\Support\MoneyFormatter::rupiah($paidAmount) }}
                </span>
            </div>

            <div class="bg-white border {{ bccomp($outstandingBalance, '0.00', 2) > 0 ? 'border-red-200/80 bg-red-50/20' : 'border-[#FFD8D8]' }} rounded-xl p-4 shadow-card">
                <span class="text-[10px] font-bold {{ bccomp($outstandingBalance, '0.00', 2) > 0 ? 'text-red-800' : 'text-[#64748B]' }} uppercase tracking-wider block">Sisa</span>
                <span class="text-xl font-extrabold {{ bccomp($outstandingBalance, '0.00', 2) > 0 ? 'text-red-700' : 'text-[#172B4D]' }} mt-1 block">
                    {{ \App\Support\MoneyFormatter::rupiah($outstandingBalance) }}
                </span>
            </div>
        </div>

        {{-- ── 3. Customer & Document Info (Compact 2-Column Card) ────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 text-xs">
                {{-- Left: Customer --}}
                <div class="space-y-2">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block border-b border-[#FFD8D8] pb-1.5">Customer</span>
                    <div class="space-y-1.5 bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#64748B]">Nama:</span>
                            <span class="font-bold text-[#172B4D] text-right">{{ $invoice->order->customer->customer_name ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#64748B]">Telepon / WA:</span>
                            <span class="font-medium text-[#172B4D] text-right">{{ $invoice->order->customer->phone ?: '-' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-[#64748B] shrink-0">Alamat:</span>
                            <span class="font-normal text-[#172B4D] text-right leading-relaxed">{{ $invoice->order->customer->address ?: '-' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Right: Dokumen --}}
                <div class="space-y-2">
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block border-b border-[#FFD8D8] pb-1.5">Dokumen</span>
                    <div class="space-y-1.5 bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#64748B]">Invoice:</span>
                            <span class="font-mono font-bold text-[#8E1B5C] text-right">{{ $invoice->invoice_no }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#64748B]">Sales Order:</span>
                            <a href="{{ route('admin.orders.show', $invoice->order) }}" class="font-mono font-bold text-[#8E1B5C] hover:underline text-right">
                                {{ $invoice->order->order_no }}
                            </a>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[#64748B]">Status Order:</span>
                            <span class="badge {{ $invoice->order->status->badgeClass() }} text-[10px]">
                                {{ $invoice->order->status->label() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 4. Item Invoice (Transaction Table) ─────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <x-nayara-section-header
                title="Item Invoice"
                meta="{{ $invoice->order->items->count() }} Varian"
            />
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-[0.02em]">
                            <th class="px-4 py-2.5 sm:py-3 text-center w-12 align-middle">No</th>
                            <th class="px-4 py-2.5 sm:py-3 align-middle">Produk &amp; Varian</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap w-[140px] align-middle">SKU</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap w-[110px] align-middle">Qty</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap w-[150px] align-middle">Harga Satuan</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap w-[160px] align-middle">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @foreach($invoice->order->items as $idx => $item)
                            <tr class="hover:bg-[#FFFCFB]/70 transition-colors">
                                <td class="px-4 py-3 text-center text-xs text-[#64748B] font-medium">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-[#172B4D] block text-xs">
                                        {{ $item->variant->product->product_name ?? 'Produk' }}
                                    </span>
                                    <span class="text-[11px] text-[#64748B]">
                                        {{ $item->variant->variant_name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $item->variant->sku ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-xs text-[#172B4D] whitespace-nowrap">
                                    {{ number_format($item->qty_ordered, 0, ',', '.') }} pcs
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-[#64748B] whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($item->unit_selling_price) }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-xs text-[#172B4D] whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($item->subtotal) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#FFD8D8] font-bold border-t border-[#FFD8D8]">
                            <td colspan="3" class="px-4 py-2.5 text-xs uppercase tracking-wider text-[#172B4D]">
                                Total Invoice
                            </td>
                            <td class="text-right px-4 py-2.5 text-xs text-[#172B4D] whitespace-nowrap">
                                {{ number_format($invoice->order->total_qty, 0, ',', '.') }} pcs
                            </td>
                            <td></td>
                            <td class="text-right px-4 py-2.5 text-sm font-extrabold text-[#8E1B5C] whitespace-nowrap">
                                {{ \App\Support\MoneyFormatter::rupiah($invoice->total_amount) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- ── 5. Payment History Table ───────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <x-nayara-section-header title="Riwayat Pembayaran" />
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-[0.02em]">
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap align-middle">Tanggal</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap align-middle">Jenis</th>
                            <th class="px-4 py-2.5 sm:py-3 align-middle">Metode</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap align-middle">Nominal</th>
                            <th class="px-4 py-2.5 sm:py-3 text-center whitespace-nowrap align-middle">Status</th>
                            <th class="px-4 py-2.5 sm:py-3 text-center whitespace-nowrap w-24 align-middle">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @forelse($invoice->payments->sortByDesc('payment_date') as $payment)
                            <tr class="hover:bg-[#FFFCFB]/70 transition-colors">
                                <td class="px-4 py-3 text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 font-medium text-[#172B4D] whitespace-nowrap">
                                    {{ $payment->payment_type->label() }}
                                </td>
                                <td class="px-4 py-3 text-[#64748B]">
                                    {{ $payment->payment_method ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-xs whitespace-nowrap {{ $payment->status === \App\Enums\PaymentStatus::CONFIRMED ? 'text-[#172B4D]' : 'text-gray-400 line-through' }}">
                                    {{ \App\Support\MoneyFormatter::rupiah($payment->amount) }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($payment->status === \App\Enums\PaymentStatus::CONFIRMED)
                                        <span class="badge badge-success text-[10px]">Dikonfirmasi</span>
                                    @elseif($payment->status === \App\Enums\PaymentStatus::CANCELLED)
                                        <span class="badge badge-danger text-[10px]">Dibatalkan</span>
                                    @else
                                        <span class="badge badge-warning text-[10px]">{{ $payment->status->label() }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($payment->status === \App\Enums\PaymentStatus::CONFIRMED)
                                        <button type="button"
                                                @click="cancelPaymentRoute = '{{ route('admin.payments.cancel', $payment) }}'; cancelPaymentAmount = '{{ \App\Support\MoneyFormatter::rupiah($payment->amount) }}'; showCancelModal = true"
                                                class="inline-flex items-center justify-center h-7 px-2.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 border border-red-200 hover:bg-red-100 transition-colors cursor-pointer shadow-2xs">
                                            Batalkan
                                        </button>
                                    @else
                                        <span class="text-xs text-[#64748B] italic">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-[#64748B] text-xs">
                                    Belum ada pembayaran yang dicatat untuk invoice ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @php
            $hasConfirmedDp = $hasConfirmedDp ?? $invoice->hasConfirmedDp();
        @endphp
        {{-- ── 6. Record Payment Modal ────────────────────────────────────── --}}
        @if($invoice->status !== \App\Enums\InvoiceStatus::CANCELLED && bccomp($outstandingBalance, '0.00', 2) > 0)
            <div x-show="showPaymentModal"
                 class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
                 x-cloak>
                <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-lg w-full p-5 sm:p-6 space-y-4 shadow-xl animate-fade-in"
                     @click.outside="showPaymentModal = false"
                     x-data="{
                         dpAmount: '{{ $dpAmount }}',
                         outstandingBalance: '{{ $outstandingBalance }}',
                         totalAmount: '{{ $invoice->total_amount }}',
                         hasConfirmedDp: {{ $hasConfirmedDp ? 'true' : 'false' }},
                         paymentType: '{{ $hasConfirmedDp ? 'PELUNASAN' : 'DP' }}',
                         paymentMethod: 'Transfer Bank',
                         inputAmount: '{{ $hasConfirmedDp ? $outstandingBalance : $dpAmount }}',
                         get isReadonly() {
                             return this.paymentType === 'DP' || this.paymentType === 'PELUNASAN';
                         },
                         get helperText() {
                             if (this.paymentType === 'DP') return '50% dari total tagihan';
                             if (this.paymentType === 'PELUNASAN') return 'Sesuai sisa tagihan';
                             return 'Masukkan nominal pembayaran';
                         },
                         onTypeChange() {
                             if (this.paymentType === 'DP') {
                                 this.inputAmount = this.dpAmount;
                             } else if (this.paymentType === 'PELUNASAN') {
                                 this.inputAmount = this.outstandingBalance;
                             } else {
                                 this.inputAmount = '';
                             }
                         }
                     }"
                     x-init="onTypeChange()">
                    <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                        <h3 class="text-sm font-bold text-[#172B4D]">Catat Pembayaran Baru</h3>
                        <button type="button"
                                @click="showPaymentModal = false"
                                class="w-7 h-7 rounded-lg text-[#64748B] hover:text-[#172B4D] hover:bg-[#FFD8D8] inline-flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('admin.invoices.payments.store', $invoice) }}" class="space-y-4">
                        @csrf

                        <div class="p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-[#64748B]">Total Tagihan:</span>
                                <span class="font-bold text-[#172B4D]">{{ \App\Support\MoneyFormatter::rupiah($invoice->total_amount) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#64748B]">Sudah Terbayar:</span>
                                <span class="font-bold text-emerald-700">{{ \App\Support\MoneyFormatter::rupiah($paidAmount) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-[#FFD8D8] pt-1">
                                <span class="text-[#64748B] font-semibold">Sisa Tagihan Maksimal:</span>
                                <span class="font-extrabold text-red-700">{{ \App\Support\MoneyFormatter::rupiah($outstandingBalance) }}</span>
                            </div>
                        </div>

                        {{-- Payment Type --}}
                        <div>
                            <label for="payment_type" class="block text-xs font-semibold text-[#172B4D] mb-1">
                                Jenis Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <select name="payment_type" id="payment_type" x-model="paymentType" @change="onTypeChange()"
                                    class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]"
                                    required>
                                @foreach(\App\Enums\PaymentType::cases() as $pt)
                                    @if($hasConfirmedDp && $pt === \App\Enums\PaymentType::DP)
                                        @continue
                                    @endif
                                    <option value="{{ $pt->value }}">{{ $pt->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Payment Date --}}
                        <div>
                            <label for="payment_date" class="block text-xs font-semibold text-[#172B4D] mb-1">
                                Tanggal Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   name="payment_date"
                                   id="payment_date"
                                   value="{{ date('Y-m-d') }}"
                                   class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]"
                                   required>
                        </div>

                        {{-- Payment Method --}}
                        <div>
                            <label for="payment_method" class="block text-xs font-semibold text-[#172B4D] mb-1">
                                Metode Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <select name="payment_method"
                                    id="payment_method"
                                    x-model="paymentMethod"
                                    class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]"
                                    required>
                                @foreach(\App\Enums\PaymentMethod::cases() as $pm)
                                    <option value="{{ $pm->value }}">{{ $pm->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Amount --}}
                        <div>
                            <label for="amount" class="block text-xs font-semibold text-[#172B4D] mb-1">
                                Nominal Pembayaran (Rp) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-bold text-[#64748B]">Rp</span>
                                <input type="number"
                                       name="amount"
                                       id="amount"
                                       x-model="inputAmount"
                                       :readonly="isReadonly"
                                       :class="isReadonly ? 'bg-[#FFFCFB] cursor-not-allowed text-[#8E1B5C]' : 'bg-white text-[#172B4D]'"
                                       step="1"
                                       min="1"
                                       :max="outstandingBalance"
                                       placeholder="Contoh: 1000000"
                                       class="w-full h-10 pl-9 pr-3 text-xs font-mono font-semibold border border-[#FFD8D8] rounded-lg focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]"
                                       required>
                            </div>
                            <p class="text-[11px] text-[#64748B] mt-1 font-medium" x-text="helperText"></p>
                            <p x-show="Number(inputAmount) > Number(outstandingBalance)" class="text-xs text-red-600 font-medium mt-1">
                                Nominal melebihi sisa tagihan (Maks: {{ \App\Support\MoneyFormatter::rupiah($outstandingBalance) }}).
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#FFD8D8]">
                            <button type="button"
                                    @click="showPaymentModal = false"
                                    class="inline-flex items-center justify-center h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                                Batal
                            </button>
                            <button type="submit"
                                    :disabled="!inputAmount || Number(inputAmount) <= 0 || Number(inputAmount) > Number(outstandingBalance)"
                                    class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Simpan & Verifikasi Pembayaran</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ── 7. Cancel Payment Confirmation Modal ──────────────────────────── --}}
        <div x-show="showCancelModal"
             class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
             x-cloak>
            <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-5 sm:p-6 space-y-4 shadow-xl animate-fade-in"
                 @click.outside="showCancelModal = false">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-[#172B4D]">Batalkan Pembayaran?</h3>
                        <p class="text-xs text-[#64748B] leading-relaxed">
                            Pembayaran sebesar <strong class="text-[#172B4D]" x-text="cancelPaymentAmount"></strong> tidak akan dihitung sebagai nilai terbayar dan status invoice akan dihitung ulang.
                        </p>
                    </div>
                </div>

                <form :action="cancelPaymentRoute" method="POST" class="flex items-center justify-end gap-2 pt-3 border-t border-[#FFD8D8]">
                    @csrf
                    <button type="button"
                            @click="showCancelModal = false"
                            class="inline-flex items-center justify-center h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                        Batal
                    </button>
                    <button type="submit"
                            class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 transition-all duration-200 cursor-pointer shadow-xs">
                        Batalkan Pembayaran
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
