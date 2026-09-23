@props(['rfq'])

{{--
    The deadline cell for a buyer's request: a live countdown while it is open,
    otherwise what happened and when.
--}}
@if ($rfq->isOpen() && $rfq->quote_deadline)
    <x-countdown-chip :until="$rfq->quote_deadline" size="sm" {{ $attributes }} />
@elseif ($rfq->status === App\Enums\RfqStatus::Awarded && $rfq->awarded_at)
    <span {{ $attributes->class('text-[13px] font-semibold text-success-700 tabular') }}>
        {{ __('buyer.awarded_on', ['date' => $rfq->awarded_at->translatedFormat('j M')]) }}
    </span>
@elseif (in_array($rfq->status, [App\Enums\RfqStatus::Closed, App\Enums\RfqStatus::Expired], true) && $rfq->quote_deadline)
    <span {{ $attributes->class('text-[13px] font-semibold text-gray-500 tabular') }}>
        {{ __('buyer.closed_on', ['date' => $rfq->quote_deadline->translatedFormat('j M')]) }}
    </span>
@else
    <span {{ $attributes->class('text-[13px] text-gray-400') }}>—</span>
@endif
