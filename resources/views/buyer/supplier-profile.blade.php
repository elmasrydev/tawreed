@php
    $logo = $profile->getFirstMediaUrl('logo') ?: null;
    $portfolio = $profile->getMedia('portfolio')->filter(fn ($media) => str_starts_with((string) $media->mime_type, 'image/'));
    $cover = $portfolio->first()?->getUrl();
    $meta = collect([
        $profile->categories->take(3)->pluck('name')->implode(app()->isLocale('ar') ? '، ' : ', '),
        $profile->facility_address,
        __('buyer.member_since', ['year' => $profile->created_at->year]),
    ])->filter();
@endphp

<x-layouts.buyer current="quotes" :title="$profile->company_name">
    <div class="flex flex-col">
        <div @class(['relative h-32 sm:h-44', 'placeholder-stripes' => ! $cover, 'bg-gray-200' => $cover])>
            @if ($cover)
                <img src="{{ $cover }}" alt="" class="size-full object-cover">
            @endif
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('buyer.quotes.index') }}" wire:navigate
               class="absolute start-4 top-4 inline-flex min-h-9 items-center gap-1.5 rounded-md bg-white/90 px-2.5 text-[13px] font-medium text-brand-700 hover:bg-white sm:start-7">
                <x-lucide name="arrow-left" :size="14" />
                {{ __('ui.back') }}
            </a>
        </div>

        <div class="shell-page pt-0 lg:pt-0">
            <div class="relative z-10 -mt-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-end sm:gap-4">
                    <x-avatar :name="$profile->company_name" :src="$logo" :size="88" class="border-4 border-gray-50 text-[26px]" />
                    <div class="min-w-0 sm:pb-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-xl font-bold tracking-[-0.3px] break-words text-brand-700 sm:text-2xl">{{ $profile->company_name }}</h1>
                            <x-status-badge :status="$profile->verification_status" />
                        </div>
                        @if ($meta->isNotEmpty())
                            <p class="mt-1 text-sm text-gray-500">{{ $meta->implode(' · ') }}</p>
                        @endif
                    </div>
                </div>

                <div class="sm:pb-1">
                    @if ($conversation)
                        <a href="{{ route('buyer.chats.show', $conversation) }}" wire:navigate class="btn btn-primary">
                            <x-lucide name="message-square" :size="16" />
                            {{ __('buyer.message') }}
                        </a>
                    @else
                        <span class="flex flex-col items-start gap-1 sm:items-end">
                            <button type="button" disabled class="btn btn-primary opacity-50" aria-describedby="message-hint">
                                <x-lucide name="message-square" :size="16" />
                                {{ __('buyer.message') }}
                            </button>
                            <span id="message-hint" class="text-xs text-gray-500">{{ __('buyer.message_locked_hint') }}</span>
                        </span>
                    @endif
                </div>
            </div>

            @include('buyer.partials.supplier-profile-body')
        </div>
    </div>
</x-layouts.buyer>
