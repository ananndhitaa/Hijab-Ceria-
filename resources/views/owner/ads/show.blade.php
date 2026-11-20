@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <x-feature-header
        :title="$campaign->title"
        :subtitle="$campaign->tagline ?? 'Rincian materi promosi dan status kampanye iklan.'">
        <x-slot:badge>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-[#F7E6EE] text-[#8E1B5C] border border-[#FFD8D8]">
                EXECUTIVE MONITORING
            </span>
        </x-slot:badge>
        <x-slot:iconSlot>
            <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px] text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
        </x-slot:iconSlot>
        <x-slot:actions>
            <a href="{{ route('owner.ads.index') }}" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#F7E6EE] hover:text-[#8E1B5C] transition shadow-xs">
                &larr; Kembali ke Daftar Iklan
            </a>
        </x-slot:actions>
    </x-feature-header>

    {{-- Review Notes Alert (if NEEDS_REVISION or REJECTED) --}}
    @if($campaign->status === 'NEEDS_REVISION' && $campaign->review_notes)
    <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 space-y-1">
        <p class="font-bold">⚠️ Perlu Revisi Materi dari Super Admin:</p>
        <p>{{ $campaign->review_notes }}</p>
        <p class="text-[11px] text-red-600 mt-1">Silakan ajukan revisi materi iklan di bawah.</p>
    </div>
    @endif

    {{-- Main Ad Overview Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left 2 Cols: Details & Schedule Booking / Management --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="card p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="badge badge-secondary text-[11px] font-bold">{{ $campaign->ad_type }}</span>
                        <h1 class="text-xl font-bold text-text-main mt-1">{{ $campaign->title }}</h1>
                        <p class="text-xs text-text-sec mt-0.5">{{ $campaign->tagline }}</p>
                    </div>
                </div>

                {{-- Banner Preview --}}
                <div class="rounded-xl overflow-hidden border border-border-c bg-soft-pink/30 aspect-video max-h-64 flex items-center justify-center">
                    @if($campaign->banner_path)
                    <img src="{{ asset('storage/' . $campaign->banner_path) }}" alt="{{ $campaign->title }}" class="w-full h-full object-cover">
                    @else
                    <span class="text-sm text-text-sec font-semibold">Tidak ada gambar</span>
                    @endif
                </div>

                {{-- If APPROVED: Schedule Booking Form --}}
                @if(in_array($campaign->status, ['APPROVED', 'DRAFT']))
                <div class="p-5 bg-soft-pink/30 rounded-2xl border border-primary/20 space-y-4 mt-4">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">📅</span>
                        <div>
                            <h3 class="text-sm font-bold text-text-main">Pesan Jadwal & Tentukan Anggaran</h3>
                            <p class="text-[11px] text-text-sec">Pilih tanggal mulai, durasi (hari), dan total anggaran. Invoice reservasi 2 jam akan diterbitkan.</p>
                        </div>
                    </div>

                    <form action="{{ route('owner.ads.book', $campaign->id) }}" method="POST" class="space-y-4" x-data="{ budget: 100000, days: 3 }">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Tanggal & Jam Mulai Tayang</label>
                                <input type="datetime-local" name="starts_at" required min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Durasi Tayang (Hari Penuh - 24 Jam/Hari)</label>
                                <input type="number" name="duration_days" min="1" x-model="days" required class="form-input">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Total Anggaran Iklan (Rp)</label>
                            <input type="number" step="5000" name="total_budget" min="10000" x-model="budget" required class="form-input">
                            <p class="text-[11px] text-primary font-semibold mt-1">
                                Estimasi Nilai Bid per Hari: Rp <span x-text="Math.round(budget / Math.max(1, days)).toLocaleString('id-ID')"></span> / hari.
                                (Peringkat penayangan dihitung berdasarkan Bid per Hari).
                            </p>
                        </div>

                        <button type="submit" class="w-full btn-primary justify-center py-2.5 text-xs font-bold" onclick="return confirm('Reservasi jadwal ini dan buat invoice pembayaran 2 jam?')">
                            Reservasi Jadwal & Buat Invoice
                        </button>
                    </form>
                </div>
                @endif

                {{-- If PAYMENT_PENDING: Link to Invoice --}}
                @if($campaign->status === 'PAYMENT_PENDING' && $campaign->invoice)
                <div class="p-5 bg-amber-50 rounded-2xl border border-amber-200 space-y-2">
                    <p class="text-sm font-bold text-amber-900">Jadwal Sedang Direservasi (Menunggu Pembayaran)</p>
                    <p class="text-xs text-amber-800">
                        Invoice {{ $campaign->invoice->invoice_no }} senilai Rp {{ number_format($campaign->total_budget, 0, ',', '.') }} telah diterbitkan.
                        Batas waktu pembayaran: <strong>{{ $campaign->invoice->expires_at->format('d M Y H:i') }} WIB</strong> (Maksimal 2 jam dari pemesanan).
                    </p>
                    <a href="{{ route('owner.subscriptions.invoice', $campaign->invoice->id) }}" class="btn-primary text-xs py-2 inline-block mt-2">
                        Bayar Invoice Sekarang →
                    </a>
                </div>
                @endif

                {{-- Active / Scheduled Information --}}
                @if(in_array($campaign->status, ['SCHEDULED', 'ACTIVE', 'COMPLETED']))
                <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-200 space-y-2 text-xs">
                    <p class="font-bold text-emerald-900 text-sm">Informasi Penayangan Iklan:</p>
                    <div class="grid grid-cols-2 gap-2 text-emerald-800">
                        <div>Waktu Mulai: {{ $campaign->starts_at?->format('d M Y H:i') }} WIB</div>
                        <div>Waktu Selesai: {{ $campaign->ends_at?->format('d M Y H:i') }} WIB</div>
                        <div>Total Anggaran: Rp {{ number_format($campaign->total_budget, 0, ',', '.') }}</div>
                        <div>Bid Efektif: Rp {{ number_format($campaign->effective_bid_per_day, 0, ',', '.') }} / hari</div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Competitor Preview (Section O) --}}
            @if(!empty($competitorPreview))
            <div class="card p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-text-main">Preview Kompetisi Iklan (Top 3)</h3>
                        <p class="text-[11px] text-text-sec">Informasi bid kompetitor pada rentang jadwal yang sama (identitas toko dirahasiakan).</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-border-c text-text-sec font-semibold text-left">
                                <th class="py-2">Peringkat</th>
                                <th class="py-2">Effective Bid / Hari</th>
                                <th class="py-2">Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($competitorPreview as $comp)
                            <tr class="border-b border-border-c/50">
                                <td class="py-2 font-bold text-primary">#{{ $comp['rank'] }}</td>
                                <td class="py-2 font-mono font-bold">Rp {{ number_format($comp['effective_bid_per_day'], 0, ',', '.') }}</td>
                                <td class="py-2 text-text-sec">{{ $comp['duration_days'] }} Hari</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Revisions History --}}
            @if($campaign->revisions->isNotEmpty())
            <div class="card p-5 space-y-3">
                <h3 class="text-sm font-bold text-text-main">Riwayat Pengajuan Revisi Materi</h3>
                <div class="space-y-2">
                    @foreach($campaign->revisions as $rev)
                    <div class="p-3 rounded-lg border border-border-c text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-text-main">{{ $rev->title }}</span>
                            <span class="badge {{ $rev->status === 'APPROVED' ? 'badge-success' : ($rev->status === 'REJECTED' ? 'badge-danger' : 'badge-warning') }}">
                                {{ $rev->status }}
                            </span>
                        </div>
                        <p class="text-text-sec text-[11px]">{{ $rev->tagline }} (Tipe: {{ $rev->revision_type }})</p>
                        @if($rev->review_notes)
                        <p class="text-red-600 text-[11px]">Catatan: {{ $rev->review_notes }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Right Col: Actions (Reschedule, Revise, Cancel) --}}
        <div class="space-y-6">

            {{-- Reschedule Box (Before Airing) --}}
            @if(!$campaign->hasAired() && in_array($campaign->status, ['SCHEDULED', 'PAYMENT_PENDING']))
            <div class="card p-5 space-y-3">
                <h3 class="text-sm font-bold text-text-main">Ubah Jadwal Tayang</h3>
                <p class="text-xs text-text-sec">
                    Sisa kuota perubahan jadwal: <strong>{{ 2 - $campaign->schedule_change_count }} kali</strong> (Maksimal 2 kali sebelum iklan tayang).
                </p>
                @if($campaign->schedule_change_count < 2)
                <form action="{{ route('owner.ads.reschedule', $campaign->id) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="form-label text-xs">Jadwal Baru</label>
                        <input type="datetime-local" name="new_starts_at" required min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" class="form-input text-xs">
                    </div>
                    <button type="submit" class="w-full btn-outline text-xs justify-center py-2" onclick="return confirm('Ubah jadwal kampanye iklan ini?')">
                        Simpan Perubahan Jadwal
                    </button>
                </form>
                @else
                <p class="text-xs text-red-600">Batas maksimal perubahan jadwal telah habis.</p>
                @endif
            </div>
            @endif

            {{-- Creative Revision Box --}}
            @if(in_array($campaign->status, ['SCHEDULED', 'ACTIVE', 'NEEDS_REVISION']))
            <div class="card p-5 space-y-3">
                <h3 class="text-sm font-bold text-text-main">Ajukan Revisi Materi</h3>
                <p class="text-xs text-text-sec">
                    Sisa kuota revisi materi kreatif yang disetujui: <strong>{{ 2 - $campaign->creative_revision_count }} kali</strong>.
                </p>
                @if($campaign->creative_revision_count < 2 || $campaign->status === 'NEEDS_REVISION')
                <form action="{{ route('owner.ads.revise', $campaign->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="form-label text-xs">Tipe Revisi</label>
                        <select name="revision_type" class="form-input text-xs">
                            <option value="CREATIVE">Kreatif (Mengonsumsi kuota)</option>
                            <option value="FACTUAL">Koreksi Faktual (Harga/Spesifikasi)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label text-xs">Judul Baru</label>
                        <input type="text" name="title" required value="{{ $campaign->title }}" class="form-input text-xs">
                    </div>
                    <div>
                        <label class="form-label text-xs">Tagline Baru</label>
                        <input type="text" name="tagline" value="{{ $campaign->tagline }}" class="form-input text-xs">
                    </div>
                    <div>
                        <label class="form-label text-xs">Banner Baru (Opsional)</label>
                        <input type="file" name="banner_file" accept="image/*" class="form-input text-xs">
                    </div>
                    <button type="submit" class="w-full btn-outline text-xs justify-center py-2">
                        Kirim Revisi Materi
                    </button>
                </form>
                @endif
            </div>
            @endif

            {{-- Cancellation & Refund Box (Section U) --}}
            @if(!in_array($campaign->status, ['CANCELLED', 'COMPLETED']))
            <div class="card p-5 border border-red-200 bg-red-50/20 space-y-3">
                <h3 class="text-sm font-bold text-red-900">Pembatalan & Pengajuan Refund</h3>
                <p class="text-[11px] text-text-sec leading-relaxed">
                    Sesuai kebijakan Section U:
                    <br>• Pembatalan <strong>SEBELUM TAYANG</strong>: Refund 100%.
                    <br>• Pembatalan <strong>SETELAH TAYANG</strong>: Refund 0%.
                </p>

                <div x-data="{ open: false }">
                    <button type="button" @click="open = true" class="w-full btn-danger text-xs justify-center py-2">
                        Batalkan Kampanye Iklan
                    </button>

                    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-cloak>
                        <div @click.away="open = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left space-y-4 shadow-xl">
                            <h3 class="text-base font-bold text-red-700">Konfirmasi Pembatalan Iklan</h3>
                            <form action="{{ route('owner.ads.cancel', $campaign->id) }}" method="POST" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="form-label text-xs">Alasan Pembatalan</label>
                                    <textarea name="reason" rows="2" required placeholder="Tuliskan alasan pembatalan..." class="form-input text-xs"></textarea>
                                </div>
                                <div class="space-y-2 pt-2 border-t border-border-c">
                                    <p class="text-xs font-bold text-text-main">Data Rekening Tujuan Refund (Jika Berhak)</p>
                                    <div>
                                        <label class="form-label text-xs">Nama Bank</label>
                                        <input type="text" name="bank_name" required placeholder="Contoh: BCA, Mandiri" class="form-input text-xs">
                                    </div>
                                    <div>
                                        <label class="form-label text-xs">Nomor Rekening</label>
                                        <input type="text" name="account_number" required placeholder="Nomor rekening" class="form-input text-xs">
                                    </div>
                                    <div>
                                        <label class="form-label text-xs">Atas Nama Pemilik Rekening</label>
                                        <input type="text" name="account_name" required placeholder="Nama pemilik rekening" class="form-input text-xs">
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 pt-3">
                                    <button type="button" @click="open = false" class="btn-outline text-xs">Tutup</button>
                                    <button type="submit" class="btn-danger text-xs">Ya, Batalkan Sekarang</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>

    </div>

</div>
@endsection
