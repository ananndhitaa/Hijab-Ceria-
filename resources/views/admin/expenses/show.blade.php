<x-app-layout pageTitle="Detail Pengeluaran Bisnis" breadcrumb="Keuangan > Biaya & Pengeluaran > Detail">
    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-5">


        {{-- ── 1. Page Header (Concise Top Identity) ───────────────────────── --}}
        <x-feature-header
            title="Detail Pengeluaran"
            icon="finance"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        PENGELUARAN BISNIS
                    </span>
                    <span class="badge {{ $expense->expense_type->badgeClass() }} text-xs px-2.5 py-0.5">
                        {{ $expense->expense_type->label() }}
                    </span>
                </div>
            </x-slot:badge>
            <p class="text-xs text-[#64748B] flex items-center gap-1.5 font-medium mt-1">
                <span>{{ $expense->expense_date ? $expense->expense_date->format('d M Y') : '-' }}</span>
                <span class="text-[#FFD8D8]">&middot;</span>
                @if($expense->order)
                    <span>Terkait Sales Order:</span>
                    <a href="{{ route('admin.orders.show', $expense->order) }}" class="font-mono font-semibold text-[#8E1B5C] hover:underline">
                        {{ $expense->order->order_no }}
                    </a>
                @else
                    <span>Pengeluaran Umum</span>
                @endif
            </p>
            <x-slot:actions>
                <a href="{{ route('admin.expenses.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Kembali ke daftar pengeluaran">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>

                <a href="{{ route('admin.expenses.edit', $expense) }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl bg-[#8E1B5C] text-white text-xs sm:text-sm font-semibold hover:bg-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Pengeluaran</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Primary Financial Emphasis Card ──────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Nominal Biaya</span>
                    <span class="text-2xl sm:text-3xl font-extrabold text-[#8E1B5C] mt-0.5 block">
                        {{ \App\Support\MoneyFormatter::rupiah($expense->amount) }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge {{ $expense->expense_type->badgeClass() }} text-xs px-3 py-1 font-semibold">
                        {{ $expense->expense_type->label() }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ── 3. Detail Pengeluaran (Compact Detail Card) ─────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-4">
            <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2">
                Rincian Pengeluaran
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80 space-y-1">
                    <span class="text-[#64748B] block text-[11px]">Tanggal Pengeluaran</span>
                    <span class="font-bold text-[#172B4D] text-sm">
                        {{ $expense->expense_date ? $expense->expense_date->format('d F Y') : '-' }}
                    </span>
                </div>

                <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80 space-y-1">
                    <span class="text-[#64748B] block text-[11px]">Jenis Pengeluaran</span>
                    <span class="font-bold text-[#172B4D] text-sm">
                        {{ $expense->expense_type->label() }}
                    </span>
                </div>

                @if($expense->campaign_name)
                    <div class="md:col-span-2 p-3 bg-[#FFD8D8]/50 rounded-lg border border-[#FFD8D8]/40 space-y-0.5">
                        <span class="text-[#8E1B5C] block text-[10px] font-bold uppercase tracking-wider">Nama Kampanye / Channel Iklan</span>
                        <span class="font-bold text-[#172B4D] text-sm">{{ $expense->campaign_name }}</span>
                    </div>
                @endif

                <div class="md:col-span-2 space-y-1">
                    <span class="text-[#64748B] block text-[11px] font-medium">Keterangan / Deskripsi</span>
                    <div class="p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] text-[#172B4D] text-xs leading-relaxed whitespace-pre-line">
                        {{ $expense->description }}
                    </div>
                </div>
            </div>

            {{-- Small Muted Footer Row --}}
            <div class="pt-3 border-t border-[#FFD8D8]/60 text-[11px] text-[#64748B] flex items-center justify-between">
                <span>Dicatat oleh <strong class="text-[#172B4D]">{{ $expense->createdByUser->name ?? 'Administrator' }}</strong></span>
                <span>{{ \App\Support\DateTimeFormatter::formatDateTime($expense->created_at) }}</span>
            </div>
        </div>

        {{-- ── 4. Pesanan Terkait (Compact Related Order Card) ─────────────── --}}
        @if($expense->order)
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-3">
                <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2">
                    Pesanan Terkait
                </h2>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8]">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-[#8E1B5C] text-sm">{{ $expense->order->order_no }}</span>
                            <span class="badge {{ $expense->order->status->badgeClass() }} text-[10px]">
                                {{ $expense->order->status->label() }}
                            </span>
                        </div>
                        <p class="text-xs text-[#64748B] flex flex-wrap items-center gap-1.5 font-medium">
                            <span class="text-[#172B4D] font-semibold">{{ $expense->order->customer->customer_name ?? '-' }}</span>
                            <span class="text-[#FFD8D8]">&middot;</span>
                            <span>{{ $expense->order->order_date ? $expense->order->order_date->format('d/m/Y') : '-' }}</span>
                            <span class="text-[#FFD8D8]">&middot;</span>
                            <span class="font-bold text-[#8E1B5C]">{{ \App\Support\MoneyFormatter::rupiah($expense->order->total_amount) }}</span>
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('admin.orders.show', $expense->order) }}"
                           class="inline-flex items-center justify-center gap-1.5 h-8 px-3.5 rounded-lg border border-[#FFD8D8] bg-white text-xs font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                            <svg class="w-3.5 h-3.5 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Lihat Sales Order</span>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── 5. Riwayat Audit Koreksi Pengeluaran (Immutable History Trail) ─ --}}
        @if($expense->histories && $expense->histories->isNotEmpty())
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-4">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                            Riwayat Audit Koreksi Pengeluaran (Audit Trail)
                        </h2>
                    </div>
                    <span class="text-[11px] font-semibold text-[#8E1B5C] bg-[#FFFCFB] px-2.5 py-0.5 rounded-full border border-[#FFD8D8]">
                        {{ $expense->histories->count() }} Perubahan Tercatat
                    </span>
                </div>

                <div class="space-y-3">
                    @foreach($expense->histories as $history)
                        <div class="p-3.5 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] space-y-2.5 text-xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-2 border-b border-[#FFD8D8]/60">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-[#172B4D]">{{ $history->actorUser->name ?? 'Pengguna ID #'.$history->actor_user_id }}</span>
                                    <span class="text-[#64748B] text-[11px]">({{ $history->actorUser?->role?->label() ?? 'Admin' }})</span>
                                </div>
                                <span class="text-[11px] text-[#64748B] font-mono">
                                    {{ \App\Support\DateTimeFormatter::formatDateTime($history->created_at) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-[11px]">
                                <div class="bg-white p-2 rounded border border-[#FFD8D8]/80">
                                    <span class="text-[#64748B] block font-medium">Nominal Sebelum &rarr; Sesudah:</span>
                                    <div class="flex items-center gap-1.5 font-mono mt-0.5">
                                        <span class="text-[#64748B] line-through">{{ \App\Support\MoneyFormatter::rupiah($history->old_amount) }}</span>
                                        <span class="text-[#172B4D] font-bold">&rarr;</span>
                                        <span class="text-[#8E1B5C] font-bold">{{ \App\Support\MoneyFormatter::rupiah($history->new_amount) }}</span>
                                    </div>
                                </div>

                                <div class="bg-white p-2 rounded border border-[#FFD8D8]/80">
                                    <span class="text-[#64748B] block font-medium">Tanggal Sebelum &rarr; Sesudah:</span>
                                    <div class="flex items-center gap-1.5 font-mono mt-0.5">
                                        <span class="text-[#64748B]">{{ $history->old_expense_date ? $history->old_expense_date->format('d/m/Y') : '-' }}</span>
                                        <span class="text-[#172B4D] font-bold">&rarr;</span>
                                        <span class="text-[#172B4D] font-bold">{{ $history->new_expense_date ? $history->new_expense_date->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </div>

                                @if($history->old_expense_type !== $history->new_expense_type)
                                    <div class="bg-white p-2 rounded border border-[#FFD8D8]/80">
                                        <span class="text-[#64748B] block font-medium">Jenis Pengeluaran:</span>
                                        <div class="mt-0.5">
                                            <span class="line-through text-[#64748B]">{{ $history->old_expense_type }}</span>
                                            <span class="text-[#172B4D] font-bold">&rarr;</span>
                                            <span class="font-bold text-[#172B4D]">{{ $history->new_expense_type }}</span>
                                        </div>
                                    </div>
                                @endif

                                @if($history->old_order_id !== $history->new_order_id)
                                    <div class="bg-white p-2 rounded border border-[#FFD8D8]/80">
                                        <span class="text-[#64748B] block font-medium">Atribusi Pesanan:</span>
                                        <div class="mt-0.5 font-mono">
                                            <span class="text-[#64748B]">{{ $history->oldOrder?->order_no ?? 'Umum' }}</span>
                                            <span class="text-[#172B4D] font-bold">&rarr;</span>
                                            <span class="text-[#8E1B5C] font-bold">{{ $history->newOrder?->order_no ?? 'Umum' }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="p-2 bg-amber-50/70 border border-amber-200/60 rounded text-[11px] text-amber-950">
                                <span class="font-bold text-amber-900 block mb-0.5">Alasan Perubahan:</span>
                                <span class="italic">"{{ $history->reason }}"</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
