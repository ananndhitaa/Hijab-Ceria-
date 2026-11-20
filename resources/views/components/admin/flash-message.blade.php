{{-- Unified Flash Message Component (NAYARA Design System) --}}
@if(session('success'))
    <div x-data="{ show: true }"
         x-show="show"
         class="mb-4 px-4 py-3 min-h-[48px] bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-[13px] sm:text-sm font-medium flex items-center gap-3 shadow-2xs animate-fade-in"
         role="alert">
        <svg class="w-5 h-5 text-emerald-600 shrink-0" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="flex-1 min-w-0 leading-snug">
            {{ session('success') }}
        </div>
        <button type="button"
                @click="show = false"
                class="text-emerald-700 hover:text-emerald-900 p-1 rounded-lg transition-colors shrink-0 cursor-pointer inline-flex items-center justify-center"
                aria-label="Tutup notifikasi">
            <svg class="w-4 h-4 shrink-0" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
@endif

@if(session('error'))
    <div x-data="{ show: true }"
         x-show="show"
         class="mb-4 px-4 py-3 min-h-[48px] bg-red-50 border border-red-200 text-red-800 rounded-lg text-[13px] sm:text-sm font-medium flex items-center gap-3 shadow-2xs animate-fade-in"
         role="alert">
        <svg class="w-5 h-5 text-red-600 shrink-0" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
        </svg>
        <div class="flex-1 min-w-0 leading-snug">
            {{ session('error') }}
        </div>
        <button type="button"
                @click="show = false"
                class="text-red-700 hover:text-red-900 p-1 rounded-lg transition-colors shrink-0 cursor-pointer inline-flex items-center justify-center"
                aria-label="Tutup notifikasi">
            <svg class="w-4 h-4 shrink-0" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
@endif

@if(session('warning'))
    <div x-data="{ show: true }"
         x-show="show"
         class="mb-4 px-4 py-3 min-h-[48px] bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-[13px] sm:text-sm font-medium flex items-center gap-3 shadow-2xs animate-fade-in"
         role="alert">
        <svg class="w-5 h-5 text-amber-600 shrink-0" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <div class="flex-1 min-w-0 leading-snug">
            {{ session('warning') }}
        </div>
        <button type="button"
                @click="show = false"
                class="text-amber-700 hover:text-amber-900 p-1 rounded-lg transition-colors shrink-0 cursor-pointer inline-flex items-center justify-center"
                aria-label="Tutup notifikasi">
            <svg class="w-4 h-4 shrink-0" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
@endif

@if(session('info'))
    <div x-data="{ show: true }"
         x-show="show"
         class="mb-4 px-4 py-3 min-h-[48px] bg-sky-50 border border-sky-200 text-sky-800 rounded-lg text-[13px] sm:text-sm font-medium flex items-center gap-3 shadow-2xs animate-fade-in"
         role="alert">
        <svg class="w-5 h-5 text-sky-600 shrink-0" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <div class="flex-1 min-w-0 leading-snug">
            {{ session('info') }}
        </div>
        <button type="button"
                @click="show = false"
                class="text-sky-700 hover:text-sky-900 p-1 rounded-lg transition-colors shrink-0 cursor-pointer inline-flex items-center justify-center"
                aria-label="Tutup notifikasi">
            <svg class="w-4 h-4 shrink-0" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
@endif
