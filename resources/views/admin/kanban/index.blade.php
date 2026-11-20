<x-app-layout pageTitle="Kanban Pesanan" breadcrumb="Penjualan > Kanban Pesanan">
    <div class="space-y-5"
         x-data="{
             csrfToken: '{{ csrf_token() }}',
             draggedOrderId: null,
             dragOverDeal: false,
             isSubmitting: false,
             toast: { show: false, message: '', type: 'success' },

             showToast(msg, type = 'success') {
                 this.toast.message = msg;
                 this.toast.type = type;
                 this.toast.show = true;
                 setTimeout(() => { this.toast.show = false; }, 4000);
             },

             onDragStart(event, orderId) {
                 this.draggedOrderId = orderId;
                 event.dataTransfer.effectAllowed = 'move';
                 event.dataTransfer.setData('text/plain', orderId);
             },

             onDragOverDeal(event) {
                 event.preventDefault();
                 event.dataTransfer.dropEffect = 'move';
                 this.dragOverDeal = true;
             },

             onDragLeaveDeal() {
                 this.dragOverDeal = false;
             },

             async onDropDeal(event) {
                 event.preventDefault();
                 this.dragOverDeal = false;
                 if (!this.draggedOrderId || this.isSubmitting) return;

                 const orderId = this.draggedOrderId;
                 this.draggedOrderId = null;
                 await this.submitConfirmDeal(orderId);
             },

             async confirmDealAccessible(orderId, orderNo) {
                 if (this.isSubmitting) return;
                 if (!confirm(`Konfirmasi pesanan ${orderNo} menjadi DEAL?`)) return;
                 await this.submitConfirmDeal(orderId);
             },

             async submitConfirmDeal(orderId) {
                 this.isSubmitting = true;
                 try {
                     const response = await fetch(`/admin/orders/${orderId}/confirm-deal`, {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'Accept': 'application/json',
                             'X-CSRF-TOKEN': this.csrfToken
                         },
                         body: JSON.stringify({ notes: 'Dikonfirmasi melalui Kanban Board.' })
                     });

                     const data = await response.json();

                     if (response.ok && data.success) {
                         this.showToast(data.message, 'success');
                         // Smooth reload to update card state across columns
                         setTimeout(() => { window.location.reload(); }, 600);
                     } else {
                         this.showToast(data.message || 'Gagal mengubah status pesanan.', 'error');
                     }
                 } catch (e) {
                     this.showToast('Terjadi kesalahan koneksi saat memproses pesanan.', 'error');
                 } finally {
                     this.isSubmitting = false;
                 }
             }
         }">

        {{-- Toast Notification --}}
        <div x-show="toast.show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="fixed top-20 right-6 z-50 p-4 rounded-xl shadow-lg border text-xs font-semibold flex items-center gap-2"
             :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-900 border-emerald-300' : 'bg-red-50 text-red-900 border-red-300'"
             style="display: none;">
            <svg x-show="toast.type === 'success'" class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <svg x-show="toast.type === 'error'" class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span x-text="toast.message"></span>
        </div>

        {{-- Feature Header --}}
        <x-feature-header
            title="Kanban Pesanan"
            subtitle="Pantau progres siklus pesanan."
            icon="order"
        >
            <span class="sr-only">Kanban Board Pesanan</span>
            <x-slot:actions>
                <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Buat Sales Order</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Filter Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
            <form method="GET" action="{{ route('admin.kanban.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                {{-- Search --}}
                <div class="md:col-span-2 relative w-full">
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

                {{-- Invoice Status --}}
                <div>
                    <select name="invoice_status" class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                        <option value="">Semua Status Invoice</option>
                        <option value="NONE" {{ $invoiceStatus === 'NONE' ? 'selected' : '' }}>Belum Ada Invoice</option>
                        <option value="UNPAID" {{ $invoiceStatus === 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                        <option value="PARTIAL" {{ $invoiceStatus === 'PARTIAL' ? 'selected' : '' }}>Partial</option>
                        <option value="PAID" {{ $invoiceStatus === 'PAID' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <input type="date"
                           name="date_from"
                           value="{{ $dateFrom }}"
                           class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer"
                           title="Dari Tanggal Order">
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer flex-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if($search || $dateFrom || $dateTo || $invoiceStatus)
                        <a href="{{ route('admin.kanban.index') }}" class="inline-flex items-center justify-center h-10 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Section C: Horizontal 9-Column Kanban Board (Auto-Height Independent Columns) --}}
        <div class="overflow-x-auto pb-4 pt-1">
            <div class="flex items-start gap-3.5 min-w-[2700px]">

                @foreach($columns as $statusKey => $col)
                    @php
                        $isKomunikasi = ($col['status'] === \App\Enums\OrderStatus::KOMUNIKASI);
                        $isDeal = ($col['status'] === \App\Enums\OrderStatus::DEAL);
                        $isBahan = ($col['status'] === \App\Enums\OrderStatus::BAHAN);
                        $hasCards = $col['cards']->isNotEmpty();
                    @endphp

                    {{-- Column Container (Fixed 290px Width, Auto Height) --}}
                    <div class="w-[290px] min-w-[290px] shrink-0 self-start h-auto flex flex-col bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] shadow-card overflow-hidden transition-all duration-200"
                         data-kanban-column="{{ $statusKey }}"
                         @if($isDeal)
                             @dragover="onDragOverDeal($event)"
                             @dragleave="onDragLeaveDeal()"
                             @drop="onDropDeal($event)"
                             :class="dragOverDeal ? 'ring-2 ring-[#8E1B5C] bg-[#FFD8D8]' : ''"
                         @endif
                    >

                        {{-- Column Header --}}
                        <div class="h-12 px-3.5 border-b border-[#FFD8D8] bg-white flex items-center justify-between shrink-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0 bg-[#8E1B5C]"></span>
                                <h3 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider truncate">{{ $col['label'] }}</h3>
                            </div>
                            <span class="badge {{ $col['badgeClass'] }} text-[11px] font-mono px-2 py-0.5 shrink-0" data-column-count="{{ $statusKey }}">
                                {{ $col['total_count'] }}
                            </span>
                        </div>

                        {{-- Capped Helper Note for SELESAI --}}
                        @if(!empty($col['is_capped']))
                            <div class="px-3 py-1 bg-amber-50 border-b border-amber-200 text-[10px] text-amber-800 font-medium shrink-0">
                                Menampilkan 20 pesanan terbaru
                            </div>
                        @endif

                        {{-- Cards List: Populated or Compact Empty State --}}
                        @if($hasCards)
                            <div class="p-3 space-y-3 h-auto">

                                {{-- Drop Zone Target Visual for DEAL column --}}
                                @if($isDeal)
                                    <div x-show="dragOverDeal"
                                         class="p-3 rounded-lg border-2 border-dashed border-[#8E1B5C] bg-[#FFD8D8]/60 text-center text-xs font-bold text-[#8E1B5C] animate-pulse">
                                        Lepas untuk Konfirmasi DEAL
                                    </div>
                                @endif

                                @foreach($col['cards'] as $card)
                                    @if($card instanceof \App\Models\Production\ProductionBatch)
                                        @php
                                            $batch = $card;
                                            $order = $batch->orderItem?->order;
                                            $variant = $batch->orderItem?->variant;
                                            $product = $variant?->product;
                                            $customer = $order?->customer;
                                            $canCreateBatch = $order ? $batchService->canCreateForOrder($order) : false;
                                            $existingBatchesCount = $order ? $order->items->flatMap->productionBatches->count() : 1;
                                        @endphp

                                        {{-- Production Batch Card (Independent per Batch) --}}
                                        <div class="bg-white border border-[#FFD8D8] rounded-lg p-3 space-y-2 text-xs shadow-xs transition-all duration-200 ease-out hover:bg-[#FFD8D8]/40 hover:border-[#FFD8D8] hover:shadow-sm cursor-default"
                                             data-card-type="batch"
                                             data-batch-no="{{ $batch->batch_no }}"
                                             data-order-no="{{ $order?->order_no }}">

                                            {{-- Batch No & Date --}}
                                            <div class="flex items-start justify-between gap-2">
                                                <a href="{{ route('admin.batches.show', $batch) }}"
                                                   class="font-mono font-bold text-xs text-[#8E1B5C] hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer truncate block"
                                                   title="{{ $batch->batch_no }}">
                                                    {{ $batch->batch_no }}
                                                </a>
                                                <span class="text-[10px] text-[#64748B] font-medium shrink-0">
                                                    {{ $batch->planned_end_date ? $batch->planned_end_date->format('d/m/Y') : ($order?->order_date ? $order->order_date->format('d/m/Y') : '-') }}
                                                </span>
                                            </div>

                                            {{-- Order Reference & Customer --}}
                                            <div>
                                                <div class="flex items-center gap-1.5 text-[11px]">
                                                    @if($order)
                                                        <a href="{{ route('admin.orders.show', $order) }}"
                                                           class="font-mono font-bold text-[#8E1B5C] hover:underline shrink-0"
                                                           title="{{ $order->order_no }}">
                                                            {{ $order->order_no }}
                                                        </a>
                                                    @else
                                                        <span class="font-mono font-bold text-[#8E1B5C]">-</span>
                                                    @endif
                                                    <span class="text-[#64748B]">•</span>
                                                    <p class="font-semibold text-[#172B4D] truncate" title="{{ $customer?->customer_name ?? '-' }}">
                                                        {{ $customer?->customer_name ?? 'Tanpa Nama' }}
                                                    </p>
                                                </div>
                                                <p class="text-[11px] text-[#172B4D] font-medium mt-0.5 truncate" title="{{ $product?->product_name ?? 'Produk' }} - {{ $variant?->variant_name ?? 'Varian' }}">
                                                    {{ $product?->product_name ?? 'Produk' }} - {{ $variant?->variant_name ?? 'Varian' }}
                                                </p>
                                                <p class="text-[10px] text-[#64748B] mt-0.5">
                                                    {{ number_format($batch->target_qty, 0, ',', '.') }} pcs
                                                </p>
                                            </div>

                                            {{-- Badges / Status Indicators --}}
                                            <div class="flex flex-wrap items-center gap-1 pt-0.5">
                                                @if($order?->invoice)
                                                    <span class="badge {{ $order->invoice->status->badgeClass() }} text-[9px] px-1.5 py-0.5">
                                                        Inv: {{ $order->invoice->status->label() }}
                                                    </span>
                                                @endif

                                                <span class="badge {{ $batch->status->badgeClass() }} text-[9px] px-1.5 py-0.5 shrink-0" data-batch-status="{{ $batch->status->value }}">
                                                    {{ $batch->status->label() }}
                                                </span>

                                                {{-- BAHAN Status Indicator / Batch count for test compatibility --}}
                                                @if($isBahan && $existingBatchesCount > 0)
                                                    <span class="badge badge-secondary text-[9px] px-1.5 py-0.5 font-mono">
                                                        {{ $existingBatchesCount }} Batch
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Card Action Footer --}}
                                            <div class="pt-2 border-t border-[#FFD8D8]/70 flex items-center justify-between gap-2 text-[11px]">
                                                {{-- Left Action --}}
                                                <div>
                                                    @if($isBahan && $canCreateBatch && $order)
                                                        <a href="{{ route('admin.orders.batches.create', $order) }}"
                                                           class="inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] font-bold text-[#8E1B5C] bg-[#FFD8D8] border border-[#FFD8D8]/30 hover:bg-[#8E1B5C] hover:text-white transition-colors cursor-pointer">
                                                            + Batch Tambahan
                                                        </a>
                                                    @else
                                                        <span></span>
                                                    @endif
                                                </div>

                                                {{-- Right Link: Canonical Admin Production Batch detail --}}
                                                <a href="{{ route('admin.batches.show', $batch) }}"
                                                   class="inline-flex items-center justify-center px-2 py-0.5 rounded border border-[#FFD8D8] bg-white text-[10px] font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                                    Detail
                                                </a>
                                            </div>
                                        </div>

                                    @else
                                        @php
                                            $order = $card;
                                            $canCreateBatch = $batchService->canCreateForOrder($order);
                                            $existingBatchesCount = $order->items->flatMap->productionBatches->count();
                                        @endphp

                                        {{-- Sales Order Card (Pre-Batch / Post-Production Stages) --}}
                                        <div class="bg-white border border-[#FFD8D8] rounded-lg p-3 space-y-2 text-xs shadow-xs transition-all duration-200 ease-out hover:bg-[#FFD8D8]/40 hover:border-[#FFD8D8] hover:shadow-sm {{ $isKomunikasi ? 'cursor-grab active:cursor-grabbing' : 'cursor-default' }}"
                                             data-card-type="order"
                                             data-order-no="{{ $order->order_no }}"
                                             @if($isKomunikasi)
                                                 draggable="true"
                                                 @dragstart="onDragStart($event, {{ $order->id }})"
                                             @endif
                                        >
                                            {{-- Order No & Date --}}
                                            <div class="flex items-start justify-between gap-2">
                                                <a href="{{ route('admin.orders.show', $order) }}"
                                                   class="font-mono font-bold text-xs text-[#8E1B5C] hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer truncate block"
                                                   title="{{ $order->order_no }}">
                                                    {{ $order->order_no }}
                                                </a>
                                                <span class="text-[10px] text-[#64748B] font-medium shrink-0">
                                                    {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}
                                                </span>
                                            </div>

                                            {{-- Customer Name --}}
                                            <div>
                                                <p class="font-semibold text-[#172B4D] truncate text-[11px]" title="{{ $order->customer->customer_name ?? '-' }}">
                                                    {{ $order->customer->customer_name ?? 'Tanpa Nama' }}
                                                </p>
                                                <p class="text-[10px] text-[#64748B] mt-0.5">
                                                    {{ $order->items_count }} item • {{ number_format($order->total_qty, 0, ',', '.') }} pcs
                                                </p>
                                            </div>

                                            {{-- Badges / Status Indicators --}}
                                            <div class="flex flex-wrap items-center gap-1 pt-0.5">
                                                @if($order->invoice)
                                                    <span class="badge {{ $order->invoice->status->badgeClass() }} text-[9px] px-1.5 py-0.5">
                                                        Inv: {{ $order->invoice->status->label() }}
                                                    </span>
                                                @endif

                                                {{-- PACKING Status Indicator --}}
                                                @if($statusKey === 'PACKING')
                                                    @php
                                                        $fulfillment = $order->getFulfillmentPresentation();
                                                    @endphp
                                                    @if($fulfillment['state'] === 'READY_TO_SHIP')
                                                        <span class="badge badge-success text-[9px] px-1.5 py-0.5">
                                                            Siap Dikirim
                                                        </span>
                                                    @elseif($fulfillment['state'] === 'WAITING_PAYMENT')
                                                        <span class="badge badge-warning text-[9px] px-1.5 py-0.5">
                                                            Menunggu Pelunasan
                                                        </span>
                                                    @else
                                                        <span class="badge badge-primary text-[9px] px-1.5 py-0.5">
                                                            Sedang Dikemas
                                                        </span>
                                                    @endif
                                                @endif

                                                {{-- DEAL Status Indicator --}}
                                                @if($isDeal && $canCreateBatch)
                                                    <span class="badge badge-warning text-[9px] px-1.5 py-0.5">
                                                        Menunggu Batch
                                                    </span>
                                                @endif

                                                {{-- BAHAN Status Indicator --}}
                                                @if($isBahan && $existingBatchesCount > 0)
                                                    <span class="badge badge-secondary text-[9px] px-1.5 py-0.5 font-mono">
                                                        {{ $existingBatchesCount }} Batch
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Card Action Footer --}}
                                            <div class="pt-2 border-t border-[#FFD8D8]/70 flex items-center justify-between gap-2 text-[11px]">
                                                {{-- Left Action (Batch CTA or Confirm Deal) --}}
                                                <div>
                                                    @if($isDeal && $canCreateBatch)
                                                        <a href="{{ route('admin.orders.batches.create', $order) }}"
                                                           class="inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] font-bold text-[#8E1B5C] bg-[#FFD8D8] border border-[#FFD8D8]/30 hover:bg-[#8E1B5C] hover:text-white transition-colors cursor-pointer">
                                                            + Buat Batch
                                                        </a>
                                                    @elseif($isBahan && $canCreateBatch)
                                                        <a href="{{ route('admin.orders.batches.create', $order) }}"
                                                           class="inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] font-bold text-[#8E1B5C] bg-[#FFD8D8] border border-[#FFD8D8]/30 hover:bg-[#8E1B5C] hover:text-white transition-colors cursor-pointer">
                                                            + Buat Batch
                                                        </a>
                                                    @elseif($isKomunikasi)
                                                        <button type="button"
                                                                @click="confirmDealAccessible({{ $order->id }}, '{{ $order->order_no }}')"
                                                                :disabled="isSubmitting"
                                                                class="inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] font-bold text-white bg-[#8E1B5C] hover:bg-[#8E1B5C] transition-colors cursor-pointer shadow-xs"
                                                                title="Konfirmasi status Deal secara langsung">
                                                            Konfirmasi Deal
                                                        </button>
                                                    @else
                                                        <span></span>
                                                    @endif
                                                </div>

                                                {{-- Right Link (No Arrow) --}}
                                                <a href="{{ route('admin.orders.show', $order) }}"
                                                   class="inline-flex items-center justify-center px-2 py-0.5 rounded border border-[#FFD8D8] bg-white text-[10px] font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                                    Detail
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach

                            </div>
                        @else
                            {{-- Compact Empty State (~220px body height -> total ~280px compact square column) --}}
                            <div class="min-h-[220px] flex flex-col items-center justify-center p-4 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                                @if($isDeal)
                                    <div x-show="dragOverDeal"
                                         class="w-full mb-3 p-3 rounded-lg border-2 border-dashed border-[#8E1B5C] bg-[#FFD8D8]/60 text-center text-xs font-bold text-[#8E1B5C] animate-pulse">
                                        Lepas untuk Konfirmasi DEAL
                                    </div>
                                @endif
                                <div class="w-9 h-9 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-2 shadow-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                </div>
                                <p class="font-bold text-[#172B4D] text-xs">Belum ada pesanan</p>
                                <p class="text-[11px] text-[#64748B] mt-0.5">Tidak ada data di tahap ini</p>
                            </div>
                        @endif

                    </div>
                @endforeach

            </div>
        </div>

    </div>
</x-app-layout>
