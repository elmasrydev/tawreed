@props(['tone' => 'neutral', 'dot' => false, 'icon' => null, 'pill' => false, 'size' => 'md'])

{{--
    Status badge. Tones follow the design system: info (blue), neutral and
    muted (grey), success and success-solid (teal), warning (amber), danger
    (red), dark, accent (orange countdown) and draft (dashed grey).
--}}
@php
    $tones = [
        'info' => ['bg-brand-100 text-brand-700', 'bg-brand-500'],
        'neutral' => ['bg-gray-100 text-gray-600', 'bg-gray-400'],
        'muted' => ['bg-gray-100 text-gray-500', 'bg-gray-400'],
        'success' => ['bg-success-50 text-success-700', 'bg-success-500'],
        'success-solid' => ['bg-success-500 text-white', 'bg-white'],
        'warning' => ['bg-warning-100 text-warning-800', 'bg-warning-600'],
        'warning-solid' => ['bg-warning-600 text-white', 'bg-white'],
        'danger' => ['bg-danger-100 text-danger-700', 'bg-danger-600'],
        'danger-solid' => ['bg-danger-600 text-white', 'bg-white'],
        'dark' => ['bg-gray-700 text-white', 'bg-white'],
        'accent' => ['border border-accent-200 bg-accent-50 text-accent-800', 'bg-accent-500'],
        'draft' => ['border border-dashed border-gray-300 bg-gray-100 text-gray-600', 'bg-gray-400'],
    ];

    [$toneClasses, $dotClass] = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center gap-1.5 font-semibold whitespace-nowrap',
    $size === 'sm' ? 'h-[22px] px-2 text-[11px]' : 'h-6 px-2.5 text-xs',
    $pill ? 'rounded-full' : 'rounded-md',
    $toneClasses,
]) }}>
    @if ($icon)
        <x-lucide :name="$icon" :size="12" :stroke="2.5" />
    @elseif ($dot)
        <span class="size-1.5 rounded-full {{ $dotClass }}"></span>
    @endif
    {{ $slot }}
</span>
