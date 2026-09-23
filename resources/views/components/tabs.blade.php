@props(['items', 'current' => null])

{{--
    Underline tabs with counts. Each item: ['key', 'label', 'count' => ?int,
    'url' => ?string]. Items without a url dispatch nothing; pass wire:click
    through the `action` key for Livewire tabs.
--}}
<nav {{ $attributes->class('scrollbar-none -mx-4 flex gap-1 overflow-x-auto border-b border-gray-200 px-4 sm:mx-0 sm:px-0') }}>
    @foreach ($items as $item)
        @php($active = $item['key'] === $current)
        @php($tag = isset($item['url']) ? 'a' : 'button')
        <{{ $tag }}
            @isset($item['url']) href="{{ $item['url'] }}" wire:navigate @else type="button" @endisset
            @isset($item['action']) wire:click="{{ $item['action'] }}" @endisset
            @if ($active) aria-current="page" @endif
            @class([
                '-mb-px flex shrink-0 items-center gap-1.5 border-b-2 px-3.5 py-2.5 text-sm whitespace-nowrap transition-colors',
                'border-brand-500 font-semibold text-brand-700' => $active,
                'border-transparent font-medium text-gray-500 hover:text-brand-700' => ! $active,
            ])>
            {{ $item['label'] }}
            @if (isset($item['count']))
                <span class="font-medium text-gray-500 tabular">{{ $item['count'] }}</span>
            @endif
        </{{ $tag }}>
    @endforeach
</nav>
