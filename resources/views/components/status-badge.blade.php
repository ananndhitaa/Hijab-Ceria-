@props([
    'status'   => null,
    'context'  => null,
    'label'    => null,
    'size'     => 'table',
    'customer' => false,
])

@php
    $badge = \App\Support\StatusBadgePresenter::get(
        status: $status,
        context: $context,
        customLabel: $label,
        isCustomer: (bool) $customer,
        size: $size,
        extraClass: (string) ($attributes->get('class') ?? '')
    );
@endphp

<span {{ $attributes->except('class')->merge(['class' => $badge['full_class']]) }}>
    {{ $slot->isNotEmpty() ? $slot : $badge['label'] }}
</span>
