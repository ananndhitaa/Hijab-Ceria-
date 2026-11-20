<x-app-layout pageTitle="Dashboard Pelanggan" breadcrumb="Overview > Beranda Akun">
    <div class="space-y-5 sm:space-y-6">

        {{-- NAYARA Customer Hero Banner --}}
        <x-dashboard-hero
            role-label="Pelanggan"
            title="Dashboard Pelanggan"
            description="Halo, {{ auth()->user()->name }} · Pantau status pesanan hijab, riwayat pembelian, dan faktur tagihan Anda."
            action-label="Semua Pesanan"
            :action-route="route('customer.orders.index')"
            action-icon="order"
        />

        {{-- Unlinked Customer Notice --}}
        @if(! $customer)
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3 shadow-card">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-xs text-amber-800">
                    <p class="font-bold">Profil Pelanggan Belum Terhubung</p>
                    <p class="mt-0.5">Akun Anda belum terhubung dengan data profil pelanggan. Silakan hubungi tim admin Nayara untuk menghubungkan akun Anda agar dapat melihat histori pesanan.</p>
                </div>
            </div>
        @endif

        {{-- Customer Overview Cards (Normalized 3 Operational Cards) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4 items-stretch">
            {{-- 1. Pesanan Aktif --}}
            <div class="h-full bg-white rounded-2xl p-4 sm:p-5 border border-[#FFD8D8] shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Pesanan Aktif</p>
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold font-mono tracking-tight leading-none text-[#8E1B5C]">{{ number_format($activeOrdersCount, 0, ',', '.') }} Pesanan</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Sedang diproses &amp; dikirim</p>
                </div>
            </div>

            {{-- 2. Pesanan Selesai --}}
            <div class="h-full bg-white rounded-2xl p-4 sm:p-5 border border-[#FFD8D8] shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Pesanan Selesai</p>
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold font-mono tracking-tight leading-none text-emerald-700">{{ number_format($completedOrdersCount, 0, ',', '.') }} Pesanan</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Histori transaksi tuntas</p>
                </div>
            </div>

            {{-- 3. Total Belanja --}}
            <div class="h-full bg-white rounded-2xl p-4 sm:p-5 border border-[#FFD8D8] shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Total Belanja</p>
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-xl sm:text-2xl font-bold font-mono tracking-tight leading-none text-[#172B4D] whitespace-nowrap">{{ \App\Support\MoneyFormatter::rupiah($totalBelanja) }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Akumulasi seluruh transaksi</p>
                </div>
            </div>
        </div>

        {{-- Recent Orders Section --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl overflow-hidden shadow-card">
            <div class="bg-[#FFFCFB] px-5 py-3.5 border-b border-[#FFD8D8] flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Pesanan Terbaru</h2>
                    <p class="text-xs text-[#64748B] mt-0.5">Daftar 5 pesanan terakhir Anda</p>
                </div>
                @if($customer && ($activeOrdersCount > 0 || $completedOrdersCount > 0))
                    <a href="{{ route('customer.orders.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200">
                        <span>Lihat Semua Pesanan</span>
                        <svg class="w-3.5 h-3.5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endif
            </div>

            @if($recentOrders->isEmpty())
                <div class="p-8 text-center text-[#64748B]">
                    <div class="w-12 h-12 rounded-full bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <p class="font-bold text-[#172B4D] text-sm">Belum Ada Pesanan</p>
                    <p class="text-xs text-[#64748B] mt-1">Pesanan Anda akan ditampilkan di sini setelah dibuat.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                                <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider">No. Order</th>
                                <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center">Item / Qty</th>
                                <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-right">Total</th>
                                <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center">Status</th>
                                <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @foreach($recentOrders as $order)
                                @php
                                    $rowTotal = '0.00';
                                    $rowQty = '0';
                                    foreach ($order->items as $it) {
                                        $rowTotal = bcadd($rowTotal, bcmul((string) $it->qty_ordered, (string) $it->unit_selling_price, 2), 2);
                                        $rowQty = bcadd($rowQty, (string) $it->qty_ordered, 4);
                                    }
                                @endphp
                                <tr class="hover:bg-[#FFD8D8]/20 transition-colors duration-150">
                                    <td class="px-4 py-3 font-mono font-bold text-xs text-[#8E1B5C]">
                                        <a href="{{ route('customer.orders.show', $order) }}" class="hover:underline">
                                            {{ $order->order_no }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-[#64748B]">
                                        {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-[#FFFCFB] border border-[#FFD8D8] text-[11px] text-[#64748B] font-medium">
                                            {{ $order->items->count() }} item ({{ number_format((float) $rowQty, 0, ',', '.') }} pcs)
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold font-mono text-xs text-[#172B4D]">
                                        {{ \App\Support\MoneyFormatter::rupiah($rowTotal) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $order->status->badgeClass() }}">
                                            {{ $order->status->customerLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('customer.orders.show', $order) }}"
                                           class="inline-flex items-center justify-center h-7 px-3 rounded-lg border border-[#FFD8D8] bg-white text-[11px] font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-xs">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Customer Profile Info (if available) --}}
        @if($customer)
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card">
                <h3 class="text-xs font-bold uppercase tracking-wider text-[#172B4D] mb-3">Informasi Akun Pelanggan</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-[#64748B] block">Nama Pelanggan</span>
                        <span class="font-bold text-[#172B4D]">{{ $customer->customer_name }}</span>
                    </div>
                    <div>
                        <span class="text-[#64748B] block">No. Telepon / WhatsApp</span>
                        <span class="font-medium text-[#172B4D]">{{ $customer->phone ?: '-' }}</span>
                    </div>
                    <div>
                        <span class="text-[#64748B] block">Alamat Pengiriman Utama</span>
                        <span class="font-medium text-[#172B4D]">{{ $customer->address ?: '-' }}</span>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
