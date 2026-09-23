@props(['links' => []])

{{--
    Sticky marketing nav. From 1024px up the anchor links sit in the bar; below
    that they move into a sheet opened by the menu button.
--}}
<nav x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false"
     class="sticky top-0 z-30 border-b border-gray-200 bg-white/92 backdrop-blur-md">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-8 lg:h-[72px] lg:px-16">
        <a href="{{ route('splash') }}" class="shrink-0">
            <x-brand-logo class="h-9 lg:h-10" />
        </a>

        <div class="hidden items-center gap-8 text-sm font-medium whitespace-nowrap text-gray-700 lg:flex">
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
