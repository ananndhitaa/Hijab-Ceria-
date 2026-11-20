<x-app-layout pageTitle="Detail Batch Produksi" breadcrumb="Batch Produksi > Detail">
    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-5">

        {{-- ── 1. Header & Quick Actions ─────────────────────────────────── --}}
        <x-feature-header
            :title="$batch->batch_no"
            icon="production"
        >
            <x-slot:badge>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                        BATCH PRODUKSI
                    </span>
                    <span class="badge {{ $batch->status->badgeClass() }} text-xs px-2.5 py-0.5">
                        {{ $batch->status->label() }}
                    </span>
                </div>
            </x-slot:badge>
            <p class="text-xs text-[#64748B] flex items-center gap-1.5 font-medium mt-1">
                <span>{{ $batch->created_at ? $batch->created_at->format('d M Y') : '-' }}</span>
                @if($batch->orderItem && $batch->orderItem->order)
                    <span class="text-[#FFD8D8]">&middot;</span>
                    <span>Terkait Sales Order:</span>
                    <a href="{{ route('admin.orders.show', $batch->orderItem->order) }}" class="font-mono font-semibold text-[#8E1B5C] hover:underline">
                        {{ $batch->orderItem->order->order_no }}
                    </a>
                @endif
            </p>
            <x-slot:actions>
                <a href="{{ route('admin.batches.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs"
                   title="Kembali ke daftar batch">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali</span>
                </a>

                @if($batch->orderItem && $batch->orderItem->order)
                    <a href="{{ route('admin.orders.show', $batch->orderItem->order) }}"
                       class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:border-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                        <svg class="w-3.5 h-3.5 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Lihat Sales Order</span>
                    </a>
                @endif
            </x-slot:actions>
        </x-feature-header>

        {{-- ── 2. Comprehensive 7-Metric Production & QC Overview ────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5 sm:gap-3">
            {{-- 1. Target Produksi --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-3 sm:p-3.5 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Target Produksi</span>
                <span class="text-base sm:text-lg font-extrabold text-[#172B4D] mt-0.5 block font-mono">
                    {{ number_format($batch->target_qty, 0, ',', '.') }} <span class="text-[10px] font-semibold text-[#64748B]">pcs</span>
                </span>
            </div>

            {{-- 2. Aktual Produksi --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-3 sm:p-3.5 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Aktual Produksi</span>
                <span class="text-base sm:text-lg font-extrabold text-[#8E1B5C] mt-0.5 block font-mono">
                    {{ number_format($batch->total_actual_qty, 0, ',', '.') }} <span class="text-[10px] font-semibold text-[#64748B]">pcs</span>
                </span>
            </div>

            {{-- 3. Sisa Target (Kekurangan) --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-3 sm:p-3.5 shadow-card {{ $batch->remaining_target_qty > 0 ? 'bg-amber-50/50' : '' }}">
                <span class="text-[10px] font-bold {{ $batch->remaining_target_qty > 0 ? 'text-amber-700' : 'text-[#64748B]' }} uppercase tracking-wider block">Sisa Target</span>
                <span class="text-base sm:text-lg font-extrabold {{ $batch->remaining_target_qty > 0 ? 'text-amber-800' : 'text-emerald-700' }} mt-0.5 block font-mono">
                    {{ number_format($batch->remaining_target_qty, 0, ',', '.') }} <span class="text-[10px] font-semibold text-[#64748B]">pcs</span>
                </span>
            </div>

            {{-- 4. Jumlah QC --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-3 sm:p-3.5 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Jumlah QC</span>
                <span class="text-base sm:text-lg font-extrabold text-sky-800 mt-0.5 block font-mono">
                    {{ number_format($batch->total_inspected_qty, 0, ',', '.') }} <span class="text-[10px] font-semibold text-[#64748B]">pcs</span>
                </span>
            </div>

            {{-- 5. Barang Good --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-3 sm:p-3.5 shadow-card">
                <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Barang Good</span>
                <span class="text-base sm:text-lg font-extrabold text-emerald-700 mt-0.5 block font-mono">
                    {{ number_format($batch->total_good_qty, 0, ',', '.') }} <span class="text-[10px] font-semibold text-[#64748B]">pcs</span>
                </span>
            </div>

            {{-- 6. Barang Reject --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-3 sm:p-3.5 shadow-card">
                <span class="text-[10px] font-bold {{ $batch->total_reject_qty > 0 ? 'text-rose-700' : 'text-[#64748B]' }} uppercase tracking-wider block">Barang Reject</span>
                <span class="text-base sm:text-lg font-extrabold {{ $batch->total_reject_qty > 0 ? 'text-rose-700' : 'text-[#64748B]' }} mt-0.5 block font-mono">
                    {{ number_format($batch->total_reject_qty, 0, ',', '.') }} <span class="text-[10px] font-semibold text-[#64748B]">pcs</span>
                </span>
            </div>

            {{-- 7. Persentase Pencapaian --}}
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-3 sm:p-3.5 shadow-card">
                <span class="text-[10px] font-bold text-[#64748B] uppercase tracking-wider block">Pencapaian</span>
                <span class="text-base sm:text-lg font-extrabold {{ $batch->achievement_percentage >= 100 ? 'text-emerald-700' : ($batch->achievement_percentage >= 80 ? 'text-[#8E1B5C]' : 'text-amber-800') }} mt-0.5 block font-mono">
                    {{ $batch->achievement_percentage }}%
                </span>
            </div>
        </div>

        {{-- ── 2B. Status Selesai Parsial Card (Jika status SELESAI_PARSIAL) ───── --}}
        @if($batch->status === \App\Enums\BatchStatus::SELESAI_PARSIAL)
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300 rounded-xl p-4 sm:p-5 shadow-card space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-amber-200 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold">
                            !
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-amber-900 leading-snug">
                                Batch Ditutup Secara Parsial (SELESAI_PARSIAL)
                            </h3>
                            <p class="text-[11px] text-amber-800">
                                Batch dihentikan sebelum memenuhi target produksi. Sisa kuantitas pesanan tidak otomatis terpenuhi.
                            </p>
                        </div>
                    </div>
                    <span class="badge bg-amber-100 text-amber-900 border border-amber-300 text-xs px-3 py-1 font-bold">
                        Kekurangan: {{ number_format($batch->calculated_shortfall_qty, 0, ',', '.') }} pcs
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-white/70 p-3 rounded-lg border border-amber-200/80">
                    <div>
                        <span class="text-[10px] text-gray-500 block uppercase font-semibold">Target Awal</span>
                        <span class="font-bold text-[#172B4D] font-mono text-sm">{{ number_format($batch->target_qty, 0, ',', '.') }} pcs</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-500 block uppercase font-semibold">Aktual Produksi</span>
                        <span class="font-bold text-[#8E1B5C] font-mono text-sm">{{ number_format($batch->total_actual_qty, 0, ',', '.') }} pcs</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-500 block uppercase font-semibold">Hasil Good QC</span>
                        <span class="font-bold text-emerald-700 font-mono text-sm">{{ number_format($batch->total_good_qty, 0, ',', '.') }} pcs</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-500 block uppercase font-semibold">Hasil Reject QC</span>
                        <span class="font-bold text-rose-700 font-mono text-sm">{{ number_format($batch->total_reject_qty, 0, ',', '.') }} pcs</span>
                    </div>
                </div>

                <div class="text-xs bg-white/80 p-3 rounded-lg border border-amber-200/80 space-y-1.5">
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-gray-600 shrink-0">Alasan Kekurangan:</span>
                        <span class="text-gray-900 font-medium italic">"{{ $batch->close_reason ?: 'Tidak dicatat' }}"</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500 pt-1 border-t border-amber-100">
                        <span>Disetujui Oleh: <strong class="text-gray-700">{{ $batch->closedByUser?->name ?? 'Admin Sistem' }}</strong></span>
                        <span>Waktu Persetujuan: <strong class="text-gray-700">{{ $batch->closed_at ? $batch->closed_at->format('d M Y, H:i WIB') : '-' }}</strong></span>
                    </div>
                </div>

                {{-- Action options after partial close --}}
                <div class="pt-2 flex flex-wrap items-center gap-2.5">
                    @if($batch->orderItem && $batch->orderItem->order)
                        <a href="{{ route('admin.orders.batches.create', ['order' => $batch->orderItem->order_id, 'order_item_id' => $batch->order_item_id, 'target_qty' => $batch->calculated_shortfall_qty]) }}"
                           class="inline-flex items-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#68113F] transition shadow-xs cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <span>Buat Batch Baru untuk Sisa Target ({{ number_format($batch->calculated_shortfall_qty) }} pcs)</span>
                        </a>
                    @endif

                    <form method="POST" action="{{ route('admin.batches.resume', $batch) }}" onsubmit="return confirm('Buka kembali batch ini ke tahap Produksi untuk PIC saat ini?');">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-lg border border-amber-300 bg-white text-amber-900 text-xs font-semibold hover:bg-amber-100 transition shadow-xs cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                            <span>Buka Kembali &amp; Lanjutkan Batch Ini</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- ── 2C. Panel Tindakan Kekurangan Produksi (Shortfall Actions) ───────── --}}
        @if($batch->status !== \App\Enums\BatchStatus::SELESAI && $batch->status !== \App\Enums\BatchStatus::SELESAI_PARSIAL && $batch->status !== \App\Enums\BatchStatus::CANCELLED && $batch->total_actual_qty > 0 && $batch->remaining_target_qty > 0)
            <div class="bg-amber-50/80 border border-amber-300 rounded-xl p-4 sm:p-5 shadow-card space-y-3.5" x-data="{ showCloseModal: false, showResumeModal: false }">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-amber-200 pb-2.5">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <h3 class="text-xs font-bold text-amber-900 uppercase tracking-wider">
                                Kekurangan Target Produksi (Sisa: {{ number_format($batch->remaining_target_qty) }} pcs)
                            </h3>
                            <p class="text-[11px] text-amber-800">
                                Aktual produksi ({{ number_format($batch->total_actual_qty) }} pcs) belum mencapai target awal ({{ number_format($batch->target_qty) }} pcs). Silakan pilih salah satu opsi di bawah ini:
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Opsi 1: Lanjutkan Batch Ini (PIC Sama) --}}
                    <div class="bg-white p-3.5 rounded-lg border border-amber-200 space-y-2 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-xs text-[#172B4D]">
                                <span class="w-5 h-5 rounded-full bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center text-[10px]">1</span>
                                <span>Lanjutkan Batch Ini</span>
                            </div>
                            <p class="text-[11px] text-[#64748B] mt-1">
                                Tetap menggunakan batch ini dengan PIC yang sama ({{ $batch->productionEmployee?->name ?? 'Belum ada' }}) untuk memproduksi sisa target.
                            </p>
                        </div>
                        <button type="button"
                                @click="showResumeModal = true"
                                class="w-full inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#68113F] transition cursor-pointer">
                            <span>Lanjutkan Produksi</span>
                        </button>
                    </div>

                    {{-- Opsi 2: Alihkan Sisa ke Batch Baru --}}
                    <div class="bg-white p-3.5 rounded-lg border border-amber-200 space-y-2 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-xs text-[#172B4D]">
                                <span class="w-5 h-5 rounded-full bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center text-[10px]">2</span>
                                <span>Alihkan ke Batch Baru</span>
                            </div>
                            <p class="text-[11px] text-[#64748B] mt-1">
                                Buat batch baru untuk sisa {{ number_format($batch->remaining_target_qty) }} pcs dan tugaskan kepada karyawan lain.
                            </p>
                        </div>
                        @if($batch->orderItem && $batch->orderItem->order)
                            <a href="{{ route('admin.orders.batches.create', ['order' => $batch->orderItem->order_id, 'order_item_id' => $batch->order_item_id, 'target_qty' => $batch->remaining_target_qty]) }}"
                               class="w-full inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition cursor-pointer">
                                <span>Buat Batch Baru</span>
                            </a>
                        @endif
                    </div>

                    {{-- Opsi 3: Tutup Batch Parsial --}}
                    <div class="bg-white p-3.5 rounded-lg border border-amber-200 space-y-2 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-xs text-[#172B4D]">
                                <span class="w-5 h-5 rounded-full bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center text-[10px]">3</span>
                                <span>Penyelesaian Parsial</span>
                            </div>
                            <p class="text-[11px] text-[#64748B] mt-1">
                                Jika produksi tidak dilanjutkan, tutup batch ini dengan mencatat alasan dan persetujuan resmi.
                            </p>
                        </div>
                        <button type="button"
                                @click="showCloseModal = true"
                                class="w-full inline-flex items-center justify-center gap-1.5 h-8 px-3 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 transition cursor-pointer">
                            <span>Tutup Batch Parsial</span>
                        </button>
                    </div>
                </div>

                {{-- Modal Lanjutkan Batch --}}
                <div x-show="showResumeModal"
                     x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div class="bg-white rounded-xl max-w-md w-full p-5 space-y-3.5 shadow-xl" @click.away="showResumeModal = false">
                        <h4 class="text-sm font-bold text-[#172B4D]">Konfirmasi Melanjutkan Batch Ini</h4>
                        <p class="text-xs text-[#64748B]">
                            Batch <strong>{{ $batch->batch_no }}</strong> akan dikembalikan ke status <strong>PRODUKSI</strong> agar PIC ({{ $batch->productionEmployee?->name ?? 'Karyawan' }}) dapat melanjutkan produksi sisa target sebesar <strong>{{ number_format($batch->remaining_target_qty) }} pcs</strong>.
                        </p>
                        <form method="POST" action="{{ route('admin.batches.resume', $batch) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="resume_notes" class="block text-xs font-semibold text-[#172B4D]">Catatan Tambahan (Opsional)</label>
                                <textarea id="resume_notes" name="notes" rows="2" placeholder="Contoh: Lanjutkan jahit sisa 10 pcs..." class="w-full mt-1 p-2.5 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] focus:bg-white focus:outline-none focus:border-[#8E1B5C]"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                                <button type="button" @click="showResumeModal = false" class="h-8 px-3 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                                <button type="submit" class="h-8 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#68113F]">Lanjutkan Batch</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Modal Tutup Batch Parsial --}}
                <div x-show="showCloseModal"
                     x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div class="bg-white rounded-xl max-w-md w-full p-5 space-y-3.5 shadow-xl" @click.away="showCloseModal = false">
                        <h4 class="text-sm font-bold text-amber-900">Konfirmasi Penutupan Batch Parsial</h4>
                        <p class="text-xs text-[#64748B]">
                            Tindakan ini akan menetapkan status batch menjadi <strong>SELESAI_PARSIAL</strong> dengan kekurangan <strong>{{ number_format($batch->remaining_target_qty) }} pcs</strong>. Wajib mencantumkan alasan kekurangan produksi.
                        </p>
                        <form method="POST" action="{{ route('admin.batches.partial-close', $batch) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="partial_reason" class="block text-xs font-semibold text-[#172B4D]">Alasan Kekurangan Produksi <span class="text-rose-600">*</span></label>
                                <textarea id="partial_reason" name="reason" rows="3" required placeholder="Contoh: Kain utama habis di supplier / Permintaan customer dicukupkan..." class="w-full mt-1 p-2.5 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] focus:bg-white focus:outline-none focus:border-[#8E1B5C]"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-2 border-t border-[#FFD8D8]">
                                <button type="button" @click="showCloseModal = false" class="h-8 px-3 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                                <button type="submit" class="h-8 px-4 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700">Tutup Batch Parsial</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── 3. Detail Batch Card ───────────────────────────────────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card space-y-4">
            <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider border-b border-[#FFD8D8] pb-2">
                Rincian Batch
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80 space-y-1">
                    <span class="text-[#64748B] block text-[11px]">Produk</span>
                    <span class="font-bold text-[#172B4D] text-sm block">
                        {{ $batch->orderItem->variant->product->product_name ?? '-' }}
                    </span>
                </div>

                <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80 space-y-1">
                    <span class="text-[#64748B] block text-[11px]">Varian &amp; SKU</span>
                    <span class="font-bold text-[#172B4D] text-sm block">
                        {{ $batch->orderItem->variant->variant_name ?? '-' }}
                    </span>
                    <span class="font-mono text-[11px] text-[#64748B] block">
                        SKU: {{ $batch->orderItem->variant->sku ?? '-' }}
                    </span>
                </div>

                <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80 space-y-1">
                    <span class="text-[#64748B] block text-[11px]">Rencana Mulai</span>
                    <span class="font-semibold text-[#172B4D]">
                        {{ $batch->planned_start_date ? $batch->planned_start_date->format('d F Y') : 'Belum ditentukan' }}
                    </span>
                </div>

                <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80 space-y-1">
                    <span class="text-[#64748B] block text-[11px]">Rencana Selesai</span>
                    <span class="font-semibold text-[#172B4D]">
                        {{ $batch->planned_end_date ? $batch->planned_end_date->format('d F Y') : 'Belum ditentukan' }}
                    </span>
                </div>

                <div class="md:col-span-2 space-y-1">
                    <span class="text-[#64748B] block text-[11px] font-medium">Catatan Khusus Batch</span>
                    <div class="p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] text-[#172B4D] text-xs leading-relaxed whitespace-pre-line">
                        {{ $batch->notes ?: 'Tidak ada catatan khusus.' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 3B. Penugasan PIC Karyawan Produksi & Pengalihan PIC ─────────── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#FFD8D8] pb-3">
                <div>
                    <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                        Penanggung Jawab Produksi (PIC)
                    </h2>
                    <p class="text-[11px] text-[#64748B] mt-0.5">
                        Karyawan yang berwenang mengakses batch ini pada dashboard kerja Produksi.
                    </p>
                </div>
                <div>
                    @if($batch->productionEmployee)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-[#8E1B5C]/10 text-[#8E1B5C]">
                            PIC Aktif: {{ $batch->productionEmployee->name }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            Belum Ada PIC (Draft)
                        </span>
                    @endif
                </div>
            </div>

            {{-- Form Tugaskan / Ganti PIC --}}
            <form method="POST" action="{{ route('admin.batches.reassign-pic', $batch) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2 space-y-1">
                        <label for="production_employee_id" class="block text-xs font-semibold text-[#172B4D]">
                            Pilih / Ganti PIC Produksi
                        </label>
                        <select id="production_employee_id"
                                name="production_employee_id"
                                class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                            <option value="">-- Kosongkan PIC (Cabut Penugasan) --</option>
                            @foreach($employeeWorkloads ?? [] as $w)
                                <option value="{{ $w['id'] }}" @selected($batch->production_employee_id == $w['id'])>
                                    [{{ $w['workload_status'] }}] {{ $w['name'] }} — {{ $w['active_batches_count'] }} Batch Aktif (Sisa: {{ number_format($w['active_remaining_qty']) }} pcs, DL: {{ $w['closest_deadline_formatted'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="reassign_notes" class="block text-xs font-semibold text-[#172B4D]">
                            Alasan / Catatan (Opsional)
                        </label>
                        <input type="text"
                               id="reassign_notes"
                               name="notes"
                               placeholder="Contoh: Pengalihan beban kerja..."
                               class="w-full h-10 px-3 text-xs border border-[#FFD8D8] rounded-lg bg-[#FFFCFB] text-[#172B4D] focus:bg-white focus:outline-none focus:border-[#8E1B5C] focus:ring-1 focus:ring-[#8E1B5C]">
                    </div>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#68113F] transition cursor-pointer shadow-xs">
                        <span>Simpan Penugasan PIC</span>
                    </button>
                </div>
            </form>

            {{-- Audit Trail Riwayat Penugasan PIC --}}
            @if($batch->picHistories && $batch->picHistories->isNotEmpty())
                <div class="pt-3 border-t border-[#FFD8D8] space-y-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#64748B] block">
                        Riwayat Penugasan &amp; Pengalihan PIC:
                    </span>
                    <div class="divide-y divide-[#FFD8D8]/60 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] overflow-hidden">
                        @foreach($batch->picHistories as $hist)
                            <div class="p-2.5 sm:p-3 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                                <div>
                                    <div class="font-medium text-[#172B4D] flex items-center gap-2">
                                        <span class="text-[#64748B]">{{ $hist->fromEmployee ? $hist->fromEmployee->name : '(Awal)' }}</span>
                                        <span class="text-[#8E1B5C] font-bold">&rarr;</span>
                                        <span class="font-bold text-[#8E1B5C]">{{ $hist->toEmployee ? $hist->toEmployee->name : '(Dicabut)' }}</span>
                                    </div>
                                    @if($hist->notes)
                                        <p class="text-[11px] text-[#64748B] italic mt-0.5">{{ $hist->notes }}</p>
                                    @endif
                                </div>
                                <div class="text-[11px] text-[#64748B] sm:text-right shrink-0">
                                    <span>{{ $hist->assigned_at ? $hist->assigned_at->format('d M Y, H:i') : '-' }}</span>
                                    <span class="text-[#FFD8D8]">&middot;</span>
                                    <span>Oleh: {{ $hist->assigner?->name ?? 'Admin' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ── 4. Contextual Production Stage & Readiness Info ────────────── --}}
        @php
            $hasSnapshot = $batch->batchMaterials->isNotEmpty();
            $isFullyAllocated = $hasSnapshot && $batch->batchMaterials->every(function ($bm) {
                return bccomp((string) $bm->allocated_qty, (string) $bm->required_qty, 4) >= 0;
            });
            $planningState = $hasSnapshot ? 'TERHITUNG' : 'BELUM TERSEDIA';
            $allocationState = $isFullyAllocated ? 'TERALOKASI' : 'BELUM TERALOKASI';
        @endphp

        <div class="bg-white border border-[#FFD8D8] rounded-xl p-4 sm:p-5 shadow-card text-xs text-[#64748B] space-y-2.5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#FFD8D8] pb-2.5">
                <div class="flex items-center gap-2 font-bold text-[#172B4D]">
                    <svg class="w-4 h-4 text-[#8E1B5C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Tahapan &amp; Kesiapan Bahan Produksi</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] text-[#64748B]">Perencanaan Bahan:</span>
                    <span class="badge {{ $hasSnapshot ? 'badge-success' : 'badge-warning' }} text-[10px]">
                        {{ $planningState }}
                    </span>
                    <span class="text-[11px] text-[#64748B] ml-1">Kesiapan Bahan:</span>
                    <span class="badge {{ $isFullyAllocated ? 'badge-success' : 'badge-secondary' }} text-[10px]">
                        {{ $allocationState }}
                    </span>
                </div>
            </div>

            @if($batch->status === \App\Enums\BatchStatus::BAHAN)
                @if(! $hasSnapshot)
                    <p class="leading-relaxed text-amber-800 font-medium">
                        Batch lama ini belum memiliki snapshot kebutuhan bahan.
                    </p>
                @elseif($isFullyAllocated)
                    <p class="leading-relaxed text-emerald-800 font-medium">
                        Kebutuhan bahan batch telah dialokasikan dan batch siap diproses oleh Produksi.
                    </p>
                @else
                    <p class="leading-relaxed">
                        Batch berada pada tahap <strong>BAHAN</strong>. Kebutuhan material telah dihitung otomatis berdasarkan BOM varian dan menunggu pemenuhan/alokasi gudang.
                    </p>
                @endif
            @else
                <p class="leading-relaxed">
                    Batch berada pada tahap <strong>{{ $batch->status->label() }}</strong>.
                </p>
            @endif
        </div>

        {{-- ── 4B. Riwayat Hasil Produksi & QC Multi-Run ──────────────────── --}}
        @if($batch->productionResults->isNotEmpty())
            <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card space-y-3.5">
                <div class="flex items-center justify-between border-b border-[#FFD8D8] pb-2.5">
                    <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                        Riwayat Hasil Produksi &amp; Pemeriksaan QC
                    </h2>
                    <span class="text-[11px] text-[#64748B]">
                        {{ $batch->productionResults->count() }} Sesi Produksi Dicatat
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-[#FFFCFB] border-b border-[#FFD8D8]">
                                <th class="px-3 py-2 text-[10px] font-bold text-[#64748B] uppercase">Sesi</th>
                                <th class="px-3 py-2 text-[10px] font-bold text-[#64748B] uppercase">Tgl Produksi</th>
                                <th class="px-3 py-2 text-[10px] font-bold text-[#64748B] uppercase text-right">Aktual</th>
                                <th class="px-3 py-2 text-[10px] font-bold text-sky-800 uppercase text-right">Diperiksa QC</th>
                                <th class="px-3 py-2 text-[10px] font-bold text-emerald-700 uppercase text-right">Good</th>
                                <th class="px-3 py-2 text-[10px] font-bold text-rose-700 uppercase text-right">Reject</th>
                                <th class="px-3 py-2 text-[10px] font-bold text-[#64748B] uppercase">Pencatat / QC</th>
                                <th class="px-3 py-2 text-[10px] font-bold text-[#64748B] uppercase">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FFD8D8]/60">
                            @foreach($batch->productionResults as $idx => $pr)
                                @php
                                    $qc = $pr->qcResult;
                                @endphp
                                <tr class="hover:bg-[#FFD8D8]/10 transition">
                                    <td class="px-3 py-2.5 font-bold font-mono text-[#8E1B5C]">
                                        #{{ $idx + 1 }}
                                    </td>
                                    <td class="px-3 py-2.5 whitespace-nowrap text-[#172B4D]">
                                        {{ $pr->result_date ? \Carbon\Carbon::parse($pr->result_date)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold text-[#172B4D]">
                                        {{ number_format($pr->actual_qty, 0, ',', '.') }} pcs
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-semibold text-sky-800">
                                        {{ $qc ? number_format($qc->inspected_qty, 0, ',', '.') . ' pcs' : 'Menunggu QC' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold text-emerald-700">
                                        {{ $qc ? number_format($qc->good_qty, 0, ',', '.') . ' pcs' : '-' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold {{ $qc && $qc->reject_qty > 0 ? 'text-rose-700' : 'text-gray-400' }}">
                                        {{ $qc ? number_format($qc->reject_qty, 0, ',', '.') . ' pcs' : '-' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-[11px] text-[#64748B]">
                                        <div>Prod: <strong class="text-[#172B4D]">{{ $pr->recordedByUser?->name ?? 'Karyawan' }}</strong></div>
                                        @if($qc)
                                            <div>QC: <strong class="text-[#172B4D]">{{ $qc->inspector?->name ?? 'Karyawan' }}</strong></div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-[11px] text-[#64748B] max-w-[200px] truncate" title="{{ $pr->notes }} {{ $qc?->notes ? '| ' . $qc->notes : '' }}">
                                        {{ $pr->notes ?: ($qc?->notes ?: '-') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ── 5. Automated HPP Configuration & Tariff Snapshot (Admin Read-Only) ── --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-6 shadow-card space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#FFD8D8] pb-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">
                            Perhitungan HPP Otomatis
                        </h2>
                        <span class="badge bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8] text-[10px] font-bold">
                            SNAPSHOT TARIF MASTER
                        </span>
                    </div>
                    <p class="text-[11px] text-[#64748B]">
                        Perhitungan HPP dilakukan otomatis oleh sistem berdasarkan pemakaian bahan riil BOM, hasil produksi, hasil QC, dan snapshot tarif produksi.
                    </p>
                </div>
            </div>

            {{-- Compact Cost Rate Snapshot Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                {{-- Printing Rate --}}
                <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80">
                    <span class="text-[10px] text-[#64748B] font-semibold block uppercase">Jasa Printing / pcs</span>
                    <span class="font-bold text-[#172B4D] font-mono text-sm mt-1 block">
                        @if($batch->effective_requires_printing)
                            Rp {{ number_format((float) ($batch->printing_cost_per_unit_snapshot ?? 0), 0, ',', '.') }}
                        @else
                            <span class="text-[#64748B] font-normal text-xs">Rp 0 (Tanpa Print)</span>
                        @endif
                    </span>
                    <span class="text-[9px] text-[#64748B] block mt-0.5">Dikalikan kuantitas aktual</span>
                </div>

                {{-- Direct Labor Rate --}}
                <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80">
                    <span class="text-[10px] text-[#64748B] font-semibold block uppercase">Tenaga Kerja / pcs</span>
                    <span class="font-bold text-[#172B4D] font-mono text-sm mt-1 block">
                        Rp {{ number_format((float) ($batch->direct_labor_cost_per_unit_snapshot ?? 0), 0, ',', '.') }}
                    </span>
                    <span class="text-[9px] text-[#64748B] block mt-0.5">Upah langsung per unit aktual</span>
                </div>

                {{-- Packaging Process Rate --}}
                <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80">
                    <span class="text-[10px] text-[#64748B] font-semibold block uppercase">Pengemasan / Good pcs</span>
                    <span class="font-bold text-[#172B4D] font-mono text-sm mt-1 block">
                        Rp {{ number_format((float) ($batch->packaging_process_cost_per_good_unit_snapshot ?? 0), 0, ',', '.') }}
                    </span>
                    <span class="text-[9px] text-[#64748B] block mt-0.5">Hanya untuk produk lolos QC</span>
                </div>

                {{-- Other Direct Rate --}}
                <div class="bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]/80">
                    <span class="text-[10px] text-[#64748B] font-semibold block uppercase">Biaya Langsung Lain / pcs</span>
                    <span class="font-bold text-[#172B4D] font-mono text-sm mt-1 block">
                        Rp {{ number_format((float) ($batch->other_direct_cost_per_unit_snapshot ?? 0), 0, ',', '.') }}
                    </span>
                    <span class="text-[9px] text-[#64748B] block mt-0.5">Biaya standar langsung per unit</span>
                </div>
            </div>

            {{-- Helper notice --}}
            <div class="p-3 bg-[#FFFCFB] rounded-lg border border-[#FFD8D8] text-xs text-[#64748B] flex items-start gap-2.5">
                <svg class="w-4 h-4 text-[#8E1B5C] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="leading-relaxed">
                    Tarif biaya produksi di atas merupakan snapshot permanen yang disalin saat batch dibuat. Perubahan tarif master pada varian produk tidak akan mengubah perhitungan HPP batch ini.
                </p>
            </div>
        </div>

    </div>
</x-app-layout>
