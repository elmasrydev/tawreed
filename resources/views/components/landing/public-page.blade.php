@props(['title', 'categories' => collect()])

{{--
    Frame for public pages beyond the landing page: the same nav, with its
    section links pointing back to the landing page, and a slim footer.
--}}
@php
    $links = [
        route('splash').'#how' => __('landing.nav_how'),
        route('splash').'#buyers' => __('landing.nav_buyers'),
        route('splash').'#suppliers' => __('landing.nav_suppliers'),
        route('splash').'#faq' => __('landing.nav_faq'),
    ];
@endphp

<x-layouts.marketing :title="$title">
    <x-landing.nav :links="$links" :categories="$categories" />

    <main class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 pt-8 pb-16 sm:px-8 lg:px-16 lg:pt-10 lg:pb-24">
        {{ $slot }}
    </main>

    <footer class="mx-auto flex w-full max-w-7xl flex-col gap-3 border-t border-gray-200 px-4 pt-5 pb-10 text-[13px] text-gray-400 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-16">
        <span>{{ __('landing.footer_copyright', ['year' => now()->year]) }}</span>
        <x-language-toggle class="self-start sm:self-auto" />
    </footer>
</x-layouts.marketing>
