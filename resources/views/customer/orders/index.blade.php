<x-app-layout pageTitle="Riwayat Pesanan" breadcrumb="Pelanggan > Riwayat Pesanan">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Riwayat Pesanan"
            subtitle="Daftar transaksi dan progres pesanan hijab."
            icon="order"
        >
            <span class="sr-only">Riwayat Pesanan</span>
            <x-slot:actions>
                <a href="{{ route('track.index') }}" class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span>Lacak Pesanan Cepat</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Filter & Search Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
            <form method="GET" action="{{ route('customer.orders.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
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
                           placeholder="Cari nomor order..."
                           class="w-full h-10 pl-10 pr-3.5 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                </div>

                {{-- Status Filter Select --}}
                <div class="w-full sm:w-56 shrink-0">
                    <select name="status" class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ $statusFilter === $st->value ? 'selected' : '' }}>
                                {{ $st->customerLabel() }}
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
                        <a href="{{ route('customer.orders.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer flex-1 sm:flex-initial">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Orders Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-[0.02em]">
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap align-middle">No. Order</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap align-middle">Tanggal</th>
                            <th class="px-4 py-2.5 sm:py-3 text-center whitespace-nowrap align-middle">Item / Qty</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap align-middle">Total Belanja</th>
                            <th class="px-4 py-2.5 sm:py-3 text-center whitespace-nowrap align-middle">Status Pesanan</th>
                            <th class="px-4 py-2.5 sm:py-3 text-center whitespace-nowrap align-middle">Pembayaran</th>
                            <th class="px-4 py-2.5 sm:py-3 text-center whitespace-nowrap w-[100px] align-middle">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @forelse($orders as $order)
                            <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                <td class="px-4 py-3 font-mono font-medium text-xs text-[#8E1B5C] whitespace-nowrap">
                                    <a href="{{ route('customer.orders.show', $order) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                        {{ $order->order_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}
                                </td>
                                @php
                                    $rowTotal = '0.00';
                                    $rowQty = '0';
                                    foreach ($order->items as $it) {
                                        $rowTotal = bcadd($rowTotal, bcmul((string) $it->qty_ordered, (string) $it->unit_selling_price, 2), 2);
                                        $rowQty = bcadd($rowQty, (string) $it->qty_ordered, 4);
                                    }
                                @endphp
                                <td class="px-4 py-3 text-center text-xs whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-[#FFFCFB] border border-[#FFD8D8] text-[11px] text-[#64748B] font-medium tabular-nums">
                                        {{ $order->items->count() }} item ({{ number_format((float) $rowQty, 0, ',', '.') }} pcs)
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-xs text-[#172B4D] whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($rowTotal) }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="badge {{ $order->status->badgeClass() }} text-[10px]">
                                        {{ $order->status->customerLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($order->invoice)
                                        <span class="badge {{ $order->invoice->status->badgeClass() }} text-[10px]">
                                            {{ $order->invoice->status->label() }}
                                        </span>
                                    @else
                                        <span class="text-xs text-[#64748B]">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <a href="{{ route('customer.orders.show', $order) }}"
                                       class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>Detail</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-[#172B4D] text-sm">Tidak ada pesanan ditemukan</p>
                                    <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">
                                        @if($search || $statusFilter)
                                            Coba ubah kata kunci pencarian atau status filter Anda.
                                        @else
                                            Anda belum memiliki pesanan terdaftar di sistem.
                                        @endif
                                    </p>
                                    @if($search || $statusFilter)
                                        <div class="mt-4">
                                            <a href="{{ route('customer.orders.index') }}" class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition-all shadow-xs">
                                                Reset Filter
                                            </a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="p-4 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
