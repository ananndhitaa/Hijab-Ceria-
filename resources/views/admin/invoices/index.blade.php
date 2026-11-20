<x-app-layout pageTitle="Invoice & Tagihan" breadcrumb="Keuangan > Invoice & Tagihan">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Invoice & Tagihan"
            subtitle="Penagihan pesanan dan pembayaran customer."
            icon="finance"
        >
            <x-slot:actions>
                <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    Lihat Sales Order
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Filter & Search Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
            <form method="GET" action="{{ route('admin.invoices.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
                {{-- Search Box --}}
                <div class="relative flex-1 w-full min-w-0">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari no. invoice, no. order, atau customer..."
                           class="w-full h-10 pl-10 pr-3.5 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                </div>

                {{-- Status Filter Select --}}
                <div class="w-full sm:w-52 shrink-0">
                    <select name="status" class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ $statusFilter === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer flex-1 sm:flex-initial">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if($search || $statusFilter)
                        <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer flex-1 sm:flex-initial">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Section C: Invoices Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[170px]">No. Invoice</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[130px]">Tanggal</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[160px]">No. Order</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-right whitespace-nowrap w-[150px]">Total Tagihan</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-right whitespace-nowrap w-[150px]">Terbayar</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-right whitespace-nowrap w-[150px]">Sisa Tagihan</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[120px]">Status</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[100px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @forelse($invoices as $invoice)
                            @php
                                $paid = $invoiceService->calculatePaidAmount($invoice);
                                $outstanding = $invoiceService->calculateOutstandingBalance($invoice);
                            @endphp
                            <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                <td class="px-4 py-3 font-mono font-bold text-xs text-[#8E1B5C] whitespace-nowrap">
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                        {{ $invoice->invoice_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 font-mono font-medium text-xs text-[#172B4D] whitespace-nowrap">
                                    @if($invoice->order)
                                        <a href="{{ route('admin.orders.show', $invoice->order) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                            {{ $invoice->order->order_no }}
                                        </a>
                                    @else
                                        <span class="text-[#64748B]">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-xs text-[#172B4D]">
                                    <p class="truncate max-w-[200px]" title="{{ $invoice->order->customer->customer_name ?? '-' }}">
                                        {{ $invoice->order->customer->customer_name ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-xs text-[#172B4D] whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($invoice->total_amount) }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-emerald-700 font-semibold whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($paid) }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs font-semibold whitespace-nowrap {{ bccomp($outstanding, '0.00', 2) > 0 ? 'text-red-700' : 'text-[#64748B]' }}">
                                    {{ \App\Support\MoneyFormatter::rupiah($outstanding) }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="badge {{ $invoice->status->badgeClass() }} text-[10px]">
                                        {{ $invoice->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                        <a href="{{ route('admin.invoices.show', $invoice) }}"
                                           class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#68113F] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                           title="Lihat Detail & Pembayaran"
                                           aria-label="Lihat Detail & Pembayaran">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        <a href="{{ route('admin.invoices.print', $invoice) }}"
                                           target="_blank"
                                           class="w-8 h-8 rounded-lg bg-white text-[#8E1B5C] border border-[#FFD8D8] hover:bg-[#8E1B5C] hover:text-white inline-flex items-center justify-center transition-all duration-200 cursor-pointer shadow-xs shrink-0"
                                           title="Cetak Faktur Invoice"
                                           aria-label="Cetak Faktur Invoice">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.05-.37-2.14-.37-3.254 0-4.694 3.806-8.5 8.5-8.5s8.5 3.806 8.5 8.5c0 1.114-.13 2.204-.37 3.254M17 17v4.5a.75.75 0 01-.75.75H7.75a.75.75 0 01-.75-.75V17m10 0H7m10 0v-4.5a.75.75 0 00-.75-.75H7.75a.75.75 0 00-.75.75V17m2-8.5h.01"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-[#172B4D] text-sm">Belum ada Invoice</p>
                                    <p class="text-xs text-[#64748B] mt-1 max-w-md mx-auto">Invoice akan tampil setelah diterbitkan dari Sales Order yang memenuhi syarat.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all shadow-xs cursor-pointer">
                                            Lihat Sales Order
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
                <div class="px-5 py-3.5 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
