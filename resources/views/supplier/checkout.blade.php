<x-layouts.supplier current="subscription" :title="__('subscription.checkout_title')">
    <div class="shell-page">
        <a href="{{ route('supplier.subscription') }}" wire:navigate
           class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-gray-500 hover:text-brand-700">
            <x-lucide name="arrow-left" :size="14" />
            {{ __('subscription.back_to_plans') }}
        </a>

        <h1 class="-mt-1 text-xl font-bold tracking-[-0.3px] text-brand-700 sm:text-2xl">{{ __('subscription.checkout_title') }}</h1>

        <form method="POST" action="{{ route('supplier.subscription.subscribe', $plan) }}" class="grid items-start gap-5 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            @csrf

            {{-- Payment method --}}
            <div class="card flex flex-col gap-3 p-4 sm:p-[18px]">
                <h2 class="card-title">{{ __('subscription.payment_method') }}</h2>

                <label class="flex cursor-pointer items-start gap-3 rounded-card border-2 border-brand-500 bg-brand-100 p-3.5">
                    <input type="radio" name="payment_method" value="cash_on_delivery" checked class="mt-1 size-4 accent-brand-700">
                    <span class="flex min-w-0 flex-col gap-1">
                        <span class="flex items-center gap-2 text-[15px] font-semibold text-gray-900">
                            <x-lucide name="truck" :size="18" class="text-brand-500" />
                            {{ __('subscription.cash_on_delivery') }}
                        </span>
                        <span class="text-[13px] leading-relaxed text-gray-600">{{ __('subscription.cash_on_delivery_body') }}</span>
                    </span>
                </label>
            </div>

            {{-- Order summary --}}
            <div class="card flex flex-col gap-3 p-4 sm:p-[18px]">
                <h2 class="card-title">{{ __('subscription.order_summary') }}</h2>

                <div class="flex flex-col gap-1">
                    <p class="text-[15px] font-semibold text-gray-900">{{ $plan->name }}</p>
                    <p class="text-xs text-gray-500 tabular">{{ __('subscription.per_period', ['days' => $plan->days]) }}</p>
                    @if ($plan->trial_days > 0 && $isFirstSubscription)
                        <p class="text-xs font-medium text-success-700">{{ __('subscription.trial_included', ['days' => $plan->trial_days]) }}</p>
                    @endif
                </div>

                <dl class="flex flex-col gap-2 border-t border-gray-100 pt-3 text-[13px]">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">{{ __('subscription.net') }}</dt>
                        <dd class="text-gray-900 tabular">{{ number_format((float) $plan->price_egp, 2) }} {{ __('common.egp') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">{{ __('subscription.vat') }}</dt>
                        <dd class="text-gray-900 tabular">{{ number_format($vat, 2) }} {{ __('common.egp') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-2 text-[15px] font-bold">
                        <dt class="text-gray-900">{{ __('subscription.total') }}</dt>
                        <dd class="text-brand-700 tabular">{{ number_format($total, 2) }} {{ __('common.egp') }}</dd>
                    </div>
                </dl>

                <button type="submit" class="btn btn-accent mt-1 h-11 w-full">{{ __('subscription.confirm_order') }}</button>
                <p class="text-xs text-gray-500">{{ __('subscription.checkout_note') }}</p>
            </div>
        </form>
    </div>
</x-layouts.supplier>
