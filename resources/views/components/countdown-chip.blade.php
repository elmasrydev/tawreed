@props(['until', 'prefix' => null, 'size' => 'md'])

{{--
    Deadline chip coloured by urgency: red under 6 hours, orange under a day,
    blue otherwise. Shows "Closed" once the deadline has passed.
--}}
@php
    $isPast = $until->isPast();
    $urgency = $isPast ? 'past' : App\Support\Countdown::urgency($until);
    $tones = [
        'urgent' => 'border-danger-200 bg-danger-50 text-danger-700',
        'soon' => 'border-accent-200 bg-accent-50 text-accent-800',
        'normal' => 'border-brand-200 bg-brand-100 text-brand-700',
        'past' => 'border-gray-200 bg-gray-100 text-gray-500',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center gap-1.5 rounded-md border font-semibold whitespace-nowrap tabular',
    $size === 'sm' ? 'h-[22px] px-2 text-[11px]' : 'h-[26px] px-2.5 text-xs',
    $tones[$urgency],
]) }}>
    <x-lucide name="clock" :size="12" />
    @if ($isPast)
        {{ __('common.closed') }}
    @else
        {{ __($prefix ?? 'common.closes_in', ['time' => App\Support\Countdown::format($until)]) }}
    @endif
</span>
