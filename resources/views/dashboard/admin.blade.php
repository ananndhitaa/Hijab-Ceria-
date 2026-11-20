<x-app-layout pageTitle="Dashboard Admin">
    <div class="space-y-5">


        {{-- NAYARA Dashboard Hero Banner --}}
        <x-dashboard-hero
            role-label="Administrator"
            title="Dashboard Admin"
            description="Selamat Datang, {{ auth()->user()->name }} · Pantau operasional pesanan, tagihan, dan antrean kerja NAYARA."
            action-label="Buat Sales Order"
            :action-route="route('admin.orders.create')"
            action-icon="plus"
        />

        {{-- Section B: Primary Operational KPI Grid (6 Normalized Core Cards) --}}
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3.5 items-stretch">
            {{-- 1. Total Order --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Total Order</p>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-[#172B4D] font-mono tracking-tight leading-none">{{ $totalOrders }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Semua pesanan</p>
                </div>
            </div>

            {{-- 2. Order Aktif --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Order Aktif</p>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-[#8E1B5C] font-mono tracking-tight leading-none">{{ $activeOrders }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Sedang diproses</p>
                </div>
            </div>

            {{-- 3. Menunggu Deal --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Menunggu Deal</p>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-[#172B4D] font-mono tracking-tight leading-none">{{ $komunikasiOrdersCount }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Tahap komunikasi</p>
                </div>
            </div>

            {{-- 4. Menunggu Batch --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Menunggu Batch</p>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-[#172B4D] font-mono tracking-tight leading-none">{{ $dealAwaitingBatchCount }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">DEAL siap produksi</p>
                </div>
            </div>

            {{-- 5. Invoice Belum Lunas --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Belum Lunas</p>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-[#172B4D] font-mono tracking-tight leading-none">{{ $unsettledInvoiceCount }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">{!! 'Unpaid & Partial' !!}</p>
                </div>
            </div>

            {{-- 6. Sisa Tagihan --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Sisa Tagihan</p>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-base sm:text-[17px] xl:text-[15px] 2xl:text-lg font-bold text-[#8E1B5C] font-mono tracking-tight leading-none whitespace-nowrap" title="{{ \App\Support\MoneyFormatter::rupiah($totalOutstanding) }}">
                            {{ \App\Support\MoneyFormatter::rupiah($totalOutstanding) }}
                        </p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Piutang usaha</p>
                </div>
            </div>
        </div>

        {{-- Section C: Perlu Tindakan (Single Canonical Action Center) --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card space-y-3">
            <div class="flex items-center justify-between pb-2.5 border-b border-[#FFD8D8]">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#8E1B5C]"></span>
                    <h3 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Perlu Tindakan</h3>
                </div>
                @if(isset($adminActions) && $adminActions->isNotEmpty())
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        {{ $adminActions->count() }} item
                    </span>
                @endif
            </div>

            @if(isset($adminActions) && $adminActions->isNotEmpty())
                <div class="divide-y divide-[#FFD8D8] border border-[#FFD8D8] rounded-xl overflow-hidden bg-[#FFFCFB]/70">
                    @foreach($adminActions as $action)
                        <div class="px-4 py-3 sm:py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-white transition-colors duration-150 min-h-[58px]">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $action['badge_class'] ?? 'bg-gray-100 text-gray-800 border-gray-200' }} shrink-0">
                                    {{ $action['status_label'] }}
                                </span>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-[#172B4D] font-mono leading-none">{{ $action['reference'] }}</div>
                                    <div class="text-[11px] text-[#64748B] mt-1">{{ $action['subtitle'] }}</div>
                                </div>
                            </div>
                            <div class="flex items-center self-end sm:self-auto shrink-0">
                                <a href="{{ $action['action_url'] }}"
                                   class="inline-flex items-center justify-center min-w-[76px] h-8 px-3.5 rounded-lg text-xs font-semibold {{ $action['button_class'] }} transition shadow-xs">
                                    {{ $action['action_label'] }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-4 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] text-xs text-[#64748B] text-center">
                    Tidak ada tindakan yang perlu ditangani.
                </div>
            @endif
        </div>

        {{-- Section D: Ringkasan Pipeline Pesanan (9 Stages Strip - Unified Box Styling) --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#FFD8D8] pb-3">
                <div>
                    <h3 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Ringkasan Pipeline Pesanan</h3>
                    <p class="text-[11px] text-[#64748B]">Distribusi jumlah pesanan di seluruh 9 tahap operasional.</p>
                </div>
                <a href="{{ route('admin.kanban.index') }}" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] cursor-pointer transition-all duration-200 ease-out">
                    <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    <span>Buka Kanban Board</span>
                </a>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-5 lg:grid-cols-9 gap-2">
                @foreach(\App\Enums\OrderStatus::kanbanColumns() as $stage)
                    @php
                        $count = $statusPipelineCounts[$stage->value] ?? 0;
                    @endphp
                    <div class="p-2.5 rounded-lg border text-center cursor-default transition-all duration-200 ease-out {{ $count > 0 ? 'bg-[#FFD8D8] border-[#FFD8D8] text-[#8E1B5C]' : 'bg-[#FFFCFB] border-[#FFD8D8] text-[#64748B]' }}">
                        <p class="text-[10px] font-bold uppercase truncate" title="{{ $stage->label() }}">
                            {{ $stage->label() }}
                        </p>
                        <p class="text-base font-bold mt-1 {{ $count > 0 ? 'text-[#8E1B5C]' : 'text-gray-400' }}">
                            {{ $count }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Section E: Recent Sales Orders & Recent Production Batches --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Recent Sales Orders --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3 border-b border-[#FFD8D8] pb-2.5">
                        <div>
                            <h3 class="text-sm font-bold text-[#172B4D]">Sales Order Terbaru</h3>
                            <p class="text-[11px] text-[#64748B]">5 pesanan penjualan terakhir.</p>
                        </div>
                        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] cursor-pointer transition-all duration-200 ease-out">
                            Semua Order
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-[#FFD8D8] text-[#64748B] uppercase tracking-wider text-[10px]">
                                    <th class="pb-2 font-semibold">No. Order</th>
                                    <th class="pb-2 font-semibold">Customer</th>
                                    <th class="pb-2 font-semibold text-right">Total</th>
                                    <th class="pb-2 font-semibold text-center">Status</th>
                                    <th class="pb-2 font-semibold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FFD8D8]">
                                @forelse($recentOrders as $order)
                                    <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                        <td class="py-2.5 font-mono font-bold text-[#8E1B5C]">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                                {{ $order->order_no }}
                                            </a>
                                        </td>
                                        <td class="py-2.5 font-medium text-[#172B4D] truncate max-w-[120px]" title="{{ $order->customer->customer_name ?? '-' }}">
                                            {{ $order->customer->customer_name ?? '-' }}
                                        </td>
                                        <td class="py-2.5 text-right font-semibold text-[#172B4D] whitespace-nowrap">
                                            {{ \App\Support\MoneyFormatter::rupiah($order->total_amount) }}
                                        </td>
                                        <td class="py-2.5 text-center">
                                            <span class="badge {{ $order->status->badgeClass() }} text-[10px]">
                                                {{ $order->status->label() }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 text-center">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex items-center justify-center px-2 py-0.5 rounded border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#8E1B5C] hover:bg-[#8E1B5C] hover:text-white hover:border-[#8E1B5C] transition-all cursor-pointer">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-xs text-[#64748B] bg-[#FFFCFB] rounded-lg cursor-default">
                                            <div class="w-8 h-8 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                                </svg>
                                            </div>
                                            <p class="font-bold text-[#172B4D]">Belum ada Sales Order</p>
                                            <p class="text-[11px] text-[#64748B] mt-0.5">Order terbaru akan tampil di sini.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Recent Production Batches --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3 border-b border-[#FFD8D8] pb-2.5">
                        <div>
                            <h3 class="text-sm font-bold text-[#172B4D]">Batch Produksi Terbaru</h3>
                            <p class="text-[11px] text-[#64748B]">5 batch produksi terakhir.</p>
                        </div>
                        <a href="{{ route('admin.batches.index') }}" class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] cursor-pointer transition-all duration-200 ease-out">
                            Semua Batch
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-[#FFD8D8] text-[#64748B] uppercase tracking-wider text-[10px]">
                                    <th class="pb-2 font-semibold">No. Batch</th>
                                    <th class="pb-2 font-semibold">No. Order</th>
                                    <th class="pb-2 font-semibold text-right">Target</th>
                                    <th class="pb-2 font-semibold text-center">Status</th>
                                    <th class="pb-2 font-semibold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FFD8D8]">
                                @forelse($recentBatches as $batch)
                                    <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                        <td class="py-2.5 font-mono font-bold text-[#8E1B5C]">
                                            <a href="{{ route('admin.batches.show', $batch) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                                {{ $batch->batch_no }}
                                            </a>
                                        </td>
                                        <td class="py-2.5 font-mono text-[#64748B]">
                                            {{ $batch->orderItem->order->order_no ?? '-' }}
                                        </td>
                                        <td class="py-2.5 text-right font-semibold text-[#172B4D] whitespace-nowrap">
                                            {{ number_format($batch->target_qty, 0, ',', '.') }} pcs
                                        </td>
                                        <td class="py-2.5 text-center">
                                            <span class="badge {{ $batch->status->badgeClass() }} text-[10px]">
                                                {{ $batch->status->label() }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 text-center">
                                            <a href="{{ route('admin.batches.show', $batch) }}" class="inline-flex items-center justify-center px-2 py-0.5 rounded border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#8E1B5C] hover:bg-[#8E1B5C] hover:text-white hover:border-[#8E1B5C] transition-all cursor-pointer">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-xs text-[#64748B] bg-[#FFFCFB] rounded-lg cursor-default">
                                            <div class="w-8 h-8 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                            </div>
                                            <p class="font-bold text-[#172B4D]">Belum ada Batch Produksi</p>
                                            <p class="text-[11px] text-[#64748B] mt-0.5">Batch terbaru akan tampil di sini.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        {{-- Section F: Recent Business Expenses + Master Data Summary --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Recent Business Expenses (2 Columns) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-3.5 lg:col-span-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#FFD8D8] pb-2.5">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-[#172B4D]">Pengeluaran Bisnis Terbaru</h3>
                            <span class="badge badge-secondary text-[10px]">
                                Bulan Ini: {{ \App\Support\MoneyFormatter::rupiah($monthlyExpenses) }}
                            </span>
                        </div>
                        <p class="text-[11px] text-[#64748B] mt-0.5">Biaya operasional dan marketing yang tercatat.</p>
                    </div>
                    <a href="{{ route('admin.expenses.index') }}" class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] cursor-pointer transition-all duration-200 ease-out">
                        Semua Biaya
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#FFD8D8] text-[#64748B] uppercase tracking-wider text-[10px]">
                                <th class="pb-2 font-semibold">Tanggal</th>
                                <th class="pb-2 font-semibold">Jenis</th>
                                <th class="pb-2 font-semibold">Deskripsi / Kampanye</th>
                                <th class="pb-2 font-semibold">Order</th>
                                <th class="pb-2 font-semibold text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @forelse($recentExpenses as $expense)
                                <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                    <td class="py-2.5 text-[#64748B] whitespace-nowrap">
                                        {{ $expense->expense_date ? $expense->expense_date->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="py-2.5 font-semibold text-[#172B4D]">
                                        <span class="badge badge-secondary text-[10px]">{{ $expense->expense_type->label() }}</span>
                                    </td>
                                    <td class="py-2.5 text-[#172B4D]">
                                        <p class="font-medium truncate max-w-[200px]" title="{{ $expense->description }}">{{ $expense->description }}</p>
                                        @if($expense->campaign_name)
                                            <p class="text-[10px] text-[#64748B]">Kampanye: {{ $expense->campaign_name }}</p>
                                        @endif
                                    </td>
                                    <td class="py-2.5 font-mono text-xs">
                                        @if($expense->order)
                                            <a href="{{ route('admin.orders.show', $expense->order) }}" class="text-[#8E1B5C] hover:text-[#8E1B5C] hover:underline transition-colors cursor-pointer">
                                                {{ $expense->order->order_no }}
                                            </a>
                                        @else
                                            <span class="text-[#64748B] italic">Umum</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-right font-bold text-[#172B4D] whitespace-nowrap">
                                        {{ \App\Support\MoneyFormatter::rupiah($expense->amount) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-xs text-[#64748B] bg-[#FFFCFB] rounded-lg cursor-default">
                                        <div class="w-8 h-8 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                            </svg>
                                        </div>
                                        <p class="font-bold text-[#172B4D]">Belum ada Pengeluaran Bisnis</p>
                                        <p class="text-[11px] text-[#64748B] mt-0.5">Catatan pengeluaran bulan ini akan tampil di sini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Master Data Summary Card (1 Column, 2x2 Interactive Tiles) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-3.5 flex flex-col justify-between">
                <div>
                    <div class="border-b border-[#FFD8D8] pb-2.5">
                        <h3 class="text-sm font-bold text-[#172B4D]">Ringkasan Master Data</h3>
                        <p class="text-[11px] text-[#64748B] mt-0.5">Jumlah master data aktif dalam sistem.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 my-3">
                        <a href="{{ route('admin.customers.index') }}" class="p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] text-center transition-all duration-200 ease-out hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:shadow-xs cursor-pointer block group">
                            <p class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider group-hover:text-[#8E1B5C]">Customer</p>
                            <p class="text-xl font-bold text-[#8E1B5C] mt-0.5">{{ $totalCustomers }}</p>
                            <span class="mt-1.5 inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] font-semibold text-[#8E1B5C] bg-[#FFD8D8] group-hover:bg-[#8E1B5C] group-hover:text-white transition-colors">
                                Lihat
                            </span>
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] text-center transition-all duration-200 ease-out hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:shadow-xs cursor-pointer block group">
                            <p class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider group-hover:text-[#8E1B5C]">Produk</p>
                            <p class="text-xl font-bold text-[#8E1B5C] mt-0.5">{{ $totalProducts }}</p>
                            <span class="mt-1.5 inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] font-semibold text-[#8E1B5C] bg-[#FFD8D8] group-hover:bg-[#8E1B5C] group-hover:text-white transition-colors">
                                Lihat
                            </span>
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] text-center transition-all duration-200 ease-out hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:shadow-xs cursor-pointer block group">
                            <p class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider group-hover:text-[#8E1B5C]">Varian Produk</p>
                            <p class="text-xl font-bold text-[#8E1B5C] mt-0.5">{{ $totalVariants }}</p>
                            <span class="mt-1.5 inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] font-semibold text-[#8E1B5C] bg-[#FFD8D8] group-hover:bg-[#8E1B5C] group-hover:text-white transition-colors">
                                Lihat
                            </span>
                        </a>
                        <div class="p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] text-center cursor-default">
                            <p class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider">Material BOM</p>
                            <p class="text-xl font-bold text-[#8E1B5C] mt-0.5">{{ $totalMaterials }}</p>
                            <span class="mt-1.5 inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] font-medium text-[#64748B] bg-[#FFD8D8]/50">
                                Tersedia
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-2.5 border-t border-[#FFD8D8] flex justify-between items-center text-xs">
                    <span class="text-[#64748B]">Kelola Master Data:</span>
                    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1 font-semibold text-[#8E1B5C] hover:text-[#8E1B5C] hover:underline cursor-pointer transition-colors">
                        Produk & BOM
                    </a>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
