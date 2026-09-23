@props(['tone' => 'light'])

{{-- EN | عربي segmented switch. Each half posts to the locale endpoint. --}}
@php
    $options = ['en' => 'EN', 'ar' => 'عربي'];
    $wrapper = $tone === 'dark' ? 'border-white/25' : 'border-gray-300';
    $hasDisplay = preg_match('/(^|\s)(hidden|flex|inline-flex|block|grid)(\s|$)/', (string) $attributes->get('class'));
    $idle = $tone === 'dark' ? 'text-white/80 hover:bg-white/10' : 'text-gray-700 hover:bg-gray-50';
@endphp

<div {{ $attributes->class(['inline-flex' => ! $hasDisplay, 'h-8 shrink-0 overflow-hidden rounded-md border text-xs font-semibold', $wrapper]) }}>
    @foreach ($options as $locale => $label)
        @if (app()->isLocale($locale))
            <span aria-current="true" @class([
                'flex items-center px-2.5',
                'bg-brand-700 text-white' => $tone !== 'dark',
                'bg-white text-brand-700' => $tone === 'dark',
                'font-arabic' => $locale === 'ar',
            ])>{{ $label }}</span>
        @else
            <form method="POST" action="{{ route('locale.update', $locale) }}" class="flex">
                @csrf
                @method('PATCH')
                <button type="submit" lang="{{ $locale }}" @class(['flex cursor-pointer items-center px-2.5 transition', $idle, 'font-arabic' => $locale === 'ar'])>
                    {{ $label }}
                </button>
            </form>
        @endif
    @endforeach
</div>
