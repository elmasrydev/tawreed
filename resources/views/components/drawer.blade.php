@props(['name', 'width' => 'max-w-[820px]', 'show' => false])

{{--
    Slide-over panel anchored to the inline end. Full screen on phones.
    Open with $dispatch('open-drawer', 'name'), or bind to Livewire with
    wire:model="property".
--}}
<div
    x-data="{ show: @if ($attributes->wire('model')->value()) @entangle($attributes->wire('model')) @else @js($show) @endif }"
    x-on:open-drawer.window="$event.detail === @js($name) && (show = true)"
    x-on:close-drawer.window="$event.detail === @js($name) && (show = false)"
    x-on:keydown.escape.window="show = false"
    x-effect="document.body.classList.toggle('overflow-hidden', show)"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50"
    role="dialog" aria-modal="true"
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>
    <div x-show="show" x-transition.opacity class="absolute inset-0 bg-gray-900/45" x-on:click="show = false"></div>

    <div x-show="show"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="translate-x-full rtl:-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full rtl:-translate-x-full"
         x-trap.noscroll.inert="show"
         class="absolute inset-y-0 end-0 flex w-full flex-col bg-white shadow-drawer {{ $width }}">
        {{ $slot }}
    </div>
</div>
