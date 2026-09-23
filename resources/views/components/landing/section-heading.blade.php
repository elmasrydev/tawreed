@props(['eyebrow', 'title', 'align' => 'center', 'size' => 'lg'])

{{-- Eyebrow + heading pair used at the top of each landing section. --}}
<div {{ $attributes->class(['flex flex-col gap-2', 'items-center text-center' => $align === 'center']) }}>
    <span class="text-[13px] font-semibold tracking-[1.2px] text-brand-500 uppercase">{{ $eyebrow }}</span>
    <h2 @class([
        'font-bold text-balance text-brand-700',
        'text-[28px] leading-tight tracking-[-0.6px] sm:text-[34px] lg:text-[40px] lg:tracking-[-1px]' => $size === 'lg',
        'text-[26px] leading-tight tracking-[-0.5px] sm:text-[30px] lg:text-[34px] lg:tracking-[-0.8px]' => $size === 'md',
    ])>{{ $title }}</h2>
    {{ $slot }}
</div>
