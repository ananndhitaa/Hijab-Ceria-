<x-app-layout pageTitle="Tagihan Saya" breadcrumb="Pelanggan > Tagihan Saya">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Daftar Tagihan & Faktur"
            subtitle="Ringkasan seluruh tagihan pembayaran faktur pesanan konveksi hijab Anda."
            icon="order"
        >
            <span class="sr-only">Tagihan Pelanggan</span>
            <x-slot:actions>
                <a href="{{ route('customer.orders.index') }}" class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Lihat Riwayat Pesanan</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- KPI Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-stretch">
            {{-- Total Tagihan --}}
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-[#FFD8D8] shadow-card flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Total Tagihan</p>
                    <div class="w-8 h-8 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60">
                    <p class="text-xl sm:text-2xl font-bold font-mono tracking-tight text-[#172B4D]">
                        {{ \App\Support\MoneyFormatter::rupiah($stats['total_amount']) }}
                    </p>
                    <p class="text-[11px] text-[#64748B] mt-1">{{ $stats['total_count'] }} faktur terbit</p>
                </div>
            </div>

            {{-- Belum Lunas (Perlu Pembayaran) --}}
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-[#FFD8D8] shadow-card flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-bold text-[#ED3500] uppercase tracking-wider leading-tight">Sisa Belum Lunas</p>
                    <div class="w-8 h-8 rounded-lg bg-[#FFD8D8] text-[#ED3500] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60">
                    <p class="text-xl sm:text-2xl font-bold font-mono tracking-tight text-[#ED3500]">
                        {{ \App\Support\MoneyFormatter::rupiah($stats['unpaid_balance']) }}
                    </p>
                    <p class="text-[11px] text-[#64748B] mt-1">{{ $stats['unpaid_count'] }} tagihan aktif / DP</p>
                </div>
            </div>

            {{-- Sudah Lunas --}}
            <div class="bg-white rounded-xl p-4 sm:p-5 border border-[#FFD8D8] shadow-card flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider leading-tight">Faktur Lunas</p>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60">
                    <p class="text-xl sm:text-2xl font-bold font-mono tracking-tight text-emerald-700">
                        {{ $stats['paid_count'] }} Faktur
                    </p>
                    <p class="text-[11px] text-[#64748B] mt-1">Pembayaran telah terverifikasi penuh</p>
                </div>
            </div>
        </div>

        {{-- Filter & Search Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
            <form method="GET" action="{{ route('customer.invoices.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
                {{-- Search Box --}}
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nomor invoice atau nomor order..."
                           class="w-full h-10 pl-10 pr-3.5 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                </div>

                {{-- Status Filter Select --}}
                <div class="w-full sm:w-56 shrink-0">
                    <select name="status" class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                        <option value="">Semua Status Tagihan</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ $statusFilter === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#A6266E] transition-all duration-200 ease-out shadow-xs cursor-pointer flex-1 sm:flex-initial">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if($search || $statusFilter)
                        <a href="{{ route('customer.invoices.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer flex-1 sm:flex-initial">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Invoices Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <x-nayara-section-header title="Daftar Tagihan Faktur" />

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#FFD8D8] text-[#64748B] uppercase text-[10px] tracking-wider border-b border-[#FFD8D8]">
                            <th class="p-3.5 pl-5">NO. INVOICE</th>
                            <th class="p-3.5">TANGGAL</th>
                            <th class="p-3.5">PESANAN TERKAIT</th>
                            <th class="p-3.5 text-right">TOTAL TAGIHAN</th>
                            <th class="p-3.5 text-right">TERBAYAR</th>
                            <th class="p-3.5 text-right">SISA TAGIHAN</th>
                            <th class="p-3.5 text-center">STATUS</th>
                            <th class="p-3.5 pr-5 text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($invoices as $inv)
                            @php
                                $paid = app(\App\Services\Sales\InvoiceService::class)->calculatePaidAmount($inv);
                                $balance = app(\App\Services\Sales\InvoiceService::class)->calculateOutstandingBalance($inv);
                            @endphp
                            <tr class="hover:bg-[#FFFCFB] transition-colors">
                                <td class="p-3.5 pl-5 font-mono font-bold text-[#8E1B5C]">
                                    {{ $inv->invoice_no }}
                                </td>
                                <td class="p-3.5 text-[#64748B] whitespace-nowrap">
                                    {{ $inv->invoice_date ? $inv->invoice_date->format('d M Y') : '—' }}
                                </td>
                                <td class="p-3.5 font-mono text-xs font-semibold text-[#172B4D]">
                                    @if($inv->order)
                                        <a href="{{ route('customer.orders.show', $inv->order) }}" class="text-[#8E1B5C] hover:underline flex items-center gap-1">
                                            <span>{{ $inv->order->order_no }}</span>
                                            <svg class="w-3 h-3 text-[#64748B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @else
                                        <span class="text-slate-400 italic">—</span>
                                    @endif
                                </td>
                                <td class="p-3.5 text-right font-mono font-semibold text-[#172B4D] whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($inv->total_amount) }}
                                </td>
                                <td class="p-3.5 text-right font-mono font-medium text-emerald-700 whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($paid) }}
                                </td>
                                <td class="p-3.5 text-right font-mono font-bold {{ bccomp($balance, '0.00', 2) > 0 ? 'text-[#ED3500]' : 'text-slate-500' }} whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($balance) }}
                                </td>
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    <span class="badge {{ $inv->status->badgeClass() }} text-[11px] font-semibold">
                                        {{ $inv->status->label() }}
                                    </span>
                                </td>
                                <td class="p-3.5 pr-5 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                        <a href="{{ route('customer.invoices.print', $inv) }}"
                                           target="_blank"
                                           class="inline-flex items-center justify-center gap-1 h-8 px-2.5 rounded-lg border border-[#FFD8D8] bg-white text-[#8E1B5C] text-[11px] font-semibold hover:bg-[#FFD8D8] transition-all shadow-xs"
                                           title="Cetak Faktur Invoice">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.05-.37-2.14-.37-3.254 0-4.694 3.806-8.5 8.5-8.5s8.5 3.806 8.5 8.5c0 1.114-.13 2.204-.37 3.254M17 17v4.5a.75.75 0 01-.75.75H7.75a.75.75 0 01-.75-.75V17m10 0H7m10 0v-4.5a.75.75 0 00-.75-.75H7.75a.75.75 0 00-.75.75V17m2-8.5h.01"/>
                                            </svg>
                                            <span>Cetak</span>
                                        </a>

                                        @if($inv->order)
                                            <a href="{{ route('customer.orders.show', $inv->order) }}"
                                               class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg bg-[#8E1B5C] text-white text-[11px] font-semibold hover:bg-[#A6266E] transition-all shadow-xs">
                                                <span>Detail Pesanan</span>
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400 italic">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-[#64748B]">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-10 h-10 rounded-full bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <p class="font-bold text-[#172B4D] text-sm">Belum Ada Tagihan</p>
                                        <p class="text-xs text-[#64748B]">
                                            @if($search || $statusFilter)
                                                Tidak ditemukan tagihan yang sesuai dengan kriteria filter pencarian.
                                            @else
                                                Faktur tagihan akan otomatis muncul setelah pesanan Anda dikonfirmasi oleh admin konveksi.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
                <div class="p-4 border-t border-[#FFD8D8]">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

