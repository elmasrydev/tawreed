@props(['profile', 'seal' => 16])

{{--
    A supplier's company name with the verified seal. Verified suppliers link
    to their public profile; anyone else is shown as plain text.
--}}
@if ($profile === null)
    <span {{ $attributes->class('text-gray-400') }}>—</span>
@elseif ($profile->isVerified())
    <a href="{{ route('buyer.suppliers.show', $profile) }}" wire:navigate
       {{ $attributes->class('inline-flex min-w-0 max-w-full items-center gap-1.5 hover:text-brand-500') }}>
        <span class="truncate">{{ $profile->company_name }}</span>
        <x-verified-seal :size="$seal" />
    </a>
@else
    <span {{ $attributes->class('inline-flex min-w-0 max-w-full items-center gap-1.5') }}>
        <span class="truncate">{{ $profile->company_name }}</span>
    </span>
@endif
