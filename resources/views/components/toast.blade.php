{{--
    Flash toast. Reads the session status or first validation error, and also
    listens for a browser `toast` event so Livewire actions can raise one with
    $this->dispatch('toast', message: '...').
--}}
@php
    $message = session('status') ?? ($errors->any() ? $errors->first() : null);
    $isError = ! session('status') && $errors->any();
@endphp

<div x-data="{
        show: false, message: '', error: false, timer: null,
        open(message, error = false) {
            this.message = message; this.error = error; this.show = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.show = false, 4000);
        }
     }"
     x-init="@if ($message) $nextTick(() => open(@js($message), @js($isError))) @endif"
     x-on:toast.window="open($event.detail.message ?? $event.detail[0]?.message ?? '', $event.detail.error ?? false)"
     class="pointer-events-none fixed inset-x-4 bottom-20 z-[60] flex justify-center lg:bottom-6"
     aria-live="polite">
    <div x-show="show" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-2 opacity-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0"
         data-toast
         class="pointer-events-auto flex max-w-md items-center gap-3 rounded-lg bg-gray-900 px-4 py-3 text-sm text-white shadow-toast">
        <span class="flex size-[22px] shrink-0 items-center justify-center rounded-full"
              :class="error ? 'bg-danger-600' : 'bg-success-500'">
            <x-lucide name="check" :size="13" :stroke="3" x-show="!error" />
            <x-lucide name="x" :size="13" :stroke="3" x-show="error" x-cloak />
        </span>
        <span x-text="message"></span>
        <button type="button" x-on:click="show = false" class="ms-2 cursor-pointer text-white/60 hover:text-white" aria-label="{{ __('common.dismiss') }}">
            <x-lucide name="x" :size="14" />
        </button>
    </div>
</div>
