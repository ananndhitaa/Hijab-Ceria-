<x-app-layout pageTitle="Batch Produksi" breadcrumb="Produksi > Batch Produksi">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Batch Produksi"
            subtitle="Pantau batch kerja produksi dari pesanan."
            icon="production"
        >
            <x-slot:actions>
                <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    Lihat Sales Order
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Filter & Search Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
            <form method="GET" action="{{ route('admin.batches.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
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
                           placeholder="Cari no. batch, no. order, customer, SKU, atau varian..."
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
                        <a href="{{ route('admin.batches.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer flex-1 sm:flex-initial">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Section C: Batches Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-wider">
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap w-[160px] align-middle">No. Batch</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap w-[160px] align-middle">No. Order</th>
                            <th class="px-4 py-2.5 sm:py-3 w-[180px] align-middle">Customer</th>
                            <th class="px-4 py-2.5 sm:py-3 align-middle">Produk & Varian</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap w-[120px] align-middle">SKU</th>
                            <th class="px-4 py-2.5 sm:py-3 text-right whitespace-nowrap w-[110px] align-middle">Target Qty</th>
                            <th class="px-4 py-2.5 sm:py-3 text-center whitespace-nowrap w-[130px] align-middle">Status</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap w-[140px] align-middle">Dibuat Pada</th>
                            <th class="px-4 py-2.5 sm:py-3 text-center whitespace-nowrap w-[90px] align-middle">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @forelse($batches as $batch)
                            <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                <td class="px-4 py-3 font-mono font-bold text-xs text-[#8E1B5C] whitespace-nowrap">
                                    <a href="{{ route('admin.batches.show', $batch) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                        {{ $batch->batch_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 font-mono font-medium text-xs text-[#172B4D] whitespace-nowrap">
                                    @if($batch->orderItem && $batch->orderItem->order)
                                        <a href="{{ route('admin.orders.show', $batch->orderItem->order) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                            {{ $batch->orderItem->order->order_no }}
                                        </a>
                                    @else
                                        <span class="text-[#64748B]">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-xs text-[#172B4D]">
                                    <p class="truncate max-w-[170px]" title="{{ $batch->orderItem->order->customer->customer_name ?? '-' }}">
                                        {{ $batch->orderItem->order->customer->customer_name ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <p class="font-semibold text-[#172B4D] leading-tight">
                                        {{ $batch->orderItem->variant->product->product_name ?? 'Produk' }}
                                    </p>
                                    <p class="text-[11px] text-[#64748B] mt-0.5">
                                        {{ $batch->orderItem->variant->variant_name ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $batch->orderItem->variant->sku ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-xs text-[#172B4D] whitespace-nowrap">
                                    {{ number_format($batch->target_qty, 0, ',', '.') }} pcs
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="badge {{ $batch->status->badgeClass() }} text-[10px]">
                                        {{ $batch->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $batch->created_at ? \App\Support\DateTimeFormatter::formatShortDateTime($batch->created_at) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <a href="{{ route('admin.batches.show', $batch) }}"
                                       class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                       title="Lihat Detail Batch"
                                       aria-label="Lihat Detail Batch">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-[#172B4D] text-sm">Belum ada Batch Produksi</p>
                                    <p class="text-xs text-[#64748B] mt-1 max-w-md mx-auto">Batch akan tampil setelah dibuat dari Sales Order yang sudah mencapai status DEAL atau BAHAN.</p>
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

            @if($batches->hasPages())
                <div class="px-5 py-3.5 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
