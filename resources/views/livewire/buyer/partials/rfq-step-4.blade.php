@php
    $review = $this->reviewValues();
    $buyerProfile = auth()->user()->buyerProfile;
    $quantity = filled($quantity) ? rtrim(rtrim(number_format((float) $quantity, 3), '0'), '.') : null;
    $supplyLabel = App\Enums\SupplyType::tryFrom($supply_type)?->label();
    $fileNames = $this->savedAttachments->pluck('file_name')->merge(collect($attachments)->map(fn ($file) => $file->getClientOriginalName()));
@endphp

<div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
    <section class="card flex flex-col gap-4 p-4 text-sm sm:p-6">
        <h2 class="card-title">{{ __('buyer.review') }}</h2>

        <dl class="grid gap-x-4 gap-y-3 text-gray-700 sm:grid-cols-[160px_minmax(0,1fr)]">
            <dt class="text-gray-500">{{ __('buyer.step_product') }}</dt>
            <dd class="font-medium break-words text-gray-900">{{ $title }}</dd>

            <dt class="text-gray-500">{{ __('buyer.col_category') }}</dt>
            <dd>{{ $review['category'] ?? '—' }}</dd>

            <dt class="text-gray-500">{{ __('ui.quantity') }}</dt>
            <dd class="tabular">
                <span dir="ltr">{{ $quantity }}</span> {{ $review['unit'] }}
                @if ($supplyLabel) · {{ $supplyLabel }}@endif
                @if ($supply_type === App\Enums\SupplyType::Recurring->value && $recurrence_note) ({{ $recurrence_note }})@endif
            </dd>

            <dt class="text-gray-500">{{ __('buyer.col_delivery') }}</dt>
            <dd class="tabular">
                {{ __('buyer.by_date', ['date' => $delivery_date ? Illuminate\Support\Carbon::parse($delivery_date)->translatedFormat('j M Y') : '—']) }}
                · {{ $review['governorate'] }}
            </dd>

            <dt class="text-gray-500">{{ __('buyer.col_deadline') }}</dt>
            <dd class="tabular">{{ $review['deadline']?->translatedFormat('j M Y') ?? '—' }}</dd>

            <dt class="text-gray-500">{{ __('ui.specs') }}</dt>
            <dd class="whitespace-pre-line">{{ $specs }}</dd>

            @if ($notes)
                <dt class="text-gray-500">{{ __('ui.notes') }}</dt>
                <dd class="whitespace-pre-line">{{ $notes }}</dd>
            @endif

            <dt class="text-gray-500">{{ __('ui.attachments') }}</dt>
            <dd class="break-words">{{ $fileNames->isNotEmpty() ? $fileNames->implode(app()->isLocale('ar') ? '، ' : ', ') : '—' }}</dd>
        </dl>

        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-gray-100 pt-3">
            <button type="button" wire:click="goToStep(1)" class="link text-[13px]">{{ __('buyer.edit_product') }}</button>
            <span aria-hidden="true" class="text-gray-300">·</span>
            <button type="button" wire:click="goToStep(2)" class="link text-[13px]">{{ __('buyer.edit_quantity') }}</button>
            <span aria-hidden="true" class="text-gray-300">·</span>
            <button type="button" wire:click="goToStep(3)" class="link text-[13px]">{{ __('buyer.edit_terms') }}</button>
        </div>
    </section>

    <div class="flex flex-col gap-2.5">
        <p class="eyebrow text-xs">{{ __('buyer.what_suppliers_will_see') }}</p>

        <div class="flex flex-col gap-3.5 rounded-card border border-gray-300 bg-white p-4 sm:p-[18px]" aria-label="{{ __('buyer.what_suppliers_will_see') }}">
            <div class="flex items-center justify-between gap-2">
                <span class="inline-flex min-w-0 items-center gap-1.5 text-xs font-semibold text-gray-600">
                    <x-lucide name="lock" :size="12" />
                    <span class="truncate">{{ $buyerProfile?->anonymousLabel() }}</span>
                </span>
                <x-status-badge :status="App\Enums\RfqStatus::Open" size="sm" />
            </div>

            <div>
                @if ($review['category'])
                    <p class="text-xs text-gray-500">{{ $review['category'] }}</p>
                @endif
                <p class="text-[17px] leading-snug font-semibold break-words text-gray-900">{{ $title }}</p>
            </div>

            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 border-y border-gray-200 py-2.5">
                <div>
                    <dt class="eyebrow">{{ __('ui.quantity') }}</dt>
                    <dd class="text-sm font-semibold tabular"><span dir="ltr">{{ $quantity }}</span> {{ $review['unit'] }}</dd>
                </div>
                <div>
                    <dt class="eyebrow">{{ __('buyer.deliver_by') }}</dt>
                    <dd class="text-sm font-semibold tabular">{{ $delivery_date ? Illuminate\Support\Carbon::parse($delivery_date)->translatedFormat('j M Y') : '—' }}</dd>
                </div>
                <div>
                    <dt class="eyebrow">{{ __('ui.supply_type') }}</dt>
                    <dd class="text-sm font-semibold">{{ $supplyLabel }}</dd>
                </div>
                <div>
                    <dt class="eyebrow">{{ __('common.nav_quotes') }}</dt>
                    <dd class="text-sm font-semibold tabular">0</dd>
                </div>
            </dl>

            <div class="flex items-center justify-between gap-2">
                @if ($review['deadline'])
                    <x-countdown-chip :until="$review['deadline']" />
                @endif
                <span aria-hidden="true" class="ms-auto inline-flex h-8 items-center rounded-md bg-accent-500 px-3.5 text-[13px] font-semibold text-white">
                    {{ __('ui.submit_quote') }}
                </span>
            </div>
        </div>

        <p class="text-xs leading-relaxed text-gray-500">
            {{ __('buyer.hidden_from_suppliers', ['details' => collect([$buyerProfile?->company_name, auth()->user()->name, auth()->user()->phone])->filter()->implode(app()->isLocale('ar') ? '، ' : ', ')]) }}
        </p>
    </div>
</div>
