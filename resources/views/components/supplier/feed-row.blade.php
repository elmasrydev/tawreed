@props(['item', 'blockerHint' => null])

{{--
    One request in the Browse RFQs list. The whole row opens the request; the
    action shows the supplier's own state: already quoted, locked or ready.
--}}
<article class="relative grid gap-3 rounded-card border border-gray-200 bg-white px-4 py-4 transition hover:border-brand-500 hover:shadow-card-hover sm:px-[18px] md:grid-cols-[minmax(0,1fr)_104px_104px_auto] md:items-center md:gap-3.5">
    <div class="flex min-w-0 flex-col gap-1.5">
        <x-supplier.buyer-line :item="$item" with-category />

        <h2 class="text-[15px] leading-snug font-semibold text-gray-900 md:truncate">
            <a href="{{ route('supplier.rfqs.show', $item->id) }}" wire:navigate class="after:absolute after:inset-0 hover:text-brand-700">
                {{ $item->title }}
            </a>
        </h2>

        <div class="flex flex-wrap items-center gap-2">
            @if ($item->deadlineAt)
                <x-countdown-chip :until="$item->deadlineAt" size="sm" />
            @endif
            <span class="text-xs text-gray-500 tabular">
                {{ trans_choice('supplier.quotes_count', $item->quotesCount, ['count' => $item->quotesCount]) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 md:contents">
        <div class="min-w-0">
            <p class="eyebrow">{{ __('ui.quantity') }}</p>
            <p class="text-sm font-semibold text-gray-900 tabular">{{ $item->quantityWithUnit() }}</p>
            <p class="text-xs text-gray-500">{{ $item->supplyType }}</p>
        </div>

        <div class="min-w-0">
            <p class="eyebrow">{{ __('supplier.deliver_by') }}</p>
            <p class="text-sm font-semibold text-gray-900 tabular">{{ $item->deliveryDate }}</p>
        </div>
    </div>

    <div class="relative z-10 flex items-center md:justify-end">
        @if ($item->hasOwnQuote())
            <x-badge tone="success" icon="check">{{ __('ui.quote_submitted_badge') }}</x-badge>
        @elseif (! $item->acceptsQuotes)
            <span class="btn btn-sm max-md:h-10 w-full cursor-not-allowed bg-gray-200 text-gray-400 md:w-auto" aria-disabled="true">
                {{ __('common.closed') }}
            </span>
        @elseif ($blockerHint)
            <span class="btn btn-sm max-md:h-10 w-full cursor-not-allowed bg-gray-200 text-gray-400 md:w-auto" aria-disabled="true" title="{{ $blockerHint }}">
                <x-lucide name="lock" :size="14" />
                {{ __('supplier.quote_action') }}
                <span class="sr-only">— {{ $blockerHint }}</span>
            </span>
        @else
            <a href="{{ route('supplier.rfqs.show', ['rfq' => $item->id, 'quote' => 1]) }}" wire:navigate class="btn btn-accent btn-sm max-md:h-10 w-full md:w-auto">
                {{ __('supplier.quote_action') }}
            </a>
        @endif
    </div>
</article>
