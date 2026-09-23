@props(['name', 'maxWidth' => 'md', 'show' => false])

{{--
    Centred dialog. Open with $dispatch('open-modal', 'name') and close with
    $dispatch('close-modal', 'name'), or bind it to a Livewire property with
    wire:model="property". On phones it becomes a bottom sheet.
--}}
@php
    $widths = ['sm' => 'sm:max-w-[420px]', 'md' => 'sm:max-w-[480px]', 'lg' => 'sm:max-w-[560px]', 'xl' => 'sm:max-w-[720px]'];
@endphp

<div
    x-data="{ show: @if ($attributes->wire('model')->value()) @entangle($attributes->wire('model')) @else @js($show) @endif }"
    x-on:open-modal.window="$event.detail === @js($name) && (show = true)"
    x-on:close-modal.window="$event.detail === @js($name) && (show = false)"
    x-on:keydown.escape.window="show = false"
    x-effect="document.body.classList.toggle('overflow-hidden', show)"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-6"
    role="dialog" aria-modal="true"
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>
    <div x-show="show" x-transition.opacity class="absolute inset-0 bg-gray-900/45" x-on:click="show = false"></div>

    <div x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-trap.noscroll.inert="show"
         class="relative max-h-[92dvh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-modal sm:rounded-xl sm:p-6 {{ $widths[$maxWidth] ?? $widths['md'] }}">
        {{ $slot }}
    </div>
</div>
