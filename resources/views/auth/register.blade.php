@php
    $roles = [
        'buyer' => [
            'url' => route('register.buyer'),
            'icon' => 'icon-pick-quote.png',
            'dot' => 'bg-success-500',
            'meta' => 'text-success-700',
        ],
        'supplier' => [
            'url' => route('register.supplier'),
            'icon' => 'icon-role-supplier-v2.svg',
            'dot' => 'bg-brand-500',
            'meta' => 'text-brand-500',
        ],
    ];
@endphp

<x-layouts.auth :title="__('ui.create_account')" context="login" switch-to="login">
    <div>
        <h1 class="text-[28px] font-bold tracking-[-0.5px] text-brand-700">{{ __('auth_ui.role_title') }}</h1>
        <p class="mt-1.5 text-[15px] text-gray-500">{{ __('auth_ui.role_sub') }}</p>
    </div>

    <form x-data="{ role: null }"
          @submit.prevent="if (role) window.location.href = $root.querySelector('input[name=role]:checked').dataset.url"
          class="flex flex-col gap-[22px]">
        <div class="grid gap-3.5 sm:grid-cols-2" role="radiogroup">
            @foreach ($roles as $key => $role)
                <label class="flex cursor-pointer flex-col gap-3.5 rounded-[14px] border-2 border-gray-200 bg-white p-5 transition hover:border-brand-500 has-checked:border-brand-500 has-checked:bg-brand-100 has-focus-visible:shadow-focus sm:p-[22px]">
                    <input type="radio" name="role" value="{{ $key }}" data-url="{{ $role['url'] }}" x-model="role" class="sr-only">
                    <img src="{{ asset('images/brand/'.$role['icon']) }}" alt="" class="size-[52px]">
                    <span>
                        <span class="block text-lg font-bold text-brand-700">{{ __("auth_ui.role_{$key}_title") }}</span>
                        <span class="mt-1 block text-[13px] leading-normal text-gray-500">{{ __("auth_ui.role_{$key}_body") }}</span>
                    </span>
                    <span class="mt-auto flex items-center gap-1.5 text-[13px] font-semibold {{ $role['meta'] }}">
                        <span class="size-1.5 rounded-full {{ $role['dot'] }}"></span>
                        {{ __("auth_ui.role_{$key}_meta") }}
                    </span>
                </label>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary h-12 rounded-card text-[15px]" :disabled="! role">
            {{ __('auth_ui.continue') }}
            <x-lucide name="arrow-right" :size="16" :stroke="2.5" />
        </button>
    </form>
</x-layouts.auth>
