@extends('platform.layouts.app')

@section('content')
<div class="w-full max-w-none mx-0 space-y-6">

    <x-feature-header
        title="Detail Materi Iklan"
        :subtitle="$campaign->title"
        icon="ads"
        badge="MODERASI IKLAN"
    >
        <x-slot:actions>
            <a href="{{ route('platform.ads.index') }}" class="btn btn-outline text-xs sm:text-sm font-semibold">
                &larr; Kembali ke Antrean
            </a>
        </x-slot:actions>
    </x-feature-header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Left: Material Preview & Actions --}}
        <div class="card p-6 space-y-5">
            <h2 class="text-base font-bold text-text-main border-b border-border-c pb-3">Informasi Materi Iklan</h2>

            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-text-sec">Judul Kampanye:</span>
                    <p class="text-sm font-bold text-text-main mt-0.5">{{ $campaign->title }}</p>
                </div>
                <div>
                    <span class="text-text-sec">Tagline:</span>
                    <p class="text-text-main">{{ $campaign->tagline ?? '-' }}</p>
                </div>
                <div class="flex justify-between pt-2 border-t border-border-c">
                    <span class="text-text-sec">Toko Pengiklan:</span>
                    <span class="font-bold text-text-main">{{ $campaign->outlet?->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-sec">Tipe Iklan:</span>
                    <span class="badge badge-secondary">{{ $campaign->ad_type }}</span>
                </div>
                @if($campaign->product)
                <div class="flex justify-between">
                    <span class="text-text-sec">Produk Terkait:</span>
                    <span class="font-semibold text-primary">{{ $campaign->product->name }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-text-sec">Waktu Pengajuan:</span>
                    <span class="text-text-sec">{{ $campaign->created_at->format('d M Y H:i') }} WIB</span>
                </div>
            </div>

            {{-- Review Decision Controls --}}
            @if(in_array($campaign->status, ['PENDING_REVIEW', 'NEEDS_REVISION']))
            <div class="pt-4 border-t border-border-c space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-text-sec">Keputusan Peninjauan Materi (SLA 12 Jam)</h3>

                <form action="{{ route('platform.ads.review', $campaign->id) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="form-label text-xs">Catatan Peninjauan (Wajib diisi jika Perlu Revisi / Tolak)</label>
                        <textarea name="notes" rows="3" placeholder="Tuliskan alasan revisi secara spesifik..." class="form-input text-xs"></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <button type="submit" name="decision" value="APPROVE" class="btn-primary text-xs justify-center py-2" onclick="return confirm('Setujui materi iklan ini?')">
                            ✓ Setujui (Approve)
                        </button>
                        <button type="submit" name="decision" value="NEEDS_REVISION" class="btn-outline text-xs justify-center py-2 text-amber-700 border-amber-300 hover:bg-amber-50">
                            ⚠️ Perlu Revisi
                        </button>
                        <button type="submit" name="decision" value="REJECT" class="btn-danger text-xs justify-center py-2" onclick="return confirm('Tolak materi iklan ini?')">
                            ✕ Tolak (Reject)
                        </button>
                    </div>
                </form>
            </div>
            @endif

            {{-- Suspend Control if Active --}}
            @if($campaign->status === 'ACTIVE')
            <div class="pt-4 border-t border-red-200">
                <div x-data="{ open: false }">
                    <button type="button" @click="open = true" class="w-full btn-danger text-xs justify-center py-2">
                        🛑 Tangguhkan Iklan (Konten Menyesatkan / Pelanggaran)
                    </button>

                    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-cloak>
                        <div @click.away="open = false" class="bg-white rounded-2xl max-w-sm w-full p-6 text-left space-y-3 shadow-xl">
                            <h3 class="text-sm font-bold text-red-700">Tangguhkan Penayangan Iklan</h3>
                            <form action="{{ route('platform.ads.suspend', $campaign->id) }}" method="POST" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="form-label text-xs">Alasan Penangguhan</label>
                                    <textarea name="reason" rows="3" required placeholder="Jelaskan pelanggaran atau klaim menyesatkan..." class="form-input text-xs"></textarea>
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" @click="open = false" class="btn-outline text-xs">Batal</button>
                                    <button type="submit" class="btn-danger text-xs">Tangguhkan Iklan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Banner Image Viewer & Pending Revisions --}}
        <div class="space-y-6">
            <div class="card p-6 space-y-3">
                <h2 class="text-base font-bold text-text-main border-b border-border-c pb-3">Banner Iklan</h2>
                <div class="rounded-xl overflow-hidden border border-border-c bg-slate-50 flex items-center justify-center p-2">
                    @if($campaign->banner_path)
                    <img src="{{ asset('storage/' . $campaign->banner_path) }}" alt="{{ $campaign->title }}" class="max-h-80 w-auto object-contain rounded-lg">
                    @else
                    <span class="text-xs text-text-sec">Tidak ada gambar</span>
                    @endif
                </div>
            </div>

            {{-- Pending Creative Revisions --}}
            @php $pendingRev = $campaign->revisions->where('status', 'PENDING')->first(); @endphp
            @if($pendingRev)
            <div class="card p-6 space-y-4 border-2 border-amber-300 bg-amber-50/30">
                <h3 class="text-sm font-bold text-amber-900">Pengajuan Revisi Materi dari Toko (Tipe: {{ $pendingRev->revision_type }})</h3>
                <div class="text-xs space-y-2">
                    <p><strong>Judul Baru:</strong> {{ $pendingRev->title }}</p>
                    <p><strong>Tagline Baru:</strong> {{ $pendingRev->tagline }}</p>
                    @if($pendingRev->banner_path)
                    <img src="{{ asset('storage/' . $pendingRev->banner_path) }}" alt="" class="h-32 object-cover rounded-lg border">
                    @endif
                </div>

                <form action="{{ route('platform.ads.review-revision', $pendingRev->id) }}" method="POST" class="space-y-3 pt-3 border-t border-amber-200">
                    @csrf
                    <div>
                        <label class="form-label text-xs">Catatan Revisi</label>
                        <input type="text" name="notes" placeholder="Catatan opsional..." class="form-input text-xs">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" name="decision" value="APPROVE" class="flex-1 btn-primary text-xs justify-center py-2">
                            ✓ Setujui Revisi
                        </button>
                        <button type="submit" name="decision" value="REJECT" class="flex-1 btn-danger text-xs justify-center py-2">
                            ✕ Tolak Revisi
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

    </div>

</div>
@endsection
