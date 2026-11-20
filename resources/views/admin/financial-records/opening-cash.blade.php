<x-app-layout pageTitle="Status Saldo Awal Kas Operasional" breadcrumb="Keuangan > Catatan Keuangan > Saldo Awal Kas">
    <div class="w-full max-w-none mx-0 space-y-5 sm:space-y-6">

        {{-- Header Card --}}
        <x-feature-header
            title="Saldo Awal Kas Operasional"
            subtitle="Informasi posisi kas tunai dan modal kerja awal konveksi."
            icon="finance"
            badge="KEUANGAN OPERASIONAL"
        >
            <x-slot:actions>
                <a href="{{ route('admin.financial-records.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-11 px-5 rounded-xl border border-[#FFD8D8] bg-white text-xs sm:text-sm font-semibold text-[#172B4D] hover:bg-[#FFD8D8] hover:text-[#8E1B5C] transition-all duration-200 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4 shrink-0 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    <span>Kembali ke Catatan</span>
                </a>
            </x-slot:actions>
        </x-feature-header>

        {{-- Owner Authority Notice Card --}}
        <div class="bg-[#FFFCFB] border border-[#FFD8D8] rounded-xl p-4.5 sm:p-5 shadow-xs flex items-start gap-3.5">
            <div class="w-9 h-9 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0 border border-[#FFD8D8]">
                <svg class="w-5 h-5 text-[#8E1B5C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="space-y-1 text-xs text-[#64748B]">
                <span class="font-bold text-[#172B4D] text-sm block">Kewenangan Eksklusif Pemilik Konveksi (Owner)</span>
                <p class="leading-relaxed">
                    Sesuai arsitektur tata kelola multi-tenant NAYARA, penetapan dan pengelolaan saldo awal kas operasional dilakukan satu kali secara permanen oleh <strong>Pemilik Konveksi (Owner)</strong> melalui menu <em>Owner &gt; Saldo Awal Kas</em>.
                </p>
                <p class="leading-relaxed text-[#172B4D] font-medium pt-1">
                    Administrator operasional hanya memiliki akses pemantauan (Read-Only) guna mencegah duplikasi atau ketidaksinkronan mutasi kas konveksi.
                </p>
                @if(auth()->user()->isOwner())
                    <div class="pt-2">
                        <a href="{{ route('owner.opening-cash.create') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#8E1B5C] text-white text-[11px] font-semibold hover:bg-[#A6266E] transition shadow-xs">
                            <span>Buka Pengaturan Saldo Awal Owner</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Read-Only Information Card --}}
        <div class="bg-white border border-[#FFD8D8] rounded-xl p-5 sm:p-7 shadow-card space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-[#64748B]">Posisi Saldo Awal Kas Terdaftar</h2>

            <div>
                <span class="block text-xs font-semibold text-[#64748B] mb-1">Nominal Saldo Awal Kas</span>
                <div class="text-xl sm:text-2xl font-bold font-mono text-[#8E1B5C] bg-[#FFFCFB] p-3.5 rounded-xl border border-[#FFD8D8]">
                    @if($current)
                        Rp {{ number_format((float) $current->amount, 0, ',', '.') }}
                    @else
                        <span class="text-xs text-amber-700 font-sans font-normal italic">Belum ditetapkan oleh Pemilik Konveksi (Owner)</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="block text-xs font-semibold text-[#64748B] mb-1">Tanggal Efektif Saldo</span>
                    <div class="text-xs font-semibold text-[#172B4D] bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8]">
                        {{ $current?->opening_date ? $current->opening_date->format('d F Y') : '—' }}
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-semibold text-[#64748B] mb-1">Status Penguncian</span>
                    <div class="text-xs font-semibold text-[#172B4D] bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8] flex items-center gap-1.5">
                        @if($current)
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="text-emerald-700">Terkunci Permanen (Tercatat)</span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span class="text-amber-700">Menunggu Penetapan Owner</span>
                        @endif
                    </div>
                </div>
            </div>

            <div>
                <span class="block text-xs font-semibold text-[#64748B] mb-1">Catatan / Keterangan</span>
                <div class="text-xs text-[#172B4D] bg-[#FFFCFB] p-3 rounded-lg border border-[#FFD8D8] min-h-[42px]">
                    {{ $current?->notes ?: 'Tidak ada catatan tambahan.' }}
                </div>
            </div>

            {{-- Action Footer --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#FFD8D8]">
                <a href="{{ route('admin.financial-records.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-lg bg-[#8E1B5C] text-white text-xs font-semibold hover:bg-[#A6266E] transition-all shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Catatan Keuangan</span>
                </a>
            </div>
        </div>

    </div>
</x-app-layout>
