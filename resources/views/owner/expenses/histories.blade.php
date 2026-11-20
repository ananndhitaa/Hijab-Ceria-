<x-app-layout pageTitle="Riwayat Audit Pengeluaran" breadcrumb="Executive Monitoring > Riwayat Audit Pengeluaran">
    <div class="w-full max-w-none mx-0 space-y-4 sm:space-y-5">

        {{-- ── 1. Page Header (Concise Top Identity) ───────────────────────── --}}
        <x-feature-header
            title="Audit Trail Pengeluaran"
            subtitle="Inspeksi riwayat koreksi finansial dan bukti audit atas pengeluaran operasional outlet ini."
            icon="finance"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        EXECUTIVE AUDIT
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                        Read-Only Owner
                    </span>
                </div>
            </x-slot:badge>
            <x-slot:actions>
                <a href="{{ route('owner.dashboard') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Primary Financial Expense Summary ─────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#FFD8D8]">
                <div>
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Nominal Saat Ini</span>
                    <span class="text-2xl sm:text-3xl font-extrabold text-[#8E1B5C] mt-0.5 block font-mono">
                        {{ \App\Support\MoneyFormatter::rupiah($expense->amount) }}
                    </span>
                </div>
                <div class="text-right sm:text-right">
                    <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Tanggal Pengeluaran</span>
                    <span class="text-sm font-bold text-[#172B4D] block mt-0.5">
                        {{ $expense->expense_date ? $expense->expense_date->format('d F Y') : '-' }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 text-xs">
                <div class="bg-[#FFFCFB] p-2.5 rounded-lg border border-[#FFD8D8]">
                    <span class="text-[11px] text-[#64748B] block">Jenis Pengeluaran:</span>
                    <span class="font-semibold text-[#172B4D]">{{ $expense->expense_type?->label() ?? $expense->expense_type }}</span>
                </div>
                <div class="bg-[#FFFCFB] p-2.5 rounded-lg border border-[#FFD8D8]">
                    <span class="text-[11px] text-[#64748B] block">Terkait Pesanan:</span>
                    <span class="font-semibold text-[#172B4D]">{{ $expense->order?->order_no ?? 'Pengeluaran Umum' }}</span>
                </div>
                <div class="bg-[#FFFCFB] p-2.5 rounded-lg border border-[#FFD8D8]">
                    <span class="text-[11px] text-[#64748B] block">Pencatat Awal:</span>
                    <span class="font-semibold text-[#172B4D]">{{ $expense->createdByUser->name ?? 'Admin' }}</span>
                </div>
            </div>

            <div class="mt-3 p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] text-xs">
                <span class="text-[#64748B] text-[11px] block font-medium mb-0.5">Keterangan:</span>
                <p class="text-[#172B4D] leading-relaxed">{{ $expense->description }}</p>
            </div>
        </div>

        {{-- ── 3. Riwayat Audit Koreksi (Immutable History Records) ────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card space-y-4">
            <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#8E1B5C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                        Log Riwayat Koreksi Finansial (Audit Trail)
                    </h2>
                </div>
                <span class="text-[11px] font-semibold text-[#8E1B5C] bg-[#FFFCFB] px-2.5 py-0.5 rounded-full border border-[#FFD8D8]">
                    {{ $histories->count() }} Catatan Perubahan
                </span>
            </div>

            @if($histories->isEmpty())
                <div class="p-6 text-center text-xs text-[#64748B] bg-[#FFFCFB] rounded-lg border border-[#FFD8D8]">
                    Belum ada riwayat koreksi untuk catatan pengeluaran ini. Data masih sesuai dengan input pencatatan awal.
                </div>
            @else
                <div class="space-y-3">
                    @foreach($histories as $history)
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
            @endif
        </div>

    </div>
</x-app-layout>
