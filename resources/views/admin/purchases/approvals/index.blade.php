<x-app-layout pageTitle="Persetujuan Purchase Order" breadcrumb="Keuangan Admin > Persetujuan PO">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Persetujuan Purchase Order"
            subtitle="Verifikasi dan persetujuan pengajuan pengadaan bahan dari Gudang."
            icon="purchase"
        >
            <x-slot:actions>
                <a href="{{ route('admin.purchases.payments.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition shadow-xs">
                    <svg class="w-4 h-4 text-[#64748B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Pembayaran Supplier</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Filter Tabs --}}
        <div class="border-b border-[#FFD8D8]">
            <nav class="flex space-x-4 sm:space-x-6 -mb-px overflow-x-auto">
                <a href="{{ route('admin.purchases.approvals.index', ['status' => 'PENDING_APPROVAL']) }}"
                   class="py-3 px-1 border-b-2 text-xs font-semibold whitespace-nowrap transition-colors duration-150 inline-flex items-center gap-2 {{ $statusFilter === 'PENDING_APPROVAL' ? 'border-[#8E1B5C] text-[#8E1B5C]' : 'border-transparent text-[#64748B] hover:text-[#172B4D] hover:border-[#FFD8D8]' }}">
                    <span>Menunggu Persetujuan</span>
                    @if($pendingCount > 0)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-[#8E1B5C] text-white">
                            {{ $pendingCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('admin.purchases.approvals.index', ['status' => 'APPROVED']) }}"
                   class="py-3 px-1 border-b-2 text-xs font-semibold whitespace-nowrap transition-colors duration-150 inline-flex items-center gap-2 {{ $statusFilter === 'APPROVED' ? 'border-[#8E1B5C] text-[#8E1B5C]' : 'border-transparent text-[#64748B] hover:text-[#172B4D] hover:border-[#FFD8D8]' }}">
                    <span>Disetujui</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-800">
                        {{ $approvedCount }}
                    </span>
                </a>
                <a href="{{ route('admin.purchases.approvals.index', ['status' => 'REVISION_REQUIRED']) }}"
                   class="py-3 px-1 border-b-2 text-xs font-semibold whitespace-nowrap transition-colors duration-150 inline-flex items-center gap-2 {{ $statusFilter === 'REVISION_REQUIRED' ? 'border-[#8E1B5C] text-[#8E1B5C]' : 'border-transparent text-[#64748B] hover:text-[#172B4D] hover:border-[#FFD8D8]' }}">
                    <span>Perlu Revisi</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-orange-100 text-orange-800">
                        {{ $revisionCount }}
                    </span>
                </a>
                <a href="{{ route('admin.purchases.approvals.index', ['status' => 'REJECTED']) }}"
                   class="py-3 px-1 border-b-2 text-xs font-semibold whitespace-nowrap transition-colors duration-150 inline-flex items-center gap-2 {{ $statusFilter === 'REJECTED' ? 'border-[#8E1B5C] text-[#8E1B5C]' : 'border-transparent text-[#64748B] hover:text-[#172B4D] hover:border-[#FFD8D8]' }}">
                    <span>Ditolak</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-rose-100 text-rose-800">
                        {{ $rejectedCount }}
                    </span>
                </a>
                <a href="{{ route('admin.purchases.approvals.index', ['status' => 'ALL']) }}"
                   class="py-3 px-1 border-b-2 text-xs font-semibold whitespace-nowrap transition-colors duration-150 inline-flex items-center gap-2 {{ $statusFilter === 'ALL' ? 'border-[#8E1B5C] text-[#8E1B5C]' : 'border-transparent text-[#64748B] hover:text-[#172B4D] hover:border-[#FFD8D8]' }}">
                    <span>Semua Pengajuan</span>
                </a>
            </nav>
        </div>

        {{-- Search Bar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl p-4 shadow-card">
            <form method="GET" action="{{ route('admin.purchases.approvals.index') }}" class="flex items-center gap-3">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="relative flex-1">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nomor PO atau nama supplier..."
                           class="w-full h-9 pl-9 pr-3 rounded-xl border border-[#FFD8D8] bg-white text-xs text-[#172B4D] placeholder-[#64748B]/60 focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C] transition">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
                <button type="submit"
                        class="h-9 px-4 inline-flex items-center justify-center rounded-xl bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition shadow-xs">
                    Cari
                </button>
                @if($search)
                    <a href="{{ route('admin.purchases.approvals.index', ['status' => $statusFilter]) }}"
                       class="h-9 px-3 inline-flex items-center justify-center rounded-xl border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] transition">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-2xl shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#FFD8D8] border-b border-[#FFD8D8] text-[#64748B] uppercase font-bold text-[10px] tracking-wider">
                            <th class="py-3 px-4">No. PO</th>
                            <th class="py-3 px-4">Tanggal Diajukan</th>
                            <th class="py-3 px-4">Diajukan Oleh</th>
                            <th class="py-3 px-4">Supplier</th>
                            <th class="py-3 px-4 text-center">Jumlah Material</th>
                            <th class="py-3 px-4 text-right">Total PO</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/60 text-[#172B4D]">
                        @forelse($purchases as $p)
                            <tr class="hover:bg-[#FFD8D8]/30 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-[#8E1B5C]">
                                    <a href="{{ route('admin.purchases.approvals.show', $p) }}" class="hover:underline">
                                        {{ $p->purchase_no }}
                                    </a>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-[#64748B]">
                                    {{ $p->submitted_at ? $p->submitted_at->format('d/m/Y H:i') : ($p->purchase_date ? $p->purchase_date->format('d/m/Y') : '—') }}
                                </td>
                                <td class="py-3.5 px-4 text-[#172B4D]">
                                    {{ $p->submittedByUser?->name ?? 'Gudang' }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-[#172B4D]">
                                    {{ $p->supplier?->supplier_name ?? '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono">
                                    {{ $p->items->count() }} item
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-[#172B4D] whitespace-nowrap">
                                    Rp {{ number_format((float)$p->total_amount_string, 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <x-status-badge :status="$p->approval_status" context="approval" size="table" />
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.purchases.approvals.show', $p) }}"
                                       class="inline-flex items-center justify-center gap-1.5 h-7 px-3 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] transition">
                                        <span>Review PO</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-[#64748B]">
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold">Tidak ada data Purchase Order dalam antrean ini.</p>
                                        <p class="text-xs">Pengajuan yang diajukan oleh staf gudang akan muncul di sini.</p>
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
