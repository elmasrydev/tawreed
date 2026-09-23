@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex flex-wrap items-center justify-between gap-3 text-[13px] text-gray-500">
        <p class="tabular">
            {{ __('common.showing_results', ['from' => $paginator->firstItem() ?? 0, 'to' => $paginator->lastItem() ?? 0, 'total' => $paginator->total()]) }}
        </p>

        <div class="flex items-center gap-1">
            @php($base = 'flex h-7 min-w-7 items-center justify-center rounded-md px-2 text-[13px] tabular')
            @if ($paginator->onFirstPage())
                <span class="{{ $base }} border border-gray-200 text-gray-300" aria-disabled="true">
                    <x-lucide name="chevron-left" :size="14" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" wire:navigate class="{{ $base }} border border-gray-300 text-gray-700 hover:bg-gray-50"
                   aria-label="{{ __('common.previous_page') }}">
                    <x-lucide name="chevron-left" :size="14" />
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $base }} text-gray-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $base }} bg-brand-700 font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" wire:navigate class="{{ $base }} hidden border border-gray-300 text-gray-700 hover:bg-gray-50 sm:flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" wire:navigate class="{{ $base }} border border-gray-300 text-gray-700 hover:bg-gray-50"
                   aria-label="{{ __('common.next_page') }}">
                    <x-lucide name="chevron-right" :size="14" />
                </a>
            @else
                <span class="{{ $base }} border border-gray-200 text-gray-300" aria-disabled="true">
                    <x-lucide name="chevron-right" :size="14" />
                </span>
            @endif
        </div>
    </nav>
@endif
