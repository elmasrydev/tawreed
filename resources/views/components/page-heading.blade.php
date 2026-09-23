@props(['title', 'subtitle' => null, 'back' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-1 inline-block text-[13px] font-medium text-brand-500">
                <span aria-hidden="true">‹</span> {{ __('ui.back') }}
            </a>
        @endif

        <h1 class="text-xl font-bold text-ink-900 sm:text-2xl">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1 max-w-2xl text-sm leading-relaxed text-ink-500">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
    @endif
</div>
