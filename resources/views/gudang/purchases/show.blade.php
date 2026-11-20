<x-app-layout pageTitle="Detail Purchase Order" breadcrumb="Pembelian > Purchase Order > Detail">
    <div class="max-w-5xl mx-auto space-y-5" x-data="{ showSubmitModal: false, showCancelModal: false, showReceiveModal: false }">

        {{-- ── 1. Page Header & Actions ───────────────────────────────────── --}}
        <x-feature-header
            :title="$purchase->purchase_no"
            icon="purchase"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        PURCHASE ORDER
                    </span>
                    <x-status-badge :status="$purchase->status" context="purchase" size="card" />
                    <x-status-badge :status="$purchase->approval_status" context="approval" size="card" />
                </div>
            </x-slot:badge>
            <p class="text-xs text-[#64748B] flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                <span>Supplier: <strong class="text-[#172B4D] font-semibold">{{ $purchase->supplier?->supplier_name ?? '—' }}</strong></span>
                <span class="text-[#FFD8D8]">&bull;</span>
                <span>Tanggal: <strong class="text-[#172B4D] font-semibold">{{ $purchase->purchase_date?->format('d M Y') ?? '—' }}</strong></span>
                @if($purchase->created_at)
                    <span class="text-[#FFD8D8]">&bull;</span>
                    <span class="text-[#64748B]">Dibuat {{ \App\Support\DateTimeFormatter::formatShortDateTime($purchase->created_at) }}</span>
                @endif
            </p>
            <x-slot:actions>
                <a href="{{ route('gudang.purchases.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>

                @if($canEdit)
                    <a href="{{ route('gudang.purchases.edit', $purchase) }}"
                       class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs cursor-pointer">
                        <svg class="w-4 h-4 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        <span>Edit Draft</span>
                    </a>
                @endif

                @if($canSubmitApproval)
                    <button type="button"
                            @click="showSubmitModal = true"
                            class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                        </svg>
                        <span>Ajukan ke Admin</span>
                    </button>
                @endif

                @if(auth()->user()->role === \App\Enums\UserRole::ADMIN && $purchase->approval_status === \App\Enums\PurchaseApprovalStatus::APPROVED && $purchase->status !== \App\Enums\PurchaseStatus::CANCELLED && bccomp($purchase->outstanding_amount, '0.00', 2) > 0)
                    <a href="{{ route('admin.purchases.payments.create', $purchase) }}"
                       class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Catat Pembayaran Supplier</span>
                    </a>
                @endif

                @if($purchase->status === \App\Enums\PurchaseStatus::ORDERED)
                    @if($canReceive)
                        <button type="button"
                                @click="showReceiveModal = true"
                                class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span>Terima Barang</span>
                        </button>
                    @else
                        <button type="button"
                                disabled
                                title="Pembayaran supplier harus lunas sebelum material dapat diterima."
                                class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-gray-100 text-gray-400 border border-gray-200 text-xs sm:text-sm font-semibold cursor-not-allowed">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span>Terima Barang (Terkunci)</span>
                        </button>
                    @endif
                @endif

                @if($canCancel)
                    <button type="button"
                            @click="showCancelModal = true"
                            class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-red-200 bg-red-50 text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-100 hover:text-red-700 transition cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>Batalkan PO</span>
                    </button>
                @endif
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Compact Operational Status Strip ───────────────────────────── --}}
        @if($purchase->status === \App\Enums\PurchaseStatus::DRAFT)
            @if($purchase->approval_status === \App\Enums\PurchaseApprovalStatus::NOT_SUBMITTED)
                <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200/80 text-xs text-gray-700">
                    <span class="w-2 h-2 rounded-full bg-gray-400 shrink-0"></span>
                    <span>Draft Baru (<strong class="font-semibold text-gray-800">Belum Diajukan</strong>) · Siap diajukan ke Admin</span>
                </div>
            @elseif($purchase->approval_status === \App\Enums\PurchaseApprovalStatus::PENDING_APPROVAL)
                <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200/80 text-xs text-amber-900">
                    <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                    <span><strong>Menunggu Persetujuan Admin</strong> · Data pembelian sedang ditinjau</span>
                </div>
            @elseif($purchase->approval_status === \App\Enums\PurchaseApprovalStatus::REVISION_REQUIRED)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 px-4 py-2.5 rounded-xl bg-orange-50 border border-orange-200 text-xs text-orange-900">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
                        <span><strong>Perlu Revisi:</strong> "{{ $purchase->review_note }}"</span>
                    </div>
                    <span class="text-[11px] text-orange-800">Klik Edit Draft untuk merevisi data</span>
                </div>
            @elseif($purchase->approval_status === \App\Enums\PurchaseApprovalStatus::APPROVED)
                @if($purchase->isFullyPaid())
                    <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                        <span><strong>Disetujui</strong> · Pembayaran Lunas</span>
                    </div>
                @else
                    <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-900">
                        <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                        <span><strong>Disetujui</strong> · Menunggu pembayaran Admin</span>
                    </div>
                @endif
            @elseif($purchase->approval_status === \App\Enums\PurchaseApprovalStatus::REJECTED)
                <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-900">
                    <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                    <span><strong>Ditolak:</strong> "{{ $purchase->review_note }}"</span>
                </div>
            @endif
        @elseif($purchase->status === \App\Enums\PurchaseStatus::ORDERED)
            <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <span>Pesanan Dikonfirmasi (ORDERED) · Menunggu kiriman fisik tiba di gudang</span>
            </div>
        @elseif($purchase->status === \App\Enums\PurchaseStatus::RECEIVED)
            <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <span>Diterima Penuh (RECEIVED) · Stok inventaris telah diperbarui</span>
            </div>
        @elseif($purchase->status === \App\Enums\PurchaseStatus::CANCELLED)
            <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-red-50 border border-red-200 text-xs text-red-900">
                <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                <span>Status Dibatalkan · Transaksi tidak diproses lebih lanjut</span>
            </div>
        @endif

        {{-- ── 3. Integrated Metadata Summary ─────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-[#FFD8D8]">
                {{-- Supplier --}}
                <div class="p-4 sm:p-5 flex flex-col justify-between h-full">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block mb-1">Supplier</span>
                    <div>
                        <p class="text-sm font-bold text-[#172B4D] truncate" title="{{ $purchase->supplier?->supplier_name }}">
                            {{ $purchase->supplier?->supplier_name ?? '—' }}
                        </p>
                        <p class="text-[11px] text-[#64748B] truncate mt-0.5">
                            {{ $purchase->supplier?->phone ?? $purchase->supplier?->email ?? '—' }}
                        </p>
                    </div>
                </div>

                {{-- Tanggal PO --}}
                <div class="p-4 sm:p-5 flex flex-col justify-between h-full">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block mb-1">Tanggal</span>
                    <div>
                        <p class="text-sm font-bold text-[#172B4D]">
                            {{ $purchase->purchase_date?->format('d M Y') ?? '—' }}
                        </p>
                        <p class="text-[11px] text-[#64748B] mt-0.5">
                            {{ $purchase->created_at ? \App\Support\DateTimeFormatter::formatTime($purchase->created_at) : '—' }}
                        </p>
                    </div>
                </div>

                {{-- Jumlah Material --}}
                <div class="p-4 sm:p-5 flex flex-col justify-between h-full">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block mb-1">Item</span>
                    <div>
                        <p class="text-lg font-bold font-mono text-[#172B4D]">
                            {{ $purchase->items->count() }} <span class="text-xs font-normal text-[#64748B]">material</span>
                        </p>
                        <p class="text-[11px] text-[#64748B] mt-0.5">
                            Qty: {{ number_format((float)$purchase->items->sum('qty'), 2, ',', '.') }}
                        </p>
                    </div>
                </div>

                {{-- Total Pembelian --}}
                <div class="p-4 sm:p-5 flex flex-col justify-between h-full bg-[#FFFCFB]/50">
                    <span class="text-[10px] font-bold text-[#8E1B5C] uppercase tracking-wider block mb-1">Total</span>
                    <div>
                        <p class="text-lg sm:text-xl font-bold font-mono text-[#8E1B5C] whitespace-nowrap">
                            Rp {{ number_format((float)$totalAmount, 2, ',', '.') }}
                        </p>
                        <p class="text-[11px] text-[#64748B] mt-0.5">
                            {{ $purchase->status === \App\Enums\PurchaseStatus::RECEIVED ? 'Masuk inventaris' : 'Nilai pembelian' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 3B. Status Finansial & Pembayaran Supplier ──────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="px-5 py-3 border-b border-[#FFD8D8] bg-[#FFFCFB]/80 flex items-center justify-between">
                <h2 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">
                    Status Pembayaran Supplier
                </h2>
                <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold border {{ $purchase->payment_status_badge_class }}">
                    {{ $purchase->payment_status_label }}
                </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-[#FFD8D8]">
                <div class="p-4 sm:p-5 flex flex-col justify-between h-full">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block mb-1">Total PO</span>
                    <p class="text-base sm:text-lg font-bold font-mono text-[#172B4D] whitespace-nowrap">
                        Rp {{ number_format((float) $purchase->total_amount, 2, ',', '.') }}
                    </p>
                </div>
                <div class="p-4 sm:p-5 flex flex-col justify-between h-full">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block mb-1">Terbayar</span>
                    <p class="text-base sm:text-lg font-bold font-mono text-emerald-700 whitespace-nowrap">
                        Rp {{ number_format((float) $purchase->paid_amount, 2, ',', '.') }}
                    </p>
                </div>
                <div class="p-4 sm:p-5 flex flex-col justify-between h-full {{ bccomp($purchase->outstanding_amount, '0.00', 2) > 0 ? 'bg-rose-50/20' : 'bg-emerald-50/20' }}">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block mb-1">Sisa</span>
                    <p class="text-base sm:text-lg font-bold font-mono {{ bccomp($purchase->outstanding_amount, '0.00', 2) > 0 ? 'text-rose-700' : 'text-emerald-700' }} whitespace-nowrap">
                        Rp {{ number_format((float) $purchase->outstanding_amount, 2, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ── 4. Items Table & Document Notes ─────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <x-nayara-section-header
                title="Rincian Item Material"
                meta="{{ $purchase->items->count() }} item"
            />

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[#64748B] uppercase font-semibold text-[11.5px] sm:text-[12px] tracking-[0.02em]">
                            <th class="py-2.5 sm:py-3 px-4 w-12 text-center align-middle">No</th>
                            <th class="py-2.5 sm:py-3 px-4 min-w-[200px] align-middle">Material</th>
                            <th class="py-2.5 sm:py-3 px-4 w-20 text-center align-middle">Satuan</th>
                            <th class="py-2.5 sm:py-3 px-4 w-28 text-right align-middle">Qty Pesan</th>
                            <th class="py-2.5 sm:py-3 px-4 w-28 text-right align-middle">Qty Diterima</th>
                            <th class="py-2.5 sm:py-3 px-4 w-32 text-right align-middle">Harga Satuan</th>
                            <th class="py-2.5 sm:py-3 px-4 w-36 text-right align-middle">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/60 text-[#172B4D]">
                        @foreach($purchase->items as $idx => $item)
                            @php
                                $subtotal = bcmul((string)$item->qty, (string)$item->unit_price, 2);
                            @endphp
                            <tr class="hover:bg-[#FFFCFB]/50 transition-colors">
                                <td class="py-3 px-4 text-center text-[#64748B]">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="py-3 px-4 text-[#172B4D]">
                                    <span class="font-medium">{{ $item->material?->material_name ?? '—' }}</span>
                                    @if($item->material && ! $item->material->active)
                                        <span class="ml-1.5 text-[10px] text-red-600 bg-red-50 border border-red-200/60 px-1.5 py-0.5 rounded font-normal">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center text-[#64748B]">
                                    {{ $item->material?->unit?->symbol ?? '—' }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums font-medium text-[#172B4D]">
                                    {{ number_format((float)$item->qty, 4, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums font-medium {{ (float)$item->qty_received > 0 ? 'text-emerald-700' : 'text-[#64748B]/60' }}">
                                    {{ number_format((float)$item->qty_received, 4, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums text-[#64748B]">
                                    Rp {{ number_format((float)$item->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums font-semibold text-[#172B4D]">
                                    Rp {{ number_format((float)$subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#FFFCFB]/90 border-t-2 border-[#FFD8D8]">
                            <td colspan="6" class="py-3 px-4 text-right uppercase tracking-wider text-xs font-semibold text-[#64748B]">
                                Total
                            </td>
                            <td class="py-3 px-4 text-right tabular-nums text-sm sm:text-base font-bold text-[#8E1B5C] whitespace-nowrap">
                                Rp {{ number_format((float)$totalAmount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($purchase->notes)
                <div class="px-5 py-3.5 border-t border-[#FFD8D8] bg-[#FFFCFB]/30">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block mb-1">Catatan Pengadaan:</span>
                    <p class="text-xs text-[#172B4D] whitespace-pre-line leading-relaxed">{{ $purchase->notes }}</p>
                </div>
            @endif
        </div>

        {{-- ── 4B. Riwayat Pembayaran Supplier (Purchase Payments) ────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <x-nayara-section-header title="Riwayat Pembayaran Supplier">
                <x-slot:subtitle>
                    Terbayar <strong class="font-mono text-emerald-700">Rp {{ number_format((float) $purchase->paid_amount, 0, ',', '.') }}</strong>
                    &bull; Sisa <strong class="font-mono {{ bccomp($purchase->outstanding_amount, '0.00', 2) > 0 ? 'text-rose-600' : 'text-emerald-700' }}">Rp {{ number_format((float) $purchase->outstanding_amount, 0, ',', '.') }}</strong>
                </x-slot:subtitle>
                @if(auth()->user()->role === \App\Enums\UserRole::ADMIN && $purchase->status !== \App\Enums\PurchaseStatus::CANCELLED && bccomp($purchase->outstanding_amount, '0.00', 2) > 0)
                    <a href="{{ route('admin.purchases.payments.create', $purchase) }}"
                       class="inline-flex items-center gap-1.5 h-9 sm:h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs self-start sm:self-auto shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Catat Pembayaran</span>
                    </a>
                @endif
            </x-nayara-section-header>

            @if($purchase->payments->isEmpty())
                <div class="py-8 px-4 text-center text-[#64748B] bg-[#FFFCFB]/40">
                    <p class="text-xs font-semibold text-[#172B4D]">Belum ada riwayat pembayaran supplier</p>
                    <p class="text-[11px] text-[#64748B] mt-0.5">Catatan mutasi pembayaran supplier akan muncul di sini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[#64748B] uppercase font-semibold text-[11.5px] sm:text-[12px] tracking-[0.02em]">
                                <th class="py-2.5 sm:py-3 px-4 w-12 text-center align-middle">No</th>
                                <th class="py-2.5 sm:py-3 px-4 align-middle">Tanggal Pembayaran</th>
                                <th class="py-2.5 sm:py-3 px-4 align-middle">Metode</th>
                                <th class="py-2.5 sm:py-3 px-4 align-middle">No. Referensi</th>
                                <th class="py-2.5 sm:py-3 px-4 align-middle">Keterangan</th>
                                <th class="py-2.5 sm:py-3 px-4 align-middle">Dicatat Oleh</th>
                                <th class="py-2.5 sm:py-3 px-4 text-center align-middle">Bukti Transfer</th>
                                <th class="py-2.5 sm:py-3 px-4 text-right align-middle">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]/60 text-[#172B4D]">
                            @foreach($purchase->payments->sortByDesc('payment_date') as $pIdx => $pPay)
                                @php
                                    $formattedMethod = ucwords(strtolower(str_replace('_', ' ', $pPay->payment_method ?? 'Transfer')));
                                @endphp
                                <tr class="hover:bg-[#FFFCFB]/50 transition-colors">
                                    <td class="py-3 px-4 text-center text-[#64748B]">{{ $pIdx + 1 }}</td>
                                    <td class="py-3 px-4 font-semibold text-[#172B4D]">
                                        {{ $pPay->payment_date ? $pPay->payment_date->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="py-3 px-4 text-[#64748B]">{{ $formattedMethod }}</td>
                                    <td class="py-3 px-4 font-mono font-bold text-[#8E1B5C]">{{ $pPay->reference_no ?: '—' }}</td>
                                    <td class="py-3 px-4 text-[#64748B]">{{ $pPay->notes ?: '—' }}</td>
                                    <td class="py-3 px-4 text-[#64748B]">{{ $pPay->createdByUser?->name ?: '—' }}</td>
                                    <td class="py-3 px-4 text-center">
                                        @if($pPay->transfer_proof_path && auth()->user()->role === \App\Enums\UserRole::ADMIN)
                                            <a href="{{ route('admin.purchases.payments.proof', $pPay) }}" target="_blank"
                                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-[#FFD8D8] text-[#8E1B5C] font-semibold text-[10.5px] hover:bg-[#FFD8D8] transition">
                                                <span>Lihat Bukti</span>
                                            </a>
                                        @elseif($pPay->transfer_proof_path)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Terlampir
                                            </span>
                                        @else
                                            <span class="text-[#64748B] text-[11px]">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                        Rp {{ number_format((float) $pPay->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ── 5. Action Modals ───────────────────────────────────────────── --}}

        {{-- Modal Ajukan Persetujuan ke Owner --}}
        @if($canSubmitApproval)
            <div x-show="showSubmitModal"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
                 @click.self="showSubmitModal = false">
                <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl animate-fade-in"
                     @click.stop>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[#172B4D]">Ajukan Persetujuan ke Admin?</h3>
                            <p class="text-xs font-mono text-[#64748B]">{{ $purchase->purchase_no }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-[#172B4D] leading-relaxed">
                        Pengajuan PO akan dikirim ke antrean review Admin. Selama menunggu persetujuan, rincian data pembelian akan dikunci dari perubahan.
                    </p>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                        <button type="button"
                                @click="showSubmitModal = false"
                                class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition cursor-pointer">
                            Batal
                        </button>
                        <form method="POST" action="{{ route('gudang.purchases.submit-approval', $purchase) }}">
                            @csrf
                            <button type="submit"
                                    class="h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                                Ya, Ajukan Persetujuan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal Terima Barang (ORDERED -> RECEIVED) --}}
        @if($canReceive)
            <div x-show="showReceiveModal"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
                 @click.self="showReceiveModal = false">
                <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl animate-fade-in"
                     @click.stop>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[#172B4D]">Konfirmasi Penerimaan Barang?</h3>
                            <p class="text-xs font-mono text-[#64748B]">{{ $purchase->purchase_no }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-[#172B4D] leading-relaxed">
                        Seluruh item pada Purchase Order ini akan dicatat diterima penuh. Stok fisik material akan bertambah dan harga rata-rata persediaan (moving average) akan diperbarui secara otomatis.
                    </p>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                        <button type="button"
                                @click="showReceiveModal = false"
                                class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition cursor-pointer">
                            Batal
                        </button>
                        <form method="POST" action="{{ route('gudang.purchases.receive', $purchase) }}">
                            @csrf
                            <button type="submit"
                                    class="h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                                Terima Barang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal Pembatalan PO --}}
        @if($canCancel)
            <div x-show="showCancelModal"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
                 @click.self="showCancelModal = false">
                <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl animate-fade-in"
                     @click.stop>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-100 text-red-700 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-red-900">Batalkan Purchase Order?</h3>
                            <p class="text-xs font-mono text-[#64748B]">{{ $purchase->purchase_no }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-[#172B4D] leading-relaxed">
                        Apakah Anda yakin ingin membatalkan Purchase Order ini? PO yang dibatalkan akan berstatus <strong class="text-red-700">CANCELLED</strong> dan tidak dapat diproses lagi.
                    </p>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                        <button type="button"
                                @click="showCancelModal = false"
                                class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition cursor-pointer">
                            Kembali
                        </button>
                        <form method="POST" action="{{ route('gudang.purchases.cancel', $purchase) }}">
                            @csrf
                            <button type="submit"
                                    class="h-9 px-4 rounded-xl bg-red-600 text-white text-xs font-semibold hover:bg-red-700 transition shadow-xs cursor-pointer">
                                Ya, Batalkan PO
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>

