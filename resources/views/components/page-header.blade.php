@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => null, 'icon' => null])

{{-- Page title row: optional back link, title with icon, subtitle and actions. --}}
<div class="flex flex-col gap-3">
    @if ($back)
        <a href="{{ $back }}" wire:navigate
           class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-gray-500 hover:text-brand-700">
            <x-lucide name="arrow-left" :size="14" />
            {{ $backLabel ?? __('ui.back') }}
        </a>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="flex items-center gap-2.5 text-xl font-bold tracking-[-0.3px] text-brand-700 sm:text-2xl">
                @if ($icon)
                    <x-lucide :name="$icon" :size="22" class="text-brand-500" />
                @endif
                <span class="min-w-0 break-words">{{ $title }}</span>
            </h1>

            @if ($subtitle)
                <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
            @endif

            @isset($meta)
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2 text-[13px] text-gray-500">{{ $meta }}</div>
            @endisset
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
