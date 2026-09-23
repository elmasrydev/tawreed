@props(['status', 'quote' => null, 'size' => 'md'])

{{--
    Maps each domain status enum to its badge style from the design system.
    Pass the quote itself to get the "Not selected" wording for quotes that
    were closed by an award rather than rejected with a reason.
--}}
@php
    [$tone, $dot, $icon, $pill, $extra] = match (true) {
        $status === \App\Enums\RfqStatus::Draft => ['draft', false, null, false, ''],
        $status === \App\Enums\RfqStatus::Open => ['info', true, null, false, ''],
        $status === \App\Enums\RfqStatus::Awarded => ['success', true, null, false, ''],
        $status === \App\Enums\RfqStatus::Closed, $status === \App\Enums\RfqStatus::Expired => ['muted', false, null, false, ''],
        $status === \App\Enums\RfqStatus::Removed => ['danger', false, null, false, ''],

        $status === \App\Enums\QuoteStatus::Pending => ['info', false, null, false, ''],
        $status === \App\Enums\QuoteStatus::Shortlisted => ['warning', false, 'star', false, ''],
        $status === \App\Enums\QuoteStatus::Selected => ['success-solid', false, 'check', false, ''],
        $status === \App\Enums\QuoteStatus::Rejected && $quote?->isNotSelected() => ['muted', false, null, false, ''],
        $status === \App\Enums\QuoteStatus::Rejected => ['danger', false, null, false, ''],
        $status === \App\Enums\QuoteStatus::Withdrawn => ['muted', false, null, false, ''],
        $status === \App\Enums\QuoteStatus::Expired => ['muted', false, null, false, 'line-through'],

        $status === \App\Enums\VerificationStatus::Pending => ['neutral', true, null, true, ''],
        $status === \App\Enums\VerificationStatus::Verified => ['success-solid', false, 'check', true, ''],
        $status === \App\Enums\VerificationStatus::Rejected => ['danger', true, null, true, ''],
        $status === \App\Enums\VerificationStatus::Suspended => ['dark', false, null, true, ''],

        $status === \App\Enums\SubscriptionStatus::Trial => ['info', false, null, false, ''],
        $status === \App\Enums\SubscriptionStatus::Active => ['success', true, null, false, ''],
        $status === \App\Enums\SubscriptionStatus::Expired => ['danger', false, null, false, ''],
        $status === \App\Enums\SubscriptionStatus::Cancelled => ['muted', false, null, false, ''],

        $status === \App\Enums\DocumentStatus::Pending => ['neutral', true, null, false, ''],
        $status === \App\Enums\DocumentStatus::Accepted => ['success', false, 'check', false, ''],
        $status === \App\Enums\DocumentStatus::Rejected => ['danger', false, null, false, ''],

        default => ['neutral', false, null, false, ''],
    };

    $label = $quote ? $quote->statusLabel() : $status->label();
@endphp

<x-badge :tone="$tone" :dot="$dot" :icon="$icon" :pill="$pill" :size="$size" {{ $attributes->class($extra) }}>
    {{ $label }}
</x-badge>
