<x-layouts.supplier current="subscription" :title="__('subscription.page_title')">
    @php
        $state = $account['subscriptionState'];
        $badgeTone = match ($state) {
            'expiring' => 'warning-solid',
            'trial' => 'info',
            default => 'success-solid',
        };
        $barColor = $state === 'expiring' ? 'bg-accent-500' : 'bg-success-500';
    @endphp

    <div class="shell-page">
        <h1 class="text-xl font-bold tracking-[-0.3px] text-brand-700 sm:text-2xl">{{ __('subscription.page_title') }}</h1>

        <div class="grid items-start gap-5 lg:grid-cols-2">
            <div class="flex min-w-0 flex-col gap-4">
                @if ($subscription)
                    {{-- Current plan --}}
                    <div class="flex flex-col gap-3 rounded-xl bg-brand-700 p-5 text-white sm:p-[22px]">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold tracking-[0.6px] uppercase opacity-75">{{ __('subscription.current_plan') }}</span>
                            <x-badge :tone="$badgeTone" size="sm">
                                {{ $state === 'expiring' ? __('supplier.plan_expiring') : $subscription->status->label() }}
                            </x-badge>
                        </div>
                        <p class="text-2xl font-bold sm:text-[26px]">
                            {{ $subscription->plan->name }}
                            <span class="text-base font-medium opacity-80">· {{ number_format((float) $subscription->amount_egp) }} {{ __('common.egp') }}</span>
                        </p>
                        <p class="text-[13px] opacity-80 tabular">
                            {{ $subscription->starts_at->translatedFormat('j M Y') }} → {{ $subscription->ends_at->translatedFormat('j M Y') }}
                        </p>
                        <div class="h-1.5 overflow-hidden rounded-full bg-white/20">
                            <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $account['progress'] }}%"></div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 text-[13px]">
                            <span class="tabular">{{ trans_choice('common.days_left', $account['daysLeft'], ['count' => $account['daysLeft']]) }}</span>

                            <form method="POST" action="{{ route('supplier.subscription.auto-renew') }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" role="switch" aria-checked="{{ $subscription->auto_renew ? 'true' : 'false' }}"
                                        class="flex min-h-9 cursor-pointer items-center gap-2">
                                    <span>{{ __('subscription.auto_renew') }}</span>
                                    <span @class(['relative h-5 w-[34px] rounded-full transition-colors', 'bg-success-500' => $subscription->auto_renew, 'bg-white/30' => ! $subscription->auto_renew])>
                                        <span @class(['absolute top-0.5 size-4 rounded-full bg-white shadow-sm transition-all', 'start-4' => $subscription->auto_renew, 'start-0.5' => ! $subscription->auto_renew])></span>
                                    </span>
                                </button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('supplier.subscription.subscribe', $subscription->plan) }}" class="mt-1">
                            @csrf
                            <button type="submit" class="btn btn-accent h-10 w-full">{{ __('subscription.renew_now') }}</button>
                        </form>
                        <p class="text-xs opacity-70">{{ __('subscription.renew_extends_note') }}</p>
                    </div>
                @else
                    <div class="card flex flex-col gap-2 border-danger-200 bg-danger-25 p-5">
                        <p class="flex items-center gap-2 text-[15px] font-semibold text-danger-800">
                            <x-lucide name="alert-circle" :size="18" />
                            {{ __('subscription.no_active_title') }}
                        </p>
                        <p class="text-[13px] leading-relaxed text-gray-700">{{ __('subscription.no_active_body') }}</p>
                        @if ($lastSubscription)
                            <p class="text-xs text-gray-500 tabular">
                                {{ __('subscription.last_plan_ended', ['plan' => $lastSubscription->plan->name, 'date' => $lastSubscription->ends_at->translatedFormat('j M Y')]) }}
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Plans --}}
                <div class="card flex flex-col gap-3 p-4 sm:p-[18px]">
                    <h2 class="card-title">{{ $subscription ? __('subscription.change_plan') : __('subscription.choose_plan') }}</h2>

                    <div class="grid gap-3 pt-2 sm:grid-cols-3">
                        @foreach ($plans as $plan)
                            @php($isCurrent = $subscription?->plan_id === $plan->id)
                            <div @class([
                                'relative flex flex-col gap-1.5 rounded-card border-2 p-3.5',
                                'border-brand-500 bg-brand-100' => $isCurrent,
                                'border-success-500 bg-white' => ! $isCurrent && $plan->is_best_value,
                                'border-gray-200 bg-white' => ! $isCurrent && ! $plan->is_best_value,
                            ])>
                                @if ($plan->is_best_value)
                                    <span class="absolute -top-2.5 end-2.5 rounded bg-success-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ __('subscription.best_value') }}</span>
                                @endif
                                @if ($isCurrent)
                                    <x-badge tone="info" size="sm" icon="check" class="absolute -top-3 start-2.5 border border-brand-200">{{ __('subscription.current') }}</x-badge>
                                @endif
                                <p class="text-[13px] text-gray-500">{{ $plan->name }}</p>
                                <p class="text-xl font-bold text-brand-700 tabular">
                                    {{ number_format((float) $plan->price_egp) }} <span class="text-xs font-semibold text-gray-500">{{ __('common.egp') }}</span>
                                </p>
                                <p class="text-[11px] text-gray-400 tabular">
                                    {{ __('subscription.per_day', ['amount' => number_format((float) $plan->price_egp / max(1, $plan->days), 1)]) }}
                                </p>
                                @if ($plan->description)
                                    <p class="text-xs leading-relaxed text-gray-600">{{ $plan->description }}</p>
                                @endif
                                @if ($plan->trial_days > 0 && $isFirstSubscription)
                                    <p class="text-xs font-medium text-success-700">{{ __('subscription.trial_included', ['days' => $plan->trial_days]) }}</p>
                                @endif
                                <form method="POST" action="{{ route('supplier.subscription.subscribe', $plan) }}" class="mt-auto pt-2">
                                    @csrf
                                    <button type="submit" @class(['btn btn-sm w-full max-md:h-10', 'btn-primary' => $plan->is_best_value && ! $isCurrent, 'btn-secondary' => ! $plan->is_best_value || $isCurrent])>
                                        {{ $isCurrent ? __('subscription.renew') : __('subscription.subscribe') }}
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    <p class="text-xs text-gray-500">{{ __('subscription.paused_note') }}</p>
                </div>
            </div>

            {{-- Invoices --}}
            <div class="card min-w-0 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3.5 sm:px-[18px]">
                    <h2 class="card-title">{{ __('subscription.invoices') }}</h2>
                    <span class="text-xs text-gray-500">{{ __('subscription.invoices_vat_note') }}</span>
                </div>

                @forelse ($invoices as $invoice)
                    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3.5 gap-y-1 border-b border-gray-100 px-4 py-3 text-[13px] last:border-b-0 sm:grid-cols-[1fr_1.4fr_1fr_auto_auto] sm:px-[18px]">
                        <div class="min-w-0 sm:contents">
                            <span class="block text-gray-500 tabular">{{ $invoice->issued_at->translatedFormat('j M Y') }}</span>
                            <span class="block min-w-0 truncate font-medium text-gray-900">
                                {{ $invoice->subscription?->plan->name }}
                                <span class="block text-xs font-normal text-gray-500"><span dir="ltr" class="inline-block tabular">{{ $invoice->number }}</span></span>
                            </span>
                        </div>
                        <span class="text-end font-semibold text-gray-900 tabular">{{ number_format((float) $invoice->total_egp, 2) }} {{ __('common.egp') }}</span>
                        <span class="max-sm:hidden"><x-badge tone="success" size="sm">{{ __('subscription.paid') }}</x-badge></span>
                        <a href="{{ route('supplier.invoices.show', $invoice) }}" class="link col-start-2 inline-flex min-h-9 items-center justify-end gap-1 text-xs sm:col-start-auto">
                            <x-lucide name="download" :size="12" />
                            PDF
                        </a>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-[13px] text-gray-500">{{ __('subscription.no_invoices') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.supplier>
