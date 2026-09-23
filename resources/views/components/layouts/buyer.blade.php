@props(['current' => null, 'title' => null])

@php
    $badges = App\Support\ShellState::buyerBadges(auth()->user());
    $profile = auth()->user()->buyerProfile;

    $nav = [
        ['key' => 'home', 'url' => route('buyer.home'), 'label' => __('common.nav_dashboard'), 'icon' => 'layout-grid'],
        ['key' => 'requests', 'url' => route('buyer.rfqs.index'), 'label' => __('common.nav_my_rfqs'), 'icon' => 'file-text',
         'badge' => $badges['rfqs'] ?: null, 'badgeTone' => 'muted'],
        ['key' => 'quotes', 'url' => route('buyer.quotes.index'), 'label' => __('common.nav_quotes'), 'icon' => 'tag',
         'badge' => $badges['quotes'] ?: null],
        ['key' => 'chats', 'url' => route('buyer.chats.index'), 'label' => __('common.nav_messages'), 'icon' => 'message-square',
         'badge' => $badges['messages'] ?: null],
        ['key' => 'account', 'url' => route('buyer.account'), 'label' => __('common.nav_settings'), 'icon' => 'settings'],
    ];
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :current="$current" :tabs="$nav"
                     :primary-action="['url' => route('buyer.rfqs.create'), 'label' => __('common.new_rfq')]"
                     :user-subtitle="$profile?->company_name">
    {{ $slot }}

    @unless (in_array($current, ['create', 'chats'], true))
        <a href="{{ route('buyer.rfqs.create') }}" wire:navigate
           class="fixed end-4 bottom-20 z-30 flex size-14 items-center justify-center rounded-full bg-accent-500 text-white shadow-toast transition hover:bg-accent-600 lg:hidden"
           aria-label="{{ __('common.new_rfq') }}">
            <x-lucide name="plus" :size="24" />
        </a>
    @endunless
</x-layouts.dashboard>
