@php
    $steps = [__('auth_ui.step_account'), __('auth_ui.step_business')];
    $accountFields = ['name', 'job_title', 'phone', 'email', 'password'];
    $initialStep = $errors->hasAny($accountFields) || $errors->isEmpty() ? 1 : 2;
@endphp

<x-layouts.auth :title="__('ui.buyer_reg_title')" context="buyer">
    <x-landing.stepper-form :action="route('register.buyer')" :total="count($steps)" :initial="$initialStep">
        <x-landing.form-steps :steps="$steps" />

        @if ($errors->any())
            <x-alert tone="danger">{{ __('auth_ui.fix_errors') }}</x-alert>
        @endif

        {{-- Step 1 · Account --}}
        <section data-step="1" x-show="step === 1" @if ($initialStep !== 1) style="display: none" @endif class="flex flex-col gap-5">
            <div>
                <h1 class="text-[26px] font-bold tracking-[-0.5px] text-brand-700">{{ __('auth_ui.account_title') }}</h1>
                <p class="mt-1.5 text-sm text-gray-500">{{ __('auth_ui.account_sub') }}</p>
            </div>

            <div class="grid gap-3.5 sm:grid-cols-2">
                <x-field name="name" :label="__('ui.manager_name')" required autocomplete="name" />
                <x-field name="job_title" :label="__('ui.job_title')" autocomplete="organization-title" />
                <x-phone-input name="phone" :label="__('ui.phone')" autocomplete="tel-national" />
                <x-field name="email" type="email" :label="__('ui.email')" required autocomplete="email" placeholder="name@company.com" dir="ltr" />
                <x-field name="password" type="password" :label="__('ui.password')" required autocomplete="new-password"
                         :placeholder="__('auth_ui.password_hint')" minlength="8" />
                <x-field name="password_confirmation" type="password" :label="__('ui.password_confirm')" required
                         autocomplete="new-password" minlength="8" />
            </div>

            <x-landing.step-actions first :back-url="route('register')" />
        </section>

        {{-- Step 2 · Business --}}
        <section data-step="2" x-show="step === 2" @if ($initialStep !== 2) style="display: none" @endif class="flex flex-col gap-5">
            <div>
                <h1 class="text-[26px] font-bold tracking-[-0.5px] text-brand-700">{{ __('auth_ui.buyer_business_title') }}</h1>
                <p class="mt-1.5 text-sm text-gray-500">{{ __('auth_ui.buyer_business_sub') }}</p>
            </div>

            <x-field name="company_name" :label="__('ui.company_name')" required autocomplete="organization" />

            <div>
                <span class="label">{{ __('ui.biz_type') }}</span>
                <div class="peer flex flex-wrap gap-2" data-required-group @change="$el.removeAttribute('data-invalid')">
                    @foreach ($businessTypes as $type)
                        <label class="pill-choice">
                            <input type="radio" name="business_type_id" value="{{ $type->id }}" class="sr-only"
                                   @checked((string) old('business_type_id') === (string) $type->id)>
                            {{ $type->name }}
                        </label>
                    @endforeach
                </div>
                <p class="error-text hidden peer-data-invalid:block">{{ __('auth_ui.required_field') }}</p>
                @error('business_type_id')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-3.5 sm:grid-cols-2">
                <div>
                    <label for="governorate_id" class="label">{{ __('ui.governorate') }}</label>
                    <select id="governorate_id" name="governorate_id" required @class(['input', 'input-error' => $errors->has('governorate_id')])>
                        <option value="">—</option>
                        @foreach ($governorates as $governorate)
                            <option value="{{ $governorate->id }}" @selected((string) old('governorate_id') === (string) $governorate->id)>{{ $governorate->name }}</option>
                        @endforeach
                    </select>
                    @error('governorate_id')
                        <p class="error-text">{{ $message }}</p>
                    @enderror
                </div>
                <x-field name="company_address" :label="__('ui.company_address')" autocomplete="street-address" />
                <x-field name="commercial_reg_no" :label="__('ui.commercial_reg')" placeholder="123456" dir="ltr" class="tabular" />
                <x-field name="tax_card_no" :label="__('ui.tax_card')" placeholder="987-654-321" dir="ltr" class="tabular" />
            </div>

            <x-alert tone="info" icon="lock">{{ __('ui.anon_note') }}</x-alert>

            <p class="text-xs text-gray-500">{{ __('ui.otp_note') }}</p>

            <x-landing.step-actions last :submit-label="__('auth_ui.create_buyer')" />
        </section>
    </x-landing.stepper-form>
</x-layouts.auth>
