<x-app-layout pageTitle="Purchase Order" breadcrumb="Pembelian > Purchase Order">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Purchase Order"
            subtitle="Pengadaan material supplier."
            icon="purchase"
        >
            <span class="sr-only">Daftar Purchase Order</span>
            <x-slot:actions>
                <a href="{{ route('gudang.purchases.create') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Buat Purchase Order</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Navigation Tabs --}}
        <div class="border-b border-[#FFD8D8]">
            <nav class="flex space-x-6 -mb-px">
                <a href="{{ route('gudang.purchases.index') }}"
                   class="py-3 px-1 border-b-2 text-xs font-semibold transition-colors duration-150 inline-flex items-center gap-2 border-[#8E1B5C] text-[#8E1B5C]">
                    <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Daftar Purchase Order</span>
                </a>
                <a href="{{ route('gudang.purchases.history') }}"
                   class="py-3 px-1 border-b-2 text-xs font-medium transition-colors duration-150 inline-flex items-center gap-2 border-transparent text-[#64748B] hover:text-[#172B4D] hover:border-[#FFD8D8]">
                    <svg class="w-4 h-4 text-[#64748B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Riwayat Pembelian Bahan Baku</span>
                </a>
            </nav>
        </div>

        {{-- Filter & Search Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card">
            <form method="GET" action="{{ route('gudang.purchases.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                {{-- Search --}}
                <div class="md:col-span-2">
                    <label for="search" class="block text-[11px] font-bold text-[#64748B] uppercase tracking-wider mb-1">Cari PO / Supplier</label>
                    <div class="relative">
                        <input type="text"
                               id="search"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Ketik nomor PO atau supplier..."
                               class="w-full h-9 pl-9 pr-3 rounded-xl border border-[#FFD8D8] bg-white text-xs text-[#172B4D] placeholder-[#64748B]/60 focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Supplier Filter --}}
                <div>
                    <label for="supplier_id" class="block text-[11px] font-bold text-[#64748B] uppercase tracking-wider mb-1">Supplier</label>
                    <select id="supplier_id"
                            name="supplier_id"
                            class="w-full h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        <option value="">Semua Supplier</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ (string)$supplierFilter === (string)$s->id ? 'selected' : '' }}>
                                {{ $s->supplier_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div>
                    <label for="status" class="block text-[11px] font-bold text-[#64748B] uppercase tracking-wider mb-1">Status</label>
                    <select id="status"
                            name="status"
                            class="w-full h-9 px-3 rounded-xl border border-[#FFD8D8] bg-white text-xs text-[#172B4D] focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ $statusFilter === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="flex-1 h-9 inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs">
                        <span>Filter</span>
                    </button>
                    @if($search || $supplierFilter || $statusFilter || $dateFrom || $dateTo)
                        <a href="{{ route('gudang.purchases.index') }}"
                           class="h-9 px-3 inline-flex items-center justify-center rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition"
                           title="Reset filter">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[#64748B] uppercase font-semibold text-[11px] tracking-wider">
                            <th class="py-3 px-4">No. PO</th>
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Supplier</th>
                            <th class="py-3 px-4 text-center">Item</th>
                            <th class="py-3 px-4 text-right">Total Pembelian</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/60 text-[#172B4D]">
                        @forelse($purchases as $p)
                            @php
                                $total = $service->calculateTotal($p);
                            @endphp
                            <tr class="hover:bg-[#FFD8D8]/30 transition">
                                <td class="py-2.5 px-4 font-mono font-medium text-[#8E1B5C]">
                                    <a href="{{ route('gudang.purchases.show', $p) }}" class="hover:underline">
                                        {{ $p->purchase_no }}
                                    </a>
                                </td>
                                <td class="py-2.5 px-4 whitespace-nowrap text-[#64748B]">
                                    {{ $p->purchase_date?->format('d/m/Y') }}
                                </td>
                                <td class="py-2.5 px-4 font-medium text-[#172B4D]">
                                    {{ $p->supplier?->supplier_name ?? '—' }}
                                </td>
                                <td class="py-2.5 px-4 text-center tabular-nums text-xs text-[#64748B]">
                                    {{ $p->items->count() }} item
                                </td>
                                <td class="py-2.5 px-4 text-right tabular-nums font-medium text-[#172B4D] whitespace-nowrap">
                                    Rp {{ number_format((float)$total, 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-4 text-center whitespace-nowrap">
                                    <x-status-badge :status="$p->effective_status['key']" context="purchase" :label="$p->effective_status['label']" size="table" />
                                </td>
                                <td class="py-2.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('gudang.purchases.show', $p) }}"
                                           class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-[#FFD8D8] bg-white text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition"
                                           title="Lihat Detail">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        @if($service->canEdit($p))
                                            <a href="{{ route('gudang.purchases.edit', $p) }}"
                                               class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-[#FFD8D8] bg-white text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition"
                                               title="Edit Draft">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-[#64748B]">
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold">Tidak ada data Purchase Order ditemukan.</p>
                                        <p class="text-xs">Klik tombol "Buat Purchase Order" untuk memulai pesanan pembelian baru.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($purchases->hasPages())
                <div class="p-4 border-t border-[#FFD8D8]">
                    {{ $purchases->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
