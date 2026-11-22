@php
    $user = auth()->user();
    $organisation = [
        ['label' => __('ui.company_name'), 'value' => $profile->company_name],
        ['label' => __('ui.biz_type'), 'value' => $profile->businessType?->name],
        ['label' => __('ui.manager_name'), 'value' => $user->name],
        ['label' => __('ui.job_title'), 'value' => $profile->job_title],
        ['label' => __('ui.governorate'), 'value' => $profile->governorate?->name],
        ['label' => __('ui.commercial_reg'), 'value' => $profile->commercial_reg_no, 'ltr' => true],
        ['label' => __('ui.tax_card'), 'value' => $profile->tax_card_no, 'ltr' => true],
        ['label' => __('ui.company_address'), 'value' => $profile->company_address, 'wide' => true, 'hint' => __('buyer.never_shown_to_suppliers')],
    ];
@endphp

<x-layouts.buyer current="account" :title="__('common.nav_settings')">
    <div class="shell-page" x-data="{ tab: @js(request('tab') === 'account' && ! $isEditing ? 'account' : 'organisation') }">
        <x-page-header :title="__('common.nav_settings')" :subtitle="__('buyer.settings_sub')" />

        <nav class="scrollbar-none -mx-4 flex gap-1 overflow-x-auto border-b border-gray-200 px-4 sm:mx-0 sm:px-0" role="tablist">
            @foreach (['organisation' => __('buyer.tab_organisation'), 'account' => __('buyer.tab_account')] as $key => $label)
                <button type="button" role="tab" x-on:click="tab = @js($key)" :aria-selected="tab === @js($key)"
                        :class="tab === @js($key) ? 'border-brand-500 font-semibold text-brand-700' : 'border-transparent font-medium text-gray-500 hover:text-brand-700'"
                        class="-mb-px shrink-0 border-b-2 px-3.5 py-2.5 text-sm whitespace-nowrap transition-colors">
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,720px)_minmax(0,340px)]">
            <div>
                @if ($isEditing)
                    <section x-show="tab === 'organisation'" class="card p-4 sm:p-6">
                        <h2 class="card-title mb-4">
                            <x-lucide name="building" :size="16" class="text-brand-500" />
                            {{ __('buyer.edit_profile') }}
                        </h2>
                        <form method="POST" action="{{ route('buyer.profile.update') }}" class="flex flex-col gap-4">
                            @csrf
                            @method('PUT')

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <x-field name="company_name" :label="__('ui.company_name')" :value="$profile->company_name" required maxlength="180" autocomplete="organization" />
                                </div>

                                <div class="sm:col-span-2">
                                    <span class="label">{{ __('ui.biz_type') }}</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($businessTypes as $type)
                                            <label class="pill-choice">
                                                <input type="radio" name="business_type_id" value="{{ $type->id }}" class="sr-only" required
                                                       @checked((string) old('business_type_id', $profile->business_type_id) === (string) $type->id)>
                                                {{ $type->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('business_type_id') <p class="error-text">{{ $message }}</p> @enderror
                                </div>

                                <x-field name="name" :label="__('ui.manager_name')" :value="$user->name" required maxlength="120" autocomplete="name" />
                                <x-field name="job_title" :label="__('ui.job_title')" :value="$profile->job_title" maxlength="120" autocomplete="organization-title" />

                                <div>
                                    <label for="governorate_id" class="label">{{ __('ui.governorate') }}</label>
                                    <select id="governorate_id" name="governorate_id" required @class(['input', 'input-error' => $errors->has('governorate_id')])>
                                        @foreach ($governorates as $governorate)
                                            <option value="{{ $governorate->id }}" @selected((string) old('governorate_id', $profile->governorate_id) === (string) $governorate->id)>{{ $governorate->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('governorate_id') <p class="error-text">{{ $message }}</p> @enderror
                                </div>
                                <x-field name="company_address" :label="__('ui.company_address')" :value="$profile->company_address" maxlength="255"
                                         autocomplete="street-address" :hint="__('buyer.never_shown_to_suppliers')" />

                                <x-field name="commercial_reg_no" :label="__('ui.commercial_reg')" :value="$profile->commercial_reg_no" maxlength="60" dir="ltr" class="tabular" />
                                <x-field name="tax_card_no" :label="__('ui.tax_card')" :value="$profile->tax_card_no" maxlength="60" dir="ltr" class="tabular" />

                                <x-field name="email" id="email" type="email" :label="__('ui.email')" :value="$user->email"
                                         required readonly disabled dir="ltr" :hint="__('buyer.contact_locked_hint')" />
                                <x-field name="phone" id="phone" type="tel" :label="__('buyer.phone_login')" :value="$user->phone"
                                         required readonly disabled dir="ltr" :hint="__('buyer.contact_locked_hint')" />
                            </div>

                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('buyer.account') }}" class="btn btn-secondary">{{ __('ui.cancel') }}</a>
                                <button type="submit" class="btn btn-primary">{{ __('ui.save') }}</button>
                            </div>
                        </form>
                    </section>
                @else
                    <section x-show="tab === 'organisation'" class="card grid gap-x-5 gap-y-4 p-4 sm:grid-cols-2 sm:p-6">
                        <div class="flex justify-end sm:col-span-2">
                            <a href="{{ route('buyer.account', ['edit' => 1]) }}" class="btn btn-secondary btn-sm">
                                <x-lucide name="edit" :size="14" />
                                {{ __('buyer.edit_profile') }}
                            </a>
                        </div>
                        @foreach ($organisation as $field)
                            <div @class(['min-w-0', 'sm:col-span-2' => $field['wide'] ?? false])>
                                <p class="label">
                                    {{ $field['label'] }}
                                    @isset($field['hint'])
                                        <span class="font-normal text-gray-400">· {{ $field['hint'] }}</span>
                                    @endisset
                                </p>
                                <p class="flex min-h-10 items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm break-words text-gray-900"
                                   @if ($field['ltr'] ?? false) dir="ltr" @endif>
                                    {{ filled($field['value']) ? $field['value'] : '—' }}
                                </p>
                            </div>
                        @endforeach
                    </section>
                @endif

                <section x-show="tab === 'account'" x-cloak class="card flex flex-col gap-4 p-4 sm:p-6">
                    <div class="grid gap-x-5 gap-y-4 sm:grid-cols-2">
                        <div class="min-w-0">
                            <p class="label">{{ __('ui.email') }}</p>
                            <p dir="ltr" class="flex min-h-10 items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm break-all text-gray-900">{{ $user->email ?: '—' }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="label">{{ __('buyer.phone_login') }}</p>
                            <p dir="ltr" class="flex min-h-10 items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 tabular">{{ $user->phone ?: '—' }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ __('common.language') }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ __('buyer.language_hint') }}</p>
                        </div>
                        <x-language-toggle />
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ __('common.log_out') }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ __('buyer.log_out_hint') }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary">
                                <x-lucide name="log-out" :size="15" />
                                {{ __('common.log_out') }}
                            </button>
                        </form>
                    </div>
                </section>
            </div>

            <aside class="card flex flex-col gap-3 p-4 sm:p-5">
                <h2 class="card-title">
                    <x-lucide name="shield-check" :size="16" class="text-success-600" />
                    {{ __('buyer.what_suppliers_see') }}
                </h2>
                <p class="text-[13px] leading-relaxed text-gray-500">{{ __('ui.anon_note') }}</p>
                <p class="flex items-center gap-2 rounded-lg bg-brand-100 px-3.5 py-2.5 text-[13px] font-semibold text-brand-700">
                    <x-lucide name="lock" :size="14" />
                    {{ $profile->anonymousLabel() }}
                </p>
            </aside>
        </div>
    </div>
</x-layouts.buyer>
