<x-dynamic-component :component="'layouts.'.$shell" current="chats" :title="__('common.nav_messages')">
    <x-chat.frame :conversations="$conversations">
        <div class="flex flex-1 flex-col items-center justify-center gap-2 px-6 text-center">
            <span class="mb-1 flex size-14 items-center justify-center rounded-[14px] bg-brand-100 text-brand-700">
                <x-lucide name="message-square" :size="26" />
            </span>
            @if ($conversations->isEmpty())
                <p class="text-base font-semibold text-gray-900">{{ __('chat.no_chats') }}</p>
                <p class="max-w-sm text-sm leading-relaxed text-gray-500">
                    {{ $shell === 'buyer' ? __('chat.no_chats_body') : __('chat.supplier_no_chats_body') }}
                </p>
                @if ($shell === 'buyer')
                    <a href="{{ route('buyer.rfqs.index') }}" wire:navigate class="btn btn-secondary mt-2">{{ __('common.nav_my_rfqs') }}</a>
                @else
                    <a href="{{ route('supplier.feed') }}" wire:navigate class="btn btn-secondary mt-2">{{ __('common.nav_browse_rfqs') }}</a>
                @endif
            @else
                <p class="text-base font-semibold text-gray-900">{{ __('chat.select_title') }}</p>
                <p class="max-w-sm text-sm leading-relaxed text-gray-500">{{ __('chat.select_body') }}</p>
            @endif
        </div>
    </x-chat.frame>
</x-dynamic-component>
