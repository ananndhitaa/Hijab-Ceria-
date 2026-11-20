<x-app-layout pageTitle="Catatan Keuangan" breadcrumb="Keuangan > Catatan Keuangan">
    <div class="space-y-4 sm:space-y-5" x-data="{ selectedRecord: null, showDetailModal: false }">

        {{-- Feature Header --}}
        <x-feature-header
            title="Catatan Keuangan"
            subtitle="Buku arus kas dan transaksi operasional."
            icon="finance"
        >
            <x-slot:actions>
                <a href="{{ route('admin.opening-cash.edit') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 sm:h-10 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs cursor-pointer"
                   title="Atur Saldo Awal Kas">
                    <span class="text-[11px] text-[#64748B]">Saldo Awal:</span>
                    <span class="font-mono font-bold text-[#8E1B5C]">
                        @if($cashPosition['has_opening_balance'])
                            Rp {{ number_format((float) $cashPosition['opening_balance'], 0, ',', '.') }}
                        @else
                            Belum diatur
                        @endif
                    </span>
                    <span class="text-[10px] text-[#8E1B5C] font-bold underline ml-0.5">Atur</span>
                </a>
                <a href="{{ route('admin.incomes.create') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 sm:h-10 px-3.5 sm:px-4 rounded-lg bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Pemasukan</span>
                </a>
                <a href="{{ route('admin.expenses.create', ['redirect_to' => 'financial-records']) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 sm:h-10 px-3.5 sm:px-4 rounded-lg bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Beban Usaha</span>
                </a>
            </x-slot:actions>
        </x-feature-header>


        {{-- Top Summary: 4 Cash Flow Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- 1. Kas Saat Ini --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Kas Saat Ini</span>
                <div class="text-lg sm:text-xl font-bold font-mono text-[#8E1B5C] mt-1">
                    @if($cashPosition['has_opening_balance'] && $cashPosition['kas_tersedia'] !== null)
                        Rp {{ number_format((float) $cashPosition['kas_tersedia'], 0, ',', '.') }}
                    @else
                        Belum tersedia
                    @endif
                </div>
                <p class="text-[10px] text-[#64748B] mt-1">
                    @if($cashPosition['has_opening_balance'] && !empty($cashPosition['opening_record']))
                        Per tgl: {{ $cashPosition['opening_record']->opening_date?->format('d/m/Y') }}
                    @else
                        Saldo Awal Kas belum ditentukan.
                    @endif
                </p>
            </div>

            {{-- 2. Uang Masuk --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Uang Masuk</span>
                <div class="text-lg sm:text-xl font-bold font-mono text-emerald-600 mt-1">
                    Rp {{ number_format((float) $summary['total_masuk'], 0, ',', '.') }}
                </div>
                <p class="text-[10px] text-[#64748B] mt-1">Pembayaran customer & pemasukan kas</p>
            </div>

            {{-- 3. Uang Keluar --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Uang Keluar</span>
                <div class="text-lg sm:text-xl font-bold font-mono text-rose-600 mt-1">
                    Rp {{ number_format((float) $summary['total_keluar'], 0, ',', '.') }}
                </div>
                <p class="text-[10px] text-[#64748B] mt-1">Beban usaha & supplier</p>
            </div>

            {{-- 4. Arus Bersih --}}
            @php
                $arusVal = (float) $summary['arus_bersih'];
                $arusColor = $arusVal >= 0 ? 'text-emerald-700' : 'text-rose-600';
            @endphp
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Arus Bersih</span>
                <div class="text-lg sm:text-xl font-bold font-mono {{ $arusColor }} mt-1">
                    {{ $arusVal < 0 ? '-' : '' }}Rp {{ number_format(abs($arusVal), 0, ',', '.') }}
                </div>
                <p class="text-[10px] text-[#64748B] mt-1">Arus Bersih Operasional</p>
            </div>
        </div>

        {{-- PO Menunggu Pembayaran (Admin Action Queue) --}}
        @if(!empty($procurementSummary['unpaid_purchases']) && $procurementSummary['unpaid_purchases']->isNotEmpty())
            <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
                <x-nayara-section-header
                    title="PO Menunggu Pembayaran"
                    subtitle="Daftar Purchase Order disetujui yang membutuhkan realisasi pembayaran kas."
                >
                    <x-slot:badge>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            {{ $procurementSummary['unpaid_purchases']->count() }} Menunggu
                        </span>
                    </x-slot:badge>
                </x-nayara-section-header>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#FFD8D8] bg-[#FFD8D8] text-[#64748B] font-semibold uppercase tracking-[0.02em] text-[11.5px] sm:text-[12px]">
                                <th class="py-2.5 sm:py-3 px-3">No. PO</th>
                                <th class="py-2.5 sm:py-3 px-3">Supplier</th>
                                <th class="py-2.5 sm:py-3 px-3">Tanggal</th>
                                <th class="py-2.5 sm:py-3 px-3 text-right">Total PO</th>
                                <th class="py-2.5 sm:py-3 px-3 text-right">Terbayar</th>
                                <th class="py-2.5 sm:py-3 px-3 text-right">Sisa Tagihan</th>
                                <th class="py-2.5 sm:py-3 px-3 text-center">Status</th>
                                <th class="py-2.5 sm:py-3 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]/60">
                            @foreach($procurementSummary['unpaid_purchases'] as $unpaidPo)
                                <tr class="hover:bg-[#FFFCFB]/60 transition-colors">
                                    <td class="py-2.5 px-3 font-mono font-bold text-[#8E1B5C] whitespace-nowrap">
                                        <a href="{{ route('gudang.purchases.show', $unpaidPo['purchase_id']) }}" class="hover:underline">
                                            {{ $unpaidPo['purchase_no'] }}
                                        </a>
                                    </td>
                                    <td class="py-2.5 px-3 font-medium text-[#172B4D] whitespace-nowrap">{{ $unpaidPo['supplier_name'] }}</td>
                                    <td class="py-2.5 px-3 text-[#64748B] whitespace-nowrap">{{ $unpaidPo['purchase_date'] }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono font-semibold text-[#172B4D] whitespace-nowrap">
                                        Rp {{ number_format((float) $unpaidPo['total_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-emerald-600 font-semibold whitespace-nowrap">
                                        Rp {{ number_format((float) $unpaidPo['paid_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-rose-600 font-bold whitespace-nowrap">
                                        Rp {{ number_format((float) $unpaidPo['outstanding_amount'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Disetujui
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                        <a href="{{ route('admin.purchases.payments.create', $unpaidPo['purchase_id']) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#8E1B5C] text-white text-[11px] font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs whitespace-nowrap">
                                            <span>Catat Pembayaran</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Compact Filter Bar --}}
        @php
            $isCustomRange = !empty($filters['start_date']) && !empty($filters['end_date'])
                && !($filters['start_date'] === date('Y-m-d') && $filters['end_date'] === date('Y-m-d'))
                && !($filters['start_date'] === date('Y-m-01') && $filters['end_date'] === date('Y-m-t'));
            $isToday = $filters['start_date'] === date('Y-m-d') && $filters['end_date'] === date('Y-m-d');
            $isThisMonth = $filters['start_date'] === date('Y-m-01') && $filters['end_date'] === date('Y-m-t');
            $isAll = empty($filters['start_date']) && empty($filters['end_date']);
            $currentFlow = $filters['flow'] ?? 'all';
            $currentType = $filters['type'] ?? 'all';
            $searchVal = $filters['search'] ?? ($filters['q'] ?? '');
        @endphp
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-3 sm:p-4 shadow-card space-y-3"
             x-data="{
                showCustomRange: {{ $isCustomRange ? 'true' : 'false' }},
                flow: '{{ $currentFlow }}',
                inflowTypes: [
                    'customer_dp', 'dp',
                    'customer_settlement', 'pelunasan', 'settlement',
                    'pemasukan_lain', 'business_income_other',
                    'modal_tambahan', 'capital_injection',
                    'pendapatan_non_penjualan', 'non_sales_income'
                ],
                outflowTypes: [
                    'pembayaran_supplier', 'supplier_payment', 'purchase_payment',
                    'operasional', 'operating',
                    'marketing',
                    'bunga', 'interest',
                    'pajak', 'tax',
                    'penyusutan', 'depreciation', 'depresiasi',
                    'amortisasi', 'amortization'
                ],
                typeAliases: {
                    'purchase_payment': 'pembayaran_supplier',
                    'supplier_payment': 'pembayaran_supplier',
                    'dp': 'customer_dp',
                    'pelunasan': 'customer_settlement',
                    'settlement': 'customer_settlement',
                    'operating': 'operasional',
                    'depresiasi': 'penyusutan',
                    'depreciation': 'penyusutan',
                    'amortization': 'amortisasi',
                    'interest': 'bunga',
                    'tax': 'pajak',
                    'capital_injection': 'modal_tambahan',
                    'non_sales_income': 'pendapatan_non_penjualan',
                    'business_income_other': 'pemasukan_lain'
                },
                inOpts: [
                    { val: 'all', txt: 'Semua Jenis Pemasukan' },
                    { val: 'customer_dp', txt: 'Uang Muka (DP)' },
                    { val: 'customer_settlement', txt: 'Pelunasan' },
                    { val: 'pemasukan_lain', txt: 'Pemasukan Lain' },
                    { val: 'modal_tambahan', txt: 'Modal Tambahan' },
                    { val: 'pendapatan_non_penjualan', txt: 'Pendapatan Non-Penjualan' }
                ],
                outOpts: [
                    { val: 'all', txt: 'Semua Jenis Pengeluaran' },
                    { val: 'pembayaran_supplier', txt: 'Pembayaran Supplier' },
                    { val: 'operasional', txt: 'Operasional Usaha' },
                    { val: 'marketing', txt: 'Marketing / Pemasaran' },
                    { val: 'bunga', txt: 'Bunga' },
                    { val: 'pajak', txt: 'Pajak' },
                    { val: 'penyusutan', txt: 'Penyusutan' },
                    { val: 'amortisasi', txt: 'Amortisasi' }
                ],
                renderTypeOptions(preferredVal) {
                    const select = document.getElementById('transaction_type_select');
                    if (!select) return;
                    const canonical = this.typeAliases[preferredVal] || preferredVal;
                    const fVal = this.flow;

                    select.innerHTML = '';

                    if (fVal === 'in') {
                        this.inOpts.forEach(o => {
                            const opt = document.createElement('option');
                            opt.value = o.val;
                            opt.textContent = o.txt;
                            select.appendChild(opt);
                        });
                        const valid = this.inOpts.map(o => o.val);
                        select.value = valid.includes(canonical) ? canonical : 'all';
                    } else if (fVal === 'out') {
                        this.outOpts.forEach(o => {
                            const opt = document.createElement('option');
                            opt.value = o.val;
                            opt.textContent = o.txt;
                            select.appendChild(opt);
                        });
                        const valid = this.outOpts.map(o => o.val);
                        select.value = valid.includes(canonical) ? canonical : 'all';
                    } else {
                        const allOpt = document.createElement('option');
                        allOpt.value = 'all';
                        allOpt.textContent = 'Semua Jenis';
                        select.appendChild(allOpt);

                        const grpIn = document.createElement('optgroup');
                        grpIn.label = 'PEMASUKAN';
                        this.inOpts.slice(1).forEach(o => {
                            const opt = document.createElement('option');
                            opt.value = o.val;
                            opt.textContent = o.txt;
                            grpIn.appendChild(opt);
                        });
                        select.appendChild(grpIn);

                        const grpOut = document.createElement('optgroup');
                        grpOut.label = 'PENGELUARAN';
                        this.outOpts.slice(1).forEach(o => {
                            const opt = document.createElement('option');
                            opt.value = o.val;
                            opt.textContent = o.txt;
                            grpOut.appendChild(opt);
                        });
                        select.appendChild(grpOut);

                        const valid = ['all', ...this.inOpts.map(o => o.val), ...this.outOpts.map(o => o.val)];
                        select.value = valid.includes(canonical) ? canonical : 'all';
                    }
                },
                onFlowChange() {
                    const select = document.getElementById('transaction_type_select');
                    if (!select) return;
                    const curVal = select.value;
                    const canonical = this.typeAliases[curVal] || curVal;

                    if (this.flow === 'in' && this.outflowTypes.includes(canonical)) {
                        this.renderTypeOptions('all');
                    } else if (this.flow === 'out' && this.inflowTypes.includes(canonical)) {
                        this.renderTypeOptions('all');
                    } else {
                        this.renderTypeOptions(canonical);
                    }
                },
                onTypeChange() {
                    const select = document.getElementById('transaction_type_select');
                    if (!select) return;
                    const val = select.value;
                    const canonical = this.typeAliases[val] || val;

                    if (val === 'all') {
                        return;
                    }

                    if (this.inflowTypes.includes(canonical)) {
                        if (this.flow !== 'in') {
                            this.flow = 'in';
                            this.renderTypeOptions(canonical);
                        }
                    } else if (this.outflowTypes.includes(canonical)) {
                        if (this.flow !== 'out') {
                            this.flow = 'out';
                            this.renderTypeOptions(canonical);
                        }
                    }
                },
                updateTypeOptions() {
                    this.onFlowChange();
                }
             }">
            <form method="GET" action="{{ route('admin.financial-records.index') }}" class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2.5">
                    {{-- Quick Presets --}}
                    <div class="flex flex-wrap items-center gap-1.5">
                        <a href="{{ route('admin.financial-records.index', array_filter(['start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'flow' => $currentFlow, 'type' => $currentType, 'search' => $searchVal])) }}"
                           class="px-2.5 py-1 rounded-md text-xs font-semibold border transition-colors {{ $isToday ? 'bg-[#8E1B5C] text-white border-[#8E1B5C]' : 'bg-[#FFFCFB] text-[#172B4D] border-[#FFD8D8] hover:bg-[#FFD8D8]' }}">
                            Hari Ini
                        </a>
                        <a href="{{ route('admin.financial-records.index', array_filter(['start_date' => date('Y-m-01'), 'end_date' => date('Y-m-t'), 'flow' => $currentFlow, 'type' => $currentType, 'search' => $searchVal])) }}"
                           class="px-2.5 py-1 rounded-md text-xs font-semibold border transition-colors {{ $isThisMonth ? 'bg-[#8E1B5C] text-white border-[#8E1B5C]' : 'bg-[#FFFCFB] text-[#172B4D] border-[#FFD8D8] hover:bg-[#FFD8D8]' }}">
                            Bulan Ini
                        </a>
                        <a href="{{ route('admin.financial-records.index', array_filter(['flow' => $currentFlow, 'type' => $currentType, 'search' => $searchVal])) }}"
                           class="px-2.5 py-1 rounded-md text-xs font-semibold border transition-colors {{ $isAll ? 'bg-[#8E1B5C] text-white border-[#8E1B5C]' : 'bg-[#FFFCFB] text-[#172B4D] border-[#FFD8D8] hover:bg-[#FFD8D8]' }}">
                            Semua
                        </a>
                        <button type="button"
                                @click="showCustomRange = !showCustomRange"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold border transition-colors cursor-pointer"
                                :class="showCustomRange ? 'bg-[#8E1B5C] text-white border-[#8E1B5C]' : 'bg-[#FFFCFB] text-[#172B4D] border-[#FFD8D8] hover:bg-[#FFD8D8]'">
                            Rentang Tanggal
                        </button>
                    </div>

                    {{-- Search, Flow & Type Filter --}}
                    <div class="flex flex-wrap items-center gap-2 grow sm:grow-0">
                        <div class="relative grow sm:w-52">
                            <input type="text"
                                   name="search"
                                   value="{{ $searchVal }}"
                                   placeholder="Cari transaksi..."
                                   class="w-full h-9 px-3 text-xs sm:text-[13px] border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C]">
                        </div>
                        <select name="flow"
                                x-model="flow"
                                @change="onFlowChange()"
                                class="h-9 px-2.5 sm:px-3 text-xs sm:text-[13px] border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] cursor-pointer">
                            <option value="all" {{ $currentFlow === 'all' ? 'selected' : '' }}>Semua Arus</option>
                            <option value="in" {{ $currentFlow === 'in' ? 'selected' : '' }}>Uang Masuk</option>
                            <option value="out" {{ $currentFlow === 'out' ? 'selected' : '' }}>Uang Keluar</option>
                        </select>
                        <select name="type"
                                id="transaction_type_select"
                                @change="onTypeChange()"
                                class="h-9 px-2.5 sm:px-3 text-xs sm:text-[13px] border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] cursor-pointer">
                            @if($currentFlow === 'in')
                                <option value="all" {{ $currentType === 'all' ? 'selected' : '' }}>Semua Jenis Pemasukan</option>
                                <option value="customer_dp" {{ $currentType === 'customer_dp' || $currentType === 'dp' ? 'selected' : '' }}>Uang Muka (DP)</option>
                                <option value="customer_settlement" {{ $currentType === 'customer_settlement' || $currentType === 'pelunasan' || $currentType === 'settlement' ? 'selected' : '' }}>Pelunasan</option>
                                <option value="pemasukan_lain" {{ $currentType === 'pemasukan_lain' || $currentType === 'business_income_other' ? 'selected' : '' }}>Pemasukan Lain</option>
                                <option value="modal_tambahan" {{ $currentType === 'modal_tambahan' || $currentType === 'capital_injection' ? 'selected' : '' }}>Modal Tambahan</option>
                                <option value="pendapatan_non_penjualan" {{ $currentType === 'pendapatan_non_penjualan' || $currentType === 'non_sales_income' ? 'selected' : '' }}>Pendapatan Non-Penjualan</option>
                            @elseif($currentFlow === 'out')
                                <option value="all" {{ $currentType === 'all' ? 'selected' : '' }}>Semua Jenis Pengeluaran</option>
                                <option value="pembayaran_supplier" {{ $currentType === 'pembayaran_supplier' || $currentType === 'purchase_payment' || $currentType === 'supplier_payment' ? 'selected' : '' }}>Pembayaran Supplier</option>
                                <option value="operasional" {{ $currentType === 'operasional' || $currentType === 'operating' ? 'selected' : '' }}>Operasional Usaha</option>
                                <option value="marketing" {{ $currentType === 'marketing' ? 'selected' : '' }}>Marketing / Pemasaran</option>
                                <option value="bunga" {{ $currentType === 'bunga' || $currentType === 'interest' ? 'selected' : '' }}>Bunga</option>
                                <option value="pajak" {{ $currentType === 'pajak' || $currentType === 'tax' ? 'selected' : '' }}>Pajak</option>
                                <option value="penyusutan" {{ $currentType === 'penyusutan' || $currentType === 'depreciation' || $currentType === 'depresiasi' ? 'selected' : '' }}>Penyusutan</option>
                                <option value="amortisasi" {{ $currentType === 'amortisasi' || $currentType === 'amortization' ? 'selected' : '' }}>Amortisasi</option>
                            @else
                                <option value="all" {{ $currentType === 'all' ? 'selected' : '' }}>Semua Jenis</option>
                                <optgroup label="PEMASUKAN">
                                    <option value="customer_dp" {{ $currentType === 'customer_dp' || $currentType === 'dp' ? 'selected' : '' }}>Uang Muka (DP)</option>
                                    <option value="customer_settlement" {{ $currentType === 'customer_settlement' || $currentType === 'pelunasan' || $currentType === 'settlement' ? 'selected' : '' }}>Pelunasan</option>
                                    <option value="pemasukan_lain" {{ $currentType === 'pemasukan_lain' || $currentType === 'business_income_other' ? 'selected' : '' }}>Pemasukan Lain</option>
                                    <option value="modal_tambahan" {{ $currentType === 'modal_tambahan' || $currentType === 'capital_injection' ? 'selected' : '' }}>Modal Tambahan</option>
                                    <option value="pendapatan_non_penjualan" {{ $currentType === 'pendapatan_non_penjualan' || $currentType === 'non_sales_income' ? 'selected' : '' }}>Pendapatan Non-Penjualan</option>
                                </optgroup>
                                <optgroup label="PENGELUARAN">
                                    <option value="pembayaran_supplier" {{ $currentType === 'pembayaran_supplier' || $currentType === 'purchase_payment' || $currentType === 'supplier_payment' ? 'selected' : '' }}>Pembayaran Supplier</option>
                                    <option value="operasional" {{ $currentType === 'operasional' || $currentType === 'operating' ? 'selected' : '' }}>Operasional Usaha</option>
                                    <option value="marketing" {{ $currentType === 'marketing' ? 'selected' : '' }}>Marketing / Pemasaran</option>
                                    <option value="bunga" {{ $currentType === 'bunga' || $currentType === 'interest' ? 'selected' : '' }}>Bunga</option>
                                    <option value="pajak" {{ $currentType === 'pajak' || $currentType === 'tax' ? 'selected' : '' }}>Pajak</option>
                                    <option value="penyusutan" {{ $currentType === 'penyusutan' || $currentType === 'depreciation' || $currentType === 'depresiasi' ? 'selected' : '' }}>Penyusutan</option>
                                    <option value="amortisasi" {{ $currentType === 'amortisasi' || $currentType === 'amortization' ? 'selected' : '' }}>Amortisasi</option>
                                </optgroup>
                            @endif
                        </select>
                        <button type="submit"
                                class="h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs sm:text-[13px] font-semibold hover:bg-[#8E1B5C] transition shadow-xs cursor-pointer">
                            Filter
                        </button>
                    </div>
                </div>

                {{-- Expandable Date Range Pickers --}}
                <div x-show="showCustomRange" x-cloak class="pt-2 border-t border-[#FFD8D8]/60 flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-[#64748B] text-[11px] font-medium">Rentang:</span>
                    <input type="date"
                           name="start_date"
                           value="{{ $filters['start_date'] ?? '' }}"
                           class="h-9 px-2 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white">
                    <span class="text-[#64748B] text-[11px]">sampai</span>
                    <input type="date"
                           name="end_date"
                           value="{{ $filters['end_date'] ?? '' }}"
                           class="h-9 px-2 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white">
                    <button type="submit" class="h-9 px-3 rounded-lg bg-[#8E1B5C] text-white text-xs font-medium hover:bg-[#8E1B5C] cursor-pointer">
                        Terapkan
                    </button>
                </div>
            </form>
        </div>

        {{-- Main Ledger: Riwayat Transaksi --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <x-nayara-section-header
                title="Riwayat Transaksi"
                subtitle="Menampilkan {{ $records->total() }} catatan arus kas operasional."
            />

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#FFD8D8] bg-[#FFD8D8] text-[#64748B] font-semibold uppercase tracking-[0.02em] text-[11.5px] sm:text-[12px]">
                            <th class="py-2.5 sm:py-3 px-3 align-middle">Tanggal</th>
                            <th class="py-2.5 sm:py-3 px-3 text-center align-middle">Jenis</th>
                            <th class="py-2.5 sm:py-3 px-3 align-middle">Kategori</th>
                            <th class="py-2.5 sm:py-3 px-3 align-middle">Referensi</th>
                            <th class="py-2.5 sm:py-3 px-3 text-right align-middle">Nominal</th>
                            <th class="py-2.5 sm:py-3 px-3 text-center align-middle">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/60">
                        @forelse($records as $row)
                            <tr class="hover:bg-[#FFFCFB]/60 transition-colors">
                                {{-- Tanggal --}}
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <div class="font-semibold text-[#172B4D]">
                                        {{ \App\Support\DateTimeFormatter::formatDate($row['date'], 'd/m/Y') }}
                                    </div>
                                    @if(!empty($row['timestamp']))
                                        <div class="text-[10px] text-[#64748B]">
                                            {{ \App\Support\DateTimeFormatter::formatTime($row['timestamp']) }}
                                        </div>
                                    @endif
                                    @if(!empty($row['is_historical']))
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-semibold bg-gray-100 text-gray-600 border border-gray-200" title="Transaksi historis sebelum cut-off saldo awal">
                                            Historis
                                        </span>
                                    @endif
                                </td>

                                {{-- Jenis (Badge) --}}
                                <td class="py-2.5 px-3 whitespace-nowrap text-center">
                                    <x-status-badge :status="$row['type']" context="flow" :label="$row['type']" size="table" />
                                </td>

                                {{-- Kategori --}}
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <span class="font-medium text-[#172B4D]">{{ $row['category'] }}</span>
                                </td>

                                {{-- Referensi (Concise) --}}
                                <td class="py-2.5 px-3">
                                    <div class="flex flex-col">
                                        @if(!empty($row['reference_url']))
                                            <a href="{{ $row['reference_url'] }}"
                                               class="font-mono font-semibold text-[#8E1B5C] hover:underline"
                                               title="Buka dokumen">
                                                @if($row['source_type'] === 'PURCHASE_PAYMENT')
                                                    PO #{{ $row['reference_code'] }}
                                                @else
                                                    {{ $row['reference_code'] }}
                                                @endif
                                            </a>
                                        @else
                                            <span class="font-mono font-semibold text-[#172B4D]">
                                                @if($row['source_type'] === 'PURCHASE_PAYMENT')
                                                    PO #{{ $row['reference_code'] }}
                                                @else
                                                    {{ $row['reference_code'] }}
                                                @endif
                                            </span>
                                        @endif
                                        @if(!empty($row['supplier_name']))
                                            <span class="text-[11px] text-[#64748B] truncate max-w-xs">{{ $row['supplier_name'] }}</span>
                                        @elseif(!empty($row['customer_name']))
                                            <span class="text-[11px] text-[#64748B] truncate max-w-xs">{{ $row['customer_name'] }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Nominal (Single Column) --}}
                                <td class="py-2.5 px-3 whitespace-nowrap text-right">
                                    @if($row['type'] === 'MASUK')
                                        <span class="font-mono font-bold text-emerald-600">
                                            + Rp {{ number_format((float) $row['amount_in'], 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="font-mono font-bold text-rose-600">
                                            &minus; Rp {{ number_format((float) $row['amount_out'], 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Aksi (Detail Modal Trigger) --}}
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <button type="button"
                                            @click="selectedRecord = {{ json_encode($row) }}; showDetailModal = true"
                                            class="inline-flex items-center justify-center px-2 py-1 rounded-md border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs cursor-pointer">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-[#64748B] bg-[#FFFCFB]">
                                    <p class="font-medium text-[#172B4D]">Belum Ada Catatan Keuangan</p>
                                    <p class="text-[11px] text-[#64748B] mt-0.5">Tidak ada transaksi kas pada periode atau kriteria filter ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($records->hasPages())
                <div class="p-3 border-t border-[#FFD8D8] bg-[#FFFCFB]/50">
                    {{ $records->links() }}
                </div>
            @endif
        </div>

        {{-- Detail Modal (Audit Notes & Context) --}}
        <div x-show="showDetailModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
            <div @click.away="showDetailModal = false"
                 class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] flex flex-col p-5 sm:p-6 shadow-2xl border border-[#FFD8D8]">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3 shrink-0">
                    <div>
                        <h3 class="text-sm font-bold text-[#172B4D]">Rincian Transaksi Kas</h3>
                        <p class="text-[11px] text-[#64748B]" x-text="selectedRecord?.category"></p>
                    </div>
                    <button type="button"
                            @click="showDetailModal = false"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-2.5 text-xs overflow-y-auto pr-1 flex-1 py-1" x-show="selectedRecord">
                    {{-- Ringkasan Pembayaran Invoice (Khusus Transaksi Pembayaran Customer / PAYMENT) --}}
                    <template x-if="selectedRecord?.source_type === 'PAYMENT' && selectedRecord?.payment_history && selectedRecord?.payment_history.length > 0">
                        <div class="border border-[#FFD8D8] rounded-xl overflow-hidden bg-white shadow-xs">
                            {{-- Header --}}
                            <div class="px-3 py-1.5 bg-[#FFFCFB] border-b border-[#FFD8D8] flex items-center justify-between">
                                <span class="text-[10px] text-[#64748B] font-bold uppercase tracking-wider">Pembayaran</span>
                            </div>

                            {{-- Payment List (Compact Stacked Rows) --}}
                            <div class="divide-y divide-[#FFD8D8]/60">
                                <template x-for="(payItem, pIdx) in selectedRecord.payment_history" :key="pIdx">
                                    <div class="p-2.5 sm:px-3 sm:py-2 flex items-center justify-between gap-2 transition-colors"
                                         :class="payItem.is_current ? 'bg-[#FFD8D8]/70' : 'hover:bg-[#FFFCFB]/50'">
                                        <div class="space-y-0.5 min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-semibold text-xs text-[#172B4D]" x-text="payItem.type_label"></span>
                                                <template x-if="payItem.is_current">
                                                    <span class="inline-flex items-center gap-1 text-[10.5px] font-semibold text-[#8E1B5C]">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#8E1B5C]"></span>
                                                        Saat ini
                                                    </span>
                                                </template>
                                            </div>
                                            <div class="text-[10.5px] text-[#64748B]">
                                                <span x-text="payItem.date_formatted"></span>
                                                <template x-if="payItem.time_formatted && payItem.time_formatted !== '-'">
                                                    <span x-text="' · ' + payItem.time_formatted"></span>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="font-mono font-bold text-xs text-[#172B4D]" x-text="payItem.amount_formatted"></div>
                                            <template x-if="payItem.status !== 'CONFIRMED' && payItem.status !== 'confirmed'">
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-medium border"
                                                      :class="payItem.status_badge"
                                                      x-text="payItem.status_label">
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Compact Payment Summary Footer --}}
                            <template x-if="selectedRecord?.payment_summary">
                                <div class="px-3 py-2 bg-[#FFFCFB]/90 border-t border-[#FFD8D8] flex items-center justify-between text-[11px] gap-2 flex-wrap">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[#64748B] text-[10px] uppercase font-semibold">Dibayar</span>
                                        <span class="font-mono font-bold text-emerald-600" x-text="'Rp ' + Number(selectedRecord.payment_summary.total_paid).toLocaleString('id-ID')"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[#64748B] text-[10px] uppercase font-semibold">Sisa</span>
                                        <span class="font-mono font-bold"
                                              :class="selectedRecord.payment_summary.is_lunas ? 'text-gray-600' : 'text-rose-600'"
                                              x-text="'Rp ' + Number(selectedRecord.payment_summary.outstanding).toLocaleString('id-ID')">
                                        </span>
                                    </div>
                                    <div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                                              :class="selectedRecord.payment_summary.status_badge"
                                              x-text="selectedRecord.payment_summary.status_label">
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Ringkasan Transaksi Biasa (Untuk Transaksi selain Customer PAYMENT) --}}
                    <template x-if="selectedRecord?.source_type !== 'PAYMENT'">
                        <div class="grid grid-cols-2 gap-3 p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8]">
                            <div>
                                <span class="text-[10px] text-[#64748B] font-semibold uppercase block">Tanggal Transaksi</span>
                                <span class="font-semibold text-[#172B4D]" x-text="selectedRecord?.date"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-[#64748B] font-semibold uppercase block">Nominal</span>
                                <span class="font-mono font-bold"
                                      :class="selectedRecord?.type === 'MASUK' ? 'text-emerald-600' : 'text-rose-600'"
                                      x-text="(selectedRecord?.type === 'MASUK' ? '+ Rp ' : '- Rp ') + Number(selectedRecord?.type === 'MASUK' ? selectedRecord?.amount_in : selectedRecord?.amount_out).toLocaleString('id-ID')">
                                </span>
                            </div>
                        </div>
                    </template>

                    {{-- Informasi Transaksi (Khusus Customer Payment) --}}
                    <template x-if="selectedRecord?.source_type === 'PAYMENT'">
                        <div class="grid grid-cols-2 gap-2 p-2.5 bg-[#FFFCFB]/60 rounded-xl border border-[#FFD8D8] text-xs">
                            <div>
                                <span class="text-[9.5px] text-[#64748B] font-semibold uppercase block tracking-wider">Invoice</span>
                                <div class="font-mono font-semibold text-[#8E1B5C]" x-text="selectedRecord?.reference_code"></div>
                            </div>
                            <div>
                                <span class="text-[9.5px] text-[#64748B] font-semibold uppercase block tracking-wider">Sales Order</span>
                                <div class="font-mono font-semibold text-[#172B4D]" x-text="selectedRecord?.order_no || '-'"></div>
                            </div>
                            <div class="col-span-2 pt-1.5 border-t border-[#FFD8D8]/60 flex items-center justify-between">
                                <div>
                                    <span class="text-[9.5px] text-[#64748B] font-semibold uppercase block tracking-wider">Customer</span>
                                    <div class="font-medium text-[#172B4D]" x-text="selectedRecord?.customer_name || '-'"></div>
                                </div>
                                <template x-if="selectedRecord?.actor_name">
                                    <div class="text-right">
                                        <span class="text-[9.5px] text-[#64748B] font-semibold uppercase block tracking-wider">Dicatat Oleh</span>
                                        <div class="text-[11px] text-[#64748B]" x-text="selectedRecord?.actor_name"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Informasi Transaksi (Untuk Transaksi selain Customer Payment) --}}
                    <template x-if="selectedRecord?.source_type !== 'PAYMENT'">
                        <div class="grid grid-cols-2 gap-2 p-2.5 bg-[#FFFCFB]/60 rounded-xl border border-[#FFD8D8] text-xs">
                            <div>
                                <span class="text-[9.5px] text-[#64748B] font-semibold uppercase block tracking-wider">Nomor Referensi</span>
                                <div class="font-mono font-semibold text-[#8E1B5C]" x-text="selectedRecord?.reference_code"></div>
                            </div>
                            <template x-if="selectedRecord?.order_no">
                                <div>
                                    <span class="text-[9.5px] text-[#64748B] font-semibold uppercase block tracking-wider">Sales Order</span>
                                    <div class="font-mono font-semibold text-[#172B4D]" x-text="selectedRecord?.order_no"></div>
                                </div>
                            </template>
                            <template x-if="selectedRecord?.supplier_name">
                                <div>
                                    <span class="text-[9.5px] text-[#64748B] font-semibold uppercase block tracking-wider">Supplier</span>
                                    <div class="font-medium text-[#172B4D]" x-text="selectedRecord?.supplier_name"></div>
                                </div>
                            </template>
                            <template x-if="selectedRecord?.customer_name">
                                <div>
                                    <span class="text-[9.5px] text-[#64748B] font-semibold uppercase block tracking-wider">Customer</span>
                                    <div class="font-medium text-[#172B4D]" x-text="selectedRecord?.customer_name"></div>
                                </div>
                            </template>
                            <template x-if="selectedRecord?.actor_name">
                                <div class="col-span-2 pt-1 border-t border-[#FFD8D8]/60">
                                    <span class="text-[9.5px] text-[#64748B] font-semibold uppercase block tracking-wider">Dicatat Oleh</span>
                                    <div class="text-[11px] text-[#64748B]" x-text="selectedRecord?.actor_name"></div>
                                </div>
                            </template>
                            <template x-if="selectedRecord?.transfer_proof_url">
                                <div class="col-span-2 pt-2 border-t border-[#FFD8D8]/60 flex items-center justify-between">
                                    <span class="text-[9.5px] text-[#64748B] font-semibold uppercase tracking-wider">Bukti Transfer</span>
                                    <a :href="selectedRecord.transfer_proof_url"
                                       target="_blank"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#8E1B5C] text-white text-[11px] font-semibold hover:bg-[#8E1B5C] transition shadow-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                        </svg>
                                        <span>Lihat Bukti</span>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Item Pesanan (Khusus Transaksi Pembayaran Customer / PAYMENT) --}}
                    <template x-if="selectedRecord?.source_type === 'PAYMENT' && selectedRecord?.order_items && selectedRecord?.order_items.length > 0">
                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-[#64748B] font-semibold uppercase tracking-wider block">Item Pesanan</span>
                            </div>
                            <div class="border border-[#FFD8D8] rounded-xl overflow-hidden bg-white">
                                <div class="max-h-52 overflow-y-auto overflow-x-auto">
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead class="bg-[#FFFCFB] text-[10px] font-bold text-[#64748B] uppercase tracking-wider sticky top-0 border-b border-[#FFD8D8]">
                                            <tr>
                                                <th class="py-1.5 px-3">Produk / Varian</th>
                                                <th class="py-1.5 px-2 text-center">Qty</th>
                                                <th class="py-1.5 px-2 text-right">Harga / Unit</th>
                                                <th class="py-1.5 px-3 text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[#FFD8D8]/60 text-[11px] sm:text-xs">
                                            <template x-for="(item, idx) in selectedRecord.order_items" :key="idx">
                                                <tr class="hover:bg-[#FFFCFB]/50 transition-colors">
                                                    <td class="py-2 px-3">
                                                        <div class="font-medium text-[#172B4D]" x-text="item.product_name"></div>
                                                        <template x-if="item.variant_name">
                                                            <div class="text-[10px] text-[#8E1B5C] font-semibold mt-0.5" x-text="item.variant_name"></div>
                                                        </template>
                                                    </td>
                                                    <td class="py-2 px-2 text-center whitespace-nowrap text-[#172B4D] font-medium" x-text="item.qty_formatted"></td>
                                                    <td class="py-2 px-2 text-right whitespace-nowrap font-mono text-[#64748B]" x-text="item.unit_price_formatted"></td>
                                                    <td class="py-2 px-3 text-right whitespace-nowrap font-mono font-semibold text-[#172B4D]" x-text="item.subtotal_formatted"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <template x-if="selectedRecord?.total_order_amount_formatted">
                                            <tfoot class="bg-[#FFFCFB]/90 text-[11px] border-t border-[#FFD8D8] font-semibold">
                                                <tr>
                                                    <td class="py-2 px-3 text-[#64748B] uppercase font-bold text-[10px]">Total Pesanan</td>
                                                    <td class="py-2 px-2 text-center text-[#172B4D] whitespace-nowrap font-bold" x-text="selectedRecord.total_order_qty_formatted"></td>
                                                    <td class="py-2 px-2 text-right text-[#64748B]"></td>
                                                    <td class="py-2 px-3 text-right font-mono font-bold text-[#8E1B5C] whitespace-nowrap" x-text="selectedRecord.total_order_amount_formatted"></td>
                                                </tr>
                                            </tfoot>
                                        </template>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Bahan Dibeli (Khusus Transaksi Pembayaran Supplier) --}}
                    <template x-if="selectedRecord?.source_type === 'PURCHASE_PAYMENT' && selectedRecord?.purchase_items && selectedRecord?.purchase_items.length > 0">
                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-[#64748B] font-semibold uppercase tracking-wider block">Bahan Dibeli</span>
                                <span class="text-[10px] text-[#64748B]" x-text="selectedRecord?.reference_code ? ('Item pada ' + (selectedRecord.reference_code.toUpperCase().includes('PO') ? selectedRecord.reference_code : 'PO-' + selectedRecord.reference_code)) : 'Item PO'"></span>
                            </div>
                            <div class="border border-[#FFD8D8] rounded-xl overflow-hidden bg-white">
                                <div class="max-h-48 overflow-y-auto overflow-x-auto">
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead class="bg-[#FFFCFB] text-[10px] font-bold text-[#64748B] uppercase tracking-wider sticky top-0 border-b border-[#FFD8D8]">
                                            <tr>
                                                <th class="py-1.5 px-3">Material</th>
                                                <th class="py-1.5 px-2 text-center">Qty</th>
                                                <th class="py-1.5 px-2 text-right">Harga / Unit</th>
                                                <th class="py-1.5 px-3 text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[#FFD8D8]/60 text-[11px] sm:text-xs">
                                            <template x-for="(item, idx) in selectedRecord.purchase_items" :key="idx">
                                                <tr class="hover:bg-[#FFFCFB]/50 transition-colors">
                                                    <td class="py-1.5 px-3 font-medium text-[#172B4D]" x-text="item.material_name"></td>
                                                    <td class="py-1.5 px-2 text-center whitespace-nowrap text-[#172B4D]" x-text="item.qty_formatted"></td>
                                                    <td class="py-1.5 px-2 text-right whitespace-nowrap font-mono text-[#64748B]" x-text="item.unit_price_formatted"></td>
                                                    <td class="py-1.5 px-3 text-right whitespace-nowrap font-mono font-semibold text-[#172B4D]" x-text="item.subtotal_formatted"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Catatan Manual (Hanya tampil jika ada user notes manual untuk Customer Payment) --}}
                    <template x-if="selectedRecord?.source_type === 'PAYMENT' && selectedRecord?.notes && selectedRecord.notes.trim() !== ''">
                        <div class="space-y-1">
                            <span class="text-[10px] text-[#64748B] font-semibold uppercase block tracking-wider">Catatan</span>
                            <div class="p-2.5 bg-gray-50 rounded-lg border border-gray-200 text-gray-800 text-[11px] leading-relaxed break-words"
                                 x-text="selectedRecord.notes">
                            </div>
                        </div>
                    </template>

                    {{-- Keterangan / Audit Log (Untuk Transaksi selain Customer Payment) --}}
                    <template x-if="selectedRecord?.source_type !== 'PAYMENT' && selectedRecord?.description">
                        <div class="space-y-1">
                            <span class="text-[10px] text-[#64748B] font-semibold uppercase block tracking-wider">Keterangan / Audit Log</span>
                            <div class="p-2.5 bg-gray-50 rounded-lg border border-gray-200 text-gray-800 text-[11px] leading-relaxed break-words"
                                 x-text="selectedRecord?.description">
                            </div>
                        </div>
                    </template>
                </div>

                <div class="pt-3 border-t border-[#FFD8D8] flex justify-end shrink-0">
                    <button type="button"
                            @click="showDetailModal = false"
                            class="px-4 py-2 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
