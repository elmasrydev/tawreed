<?php

use App\Actions\Chat\SendMessage;
use App\Enums\MessageType;
use App\Enums\QuoteStatus;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The private 1:1 thread that opens once a quote is selected. Polling keeps it
 * current; swapping in broadcasting later would not change this component.
 */
new class extends Component
{
    public Conversation $conversation;

    public string $draft = '';

    public ?int $lastMessageId = null;

    public function mount(Conversation $conversation): void
    {
        Gate::authorize('view', $conversation);

        $this->conversation = $conversation;

        app(SendMessage::class)->markRead($conversation, auth()->user());

        $this->lastMessageId = $this->messages->last()?->id;
    }

    #[Computed]
    public function messages(): Collection
    {
        return $this->conversation->messages()
            ->with('media')
            ->where('is_removed', false)
            ->oldest()
            ->oldest('id')
            ->get();
    }

    #[Computed]
    public function counterpart(): User
    {
        return $this->conversation->counterpartFor(auth()->user())
            ->load(auth()->user()->isBuyer() ? 'supplierProfile.media' : 'buyerProfile');
    }

    #[Computed]
    public function counterpartName(): string
    {
        $profile = auth()->user()->isBuyer() ? $this->counterpart->supplierProfile : $this->counterpart->buyerProfile;

        return $profile?->company_name ?? $this->counterpart->name;
    }

    /**
     * Contact details revealed to both sides now that the deal is awarded.
     *
     * @return array{name: string, phone: ?string, email: string}
     */
    #[Computed]
    public function counterpartContact(): array
    {
        return [
            'name' => $this->counterpart->name,
            'phone' => $this->counterpart->phone,
            'email' => $this->counterpart->email,
        ];
    }

    public function send(SendMessage $sendMessage): void
    {
        Gate::authorize('reply', $this->conversation);

        $validated = $this->validate([
            'draft' => ['required', 'string', 'max:2000'],
        ]);

        $sendMessage->handle($this->conversation, auth()->user(), trim($validated['draft']));

        $this->draft = '';
        $this->syncMessages(force: true);
    }

    public function requestSample(SendMessage $sendMessage): void
    {
        Gate::authorize('reply', $this->conversation);

        $sendMessage->handle(
            $this->conversation,
            auth()->user(),
            __('chat.sample_request_body'),
            MessageType::SampleRequest,
        );

        $this->syncMessages(force: true);
    }

    public function refresh(SendMessage $sendMessage): void
    {
        $this->syncMessages();

        $sendMessage->markRead($this->conversation, auth()->user());
    }

    /**
     * Reloads the thread and asks the browser to scroll to the newest message
     * when something new arrived (always after the viewer sends).
     */
    private function syncMessages(bool $force = false): void
    {
        unset($this->messages);

        $latestId = $this->messages->last()?->id;

        if ($force || $latestId !== $this->lastMessageId) {
            $this->dispatch('chat-scroll', force: $force);
        }

        $this->lastMessageId = $latestId;
    }
};
?>

@php
    $viewer = auth()->user();
    $isBuyer = $viewer->isBuyer();
    $quote = $conversation->quote;
    $rfq = $conversation->rfq;
    $contact = $this->counterpartContact;
    $isVerified = $isBuyer && $this->counterpart->supplierProfile?->isVerified();
    $logo = $isBuyer ? ($this->counterpart->supplierProfile?->getFirstMediaUrl('logo') ?: null) : null;
    $isSelected = $quote->status === QuoteStatus::Selected;
    $total = number_format($quote->grandTotal());
@endphp

