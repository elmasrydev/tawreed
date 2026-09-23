@props(['size' => 16])

<svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" aria-label="{{ __('common.verified_supplier') }}" role="img"
     {{ $attributes->class('shrink-0') }}>
    <circle cx="12" cy="12" r="11" fill="#17A398" />
    <polyline points="17 8 10.5 15 7 11.5" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
</svg>
