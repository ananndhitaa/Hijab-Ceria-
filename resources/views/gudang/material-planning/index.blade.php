<x-app-layout pageTitle="Perencanaan Bahan" breadcrumb="Material & Stok > Perencanaan Bahan">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Perencanaan Bahan"
            subtitle="Kebutuhan material per batch."
            icon="mrp"
        >
            <span class="sr-only">Perencanaan Kebutuhan Bahan (MRP)</span>
        </x-feature-header>

        {{-- Compact Filter Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-3 sm:p-4 shadow-card">
            <form method="GET" action="{{ route('gudang.material-planning.index') }}" class="flex flex-wrap items-center gap-2.5">
                {{-- Search Input --}}
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari batch, order, customer, produk..."
                           class="w-full h-9 pl-9 pr-3 text-xs bg-white border border-[#FFD8D8] rounded-xl text-[#172B4D] placeholder-[#64748B]/60 focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                {{-- Batch Status Filter (Default: Semua Batch) --}}
                <div class="w-full sm:w-40">
                    <select name="batch_status"
                            onchange="this.form.submit()"
                            class="w-full h-9 px-3 text-xs bg-white border border-[#FFD8D8] rounded-xl text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition cursor-pointer">
                        <option value="" {{ empty($batchStatus) || $batchStatus === 'ALL' ? 'selected' : '' }}>Semua Batch</option>
                        <option value="AKTIF" {{ $batchStatus === 'AKTIF' ? 'selected' : '' }}>Aktif</option>
                        <option value="SELESAI" {{ $batchStatus === 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>

                {{-- Kesiapan Bahan Filter --}}
                <div class="w-full sm:w-48">
                    <select name="readiness_status"
                            onchange="this.form.submit()"
                            class="w-full h-9 px-3 text-xs bg-white border border-[#FFD8D8] rounded-xl text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition cursor-pointer">
                        <option value="" {{ empty($readinessStatus) ? 'selected' : '' }}>Semua Kesiapan Bahan</option>
                        <option value="SIAP_DIALOKASIKAN" {{ $readinessStatus === 'SIAP_DIALOKASIKAN' ? 'selected' : '' }}>Siap Dialokasikan</option>
                        <option value="TERALOKASI" {{ $readinessStatus === 'TERALOKASI' ? 'selected' : '' }}>Teralokasi</option>
                        <option value="PERLU_PEMBELIAN" {{ $readinessStatus === 'PERLU_PEMBELIAN' ? 'selected' : '' }}>Perlu Pembelian</option>
                    </select>
                </div>

                {{-- Filter & Reset Buttons --}}
                <button type="submit"
                        class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#8E1B5C] text-white hover:bg-[#8E1B5C] text-xs font-semibold shadow-xs transition cursor-pointer">
                    Filter
                </button>
                @if($search || (!empty($batchStatus) && $batchStatus !== 'ALL') || !empty($readinessStatus))
                    <a href="{{ route('gudang.material-planning.index') }}"
                       class="inline-flex items-center justify-center h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition cursor-pointer">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Batches Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#FFD8D8] flex items-center justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wider text-[#172B4D]">
                    Batch Produksi
                </h2>
                <span class="text-xs font-medium text-[#64748B]">
                    {{ $batches->total() }} batch
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#172B4D] border-collapse">
                    <thead class="bg-[#FFFCFB] text-[11px] font-semibold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8]">
                        <tr>
                            <th class="py-3 px-4">Batch</th>
                            <th class="py-3 px-4">Pesanan</th>
                            <th class="py-3 px-4">Produk / Varian</th>
                            <th class="py-3 px-4 text-right">Target Qty</th>
                            <th class="py-3 px-4 text-center">Kesiapan Bahan</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/60">
                        @forelse($batches as $batch)
                            @php
                                $readiness = $batch->material_readiness;
                                $order = $batch->orderItem?->order;
                                $variant = $batch->orderItem?->variant;
                            @endphp
                            <tr class="hover:bg-[#FFFCFB]/50 transition-colors">
                                <td class="py-3 px-4">
                                    <a href="{{ route('gudang.material-planning.show', $batch) }}"
                                       class="font-mono font-medium text-xs text-[#8E1B5C] hover:text-[#8E1B5C] transition-colors block leading-tight">
                                        {{ $batch->batch_no }}
                                    </a>
                                    @if($batch->status !== \App\Enums\BatchStatus::SELESAI && $batch->status !== \App\Enums\BatchStatus::BAHAN)
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] font-medium bg-[#FFD8D8] text-[#8E1B5C]">{{ $batch->status->label() }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-xs text-[#172B4D] block leading-tight">{{ $order?->order_no ?? '—' }}</span>
                                    <span class="text-[11px] text-[#64748B] block truncate mt-0.5">{{ $order?->customer?->customer_name ?? '—' }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-xs text-[#172B4D] block leading-tight">{{ $variant?->product?->product_name ?? '—' }}</span>
                                    <span class="text-[11px] text-[#64748B] block truncate mt-0.5">{{ $variant?->variant_name ?? '—' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums font-medium text-xs text-[#172B4D]">
                                    {{ number_format((float)$batch->target_qty, 0, ',', '.') }} pcs
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($batch->status === \App\Enums\BatchStatus::SELESAI)
                                        <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-emerald-50 text-emerald-700 border border-emerald-200" title="Selesai">
                                            Selesai
                                        </span>
                                    @elseif($readiness === \App\Enums\MaterialReadiness::TERALOKASI)
                                        <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-emerald-50 text-emerald-700 border border-emerald-200" data-status="TERALOKASI" title="TERALOKASI">
                                            <span class="sr-only">TERALOKASI</span>
                                            <span>Teralokasi</span>
                                        </span>
                                    @elseif($readiness === \App\Enums\MaterialReadiness::SIAP_DIALOKASIKAN)
                                        <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]" data-status="SIAP DIALOKASIKAN" title="SIAP DIALOKASIKAN">
                                            <span class="sr-only">SIAP DIALOKASIKAN</span>
                                            <span>Siap Dialokasikan</span>
                                        </span>
                                    @elseif($readiness === \App\Enums\MaterialReadiness::PERLU_PEMBELIAN)
                                        <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-amber-50 text-amber-700 border border-amber-200" data-status="PERLU PEMBELIAN" title="PERLU PEMBELIAN">
                                            <span class="sr-only">PERLU PEMBELIAN</span>
                                            <span>Perlu Pembelian</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-slate-50 text-slate-700 border border-slate-200" data-status="PERENCANAAN BELUM TERSEDIA">
                                            <span class="sr-only">PERENCANAAN BELUM TERSEDIA</span>
                                            <span>Belum Tersedia</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('gudang.material-planning.show', $batch) }}"
                                       class="inline-flex items-center justify-center h-8 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs font-medium text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition shadow-xs"
                                       title="Lihat Detail Analisis MRP">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-xs text-[#64748B]">
                                    {{ $search ? 'Data tidak ditemukan.' : 'Tidak ada batch yang perlu ditangani.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
                <div class="p-4 border-t border-[#FFD8D8] bg-[#FFFCFB]/50">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