<div class="flex min-h-0 flex-1 flex-col" wire:poll.5s="refresh">
    {{-- Thread header --}}
    <header class="border-b border-gray-200 bg-white">
        <div class="flex items-center gap-3 px-3 py-2.5 sm:px-5 sm:py-3">
            <a href="{{ route($isBuyer ? 'buyer.chats.index' : 'supplier.chats.index') }}" wire:navigate
               class="btn btn-ghost btn-icon -ms-1 shrink-0 lg:hidden" aria-label="{{ __('chat.back_to_list') }}">
                <x-lucide name="arrow-left" :size="18" />
            </a>

            <x-avatar :name="$this->counterpartName" :src="$logo" :size="40" :tone="$isBuyer ? 'navy' : 'light'" />

            <div class="min-w-0 flex-1">
                <p class="flex items-center gap-1.5 text-[15px] font-semibold text-gray-900">
                    <span class="truncate">{{ $this->counterpartName }}</span>
                    @if ($isVerified)
                        <x-verified-seal :size="14" />
                    @endif
                </p>
                <p class="flex min-w-0 flex-wrap items-center gap-x-1.5 text-xs text-gray-500" aria-label="{{ __('chat.contact_details') }}">
                    <span class="truncate">{{ $contact['name'] }}</span>
                    @if ($contact['phone'])
                        <span aria-hidden="true">·</span>
                        <a href="tel:{{ $contact['phone'] }}" dir="ltr" class="tabular hover:text-brand-500">{{ $contact['phone'] }}</a>
                    @endif
                    <span aria-hidden="true" class="max-sm:hidden">·</span>
                    <a href="mailto:{{ $contact['email'] }}" class="truncate hover:text-brand-500 max-sm:basis-full">{{ $contact['email'] }}</a>
                </p>
            </div>

            {{-- RFQ context (wide screens) --}}
            <div class="hidden shrink-0 items-center gap-2.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs whitespace-nowrap 2xl:flex"
                 aria-label="{{ __('chat.rfq_context') }}">
                <span class="text-gray-500 tabular" dir="ltr">{{ $rfq->reference }}</span>
                @if ($isBuyer)
                    <a href="{{ route('buyer.rfqs.show', $rfq) }}" wire:navigate class="max-w-55 truncate font-semibold text-gray-900 hover:text-brand-500">{{ $rfq->title }}</a>
                @else
                    <span class="max-w-55 truncate font-semibold text-gray-900">{{ $rfq->title }}</span>
                @endif
                <span class="text-gray-300">|</span>
                @if ($isSelected)
                    <x-badge tone="success-solid" icon="check" size="sm" class="h-5! px-1.5! text-[10px]!">
                        {{ $isBuyer ? __('chat.selected') : __('chat.your_quote_selected') }}
                    </x-badge>
                @endif
                <span class="font-bold text-brand-700 tabular">{{ __('common.egp') }} {{ $total }}</span>
            </div>
        </div>

        {{-- RFQ context (compact strip below 1536px) --}}
        <div class="flex items-center gap-2 border-t border-gray-100 bg-gray-50 px-3 py-1.5 text-xs sm:px-5 2xl:hidden"
             aria-label="{{ __('chat.rfq_context') }}">
            <span class="shrink-0 text-gray-500 tabular max-sm:hidden" dir="ltr">{{ $rfq->reference }}</span>
            @if ($isBuyer)
                <a href="{{ route('buyer.rfqs.show', $rfq) }}" wire:navigate class="min-w-0 flex-1 truncate font-semibold text-gray-900 hover:text-brand-500">{{ $rfq->title }}</a>
            @else
                <span class="min-w-0 flex-1 truncate font-semibold text-gray-900">{{ $rfq->title }}</span>
            @endif
            @if ($isSelected)
                <x-badge tone="success-solid" icon="check" size="sm" class="h-5! px-1.5! text-[10px]!">
                    {{ $isBuyer ? __('chat.selected') : __('chat.your_quote_selected') }}
                </x-badge>
            @endif
            <span class="shrink-0 font-bold text-brand-700 tabular">{{ __('common.egp') }} {{ $total }}</span>
        </div>
    </header>

    {{-- Messages --}}
    <div class="flex min-h-0 flex-1 flex-col gap-2.5 overflow-y-auto overscroll-contain px-3 py-4 sm:px-5 sm:py-5"
         x-data="{
             stick: true,
             toBottom() { this.$el.scrollTop = this.$el.scrollHeight },
         }"
         x-init="toBottom(); requestAnimationFrame(() => toBottom())"
         x-on:scroll.passive="stick = $el.scrollHeight - $el.scrollTop - $el.clientHeight < 96"
         x-on:chat-scroll.window="requestAnimationFrame(() => { if ($event.detail.force || stick) toBottom() })"
         role="log" aria-live="polite">
        @foreach ($this->messages as $message)
            @php
                $day = $message->created_at->toDateString();
                $isNewDay = $day !== ($previousDay ?? null);
                $previousDay = $day;
            @endphp
            @if ($isNewDay)
                <div class="my-1 flex items-center gap-3 text-[11px] font-semibold text-gray-400" wire:key="day-{{ $day }}">
                    <span class="h-px flex-1 bg-gray-200"></span>
                    <span>
                        @if ($message->created_at->isToday())
                            {{ __('chat.today') }}
                        @elseif ($message->created_at->isYesterday())
                            {{ __('chat.yesterday') }}
                        @else
                            {{ $message->created_at->translatedFormat('l j F') }}
                        @endif
                    </span>
                    <span class="h-px flex-1 bg-gray-200"></span>
                </div>
            @endif

            @if ($message->isSystem())
                <p class="max-w-[92%] self-center rounded-2xl bg-gray-200 px-3 py-1 text-center text-xs leading-relaxed text-gray-500 sm:rounded-full" wire:key="message-{{ $message->id }}">
                    {{ $message->body }}
                    <span class="tabular">· {{ $message->created_at->format('H:i') }}</span>
                </p>
            @else
                @php
                    $mine = $message->sender_id === $viewer->id;
                    $isSample = $message->type === MessageType::SampleRequest;
                    $file = $message->type === MessageType::File ? $message->getFirstMedia('file') : null;
                @endphp
                <div wire:key="message-{{ $message->id }}" @class([
                    'max-w-[85%] rounded-xl px-3.5 py-2.5 text-sm leading-[1.45] sm:max-w-[70%]',
                    'self-end' => $mine,
                    'self-start' => ! $mine,
                    'rounded-ee-sm' => $mine,
                    'rounded-ss-sm' => ! $mine,
                    'border border-accent-200 bg-accent-50 text-gray-900' => $isSample,
                    'bg-brand-700 text-white' => $mine && ! $isSample,
                    'border border-gray-200 bg-white text-gray-900' => ! $mine && ! $isSample,
                ])>
                    @if ($isSample)
                        <p class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-accent-800">
                            <x-lucide name="flask" :size="14" />
                            {{ $message->type->label() }}
                        </p>
                    @endif

                    <p class="wrap-break-word whitespace-pre-line" dir="auto">{{ $message->body }}</p>

                    @if ($file)
                        <a href="{{ $file->getUrl() }}" target="_blank" rel="noopener" download
                           @class([
                               'mt-2 flex items-center gap-2 rounded-lg border px-2.5 py-2 text-[13px]',
                               'border-white/25 bg-white/15 hover:bg-white/25' => $mine,
                               'border-gray-200 bg-gray-50 hover:bg-gray-100' => ! $mine,
                           ])
                           aria-label="{{ __('chat.download') }} {{ $file->file_name }}">
                            <span class="grid size-7 shrink-0 place-items-center rounded-md bg-white text-[10px] font-bold text-brand-700 uppercase">
                                {{ Str::limit(pathinfo($file->file_name, PATHINFO_EXTENSION) ?: __('chat.attachment'), 4, '') }}
                            </span>
                            <span class="min-w-0 flex-1 truncate" dir="auto">{{ $file->file_name }}</span>
                            <x-lucide name="download" :size="14" class="shrink-0 opacity-80" />
                        </a>
                    @endif

                    <p @class(['mt-1 flex items-center gap-1 text-[11px] tabular', 'text-white/70' => $mine && ! $isSample, 'text-gray-500' => ! $mine || $isSample])>
                        <span>{{ $message->created_at->format('H:i') }}</span>
                        @if ($mine && $message->read_at)
                            <span aria-label="{{ __('chat.read') }}" title="{{ __('chat.read') }}">✓✓</span>
                        @endif
                    </p>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Composer --}}
    <div class="border-t border-gray-200 bg-white px-3 py-2.5 sm:px-5 sm:py-3">
        @if ($isBuyer)
            <div class="mb-2 flex flex-wrap gap-1.5">
                <button type="button" wire:click="requestSample" wire:loading.attr="disabled" wire:target="requestSample"
                        class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-full border border-gray-300 px-3 text-xs font-semibold whitespace-nowrap text-brand-700 transition hover:bg-brand-100 disabled:opacity-60">
                    <x-lucide name="flask" :size="12" />
                    {{ __('chat.request_sample') }}
                </button>
            </div>
        @endif

        <form wire:submit="send" class="flex items-center gap-2 sm:gap-2.5">
            <label for="chat-draft" class="sr-only">{{ __('chat.message_placeholder') }}</label>
            <input id="chat-draft" type="text" wire:model="draft" placeholder="{{ __('chat.message_placeholder') }}"
                   maxlength="2000" autocomplete="off"
                   @class(['input h-10 min-w-0 flex-1', 'input-error' => $errors->has('draft')])>
            <button type="submit" class="btn btn-primary h-10 shrink-0 max-sm:w-10 max-sm:px-0" wire:loading.attr="disabled" wire:target="send"
                    aria-label="{{ __('ui.send') }}">
                <span class="max-sm:hidden">{{ __('ui.send') }}</span>
                <x-lucide name="send" :size="14" />
            </button>
        </form>

        @error('draft')
            <p class="error-text mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
