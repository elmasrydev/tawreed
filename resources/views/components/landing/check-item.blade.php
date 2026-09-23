@props(['title' => null, 'tone' => 'success'])

{{-- A benefit line with a filled check disc, used by the landing benefit cards. --}}
<li {{ $attributes->class('flex gap-3') }}>
    <span @class([
        'mt-px flex size-[22px] shrink-0 items-center justify-center rounded-full text-white',
        'bg-success-500' => $tone === 'success',
        'bg-accent-500' => $tone === 'accent',
    ])>
        <x-lucide name="check" :size="12" :stroke="3" />
    </span>
    <span>
        @if ($title)
            <b class="font-semibold">{{ $title }}</b>
        @endif
        {{ $slot }}
    </span>
</li>
