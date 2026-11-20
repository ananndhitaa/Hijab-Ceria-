<x-app-layout pageTitle="Detail Performa Produksi: {{ $user->name }}" breadcrumb="Detail Performa Produksi">
    <div class="max-w-7xl mx-auto space-y-4 sm:space-y-5">

        {{-- Feature Header NAYARA --}}
        <x-feature-header
            title="Detail Performa Produksi"
            subtitle="{{ $user->name }} · {{ $user->email }}"
            icon="chart"
            badge="Performa Produksi • Read-Only"
        >
            <span class="sr-only">Detail Performa Produksi</span>
            <x-slot:actions>
                <a href="{{ route('owner.employee-performance.index', ['tab' => 'production', 'preset' => $preset, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Performa Karyawan</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Compact Period Filter Bar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-3 sm:p-4 shadow-card">
            <form method="GET" action="{{ route('owner.employee-performance.production-detail', $user) }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-bold text-[#64748B] uppercase tracking-wider mr-1">Periode Selesai:</span>

                    <a href="{{ route('owner.employee-performance.production-detail', ['user' => $user->id, 'preset' => 'today']) }}"
                       class="px-2.5 py-1 rounded-xl text-xs font-semibold transition {{ $preset === 'today' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('owner.employee-performance.production-detail', ['user' => $user->id, 'preset' => 'this_month']) }}"
                       class="px-2.5 py-1 rounded-xl text-xs font-semibold transition {{ $preset === 'this_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Bulan Ini
                    </a>
                    <a href="{{ route('owner.employee-performance.production-detail', ['user' => $user->id, 'preset' => 'last_month']) }}"
                       class="px-2.5 py-1 rounded-xl text-xs font-semibold transition {{ $preset === 'last_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Bulan Lalu
                    </a>
                    <a href="{{ route('owner.employee-performance.production-detail', ['user' => $user->id, 'preset' => 'all']) }}"
                       class="px-2.5 py-1 rounded-xl text-xs font-semibold transition {{ $preset === 'all' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Semua Waktu
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="preset" value="custom">
                    <input type="date"
                           name="start_date"
                           value="{{ $startDate }}"
                           class="h-8 px-2.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                    <span class="text-xs text-[#64748B]">s/d</span>
                    <input type="date"
                           name="end_date"
                           value="{{ $endDate }}"
                           class="h-8 px-2.5 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                    <button type="submit"
                            class="inline-flex items-center justify-center h-8 px-3 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition cursor-pointer shadow-xs">
                        Terapkan
                    </button>
                </div>
            </form>
        </div>

        {{-- Compact KPI Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
            {{-- Total Batch --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-3.5 sm:p-4 shadow-card">
                <span class="text-xs font-semibold text-[#64748B]">Batch Selesai</span>
                <p class="text-2xl font-bold text-[#172B4D] mt-1">{{ number_format($detail['total_batches'], 0, ',', '.') }}</p>
                <span class="text-[11px] text-[#64748B]">Diselesaikan karyawan</span>
            </div>

            {{-- Tepat Waktu --}}
            <div class="bg-white border border-emerald-200/80 rounded-2xl p-3.5 sm:p-4 shadow-card bg-emerald-50/20">
                <span class="text-xs font-semibold text-emerald-800">Tepat Waktu / Lebih Cepat</span>
                <p class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($detail['on_time_batches'], 0, ',', '.') }}</p>
                <span class="text-[11px] text-emerald-600">Dari {{ $detail['batches_with_target'] }} batch bertarget</span>
            </div>

            {{-- Ketepatan Waktu --}}
            <div class="bg-white border border-[#8E1B5C]/20 rounded-2xl p-3.5 sm:p-4 shadow-card bg-[#8E1B5C]/5">
                <span class="text-xs font-semibold text-[#8E1B5C]">Ketepatan Waktu</span>
                <p class="text-2xl font-bold text-[#8E1B5C] mt-1">{{ $detail['on_time_percentage_text'] }}</p>
                <span class="text-[11px] text-[#64748B]">Tepat waktu &divide; Batch bertarget</span>
            </div>

            {{-- Total Hasil Produksi --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-3.5 sm:p-4 shadow-card">
                <span class="text-xs font-semibold text-[#64748B]">Hasil vs Target</span>
                <p class="text-lg sm:text-xl font-bold text-[#172B4D] mt-1">
                    {{ number_format($detail['total_actual_qty'], 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B]">/ {{ number_format($detail['total_target_qty'], 0, ',', '.') }} pcs</span>
                </p>
                <span class="text-[11px] text-[#64748B]">Total pcs aktual vs target</span>
            </div>

            {{-- Ketepatan Jumlah --}}
            <div class="bg-white border border-indigo-200/80 rounded-2xl p-3.5 sm:p-4 shadow-card bg-indigo-50/20 col-span-2 md:col-span-1">
                <span class="text-xs font-semibold text-indigo-800">Ketepatan Jumlah</span>
                <p class="text-2xl font-bold text-indigo-700 mt-1">{{ $detail['qty_achievement_percentage_text'] }}</p>
                <span class="text-[11px] text-indigo-600">&Sigma; Aktual &divide; &Sigma; Target</span>
            </div>
        </div>

        {{-- Completed Batches Table --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#FFD8D8] bg-[#FFFCFB] flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-[#172B4D]">Daftar Batch Produksi yang Dikerjakan</h3>
                    <p class="text-xs text-[#64748B] mt-0.5">{{ $detail['total_batches'] }} batch selesai pada periode ini.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#172B4D]">
                    <thead class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3">No. Batch &amp; Pesanan</th>
                            <th class="px-4 py-3">Produk &amp; Varian</th>
                            <th class="px-4 py-3 text-center">Target Selesai</th>
                            <th class="px-4 py-3 text-center">Selesai Aktual</th>
                            <th class="px-4 py-3 text-center">Ketepatan Waktu</th>
                            <th class="px-4 py-3 text-right">Target Qty</th>
                            <th class="px-4 py-3 text-right">Hasil Aktual</th>
                            <th class="px-5 py-3 text-center">Pencapaian Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($detail['batches'] as $batch)
                            @php
                                $item = $batch->orderItem;
                                $order = $item?->order;
                                $res = $batch->productionResult;
                                $actualQty = $res ? (float) $res->actual_qty : 0.0;
                                $targetQty = (float) $batch->target_qty;
                                $achievement = $batch->production_quantity_achievement_percent;
                            @endphp
                            <tr class="hover:bg-[#FFFCFB]/60 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-mono font-bold text-[#8E1B5C]">{{ $batch->batch_no }}</div>
                                    <div class="text-[11px] text-[#64748B] flex items-center gap-1 mt-0.5">
                                        <span>Order: {{ $order?->order_no ?? '-' }}</span>
                                        <span>&middot;</span>
                                        <span>{{ $order?->customer?->customer_name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-[#172B4D]">{{ $item?->variant?->product?->product_name ?? '-' }}</p>
                                    <p class="text-[11px] text-[#64748B]">Varian: {{ $item?->variant?->variant_name ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-center text-[#64748B]">
                                    {{ $batch->planned_end_date ? $batch->planned_end_date->isoFormat('D MMM Y') : '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-medium text-[#172B4D]">
                                    {{ $res?->result_date ? $res->result_date->isoFormat('D MMM Y') : '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    @if($batch->time_performance_status !== null)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $batch->time_performance_badge_class }}">
                                            {{ $batch->time_performance_label }}
                                        </span>
                                    @else
                                        <span class="text-[#64748B] italic text-[11px]">Belum Ada Data</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right font-medium text-[#64748B]">
                                    {{ number_format($targetQty, 0, ',', '.') }} pcs
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-[#172B4D]">
                                    {{ number_format($actualQty, 0, ',', '.') }} pcs
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if($achievement !== null)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $achievement >= 100 ? 'bg-emerald-100 text-emerald-800' : ($achievement >= 80 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                            {{ number_format($achievement, 1, ',', '.') }}%
                                        </span>
                                    @else
                                        <span class="text-[#64748B] italic text-[11px]">Belum Ada Data</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-8 text-center text-xs text-[#64748B]">
                                    Belum ada batch produksi yang diselesaikan oleh karyawan ini pada periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
