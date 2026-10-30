@props(['links' => [], 'categories' => collect()])

{{--
    Sticky marketing nav. From 1024px up the anchor links sit in the bar; below
    that they move into a sheet opened by the menu button. The categories menu
    lists the busiest ones, then a link to all of them.
--}}
<nav x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false"
     class="sticky top-0 z-30 border-b border-gray-200 bg-white/92 backdrop-blur-md">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-8 lg:h-[72px] lg:px-16">
        <a href="{{ route('splash') }}" class="shrink-0">
            <x-brand-logo class="h-9 lg:h-10" />
        </a>

        <div class="hidden items-center gap-8 text-sm font-medium whitespace-nowrap text-gray-700 lg:flex">
            @if ($categories->isNotEmpty())
                <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" class="inline-flex items-center gap-1 transition hover:text-brand-700" @click="open = ! open"
                            :aria-expanded="open.toString()" aria-controls="landing-categories-menu" data-testid="nav-categories">
                        {{ __('landing.nav_categories') }}
                        <x-lucide name="chevron-down" :size="14" ::class="open && 'rotate-180'" class="transition" />
                    </button>
                    <div id="landing-categories-menu" x-show="open" x-transition.opacity style="display: none"
                         class="absolute start-0 top-full z-40 mt-3 flex w-64 flex-col rounded-xl border border-gray-200 bg-white py-1.5 shadow-dropdown">
                        @foreach ($categories as $category)
                            <a href="{{ route('rfqs.index', ['category' => $category->slug]) }}"
                               class="flex items-center justify-between gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 hover:text-brand-700">
                                <span class="truncate">{{ $category->name }}</span>
                                <span class="text-xs text-gray-400 tabular">{{ $category->open_rfqs_count }}</span>
                            </a>
                        @endforeach
                        <a href="{{ route('categories.index') }}"
                           class="mt-1.5 flex items-center justify-between gap-3 border-t border-gray-100 px-4 pt-3 pb-2 font-semibold text-brand-700 hover:text-brand-500">
                            {{ __('landing.nav_all_categories') }}
                            <x-lucide name="arrow-right" :size="14" />
                        </a>
                    </div>
                </div>
            @endif
            @foreach ($links as $href => $label)
                <a href="{{ $href }}" class="transition hover:text-brand-700">{{ $label }}</a>
            @endforeach
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <x-language-toggle class="hidden lg:inline-flex" />
            <a href="{{ route('login') }}" class="hidden px-2 text-sm font-semibold whitespace-nowrap text-brand-700 hover:text-brand-500 sm:inline">
                {{ __('landing.log_in') }}
            </a>
            <a href="{{ route('register') }}" class="btn btn-accent h-9 px-3.5 sm:h-10 sm:px-[18px]">
                {{ __('landing.get_started') }}
            </a>
            <button type="button" class="btn btn-secondary btn-icon lg:hidden" @click="menuOpen = ! menuOpen"
                    :aria-expanded="menuOpen.toString()" aria-controls="landing-menu"
                    :aria-label="menuOpen ? @js(__('common.close_menu')) : @js(__('common.open_menu'))">
                <x-lucide name="menu" :size="18" x-show="! menuOpen" />
                <x-lucide name="x" :size="18" x-show="menuOpen" style="display: none" />
            </button>
        </div>
    </div>

    <div id="landing-menu" x-show="menuOpen" x-transition.opacity style="display: none"
         class="absolute inset-x-0 top-full border-b border-gray-200 bg-white shadow-dropdown lg:hidden">
        <div class="mx-auto flex max-w-7xl flex-col px-4 py-3 sm:px-8">
            @if ($categories->isNotEmpty())
                <div x-data="{ open: false }" class="border-b border-gray-100">
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                            class="flex h-11 w-full items-center justify-between text-[15px] font-medium text-gray-700 hover:text-brand-700">
                        {{ __('landing.nav_categories') }}
                        <x-lucide name="chevron-down" :size="16" ::class="open && 'rotate-180'" class="transition" />
                    </button>
                    <div x-show="open" x-collapse style="display: none" class="flex flex-col pb-2">
                        @foreach ($categories as $category)
                            <a href="{{ route('rfqs.index', ['category' => $category->slug]) }}"
                               class="flex h-10 items-center justify-between gap-3 ps-3 text-sm text-gray-600 hover:text-brand-700">
                                <span class="truncate">{{ $category->name }}</span>
                                <span class="text-xs text-gray-400 tabular">{{ $category->open_rfqs_count }}</span>
                            </a>
                        @endforeach
                        <a href="{{ route('categories.index') }}" class="flex h-10 items-center ps-3 text-sm font-semibold text-brand-700">
                            {{ __('landing.nav_all_categories') }}
                        </a>
                    </div>
                </div>
            @endif
            @foreach ($links as $href => $label)
                <a href="{{ $href }}" @click="menuOpen = false"
                   class="flex h-11 items-center border-b border-gray-100 text-[15px] font-medium text-gray-700 hover:text-brand-700">
                    {{ $label }}
                </a>
            @endforeach
            <div class="flex items-center justify-between gap-3 pt-3 pb-1">
                <x-language-toggle />
                <a href="{{ route('login') }}" class="btn btn-secondary">{{ __('landing.log_in') }}</a>
            </div>
        </div>
    </div>
</nav>
