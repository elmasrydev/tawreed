@props(['item'])

{{--
    One open request on the public pages. Buyer identity is already stripped
    by the view model; the whole row leads to the supplier view, which asks a
    visitor to sign in first.
--}}
<article {{ $attributes->class('relative flex flex-col gap-2 rounded-card border border-gray-200 bg-white px-4 py-3.5 transition hover:border-brand-500 hover:shadow-card-hover') }}
         data-testid="public-rfq">
    <div class="flex items-center justify-between gap-3">
        <span class="inline-flex min-w-0 items-center gap-1.5 text-xs text-gray-500">
            <x-lucide name="lock" :size="11" class="shrink-0" />
            <span class="truncate">{{ $item->buyerLabel() !== '' ? $item->buyerLabel() : __('supplier.anonymous_buyer') }}</span>
        </span>
        @if ($item->deadlineAt)
            <x-countdown-chip :until="$item->deadlineAt" size="sm" />
        @endif
    </div>

    <h3 class="text-[15px] leading-snug font-semibold text-gray-900">
        <a href="{{ route('supplier.rfqs.show', $item->id) }}" class="line-clamp-2 break-words after:absolute after:inset-0 hover:text-brand-700">
            {{ $item->title }}
        </a>
    </h3>

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
        @if ($item->categoryPath() !== '')
            <span class="min-w-0 truncate">{{ $item->categoryPath() }}</span>
        @endif
        @if ($item->quantity !== '')
            <span class="font-semibold text-gray-900 tabular"><span dir="ltr">{{ $item->quantity }}</span> {{ $item->unit }}</span>
        @endif
        <span class="ms-auto inline-flex items-center gap-1 font-semibold text-brand-700">
            {{ trans_choice('landing.card_quotes', $item->quotesCount, ['count' => $item->quotesCount]) }}
        </span>
    </div>
</article>
