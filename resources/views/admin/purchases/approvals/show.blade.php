<x-app-layout pageTitle="Review Persetujuan PO" breadcrumb="Keuangan Admin > Persetujuan PO > Review">
    <div class="max-w-5xl mx-auto space-y-5"
         x-data="{ showApproveModal: false, showRevisionModal: false, showRejectModal: false }">

        {{-- ── 1. Page Header & Actions ───────────────────────────────────── --}}
        <x-feature-header
            :title="$purchase->purchase_no"
            icon="purchase"
        >
            <x-slot:badge>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        PERSETUJUAN PO
                    </span>
                    <x-status-badge :status="$purchase->approval_status" context="approval" size="card" />
                    <x-status-badge :status="$purchase->status" context="purchase" size="card" />
                </div>
            </x-slot:badge>
            <p class="text-xs text-[#64748B] flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                <span>Supplier: <strong class="text-[#172B4D] font-medium">{{ $purchase->supplier?->supplier_name ?? '—' }}</strong></span>
                <span class="text-gray-300 hidden sm:inline">&bull;</span>
                <span>Tanggal PO: <strong class="text-[#172B4D] font-medium">{{ $purchase->purchase_date?->format('d M Y') ?? '—' }}</strong></span>
                @if($purchase->submitted_at)
                    <span class="text-gray-300 hidden sm:inline">&bull;</span>
                    <span>Diajukan: <strong class="text-[#172B4D] font-medium">{{ \App\Support\DateTimeFormatter::formatShortDateTime($purchase->submitted_at) }}</strong></span>
                    @if($purchase->submittedByUser)
                        <span class="text-gray-300 hidden sm:inline">&bull;</span>
                        <span>Oleh: <strong class="text-[#172B4D] font-medium">{{ $purchase->submittedByUser->name }}</strong></span>
                    @endif
                @endif
            </p>
            <x-slot:actions>
                <a href="{{ route('admin.purchases.approvals.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali ke Antrean</span>
                </a>

                @if($purchase->approval_status === \App\Enums\PurchaseApprovalStatus::APPROVED && $purchase->status !== \App\Enums\PurchaseStatus::CANCELLED && bccomp($purchase->outstanding_amount, '0.00', 2) > 0)
                    <a href="{{ route('admin.purchases.payments.create', $purchase) }}"
                       class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Catat Pembayaran Supplier</span>
                    </a>
                @endif

                @if($purchase->approval_status === \App\Enums\PurchaseApprovalStatus::PENDING_APPROVAL)
                    {{-- Button Tolak --}}
                    <button type="button"
                            @click="showRejectModal = true"
                            class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-rose-300 bg-rose-50 text-xs sm:text-sm font-semibold text-rose-700 hover:bg-rose-100 hover:text-rose-800 transition shadow-xs cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>Tolak</span>
                    </button>

                    {{-- Button Minta Revisi --}}
                    <button type="button"
                            @click="showRevisionModal = true"
                            class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-orange-300 bg-orange-50 text-xs sm:text-sm font-semibold text-orange-800 hover:bg-orange-100 hover:text-orange-900 transition shadow-xs cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>
                        </svg>
                        <span>Minta Revisi</span>
                    </button>

                    {{-- Button Setujui --}}
                    @if($isSufficient)
                        <button type="button"
                                @click="showApproveModal = true"
                                class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl bg-emerald-700 text-white text-xs sm:text-sm font-semibold hover:bg-emerald-800 transition shadow-xs cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>Setujui Pembelian</span>
                        </button>
                    @else
                        <button type="button"
                                disabled
                                title="Dana tersedia belum mencukupi untuk menyetujui pembelian ini."
                                class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl bg-gray-200 text-gray-500 text-xs sm:text-sm font-semibold cursor-not-allowed opacity-75 shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span>Setujui (Dana Belum Cukup)</span>
                        </button>
                    @endif
                @endif
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Source Context Badge ────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg {{ $isManual ? 'bg-gray-100 text-gray-600' : 'bg-[#FFD8D8] text-[#8E1B5C]' }} flex items-center justify-center shrink-0">
                    @if($isManual)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                        </svg>
                    @endif
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-[#172B4D]">
                            {{ $isManual ? 'Pengadaan Manual (Non-Batch)' : 'Pengadaan Terkait Batch Produksi' }}
                        </span>
                        @if(! $isManual)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                                {{ $purchase->batch->batch_no }}
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-[#64748B]">
                        @if($isManual)
                            Pengadaan bahan baku stok gudang reguler tanpa alokasi pesanan spesifik.
                        @else
                            Dibuat berdasarkan kebutuhan material batch produksi pesanan {{ $purchase->batch?->orderItem?->order?->order_no ?? '—' }} ({{ $purchase->batch?->orderItem?->order?->customer?->customer_name ?? '—' }}).
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- ── MRP Operational Planning Context ────────────────────────────── --}}
        @if(! $isManual && $purchase->batch)
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <x-nayara-section-header
                    title="Perencanaan Kebutuhan Bahan (MRP)"
                    meta="Batch {{ $purchase->batch->batch_no }}"
                />

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[#64748B] uppercase font-semibold text-[11.5px] tracking-[0.02em]">
                                <th class="py-2.5 px-4">Material</th>
                                <th class="py-2.5 px-4 text-right">Kebutuhan Batch</th>
                                <th class="py-2.5 px-4 text-right">Stok Saat Ini</th>
                                <th class="py-2.5 px-4 text-right">Kekurangan (Shortage)</th>
                                <th class="py-2.5 px-4 text-center">Status MRP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]/60 text-[#172B4D]">
                            @foreach($mrpMap as $matId => $mItem)
                                <tr class="hover:bg-[#FFFCFB]/50 transition-colors">
                                    <td class="py-3 px-4 font-medium">{{ $mItem['material_name'] ?? '—' }}</td>
                                    <td class="py-3 px-4 text-right font-mono">{{ number_format((float)($mItem['required_qty'] ?? 0), 4, ',', '.') }} {{ $mItem['symbol'] ?? '' }}</td>
                                    <td class="py-3 px-4 text-right font-mono text-[#64748B]">{{ number_format((float)($mItem['current_stock'] ?? 0), 4, ',', '.') }} {{ $mItem['symbol'] ?? '' }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-rose-600">{{ number_format((float)($mItem['shortage_qty'] ?? 0), 4, ',', '.') }} {{ $mItem['symbol'] ?? '' }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            {{ $mItem['status_label'] ?? 'TERPENUHI' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ── 3. Financial Context Overview ─────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- Kas Saat Ini --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Kas Saat Ini</span>
                <p class="text-lg font-bold font-mono text-[#8E1B5C] mt-1">
                    Rp {{ number_format((float) $currentCash, 0, ',', '.') }}
                </p>
                <p class="text-[10px] text-[#64748B] mt-0.5">Saldo riil kas operasional</p>
            </div>

            {{-- Komitmen Belum Bayar --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Komitmen PO Lain</span>
                <p class="text-lg font-bold font-mono text-amber-700 mt-1">
                    Rp {{ number_format((float) $committedUnpaid, 0, ',', '.') }}
                </p>
                <p class="text-[10px] text-[#64748B] mt-0.5">Total sisa PO disetujui lain</p>
            </div>

            {{-- Dana Tersedia untuk PO Baru --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Dana Tersedia untuk Komitmen Baru</span>
                <p class="text-lg font-bold font-mono {{ bccomp($availableToCommit, '0.00', 2) < 0 ? 'text-rose-600' : 'text-emerald-700' }} mt-1">
                    Rp {{ number_format((float) $availableToCommit, 0, ',', '.') }}
                </p>
                <p class="text-[10px] text-[#64748B] mt-0.5">Kas dikurangi seluruh komitmen</p>
            </div>

            {{-- Total Tagihan PO Ini & Kecukupan --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider">Nilai PO Diajukan</span>
                    @if($isSufficient)
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            ✓ CUKUP
                        </span>
                    @else
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            ⚠ DANA KURANG
                        </span>
                    @endif
                </div>
                <p class="text-lg font-bold font-mono text-[#172B4D] mt-1">
                    Rp {{ number_format((float) $poTotal, 0, ',', '.') }}
                </p>
                <p class="text-[10px] text-[#64748B] mt-0.5">
                    Proyeksi Sisa Kas: <strong class="font-mono {{ bccomp($proyeksiSisa, '0.00', 2) < 0 ? 'text-rose-600' : 'text-emerald-700' }}">Rp {{ number_format((float) $proyeksiSisa, 0, ',', '.') }}</strong>
                </p>
            </div>
        </div>

        {{-- ── 4. Rincian Material Items ─────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <x-nayara-section-header
                title="Rincian Item Material"
                meta="{{ $purchase->items->count() }} item material"
            />

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[#64748B] uppercase font-semibold text-[11.5px] sm:text-[12px] tracking-[0.02em]">
                            <th class="py-2.5 sm:py-3 px-4 w-12 text-center align-middle">No</th>
                            <th class="py-2.5 sm:py-3 px-4 min-w-[200px] align-middle">Material</th>
                            <th class="py-2.5 sm:py-3 px-4 w-20 text-center align-middle">Satuan</th>
                            <th class="py-2.5 sm:py-3 px-4 w-28 text-right align-middle">Qty Pesan</th>
                            <th class="py-2.5 sm:py-3 px-4 w-32 text-right align-middle">Harga Satuan</th>
                            <th class="py-2.5 sm:py-3 px-4 w-36 text-right align-middle">Subtotal</th>
                            @if(! $isManual)
                                <th class="py-2.5 sm:py-3 px-4 text-center align-middle">Status Kebutuhan</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/60 text-[#172B4D]">
                        @foreach($purchase->items as $idx => $item)
                            @php
                                $subtotal = bcmul((string)$item->qty, (string)$item->unit_price, 2);
                                $mrpInfo = $mrpMap[$item->material_id] ?? null;
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
                                <td class="py-3 px-4 text-right tabular-nums font-semibold text-[#172B4D]">
                                    {{ number_format((float)$item->qty, 4, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums text-[#64748B]">
                                    Rp {{ number_format((float)$item->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums font-bold text-[#172B4D]">
                                    Rp {{ number_format((float)$subtotal, 0, ',', '.') }}
                                </td>
                                @if(! $isManual)
                                    <td class="py-3 px-4 text-center">
                                        @if($mrpInfo)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ $mrpInfo['status_label'] }}
                                            </span>
                                        @else
                                            <span class="text-[#64748B] text-[11px]">—</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#FFFCFB]/90 border-t-2 border-[#FFD8D8]">
                            <td colspan="5" class="py-3 px-4 text-right uppercase tracking-wider text-xs font-semibold text-[#64748B]">
                                Total Pembelian
                            </td>
                            <td class="py-3 px-4 text-right tabular-nums text-sm sm:text-base font-bold text-[#8E1B5C] whitespace-nowrap">
                                Rp {{ number_format((float)$purchase->total_amount_string, 0, ',', '.') }}
                            </td>
                            @if(! $isManual)
                                <td></td>
                            @endif
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

        {{-- ── 5. Riwayat Persetujuan (Audit Trail) ───────────────────────── --}}
        @if($purchase->approvalHistories->isNotEmpty())
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <x-nayara-section-header
                    title="Riwayat Persetujuan"
                    meta="{{ $purchase->approvalHistories->count() }} catatan riwayat"
                />

                <div class="divide-y divide-[#FFD8D8]/60">
                    @foreach($purchase->approvalHistories->sortByDesc('id') as $history)
                        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-start justify-between gap-3 text-xs">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-[#172B4D]">{{ $history->action }}</span>
                                    <span class="text-[#64748B]">&bull;</span>
                                    <span class="text-[#64748B]">Oleh: <strong class="text-[#172B4D]">{{ $history->user?->name ?? 'Sistem' }}</strong></span>
                                </div>
                                @if($history->note)
                                    <p class="text-[#172B4D] bg-[#FFD8D8]/30 p-2.5 rounded-lg border border-[#FFD8D8]">
                                        "{{ $history->note }}"
                                    </p>
                                @endif
                            </div>
                            <div class="text-[11px] text-[#64748B] shrink-0 font-mono">
                                {{ $history->created_at ? $history->created_at->format('d/m/Y H:i') : '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── 6. Modals ──────────────────────────────────────────────────── --}}

        {{-- Modal Setujui PO --}}
        <div x-show="showApproveModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
             @click.self="showApproveModal = false">
            <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl"
                 @click.stop>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#172B4D]">Setujui Purchase Order?</h3>
                        <p class="text-xs font-mono text-[#64748B]">{{ $purchase->purchase_no }}</p>
                    </div>
                </div>

                <p class="text-xs text-[#172B4D] leading-relaxed">
                    Setelah disetujui, Purchase Order akan masuk ke antrean pembayaran supplier dan dana komitmen sebesar <strong>Rp {{ number_format((float)$purchase->total_amount_string, 0, ',', '.') }}</strong> akan dikomitmenkan.
                </p>

                <form method="POST" action="{{ route('admin.purchases.approvals.approve', $purchase) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="approve_note" class="block text-xs font-semibold text-[#172B4D] mb-1">
                            Catatan Persetujuan (Opsional)
                        </label>
                        <textarea id="approve_note"
                                  name="review_note"
                                  rows="2"
                                  placeholder="Tambahkan catatan jika diperlukan..."
                                  class="w-full p-2.5 text-xs border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                        <button type="button"
                                @click="showApproveModal = false"
                                class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-9 px-4 rounded-xl bg-emerald-700 text-white text-xs font-semibold hover:bg-emerald-800 transition shadow-xs cursor-pointer">
                            Ya, Setujui Pembelian
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Minta Revisi PO --}}
        <div x-show="showRevisionModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
             @click.self="showRevisionModal = false">
            <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl"
                 @click.stop>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-800 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[#172B4D]">Minta Revisi ke Gudang</h3>
                        <p class="text-xs font-mono text-[#64748B]">{{ $purchase->purchase_no }}</p>
                    </div>
                </div>

                <p class="text-xs text-[#172B4D] leading-relaxed">
                    PO akan dikembalikan ke status revisi agar staf Gudang dapat memperbaiki rincian material atau kuantitas yang diajukan.
                </p>

                <form method="POST" action="{{ route('admin.purchases.approvals.request-revision', $purchase) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="revision_note" class="block text-xs font-semibold text-[#172B4D] mb-1">
                            Poin Revisi <span class="text-red-500">*</span>
                        </label>
                        <textarea id="revision_note"
                                  name="review_note"
                                  rows="3"
                                  required
                                  minlength="3"
                                  placeholder="Jelaskan alasan dan poin yang perlu diperbaiki oleh Gudang..."
                                  class="w-full p-2.5 text-xs border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                        <button type="button"
                                @click="showRevisionModal = false"
                                class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-9 px-4 rounded-xl bg-orange-600 text-white text-xs font-semibold hover:bg-orange-700 transition shadow-xs cursor-pointer">
                            Kirim Permintaan Revisi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Tolak PO --}}
        <div x-show="showRejectModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
             @click.self="showRejectModal = false">
            <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl"
                 @click.stop>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-rose-900">Tolak Purchase Order</h3>
                        <p class="text-xs font-mono text-[#64748B]">{{ $purchase->purchase_no }}</p>
                    </div>
                </div>

                <p class="text-xs text-[#172B4D] leading-relaxed">
                    PO yang ditolak tidak dapat diproses lagi atau dibayar. Berikan alasan penolakan yang jelas untuk dicatat pada riwayat audit.
                </p>

                <form method="POST" action="{{ route('admin.purchases.approvals.reject', $purchase) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="reject_reason" class="block text-xs font-semibold text-[#172B4D] mb-1">
                            Alasan Penolakan <span class="text-red-500">*</span>
                        </label>
                        <textarea id="reject_reason"
                                  name="reason"
                                  rows="3"
                                  required
                                  minlength="3"
                                  placeholder="Tuliskan alasan penolakan PO..."
                                  class="w-full p-2.5 text-xs border border-[#FFD8D8] rounded-xl bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C]"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                        <button type="button"
                                @click="showRejectModal = false"
                                class="h-9 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="h-9 px-4 rounded-xl bg-rose-700 text-white text-xs font-semibold hover:bg-rose-800 transition shadow-xs cursor-pointer">
                            Tolak Pembelian
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
