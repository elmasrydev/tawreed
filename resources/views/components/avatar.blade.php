@props(['name' => '', 'src' => null, 'size' => 36, 'shape' => 'square', 'tone' => 'navy'])

{{-- Initials or logo tile. Square tiles are companies, round ones are people. --}}
@php
    $words = preg_split('/\s+/u', trim((string) $name)) ?: [];
    $initials = mb_strtoupper(collect($words)->filter()->take(2)->map(fn (string $word): string => mb_substr($word, 0, 1))->implode(''));
    $tones = [
        'navy' => 'bg-brand-700 text-white',
        'blue' => 'bg-brand-500 text-white',
        'light' => 'bg-brand-100 text-brand-700',
        'teal' => 'bg-success-500 text-white',
        'orange' => 'bg-accent-500 text-white',
    ];
    $radius = $shape === 'round' ? 'rounded-full' : ($size >= 48 ? 'rounded-xl' : 'rounded-lg');
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center overflow-hidden font-bold', $radius, $tones[$tone] ?? $tones['navy'] => ! $src]) }}
      style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ max(11, (int) round($size * 0.36)) }}px">
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="size-full object-cover">
    @else
        {{ $initials ?: '?' }}
    @endif
</span>
