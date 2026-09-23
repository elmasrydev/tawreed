@php
    $user = auth()->user();
    $context = $user->isSupplier() ? 'supplier' : 'buyer';
    $codeLength = App\Services\OtpService::CODE_LENGTH;
@endphp

<x-layouts.auth :title="__('auth_ui.verify_title')" :context="$context">
    <div class="flex flex-col items-center gap-5 text-center">
        <span class="flex size-16 items-center justify-center rounded-2xl bg-brand-100 text-brand-700">
            <x-lucide name="phone" :size="28" />
        </span>
        <div>
            <h1 class="text-[28px] font-bold tracking-[-0.5px] text-brand-700">{{ __('auth_ui.verify_title') }}</h1>
            <p class="mt-1.5 text-[15px] text-gray-500">
                {{ __('auth_ui.verify_sub', ['phone' => '']) }}<span dir="ltr" class="font-semibold text-gray-900 tabular">{{ $user->phone }}</span>
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('phone.verify') }}" class="mx-auto flex w-full max-w-[400px] flex-col gap-4">
        @csrf

        <div>
            <label for="code" class="sr-only">{{ __('ui.verify_code') }}</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                   maxlength="{{ $codeLength }}" required autofocus dir="ltr" placeholder="{{ str_repeat('•', $codeLength) }}"
                   x-data @input="$el.value = $el.value.replace(/\D/g, '').slice(0, {{ $codeLength }})"
                   @class([
                       'input h-16 text-center text-[28px] font-bold tracking-[0.5em] text-brand-700 tabular placeholder:text-gray-300',
                       'input-error' => $errors->has('code'),
                   ])>
            @error('code')
                <p class="error-text text-center">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-accent h-12 rounded-card text-[15px]">
            {{ __('auth_ui.verify_button') }}
            <x-lucide name="arrow-right" :size="16" :stroke="2.5" />
        </button>
    </form>

    <div class="flex flex-col items-center gap-3 text-[13px] text-gray-500">
        <form method="POST" action="{{ route('phone.resend') }}" class="flex flex-wrap items-center justify-center gap-1.5">
            @csrf
            {{ __('auth_ui.no_code') }}
            <button type="submit" class="link cursor-pointer">{{ __('auth_ui.resend') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="flex flex-wrap items-center justify-center gap-1.5">
            @csrf
            {{ __('auth_ui.not_you') }}
            <button type="submit" class="cursor-pointer font-semibold text-gray-600 hover:text-brand-700">{{ __('auth_ui.log_out') }}</button>
        </form>
    </div>
</x-layouts.auth>
