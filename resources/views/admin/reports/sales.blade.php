<x-app-layout pageTitle="Laporan Penjualan" breadcrumb="Keuangan > Laporan Penjualan">
    <div class="max-w-7xl mx-auto space-y-4 sm:space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Laporan Penjualan"
            subtitle="Ringkasan pendapatan transaksi penjualan."
            icon="chart"
        >
            <x-slot:actions>
                <a href="{{ route('admin.reports.sales.export-csv', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Unduh Laporan Penjualan format CSV">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    <span>Unduh CSV</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Filter Form Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card">
            <form method="GET" action="{{ route('admin.reports.sales') }}" class="flex flex-wrap items-end gap-3">
                <div class="w-full sm:w-auto flex-1 min-w-[160px]">
                    <label for="start_date" class="block text-xs font-semibold text-[#172B4D] mb-1">Tanggal Mulai</label>
                    <input type="date"
                           name="start_date"
                           id="start_date"
                           value="{{ $startDate }}"
                           class="w-full h-9 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                </div>

                <div class="w-full sm:w-auto flex-1 min-w-[160px]">
                    <label for="end_date" class="block text-xs font-semibold text-[#172B4D] mb-1">Tanggal Selesai</label>
                    <input type="date"
                           name="end_date"
                           id="end_date"
                           value="{{ $endDate }}"
                           class="w-full h-9 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-white text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                        <span>Terapkan</span>
                    </button>

                    <a href="{{ route('admin.reports.sales') }}"
                       class="inline-flex items-center justify-center h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#64748B] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- ── 2. Top Summary Metrics (3 Restrained Cards) ──────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            {{-- Card 1: Total Pendapatan --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card flex flex-col justify-between space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#64748B] uppercase tracking-wider">Total Pendapatan</span>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-bold font-mono text-[#8E1B5C]">
                        {{ \App\Support\MoneyFormatter::rupiah($report['totalRevenue']) }}
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-0.5">
                        {{ $report['totalQty'] }} pcs dari {{ $report['orders']->count() }} pesanan (DEAL+)
                    </p>
                </div>
            </div>

            {{-- Card 2: Pembayaran Diterima --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card flex flex-col justify-between space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#64748B] uppercase tracking-wider">Pembayaran Diterima</span>
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-bold font-mono text-emerald-700">
                        {{ \App\Support\MoneyFormatter::rupiah($report['totalPaid']) }}
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-0.5">
                        Total pembayaran berstatus CONFIRMED
                    </p>
                </div>
            </div>

            {{-- Card 3: Sisa Tagihan --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card flex flex-col justify-between space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-[#64748B] uppercase tracking-wider">Sisa Tagihan</span>
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-bold font-mono text-amber-700">
                        {{ \App\Support\MoneyFormatter::rupiah($report['totalOutstanding']) }}
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-0.5">
                        Sisa tagihan invoice yang belum terbayar
                    </p>
                </div>
            </div>
        </div>

        {{-- ── 3. Transaction Table ─────────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl shadow-card overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-[#FFD8D8] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-[#172B4D]">Daftar Transaksi Penjualan</h2>
                    <p class="text-xs text-[#64748B]">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-[#FFD8D8] text-[#8E1B5C]">
                    {{ count($report['rows']) }} Transaksi
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[#64748B] font-semibold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">No. Sales Order</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-3 py-3 text-center">Qty</th>
                            <th class="px-4 py-3 text-right">Total Pendapatan</th>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Terbayar</th>
                            <th class="px-4 py-3 text-right">Sisa Tagihan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($report['rows'] as $row)
                            <tr class="hover:bg-[#FFFCFB]/80 transition-colors">
                                <td class="px-4 py-3 text-[#64748B] whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($row['order_date'])->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 font-mono font-semibold text-[#8E1B5C] whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $row['order']) }}" class="hover:underline">
                                        {{ $row['order_no'] }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-[#172B4D] font-medium whitespace-nowrap">
                                    {{ $row['customer_name'] }}
                                </td>
                                <td class="px-3 py-3 text-center font-mono text-[#172B4D] whitespace-nowrap">
                                    {{ $row['total_qty'] }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-[#172B4D] whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($row['revenue']) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($row['has_invoice'])
                                        <a href="{{ route('admin.invoices.show', $row['order']->invoice) }}" class="font-mono font-semibold text-[#8E1B5C] hover:underline">
                                            {{ $row['invoice_no'] }}
                                        </a>
                                    @else
                                        <span class="text-[#64748B] italic text-[11px]">Belum dibuat</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    @if($row['has_invoice'])
                                        <span class="badge {{ $row['order']->invoice->status->badgeClass() }} text-[10px] px-2 py-0.5">
                                            {{ $row['invoice_status'] }}
                                        </span>
                                    @else
                                        <span class="text-[#64748B]">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-emerald-700 whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($row['paid_amount']) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-semibold whitespace-nowrap">
                                    @if($row['outstanding'] !== null)
                                        <span class="{{ bccomp($row['outstanding'], '0.00', 2) > 0 ? 'text-red-700 font-extrabold' : 'text-[#64748B]' }}">
                                            {{ \App\Support\MoneyFormatter::rupiah($row['outstanding']) }}
                                        </span>
                                    @else
                                        <span class="text-[#64748B]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-[#64748B]">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-8 h-8 text-[#FFD8D8]/60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                        </svg>
                                        <p class="text-xs font-medium">Tidak ada transaksi penjualan pada periode {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($report['rows']) > 0)
                        <tfoot class="bg-[#FFFCFB] border-t-2 border-[#FFD8D8] font-bold text-[#172B4D] text-xs">
                            <tr>
                                <td colspan="3" class="px-4 py-3 uppercase tracking-wider text-[11px]">TOTAL</td>
                                <td class="px-3 py-3 text-center font-mono">{{ $report['totalQty'] }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[#8E1B5C]">
                                    {{ \App\Support\MoneyFormatter::rupiah($report['totalRevenue']) }}
                                </td>
                                <td colspan="2"></td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-700">
                                    {{ \App\Support\MoneyFormatter::rupiah($report['totalPaid']) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-amber-700">
                                    {{ \App\Support\MoneyFormatter::rupiah($report['totalOutstanding']) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
