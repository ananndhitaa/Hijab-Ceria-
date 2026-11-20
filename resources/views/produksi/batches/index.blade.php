<x-app-layout pageTitle="Batch Produksi" breadcrumb="Operasional > Batch Produksi">
    <div class="space-y-6">

        {{-- Feature Header --}}
        <x-feature-header
            title="Batch Produksi"
            subtitle="Antrean pengerjaan dan progres manufaktur."
            icon="production"
        >
            <span class="sr-only">Antrean Batch Produksi</span>
        </x-feature-header>

        {{-- Filter & Search Card --}}
        <div class="card p-4 sm:p-5">
            <form method="GET" action="{{ route('produksi.batches.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- Search --}}
                <div class="space-y-1">
                    <label for="search" class="block text-[11px] font-semibold text-[#64748B] uppercase tracking-wider">Pencarian</label>
                    <input type="text"
                           name="search"
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="No. Batch, Order, Produk..."
                           class="w-full h-9 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                </div>

                {{-- Filter Status --}}
                <div class="space-y-1">
                    <label for="status" class="block text-[11px] font-semibold text-[#64748B] uppercase tracking-wider">Status Batch</label>
                    <select name="status"
                            id="status"
                            class="w-full h-9 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        <option value="">Semua Status</option>
                        @foreach(\App\Enums\BatchStatus::cases() as $st)
                            <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Route --}}
                <div class="space-y-1">
                    <label for="route" class="block text-[11px] font-semibold text-[#64748B] uppercase tracking-wider">Rute Produksi</label>
                    <select name="route"
                            id="route"
                            class="w-full h-9 px-3 rounded-lg border border-[#FFD8D8] bg-[#FFFCFB] text-xs text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        <option value="">Semua Rute</option>
                        <option value="printing" {{ request('route') === 'printing' ? 'selected' : '' }}>Dengan Printing</option>
                        <option value="plain" {{ request('route') === 'plain' ? 'selected' : '' }}>Tanpa Printing</option>
                        <option value="unassigned" {{ request('route') === 'unassigned' ? 'selected' : '' }}>Belum Ditetapkan</option>
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="flex-1 h-9 px-3 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'status', 'route']))
                        <a href="{{ route('produksi.batches.index') }}"
                           class="h-9 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition inline-flex items-center justify-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Batches Table Card --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8] text-[11.5px] sm:text-[12px] font-semibold text-[#64748B] uppercase tracking-wider">
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap align-middle">No. Batch</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap align-middle">Sales Order & Customer</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap align-middle">Produk & Varian</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap text-center align-middle">Target</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap text-center align-middle">Target Selesai</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap text-center align-middle">Rute Produksi</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap text-center align-middle">Status</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap text-center align-middle">Kesiapan Bahan</th>
                            <th class="px-4 py-2.5 sm:py-3 whitespace-nowrap text-center align-middle w-[120px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]">
                        @forelse($batches as $batch)
                            <tr class="hover:bg-[#FFD8D8]/30 transition">
                                <td class="px-4 py-3.5 font-medium font-mono text-[#8E1B5C] whitespace-nowrap">
                                    <a href="{{ route('produksi.batches.show', $batch) }}" class="hover:underline">
                                        {{ $batch->batch_no }}
                                    </a>
                                    @if($batch->productionEmployee)
                                        <div class="text-[11px] font-normal text-[#64748B] flex items-center gap-1 mt-0.5">
                                            <span>PIC: {{ $batch->productionEmployee->name }}</span>
                                            @if(auth()->id() === $batch->production_employee_id)
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-[#8E1B5C]/10 text-[#8E1B5C]">
                                                    PIC Anda
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-medium text-[#172B4D]">{{ $batch->orderItem?->order?->order_no ?? '-' }}</div>
                                    <div class="text-[11px] text-[#64748B]">{{ $batch->orderItem?->order?->customer?->customer_name ?? 'Customer Umum' }}</div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-medium text-[#172B4D]">{{ $batch->orderItem?->variant?->product?->product_name ?? '-' }}</div>
                                    <div class="text-[11px] text-[#64748B]">{{ $batch->orderItem?->variant?->variant_name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-center font-medium tabular-nums text-[#172B4D] whitespace-nowrap">
                                    {{ \App\Support\QuantityFormatter::format($batch->target_qty, 'pcs') }}
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <div class="font-normal text-[#172B4D]">{{ \App\Support\DateTimeFormatter::formatDate($batch->planned_end_date, 'd M Y', 'Belum ditentukan') }}</div>
                                    @if($batch->is_overdue)
                                        <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap bg-rose-50 text-rose-700 border border-rose-200">Terlambat</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center px-2.5 h-6 rounded-lg text-[11px] font-semibold whitespace-nowrap border {{ $batch->route_badge_class }}">
                                        {{ $batch->route_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <x-status-badge :status="$batch->status" context="batch" size="table" />
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    @if($batch->isMaterialReady())
                                        <x-status-badge status="READY" context="stock" label="Siap" size="table" />
                                    @else
                                        <x-status-badge status="NOT_READY" context="stock" label="Belum Siap" size="table" />
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center align-middle whitespace-nowrap w-[120px]">
                                    <div class="flex items-center justify-center">
                                        <a href="{{ route('produksi.batches.show', $batch) }}"
                                           class="inline-flex items-center justify-center gap-2 h-8 px-3.5 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition shadow-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <span>Detail</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-[#64748B]">
                                    Tidak ada data batch produksi yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
                <div class="p-4 border-t border-[#FFD8D8]">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
