@props(['conversations', 'active' => null])

@php
    $viewer = auth()->user();
    $isBuyer = $viewer->isBuyer();
    $showRoute = $isBuyer ? 'buyer.chats.show' : 'supplier.chats.show';

    $stamp = function (?Carbon\CarbonInterface $at): string {
        if ($at === null) {
            return '';
        }

        return match (true) {
            $at->isToday() => $at->format('H:i'),
            $at->isYesterday() => __('chat.yesterday'),
            $at->greaterThan(now()->subDays(6)->startOfDay()) => $at->translatedFormat('D'),
            default => $at->translatedFormat('j M'),
        };
    };
@endphp

<div class="flex flex-col gap-3 px-4 pt-4 pb-3 sm:px-[18px]">
    <div class="flex flex-col gap-1.5">
        <h1 class="flex items-center gap-2 text-xl font-bold text-brand-700">
            <x-lucide name="message-square" :size="20" />
            {{ __('common.nav_messages') }}
        </h1>
        <p class="text-xs leading-snug text-gray-500">
            {{ $isBuyer ? __('chat.buyer_list_note') : __('chat.supplier_list_note') }}
        </p>
    </div>

    @if ($conversations->isNotEmpty())
        <label class="relative block">
            <span class="sr-only">{{ __('chat.search_placeholder') }}</span>
            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-gray-400">
                <x-lucide name="search" :size="14" />
            </span>
            <input type="search" x-model="query" placeholder="{{ __('chat.search_placeholder') }}"
                   class="input h-9 ps-9 text-[13px]" autocomplete="off">
        </label>
    @endif
</div>

@if ($conversations->isEmpty())
    <div class="flex flex-1 flex-col items-center justify-center gap-2 border-t border-gray-100 px-6 py-12 text-center lg:hidden">
        <span class="mb-1 flex size-14 items-center justify-center rounded-[14px] bg-brand-100 text-brand-700">
            <x-lucide name="message-square" :size="26" />
        </span>
        <p class="text-base font-semibold text-gray-900">{{ __('chat.no_chats') }}</p>
        <p class="max-w-xs text-sm leading-relaxed text-gray-500">
            {{ $isBuyer ? __('chat.no_chats_body') : __('chat.supplier_no_chats_body') }}
        </p>
    </div>
@else
    <nav class="min-h-0 flex-1 overflow-y-auto" x-ref="threads" aria-label="{{ __('common.nav_messages') }}">
        @foreach ($conversations as $conversation)
            @php
                $counterpart = $conversation->counterpartFor($viewer);
                $profile = $isBuyer ? $counterpart->supplierProfile : $counterpart->buyerProfile;
                $name = $profile?->company_name ?? $counterpart->name;
                $logo = $isBuyer ? $profile?->getFirstMediaUrl('logo') : null;
                $latest = $conversation->latestMessage;
                $unread = $conversation->id === $active?->id ? 0 : (int) $conversation->unread_count;
                $preview = match (true) {
                    $latest === null => '',
                    $latest->type === App\Enums\MessageType::SampleRequest => $latest->type->label(),
                    $latest->sender_id === $viewer->id => __('chat.you_prefix', ['body' => $latest->body]),
                    default => $latest->body,
                };
                $isActive = $conversation->id === $active?->id;
                $haystack = mb_strtolower($name.' '.$conversation->rfq->title.' '.$conversation->rfq->reference);
            @endphp

            <a href="{{ route($showRoute, $conversation) }}" wire:navigate
               data-search="{{ $haystack }}"
               x-show="matches($el)"
               @if ($isActive) aria-current="page" @endif
               @class([
                   'flex gap-3 border-t border-gray-100 px-4 py-3 transition sm:px-[18px]',
                   'bg-brand-100' => $isActive,
                   'hover:bg-gray-50' => ! $isActive,
               ])>
                <x-avatar :name="$name" :src="$logo ?: null" :size="40" :tone="$isBuyer ? 'navy' : 'light'" />

                <span class="min-w-0 flex-1">
                    <span class="flex items-baseline justify-between gap-2">
                        <span class="truncate text-sm font-semibold text-gray-900">{{ $name }}</span>
                        <span class="shrink-0 text-[11px] text-gray-400 tabular">{{ $stamp($conversation->last_message_at) }}</span>
                    </span>
                    <span class="block truncate text-xs font-medium text-brand-500">{{ $conversation->rfq->title }}</span>
                    <span class="flex items-center justify-between gap-2">
                        <span dir="auto" @class(['truncate text-[13px]', 'font-semibold text-gray-900' => $unread > 0, 'text-gray-500' => $unread === 0])>{{ $preview }}</span>
                        @if ($unread > 0)
                            <span class="grid h-[18px] min-w-[18px] shrink-0 place-items-center rounded-full bg-accent-500 px-[5px] text-[11px] font-bold text-white tabular"
                                  data-unread="{{ $unread }}"
                                  aria-label="{{ trans_choice('chat.unread_count', $unread, ['count' => $unread]) }}">{{ $unread }}</span>
                        @endif
                    </span>
                </span>
            </a>
        @endforeach

        <p x-show="query && none()" x-cloak class="border-t border-gray-100 px-6 py-8 text-center text-[13px] text-gray-500">
            {{ __('chat.no_matches') }}
        </p>
    </nav>
@endif
