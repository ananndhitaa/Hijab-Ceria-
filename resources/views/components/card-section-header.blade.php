@props([
    'title' => '',
    'subtitle' => null,
    'badge' => null,
    'meta' => null,
    'accent' => true,
])

@php
    $displayMeta = $meta ?? $badge;
@endphp

<div {{ $attributes->merge(['class' => 'nayara-section-header min-h-[52px] sm:min-h-[56px] px-5 sm:px-6 py-3 bg-[#FFD8D8] border-b border-[#FFD8D8] flex items-center justify-between flex-wrap gap-2.5 rounded-t-2xl']) }}>
    <div class="flex items-center gap-3 min-w-0">
        @if($accent)
            <span class="nayara-accent w-1 h-6 sm:h-7 rounded-xs bg-[#8E1B5C] shrink-0" aria-hidden="true"></span>
        @endif
        <div class="min-w-0">
            <h2 class="text-[15px] sm:text-[16px] font-semibold text-[#172B4D] leading-normal">
                {{ $title }}
            </h2>
            @if($subtitle)
                <p class="text-[12px] sm:text-[13px] text-[#64748B] mt-0.5 leading-tight">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @if($displayMeta || isset($actions) || (isset($slot) && $slot->isNotEmpty()))
        <div class="flex items-center gap-2 shrink-0">
            @if($displayMeta)
                <span class="text-[12px] sm:text-[13px] font-medium text-[#8E1B5C] bg-[#FFD8D8] border border-[#FFD8D8] px-2.5 py-0.5 rounded-lg">
                    {{ $displayMeta }}
                </span>
            @endif
            @if(isset($actions))
                {{ $actions }}
            @endif
            @if(isset($slot) && $slot->isNotEmpty())
                {{ $slot }}
            @endif
        </div>
    @endif
</div>
