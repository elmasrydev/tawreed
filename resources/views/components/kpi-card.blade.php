@props(['label', 'value', 'delta' => null, 'deltaTone' => 'muted', 'icon' => 'file-text', 'iconTone' => 'info', 'href' => null])

@php
    $iconTones = [
        'info' => 'bg-brand-100 text-brand-500',
        'accent' => 'bg-accent-50 text-accent-500',
        'success' => 'bg-success-50 text-success-500',
        'neutral' => 'bg-gray-100 text-gray-600',
        'danger' => 'bg-danger-50 text-danger-600',
    ];
    $deltaTones = [
        'muted' => 'text-gray-500',
        'success' => 'text-success-700',
        'danger' => 'text-danger-700',
        'warning' => 'text-accent-800',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->class(['card flex flex-col gap-1 px-4 py-4 sm:px-[18px]', 'transition hover:shadow-card-hover' => $href]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="eyebrow text-xs">{{ $label }}</p>
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $iconTones[$iconTone] ?? $iconTones['info'] }}">
            <x-lucide :name="$icon" :size="18" />
        </span>
    </div>
    <p class="text-2xl leading-none font-bold text-brand-700 tabular sm:text-[30px]">{{ $value }}</p>
    @if ($delta)
        <p class="mt-1 text-xs {{ $deltaTones[$deltaTone] ?? $deltaTones['muted'] }}">{{ $delta }}</p>
    @endif
</{{ $tag }}>
