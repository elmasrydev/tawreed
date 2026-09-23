@props(['current' => null, 'title' => null])

@php
    $supplier = auth()->user();
    $badges = App\Support\ShellState::supplierBadges($supplier);
    $account = App\Support\ShellState::supplierAccount($supplier);

    $nav = [
        ['key' => 'dashboard', 'url' => route('supplier.dashboard'), 'label' => __('common.nav_dashboard'), 'icon' => 'layout-grid'],
        ['key' => 'feed', 'url' => route('supplier.feed'), 'label' => __('common.nav_browse_rfqs'), 'icon' => 'search'],
        ['key' => 'quotes', 'url' => route('supplier.quotes.index'), 'label' => __('common.nav_my_quotes'), 'icon' => 'tag',
         'badge' => $badges['quotes'] ?: null, 'badgeTone' => 'muted'],
        ['key' => 'chats', 'url' => route('supplier.chats.index'), 'label' => __('common.nav_messages'), 'icon' => 'message-square',
         'badge' => $badges['messages'] ?: null],
        ['key' => 'account', 'url' => route('supplier.account'), 'label' => __('common.nav_profile'), 'icon' => 'building'],
        ['key' => 'subscription', 'url' => route('supplier.subscription'), 'label' => __('common.nav_subscription'), 'icon' => 'credit-card'],
    ];

    $tabs = array_values(array_filter($nav, fn (array $item): bool => $item['key'] !== 'account'));

    $planTone = match ($account['subscriptionState']) {
        'expiring' => 'warning-solid',
        'none' => 'danger-solid',
        'trial' => 'info',
        default => 'success-solid',
    };
    $barColor = match ($account['subscriptionState']) {
        'expiring' => 'bg-accent-500',
        'none' => 'bg-danger-600',
        default => 'bg-success-500',
    };
@endphp

<x-layouts.dashboard :title="$title" :nav="$nav" :current="$current" :tabs="$tabs"
                     :user-subtitle="$supplier->supplierProfile?->company_name">
    <x-slot:sidebarFooter>
        <a href="{{ route('supplier.subscription') }}" wire:navigate class="flex flex-col gap-2 rounded-lg bg-white/8 p-3 transition hover:bg-white/12">
            <span class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-semibold tracking-[0.6px] text-white/70 uppercase">{{ __('common.plan') }}</span>
                @if ($account['subscription'])
                    <x-badge :tone="$planTone" size="sm">
                        {{ $account['subscriptionState'] === 'expiring' ? __('enums.subscription_status.active') : $account['subscription']->status->label() }}
                    </x-badge>
                @endif
            </span>
            <span class="text-[13px] font-semibold">{{ $account['subscription']?->plan->name ?? __('common.no_plan') }}</span>
            <span class="h-[5px] overflow-hidden rounded-full bg-white/20">
                <span class="block h-full rounded-full {{ $barColor }}" style="width: {{ $account['progress'] }}%"></span>
            </span>
            @if ($account['subscription'])
                <span class="text-[11px] text-white/70">{{ trans_choice('common.days_left', $account['daysLeft'], ['count' => $account['daysLeft']]) }}</span>
            @endif
        </a>
    </x-slot:sidebarFooter>

    @if ($account['banner'])
        <x-slot:banner>
            @php($banner = $account['banner'])
            <div @class([
                'flex flex-wrap items-center gap-x-3 gap-y-2 border-b px-4 py-2.5 text-[13px] sm:px-6 lg:px-7',
                'border-warning-200 bg-warning-100 text-warning-800' => $banner['tone'] === 'warning',
                'border-danger-200 bg-danger-100 text-danger-800' => $banner['tone'] === 'danger',
                'border-gray-200 bg-gray-100 text-gray-700' => $banner['tone'] === 'neutral',
            ])>
                <x-lucide :name="$banner['icon']" :size="16" />
                <p class="min-w-0 flex-1"><span class="font-semibold">{{ $banner['title'] }}</span> {{ $banner['body'] }}</p>
                <a href="{{ $banner['url'] }}" wire:navigate class="btn btn-accent btn-xs">{{ $banner['action'] }}</a>
            </div>
        </x-slot:banner>
    @endif

    {{ $slot }}
</x-layouts.dashboard>
