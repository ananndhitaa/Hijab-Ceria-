<x-app-layout pageTitle="Dashboard Produksi & QC" breadcrumb="Overview > Dashboard Produksi">
    <div class="space-y-5 sm:space-y-6">
        
        {{-- NAYARA Produksi Hero Banner --}}
        <x-dashboard-hero
            role-label="Produksi & QC"
            title="Dashboard Pekerjaan Produksi & QC"
            description="Selamat Datang, {{ auth()->user()->name }} · Pusat penugasan pengerjaan batch produksi dan pemeriksaan QC Anda."
            action-label="Riwayat Selesai"
            :action-route="route('produksi.history')"
            action-icon="archive"
        />

        {{-- Personal Metric Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3.5 items-stretch">
            {{-- 1. Batch Aktif Anda --}}
            <div class="h-full bg-white rounded-2xl p-4 border border-[#FFD8D8] shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Batch Aktif</p>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-[#172B4D] font-mono tracking-tight leading-none">{{ number_format($activeBatchesCount) }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Ditugaskan ke Anda</p>
                </div>
            </div>

            {{-- 2. Siap Dikerjakan --}}
            <div class="h-full bg-white rounded-2xl p-4 border border-[#FFD8D8] shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Siap Kerja</p>
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-emerald-700 font-mono tracking-tight leading-none">{{ number_format($readyToStartCount) }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Bahan siap dialokasikan</p>
                </div>
            </div>

            {{-- 3. Sedang Berjalan --}}
            <div class="h-full bg-white rounded-2xl p-4 border border-[#FFD8D8] shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Sedang Jalan</p>
                    <div class="w-7 h-7 rounded-lg bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-[#8E1B5C] font-mono tracking-tight leading-none">{{ number_format($inProgressCount) }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Dalam proses kerja</p>
                </div>
            </div>

            {{-- 4. Menunggu QC --}}
            <div class="h-full bg-white rounded-2xl p-4 border border-[#FFD8D8] shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Menunggu QC</p>
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-amber-700 font-mono tracking-tight leading-none">{{ number_format($waitingQcCount) }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Perlu inspeksi</p>
                </div>
            </div>

            {{-- 5. Batch Selesai --}}
            <div class="h-full bg-white rounded-2xl p-4 border border-[#FFD8D8] shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Selesai</p>
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-emerald-700 font-mono tracking-tight leading-none">{{ number_format($completedCount) }}</p>
                    </div>
                    <p class="text-[11px] text-[#64748B] mt-1.5 truncate">Total riwayat rampung</p>
                </div>
            </div>

            {{-- 6. Lewat Target --}}
            <div class="h-full bg-white rounded-2xl p-4 border border-[#FFD8D8] shadow-card hover:border-[#FFD8D8] hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 min-h-[38px]">
                    <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider leading-tight">Lewat Target</p>
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center shrink-0 border border-rose-200/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-[#FFD8D8]/60 flex flex-col justify-end">
                    <div class="flex items-baseline min-h-[28px]">
                        <p class="text-2xl font-bold text-rose-700 font-mono tracking-tight leading-none">{{ number_format($overdueCount) }}</p>
                    </div>
                    <p class="text-[11px] text-rose-700 font-medium mt-1.5 truncate">Prioritas utama</p>
                </div>
            </div>
        </div>

        {{-- Personal Target & Actual Workload Bar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <span class="text-xs font-bold text-[#172B4D]">Beban Target Aktif Anda</span>
                <p class="text-[11px] text-[#64748B]">
                    Ringkasan akumulasi target kuantitas batch yang sedang menjadi tanggung jawab Anda saat ini.
                </p>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-[#64748B] block">Total Target</span>
                    <span class="text-base font-extrabold text-[#172B4D] font-mono">{{ number_format($personalTotalTarget) }} <span class="text-xs font-normal text-[#64748B]">pcs</span></span>
                </div>
                <div class="text-right border-l border-[#FFD8D8] pl-4">
                    <span class="text-[10px] uppercase font-bold text-[#64748B] block">Aktual Selesai</span>
                    <span class="text-base font-extrabold text-emerald-700 font-mono">{{ number_format($personalTotalActual) }} <span class="text-xs font-normal text-[#64748B]">pcs</span></span>
                </div>
                <div class="text-right border-l border-[#FFD8D8] pl-4">
                    <span class="text-[10px] uppercase font-bold text-[#64748B] block">Sisa Beban</span>
                    <span class="text-base font-extrabold text-[#8E1B5C] font-mono">{{ number_format($personalTotalRemaining) }} <span class="text-xs font-normal text-[#64748B]">pcs</span></span>
                </div>
            </div>
        </div>

        {{-- Action Center: PERLU TINDAKAN PRIBADI --}}
        @if($produksiActions->isNotEmpty())
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-5 shadow-card space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-[#FFD8D8]">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#8E1B5C]"></span>
                        <h3 class="text-xs font-bold text-[#172B4D] uppercase tracking-wider">Perlu Tindakan Anda Segera</h3>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        {{ $produksiActions->count() }} item
                    </span>
                </div>

                <div class="divide-y divide-[#FFD8D8] border border-[#FFD8D8] rounded-xl overflow-hidden bg-[#FFFCFB]/70">
                    @foreach($produksiActions as $item)
                        <div class="px-4 py-3 sm:py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-white transition-colors duration-150 min-h-[58px]">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $item['badge_class'] }} shrink-0">
                                    {{ $item['status_label'] }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold text-[#172B4D] font-mono leading-none">{{ $item['reference'] }}</span>
                                        @if(!empty($item['target_label']))
                                            <span class="text-[10px] font-semibold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200 leading-none">
                                                {{ $item['target_label'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-[#64748B] mt-1">{{ $item['subtitle'] }}</div>
                                </div>
                            </div>
                            <div class="flex items-center self-end sm:self-auto shrink-0">
                                <a href="{{ $item['action_url'] }}"
                                   class="inline-flex items-center justify-center min-w-[76px] h-8 px-3.5 rounded-lg text-xs font-semibold bg-[#8E1B5C] text-white hover:bg-[#68113F] transition shadow-xs">
                                    {{ $item['action_label'] }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── 3. Pusat Pekerjaan Harian & Kartu Pekerjaan (Bagian 5) ───────────── --}}
        <div class="space-y-4">
            {{-- Filter & Sorting Header --}}
            <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 sm:p-5 shadow-card space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-[#FFD8D8] pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-[#172B4D]">Pusat Pekerjaan Harian Produksi</h2>
                        <p class="text-xs text-[#64748B] mt-0.5">
                            Menampilkan penugasan batch aktif milik Anda. Terurut secara prioritas berdasarkan deadline.
                        </p>
                    </div>

                    {{-- Sorting Selector Tabs --}}
                    <div class="flex items-center gap-1.5 bg-[#FFFCFB] p-1 rounded-xl border border-[#FFD8D8] text-xs">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'deadline']) }}"
                           class="px-3 py-1.5 rounded-lg font-semibold transition {{ $sort === 'deadline' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'text-[#64748B] hover:text-[#172B4D]' }}">
                            Prioritas Deadline
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'newest_order']) }}"
                           class="px-3 py-1.5 rounded-lg font-semibold transition {{ $sort === 'newest_order' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'text-[#64748B] hover:text-[#172B4D]' }}">
                            Pesanan Terbaru
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'oldest_order']) }}"
                           class="px-3 py-1.5 rounded-lg font-semibold transition {{ $sort === 'oldest_order' ? 'bg-[#8E1B5C] text-white shadow-xs' : 'text-[#64748B] hover:text-[#172B4D]' }}">
                            Pesanan Terlama
                        </a>
                    </div>
                </div>

                {{-- Search & Secondary Filters --}}
                <form method="GET" action="{{ route('produksi.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input type="hidden" name="sort" value="{{ $sort }}">

                    <div>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Cari Batch, Order, Produk, SKU..."
                               class="w-full h-9 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                    </div>

                    <div>
                        <select name="status"
                                class="w-full h-9 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                            <option value="">-- Semua Tahapan Pekerjaan --</option>
                            @foreach(\App\Enums\BatchStatus::cases() as $st)
                                @if(!in_array($st, [\App\Enums\BatchStatus::SELESAI, \App\Enums\BatchStatus::SELESAI_PARSIAL, \App\Enums\BatchStatus::CANCELLED]))
                                    <option value="{{ $st->value }}" @selected(request('status') === $st->value)>
                                        {{ $st->label() }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#68113F] transition cursor-pointer shadow-xs">
                            Filter
                        </button>
                        @if(request()->hasAny(['search', 'status']))
                            <a href="{{ route('produksi.dashboard', ['sort' => $sort]) }}"
                               class="h-9 px-3 inline-flex items-center justify-center rounded-lg border border-[#FFD8D8] text-xs font-semibold text-[#64748B] hover:text-[#172B4D] hover:bg-[#FFD8D8]/30 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- ── 4. Daftar Kartu Pekerjaan (Job Cards Grid) ────────────────────────── --}}
            @if($assignedJobs->isEmpty())
                <div class="p-8 sm:p-12 bg-white rounded-2xl border border-[#FFD8D8] shadow-card text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-[#172B4D]">Tidak Ada Penugasan Aktif</h3>
                        <p class="text-xs text-[#64748B] max-w-md mx-auto">
                            Anda saat ini tidak memiliki batch produksi aktif yang ditugaskan. Pekerjaan baru yang diberikan oleh Admin akan otomatis muncul di sini.
                        </p>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($assignedJobs as $job)
                        @php
                            $order = $job->orderItem?->order;
                            $variant = $job->orderItem?->variant;
                            $product = $variant?->product;
                            $prodResult = $job->productionResult;
                            $qcResult = $job->qcResult;

                            $targetQty = (float) $job->target_qty;
                            $actualQty = (float) ($prodResult?->actual_qty ?? 0);
                            $remainingQty = max(0.0, $targetQty - $actualQty);
                            $progressPct = $targetQty > 0 ? min(100, round(($actualQty / $targetQty) * 100)) : 0;

                            $isOverdue = $job->is_overdue;
                            $isMaterialReady = $job->isMaterialReady();

                            // Operational status label
                            $opStatusLabel = match(true) {
                                $job->status === \App\Enums\BatchStatus::BAHAN && ! $isMaterialReady => 'Menunggu Bahan Gudang',
                                $job->status === \App\Enums\BatchStatus::BAHAN && $isMaterialReady => 'Siap Dikerjakan',
                                $job->status === \App\Enums\BatchStatus::PRINTING => 'Sedang Printing',
                                $job->status === \App\Enums\BatchStatus::PRODUKSI && $actualQty == 0 => 'Sedang Jahit / Produksi',
                                $job->status === \App\Enums\BatchStatus::PRODUKSI && $actualQty < $targetQty => 'Produksi Parsial',
                                $job->status === \App\Enums\BatchStatus::QC => 'Menunggu QC',
                                $job->status === \App\Enums\BatchStatus::PACKING => 'Tahap Packing',
                                default => $job->status->label(),
                            };

                            $assignedDate = $job->picHistories->first()?->assigned_at ?? $job->created_at;
                        @endphp

                        <div class="bg-white rounded-2xl border {{ $isOverdue ? 'border-rose-300 ring-1 ring-rose-200' : 'border-[#FFD8D8]' }} shadow-card p-4 sm:p-5 flex flex-col justify-between space-y-4 hover:shadow-md transition-all duration-200">
                            {{-- Card Top: Batch & Order Reference --}}
                            <div class="space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <a href="{{ route('produksi.batches.show', $job) }}" class="font-mono font-bold text-sm text-[#8E1B5C] hover:underline">
                                                {{ $job->batch_no }}
                                            </a>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10.5px] font-bold border {{ $job->status->badgeClass() }}">
                                                {{ $opStatusLabel }}
                                            </span>
                                            @if($isOverdue)
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-rose-50 text-rose-700 border border-rose-300 animate-pulse">
                                                    Terlambat
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-[#64748B] flex items-center gap-1.5 mt-0.5">
                                            <span>Sales Order:</span>
                                            <span class="font-mono font-semibold text-[#172B4D]">{{ $order?->order_no ?? '-' }}</span>
                                            <span class="text-[#FFD8D8]">&middot;</span>
                                            <span>{{ $order?->customer?->customer_name ?? 'Customer Umum' }}</span>
                                        </div>
                                    </div>

                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $job->route_badge_class }}">
                                        {{ $job->route_label }}
                                    </span>
                                </div>

                                {{-- Product & SKU details --}}
                                <div class="p-2.5 bg-[#FFFCFB] rounded-xl border border-[#FFD8D8] text-xs">
                                    <div class="font-bold text-[#172B4D]">{{ $product?->product_name ?? 'Produk' }}</div>
                                    <div class="text-[11px] text-[#64748B] flex items-center justify-between gap-2 mt-0.5">
                                        <span>Varian: <strong class="text-[#172B4D]">{{ $variant?->variant_name ?? '-' }}</strong></span>
                                        <span class="font-mono text-[10.5px]">SKU: {{ $variant?->sku ?? '-' }}</span>
                                    </div>
                                </div>

                                {{-- Key Authoritative Dates --}}
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[10.5px] pt-1">
                                    <div class="p-1.5 bg-[#FFFCFB] rounded border border-[#FFD8D8]/70">
                                        <span class="block text-[#64748B] text-[9.5px]">Order Dibuat</span>
                                        <span class="font-semibold text-[#172B4D]">{{ $order?->created_at ? $order->created_at->format('d/m/Y') : '-' }}</span>
                                    </div>
                                    <div class="p-1.5 bg-[#FFFCFB] rounded border border-[#FFD8D8]/70">
                                        <span class="block text-[#64748B] text-[9.5px]">Penugasan</span>
                                        <span class="font-semibold text-[#172B4D]">{{ $assignedDate ? $assignedDate->format('d/m/Y') : '-' }}</span>
                                    </div>
                                    <div class="p-1.5 bg-[#FFFCFB] rounded border border-[#FFD8D8]/70">
                                        <span class="block text-[#64748B] text-[9.5px]">Rencana Mulai</span>
                                        <span class="font-semibold text-[#172B4D]">{{ $job->planned_start_date ? $job->planned_start_date->format('d/m/Y') : '-' }}</span>
                                    </div>
                                    <div class="p-1.5 bg-[#FFFCFB] rounded border {{ $isOverdue ? 'border-rose-300 bg-rose-50/50' : 'border-[#FFD8D8]/70' }}">
                                        <span class="block text-[9.5px] {{ $isOverdue ? 'text-rose-700 font-bold' : 'text-[#64748B]' }}">Deadline</span>
                                        <span class="font-bold {{ $isOverdue ? 'text-rose-700' : 'text-[#172B4D]' }}">{{ $job->planned_end_date ? $job->planned_end_date->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </div>

                                {{-- Progress & Target Counters --}}
                                <div class="pt-2 space-y-1.5">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-[#64748B] text-[11px]">Progres Penyelesaian:</span>
                                        <span class="font-bold text-[#8E1B5C] font-mono">{{ $progressPct }}%</span>
                                    </div>
                                    <div class="w-full bg-[#FFD8D8]/50 rounded-full h-2 overflow-hidden">
                                        <div class="bg-[#8E1B5C] h-2 rounded-full transition-all duration-300" style="width: {{ $progressPct }}%"></div>
                                    </div>

                                    <div class="grid grid-cols-3 gap-2 text-center text-xs pt-1">
                                        <div class="p-1.5 rounded bg-[#FFFCFB] border border-[#FFD8D8]">
                                            <span class="block text-[10px] text-[#64748B]">Target Pribadi</span>
                                            <span class="font-extrabold text-[#172B4D]">{{ number_format($targetQty) }} pcs</span>
                                        </div>
                                        <div class="p-1.5 rounded bg-[#FFFCFB] border border-[#FFD8D8]">
                                            <span class="block text-[10px] text-[#64748B]">Aktual Produksi</span>
                                            <span class="font-extrabold text-emerald-700">{{ number_format($actualQty) }} pcs</span>
                                        </div>
                                        <div class="p-1.5 rounded bg-[#FFFCFB] border border-[#FFD8D8]">
                                            <span class="block text-[10px] text-[#64748B]">Sisa Target</span>
                                            <span class="font-extrabold text-[#8E1B5C]">{{ number_format($remainingQty) }} pcs</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Card Footer: Direct Contextual Actions --}}
                            <div class="pt-3 border-t border-[#FFD8D8] flex items-center justify-between gap-2">
                                <a href="{{ route('produksi.batches.show', $job) }}"
                                   class="inline-flex items-center gap-1 text-xs font-semibold text-[#8E1B5C] hover:underline">
                                    <span>Buka Detail &rarr;</span>
                                </a>

                                <div class="flex items-center gap-2">
                                    @if($job->status === \App\Enums\BatchStatus::BAHAN && $isMaterialReady)
                                        <form method="POST" action="{{ route('produksi.batches.start', $job) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="h-8 px-3.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold transition cursor-pointer shadow-xs">
                                                Mulai Kerja
                                            </button>
                                        </form>
                                    @elseif($job->status === \App\Enums\BatchStatus::PRINTING)
                                        <form method="POST" action="{{ route('produksi.batches.complete-printing', $job) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="h-8 px-3.5 rounded-lg bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold transition cursor-pointer shadow-xs">
                                                Selesai Print
                                            </button>
                                        </form>
                                    @elseif($job->status === \App\Enums\BatchStatus::PRODUKSI)
                                        <a href="{{ route('produksi.batches.production-result.create', $job) }}"
                                           class="inline-flex items-center h-8 px-3.5 rounded-lg bg-[#8E1B5C] hover:bg-[#68113F] text-white text-xs font-semibold transition cursor-pointer shadow-xs">
                                            Catat Hasil
                                        </a>
                                    @elseif($job->status === \App\Enums\BatchStatus::QC)
                                        <a href="{{ route('produksi.batches.qc.create', $job) }}"
                                           class="inline-flex items-center h-8 px-3.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold transition cursor-pointer shadow-xs">
                                            Input QC
                                        </a>
                                    @elseif($job->status === \App\Enums\BatchStatus::PACKING)
                                        <form method="POST" action="{{ route('produksi.batches.complete-packing', $job) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="h-8 px-3.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold transition cursor-pointer shadow-xs">
                                                Selesai Packing
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
