<x-app-layout pageTitle="Dashboard Gudang" breadcrumb="Overview > Dashboard Gudang">
    <div class="space-y-5 sm:space-y-6">

        {{-- NAYARA Gudang Hero Banner --}}
        <x-dashboard-hero
            role-label="Gudang & Logistik"
            title="Dashboard Gudang"
            description="Selamat Datang, {{ auth()->user()->name }} · Ringkasan stok bahan baku dan pengadaan · Total Supplier: {{ $totalSuppliers }} vendor"
            action-label="Tambah Material"
            :action-route="route('gudang.materials.create')"
            action-icon="plus"
        />

        {{-- Metric Overview Cards (5 Normalized Operational Cards) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3.5 items-stretch">
            {{-- 1. Total Material --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Total Material</p>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-[#172B4D] font-mono tracking-tight leading-none">{{ $totalMaterials }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Bahan & aksesoris</p>
                </div>
            </div>

            {{-- 2. Stok Aman --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Stok Aman</p>
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-emerald-700 font-mono tracking-tight leading-none">{{ $safeStockCount }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Di atas batas minimum</p>
                </div>
            </div>

            {{-- 3. Stok Rendah --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Stok Rendah</p>
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-amber-700 font-mono tracking-tight leading-none">{{ $lowStockCount }}</p>
                    </div>
                    <p class="text-[11px] text-amber-700 font-medium mt-1.5 truncate">Perlu restock segera</p>
                </div>
            </div>

            {{-- 4. Stok Habis --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Stok Habis</p>
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center shrink-0 border border-rose-200/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-rose-700 font-mono tracking-tight leading-none">{{ $outOfStockCount }}</p>
                    </div>
                    <p class="text-[11px] text-rose-700 font-medium mt-1.5 truncate">Stok 0 (kritis)</p>
                </div>
            </div>

            {{-- 5. Total Supplier --}}
            <div class="h-full bg-white border border-[#FFD8D8] rounded-2xl p-4 flex flex-col justify-between shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Total Supplier</p>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-[#172B4D] font-mono tracking-tight leading-none">{{ $totalSuppliers }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Mitra terdaftar</p>
                </div>
            </div>
        </div>

        {{-- Action Queue: PERLU TINDAKAN --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card space-y-3">
            <div class="flex items-center justify-between pb-2.5 border-b border-[#FFD8D8]">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#8E1B5C]"></span>
                    <h2 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Perlu Tindakan</h2>
                </div>
                @if(!empty($totalActionablePurchases) && $totalActionablePurchases > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        {{ $totalActionablePurchases }} item
                    </span>
                @endif
            </div>

            @if(empty($actionablePurchases) || $actionablePurchases->isEmpty())
                <div class="p-4 rounded-xl bg-[#FFFCFB] border border-[#FFD8D8] text-xs text-[#64748B] text-center">
                    Tidak ada tindakan yang perlu ditangani.
                </div>
            @else
                <div class="divide-y divide-[#FFD8D8] border border-[#FFD8D8] rounded-xl overflow-hidden bg-[#FFFCFB]/70">
                    @foreach($actionablePurchases as $item)
                        <div class="px-4 py-3 sm:py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-white transition-colors duration-150 min-h-[58px]">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $item['badge_class'] }} shrink-0">
                                    {{ $item['status_label'] }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ $item['action_url'] }}" class="font-mono font-bold text-xs text-[#172B4D] hover:text-[#8E1B5C] hover:underline">
                                            {{ $item['purchase_no'] ?? $item['reference'] }}
                                        </a>
                                        <span class="text-xs text-[#64748B] truncate">
                                            &middot; {{ $item['supplier_name'] ?? $item['subtitle'] }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-[#64748B] mt-1">
                                        Tanggal: {{ $item['date'] }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center self-end sm:self-auto shrink-0">
                                <a href="{{ $item['action_url'] }}"
                                   class="inline-flex items-center justify-center min-w-[76px] h-8 px-3.5 rounded-lg text-xs font-semibold bg-[#68113F] text-white hover:bg-[#520C31] transition shadow-xs">
                                    {{ $item['action_label'] }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Material List Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-[#FFD8D8] flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Material Terbaru</h2>
                    <p class="text-xs text-[#64748B] mt-0.5">Daftar bahan baku dan status persediaan terkini</p>
                </div>
                <a href="{{ route('gudang.materials.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200">
                    <span>Lihat Semua</span>
                    <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#172B4D]">
                    <thead class="bg-[#FFFCFB] text-[11px] font-semibold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8]">
                        <tr>
                            <th class="py-3 px-4">Nama Material</th>
                            <th class="py-3 px-4">Satuan</th>
                            <th class="py-3 px-4 text-right">Stok Saat Ini</th>
                            <th class="py-3 px-4 text-right">Stok Minimum</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($recentMaterials as $material)
                            <tr class="hover:bg-[#FFFCFB] transition-colors">
                                <td class="py-3 px-4 font-semibold text-[#172B4D]">
                                    <a href="{{ route('gudang.materials.show', $material) }}" class="hover:text-[#8E1B5C] hover:underline">
                                        {{ $material->material_name }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-[#64748B]">
                                    {{ $material->unit?->unit_name }} ({{ $material->unit?->symbol }})
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums font-bold text-[#172B4D]">
                                    {{ $material->formatted_qty }} {{ $material->unit?->symbol }}
                                </td>
                                <td class="py-3 px-4 text-right tabular-nums text-[#64748B]">
                                    {{ $material->formatted_min_stock }} {{ $material->unit?->symbol }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="badge {{ $material->stock_status_badge }} text-[10px]">
                                        {{ $material->stock_status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('gudang.materials.show', $material) }}"
                                       class="inline-flex items-center justify-center h-7 px-3 rounded-lg border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-xs">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-xs text-[#64748B]">
                                    Belum ada data material.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
