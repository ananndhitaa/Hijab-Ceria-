<x-guest-layout title="403 - Akses Ditolak">
    <div class="min-h-screen flex items-center justify-center p-4 bg-[#FFFCFB]">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-[#FFD8D8] p-8 text-center">
            
            <div class="w-16 h-16 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>

            <h1 class="text-3xl font-extrabold text-[#172B4D] mb-2">403</h1>
            <h2 class="text-base font-semibold text-[#8E1B5C] mb-3">Akses Ditolak (Forbidden)</h2>
            <p class="text-xs text-[#64748B] mb-6">
                {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses untuk membuka halaman ini.' }}
            </p>

            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-2.5 bg-[#8E1B5C] hover:bg-[#8E1B5C] text-white text-xs font-semibold rounded-lg shadow transition">
                &larr; Kembali ke Dashboard
            </a>

        </div>
    </div>
</x-guest-layout>
