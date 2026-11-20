<x-app-layout pageTitle="Pembayaran Supplier" breadcrumb="Keuangan > Pembayaran Supplier">
    <div class="space-y-4 sm:space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Pembayaran Supplier"
            subtitle="Penyelesaian tagihan pengadaan bahan."
            icon="finance"
        >
            <x-slot:actions>
                <a href="{{ route('admin.financial-records.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 text-[#64748B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Catatan Keuangan</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-4 py-3 rounded-xl flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs px-4 py-3 rounded-xl flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Top Compact Summary (4 Cards) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- 1. Kas Saat Ini --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Kas Saat Ini</span>
                <div class="text-lg sm:text-xl font-bold font-mono text-[#8E1B5C] mt-1">
                    @if($kasTersedia !== null)
                        Rp {{ number_format((float) $kasTersedia, 0, ',', '.') }}
                    @else
                        <span class="text-gray-400 font-sans text-sm font-normal">Belum tersedia</span>
                    @endif
                </div>
                <p class="text-[10px] text-[#64748B] mt-1">Saldo likuid operasional</p>
            </div>

            {{-- 2. PO Menunggu Bayar --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">PO Menunggu Bayar</span>
                <div class="text-lg sm:text-xl font-bold font-mono text-[#172B4D] mt-1">
                    {{ $unpaidPurchases->count() }} PO
                </div>
                <p class="text-[10px] text-[#64748B] mt-1">Telah disetujui Owner</p>
            </div>

            {{-- 3. Total Kewajiban --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Total Kewajiban</span>
                <div class="text-lg sm:text-xl font-bold font-mono text-rose-600 mt-1">
                    Rp {{ number_format((float) $procurementSummary['po_outstanding'], 0, ',', '.') }}
                </div>
                <p class="text-[10px] text-[#64748B] mt-1">Sisa tagihan seluruh PO</p>
            </div>

            {{-- 4. Sisa Kas Jika Dilunasi & Status --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider">Sisa Kas Jika Dilunasi</span>
                    @if($procurementSummary['sufficiency_status'] === 'CUKUP')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Cukup
                        </span>
                    @elseif($procurementSummary['sufficiency_status'] === 'TIDAK_CUKUP')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            Tidak Cukup
                        </span>
                    @else
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            Belum Dihitung
                        </span>
                    @endif
                </div>
                <div class="text-lg sm:text-xl font-bold font-mono {{ ($procurementSummary['projected_cash'] ?? 0) < 0 ? 'text-rose-600' : 'text-emerald-600' }} mt-1">
                    @if($procurementSummary['projected_cash'] !== null)
                        Rp {{ number_format((float) $procurementSummary['projected_cash'], 0, ',', '.') }}
                    @else
                        —
                    @endif
                </div>
                <p class="text-[10px] text-[#64748B] mt-1">
                    @if($procurementSummary['sufficiency_status'] === 'TIDAK_CUKUP' && !empty($procurementSummary['shortage']))
                        Kekurangan: Rp {{ number_format((float) $procurementSummary['shortage'], 0, ',', '.') }}
                    @else
                        Proyeksi kas setelah pelunasan
                    @endif
                </p>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="p-4 border-b border-[#FFD8D8] flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-xs sm:text-sm font-bold text-[#172B4D]">Daftar PO Menunggu Pembayaran</h2>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded {{ $unpaidPurchases->isNotEmpty() ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-gray-50 text-gray-600 border border-gray-200' }}">
                        {{ $unpaidPurchases->count() }}
                    </span>
                </div>
            </div>

            @if($unpaidPurchases->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#FFD8D8] bg-[#FFFCFB] text-[#64748B] font-semibold uppercase tracking-wider text-[10px]">
                                <th class="py-2.5 px-3">No. PO</th>
                                <th class="py-2.5 px-3">Supplier</th>
                                <th class="py-2.5 px-3">Tanggal</th>
                                <th class="py-2.5 px-3 text-right">Total</th>
                                <th class="py-2.5 px-3 text-right">Terbayar</th>
                                <th class="py-2.5 px-3 text-right">Sisa</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                                <th class="py-2.5 px-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]/60">
                            @foreach($unpaidPurchases as $unpaidPo)
                                <tr class="hover:bg-[#FFFCFB]/60 transition-colors">
                                    <td class="py-2.5 px-3 font-mono font-bold text-[#8E1B5C]">
                                        <a href="{{ route('gudang.purchases.show', $unpaidPo['purchase_id']) }}" class="hover:underline">
                                            {{ $unpaidPo['purchase_no'] }}
                                        </a>
                                    </td>
                                    <td class="py-2.5 px-3 font-medium text-[#172B4D]">{{ $unpaidPo['supplier_name'] }}</td>
                                    <td class="py-2.5 px-3 text-[#64748B]">{{ $unpaidPo['purchase_date'] }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono font-semibold text-[#172B4D]">
                                        Rp {{ number_format((float) $unpaidPo['total_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-emerald-600 font-semibold">
                                        Rp {{ number_format((float) $unpaidPo['paid_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-rose-600 font-bold">
                                        Rp {{ number_format((float) $unpaidPo['outstanding_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Disetujui
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <a href="{{ route('admin.purchases.payments.create', $unpaidPo['purchase_id']) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#8E1B5C] text-white text-[11px] font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs whitespace-nowrap">
                                            <span>Bayar</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center text-xs text-[#64748B]">
                    <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="font-medium text-[#172B4D]">Tidak ada PO yang menunggu pembayaran</p>
                    <p class="text-[11px] text-[#64748B] mt-0.5">Semua Purchase Order yang disetujui Owner telah lunas atau belum ada pengajuan baru.</p>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
