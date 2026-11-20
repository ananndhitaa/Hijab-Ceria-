<x-app-layout pageTitle="Performa Karyawan" breadcrumb="Performa Karyawan">
    <div class="max-w-7xl mx-auto space-y-4 sm:space-y-6"
         x-data="{ activeTab: '{{ request('tab', 'admin') }}' }">

        {{-- Feature Header --}}
        <x-feature-header
            title="Performa Karyawan"
            subtitle="Monitoring konversi prospek Admin WhatsApp dan ketepatan operasional tim Produksi."
            icon="chart"
            badge="Performa Karyawan • Read-Only"
        >
            <span class="sr-only">Performa Karyawan</span>
            <x-slot:actions>
                <a href="{{ route('owner.dashboard') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Tab Navigation: Performa Admin vs Performa Produksi --}}
        <div class="flex items-center gap-2 border-b border-[#FFD8D8] pb-2">
            <button type="button"
                    @click="activeTab = 'admin'"
                    :class="activeTab === 'admin'
                        ? 'bg-[#8E1B5C] text-white shadow-xs'
                        : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Performa Admin</span>
            </button>

            <button type="button"
                    @click="activeTab = 'production'"
                    :class="activeTab === 'production'
                        ? 'bg-[#8E1B5C] text-white shadow-xs'
                        : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Performa Produksi</span>
            </button>
        </div>

        {{-- ═════════════════════════════════════════════════════════════════ --}}
        {{-- TAB 1: PERFORMA ADMIN                                             --}}
        {{-- ═════════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'admin'" class="space-y-4 sm:space-y-6">
            {{-- Period Filter Admin --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
                <form method="GET" action="{{ route('owner.employee-performance.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                    <input type="hidden" name="tab" value="admin">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs font-bold text-[#64748B] uppercase tracking-wider mr-2">Periode Masuk Customer:</span>

                        <a href="{{ route('owner.employee-performance.index', ['tab' => 'admin', 'preset' => 'today']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'today' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                            Hari Ini
                        </a>
                        <a href="{{ route('owner.employee-performance.index', ['tab' => 'admin', 'preset' => 'this_month']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'this_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                            Bulan Ini
                        </a>
                        <a href="{{ route('owner.employee-performance.index', ['tab' => 'admin', 'preset' => 'last_month']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'last_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                            Bulan Lalu
                        </a>
                        <a href="{{ route('owner.employee-performance.index', ['tab' => 'admin', 'preset' => 'all']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'all' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                            Semua
                        </a>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="hidden" name="preset" value="custom">
                        <input type="date"
                               name="start_date"
                               value="{{ $startDate }}"
                               class="h-9 px-3 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                        <span class="text-xs text-[#64748B]">s/d</span>
                        <input type="date"
                               name="end_date"
                               value="{{ $endDate }}"
                               class="h-9 px-3 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                        <button type="submit"
                                class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition cursor-pointer shadow-xs">
                            Terapkan
                        </button>
                    </div>
                </form>
                <div class="mt-2 text-[11px] text-[#64748B]">
                    Cohort dihitung berdasarkan <span class="font-medium text-[#172B4D]">Tanggal Masuk Customer</span> (pertama kali customer menghubungi lewat WhatsApp).
                </div>
            </div>

            {{-- Summary Metric Cards Admin --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4">
                {{-- Total Customer --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card">
                    <span class="text-xs font-semibold text-[#64748B]">Total Customer</span>
                    <p class="text-2xl font-bold text-[#172B4D] mt-1">{{ number_format($performance['grand_total_customers'], 0, ',', '.') }}</p>
                    <span class="text-[11px] text-[#64748B]">Semua prospek terdaftar</span>
                </div>

                {{-- Deal --}}
                <div class="bg-white border border-emerald-200 rounded-2xl p-4 shadow-card bg-emerald-50/20">
                    <span class="text-xs font-semibold text-emerald-800">Deal</span>
                    <p class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($performance['grand_deal_customers'], 0, ',', '.') }}</p>
                    <span class="text-[11px] text-emerald-600">Customer sudah DP</span>
                </div>

                {{-- Belum Deal --}}
                <div class="bg-white border border-amber-200 rounded-2xl p-4 shadow-card bg-amber-50/20">
                    <span class="text-xs font-semibold text-amber-800">Belum Deal</span>
                    <p class="text-2xl font-bold text-amber-700 mt-1">{{ number_format($performance['grand_belum_deal_customers'], 0, ',', '.') }}</p>
                    <span class="text-[11px] text-amber-600">Belum menjadi order</span>
                </div>

                {{-- Persentase Deal --}}
                <div class="bg-white border border-[#8E1B5C]/30 rounded-2xl p-4 shadow-card bg-[#8E1B5C]/5">
                    <span class="text-xs font-semibold text-[#8E1B5C]">Persentase Deal</span>
                    <p class="text-2xl font-bold text-[#8E1B5C] mt-1">{{ $performance['overall_deal_text'] }}</p>
                    <span class="text-[11px] text-[#64748B]">Deal &divide; Total Customer</span>
                </div>

                {{-- Jumlah Order --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card col-span-2 md:col-span-1">
                    <span class="text-xs font-semibold text-[#64748B]">Jumlah Order</span>
                    <p class="text-2xl font-bold text-[#172B4D] mt-1">{{ number_format($performance['grand_total_orders'], 0, ',', '.') }}</p>
                    <span class="text-[11px] text-[#64748B]">Termasuk repeat order</span>
                </div>
            </div>

            @if($performance['unassigned_count'] > 0)
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-800 flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <span class="font-bold">Informasi Data Historis:</span> Terdapat {{ $performance['unassigned_count'] }} customer pada database historis yang belum memiliki atribut PIC Admin. Data ini tidak dihubungkan secara acak ke Admin mana pun guna menjaga akurasi evaluasi performa.
                    </div>
                </div>
            @endif

            {{-- Table: Performa Admin --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-4 border-b border-[#FFD8D8] bg-[#FFFCFB] flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-[#172B4D]">Tabel Performa Admin</h3>
                        <p class="text-xs text-[#64748B] mt-0.5">Evaluasi rasio konversi prospek customer ke tahap Deal per masing-masing Admin operasional.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-[#172B4D]">
                        <thead class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5">Admin</th>
                                <th class="px-4 py-3.5 text-center">Total Customer</th>
                                <th class="px-4 py-3.5 text-center">Deal</th>
                                <th class="px-4 py-3.5 text-center">Belum Deal</th>
                                <th class="px-4 py-3.5 text-center">Persentase Deal</th>
                                <th class="px-4 py-3.5 text-center">Jumlah Order</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @forelse($performance['admin_stats'] as $stat)
                                <tr class="hover:bg-[#FFFCFB]/60 transition">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-[#8E1B5C]/10 text-[#8E1B5C] flex items-center justify-center font-bold text-xs uppercase">
                                                {{ substr($stat['admin']->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-[#172B4D]">{{ $stat['admin']->name }}</p>
                                                <p class="text-[11px] text-[#64748B]">{{ $stat['admin']->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-center font-semibold text-[#172B4D]">
                                        {{ number_format($stat['total_customers'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            {{ number_format($stat['deal_customers'], 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            {{ number_format($stat['belum_deal_customers'], 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        @if($stat['deal_percentage'] !== null)
                                            <div class="inline-flex items-center gap-1.5">
                                                <span class="font-bold text-[#8E1B5C] text-xs">{{ $stat['deal_percentage_text'] }}</span>
                                            </div>
                                        @else
                                            <span class="text-[#64748B] italic text-[11px]">Belum Ada Data</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center text-[#172B4D] font-medium">
                                        {{ number_format($stat['total_orders'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <a href="{{ route('owner.employee-performance.admin-detail', ['admin' => $stat['admin']->id, 'preset' => $preset, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                                            <span>Detail</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-xs text-[#64748B]">
                                        Tidak ada data Admin pada sistem.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ═════════════════════════════════════════════════════════════════ --}}
        {{-- TAB 2: PERFORMA PRODUKSI                                          --}}
        {{-- ═════════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'production'" class="space-y-4 sm:space-y-6">
            {{-- Period Filter Produksi --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card">
                <form method="GET" action="{{ route('owner.employee-performance.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                    <input type="hidden" name="tab" value="production">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs font-bold text-[#64748B] uppercase tracking-wider mr-2">Periode Selesai Produksi:</span>

                        <a href="{{ route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'today']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'today' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                            Hari Ini
                        </a>
                        <a href="{{ route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'this_month']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'this_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                            Bulan Ini
                        </a>
                        <a href="{{ route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'last_month']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'last_month' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                            Bulan Lalu
                        </a>
                        <a href="{{ route('owner.employee-performance.index', ['tab' => 'production', 'preset' => 'all']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $preset === 'all' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'bg-[#FFFCFB] text-[#64748B] hover:bg-[#FFD8D8] border border-[#FFD8D8]' }}">
                            Semua
                        </a>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="hidden" name="preset" value="custom">
                        <input type="date"
                               name="start_date"
                               value="{{ $startDate }}"
                               class="h-9 px-3 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                        <span class="text-xs text-[#64748B]">s/d</span>
                        <input type="date"
                               name="end_date"
                               value="{{ $endDate }}"
                               class="h-9 px-3 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:ring-1 focus:ring-[#8E1B5C] focus:border-[#8E1B5C]">
                        <button type="submit"
                                class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition cursor-pointer shadow-xs">
                            Terapkan
                        </button>
                    </div>
                </form>
                <div class="mt-2 text-[11px] text-[#64748B]">
                    Evaluasi dihitung berdasarkan <span class="font-medium text-[#172B4D]">Tanggal Selesai Produksi Aktual</span> yang dicatat pada form hasil produksi.
                </div>
            </div>

            {{-- Bagian 7: Monitoring Produktivitas Tim Produksi Konveksi (Aggregate Overview) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card space-y-4">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-3">
                    <div>
                        <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                            Ringkasan Produktivitas &amp; Kapasitas Karyawan
                        </h3>
                        <p class="text-[11px] text-[#64748B] mt-0.5">
                            Status ketersediaan karyawan, beban batch aktif, dan sisa target berjalan di konveksi Anda.
                        </p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#8E1B5C]/10 text-[#8E1B5C]">
                        {{ $productionPerformance['total_production_staff'] ?? 0 }} Staff Produksi
                    </span>
                </div>

                {{-- 4-Card Capacity & Workload Metrics --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8]">
                        <span class="text-[10px] uppercase font-bold text-[#64748B] block">Memiliki Pekerjaan</span>
                        <div class="text-xl font-extrabold text-[#172B4D] font-mono mt-1">
                            {{ $productionPerformance['active_production_staff'] ?? 0 }}
                            <span class="text-xs font-normal text-[#64748B]">orang</span>
                        </div>
                        <span class="text-[10.5px] text-[#64748B] mt-0.5 block">Sedang menangani batch</span>
                    </div>

                    <div class="p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8]">
                        <span class="text-[10px] uppercase font-bold text-[#64748B] block">Kandidat Tersedia</span>
                        <div class="text-xl font-extrabold text-emerald-700 font-mono mt-1">
                            {{ $productionPerformance['available_production_staff'] ?? 0 }}
                            <span class="text-xs font-normal text-[#64748B]">orang</span>
                        </div>
                        <span class="text-[10.5px] text-emerald-600 mt-0.5 block">Belum ada batch aktif</span>
                    </div>

                    <div class="p-3 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8]">
                        <span class="text-[10px] uppercase font-bold text-[#64748B] block">Mencapai Target</span>
                        <div class="text-xl font-extrabold text-[#8E1B5C] font-mono mt-1">
                            {{ $productionPerformance['target_met_staff'] ?? 0 }}
                            <span class="text-xs font-normal text-[#64748B]">orang</span>
                        </div>
                        <span class="text-[10.5px] text-[#64748B] mt-0.5 block">Pencapaian &ge; 100%</span>
                    </div>

                    <div class="p-3 bg-[#FFFCFB] rounded-xl border border-rose-200 bg-rose-50/20">
                        <span class="text-[10px] uppercase font-bold text-rose-800 block">Batch Melewati Deadline</span>
                        <div class="text-xl font-extrabold text-rose-700 font-mono mt-1">
                            {{ $productionPerformance['total_overdue_batches'] ?? 0 }}
                            <span class="text-xs font-normal text-rose-600">batch</span>
                        </div>
                        <span class="text-[10.5px] text-rose-600 mt-0.5 block">Dari {{ $productionPerformance['total_active_batches'] ?? 0 }} batch aktif</span>
                    </div>
                </div>

                {{-- Target & Actual Volume Aggregates --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 border-t border-[#FFD8D8]">
                    <div class="p-2.5 bg-white rounded-lg border border-[#FFD8D8] text-center">
                        <span class="text-[10px] uppercase font-bold text-[#64748B] block">Total Target Berjalan</span>
                        <span class="text-lg font-extrabold text-[#172B4D] font-mono mt-0.5 block">
                            {{ number_format($productionPerformance['total_active_target_qty'] ?? 0) }} pcs
                        </span>
                    </div>
                    <div class="p-2.5 bg-white rounded-lg border border-[#FFD8D8] text-center">
                        <span class="text-[10px] uppercase font-bold text-[#64748B] block">Total Aktual Selesai</span>
                        <span class="text-lg font-extrabold text-emerald-700 font-mono mt-0.5 block">
                            {{ number_format($productionPerformance['total_active_actual_qty'] ?? 0) }} pcs
                        </span>
                    </div>
                    <div class="p-2.5 bg-white rounded-lg border border-[#FFD8D8] text-center">
                        <span class="text-[10px] uppercase font-bold text-[#64748B] block">Total Sisa Target</span>
                        <span class="text-lg font-extrabold text-[#8E1B5C] font-mono mt-0.5 block">
                            {{ number_format($productionPerformance['total_active_remaining_qty'] ?? 0) }} pcs
                        </span>
                    </div>
                </div>
            </div>

            {{-- Historical Evaluation Metric Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                {{-- Total Batch Selesai Periode --}}
                <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card">
                    <span class="text-xs font-semibold text-[#64748B]">Batch Selesai Historis</span>
                    <p class="text-2xl font-bold text-[#172B4D] mt-1">{{ number_format($productionPerformance['grand_total_batches'], 0, ',', '.') }}</p>
                    <span class="text-[11px] text-[#64748B]">Periode terpilih</span>
                </div>

                {{-- Tepat Waktu / Cepat --}}
                <div class="bg-white border border-emerald-200 rounded-2xl p-4 shadow-card bg-emerald-50/20">
                    <span class="text-xs font-semibold text-emerald-800">Tepat Waktu / Cepat</span>
                    <p class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($productionPerformance['grand_on_time_batches'], 0, ',', '.') }}</p>
                    <span class="text-[11px] text-emerald-600">Dari {{ $productionPerformance['grand_batches_with_target'] }} batch bertarget</span>
                </div>

                {{-- Ketepatan Waktu --}}
                <div class="bg-white border border-[#8E1B5C]/30 rounded-2xl p-4 shadow-card bg-[#8E1B5C]/5">
                    <span class="text-xs font-semibold text-[#8E1B5C]">Rasio Ketepatan Waktu</span>
                    <p class="text-2xl font-bold text-[#8E1B5C] mt-1">{{ $productionPerformance['overall_on_time_text'] }}</p>
                    <span class="text-[11px] text-[#64748B]">Penyelesaian tepat waktu</span>
                </div>

                {{-- Rasio Kuantitas Fisik --}}
                <div class="bg-white border border-indigo-200 rounded-2xl p-4 shadow-card bg-indigo-50/20">
                    <span class="text-xs font-semibold text-indigo-800">Rasio Hasil Kuantitas</span>
                    <p class="text-2xl font-bold text-indigo-700 mt-1">{{ $productionPerformance['overall_qty_text'] }}</p>
                    <span class="text-[11px] text-indigo-600">&Sigma; Aktual &divide; &Sigma; Target</span>
                </div>
            </div>

            @if($productionPerformance['unassigned_batches_count'] > 0)
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3.5 sm:p-4 text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-amber-600 shrink-0" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span><strong class="font-bold">{{ $productionPerformance['unassigned_batches_count'] }} batch historis</strong> belum memiliki PIC Produksi.</span>
                    </div>
                    <a href="{{ route('owner.employee-performance.unassigned-batches') }}"
                       class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs transition shadow-xs self-start sm:self-auto cursor-pointer">
                        <span>Lengkapi PIC</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            @endif

            {{-- Table: Performa & Produktivitas Produksi --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-4 border-b border-[#FFD8D8] bg-[#FFFCFB] flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-[#172B4D]">Tabel Performa Karyawan Produksi</h3>
                        <p class="text-xs text-[#64748B] mt-0.5">Monitoring beban aktif berjalan, produktivitas, sisa target, deadline terdekat, dan riwayat penyelesaian.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-[#172B4D]">
                        <thead class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5">Karyawan</th>
                                <th class="px-4 py-3.5 text-center">Status Kerja</th>
                                <th class="px-4 py-3.5 text-center">Batch Aktif</th>
                                <th class="px-4 py-3.5 text-right">Target Berjalan</th>
                                <th class="px-4 py-3.5 text-right">Aktual / Sisa</th>
                                <th class="px-4 py-3.5 text-center">Pencapaian</th>
                                <th class="px-4 py-3.5 text-center">Deadline Terdekat</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]">
                            @forelse($productionPerformance['production_stats'] as $stat)
                                <tr class="hover:bg-[#FFFCFB]/60 transition">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-[#8E1B5C]/10 text-[#8E1B5C] flex items-center justify-center font-bold text-xs uppercase">
                                                {{ substr($stat['user']->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-[#172B4D]">{{ $stat['user']->name }}</p>
                                                <p class="text-[11px] text-[#64748B]">{{ $stat['user']->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10.5px] font-bold border {{ $stat['workload_badge_class'] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $stat['workload_status'] ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center font-semibold text-[#172B4D]">
                                        {{ number_format($stat['active_batches_count'] ?? 0) }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-medium text-[#172B4D]">
                                        <span class="text-xs font-bold">{{ number_format($stat['active_target_qty'] ?? 0) }}</span>
                                        <span class="text-[11px] text-[#64748B]">pcs</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-medium">
                                        <div class="text-xs font-bold text-emerald-700">{{ number_format($stat['active_actual_qty'] ?? 0) }} pcs</div>
                                        <div class="text-[10.5px] text-[#8E1B5C]">Sisa: {{ number_format($stat['active_remaining_qty'] ?? 0) }} pcs</div>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        @php
                                            $achPct = $stat['achievement_percentage'] ?? 0;
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $achPct >= 100 ? 'bg-emerald-100 text-emerald-800' : ($achPct >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') }}">
                                            {{ number_format($achPct, 1, ',', '.') }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        <span class="text-xs font-semibold {{ ($stat['has_overdue'] ?? false) ? 'text-rose-600 font-bold' : 'text-[#172B4D]' }}">
                                            {{ $stat['closest_deadline_formatted'] ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <a href="{{ route('owner.employee-performance.production-detail', ['user' => $stat['user']->id, 'preset' => $preset, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1 rounded-xl border border-[#FFD8D8] bg-[#FFFCFB] text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] transition shadow-2xs">
                                            <span>Detail</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-xs text-[#64748B]">
                                        Tidak ada data karyawan Produksi pada sistem konveksi Anda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
