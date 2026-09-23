@props(['icon' => 'file-text', 'title', 'body' => null])

<div {{ $attributes->class('card flex flex-col items-center gap-2 px-6 py-10 text-center') }}>
    <span class="mb-1 flex size-14 items-center justify-center rounded-[14px] bg-brand-100 text-brand-700">
        <x-lucide :name="$icon" :size="26" />
    </span>
    <p class="text-base font-semibold text-gray-900">{{ $title }}</p>
    @if ($body)
        <p class="max-w-sm text-sm leading-relaxed text-gray-500">{{ $body }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-2 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
