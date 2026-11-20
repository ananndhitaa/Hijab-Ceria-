@props([
    'title' => '',
    'subtitle' => null,
    'badge' => null,
    'icon' => null,
    'accent' => 'border-l-[#8E1B5C]',
    'titleClass' => 'text-xl sm:text-[22px] md:text-[24px] font-bold',
])

@if(request()->routeIs('platform.*') || request()->is('platform*'))
    <section class="rounded-2xl px-5 py-3.5 sm:px-6 sm:py-4 text-white relative overflow-hidden shadow-lg border border-white/10 mb-6"
             style="background: linear-gradient(135deg, #691B3D 0%, #4A132E 55%, #350920 100%);">

        {{-- Ambient wave circles behind --}}
        <div class="absolute -right-16 -top-24 w-96 h-96 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
        <div class="absolute right-48 -bottom-20 w-80 h-80 rounded-full bg-[#8E1B5C]/20 blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3.5 relative z-10 min-w-0">
                @if(isset($iconSlot) || $icon)
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/10 border border-white/20 text-white flex items-center justify-center shrink-0 shadow-xs">
                        @if(isset($iconSlot))
                            {{ $iconSlot }}
                        @elseif($icon === 'finance' || $icon === 'money' || $icon === 'credit-card')
                            <svg class="w-4.5 h-4.5"><use href="#i-credit-card"/></svg>
                        @elseif($icon === 'ads' || $icon === 'megaphone')
                            <svg class="w-4.5 h-4.5"><use href="#i-megaphone"/></svg>
                        @elseif($icon === 'refund' || $icon === 'refresh')
                            <svg class="w-4.5 h-4.5"><use href="#i-refresh"/></svg>
                        @elseif($icon === 'chart' || $icon === 'analytics')
                            <svg class="w-4.5 h-4.5"><use href="#i-chart"/></svg>
                        @elseif($icon === 'building' || $icon === 'tenant')
                            <svg class="w-4.5 h-4.5"><use href="#i-building"/></svg>
                        @elseif($icon === 'clipboard' || $icon === 'register')
                            <svg class="w-4.5 h-4.5"><use href="#i-clipboard"/></svg>
                        @elseif($icon === 'users' || $icon === 'customer')
                            <svg class="w-4.5 h-4.5"><use href="#i-users"/></svg>
                        @elseif($icon === 'security' || $icon === 'key')
                            <svg class="w-4.5 h-4.5"><use href="#i-settings"/></svg>
                        @elseif($icon === 'history' || $icon === 'audit')
                            <svg class="w-4.5 h-4.5"><use href="#i-history"/></svg>
                        @elseif($icon === 'graduation' || $icon === 'services')
                            <svg class="w-4.5 h-4.5"><use href="#i-graduation"/></svg>
                        @elseif($icon === 'activity' || $icon === 'system')
                            <svg class="w-4.5 h-4.5"><use href="#i-activity"/></svg>
                        @else
                            <svg class="w-4.5 h-4.5"><use href="#i-grid"/></svg>
                        @endif
                    </div>
                @endif

                <div class="space-y-1 min-w-0">
                    @if($badge)
                        <div class="flex items-center gap-2 mb-0.5">
                            @if(is_string($badge) && !str_contains($badge, '<'))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9.5px] font-bold uppercase tracking-wider bg-white/10 text-[#FFD8D8] border border-white/15">
                                    {!! $badge !!}
                                </span>
                            @else
                                {!! $badge !!}
                            @endif
                        </div>
                    @endif

                    <h1 class="text-lg sm:text-xl md:text-[22px] font-extrabold text-white tracking-tight leading-tight">
                        {{ $title }}
                    </h1>

                    @if($subtitle)
                        <p class="text-xs text-[#F7E6EE]/90 leading-normal font-normal pt-0.5">
                            {{ $subtitle }}
                        </p>
                    @endif

                    @if(isset($slot) && $slot->isNotEmpty())
                        {{ $slot }}
                    @endif
                </div>
            </div>

            @if(isset($actions) && $actions->isNotEmpty())
                <div class="nayara-header-actions flex flex-wrap items-center gap-2.5 sm:gap-3 shrink-0 relative z-10">
                    {{ $actions }}
                </div>
            @else
                <div class="hidden xl:flex items-center justify-end shrink-0 pointer-events-none select-none pl-1 glass-tablet-3d-wrap">
                    <div class="glass-tablet-3d w-44 p-2.5 relative overflow-hidden flex items-center justify-between gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#E11D48] via-[#ba4e77] to-[#8E1B5C] border border-white/40 flex flex-col items-center justify-center shadow-xs p-0.5 shrink-0">
                            <span class="font-serif font-black text-white text-sm leading-none italic select-none">n</span>
                        </div>
                        <div class="flex-1 bg-white/10 border border-white/20 rounded-lg p-1.5 backdrop-blur-md flex flex-col justify-between h-14">
                            <div class="w-12 h-1 bg-white/40 rounded-full"></div>
                            <div class="flex items-end justify-between gap-1 h-7">
                                <div class="w-2.5 h-[40%] bg-white/30 rounded-t-xs"></div>
                                <div class="w-2.5 h-[65%] bg-white/45 rounded-t-xs"></div>
                                <div class="w-2.5 h-[90%] bg-[#FFD8D8]/70 rounded-t-xs"></div>
                                <div class="w-2.5 h-[50%] bg-white/35 rounded-t-xs"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@else
<div {{ $attributes->merge(['class' => "nayara-feature-header nayara-page-header bg-white border border-[#FFD8D8] border-l-4 {$accent} rounded-2xl min-h-[104px] md:min-h-[116px] lg:min-h-[120px] px-5 sm:px-6 md:px-7 py-4 md:py-[22px] shadow-card flex flex-col md:flex-row md:items-center justify-between gap-4 sm:gap-6 w-full"]) }}>
    <div class="min-w-0 flex items-center gap-3.5 sm:gap-4">
        @if(isset($iconSlot) || $icon)
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-[#FFD8D8] text-[#8E1B5C] flex items-center justify-center shrink-0 border border-[#FFD8D8]/80 shadow-xs">
                @if(isset($iconSlot))
                    {{ $iconSlot }}
                @elseif($icon === 'mrp' || $icon === 'planning')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                @elseif($icon === 'purchase' || $icon === 'truck')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                @elseif($icon === 'order' || $icon === 'cart')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                @elseif($icon === 'material' || $icon === 'box')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                @elseif($icon === 'finance' || $icon === 'money')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif($icon === 'production' || $icon === 'batch')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                @elseif($icon === 'customer' || $icon === 'users')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                @elseif($icon === 'product' || $icon === 'tags')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                @elseif($icon === 'supplier')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                @elseif($icon === 'chart' || $icon === 'analytics')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                @elseif($icon === 'security' || $icon === 'key')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                @elseif($icon === 'building' || $icon === 'tenant')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                @elseif($icon === 'clipboard' || $icon === 'register')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                @elseif($icon === 'ads' || $icon === 'megaphone')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                @elseif($icon === 'refund' || $icon === 'refresh')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                @elseif($icon === 'clock' || $icon === 'history' || $icon === 'audit' || $icon === 'activity' || $icon === 'log')
                    <svg class="w-5 h-5 sm:w-[22px] sm:h-[22px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    {!! $icon !!}
                @endif
            </div>
        @endif

        <div class="space-y-1 min-w-0">
            @if($badge)
                <div class="flex items-center gap-2 mb-1">
                    @if(is_string($badge) && !str_contains($badge, '<'))
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] sm:text-xs font-bold uppercase tracking-wider bg-[#FFD8D8] text-[#8E1B5C] border border-[#FFD8D8]">
                            {{ $badge }}
                        </span>
                    @else
                        {!! $badge !!}
                    @endif
                </div>
            @endif

            <h1 class="{{ $titleClass }} tracking-tight text-[#172B4D] leading-tight">
                {{ $title }}
            </h1>

            @if($subtitle)
                <p class="text-[13px] sm:text-sm text-[#64748B] leading-relaxed">
                    {{ $subtitle }}
                </p>
            @endif

            @if(isset($slot) && $slot->isNotEmpty())
                {{ $slot }}
            @endif
        </div>
    </div>

    @if(isset($actions) && $actions->isNotEmpty())
        <div class="nayara-header-actions flex flex-wrap items-center gap-2.5 sm:gap-3 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
@endif
