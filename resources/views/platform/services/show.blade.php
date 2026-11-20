@extends('platform.layouts.app')

@section('content')
<div class="w-full max-w-none mx-0 space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('platform.services.index') }}" class="text-xs font-bold text-primary hover:underline">
            ← Kembali ke Antrean Layanan
        </a>
        <span class="badge badge-primary font-bold">{{ $serviceRequest->status }}</span>
    </div>

    <div class="card p-6 sm:p-8 space-y-6">
        <h1 class="text-xl font-bold text-text-main border-b border-border-c pb-4">
            Rincian Permintaan Layanan #{{ $serviceRequest->id }}
        </h1>

        <div class="space-y-3 text-xs">
            <div class="flex justify-between">
                <span class="text-text-sec">Toko / Outlet Pemohon:</span>
                <span class="font-bold text-text-main">🏬 {{ $serviceRequest->outlet?->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-sec">Diajukan Oleh:</span>
                <span class="text-text-main">{{ $serviceRequest->creator?->name }} ({{ $serviceRequest->creator?->email }})</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-sec">Tipe Layanan:</span>
                <span class="badge badge-secondary">{{ $serviceRequest->service_type }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-sec">Tanggal yang Diinginkan:</span>
                <span class="text-text-main">{{ $serviceRequest->preferred_date ? $serviceRequest->preferred_date->format('d M Y') : 'Fleksibel' }}</span>
            </div>
            <div>
                <span class="text-text-sec font-bold">Rincian Kebutuhan & Ruang Lingkup Toko:</span>
                <p class="text-text-main mt-1 p-3 bg-slate-50 rounded-xl border border-slate-100 whitespace-pre-line leading-relaxed">
                    {{ $serviceRequest->scope_details }}
                </p>
            </div>
        </div>

        {{-- Form to Update Status and Record Custom Quotation --}}
        <form action="{{ route('platform.services.update', $serviceRequest->id) }}" method="POST" class="space-y-4 pt-4 border-t border-border-c">
            @csrf
            @method('PUT')
            <h2 class="text-sm font-bold text-text-main">Pembaruan Status & Penawaran Ruang Lingkup</h2>

            <div>
                <label class="form-label text-xs">Status Permintaan</label>
                <select name="status" class="form-input text-xs">
                    <option value="SUBMITTED" {{ $serviceRequest->status === 'SUBMITTED' ? 'selected' : '' }}>SUBMITTED (Baru Masuk)</option>
                    <option value="IN_REVIEW" {{ $serviceRequest->status === 'IN_REVIEW' ? 'selected' : '' }}>IN_REVIEW (Sedang Ditinjau)</option>
                    <option value="SCOPED" {{ $serviceRequest->status === 'SCOPED' ? 'selected' : '' }}>SCOPED (Penawaran Ditetapkan)</option>
                    <option value="SCHEDULED" {{ $serviceRequest->status === 'SCHEDULED' ? 'selected' : '' }}>SCHEDULED (Jadwal Terkonfirmasi)</option>
                    <option value="IN_PROGRESS" {{ $serviceRequest->status === 'IN_PROGRESS' ? 'selected' : '' }}>IN_PROGRESS (Pelaksanaan Berjalan)</option>
                    <option value="COMPLETED" {{ $serviceRequest->status === 'COMPLETED' ? 'selected' : '' }}>COMPLETED (Selesai)</option>
                    <option value="CANCELLED" {{ $serviceRequest->status === 'CANCELLED' ? 'selected' : '' }}>CANCELLED (Dibatalkan)</option>
                </select>
            </div>

            <div>
                <label class="form-label text-xs">Nominal Estimasi Penawaran Kustom (Rp)</label>
                <input type="number" step="50000" name="custom_quote_amount" value="{{ old('custom_quote_amount', $serviceRequest->custom_quote_amount) }}" placeholder="Contoh: 1500000" class="form-input text-xs">
                <p class="text-[11px] text-text-sec mt-1">Kosongkan jika masih dalam tahap peninjauan awal atau tidak ada biaya tambahan.</p>
            </div>

            <div>
                <label class="form-label text-xs">Catatan & Jadwal Super Admin untuk Toko</label>
                <textarea name="super_admin_notes" rows="3" placeholder="Informasi tanggal pelaksanaan, nama instruktur/teknisi, atau rincian modul..." class="form-input text-xs">{{ old('super_admin_notes', $serviceRequest->super_admin_notes) }}</textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="submit" class="btn-primary text-xs">
                    Simpan Perubahan Status & Penawaran
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
