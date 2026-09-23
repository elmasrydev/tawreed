@props([
    'title' => null,
    'nav' => [],
    'current' => null,
    'tabs' => [],
    'primaryAction' => null,
    'userSubtitle' => null,
])

{{--
    Buyer and supplier app shell (design option 2a). Desktop: fixed navy
    sidebar and a white top bar. Below 1024px: compact top bar with a menu
    button, an off-canvas drawer and a bottom tab bar.
--}}
<x-layouts.app :title="$title">
    <div x-data="{ drawer: false }" x-on:keydown.escape.window="drawer = false" class="min-h-dvh lg:grid lg:grid-cols-[232px_minmax(0,1fr)]">
        <aside class="sticky top-0 hidden h-dvh overflow-y-auto lg:block">
            <x-shell.sidebar :nav="$nav" :current="$current" :primary-action="$primaryAction">
                @isset($userSubtitle) <x-slot:subtitle>{{ $userSubtitle }}</x-slot:subtitle> @endisset
                {{ $sidebarFooter ?? '' }}
            </x-shell.sidebar>
        </aside>

        {{-- Mobile drawer --}}
        <div x-show="drawer" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
            <div x-show="drawer" x-transition.opacity class="absolute inset-0 bg-gray-900/45" x-on:click="drawer = false"></div>
            <div x-show="drawer"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full rtl:translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full rtl:translate-x-full"
                 x-trap.noscroll="drawer"
                 class="absolute inset-y-0 start-0 w-[280px] max-w-[85vw] overflow-y-auto">
                <button type="button" x-on:click="drawer = false" class="absolute end-2 top-4 z-10 flex size-9 cursor-pointer items-center justify-center rounded-md text-white/80 hover:bg-white/10"
                        aria-label="{{ __('common.close_menu') }}">
                    <x-lucide name="x" :size="18" />
                </button>
                <x-shell.sidebar :nav="$nav" :current="$current" :primary-action="$primaryAction">
                    @isset($userSubtitle) <x-slot:subtitle>{{ $userSubtitle }}</x-slot:subtitle> @endisset
                    {{ $sidebarFooter ?? '' }}
                </x-shell.sidebar>
            </div>
        </div>

        <div class="flex min-h-dvh min-w-0 flex-col">
            <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-gray-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:h-[60px]">
                <button type="button" x-on:click="drawer = true" class="-ms-1.5 flex size-9 cursor-pointer items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 lg:hidden"
                        aria-label="{{ __('common.open_menu') }}">
                    <x-lucide name="menu" :size="20" />
                </button>
                <a href="{{ route(auth()->user()->role->homeRoute()) }}" wire:navigate class="lg:hidden">
                    <x-brand-logo class="h-7" />
                </a>

                <div class="min-w-0 flex-1">{{ $topbar ?? '' }}</div>

                <x-language-toggle />
            </header>

            {{ $banner ?? '' }}

            <main class="flex-1 pb-20 lg:pb-0">
                {{ $slot }}
            </main>
        </div>

        @if ($tabs)
            <nav class="fixed inset-x-0 bottom-0 z-40 grid border-t border-gray-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden"
                 style="grid-template-columns: repeat({{ count($tabs) }}, minmax(0, 1fr))"
                 aria-label="{{ __('common.main_navigation') }}">
                @foreach ($tabs as $tab)
                    @php($active = in_array($current, (array) ($tab['matches'] ?? $tab['key']), true))
                    <a href="{{ $tab['url'] }}" wire:navigate @if ($active) aria-current="page" @endif
                       @class(['relative flex flex-col items-center gap-1 py-2 text-[10.5px] font-medium', 'text-brand-700' => $active, 'text-gray-500' => ! $active])>
                        <x-lucide :name="$tab['icon']" :size="20" :stroke="$active ? 2.2 : 1.8" />
                        <span class="max-w-full truncate px-1">{{ $tab['label'] }}</span>
                        @if (! empty($tab['badge']))
                            <span class="absolute top-1 start-[calc(50%+6px)] min-w-4 rounded-full bg-accent-500 px-1 text-center text-[10px] leading-4 font-bold text-white tabular">{{ $tab['badge'] }}</span>
                        @endif
                        @if ($active)
                            <span class="absolute inset-x-5 top-0 h-0.5 rounded-b bg-brand-500"></span>
                        @endif
                    </a>
                @endforeach
            </nav>
        @endif
    </div>
</x-layouts.app>
