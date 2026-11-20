<x-app-layout pageTitle="Detail Customer">
    <div class="space-y-5">

        {{-- Section A: Header Card --}}
        <x-feature-header
            :title="$customer->customer_name"
            subtitle="Detail informasi profil customer dan riwayat Sales Order."
            icon="customer"
            badge="DETAIL CUSTOMER"
        >
            <x-slot:actions>
                <a href="{{ route('admin.orders.create', ['customer_id' => $customer->id]) }}" class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 ease-out shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Buat Sales Order</span>
                </a>
                <a href="{{ route('admin.customers.edit', $customer) }}" class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Customer</span>
                </a>
                <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 ease-out cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Daftar</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Section B: Two-Column Main Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            
            {{-- Left Column: Customer Profile Card (4/12) --}}
            <div class="lg:col-span-4 bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-4">
                <div class="border-b border-[#FFD8D8] pb-3">
                    <h2 class="text-sm font-bold text-[#172B4D]">Informasi Customer</h2>
                    <p class="text-xs text-[#64748B] mt-0.5">Data kontak dan status akun customer.</p>
                </div>

                <div class="space-y-3.5">
                    {{-- Status Transaksi --}}
                    <div class="space-y-1">
                        <span class="text-[11px] font-semibold text-[#64748B] block">Status Transaksi</span>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $customer->status_badge_class }}">
                                {{ $customer->status_label }}
                            </span>
                        </div>
                        <p class="text-[11px] text-[#64748B]">
                            @if($customer->isDeal())
                                Customer memiliki minimal satu Sales Order resmi dengan pembayaran terkonfirmasi.
                            @else
                                Customer prospek; belum ada Sales Order dengan pembayaran DP/pelunasan terkonfirmasi.
                            @endif
                        </p>
                    </div>

                    {{-- Nama Customer --}}
                    <div class="space-y-0.5 pt-2 border-t border-[#FFD8D8]/60">
                        <span class="text-[11px] font-semibold text-[#64748B] block">Nama Customer</span>
                        <p class="text-sm font-semibold text-[#172B4D]">{{ $customer->customer_name }}</p>
                    </div>

                    {{-- Nomor Telepon --}}
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold text-[#64748B] block">Nomor Telepon / WhatsApp</span>
                        <p class="text-xs font-medium font-mono text-[#172B4D]">{{ $customer->formattedPhone() }}</p>
                    </div>

                    {{-- Tanggal Masuk --}}
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold text-[#64748B] block">Tanggal Masuk / Pertama Tanya</span>
                        <p class="text-xs font-medium text-[#172B4D]">{{ $customer->first_contact_date ? $customer->first_contact_date->translatedFormat('d F Y') : '—' }}</p>
                    </div>

                    {{-- PIC Admin --}}
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold text-[#64748B] block">PIC Admin yang Menangani</span>
                        <p class="text-xs font-medium text-[#172B4D] flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-[#FFD8D8] text-[#8E1B5C] text-[9.5px] font-bold flex items-center justify-center">
                                {{ strtoupper(substr($customer->handledBy?->name ?? 'A', 0, 1)) }}
                            </span>
                            <span>{{ $customer->handledBy?->name ?? 'Belum Ditugaskan' }}</span>
                        </p>
                    </div>

                    {{-- Catatan Prospek --}}
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold text-[#64748B] block">Catatan Prospek</span>
                        <p class="text-xs font-medium text-[#172B4D] leading-relaxed bg-[#FFFCFB] p-2.5 rounded-lg border border-[#FFD8D8]">
                            {{ $customer->notes ?: 'Tidak ada catatan khusus.' }}
                        </p>
                    </div>

                    {{-- Alamat --}}
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold text-[#64748B] block">Alamat Pengiriman / Toko</span>
                        <p class="text-xs font-medium text-[#172B4D] leading-relaxed">{{ $customer->address ?? '—' }}</p>
                    </div>

                    {{-- Status Akun --}}
                    <div class="space-y-1.5 pt-2 border-t border-[#FFD8D8]/60">
                        <span class="text-[11px] font-semibold text-[#64748B] block">Status Akun Login</span>
                        <div>
                            @if($customer->user_id)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-[#FFD8D8] border border-[#FFD8D8]/40 text-[#8E1B5C]">
                                    Terhubung ({{ $customer->user->name ?? 'User' }})
                                </span>
                                @if($customer->user && $customer->user->email)
                                    <p class="text-[11px] text-[#64748B] mt-1 font-mono">{{ $customer->user->email }}</p>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-[#FFFCFB] border border-[#FFD8D8] text-[#64748B]">
                                    Belum Terhubung
                                </span>
                                <p class="text-[11px] text-[#64748B] mt-1">Customer biasa tanpa akun login.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Sales Order History Card (8/12) --}}
            <div class="lg:col-span-8 bg-white border border-[#FFD8D8] rounded-xl overflow-hidden shadow-card">
                <div class="p-4 sm:p-5 border-b border-[#FFD8D8] flex items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <h2 class="text-sm font-bold text-[#172B4D]">Riwayat Sales Order</h2>
                        <p class="text-xs text-[#64748B]">Daftar pesanan yang pernah dibuat untuk customer ini.</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]/30 font-mono">
                        {{ $customer->orders->count() }} Order
                    </span>
                </div>

                @if($customer->orders->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">No. Order</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider whitespace-nowrap">Tanggal</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-right whitespace-nowrap">Total</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap">Status</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-[#64748B] uppercase tracking-wider text-center whitespace-nowrap w-[70px]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FFD8D8]/70">
                                @foreach($customer->orders as $order)
                                    <tr class="transition-colors duration-150 ease-out hover:bg-[#FFD8D8]/50">
                                        <td class="px-4 py-3 font-mono font-bold text-xs text-[#8E1B5C] whitespace-nowrap">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="hover:underline hover:text-[#8E1B5C] transition-colors cursor-pointer">
                                                {{ $order->order_no }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-[#64748B] whitespace-nowrap">
                                            {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-xs text-[#172B4D] whitespace-nowrap">
                                            {{ \App\Support\MoneyFormatter::rupiah($order->grand_total ?? $order->total_amount ?? 0) }}
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="badge {{ $order->status->badgeClass() }} text-[10px]">
                                                {{ $order->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <a href="{{ route('admin.orders.show', $order) }}"
                                               class="w-8 h-8 rounded-lg bg-[#8E1B5C] text-white border border-[#8E1B5C] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] inline-flex items-center justify-center transition-all duration-200 ease-out cursor-pointer shadow-xs shrink-0"
                                               title="Lihat Detail Sales Order"
                                               aria-label="Lihat Detail Sales Order">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 text-center text-[#64748B] bg-[#FFFCFB] cursor-default">
                        <div class="w-10 h-10 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center mx-auto mb-3 shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="font-bold text-[#172B4D] text-sm">Belum ada Sales Order</p>
                        <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">Customer ini belum memiliki riwayat pesanan.</p>
                    </div>
                @endif
            </div>

        </div>

    </div>
</x-app-layout>
