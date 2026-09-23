<x-layouts.auth :title="__('auth_ui.login_title')" context="login">
    <div>
        <h1 class="text-[28px] font-bold tracking-[-0.5px] text-brand-700">{{ __('auth_ui.login_title') }}</h1>
        <p class="mt-1.5 text-[15px] text-gray-500">{{ __('auth_ui.login_sub') }}</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-[22px]">
        @csrf

        <div class="flex flex-col gap-3.5">
            <x-field name="login" :label="__('ui.login_field')" required autocomplete="username" dir="auto"
                     :placeholder="__('auth_ui.login_placeholder')" class="h-[46px] text-[15px]" autofocus />

            <div x-data="{ visible: false }">
                <label for="password" class="label">{{ __('ui.password') }}</label>
                <div class="relative">
                    <input id="password" name="password" :type="visible ? 'text' : 'password'" type="password" required
                           autocomplete="current-password" placeholder="••••••••"
                           @class(['input h-[46px] pe-16 text-[15px]', 'input-error' => $errors->has('password')])>
                    <button type="button" @click="visible = ! visible"
                            class="absolute inset-y-0 end-0 flex cursor-pointer items-center px-3 text-xs font-semibold text-gray-500 hover:text-brand-700"
                            :aria-pressed="visible.toString()">
                        <span x-text="visible ? @js(__('auth_ui.hide')) : @js(__('auth_ui.show'))">{{ __('auth_ui.show') }}</span>
                    </button>
                </div>
                @error('password')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex cursor-pointer items-center gap-2 text-[13px] text-gray-700">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                       class="size-[18px] cursor-pointer rounded border-gray-300 accent-brand-700">
                {{ __('auth_ui.remember') }}
            </label>
        </div>

        <button type="submit" class="btn btn-accent h-12 rounded-card text-[15px]">
            {{ __('auth_ui.log_in') }}
            <x-lucide name="arrow-right" :size="16" :stroke="2.5" />
        </button>
    </form>
</x-layouts.auth>
