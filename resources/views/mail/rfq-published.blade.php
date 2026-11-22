@php($buyer = $rfq->buyer)
<x-mail::message>
# {{ __('admin.rfq_published_mail_heading') }}

**{{ $rfq->reference }}** · {{ $rfq->title }}

<x-mail::table>
| | |
| :-- | :-- |
| {{ __('ui.categories') }} | {{ $rfq->categoryPath() ?? '—' }} |
| {{ __('admin.rfq_published_mail_quantity') }} | {{ collect([$rfq->formattedQuantity(), $rfq->unit?->name])->filter()->implode(' ') ?: '—' }} |
| {{ __('ui.governorate') }} | {{ $rfq->governorate?->name ?? '—' }} |
| {{ __('admin.rfq_published_mail_deadline') }} | {{ $rfq->quote_deadline?->toDateString() ?? '—' }} |
| {{ __('ui.company_name') }} | {{ $buyer->buyerProfile?->company_name ?? '—' }} |
| {{ __('ui.manager_name') }} | {{ $buyer->name }} |
| {{ __('ui.email') }} | {{ $buyer->email }} |
| {{ __('ui.phone') }} | {{ $buyer->phone ?? '—' }} |
</x-mail::table>

<x-mail::button :url="route('filament.admin.resources.rfqs.index')">
{{ __('admin.rfq_published_mail_button') }}
</x-mail::button>
</x-mail::message>
