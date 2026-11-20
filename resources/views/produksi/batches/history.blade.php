<x-app-layout pageTitle="Riwayat Produksi Karyawan" breadcrumb="Operasional > Riwayat Produksi">
    <div class="space-y-5 sm:space-y-6">

        {{-- Feature Header --}}
        <x-feature-header
            title="Riwayat Pekerjaan Produksi"
            subtitle="Catatan historis seluruh batch produksi yang pernah Anda kerjakan dan selesaikan."
            icon="archive"
        >
            <x-slot:badge>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                    ARSIP PRIBADI
                </span>
            </x-slot:badge>

            <x-slot:actions>
                <a href="{{ route('produksi.dashboard') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Dashboard Produksi</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Statistics Bar --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Total Batch Selesai</span>
                    <span class="text-2xl font-extrabold text-[#172B4D] font-mono mt-1 block">
                        {{ number_format($totalCompleted) }} <span class="text-xs font-semibold text-[#64748B]">batch</span>
                    </span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Total Output Aktual Anda</span>
                    <span class="text-2xl font-extrabold text-[#8E1B5C] font-mono mt-1 block">
                        {{ number_format($totalProducedQty) }} <span class="text-xs font-semibold text-[#64748B]">pcs</span>
                    </span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Search & Filter --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card">
            <form method="GET" action="{{ route('produksi.history') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari No. Batch, Sales Order, Nama Produk, SKU..."
                           class="w-full h-10 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="h-10 px-5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#68113F] transition cursor-pointer shadow-xs">
                        Cari Riwayat
                    </button>
                    @if(request('search'))
                        <a href="{{ route('produksi.history') }}"
                           class="h-10 px-3 inline-flex items-center justify-center rounded-lg border border-[#FFD8D8] text-xs font-semibold text-[#64748B] hover:text-[#172B4D] hover:bg-[#FFD8D8]/30 transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- History Table --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">No. Batch &amp; SO</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Produk &amp; Varian</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Kronologi Tanggal</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap text-center">Target / Aktual</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap text-center">Hasil QC</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap text-center">Status Akhir</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($batches as $batch)
                            @php
                                $order = $batch->orderItem?->order;
                                $variant = $batch->orderItem?->variant;
                                $prodRes = $batch->productionResult;
                                $qcRes = $batch->qcResult;
                                $assignedDate = $batch->picHistories->first()?->assigned_at ?? $batch->created_at;
                                $actualStartDate = $batch->getActualProductionDate();
                                $completedDate = $qcRes?->created_at ?? $prodRes?->created_at ?? $batch->updated_at;
                            @endphp
                            <tr class="hover:bg-[#FFD8D8]/20 transition">
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <a href="{{ route('produksi.batches.show', $batch) }}" class="font-bold font-mono text-[#8E1B5C] hover:underline block">
                                        {{ $batch->batch_no }}
                                    </a>
                                    <div class="text-[11px] text-[#64748B] mt-0.5">
                                        SO: <span class="font-mono font-semibold text-[#172B4D]">{{ $order?->order_no ?? '-' }}</span>
                                    </div>
                                    <div class="text-[10px] text-[#64748B] truncate max-w-[180px]">
                                        {{ $order?->customer?->customer_name ?? '-' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-semibold text-[#172B4D]">{{ $variant?->product?->product_name ?? '-' }}</div>
                                    <div class="text-[11px] text-[#64748B]">{{ $variant?->variant_name ?? '-' }}</div>
                                    <div class="font-mono text-[10px] text-[#64748B]">SKU: {{ $variant?->sku ?? '-' }}</div>
                                </td>

                                <td class="px-4 py-3.5 whitespace-nowrap text-[11px] text-[#64748B] space-y-0.5">
                                    <div>Order Dibuat: <strong class="text-[#172B4D]">{{ $order?->created_at ? $order->created_at->format('d/m/Y') : '-' }}</strong></div>
                                    <div>Ditugaskan: <strong class="text-[#172B4D]">{{ $assignedDate ? $assignedDate->format('d/m/Y') : '-' }}</strong></div>
                                    <div>Mulai Aktual: <strong class="text-[#172B4D]">{{ $actualStartDate ? \Carbon\Carbon::parse($actualStartDate)->format('d/m/Y') : '-' }}</strong></div>
                                    <div>Selesai: <strong class="text-emerald-700">{{ $completedDate ? $completedDate->format('d/m/Y') : '-' }}</strong></div>
                                </td>

                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <div class="font-bold text-[#172B4D]">{{ number_format($batch->target_qty) }} pcs</div>
                                    <div class="text-[11px] font-bold text-emerald-700 mt-0.5">
                                        Aktual: {{ $batch->total_actual_qty > 0 ? number_format($batch->total_actual_qty) . ' pcs' : ($prodRes ? number_format($prodRes->actual_qty) . ' pcs' : '-') }}
                                    </div>
                                    @if($batch->calculated_shortfall_qty > 0)
                                        <div class="text-[10px] font-semibold text-amber-700 mt-0.5">
                                            Sisa: {{ number_format($batch->calculated_shortfall_qty) }} pcs
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    @if($qcRes)
                                        <div class="inline-flex flex-col gap-0.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Good: {{ number_format($qcRes->good_qty) }}
                                            </span>
                                            @if($qcRes->reject_qty > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                    Reject: {{ number_format($qcRes->reject_qty) }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-[#64748B] text-[11px]">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span class="badge {{ $batch->status->badgeClass() }} text-[11px]">
                                        {{ $batch->status->label() }}
                                    </span>
                                </td>

                                <td class="px-4 py-3.5 text-center align-middle whitespace-nowrap">
                                    <a href="{{ route('produksi.batches.show', $batch) }}"
                                       class="inline-flex items-center justify-center h-8 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition shadow-xs">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-[#64748B]">
                                    Belum ada riwayat pekerjaan yang selesai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
                <div class="p-4 border-t border-[#FFD8D8]">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

