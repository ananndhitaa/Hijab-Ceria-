<x-app-layout pageTitle="Detail Varian & BOM">
    <div class="space-y-5" x-data="{
        showAddBomModal: false,
        showDeleteBomModal: false,
        deleteBomRoute: '',
        deleteMaterialName: '',
        editingBomId: null,
        editingQty: '',
        showApplyTemplateModal: false,
        templates: {{ \Illuminate\Support\Js::from($templatePayload ?? []) }},
        selectedTemplateId: '',
        replaceExisting: false,
        hasExistingBom: {{ $variant->bomItems->count() > 0 ? 'true' : 'false' }},
        get selectedTemplate() {
            return this.templates.find(t => t.id == this.selectedTemplateId) || null;
        }
    }">


        @if($errors->any())
            <div class="p-4 bg-red-50 border border-red-200 rounded-xl shadow-xs">
                <div class="flex items-center gap-2 text-red-800 font-semibold text-xs mb-1.5">
                    <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Terjadi Kesalahan Input:</span>
                </div>
                <ul class="list-disc list-inside text-xs text-red-700 space-y-0.5 ml-6">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Section A: Header Card --}}
        <x-feature-header
            :title="$variant->full_name"
            icon="product"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <a href="{{ route('admin.products.show', $variant->product) }}" class="text-xs text-[#64748B] hover:underline hover:text-[#8E1B5C]">{{ $variant->product->product_name }}</a>
                    <span class="text-xs text-[#64748B]">/</span>
                    <span class="font-mono text-xs text-[#8E1B5C] font-semibold bg-[#FFD8D8] px-2.5 py-0.5 rounded-md border border-[#FFD8D8]">
                        SKU: {{ $variant->sku }}
                    </span>
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('admin.variants.edit', $variant) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Varian</span>
                </a>
                <a href="{{ route('admin.products.show', $variant->product) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Produk</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        @if($variant->image_url)
        <div class="p-3.5 bg-white border border-[#FFD8D8] rounded-xl flex items-center gap-3.5 shadow-xs">
            <img src="{{ $variant->image_url }}" alt="{{ $variant->variant_name }}" class="w-14 h-14 rounded-lg object-cover border border-[#FFD8D8] shrink-0">
            <div class="min-w-0">
                <span class="text-xs font-bold text-[#172B4D] block">Foto Khusus Varian</span>
                <p class="text-[11px] text-[#64748B] mt-0.5">Foto ini akan ditampilkan secara otomatis pada gambar utama katalog storefront ketika pengunjung memilih varian <strong>{{ $variant->variant_name }}</strong>.</p>
            </div>
        </div>
        @endif

        {{-- Section B: Variant Summary Card --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 divide-y sm:divide-y-0 sm:divide-x divide-[#FFD8D8] bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="p-4 sm:p-5 space-y-1">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Produk Utama</span>
                <p class="text-sm font-bold text-[#172B4D] whitespace-normal break-words leading-snug">{{ $variant->product->product_name }}</p>
            </div>

            <div class="p-4 sm:p-5 space-y-1">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Rute Produksi</span>
                <div class="pt-0.5">
                    @if($variant->requires_printing === true)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">Dengan Printing</span>
                    @elseif($variant->requires_printing === false)
                        <span class="badge-secondary text-xs">Tanpa Printing</span>
                    @else
                        <span class="badge-warning text-xs">Belum Ditetapkan</span>
                    @endif
                </div>
            </div>

            <div class="p-4 sm:p-5 space-y-1">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Harga Jual Master</span>
                <p class="text-sm font-bold text-emerald-700">
                    @if($variant->base_selling_price !== null)
                        {{ \App\Support\MoneyFormatter::rupiah($variant->base_selling_price) }}
                    @else
                        <span class="text-[#64748B] italic font-normal text-xs">Belum ditentukan</span>
                    @endif
                </p>
            </div>

            <div class="p-4 sm:p-5 space-y-1">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Status BOM Recipe</span>
                <div class="pt-0.5">
                    @if($variant->bomItems->count() > 0)
                        <span class="badge-success text-xs">Resep BOM Lengkap</span>
                    @else
                        <span class="badge-warning text-xs">BOM Belum Diatur</span>
                    @endif
                </div>
            </div>

            <div class="p-4 sm:p-5 space-y-1">
                <span class="text-[11px] font-semibold text-[#64748B] uppercase tracking-wider block">Total Material BOM</span>
                <p class="text-sm font-bold text-[#172B4D]">{{ $variant->bomItems->count() }} Jenis Material</p>
            </div>
        </div>

        {{-- Section B1: Financial Analysis (HARGA & MODAL) --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card"
             x-data="{ openRefHpp: false, openActualHpp: false }">
            @php
                $priceStatus = $marginEval['price_status'] ?? 'HARGA BELUM DITETAPKAN';
                $statusMessage = $marginEval['status_message'] ?? 'Harga jual belum ditetapkan.';
            @endphp
            <div class="bg-[#FFFCFB] px-4 sm:px-5 py-3.5 border-b border-[#FFD8D8] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-0.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Harga &amp; Modal — Analisis HPP / Modal vs Harga Jual</h3>
                        @if($priceStatus === 'VALID')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                ✓ VALID
                            </span>
                        @elseif($priceStatus === 'PERLU REVISI')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-100 text-red-800 border border-red-300">
                                ⚠️ PERLU REVISI
                            </span>
                        @elseif($priceStatus === 'BELUM DAPAT DIVERIFIKASI')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                ℹ️ BELUM DAPAT DIVERIFIKASI
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-300">
                                HARGA BELUM DITETAPKAN
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-[#64748B]">HPP / Modal Acuan dihitung otomatis oleh sistem dari Resep BOM &amp; Biaya Standar sebagai dasar harga jual.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.variants.edit', $variant) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition-colors shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Ubah Harga / Biaya</span>
                    </a>
                </div>
            </div>

            {{-- Status Harga Callout Banner --}}
            <div class="px-4 sm:px-5 py-2.5 border-b border-[#FFD8D8] flex items-center justify-between gap-3
                {{ $priceStatus === 'VALID' ? 'bg-emerald-50/80 text-emerald-900' : '' }}
                {{ $priceStatus === 'PERLU REVISI' ? 'bg-red-50/80 text-red-900' : '' }}
                {{ $priceStatus === 'BELUM DAPAT DIVERIFIKASI' ? 'bg-amber-50/80 text-amber-900' : '' }}
                {{ $priceStatus === 'HARGA BELUM DITETAPKAN' ? 'bg-slate-50/80 text-slate-800' : '' }}
            ">
                <div class="flex items-center gap-2 text-xs flex-wrap">
                    <span class="font-bold uppercase tracking-wider text-[11px]">Status Harga:</span>
                    <strong class="font-bold font-mono px-1.5 py-0.5 rounded text-[11px]
                        {{ $priceStatus === 'VALID' ? 'bg-emerald-200 text-emerald-900' : '' }}
                        {{ $priceStatus === 'PERLU REVISI' ? 'bg-red-200 text-red-900' : '' }}
                        {{ $priceStatus === 'BELUM DAPAT DIVERIFIKASI' ? 'bg-amber-200 text-amber-900' : '' }}
                        {{ $priceStatus === 'HARGA BELUM DITETAPKAN' ? 'bg-slate-200 text-slate-800' : '' }}
                    ">{{ $priceStatus }}</strong>
                    <span class="font-medium">&mdash; {{ $statusMessage }}</span>
                </div>
            </div>

            {{-- 4 Main Cards: HPP Acuan, Harga Jual Master, Margin Nominal, Margin % --}}
            <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- 1. HPP / Modal Acuan --}}
                <div class="p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] flex flex-col justify-between space-y-2">
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">HPP / Modal Acuan</span>
                        @if(isset($estimatedCost) && ($estimatedCost['is_available'] ?? false))
                            <p class="text-lg font-black text-[#8E1B5C] font-mono leading-tight">
                                {{ \App\Support\MoneyFormatter::rupiah($estimatedCost['grand_total'] ?? $estimatedCost['estimated_total_cost'] ?? 0) }}
                                <span class="text-xs font-normal text-[#64748B] font-sans">/ pcs</span>
                            </p>
                            <span class="text-[10px] text-emerald-700 font-semibold block">Dihitung otomatis oleh sistem</span>
                        @else
                            <p class="text-xs font-bold text-amber-800 leading-snug">
                                HPP / Modal Acuan belum dapat dihitung.
                            </p>
                            <span class="text-[10px] text-[#64748B] block">BOM / data biaya belum lengkap</span>
                        @endif
                    </div>
                    <button type="button"
                            @click="openRefHpp = !openRefHpp"
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-[#8E1B5C] hover:underline cursor-pointer pt-1 border-t border-[#FFD8D8]/80">
                        <span x-text="openRefHpp ? 'Sembunyikan Rincian' : '[ Lihat Rincian HPP ]'"></span>
                        <svg class="w-3 h-3 transition-transform" :class="openRefHpp ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>

                {{-- 2. Harga Jual Master --}}
                <div class="p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] flex flex-col justify-between space-y-2">
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Harga Jual Master</span>
                        @if($variant->base_selling_price !== null)
                            <p class="text-lg font-black text-[#172B4D] font-mono leading-tight">
                                {{ \App\Support\MoneyFormatter::rupiah($variant->base_selling_price) }}
                                <span class="text-xs font-normal text-[#64748B] font-sans">/ pcs</span>
                            </p>
                            <span class="text-[10px] text-[#64748B] block">Harga acuan transaksi Sales Order</span>
                        @else
                            <p class="text-xs font-semibold text-[#64748B] italic leading-snug">
                                Belum ditentukan
                            </p>
                            <span class="text-[10px] text-amber-700 block">Wajib &gt; HPP / Modal Acuan</span>
                        @endif
                    </div>
                    <div class="text-[10px] text-[#64748B] pt-1 border-t border-[#FFD8D8]/80 flex items-center justify-between">
                        <span>Aturan: Wajib di atas HPP</span>
                        <strong class="{{ $priceStatus === 'VALID' ? 'text-emerald-700' : ($priceStatus === 'PERLU REVISI' ? 'text-red-700' : 'text-amber-700') }} font-mono">{{ $priceStatus }}</strong>
                    </div>
                </div>

                {{-- 3. Margin Nominal --}}
                <div class="p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] flex flex-col justify-between space-y-2">
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Margin Nominal</span>
                        @if(isset($marginEval) && $marginEval['margin'] !== null)
                            <p class="text-lg font-black font-mono leading-tight {{ $marginEval['is_below_cost'] ? 'text-red-700' : 'text-emerald-700' }}">
                                {{ $marginEval['margin_per_unit'] >= 0 ? '+' : '' }}{{ \App\Support\MoneyFormatter::rupiah($marginEval['margin']) }}
                                <span class="text-xs font-normal text-[#64748B] font-sans">/ pcs</span>
                            </p>
                            <span class="text-[10px] font-semibold block {{ $marginEval['is_below_cost'] ? 'text-red-700' : 'text-emerald-700' }}">
                                {{ $marginEval['is_below_cost'] ? '⚠️ Dibawah/sama dengan HPP' : 'Harga jual - HPP Acuan' }}
                            </span>
                        @else
                            <p class="text-xs font-semibold text-[#64748B] italic leading-snug">
                                Belum tersedia
                            </p>
                            <span class="text-[10px] text-[#64748B] block">Memerlukan harga &amp; HPP lengkap</span>
                        @endif
                    </div>
                    <div class="text-[10px] text-[#64748B] pt-1 border-t border-[#FFD8D8]/80">
                        Formula: Harga Jual - HPP Acuan
                    </div>
                </div>

                {{-- 4. Margin % --}}
                <div class="p-4 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] flex flex-col justify-between space-y-2">
                    <div class="space-y-1">
                        <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Margin %</span>
                        @if(isset($marginEval) && $marginEval['margin_percent'] !== null)
                            <p class="text-lg font-black font-mono leading-tight {{ $marginEval['is_below_cost'] ? 'text-red-700' : 'text-emerald-700' }}">
                                {{ number_format((float) $marginEval['margin_percent'], 2, ',', '.') }}%
                            </p>
                            <span class="text-[10px] font-semibold block {{ $marginEval['is_below_cost'] ? 'text-red-700' : 'text-emerald-700' }}">
                                {{ $marginEval['is_below_cost'] ? 'Tidak memenuhi margin positif' : 'Persentase terhadap harga jual' }}
                            </span>
                        @else
                            <p class="text-xs font-semibold text-[#64748B] italic leading-snug">
                                Belum tersedia
                            </p>
                            <span class="text-[10px] text-[#64748B] block">Memerlukan harga &amp; HPP lengkap</span>
                        @endif
                    </div>
                    <div class="text-[10px] text-[#64748B] pt-1 border-t border-[#FFD8D8]/80">
                        Formula: (Margin / Harga Jual) &times; 100
                    </div>
                </div>
            </div>

            {{-- Row: HPP Aktual Produksi Terakhir Banner --}}
            <div class="px-4 sm:px-5 py-3 bg-white border-t border-[#FFD8D8] flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-[#172B4D]">HPP Aktual Produksi Terakhir:</span>
                    @if(isset($latestActualHpp) && ($latestActualHpp['is_available'] ?? false))
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            HPP Aktual Tersedia
                        </span>
                        <span class="text-sm font-extrabold text-[#8E1B5C] font-mono">
                            {{ \App\Support\MoneyFormatter::rupiah($latestActualHpp['latest_actual_hpp']) }} / Good pcs
                        </span>
                        <span class="text-xs text-[#64748B] font-mono">(Batch: {{ $latestActualHpp['latest_batch_no'] }})</span>
                    @else
                        <span class="text-xs text-[#64748B] italic">Belum tersedia (Belum ada batch produksi SELESAI)</span>
                    @endif
                </div>
                <div>
                    <button type="button"
                            @click="openActualHpp = !openActualHpp"
                            class="inline-flex items-center gap-1 text-xs font-bold text-[#8E1B5C] hover:underline cursor-pointer">
                        <span x-text="openActualHpp ? 'Tutup Rincian HPP Aktual' : '[ Lihat Rincian HPP Aktual ]'"></span>
                        <svg class="w-3.5 h-3.5 transition-transform" :class="openActualHpp ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Collapsible 1: [ Lihat Rincian HPP ] (HPP / Modal Acuan Variant) --}}
            <div x-show="openRefHpp" x-cloak class="border-t border-[#FFD8D8] bg-[#FFFCFB]/70 p-4 sm:p-6 space-y-5 transition-all">
                <div class="border-b border-[#FFD8D8] pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h4 class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Rincian Pembentuk HPP / Modal Acuan Variant</h4>
                        <p class="text-[11px] text-[#64748B]">Rincian formula pembentuk biaya standar per 1 pcs varian sebelum order/produksi.</p>
                    </div>
                    <span class="text-[11px] font-semibold text-[#64748B]">Grand Total = Subtotal Bahan + Subtotal Biaya Produksi</span>
                </div>

                @if(isset($estimatedCost) && ($estimatedCost['is_available'] ?? false))
                    {{-- 1. RINCIAN BAHAN --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h5 class="text-xs font-bold text-[#172B4D] uppercase tracking-wide">RINCIAN BAHAN (BOM &times; MWA)</h5>
                            <span class="text-[11px] text-[#64748B]">Sumber modal material: Modal Rata-rata Persediaan (MWA)</span>
                        </div>
                        <div class="overflow-x-auto border border-[#FFD8D8] rounded-xl bg-white shadow-xs">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B]">
                                        <th class="px-4 py-2.5">Material</th>
                                        <th class="px-4 py-2.5 text-right">Qty BOM per pcs</th>
                                        <th class="px-4 py-2.5 text-center">Satuan</th>
                                        <th class="px-4 py-2.5 text-right">Modal Rata-rata / Unit (MWA)</th>
                                        <th class="px-4 py-2.5 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#FFD8D8]/60 font-mono">
                                    @foreach($estimatedCost['breakdown']['materials'] as $mat)
                                        <tr class="hover:bg-[#FFFCFB]">
                                            <td class="px-4 py-2.5 font-sans font-semibold text-[#172B4D]">
                                                @php
                                                    $isCorruptEstMat = config('nayara.tenant_isolation_enabled', false)
                                                        && $variant->outlet_id
                                                        && isset($mat['material_id'])
                                                        && (\App\Models\Master\Material::where('id', $mat['material_id'])->value('outlet_id') !== (int) $variant->outlet_id);
                                                @endphp
                                                @if($isCorruptEstMat)
                                                    <span class="text-amber-700 italic font-normal">[Integritas Tenant Tidak Valid: Material Tidak Sah]</span>
                                                @else
                                                    {{ $mat['material_name'] }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right">{{ \App\Support\QuantityFormatter::format($mat['qty_per_unit']) }}</td>
                                            <td class="px-4 py-2.5 text-center font-sans text-[#64748B]">
                                                @if($isCorruptEstMat)
                                                    —
                                                @else
                                                    {{ $mat['unit_name'] }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right text-[#64748B]">
                                                @if($isCorruptEstMat || $mat['avg_unit_cost'] === null)
                                                    —
                                                @else
                                                    {{ \App\Support\MoneyFormatter::rupiah($mat['avg_unit_cost']) }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-bold text-[#172B4D]">
                                                @if($isCorruptEstMat || $mat['subtotal'] === null)
                                                    —
                                                @else
                                                    {{ \App\Support\MoneyFormatter::rupiah($mat['subtotal']) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-[#FFFCFB] border-t border-[#FFD8D8] font-bold text-xs">
                                        <td colspan="4" class="px-4 py-2.5 text-right text-[#172B4D]">Subtotal Bahan:</td>
                                        <td class="px-4 py-2.5 text-right font-mono text-[#8E1B5C] text-sm">
                                            @if($estimatedCost['breakdown']['material_subtotal'] !== null)
                                                {{ \App\Support\MoneyFormatter::rupiah($estimatedCost['breakdown']['material_subtotal']) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- 2. RINCIAN BIAYA PRODUKSI --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h5 class="text-xs font-bold text-[#172B4D] uppercase tracking-wide">RINCIAN BIAYA PRODUKSI (STANDAR VARIANT)</h5>
                            <span class="text-[11px] text-[#64748B]">Tarif langsung per 1 pcs varian</span>
                        </div>
                        <div class="overflow-x-auto border border-[#FFD8D8] rounded-xl bg-white shadow-xs">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B]">
                                        <th class="px-4 py-2.5">Komponen Biaya</th>
                                        <th class="px-4 py-2.5">Keterangan / Status</th>
                                        <th class="px-4 py-2.5 text-right">Tarif Standar / pcs</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#FFD8D8]/60">
                                    {{-- Printing --}}
                                    <tr class="hover:bg-[#FFFCFB]">
                                        <td class="px-4 py-2.5 font-semibold text-[#172B4D]">Biaya Printing Standar</td>
                                        <td class="px-4 py-2.5 text-[11px] text-[#64748B]">
                                            @if($estimatedCost['breakdown']['direct_costs']['requires_printing'])
                                                Memerlukan proses printing kain
                                            @else
                                                Varian tanpa printing (tarif = Rp 0)
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                            {{ \App\Support\MoneyFormatter::rupiah($estimatedCost['breakdown']['direct_costs']['printing_cost_per_unit']) }}
                                        </td>
                                    </tr>

                                    {{-- Tenaga Kerja --}}
                                    <tr class="hover:bg-[#FFFCFB]">
                                        <td class="px-4 py-2.5 font-semibold text-[#172B4D]">Biaya Tenaga Kerja Standar</td>
                                        <td class="px-4 py-2.5 text-[11px] text-[#64748B]">Tarif upah langsung jahit/cutting per pcs</td>
                                        <td class="px-4 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                            {{ \App\Support\MoneyFormatter::rupiah($estimatedCost['breakdown']['direct_costs']['direct_labor_cost_per_unit']) }}
                                        </td>
                                    </tr>

                                    {{-- Packing / Proses --}}
                                    <tr class="hover:bg-[#FFFCFB]">
                                        <td class="px-4 py-2.5 font-semibold text-[#172B4D]">Biaya Packing / Proses Standar</td>
                                        <td class="px-4 py-2.5 text-[11px] text-[#64748B]">
                                            Biaya jasa proses packaging per good unit (material kemasan seperti plastik/label dicatat pada BOM)
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                            {{ \App\Support\MoneyFormatter::rupiah($estimatedCost['breakdown']['direct_costs']['packaging_process_cost_per_good_unit']) }}
                                        </td>
                                    </tr>

                                    {{-- Biaya Produksi Tambahan --}}
                                    <tr class="hover:bg-[#FFFCFB]">
                                        <td class="px-4 py-2.5 font-semibold text-[#172B4D]">Biaya Produksi Tambahan</td>
                                        <td class="px-4 py-2.5 text-[11px] text-[#64748B]">Overhead langsung / finishing tambahan</td>
                                        <td class="px-4 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                            {{ \App\Support\MoneyFormatter::rupiah($estimatedCost['breakdown']['direct_costs']['other_direct_cost_per_unit']) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-[#FFFCFB] border-t border-[#FFD8D8] font-bold text-xs">
                                        <td colspan="2" class="px-4 py-2.5 text-right text-[#172B4D]">Subtotal Biaya Produksi:</td>
                                        <td class="px-4 py-2.5 text-right font-mono text-[#8E1B5C] text-sm">
                                            {{ \App\Support\MoneyFormatter::rupiah($estimatedCost['breakdown']['direct_costs']['direct_cost_subtotal']) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- 3. SUMMARY BOX --}}
                    <div class="p-4 bg-white border border-[#FFD8D8] rounded-xl shadow-xs space-y-3">
                        <h5 class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wide">SUMMARY HPP / MODAL ACUAN</h5>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between items-center text-[#64748B]">
                                <span>Subtotal Bahan</span>
                                <span class="font-mono font-semibold text-[#172B4D]">{{ \App\Support\MoneyFormatter::rupiah($estimatedCost['breakdown']['material_subtotal']) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-[#64748B]">
                                <span>Subtotal Biaya Produksi</span>
                                <span class="font-mono font-semibold text-[#172B4D]">{{ \App\Support\MoneyFormatter::rupiah($estimatedCost['breakdown']['direct_costs']['direct_cost_subtotal']) }}</span>
                            </div>
                            <div class="border-t border-[#FFD8D8] pt-2 flex justify-between items-center text-sm font-bold">
                                <span class="text-[#172B4D]">HPP / Modal Acuan per pcs:</span>
                                <span class="text-base font-black text-[#8E1B5C] font-mono">{{ \App\Support\MoneyFormatter::rupiah($estimatedCost['grand_total'] ?? $estimatedCost['breakdown']['grand_total'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Missing Data State --}}
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl space-y-2">
                        <div class="flex items-center gap-2 text-amber-900 font-bold text-xs">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>HPP / Modal Acuan belum dapat dihitung.</span>
                        </div>
                        <p class="text-xs text-amber-800">
                            Sistem tidak menampilkan angka default atau Rp 0 palsu. Agar HPP Acuan dapat dihitung, lengkapi data berikut:
                        </p>
                        <ul class="list-disc list-inside text-xs text-amber-800 space-y-0.5 ml-2">
                            @foreach(($estimatedCost['missing_reasons'] ?? ['BOM atau data biaya belum lengkap.']) as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Collapsible 2: [ Lihat Rincian HPP Aktual ] (Realisasi Batch Produksi Terakhir) --}}
            <div x-show="openActualHpp" x-cloak class="border-t border-[#FFD8D8] bg-[#FFFCFB]/70 p-4 sm:p-6 space-y-4 transition-all">
                <div class="border-b border-[#FFD8D8] pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h4 class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Rincian HPP Aktual Produksi Terakhir</h4>
                        <p class="text-[11px] text-[#64748B]">Realisasi aktual dari batch produksi selesai (BatchCostCalculationService).</p>
                    </div>
                    @if(isset($latestActualHpp) && ($latestActualHpp['is_available'] ?? false))
                        <span class="font-mono text-xs font-bold text-[#8E1B5C]">
                            Batch: {{ $latestActualHpp['latest_batch_no'] }}
                        </span>
                    @endif
                </div>

                @if(isset($latestActualHpp) && ($latestActualHpp['is_available'] ?? false))
                    <div class="overflow-x-auto border border-[#FFD8D8] rounded-xl bg-white shadow-xs">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B]">
                                    <th class="px-4 py-2.5">Komponen Biaya Aktual</th>
                                    <th class="px-4 py-2.5 text-right">Nilai Batch</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FFD8D8]/60 font-mono">
                                <tr class="hover:bg-[#FFFCFB]">
                                    <td class="px-4 py-2.5 font-sans font-semibold text-[#172B4D]">Actual Material Cost (Bahan Baku)</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-[#172B4D]">
                                        {{ \App\Support\MoneyFormatter::rupiah($latestActualHpp['breakdown']['material_cost'] ?? 0) }}
                                    </td>
                                </tr>
                                <tr class="hover:bg-[#FFFCFB]">
                                    <td class="px-4 py-2.5 font-sans font-semibold text-[#172B4D]">Biaya Printing Aktual</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-[#172B4D]">
                                        {{ \App\Support\MoneyFormatter::rupiah($latestActualHpp['breakdown']['printing_cost'] ?? 0) }}
                                    </td>
                                </tr>
                                <tr class="hover:bg-[#FFFCFB]">
                                    <td class="px-4 py-2.5 font-sans font-semibold text-[#172B4D]">Biaya Tenaga Kerja Aktual (Labor)</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-[#172B4D]">
                                        {{ \App\Support\MoneyFormatter::rupiah($latestActualHpp['breakdown']['labor_cost'] ?? 0) }}
                                    </td>
                                </tr>
                                <tr class="hover:bg-[#FFFCFB]">
                                    <td class="px-4 py-2.5 font-sans font-semibold text-[#172B4D]">Biaya Packing (Proses)</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-[#172B4D]">
                                        {{ \App\Support\MoneyFormatter::rupiah($latestActualHpp['breakdown']['packaging_cost'] ?? 0) }}
                                    </td>
                                </tr>
                                <tr class="hover:bg-[#FFFCFB]">
                                    <td class="px-4 py-2.5 font-sans font-semibold text-[#172B4D]">Biaya Produksi Tambahan (Other Direct)</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-[#172B4D]">
                                        {{ \App\Support\MoneyFormatter::rupiah($latestActualHpp['breakdown']['other_direct_cost'] ?? 0) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-[#FFFCFB] border-t border-[#FFD8D8] font-bold text-xs">
                                    <td class="px-4 py-2.5 font-sans text-[#172B4D]">Total Biaya Batch:</td>
                                    <td class="px-4 py-2.5 text-right font-mono text-[#8E1B5C] text-sm">
                                        {{ \App\Support\MoneyFormatter::rupiah($latestActualHpp['total_hpp'] ?? 0) }}
                                    </td>
                                </tr>
                                <tr class="bg-white border-t border-[#FFD8D8] text-xs">
                                    <td class="px-4 py-2.5 font-sans text-[#64748B]">Good Qty (Lolos QC):</td>
                                    <td class="px-4 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                        {{ \App\Support\QuantityFormatter::format($latestActualHpp['good_qty'] ?? 0) }} pcs
                                    </td>
                                </tr>
                                <tr class="bg-[#FFFCFB] border-t-2 border-[#8E1B5C]/30 text-xs font-bold">
                                    <td class="px-4 py-2.5 font-sans text-[#172B4D]">HPP Aktual per Good pcs:</td>
                                    <td class="px-4 py-2.5 text-right font-mono text-base text-[#8E1B5C] font-black">
                                        {{ \App\Support\MoneyFormatter::rupiah($latestActualHpp['latest_actual_hpp']) }} / pcs
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-center text-xs text-[#64748B]">
                        Belum tersedia — Varian ini belum memiliki batch produksi dengan status SELESAI yang telah melewati QC.
                    </div>
                @endif
            </div>
        </div>

        {{-- Lower Content: Production Rates & BOM Grid --}}
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

            {{-- Section B2: Master Production Cost Rates --}}
            <div class="xl:col-span-5 bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-3 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 border-b border-[#FFD8D8] pb-3">
                    <div class="space-y-0.5 min-w-0">
                        <h3 class="text-xs font-bold text-[#8E1B5C] uppercase tracking-wider">Tarif Standar Produksi (Master)</h3>
                        <p class="text-[11px] text-[#64748B] leading-relaxed">
                            Tarif ini digunakan sistem sebagai nilai standar saat biaya produksi belum ditentukan pada batch dan disimpan sebagai snapshot saat Batch dibuat.
                        </p>
                    </div>
                    <a href="{{ route('admin.variants.edit', $variant) }}"
                       class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg border border-[#FFD8D8]/60 bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#8E1B5C] transition-all duration-200 ease-out shadow-2xs shrink-0 whitespace-nowrap cursor-pointer self-start">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Edit Tarif</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Biaya Printing Standar --}}
                    <div class="bg-[#FFFCFB] p-3.5 rounded-xl border border-[#FFD8D8]/80 flex flex-col justify-between min-w-0">
                        <span class="text-xs font-semibold text-[#64748B] block">Biaya Printing Standar</span>
                        <div class="mt-1.5">
                            @if($variant->requires_printing)
                                @if($variant->printing_cost_per_unit !== null)
                                    <p class="text-sm font-bold text-[#172B4D] font-mono">
                                        Rp {{ number_format((float) $variant->printing_cost_per_unit, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B] font-sans">/ pcs</span>
                                    </p>
                                @else
                                    <span class="text-xs text-[#64748B] italic font-normal">Belum ditentukan</span>
                                @endif
                            @else
                                <p class="text-sm font-bold text-[#172B4D] font-mono">
                                    Rp 0 <span class="text-xs font-normal text-[#64748B] font-sans">/ pcs</span>
                                </p>
                                <span class="text-[11px] text-[#64748B] block mt-0.5">Tanpa printing</span>
                            @endif
                        </div>
                    </div>

                    {{-- Biaya Tenaga Kerja Standar --}}
                    <div class="bg-[#FFFCFB] p-3.5 rounded-xl border border-[#FFD8D8]/80 flex flex-col justify-between min-w-0">
                        <span class="text-xs font-semibold text-[#64748B] block">Biaya Tenaga Kerja Standar</span>
                        <div class="mt-1.5">
                            @if($variant->direct_labor_cost_per_unit !== null)
                                <p class="text-sm font-bold text-[#172B4D] font-mono">
                                    Rp {{ number_format((float) $variant->direct_labor_cost_per_unit, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B] font-sans">/ pcs</span>
                                </p>
                            @else
                                <span class="text-xs text-[#64748B] italic font-normal">Belum ditentukan</span>
                            @endif
                        </div>
                    </div>

                    {{-- Biaya Packing Standar (Proses/Jasa) --}}
                    <div class="bg-[#FFFCFB] p-3.5 rounded-xl border border-[#FFD8D8]/80 flex flex-col justify-between min-w-0">
                        <span class="text-xs font-semibold text-[#64748B] block">Biaya Packing Standar (Proses)</span>
                        <div class="mt-1.5">
                            @if($variant->packaging_process_cost_per_good_unit !== null)
                                <p class="text-sm font-bold text-[#172B4D] font-mono">
                                    Rp {{ number_format((float) $variant->packaging_process_cost_per_good_unit, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B] font-sans">/ pcs</span>
                                </p>
                            @else
                                <span class="text-xs text-[#64748B] italic font-normal">Belum ditentukan</span>
                            @endif
                            <span class="text-[10px] text-[#64748B] block mt-0.5">Biaya jasa pengemasan (material seperti plastik/label dicatat pada BOM)</span>
                        </div>
                    </div>

                    {{-- Biaya Produksi Tambahan --}}
                    <div class="bg-[#FFFCFB] p-3.5 rounded-xl border border-[#FFD8D8]/80 flex flex-col justify-between min-w-0">
                        <span class="text-xs font-semibold text-[#64748B] block">Biaya Produksi Tambahan</span>
                        <div class="mt-1.5">
                            @if($variant->other_direct_cost_per_unit !== null)
                                <p class="text-sm font-bold text-[#172B4D] font-mono">
                                    Rp {{ number_format((float) $variant->other_direct_cost_per_unit, 0, ',', '.') }} <span class="text-xs font-normal text-[#64748B] font-sans">/ pcs</span>
                                </p>
                            @else
                                <span class="text-xs text-[#64748B] italic font-normal">Belum ditentukan</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section C: BOM Section Card --}}
            <div class="xl:col-span-7 bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card min-w-0">
                <div class="p-4 sm:p-5 border-b border-[#FFD8D8] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-1 min-w-0">
                        <h2 class="text-sm font-bold text-[#172B4D]">Bill of Materials (BOM)</h2>
                        <p class="text-xs text-[#64748B] leading-relaxed">
                            BOM menentukan kebutuhan bahan untuk menghasilkan 1 pcs varian. Kebutuhan batch dihitung otomatis dari BOM × target produksi.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 flex-wrap">
                        <button type="button"
                                @click="showApplyTemplateModal = true; selectedTemplateId = templates.length > 0 ? templates[0].id : ''; replaceExisting = false"
                                class="inline-flex items-center justify-center gap-1.5 h-9 px-3 rounded-lg border border-[#FFD8D8]/60 bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#8E1B5C] transition-all duration-200 ease-out shadow-2xs cursor-pointer shrink-0 whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                            </svg>
                            <span>Terapkan Template</span>
                        </button>
                        <button type="button"
                                @click="showAddBomModal = true"
                                class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer shrink-0 whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Tambah Material</span>
                        </button>
                    </div>
                </div>

                @php
                    $hasCorruptBomInBlade = config('nayara.tenant_isolation_enabled', false)
                        && $variant->outlet_id
                        && $variant->bomItems->contains(function ($item) use ($variant) {
                            return ! $item->material || (int) $item->material->outlet_id !== (int) $variant->outlet_id;
                        });
                @endphp
                @if($hasCorruptBomInBlade)
                    <div class="p-3.5 m-4 mb-0 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-2.5 text-amber-900 text-xs">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <strong class="font-bold block">Peringatan Integritas BOM</strong>
                            <span>Integritas BOM tidak valid. Perhitungan biaya belum dapat digunakan sampai data BOM diverifikasi.</span>
                        </div>
                    </div>
                @endif

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                                <th class="px-4 py-3 text-center whitespace-nowrap w-12">#</th>
                                <th class="px-4 py-3 min-w-[180px]">Material Bahan Baku</th>
                                <th class="px-4 py-3 whitespace-nowrap w-24">Satuan</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap w-36">Kebutuhan</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]/70">
                            @forelse($variant->bomItems as $index => $item)
                                <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/40">
                                    <td class="px-4 py-3 text-center font-mono text-[#64748B] whitespace-nowrap">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-semibold text-xs text-[#172B4D] leading-snug">
                                        @if(config('nayara.tenant_isolation_enabled', false) && $variant->outlet_id && (! $item->material || (int) $item->material->outlet_id !== (int) $variant->outlet_id))
                                            <span class="text-amber-700 italic font-normal">[Integritas Tenant Tidak Valid: Material Tidak Sah]</span>
                                        @else
                                            {{ $item->material?->material_name ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-[#64748B] whitespace-nowrap">
                                        @if(config('nayara.tenant_isolation_enabled', false) && $variant->outlet_id && (! $item->material || (int) $item->material->outlet_id !== (int) $variant->outlet_id))
                                            —
                                        @else
                                            {{ $item->material->unit->unit_name ?? $item->material->unit->symbol ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <template x-if="editingBomId !== {{ $item->id }}">
                                            <div class="inline-flex items-baseline justify-end gap-1.5 font-mono">
                                                <span class="text-sm font-bold text-[#172B4D]">{{ \App\Support\QuantityFormatter::format($item->qty_per_unit) }}</span>
                                                <span class="text-xs font-normal text-[#64748B] font-sans">{{ $item->material->unit->symbol ?? $item->material->unit->unit_name ?? '' }}</span>
                                            </div>
                                        </template>

                                        {{-- Quick Edit Inline Form --}}
                                        <template x-if="editingBomId === {{ $item->id }}">
                                            <form method="POST" action="{{ route('admin.variants.bom.update', [$variant, $item]) }}" class="flex items-center justify-end gap-1.5">
                                                @csrf
                                                @method('PUT')
                                                <input type="number"
                                                       step="0.0001"
                                                       min="0.0001"
                                                       name="qty_per_unit"
                                                       x-model="editingQty"
                                                       required
                                                       class="w-24 h-8 px-2 bg-white border border-[#8E1B5C] rounded-lg text-xs font-mono text-right focus:outline-none">
                                                <button type="submit" class="h-8 px-2.5 bg-[#8E1B5C] text-white text-[11px] font-bold rounded-lg hover:bg-[#8E1B5C] transition-colors">Simpan</button>
                                                <button type="button" @click="editingBomId = null" class="h-8 px-2 bg-gray-100 text-gray-700 text-[11px] rounded-lg hover:bg-gray-200 border border-gray-300 transition-colors">Batal</button>
                                            </form>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button"
                                                    @click="editingBomId = {{ $item->id }}; editingQty = '{{ (float) $item->qty_per_unit }}'"
                                                    class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                                    title="Edit Kuantitas"
                                                    aria-label="Edit Kuantitas">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            @php
                                                $isCorruptBomItem = config('nayara.tenant_isolation_enabled', false) && $variant->outlet_id && (! $item->material || (int) $item->material->outlet_id !== (int) $variant->outlet_id);
                                                $safeDeleteMatName = $isCorruptBomItem ? '[Integritas Tenant Tidak Valid: Material Tidak Sah]' : ($item->material?->material_name ?? 'Material');
                                            @endphp
                                            <button type="button"
                                                    @click="deleteBomRoute = '{{ route('admin.variants.bom.destroy', [$variant, $item]) }}'; deleteMaterialName = '{{ addslashes($safeDeleteMatName) }}'; showDeleteBomModal = true"
                                                    class="w-8 h-8 rounded-lg bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs"
                                                    title="Hapus Material"
                                                    aria-label="Hapus Material">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-[#172B4D] text-sm">Belum ada Material BOM</p>
                                    <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">Tambahkan bahan yang dibutuhkan untuk memproduksi 1 unit varian ini.</p>
                                    <div class="mt-4">
                                        <button type="button"
                                                @click="showAddBomModal = true"
                                                class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Tambah Material</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        {{-- Add Material Modal --}}
        <div x-show="showAddBomModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showAddBomModal = false" class="bg-white rounded-2xl p-5 sm:p-6 max-w-md w-full shadow-2xl space-y-4 border border-[#FFD8D8]">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                    <h3 class="font-bold text-sm text-[#172B4D]">Tambah Material ke BOM</h3>
                    <button type="button" @click="showAddBomModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.variants.bom.store', $variant) }}" class="space-y-4">
                    @csrf

                    <div class="space-y-1.5">
                        <label for="material_id" class="block text-xs font-semibold text-[#172B4D]">
                            Pilih Material <span class="text-red-500">*</span>
                        </label>
                        <select name="material_id"
                                id="material_id"
                                required
                                class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                            <option value="">-- Pilih Material Aktif --</option>
                            @php
                                $tenantMaterials = (config('nayara.tenant_isolation_enabled', false) && $variant->outlet_id)
                                    ? $materials->filter(fn ($m) => (int) $m->outlet_id === (int) $variant->outlet_id)
                                    : $materials;
                            @endphp
                            @foreach($tenantMaterials as $material)
                                <option value="{{ $material->id }}">
                                    {{ $material->material_name }} ({{ $material->unit->symbol ?? $material->unit->unit_name ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="qty_per_unit" class="block text-xs font-semibold text-[#172B4D]">
                            Kuantitas per 1 Unit Varian <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               step="0.0001"
                               min="0.0001"
                               name="qty_per_unit"
                               id="qty_per_unit"
                               placeholder="Contoh: 1.1500"
                               required
                               class="w-full h-10 px-3 text-xs font-mono border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                        <p class="text-[11px] text-[#64748B]">Nilai kuantitas bahan baku harus lebih besar dari 0.</p>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                        <button type="button" @click="showAddBomModal = false" class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all cursor-pointer">
                            Tambah Material
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete BOM Confirmation Modal --}}
        <div x-show="showDeleteBomModal"
             class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
             x-cloak>
            <div class="bg-white border border-[#FFD8D8] rounded-2xl max-w-md w-full p-5 sm:p-6 space-y-4 shadow-xl animate-fade-in"
                 @click.outside="showDeleteBomModal = false">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-[#172B4D]">Hapus Material BOM?</h3>
                        <p class="text-xs text-[#64748B] leading-relaxed">
                            Material <strong class="text-[#172B4D]" x-text="deleteMaterialName"></strong> akan dihapus dari resep varian ini. Tindakan ini tidak menghapus master material.
                        </p>
                    </div>
                </div>

                <form :action="deleteBomRoute" method="POST" class="flex items-center justify-end gap-2 pt-3 border-t border-[#FFD8D8]">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                            @click="showDeleteBomModal = false"
                            class="inline-flex items-center justify-center h-9 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                        Batal
                    </button>
                    <button type="submit"
                            class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 transition-all duration-200 cursor-pointer shadow-xs">
                        Hapus Material
                    </button>
                </form>
            </div>
        </div>

        {{-- Apply BOM Template Modal --}}
        <div x-show="showApplyTemplateModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showApplyTemplateModal = false" class="bg-white rounded-2xl p-5 sm:p-6 max-w-lg w-full shadow-2xl space-y-4 border border-[#FFD8D8]">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                    <div class="space-y-0.5">
                        <h3 class="font-bold text-sm text-[#172B4D]">Terapkan Template BOM</h3>
                        <p class="text-[11px] text-[#64748B]">Salin resep bahan baku dari template yang disetujui untuk produk ini.</p>
                    </div>
                    <button type="button" @click="showApplyTemplateModal = false" class="text-[#64748B] hover:text-[#172B4D] text-sm cursor-pointer">✕</button>
                </div>

                <template x-if="templates.length === 0">
                    <div class="py-6 text-center space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-[#172B4D]">Belum Ada Template BOM Aktif untuk Produk Ini</p>
                        <p class="text-[11px] text-[#64748B] max-w-xs mx-auto">Buat template BOM terlebih dahulu melalui menu BOM Template pada katalog produk.</p>
                        <div>
                            <a href="{{ route('admin.bom-templates.create', ['product_id' => $variant->product_id]) }}" class="inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all cursor-pointer">
                                Buat Template BOM Baru
                            </a>
                        </div>
                    </div>
                </template>

                <template x-if="templates.length > 0">
                    <form method="POST" action="{{ route('admin.variants.bom.apply-template', $variant) }}" class="space-y-4">
                        @csrf

                        {{-- Select Template --}}
                        <div class="space-y-1.5">
                            <label for="apply_bom_template_id" class="block text-xs font-semibold text-[#172B4D]">
                                Pilih Template BOM <span class="text-red-500">*</span>
                            </label>
                            <select name="bom_template_id"
                                    id="apply_bom_template_id"
                                    x-model="selectedTemplateId"
                                    required
                                    class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                                <template x-for="tpl in templates" :key="tpl.id">
                                    <option :value="tpl.id" x-text="tpl.name"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Live Local Preview Table --}}
                        <div class="space-y-1.5">
                            <span class="block text-xs font-semibold text-[#172B4D]">Preview Resep Material</span>
                            <div class="border border-[#FFD8D8] rounded-lg overflow-hidden max-h-48 overflow-y-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[10px] font-bold text-[#64748B] uppercase">
                                        <tr>
                                            <th class="px-3 py-2">Material</th>
                                            <th class="px-3 py-2">Satuan</th>
                                            <th class="px-3 py-2 text-right">Kebutuhan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#FFD8D8]/60">
                                        <template x-for="item in (selectedTemplate ? selectedTemplate.items : [])" :key="item.material_name">
                                            <tr class="hover:bg-[#FFD8D8]/30">
                                                <td class="px-3 py-2 text-xs font-medium text-[#172B4D]" x-text="item.material_name"></td>
                                                <td class="px-3 py-2 text-xs text-[#64748B]" x-text="item.unit"></td>
                                                <td class="px-3 py-2 text-xs text-right font-mono font-bold text-[#172B4D]" x-text="item.qty_formatted"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Replacement Warning & Checkbox (when existing BOM > 0) --}}
                        <template x-if="hasExistingBom">
                            <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl space-y-2.5">
                                <div class="flex items-start gap-2 text-amber-800 text-xs leading-relaxed">
                                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <div>
                                        <strong class="font-bold block">Peringatan: Varian Sudah Memiliki BOM</strong>
                                        <span>Menerapkan template akan menghapus seluruh resep BOM varian saat ini dan menggantikannya dengan resep template yang dipilih.</span>
                                    </div>
                                </div>
                                <label class="flex items-start gap-2 cursor-pointer pt-1 border-t border-amber-200/80">
                                    <input type="checkbox"
                                           name="replace_existing"
                                           value="1"
                                           x-model="replaceExisting"
                                           required
                                           class="mt-0.5 w-4 h-4 rounded text-[#8E1B5C] focus:ring-[#8E1B5C] border-amber-300">
                                    <span class="text-xs font-semibold text-amber-900">Saya mengerti dan menyetujui untuk mengganti seluruh resep BOM varian ini dengan resep template.</span>
                                </label>
                            </div>
                        </template>

                        <div class="pt-3 flex items-center justify-end gap-2.5 border-t border-[#FFD8D8]">
                            <button type="button" @click="showApplyTemplateModal = false" class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    :disabled="hasExistingBom && !replaceExisting"
                                    class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shadow-xs">
                                Terapkan Template
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>
