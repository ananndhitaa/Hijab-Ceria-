<x-app-layout pageTitle="Sales Order" breadcrumb="Penjualan > Sales Order">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Sales Order"
            subtitle="Kelola pesanan customer dan progres operasional."
            icon="order"
        >
            <x-slot:actions>
                <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Buat Sales Order</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Filter & Search Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
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
                           placeholder="Cari nomor order atau nama customer..."
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
                        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer flex-1 sm:flex-initial">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Section C: Orders Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                            <th class="px-4 py-3 text-[11px] font-semibold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[180px]">No. Order</th>
                            <th class="px-4 py-3 text-[11px] font-semibold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[130px]">Tanggal</th>
                            <th class="px-4 py-3 text-[11px] font-semibold text-[#64748B] uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-3 text-[11px] font-semibold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[170px]">Item / Qty</th>
                            <th class="px-4 py-3 text-[11px] font-semibold text-[#64748B] uppercase tracking-wider text-right whitespace-nowrap w-[150px]">Total</th>
                            <th class="px-4 py-3 text-[11px] font-semibold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[130px]">Status</th>
                            <th class="px-4 py-3 text-[11px] font-semibold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[100px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @forelse($orders as $order)
                            <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                <td class="px-4 py-3 font-mono font-medium text-xs text-[#8E1B5C] whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                        {{ $order->order_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 font-medium text-xs text-[#172B4D]">
                                    <p class="truncate max-w-[240px]" title="{{ $order->customer->customer_name ?? '-' }}">
                                        {{ $order->customer->customer_name ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-center text-xs whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-[#FFFCFB] border border-[#FFD8D8] text-[11px] text-[#64748B] font-medium tabular-nums">
                                        {{ $order->items->count() }} item ({{ number_format($order->total_qty, 0, ',', '.') }} pcs)
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-xs text-[#172B4D] whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($order->total_amount) }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="badge {{ $order->status->badgeClass() }} text-[10px]">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                           class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                           title="Lihat Detail"
                                           aria-label="Lihat Detail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        @if(in_array($order->status, [\App\Enums\OrderStatus::KOMUNIKASI, \App\Enums\OrderStatus::DEAL]))
                                            <a href="{{ route('admin.orders.edit', $order) }}"
                                               class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                               title="Edit Order"
                                               aria-label="Edit Order">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
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
                                    <p class="font-bold text-[#172B4D] text-sm">Belum ada Sales Order</p>
                                    <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">Pesanan yang dibuat akan tampil dan dapat dipantau dari halaman ini.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Buat Sales Order</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="px-5 py-3.5 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
