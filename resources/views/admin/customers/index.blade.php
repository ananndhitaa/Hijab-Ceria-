<x-app-layout pageTitle="Customer" breadcrumb="Penjualan > Customer">
    <div class="space-y-5">

        {{-- Feature Header --}}
        <x-feature-header
            title="Customer"
            subtitle="Kelola data pelanggan dan kontak terhubung."
            icon="customer"
        >
            <x-slot:actions>
                <a href="{{ route('admin.customers.create') }}" class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Customer</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Search Toolbar --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 shadow-card">
            <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
                {{-- Search Box --}}
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#64748B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nama, telepon, atau alamat customer..."
                           class="w-full h-10 pl-10 pr-3.5 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] placeholder:text-[#64748B]/60 focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all">
                </div>

                {{-- Status Filter --}}
                <div class="w-full sm:w-44 shrink-0">
                    <select name="status"
                            class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#FFD8D8] focus:ring-2 focus:ring-[#8E1B5C]/15 transition-all cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="BELUM_DEAL" {{ ($statusFilter ?? '') === 'BELUM_DEAL' ? 'selected' : '' }}>Belum Deal</option>
                        <option value="DEAL" {{ ($statusFilter ?? '') === 'DEAL' ? 'selected' : '' }}>Deal</option>
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer flex-1 sm:flex-initial">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Cari</span>
                    </button>
                    @if($search || !empty($statusFilter))
                        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer flex-1 sm:flex-initial">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Section C: Customers Table Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap w-[50px]">#</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Customer</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">WhatsApp</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Tanggal Masuk</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap text-center">Status</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">PIC Admin</th>
                            <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[110px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#FFD8D8]/70">
                        @forelse($customers as $index => $customer)
                            <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                <td class="px-4 py-3 font-mono text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $customers->firstItem() + $index }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="font-semibold text-xs text-[#172B4D] hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer block">
                                        {{ $customer->customer_name }}
                                    </a>
                                    @if($customer->address)
                                        <p class="text-[11px] text-[#64748B] truncate max-w-xs mt-0.5" title="{{ $customer->address }}">
                                            {{ $customer->address }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs font-mono text-[#172B4D] whitespace-nowrap">
                                    {{ $customer->formattedPhone() }}
                                </td>
                                <td class="px-4 py-3 text-xs text-[#64748B] whitespace-nowrap">
                                    {{ $customer->first_contact_date ? $customer->first_contact_date->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $customer->status_badge_class }}">
                                        {{ $customer->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-[#172B4D] whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-medium">
                                        <span class="w-5 h-5 rounded-full bg-[#FFD8D8] text-[#8E1B5C] text-[9.5px] font-bold flex items-center justify-center">
                                            {{ strtoupper(substr($customer->handledBy?->name ?? 'A', 0, 1)) }}
                                        </span>
                                        <span>{{ $customer->handledBy?->name ?? '—' }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('admin.customers.show', $customer) }}"
                                           class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                           title="Lihat Detail Customer"
                                           aria-label="Lihat Detail Customer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.customers.edit', $customer) }}"
                                           class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                           title="Edit Customer"
                                           aria-label="Edit Customer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                                    <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-[#172B4D] text-sm">Belum ada Customer</p>
                                    <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">Tambahkan data customer agar dapat digunakan saat membuat Sales Order.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('admin.customers.create') }}" class="inline-flex items-center justify-center gap-1.5 h-9 px-3.5 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#8E1B5C] transition-all shadow-xs cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span>Tambah Customer</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="px-5 py-3.5 border-t border-[#FFD8D8] bg-[#FFFCFB]">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
