<x-layouts.supplier current="dashboard" :title="__('common.nav_dashboard')">
    @php
        $verification = $account['verification'];
        $subscription = $account['subscription'];
        $firstName = Illuminate\Support\Str::before(trim($supplier->name), ' ');
        $greeting = match (true) {
            now()->hour < 12 => 'supplier.greeting_morning',
            now()->hour < 17 => 'supplier.greeting_afternoon',
            default => 'supplier.greeting_evening',
        };
        $planTone = match ($account['subscriptionState']) {
            'expiring' => 'warning',
            'trial' => 'info',
            'none' => 'danger',
            default => 'success',
        };
    @endphp

    <div class="shell-page">
        <div>
            <h1 class="text-xl font-bold tracking-[-0.3px] text-brand-700 sm:text-2xl">{{ __($greeting, ['name' => $firstName]) }}</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                {{ now()->translatedFormat('l j F Y') }}
                ·
                {{ trans_choice('supplier.dashboard_matching', $matchingCount, ['count' => $matchingCount]) }}
            </p>
        </div>

        {{-- Verification and plan status --}}
        <div class="grid gap-4 md:grid-cols-2">
            <div class="card flex flex-wrap items-center gap-4 px-5 py-4 sm:flex-nowrap">
                <span @class([
                    'flex size-12 shrink-0 items-center justify-center rounded-xl',
                    'bg-success-50 text-success-500' => $verification->allowsQuoting(),
                    'bg-danger-50 text-danger-600' => in_array($verification, [App\Enums\VerificationStatus::Rejected, App\Enums\VerificationStatus::Suspended], true),
                    'bg-gray-100 text-gray-400' => $verification === App\Enums\VerificationStatus::Pending,
                ])>
                    <x-lucide name="shield-check" :size="24" />
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[15px] font-semibold text-gray-900">{{ __('ui.verification') }}</span>
                        <x-status-badge :status="$verification" size="sm" />
                    </div>
                    <p class="mt-1 text-[13px] leading-relaxed text-gray-500">{{ $verification->hint() }}</p>
                </div>
                <a href="{{ route('supplier.account', ['tab' => 'documents']) }}" wire:navigate class="link text-[13px] whitespace-nowrap">
                    {{ __('supplier.banner_view_documents') }}
                </a>
            </div>

            <div class="card flex flex-wrap items-center gap-4 px-5 py-4 sm:flex-nowrap">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-500">
                    <x-lucide name="credit-card" :size="24" />
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[15px] font-semibold text-gray-900">{{ $subscription?->plan->name ?? __('common.no_plan') }}</span>
                        @if ($subscription)
                            <x-badge :tone="$planTone" size="sm">
                                {{ $account['subscriptionState'] === 'expiring' ? __('supplier.plan_expiring') : $subscription->status->label() }}
                            </x-badge>
                        @endif
                    </div>
                    <p class="mt-1 text-[13px] text-gray-500 tabular">
                        @if ($subscription)
                            {{ $subscription->starts_at->translatedFormat('j M') }} → {{ $subscription->ends_at->translatedFormat('j M Y') }}
                            · {{ trans_choice('common.days_left', $account['daysLeft'], ['count' => $account['daysLeft']]) }}
                        @else
                            {{ __('subscription.no_active_body') }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('supplier.subscription') }}" wire:navigate class="btn btn-accent btn-sm">
                    {{ $subscription ? __('supplier.renew') : __('subscription.subscribe') }}
                </a>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <x-kpi-card :label="__('supplier.kpi_matching')" :value="number_format($matchingCount)"
                        :delta="trans_choice('supplier.kpi_new_today', $matchingToday, ['count' => $matchingToday])"
                        :delta-tone="$matchingToday > 0 ? 'success' : 'muted'"
                        icon="file-text" icon-tone="info" :href="route('supplier.feed')" />

            <x-kpi-card :label="__('supplier.kpi_submitted')" :value="number_format($stats['submittedThisMonth'])"
                        :delta="trans_choice('supplier.kpi_submitted_delta', $stats['active'], ['count' => $stats['active']])"
                        icon="tag" icon-tone="accent" :href="route('supplier.quotes.index')" />

            <x-kpi-card :label="__('supplier.kpi_selected')" :value="number_format($stats['selected'])"
                        :delta="__('supplier.kpi_won_value', ['amount' => number_format($stats['wonValue'])])"
                        icon="award" icon-tone="success" :href="route('supplier.quotes.index', ['status' => 'selected'])" />

            <x-kpi-card :label="__('supplier.kpi_win_rate')" :value="$stats['winRate'] !== null ? $stats['winRate'].'%' : '—'"
                        :delta="$stats['decided'] > 0
                            ? trans_choice('supplier.kpi_win_rate_delta', $stats['decided'], ['selected' => $stats['selected'], 'count' => $stats['decided']])
                            : __('supplier.kpi_win_rate_empty')"
                        icon="bar-chart" icon-tone="neutral" />
        </div>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
            {{-- New matching requests --}}
            <section class="flex min-w-0 flex-col gap-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="card-title">
                        <x-lucide name="file-text" :size="16" class="text-brand-500" />
                        {{ __('supplier.new_rfqs_title') }}
                    </h2>
                    <a href="{{ route('supplier.feed') }}" wire:navigate class="link text-[13px]">{{ __('supplier.browse_all') }}</a>
                </div>

                @forelse ($newRfqs as $item)
                    <x-supplier.rfq-card :item="$item" />
                @empty
                    <x-empty-state icon="search" :title="__('supplier.no_matching_title')" :body="__('supplier.no_matching_body')">
                        <a href="{{ route('supplier.feed') }}" wire:navigate class="btn btn-secondary btn-sm">{{ __('supplier.browse_all') }}</a>
                    </x-empty-state>
                @endforelse
            </section>

            {{-- Recent quotes --}}
            <section class="flex min-w-0 flex-col gap-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="card-title">
                        <x-lucide name="tag" :size="16" class="text-accent-500" />
                        {{ __('supplier.recent_quotes') }}
                    </h2>
                    <a href="{{ route('supplier.quotes.index') }}" wire:navigate class="link text-[13px]">{{ __('supplier.all_quotes') }}</a>
                </div>

                @if ($recentQuotes->isEmpty())
                    <x-empty-state icon="tag" :title="__('quote.no_quotes')" :body="__('quote.no_quotes_body')" />
                @else
                    <div class="card overflow-hidden">
                        @foreach ($recentQuotes as ['quote' => $quote, 'rfq' => $rfq])
                            <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3 text-[13px] last:border-b-0">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-gray-900">{{ $rfq->title }}</p>
                                    <p class="truncate text-xs text-gray-500 tabular">
                                        {{ $rfq->buyerLabel() }} ·
                                        <span class="whitespace-nowrap">{{ number_format($quote->grandTotal()) }} {{ __('common.egp') }}</span>
                                    </p>
                                </div>
                                <x-status-badge :status="$quote->status" :quote="$quote" size="sm" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.supplier>
