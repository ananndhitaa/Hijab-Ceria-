<x-app-layout pageTitle="Log Aktivitas Konveksi" breadcrumb="Tata Kelola > Log Aktivitas">
    <div class="space-y-5 sm:space-y-6">

        {{-- Feature Header --}}
        <x-feature-header
            title="Log Aktivitas Konveksi"
            subtitle="Riwayat audit resmi transaksi, pembuatan batch, penugasan karyawan, hasil produksi, dan pemeriksaan QC."
            icon="clock"
        >
            <x-slot:badge>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                    AUDIT TRAIL &bull; READ-ONLY
                </span>
            </x-slot:badge>

            <x-slot:actions>
                <a href="{{ route('owner.dashboard') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Dashboard Owner</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- 4 Metric Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 sm:gap-4">
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card">
                <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Total Aktivitas</span>
                <p class="text-2xl font-extrabold text-[#172B4D] font-mono mt-1">{{ number_format($totalLogsCount) }}</p>
                <span class="text-[11px] text-[#64748B]">Semua histori tercatat</span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card">
                <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Aktivitas Hari Ini</span>
                <p class="text-2xl font-extrabold text-emerald-700 font-mono mt-1">{{ number_format($todayLogsCount) }}</p>
                <span class="text-[11px] text-emerald-600">Pergerakan operasional</span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card">
                <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Log Manufaktur &amp; QC</span>
                <p class="text-2xl font-extrabold text-[#8E1B5C] font-mono mt-1">{{ number_format($productionLogsCount) }}</p>
                <span class="text-[11px] text-[#64748B]">Batch, produksi, dan QC</span>
            </div>

            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card">
                <span class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider block">Log Penjualan</span>
                <p class="text-2xl font-extrabold text-indigo-700 font-mono mt-1">{{ number_format($salesLogsCount) }}</p>
                <span class="text-[11px] text-[#64748B]">Order &amp; kesepakatan</span>
            </div>
        </div>

        {{-- Filter & Search Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card space-y-4">
            <form method="GET" action="{{ route('owner.activity-logs.index') }}" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    {{-- Search --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-[#64748B] mb-1">Pencarian</label>
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="No. Ref, Nama User, Deskripsi..."
                               class="w-full h-9 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                    </div>

                    {{-- Module Filter --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-[#64748B] mb-1">Modul</label>
                        <select name="module"
                                class="w-full h-9 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                            <option value="all">-- Semua Modul --</option>
                            <option value="SALES_ORDER" @selected($module === 'SALES_ORDER')>Sales Order</option>
                            <option value="BATCH_PRODUKSI" @selected($module === 'BATCH_PRODUKSI')>Batch Produksi</option>
                            <option value="PRODUKSI" @selected($module === 'PRODUKSI')>Pengerjaan Produksi</option>
                            <option value="QC" @selected($module === 'QC')>Pemeriksaan QC</option>
                            <option value="GUDANG" @selected($module === 'GUDANG')>Gudang &amp; Stok</option>
                            <option value="PURCHASING" @selected($module === 'PURCHASING')>Pengadaan Bahan</option>
                            <option value="KEUANGAN" @selected($module === 'KEUANGAN')>Keuangan &amp; Kas</option>
                            <option value="TENANT" @selected($module === 'TENANT')>Tata Kelola Tenant</option>
                        </select>
                    </div>

                    {{-- User / Actor Filter --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-[#64748B] mb-1">Pengguna / Aktor</label>
                        <select name="user_id"
                                class="w-full h-9 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                            <option value="all">-- Semua Pengguna --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected($userId == $u->id)>
                                    {{ $u->name }} ({{ $u->role->label() ?? $u->role }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Preset Period Filter --}}
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-[#64748B] mb-1">Periode</label>
                        <select name="preset"
                                class="w-full h-9 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                            <option value="all" @selected($preset === 'all')>Semua Periode</option>
                            <option value="today" @selected($preset === 'today')>Hari Ini</option>
                            <option value="this_month" @selected($preset === 'this_month')>Bulan Ini</option>
                            <option value="last_month" @selected($preset === 'last_month')>Bulan Lalu</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-[#FFD8D8]/70">
                    <span class="text-[11px] text-[#64748B]">
                        Menampilkan histori terverifikasi yang terisolasi khusus konveksi Anda.
                    </span>

                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#68113F] transition cursor-pointer shadow-xs">
                            Terapkan Filter
                        </button>
                        @if($search || $module !== 'all' || $userId !== 'all' || $preset !== 'all')
                            <a href="{{ route('owner.activity-logs.index') }}"
                               class="h-9 px-3 inline-flex items-center justify-center rounded-lg border border-[#FFD8D8] text-xs font-semibold text-[#64748B] hover:text-[#172B4D] hover:bg-[#FFD8D8]/30 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Activity Logs Table --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#172B4D]">
                    <thead class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3.5 whitespace-nowrap">Waktu</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Pengguna</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Modul &amp; Aksi</th>
                            <th class="px-4 py-3.5 whitespace-nowrap font-mono">No. Referensi</th>
                            <th class="px-5 py-3.5">Deskripsi Aktivitas</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-right">Perubahan Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($logs as $log)
                            @php
                                $badgeClass = match($log->module) {
                                    'SALES_ORDER' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'BATCH_PRODUKSI' => 'bg-[#FFD8D8] text-[#8E1B5C] border-[#FFD8D8]',
                                    'PRODUKSI' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'QC' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'GUDANG' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                    'PURCHASING' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'KEUANGAN' => 'bg-teal-50 text-teal-700 border-teal-200',
                                    'TENANT' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200',
                                };
                            @endphp
                            @php
                                $logTime = $log->created_at ? \App\Support\DateTimeFormatter::toDisplayTimezone($log->created_at) : null;
                            @endphp
                            <tr class="hover:bg-[#FFFCFB]/70 transition">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="font-semibold text-[#172B4D]">
                                        {{ $logTime ? $logTime->translatedFormat('d M Y') : '-' }}
                                    </div>
                                    <div class="text-[11px] text-[#64748B] font-mono">
                                        {{ $logTime ? $logTime->format('H:i:s') : '-' }} WIB
                                    </div>
                                </td>

                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-bold text-[#172B4D]">{{ $log->user_name }}</div>
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-gray-100 text-gray-700 mt-0.5">
                                        {{ $log->user_role }}
                                    </span>
                                </td>

                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $badgeClass }}">
                                        {{ $log->module }}
                                    </span>
                                    <div class="text-[11px] font-mono font-semibold text-[#64748B] mt-1">
                                        {{ $log->action_type }}
                                    </div>
                                </td>

                                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-semibold text-[#8E1B5C]">
                                    {{ $log->reference_no ?: '-' }}
                                </td>

                                <td class="px-5 py-3.5">
                                    <p class="text-xs text-[#172B4D] leading-relaxed max-w-lg">
                                        {{ str_replace('.0000 pcs', ' pcs', $log->description) }}
                                    </p>
                                </td>

                                <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                    @if($log->before_values || $log->after_values)
                                        <div x-data="{ showDiff: false }" class="relative inline-block text-left">
                                            <button type="button"
                                                    @click="showDiff = !showDiff"
                                                    class="text-[11px] font-bold text-[#8E1B5C] hover:underline cursor-pointer">
                                                <span x-text="showDiff ? 'Tutup Rincian' : 'Lihat Nilai'"></span>
                                            </button>

                                            <div x-show="showDiff"
                                                 x-cloak
                                                 @click.outside="showDiff = false"
                                                 class="absolute right-0 top-full mt-1 w-72 sm:w-80 bg-white rounded-xl border border-[#FFD8D8] shadow-lg p-3 z-30 text-[11px] space-y-2">
                                                @if($log->before_values)
                                                    <div>
                                                        <span class="font-bold text-[#64748B] uppercase text-[10px]">Sebelum:</span>
                                                        <pre class="bg-[#FFFCFB] p-2 rounded border border-[#FFD8D8] text-[10px] text-[#172B4D] overflow-x-auto">{{ json_encode($log->before_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    </div>
                                                @endif
                                                @if($log->after_values)
                                                    <div>
                                                        <span class="font-bold text-[#64748B] uppercase text-[10px]">Sesudah:</span>
                                                        <pre class="bg-[#FFFCFB] p-2 rounded border border-[#FFD8D8] text-[10px] text-[#172B4D] overflow-x-auto">{{ json_encode($log->after_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-[#64748B] text-[11px]">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-xs text-[#64748B]">
                                    Belum ada log aktivitas tercatat untuk kriteria pencarian ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-4 border-t border-[#FFD8D8]">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

