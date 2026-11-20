@props([
    'roleLabel' => null,
    'title' => null,
    'description' => null,
    'actionLabel' => null,
    'actionRoute' => null,
    'actionIcon' => 'plus',
    'showAction' => true,
    'badgeIndicator' => 'bg-emerald-400',
])

<div class="nayara-dashboard-hero relative overflow-hidden rounded-2xl p-5 sm:p-6 text-white shadow-card flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 cursor-default"
     data-nayara-hero
     style="background: linear-gradient(114deg, #74163e 0%, #4e102e 75%, #7e2451 100%);">
    
    {{-- Subtle decorative watermark star --}}
    <div class="absolute -right-4 -bottom-8 text-white/[0.05] text-9xl font-serif pointer-events-none select-none" aria-hidden="true">✦</div>

    <div class="space-y-1 relative z-10">
        @if($roleLabel)
            <div class="flex items-center gap-2">
                <span class="nayara-hero-badge inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-white border border-white/15"
                      style="color: #FFFFFF !important;">
                    <span class="w-1.5 h-1.5 rounded-full {{ $badgeIndicator }}"></span>
                    {{ $roleLabel }}
                </span>
            </div>
        @endif

        <h1 class="nayara-hero-title text-xl sm:text-2xl font-bold tracking-tight text-white"
            style="color: #FFFFFF !important; font-size: 25px !important; font-weight: 700 !important;">
            {{ $title }}
        </h1>

        @if($description)
            <p class="nayara-hero-subtitle text-xs sm:text-sm text-[#FFD8D8]/90 max-w-xl leading-relaxed"
               style="color: rgba(255, 255, 255, 0.88) !important; font-size: 14px !important;">
                {{ $description }}
            </p>
        @endif
    </div>

    @if($showAction && $actionRoute && $actionLabel)
        <div class="flex items-center gap-2 shrink-0 relative z-10">
            <a href="{{ $actionRoute }}"
               class="nayara-hero-action inline-flex items-center justify-center gap-1.5 h-10 px-5 rounded-full bg-white text-[#68113F] hover:bg-[#F7E6EE] text-xs sm:text-sm font-bold shadow-sm transition-all duration-200 cursor-pointer"
               style="background-color: #FFFFFF !important; color: #68113F !important;">
                @if($actionIcon === 'plus' || $actionIcon === 'order')
                    <svg class="w-4 h-4 text-[#68113F]" style="color: #68113F !important;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                @elseif($actionIcon === 'search')
                    <svg class="w-4 h-4 text-[#68113F]" style="color: #68113F !important;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                @elseif($actionIcon === 'eye')
                    <svg class="w-4 h-4 text-[#68113F]" style="color: #68113F !important;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                @elseif($actionIcon === 'chart')
                    <svg class="w-4 h-4 text-[#68113F]" style="color: #68113F !important;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                @endif
                <span style="color: #68113F !important;">{{ $actionLabel }}</span>
            </a>
        </div>
    @elseif(isset($slot) && $slot->isNotEmpty())
        <div class="flex items-center gap-2 shrink-0 relative z-10">
            {{ $slot }}
        </div>
    @endif
</div>
