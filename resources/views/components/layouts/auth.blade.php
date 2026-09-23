@props(['title' => null, 'context' => 'login', 'switchTo' => null])

{{--
    Split-screen auth layout. The navy brand panel (480px) shows from 1024px
    up; smaller screens get a compact navy header and a full-width form.
    Context switches the panel copy: login, buyer or supplier. `switchTo`
    picks the account link (register or login); it defaults from the context.
--}}
@php
    $panel = [
        'tag' => __("auth_ui.panel_{$context}_tag"),
        'title' => __("auth_ui.panel_{$context}_title"),
        'points' => [
            __("auth_ui.panel_{$context}_point_1"),
            __("auth_ui.panel_{$context}_point_2"),
            __("auth_ui.panel_{$context}_point_3"),
        ],
    ];
    $isGuest = auth()->guest();
    $switchTo ??= $context === 'login' ? 'register' : 'login';
@endphp

<x-layouts.app :title="$title">
    <div class="min-h-dvh lg:grid lg:grid-cols-[480px_minmax(0,1fr)]">
        <aside class="relative hidden overflow-hidden bg-brand-700 px-11 py-10 text-white lg:flex lg:flex-col">
            <span class="pointer-events-none absolute -end-[120px] -bottom-[160px] size-[420px] rounded-full bg-brand-500/35"></span>
            <span class="pointer-events-none absolute end-[60px] bottom-[120px] size-[220px] rounded-full bg-accent-500/18"></span>

            <a href="{{ route('splash') }}" class="relative self-start">
                <x-brand-logo variant="white" class="h-10" />
            </a>

            <div class="relative mt-auto flex flex-col gap-6">
                <span class="inline-flex h-7 items-center self-start rounded-full bg-white/12 px-3 text-xs font-semibold">{{ $panel['tag'] }}</span>
                <h2 class="text-[34px] leading-tight font-extrabold tracking-[-0.5px] text-balance">{{ $panel['title'] }}</h2>
                <ul class="flex flex-col gap-3">
                    @foreach ($panel['points'] as $point)
                        <li class="flex items-center gap-3 text-[15px] text-white/88">
                            <span class="flex size-[22px] shrink-0 items-center justify-center rounded-full bg-accent-500">
                                <x-lucide name="check" :size="13" :stroke="3" />
                            </span>
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>

        <div class="flex min-h-dvh flex-col bg-white">
            <header class="flex items-center justify-between gap-3 bg-brand-700 px-4 py-3 lg:hidden">
                <a href="{{ route('splash') }}"><x-brand-logo variant="white" class="h-8" /></a>
                <x-language-toggle tone="dark" />
            </header>

            <div class="hidden items-center justify-end gap-4 px-12 pt-5 lg:flex">
                @if ($isGuest)
                    <p class="text-[13px] text-gray-500">
                        @if ($switchTo === 'register')
                            {{ __('ui.no_account') }}
                            <a href="{{ route('register') }}" class="link">{{ __('ui.create_one') }}</a>
                        @else
                            {{ __('ui.have_account') }}
                            <a href="{{ route('login') }}" class="link">{{ __('ui.sign_in') }}</a>
                        @endif
                    </p>
                @endif
                <x-language-toggle />
            </div>

            <main class="flex flex-1 justify-center px-4 py-8 sm:px-8 lg:items-center lg:px-12 lg:py-6">
                <div class="flex w-full max-w-[560px] flex-col gap-6">
                    {{ $slot }}
                </div>
            </main>

            <footer class="flex flex-wrap items-center justify-between gap-2 px-4 pb-6 text-xs text-gray-400 sm:px-8 lg:px-12">
                <span>© {{ now()->year }} {{ __('ui.app_name') }} · {{ __('auth_ui.footer_location') }}</span>
                @if ($isGuest)
                    <span class="lg:hidden">
                        @if ($switchTo === 'register')
                            <a href="{{ route('register') }}" class="link">{{ __('ui.create_one') }}</a>
                        @else
                            <a href="{{ route('login') }}" class="link">{{ __('ui.sign_in') }}</a>
                        @endif
                    </span>
                @endif
            </footer>
        </div>
    </div>
</x-layouts.app>
