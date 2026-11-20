<x-app-layout pageTitle="Biaya & Pengeluaran" breadcrumb="Keuangan > Biaya & Pengeluaran">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Biaya & Pengeluaran"
            subtitle="Beban operasional usaha."
            icon="finance"
        >
            <x-slot:actions>
                <a href="{{ route('admin.expenses.create') }}" class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Catat Pengeluaran</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Filter Toolbar Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card">
            <form method="GET" action="{{ route('admin.expenses.index') }}" class="space-y-4">
                {{-- Row 1: Search, Jenis, Alokasi --}}
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-6 space-y-1">
                        <label for="search" class="block text-xs font-semibold text-[#172B4D]">Pencarian</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#64748B]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   value="{{ $search }}"
                                   placeholder="Cari deskripsi, kampanye, atau order..."
                                   class="w-full h-10 pl-10 pr-3.5 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                        </div>
                    </div>
                    <div class="md:col-span-3 space-y-1">
                        <label for="expense_type" class="block text-xs font-semibold text-[#172B4D]">Jenis Pengeluaran</label>
                        <select name="expense_type" id="expense_type" class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                            <option value="">Semua Jenis</option>
                            @foreach($expenseTypes as $type)
                                <option value="{{ $type->value }}" {{ $typeFilter === $type->value ? 'selected' : '' }}>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3 space-y-1">
                        <label for="attribution" class="block text-xs font-semibold text-[#172B4D]">Alokasi Order</label>
                        <select name="attribution" id="attribution" class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                            <option value="">Semua Alokasi</option>
                            <option value="order" {{ $attributionFilter === 'order' ? 'selected' : '' }}>Terkait Order Tertentu</option>
                            <option value="general" {{ $attributionFilter === 'general' ? 'selected' : '' }}>Pengeluaran Umum (Non-Order)</option>
                        </select>
                    </div>
                </div>

                {{-- Row 2: Date Range, Submit, Reset --}}
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 pt-3 border-t border-[#FFD8D8]/60">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full sm:w-auto">
                        <div class="space-y-1">
                            <label for="start_date" class="block text-xs font-semibold text-[#172B4D]">Dari Tanggal</label>
                            <input type="date"
                                   id="start_date"
                                   name="start_date"
                                   value="{{ $startDate }}"
                                   class="w-full sm:w-44 h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                        </div>
                        <div class="space-y-1">
                            <label for="end_date" class="block text-xs font-semibold text-[#172B4D]">Sampai Tanggal</label>
                            <input type="date"
                                   id="end_date"
                                   name="end_date"
                                   value="{{ $endDate }}"
                                   class="w-full sm:w-44 h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                        <button type="submit" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer flex-1 sm:flex-initial">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span>Terapkan Filter</span>
                        </button>
                        @if($search || $typeFilter || $attributionFilter || $startDate || $endDate)
                            <a href="{{ route('admin.expenses.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer flex-1 sm:flex-initial">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Section C: Expenses Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[140px]">Tanggal</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[160px]">Jenis</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider">Kampanye / Deskripsi</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[180px]">Order Terkait</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-right whitespace-nowrap w-[160px]">Nominal</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[110px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @forelse($expenses as $expense)
                            <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                <td class="px-4 py-3 text-xs text-[#64748B] whitespace-nowrap font-medium">
                                    {{ $expense->expense_date ? $expense->expense_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="badge {{ $expense->expense_type->badgeClass() }} text-[10px]">
                                        {{ $expense->expense_type->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if($expense->expense_type === \App\Enums\ExpenseType::MARKETING && $expense->campaign_name)
                                        <p class="font-semibold text-[#172B4D] leading-tight truncate max-w-xs md:max-w-md" title="{{ $expense->campaign_name }}">
                                            {{ $expense->campaign_name }}
                                        </p>
                                        <p class="text-[11px] text-[#64748B] mt-0.5 truncate max-w-xs md:max-w-md" title="{{ $expense->description }}">
                                            {{ $expense->description }}
                                        </p>
                                    @else
                                        <p class="text-[#172B4D] font-medium truncate max-w-xs md:max-w-md" title="{{ $expense->description }}">
                                            {{ $expense->description }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap">
                                    @if($expense->order)
                                        <a href="{{ route('admin.orders.show', $expense->order) }}" class="font-mono font-bold text-xs text-[#8E1B5C] hover:underline block">
                                            {{ $expense->order->order_no }}
                                        </a>
                                        <span class="text-[11px] text-[#64748B] truncate max-w-[160px] block" title="{{ $expense->order->customer->customer_name ?? '-' }}">
                                            {{ $expense->order->customer->customer_name ?? '-' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#FFFCFB] border border-[#FFD8D8] text-[#64748B]">
                                            Umum
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-xs text-[#172B4D] whitespace-nowrap">
                                    {{ \App\Support\MoneyFormatter::rupiah($expense->amount) }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('admin.expenses.show', $expense) }}"
                                           class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                           title="Lihat Detail"
                                           aria-label="Lihat Detail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.expenses.edit', $expense) }}"
                                           class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                           title="Edit Pengeluaran"
                                           aria-label="Edit Pengeluaran">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-[#172B4D] text-sm">Belum ada Pengeluaran</p>
                                    <p class="text-xs text-[#64748B] mt-1 max-w-md mx-auto">Catat biaya operasional, marketing, atau pengeluaran bisnis lainnya.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('admin.expenses.create') }}" class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Catat Pengeluaran</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($expenses->hasPages())
                <div class="px-5 py-3.5 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
