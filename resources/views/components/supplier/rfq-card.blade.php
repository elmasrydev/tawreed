@props(['item'])

{{-- Compact request card used on the dashboard ("ledger" style from the design). --}}
<a href="{{ route('supplier.rfqs.show', $item->id) }}" wire:navigate
   class="group grid grid-cols-[minmax(0,1fr)_auto] gap-x-4 gap-y-1.5 rounded-card border border-gray-300 bg-white px-4 py-3.5 transition hover:border-brand-500 hover:shadow-card-hover">
    <x-supplier.buyer-line :item="$item" with-category />

    @if ($item->deadlineAt)
        <x-countdown-chip :until="$item->deadlineAt" size="sm" class="justify-self-end" />
    @endif

    <h3 class="text-[15px] leading-snug font-semibold text-gray-900 group-hover:text-brand-700">{{ $item->title }}</h3>

    <span class="self-start text-end text-xs whitespace-nowrap text-gray-500 tabular">
        {{ trans_choice('supplier.quotes_count', $item->quotesCount, ['count' => $item->quotesCount]) }}
    </span>

    <p class="col-span-2 text-[13px] text-gray-700 tabular">
        <span class="font-semibold">{{ $item->quantityWithUnit() }}</span>
        · {{ __('supplier.deliver_by') }} <span class="font-semibold">{{ $item->deliveryDate }}</span>
        @if ($item->isRecurring)
            · {{ $item->supplyType }}
        @endif
        @if ($item->hasOwnQuote())
            <x-badge tone="success" icon="check" size="sm" class="ms-1 align-middle">{{ __('ui.quote_submitted_badge') }}</x-badge>
        @endif
    </p>
</a>
