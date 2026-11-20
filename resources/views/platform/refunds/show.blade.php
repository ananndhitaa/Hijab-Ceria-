@extends('platform.layouts.app')

@section('content')
<div class="w-full max-w-none mx-0 space-y-6">

    <x-feature-header
        :title="'Rincian Pengajuan Refund #' . $refund->id"
        :subtitle="'Toko: ' . ($refund->outlet?->name ?? '-')"
        icon="refund"
        badge="AUDIT REFUND"
    >
        <x-slot:actions>
            <a href="{{ route('platform.refunds.index') }}" class="btn btn-outline text-xs sm:text-sm font-semibold">
                &larr; Kembali ke Daftar Refund
            </a>
        </x-slot:actions>
    </x-feature-header>

    <div class="card p-6 sm:p-8 space-y-6">
        <h1 class="text-xl font-bold text-text-main border-b border-border-c pb-4">Rincian Pengajuan Refund Platform #{{ $refund->id }}</h1>

        <div class="space-y-3 text-xs">
            <div class="flex justify-between">
                <span class="text-text-sec">Toko / Outlet:</span>
                <span class="font-bold text-text-main">{{ $refund->outlet?->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-text-sec">Tipe Refund:</span>
                <span class="badge badge-secondary">{{ $refund->refund_type }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-y border-border-c">
                <span class="text-sm font-bold text-text-main">Nominal Pengembalian:</span>
                <span class="text-2xl font-extrabold text-rose-600">Rp {{ number_format($refund->amount, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-text-sec">Alasan Pengajuan:</span>
                <p class="text-text-main mt-0.5 p-3 bg-slate-50 rounded-lg border border-slate-100">{{ $refund->reason }}</p>
            </div>
            <div class="pt-2">
                <span class="text-text-sec font-bold">Rekening Bank Tujuan:</span>
                <div class="p-4 bg-soft-pink/40 rounded-xl border border-border-c mt-1 space-y-1">
                    <p class="text-xs font-semibold text-text-sec">Bank: <span class="text-text-main font-bold">{{ $refund->destination_bank_name }}</span></p>
                    <p class="text-xs font-semibold text-text-sec">No. Rekening: <span class="text-text-main font-mono font-bold">{{ $refund->destination_account_number }}</span></p>
                    <p class="text-xs font-semibold text-text-sec">Atas Nama: <span class="text-text-main font-bold">{{ $refund->destination_account_name }}</span></p>
                </div>
            </div>
            @if($refund->approved_at)
            <div class="flex justify-between text-blue-700 bg-blue-50 p-2 rounded-lg font-semibold">
                <span>Disetujui Oleh:</span>
                <span>{{ $refund->approver?->name }} ({{ $refund->approved_at->format('d M Y H:i') }})</span>
            </div>
            @endif
            @if($refund->completed_at)
            <div class="flex justify-between text-emerald-700 bg-emerald-50 p-2 rounded-lg font-semibold">
                <span>Diselesaikan Oleh:</span>
                <span>{{ $refund->completer?->name }} ({{ $refund->completed_at->format('d M Y H:i') }})</span>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        @if($refund->status === 'REQUESTED')
        <form action="{{ route('platform.refunds.approve', $refund->id) }}" method="POST" class="pt-4 border-t border-border-c">
            @csrf
            <button type="submit" class="w-full btn-primary justify-center py-2.5 text-xs font-bold" onclick="return confirm('Setujui pengajuan refund ini untuk diproses ke transfer bank?')">
                ✓ Setujui Pengajuan Refund
            </button>
        </form>
        @endif

        @if(in_array($refund->status, ['REQUESTED', 'APPROVED']))
        <form action="{{ route('platform.refunds.complete', $refund->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4 pt-4 border-t border-border-c">
            @csrf
            <h3 class="text-xs font-bold uppercase tracking-wider text-text-sec">Selesaikan Pencairan & Unggah Bukti Transfer Bank</h3>
            <div>
                <label class="form-label text-xs">Bukti Transfer Pencairan Refund (JPG, PNG, atau PDF max 3MB) <span class="text-red-500">*</span></label>
                <input type="file" name="transfer_proof" required accept=".jpg,.jpeg,.png,.pdf" class="form-input text-xs">
            </div>
            <button type="submit" class="w-full btn-primary justify-center py-2.5 text-xs font-bold" onclick="return confirm('Konfirmasi bahwa dana refund telah berhasil ditransfer ke rekening pemilik toko?')">
                Selesaikan Refund & Rekam Bukti Mutasi
            </button>
        </form>
        @endif

        @if($refund->status === 'COMPLETED' && $refund->transfer_proof_path)
        <div class="pt-4 border-t border-border-c space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-text-sec">Bukti Transfer Pencairan</h3>
            <div class="p-2 border border-border-c rounded-xl bg-slate-50 flex items-center justify-center">
                <img src="{{ asset('storage/' . $refund->transfer_proof_path) }}" alt="Bukti Transfer Refund" class="max-h-64 object-contain rounded">
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
