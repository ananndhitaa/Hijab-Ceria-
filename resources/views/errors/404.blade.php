<x-guest-layout title="404 - Halaman Tidak Ditemukan">
    <div class="min-h-screen flex items-center justify-center p-4 bg-[#FFFCFB]">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-[#FFD8D8] p-8 text-center">
            
            <div class="w-16 h-16 bg-[#FFD8D8] text-[#8E1B5C] rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h1 class="text-3xl font-extrabold text-[#172B4D] mb-2">404</h1>
            <h2 class="text-base font-semibold text-[#8E1B5C] mb-3">Halaman Tidak Ditemukan</h2>
            <p class="text-xs text-[#64748B] mb-6">
                {{ $exception->getMessage() ?: 'Halaman atau token yang Anda cari tidak ditemukan.' }}
            </p>

            <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-6 py-2.5 bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white text-xs font-semibold rounded-lg shadow transition">
                &larr; Kembali ke Beranda
            </a>

        </div>
    </div>
</x-guest-layout>
