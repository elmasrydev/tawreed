@props(['tone' => 'info', 'icon' => null, 'title' => null])

{{-- Inline notice. Tones: info, success, warning, danger, neutral. --}}
@php
    $tones = [
        'info' => ['border-brand-200 bg-brand-100 text-brand-700', 'info'],
        'success' => ['border-success-200 bg-success-50 text-success-700', 'check-circle'],
        'warning' => ['border-warning-200 bg-warning-100 text-warning-800', 'alert-circle'],
        'danger' => ['border-danger-200 bg-danger-100 text-danger-800', 'alert-circle'],
        'neutral' => ['border-gray-200 bg-gray-100 text-gray-700', 'info'],
        'accent' => ['border-accent-200 bg-accent-50 text-accent-800', 'alert-circle'],
    ];
    [$classes, $defaultIcon] = $tones[$tone] ?? $tones['info'];
@endphp

<div {{ $attributes->class(['flex items-start gap-3 rounded-lg border px-4 py-3 text-[13px] leading-relaxed', $classes]) }} role="status">
    <x-lucide :name="$icon ?? $defaultIcon" :size="16" class="mt-0.5" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <span class="font-semibold">{{ $title }}</span>
        @endif
        {{ $slot }}
    </div>
    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
