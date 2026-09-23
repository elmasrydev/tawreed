@props(['amount', 'decimals' => 2])

{{-- An EGP amount with the number kept left-to-right inside Arabic text. --}}
<span {{ $attributes->class('whitespace-nowrap tabular') }}><span dir="ltr">{{ number_format((float) $amount, $decimals) }}</span> {{ __('ui.egp') }}</span>
