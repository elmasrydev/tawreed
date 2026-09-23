@props(['item', 'withCategory' => false])

{{-- The anonymised buyer line: lock, business type, governorate and optionally the category path. --}}
<p {{ $attributes->class('flex min-w-0 items-center gap-1.5 text-xs text-gray-500') }}>
    <span class="inline-flex shrink-0 items-center gap-1.5 font-semibold text-gray-600">
        <x-lucide name="lock" :size="12" />
        {{ $item->buyerType() }}
    </span>
    @if ($item->governorate !== '')
        <span aria-hidden="true">·</span>
        <span class="shrink-0">{{ $item->governorate }}</span>
    @endif
    @if ($withCategory && $item->categoryPath() !== '')
        <span aria-hidden="true">·</span>
        <span class="truncate">{{ $item->categoryPath() }}</span>
    @endif
</p>
