@props(['subtotal', 'vat' => null, 'delivery', 'grandTotal', 'deliveryDate' => null, 'validUntil' => null])

{{-- Live figures of the quote being written: the same grand total the buyer compares. --}}
@php($money = fn (float $amount): string => number_format($amount, 2))

<div {{ $attributes->class('flex flex-col gap-4') }}>
    <p class="eyebrow">{{ __('quote.live_summary') }}</p>

    <dl class="flex flex-col gap-2.5 text-sm tabular">
        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500">{{ __('quote.subtotal') }}</dt>
            <dd class="font-semibold text-gray-900" dir="ltr">{{ $money($subtotal) }}</dd>
        </div>
        @if ($vat !== null)
            <div class="flex items-center justify-between gap-3">
                <dt class="text-gray-500">{{ __('ui.vat') }}</dt>
                <dd class="font-semibold text-gray-900" dir="ltr">{{ $money($vat) }}</dd>
            </div>
        @endif
        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500">{{ __('quote.delivery') }}</dt>
            <dd class="font-semibold text-gray-900" dir="ltr">{{ $money($delivery) }}</dd>
        </div>
        <div class="flex items-baseline justify-between gap-3 border-t border-gray-200 pt-2.5">
            <dt class="font-semibold text-gray-900">{{ __('quote.grand_total') }}</dt>
            <dd class="text-xl font-bold text-brand-700">{{ $money($grandTotal) }} {{ __('common.egp') }}</dd>
        </div>
    </dl>

    <dl class="flex flex-col gap-1.5 rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-700">
        <div class="flex justify-between gap-3">
            <dt>{{ __('quote.summary_delivery') }}</dt>
            <dd class="font-semibold tabular">{{ $deliveryDate?->translatedFormat('j M Y') ?? '—' }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt>{{ __('quote.valid_until') }}</dt>
            <dd class="font-semibold tabular">{{ $validUntil?->translatedFormat('j M Y') ?? '—' }}</dd>
        </div>
    </dl>

    <p class="flex gap-2 text-xs leading-relaxed text-gray-500">
        <x-lucide name="shield" :size="14" class="mt-px shrink-0" />
        {{ __('quote.privacy_note') }}
    </p>
</div>
