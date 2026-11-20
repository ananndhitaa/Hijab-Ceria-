@php
    $role = auth()->user()->role;
    $currentRoute = request()->route()?->getName() ?? '';
    $linkBaseClass = "group flex items-center gap-2.5 h-10 px-3 rounded-lg text-xs sm:text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white transition-all duration-150 ease-out cursor-pointer select-none";
    $activeClass = "bg-white/15 text-white font-semibold bg-gradient-to-r from-[#ba4e77] to-[#993059] font-bold shadow-md hover:from-[#ba4e77] hover:to-[#993059] hover:text-white";
@endphp

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="nayara-erp-sidebar fixed inset-y-0 left-0 z-40 w-64 text-white flex flex-col h-screen h-dvh shrink-0 -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-xl lg:shadow-none border-r border-white/10 overflow-hidden"
       style="background: linear-gradient(166deg, #691b3d 0%, #4a132e 49%, #260b20 100%);">

    {{-- Subtle Watermark Star (seamless decorative watermark, no box background) --}}
    <div class="nayara-sidebar-watermark-star" aria-hidden="true">✦</div>

    {{-- Section A: Brand / Logo Header --}}
    <div class="nayara-sidebar-brand h-[76px] flex items-center justify-between px-5 border-b border-white/10 shrink-0 relative z-10">
        <div class="flex items-center gap-3 min-w-0">
            @if($role === \App\Enums\UserRole::SUPER_ADMIN)
                <img src="{{ asset('images/nayara-logo.jpg') }}" alt="NAYARA GROUP" class="w-10 h-10 rounded-xl object-contain shadow-xs shrink-0 border border-white/20">
                <div class="space-y-0.5 min-w-0">
                    <h2 class="font-bold text-base tracking-widest text-white leading-none font-serif truncate">NAYARA</h2>
                    <p class="text-[9px] text-[#ffd8d8] tracking-widest font-semibold uppercase leading-none">PLATFORM ADMIN</p>
                </div>
            @else
                @php
                    $tenantOutlet = auth()->user()->outlet;
                    $tenantLogo = $tenantOutlet?->logo_path;
                    $hasCustomTenantLogo = $tenantLogo && file_exists(public_path('storage/' . $tenantLogo));
                @endphp
                @if($hasCustomTenantLogo)
                    <img src="{{ asset('storage/' . $tenantLogo) }}" alt="{{ $tenantOutlet->outlet_name ?? 'Tenant' }}" class="w-10 h-10 rounded-xl object-cover shadow-xs shrink-0 border border-white/20">
                @else
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center font-bold text-white text-lg shrink-0 shadow-xs" aria-hidden="true">
                        ✦
                    </div>
                @endif
                <div class="space-y-0.5 min-w-0">
                    <h2 class="font-bold text-base tracking-widest text-white leading-none font-serif truncate">
                        {{ $tenantOutlet?->outlet_name ? (str_contains($tenantOutlet->outlet_name, 'NAYARA') ? 'NAYARA' : $tenantOutlet->outlet_name) : 'NAYARA' }}
                    </h2>
                    <p class="text-[9px] text-[#ffd8d8] tracking-widest font-semibold uppercase leading-none">PRODUCTION &amp; MRP</p>
                </div>
            @endif
        </div>
        <button type="button" @click="sidebarOpen = false" class="lg:hidden text-white/70 hover:text-white p-1 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Section B: Admin Profile Card --}}
    <div class="px-4 pt-3 pb-1 shrink-0 relative z-10">
        <div class="p-3 bg-white/10 rounded-xl border border-white/15 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shadow-xs shrink-0 text-white"
                 style="background: linear-gradient(145deg, #aa255b, #721743);">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="overflow-hidden space-y-0.5">
                <p class="text-sm sm:text-base font-semibold text-white truncate leading-tight">{{ auth()->user()->name }}</p>
                <span class="inline-block text-xs font-semibold text-[#68113F] bg-[#FFD8D8] px-2 py-0.5 rounded leading-tight">
                    {{ $role->label() }}
                </span>
            </div>
        </div>
    </div>

    {{-- Section C: Navigation Menu --}}
    <nav id="nayara-sidebar-nav" class="flex-1 min-h-0 overflow-y-auto px-3 py-2 space-y-1 relative z-10">

        {{-- ── ADMIN SIDEBAR ── --}}
        @if($role === \App\Enums\UserRole::ADMIN)
            <div class="text-[10px] font-semibold text-[#FFD8D8] uppercase px-3 pt-2 pb-1 tracking-wider select-none">Dashboard</div>
            <a href="{{ route('admin.dashboard') }}"
               class="{{ $linkBaseClass }} {{ request()->routeIs('admin.dashboard') ? $activeClass : '' }}">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="truncate">Dashboard</span>
            </a>

            {{-- Kategori: Pesanan --}}
            @php
                $isPesananActive = request()->routeIs('admin.orders.*', 'admin.kanban.*', 'admin.batches.*');
            @endphp
            <div x-data="{ open: {{ $isPesananActive ? 'true' : "localStorage.getItem('nayara_sb_adm_pesanan') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_adm_pesanan', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Pesanan</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('admin.orders.index') }}"
                       class="{{ $linkBaseClass }} {{ (request()->routeIs('admin.orders.*') && ! request()->routeIs('admin.orders.batches.*')) ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="truncate">Sales Order</span>
                    </a>
                    <a href="{{ route('admin.kanban.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('admin.kanban.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        <span class="truncate">Kanban Pesanan</span>
                    </a>
                    <a href="{{ route('admin.batches.index') }}"
                       class="{{ $linkBaseClass }} {{ (request()->routeIs('admin.batches.*') || request()->routeIs('admin.orders.batches.*')) ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span class="truncate">Batch Produksi</span>
                    </a>
                </div>
            </div>

            {{-- Kategori: Master Data --}}
            @php
                $isMasterActive = request()->routeIs('admin.customers.*', 'admin.products.*', 'admin.variants.*', 'admin.bom-templates.*');
            @endphp
            <div x-data="{ open: {{ $isMasterActive ? 'true' : "localStorage.getItem('nayara_sb_adm_master') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_adm_master', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Master Data</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('admin.customers.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('admin.customers.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="truncate">Customer</span>
                    </a>
                    <a href="{{ route('admin.products.index') }}"
                       class="{{ $linkBaseClass }} {{ (request()->routeIs('admin.products.*') || request()->routeIs('admin.variants.*')) ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span class="truncate">Produk & BOM</span>
                    </a>
                </div>
            </div>

            {{-- Kategori: Keuangan Admin --}}
            @php
                $isKeuanganActive = request()->routeIs('admin.purchases.approvals.*', 'admin.financial-records.*', 'admin.invoices.*', 'admin.payments.*', 'admin.reports.sales*');
            @endphp
            <div x-data="{ open: {{ $isKeuanganActive ? 'true' : "localStorage.getItem('nayara_sb_adm_keuangan') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_adm_keuangan', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Keuangan Admin</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('admin.purchases.approvals.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('admin.purchases.approvals.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="truncate">Persetujuan PO</span>
                    </a>
                    <a href="{{ route('admin.financial-records.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('admin.financial-records.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="truncate">Catatan Keuangan</span>
                    </a>
                    <a href="{{ route('admin.invoices.index') }}"
                       class="{{ $linkBaseClass }} {{ (request()->routeIs('admin.invoices.*') || request()->routeIs('admin.payments.*')) ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="truncate">Invoice & Tagihan</span>
                    </a>
                    <a href="{{ route('admin.reports.sales') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('admin.reports.sales*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="truncate">Laporan Penjualan</span>
                    </a>
                </div>
            </div>
        @endif

        {{-- ── GUDANG SIDEBAR ── --}}
        @if($role === \App\Enums\UserRole::GUDANG)
            <div class="text-[10px] font-semibold text-[#FFD8D8] uppercase px-3 pt-2 pb-1 tracking-wider select-none">Dashboard</div>
            <a href="{{ route('gudang.dashboard') }}"
               class="{{ $linkBaseClass }} {{ request()->routeIs('gudang.dashboard') ? $activeClass : '' }}">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="truncate">Dashboard</span>
            </a>

            {{-- Kategori: Material & Stok --}}
            @php
                $isMaterialActive = request()->routeIs('gudang.materials.*', 'gudang.units.*', 'gudang.material-planning.*', 'gudang.stock-movements.*');
            @endphp
            <div x-data="{ open: {{ $isMaterialActive ? 'true' : "localStorage.getItem('nayara_sb_gdg_material') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_gdg_material', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Material &amp; Stok</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('gudang.materials.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('gudang.materials.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span class="truncate">Material</span>
                    </a>
                    <a href="{{ route('gudang.units.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('gudang.units.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        <span class="truncate">Satuan</span>
                    </a>
                    <a href="{{ route('gudang.material-planning.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('gudang.material-planning.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="truncate">Perencanaan Bahan</span>
                    </a>
                    <a href="{{ route('gudang.stock-movements.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('gudang.stock-movements.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                        <span class="truncate">Riwayat Mutasi Stok</span>
                    </a>
                </div>
            </div>

            {{-- Kategori: Pembelian --}}
            @php
                $isPembelianActive = request()->routeIs('gudang.suppliers.*', 'gudang.purchases.*');
            @endphp
            <div x-data="{ open: {{ $isPembelianActive ? 'true' : "localStorage.getItem('nayara_sb_gdg_pembelian') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_gdg_pembelian', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Pembelian</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('gudang.suppliers.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('gudang.suppliers.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span class="truncate">Supplier</span>
                    </a>
                    <a href="{{ route('gudang.purchases.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('gudang.purchases.index', 'gudang.purchases.show', 'gudang.purchases.create', 'gudang.purchases.edit') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span class="truncate">Purchase Order</span>
                    </a>
                    <a href="{{ route('gudang.purchases.history') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('gudang.purchases.history') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="truncate">Riwayat Pembelian</span>
                    </a>
                </div>
            </div>
        @endif

        {{-- ── PRODUKSI SIDEBAR ── --}}
        @if($role === \App\Enums\UserRole::PRODUKSI)
            <div class="text-[10px] font-semibold text-[#FFD8D8] uppercase px-3 pt-2 pb-1 tracking-wider select-none">Main</div>
            <a href="{{ route('produksi.dashboard') }}"
               class="{{ $linkBaseClass }} {{ request()->routeIs('produksi.dashboard') ? $activeClass : '' }}">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="truncate">Dashboard Produksi</span>
            </a>

            {{-- Kategori: Operasional Produksi --}}
            @php
                $isOpsProduksiActive = request()->routeIs('produksi.batches.*', 'produksi.history');
            @endphp
            <div x-data="{ open: {{ $isOpsProduksiActive ? 'true' : "localStorage.getItem('nayara_sb_prd_ops') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_prd_ops', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Operasional Produksi</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('produksi.batches.index') }}"
                       class="{{ $linkBaseClass }} {{ (request()->routeIs('produksi.batches.*') && !request()->routeIs('produksi.history')) ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span class="truncate">Batch Produksi</span>
                    </a>
                    <a href="{{ route('produksi.history') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('produksi.history') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="truncate">Riwayat Pekerjaan</span>
                    </a>
                </div>
            </div>

            {{-- Kategori: Pengiriman --}}
            @php
                $isShipmentsActive = request()->routeIs('produksi.shipments.*');
            @endphp
            <div x-data="{ open: {{ $isShipmentsActive ? 'true' : "localStorage.getItem('nayara_sb_prd_kirim') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_prd_kirim', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Pengiriman</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('produksi.shipments.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('produksi.shipments.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        <span class="truncate">Pengiriman</span>
                    </a>
                </div>
            </div>
        @endif

        {{-- ── OWNER SIDEBAR ── --}}
        @if($role === \App\Enums\UserRole::OWNER)
            {{-- Kategori: Executive Monitoring --}}
            @php
                $isOwnerMonitoringActive = request()->routeIs('owner.dashboard', 'owner.hpp.*', 'owner.profitability.*', 'owner.performance.*', 'owner.financial-summary.*', 'owner.employee-performance.*');
            @endphp
            <div x-data="{ open: {{ $isOwnerMonitoringActive ? 'true' : "localStorage.getItem('nayara_sb_own_mon') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_own_mon', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Executive Monitoring</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('owner.dashboard') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.dashboard') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span class="truncate">Dashboard</span>
                    </a>
                    <a href="{{ route('owner.hpp.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.hpp.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="truncate">HPP Produksi</span>
                    </a>
                    <a href="{{ route('owner.profitability.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.profitability.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="truncate">Profitabilitas Pesanan</span>
                    </a>
                    <a href="{{ route('owner.performance.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.performance.*', 'owner.financial-summary.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span class="truncate">Ringkasan Keuangan</span>
                    </a>
                    <a href="{{ route('owner.employee-performance.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.employee-performance.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        <span class="truncate">Performa Karyawan</span>
                    </a>
                </div>
            </div>

            {{-- Kategori: Manajemen --}}
            @php
                $isOwnerMgmtActive = request()->routeIs('owner.opening-cash.*', 'owner.employees.*', 'owner.activity-logs.*');
            @endphp
            <div x-data="{ open: {{ $isOwnerMgmtActive ? 'true' : "localStorage.getItem('nayara_sb_own_mgmt') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_own_mgmt', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Manajemen</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    @if(config('nayara.tenant_isolation_enabled', false))
                    <a href="{{ route('owner.opening-cash.create') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.opening-cash.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="truncate">Saldo Awal Kas</span>
                    </a>
                    @endif
                    <a href="{{ route('owner.employees.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.employees.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                        <span class="truncate">Karyawan</span>
                    </a>
                    <a href="{{ route('owner.activity-logs.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.activity-logs.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="truncate">Log Aktivitas</span>
                    </a>
                </div>
            </div>

            {{-- Kategori: NAYARA SaaS & Monetisasi --}}
            @php
                $isOwnerSaasActive = request()->routeIs('owner.publications.*', 'owner.subscriptions.*', 'owner.ads.*', 'owner.analytics.*', 'owner.services.*', 'owner.notifications.*', 'owner.complaints.*');
            @endphp
            <div x-data="{ open: {{ $isOwnerSaasActive ? 'true' : "localStorage.getItem('nayara_sb_own_saas') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_own_saas', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">NAYARA SaaS &amp; Monetisasi</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('owner.publications.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.publications.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span class="truncate">Etalase Publik</span>
                    </a>
                    <a href="{{ route('owner.subscriptions.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.subscriptions.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span class="truncate">Langganan & Slot</span>
                    </a>
                    <a href="{{ route('owner.ads.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.ads.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span class="truncate">Iklan & Promosi</span>
                    </a>
                    @php
                        $sidebarOutletId = auth()->user()?->outlet_id;
                        $hasAnalyticsEntitlement = $sidebarOutletId
                            ? app(\App\Services\Platform\TenantEntitlementService::class)->hasFinancialAnalyticsEntitlement((int) $sidebarOutletId)
                            : false;
                    @endphp
                    <a href="{{ route('owner.analytics.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.analytics.*') ? $activeClass : '' }} justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                            <span class="whitespace-nowrap text-xs sm:text-[13px]">Financial Analytics</span>
                        </div>
                        @if(!$hasAnalyticsEntitlement)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-white/10 text-[#FFD8D8] border border-white/15 shrink-0" title="Fitur Premium / Add-On">
                                <svg class="w-2.5 h-2.5 text-[#FFD8D8]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                                <span>PRO</span>
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('owner.services.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.services.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span class="truncate">Layanan & Pelatihan</span>
                    </a>
                    <a href="{{ route('owner.notifications.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.notifications.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="truncate">Notifikasi Platform</span>
                    </a>
                    <a href="{{ route('owner.complaints.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('owner.complaints.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span class="truncate">Keluhan &amp; Bantuan</span>
                    </a>
                </div>
            </div>
        @endif

        {{-- ── SUPER ADMIN SIDEBAR ── --}}
        @if($role === \App\Enums\UserRole::SUPER_ADMIN)
            {{-- Kategori: Platform Otoritas --}}
            @php
                $isPlatformOtoritasActive = request()->routeIs('platform.dashboard', 'platform.registrations.*', 'platform.tenants.*');
            @endphp
            <div x-data="{ open: {{ $isPlatformOtoritasActive ? 'true' : "localStorage.getItem('nayara_sb_sa_otoritas') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_sa_otoritas', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Platform Otoritas</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('platform.dashboard') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('platform.dashboard') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span class="truncate">Platform Dashboard</span>
                    </a>
                    <a href="{{ route('platform.registrations.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('platform.registrations.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="truncate">Review Registrasi</span>
                    </a>
                    <a href="{{ route('platform.tenants.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('platform.tenants.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span class="truncate">Direktori Tenant</span>
                    </a>
                </div>
            </div>

            {{-- Kategori: Monetisasi & Iklan --}}
            @php
                $isMonetisasiActive = request()->routeIs('platform.billing.*', 'platform.ads.*', 'platform.refunds.*', 'platform.analytics.*', 'platform.services.*', 'platform.audit.*', 'platform.system.*');
            @endphp
            <div x-data="{ open: {{ $isMonetisasiActive ? 'true' : "localStorage.getItem('nayara_sb_sa_monetisasi') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_sa_monetisasi', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Monetisasi &amp; Iklan</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('platform.billing.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('platform.billing.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                        <span class="truncate">Verifikasi Tagihan</span>
                    </a>
                    <a href="{{ route('platform.ads.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('platform.ads.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span class="truncate">Moderasi Iklan</span>
                    </a>
                    <a href="{{ route('platform.refunds.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('platform.refunds.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        <span class="truncate">Pencairan Refund</span>
                    </a>
                    <a href="{{ route('platform.analytics.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('platform.analytics.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span class="truncate">Global Analytics</span>
                    </a>
                    <a href="{{ route('platform.services.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('platform.services.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2H-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="truncate">Layanan & Training</span>
                    </a>
                    <a href="{{ route('platform.audit.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('platform.audit.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="truncate">Audit Trail Otoritas</span>
                    </a>
                    <a href="{{ route('platform.system.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('platform.system.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="truncate">Status Sistem</span>
                    </a>
                </div>
            </div>
        @endif

        {{-- ── CUSTOMER SIDEBAR ── --}}
        @if($role === \App\Enums\UserRole::CUSTOMER)
            @php
                $isCustomerActive = request()->routeIs('customer.dashboard', 'customer.orders.*', 'customer.invoices.*');
            @endphp
            <div x-data="{ open: {{ $isCustomerActive ? 'true' : "localStorage.getItem('nayara_sb_cus_pelanggan') !== 'false'" }} }" class="space-y-1 pt-1.5">
                <button type="button"
                        @click="open = !open; localStorage.setItem('nayara_sb_cus_pelanggan', open)"
                        class="w-full flex items-center justify-between text-[10px] font-bold text-[#FFD8D8] uppercase px-3 py-1.5 tracking-wider select-none hover:text-white hover:bg-white/5 rounded-lg transition-colors cursor-pointer group"
                        :aria-expanded="open.toString()">
                    <span class="truncate">Pelanggan</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#FFD8D8]/70 group-hover:text-white shrink-0 ml-1.5"
                         :class="open ? 'rotate-90 text-white' : 'rotate-0'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition-all ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition-all ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="space-y-1 pt-0.5">
                    <a href="{{ route('customer.dashboard') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('customer.dashboard') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span class="truncate">Beranda Akun</span>
                    </a>
                    <a href="{{ route('customer.orders.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('customer.orders.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span class="truncate">Riwayat Pesanan</span>
                    </a>
                    <a href="{{ route('customer.invoices.index') }}"
                       class="{{ $linkBaseClass }} {{ request()->routeIs('customer.invoices.*') ? $activeClass : '' }}">
                        <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="truncate">Tagihan Saya</span>
                    </a>
                </div>
            </div>
        @endif



    </nav>

    {{-- Section D: Footer --}}
    <div class="p-3.5 border-t border-white/10 text-center shrink-0 relative z-10">
        <p class="text-[11px] text-[#FFD8D8] font-normal select-none">Nayara v1.0.0 &copy; 2026</p>
    </div>
</aside>

{{-- Sidebar Scroll Persistence & Active Element Auto-Focus --}}
<script>
    (function() {
        function initNayaraSidebarScroll() {
            var nav = document.getElementById('nayara-sidebar-nav');
            if (!nav) return;

            // 1. Restore scroll position from sessionStorage strictly
            var savedScroll = sessionStorage.getItem('nayara_sidebar_scroll_top');
            if (savedScroll !== null) {
                var pos = parseInt(savedScroll, 10);
                if (!isNaN(pos) && pos >= 0) {
                    nav.scrollTop = pos;
                }
            } else {
                // 2. Only on initial session / fresh visit (no saved scroll), scroll active link into view
                var activeLink = nav.querySelector('a.bg-gradient-to-r, a.font-bold, a.bg-white\\/15, a[aria-current="page"]');
                if (activeLink) {
                    activeLink.scrollIntoView({ block: 'nearest', behavior: 'instant' });
                }
            }

            // 3. Save scroll position on user scrolling
            var scrollTimer;
            nav.addEventListener('scroll', function() {
                if (scrollTimer) clearTimeout(scrollTimer);
                scrollTimer = setTimeout(function() {
                    sessionStorage.setItem('nayara_sidebar_scroll_top', nav.scrollTop);
                }, 50);
            }, { passive: true });

            // 4. Save scroll immediately before page transition when clicking a link
            nav.addEventListener('click', function(e) {
                var link = e.target.closest('a');
                if (link) {
                    sessionStorage.setItem('nayara_sidebar_scroll_top', nav.scrollTop);
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initNayaraSidebarScroll);
        } else {
            initNayaraSidebarScroll();
        }
    })();
</script>
