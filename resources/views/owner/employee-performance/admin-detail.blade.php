<x-app-layout pageTitle="Detail Performa Admin: {{ $admin->name }}" breadcrumb="Detail Performa Admin">
    <div class="max-w-7xl mx-auto space-y-4 sm:space-y-5">

        {{-- Feature Header NAYARA --}}
        <x-feature-header
            title="Detail Performa Admin"
            subtitle="{{ $admin->name }} · {{ $admin->email }}"
            icon="chart"
            badge="Performa Admin • Read-Only"
        >
            <span class="sr-only">Detail Performa Admin</span>
            <x-slot:actions>
                <a href="{{ route('owner.employee-performance.index', ['preset' => $preset, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
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
            <form method="GET" action="{{ route('owner.employee-performance.admin-detail', $admin) }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-bold text-[#64748B] uppercase tracking-wider mr-1">Periode:</span>

                    <a href="{{ route('owner.employee-performance.admin-detail', ['admin' => $admin->id, 'preset' => 'today']) }}"
                       class="px-2.5 py-1 rounded-xl text-xs font-semibold transition {{ $preset === 'today' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Hari Ini
                    </a>
                    <a href="{{ route('owner.employee-performance.admin-detail', ['admin' => $admin->id, 'preset' => 'this_month']) }}"
                       class="px-2.5 py-1 rounded-xl text-xs font-semibold transition {{ $preset === 'this_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Bulan Ini
                    </a>
                    <a href="{{ route('owner.employee-performance.admin-detail', ['admin' => $admin->id, 'preset' => 'last_month']) }}"
                       class="px-2.5 py-1 rounded-xl text-xs font-semibold transition {{ $preset === 'last_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                        Bulan Lalu
                    </a>
                    <a href="{{ route('owner.employee-performance.admin-detail', ['admin' => $admin->id, 'preset' => 'all']) }}"
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
            {{-- Total Customer --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-3.5 sm:p-4 shadow-card">
                <span class="text-xs font-semibold text-[#64748B]">Total Customer</span>
                <p class="text-2xl font-bold text-[#172B4D] mt-1">{{ number_format($detail['total_customers'], 0, ',', '.') }}</p>
                <span class="text-[11px] text-[#64748B]">Ditangani Admin</span>
            </div>

            {{-- Deal --}}
            <div class="bg-white border border-emerald-200/80 rounded-2xl p-3.5 sm:p-4 shadow-card bg-emerald-50/20">
                <span class="text-xs font-semibold text-emerald-800">Deal</span>
                <p class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($detail['deal_customers'], 0, ',', '.') }}</p>
                <span class="text-[11px] text-emerald-600">Customer sudah DP</span>
            </div>

            {{-- Belum Deal --}}
            <div class="bg-white border border-amber-200/80 rounded-2xl p-3.5 sm:p-4 shadow-card bg-amber-50/20">
                <span class="text-xs font-semibold text-amber-800">Belum Deal</span>
                <p class="text-2xl font-bold text-amber-700 mt-1">{{ number_format($detail['belum_deal_customers'], 0, ',', '.') }}</p>
                <span class="text-[11px] text-amber-600">Belum menjadi order</span>
            </div>

            {{-- Persentase Deal --}}
            <div class="bg-white border border-[#8E1B5C]/20 rounded-2xl p-3.5 sm:p-4 shadow-card bg-[#8E1B5C]/5">
                <span class="text-xs font-semibold text-[#8E1B5C]">Persentase Deal</span>
                <p class="text-2xl font-bold text-[#8E1B5C] mt-1">{{ $detail['deal_percentage_text'] }}</p>
                <span class="text-[11px] text-[#64748B]">Deal &divide; Total Customer</span>
            </div>

            {{-- Jumlah Order --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-3.5 sm:p-4 shadow-card col-span-2 md:col-span-1">
                <span class="text-xs font-semibold text-[#64748B]">Jumlah Order</span>
                <p class="text-2xl font-bold text-[#172B4D] mt-1">{{ number_format($detail['total_orders'], 0, ',', '.') }}</p>
                <span class="text-[11px] text-[#64748B]">Total pesanan dibuat</span>
            </div>
        </div>

        {{-- Customers Handled Table --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#FFD8D8] bg-[#FFFCFB] flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-[#172B4D]">Daftar Customer</h3>
                    <p class="text-xs text-[#64748B] mt-0.5">{{ $detail['total_customers'] }} customer pada periode ini.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#172B4D]">
                    <thead class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3">Customer / Toko</th>
                            <th class="px-4 py-3">WhatsApp</th>
                            <th class="px-4 py-3">Tanggal Masuk</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Jumlah Order</th>
                            <th class="px-5 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($detail['customers'] as $cust)
                            <tr class="hover:bg-[#FFFCFB]/60 transition">
                                <td class="px-5 py-3.5 font-bold text-[#172B4D]">
                                    @if(filled($cust->customer_name))
                                        <span>{{ $cust->customer_name }}</span>
                                    @else
                                        <span class="text-xs text-[#64748B] italic font-normal">Nama belum diisi</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-[#64748B] font-mono">
                                    {{ $cust->formattedPhone() }}
                                </td>
                                <td class="px-4 py-3.5 text-[#64748B]">
                                    {{ $cust->first_contact_date ? $cust->first_contact_date->isoFormat('D MMM Y') : ($cust->created_at ? $cust->created_at->isoFormat('D MMM Y') : '-') }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $cust->status_badge_class }}">
                                        {{ $cust->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-semibold text-[#172B4D]">
                                    {{ $cust->orders->count() }}
                                </td>
                                <td class="px-5 py-3.5 text-[#64748B] max-w-xs truncate" title="{{ $cust->notes }}">
                                    {{ $cust->notes ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-xs text-[#64748B]">
                                    Belum ada customer yang ditangani oleh Admin ini pada periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
