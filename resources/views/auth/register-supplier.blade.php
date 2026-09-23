@php
    $steps = [__('auth_ui.step_account'), __('auth_ui.step_company'), __('auth_ui.step_coverage'), __('auth_ui.step_documents')];
    $stepFields = [
        1 => ['name', 'phone', 'email', 'password'],
        2 => ['company_name', 'commercial_reg_no', 'tax_number', 'facility_address', 'activity_description', 'payment_method'],
        3 => ['governorate_ids', 'governorate_ids.*', 'category_ids', 'category_ids.*'],
        4 => ['documents.commercial_registration', 'documents.tax_card', 'documents.logo'],
    ];
    $initialStep = collect($stepFields)->search(fn (array $fields): bool => $errors->hasAny($fields)) ?: 1;

    $documents = [
        'commercial_registration' => ['icon' => 'file-text', 'required' => true, 'accept' => '.pdf,.jpg,.jpeg,.png'],
        'tax_card' => ['icon' => 'credit-card', 'required' => true, 'accept' => '.pdf,.jpg,.jpeg,.png'],
        'logo' => ['icon' => 'image', 'required' => false, 'accept' => '.jpg,.jpeg,.png,.webp'],
    ];

    $oldGovernorates = array_map('strval', (array) old('governorate_ids', []));
    $oldCategories = array_map('strval', (array) old('category_ids', []));
@endphp

<x-layouts.auth :title="__('ui.sup_reg_title')" context="supplier">
    <x-landing.stepper-form :action="route('register.supplier')" :total="count($steps)" :initial="$initialStep" multipart>
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
                <x-field name="name" :label="__('ui.responsible_person')" required autocomplete="name" />
                <x-phone-input name="phone" :label="__('ui.phone')" autocomplete="tel-national" />
                <div class="sm:col-span-2">
                    <x-field name="email" type="email" :label="__('ui.email')" required autocomplete="email" placeholder="name@company.com" dir="ltr" />
                </div>
                <x-field name="password" type="password" :label="__('ui.password')" required autocomplete="new-password"
                         :placeholder="__('auth_ui.password_hint')" minlength="8" />
                <x-field name="password_confirmation" type="password" :label="__('ui.password_confirm')" required
                         autocomplete="new-password" minlength="8" />
            </div>

            <x-landing.step-actions first :back-url="route('register')" />
        </section>

        {{-- Step 2 · Company --}}
        <section data-step="2" x-show="step === 2" @if ($initialStep !== 2) style="display: none" @endif class="flex flex-col gap-5">
            <div>
                <h1 class="text-[26px] font-bold tracking-[-0.5px] text-brand-700">{{ __('auth_ui.company_title') }}</h1>
                <p class="mt-1.5 text-sm text-gray-500">{{ __('auth_ui.company_sub') }}</p>
            </div>

            <x-field name="company_name" :label="__('ui.factory_name')" required autocomplete="organization" />

            <div class="grid gap-3.5 sm:grid-cols-2">
                <x-field name="commercial_reg_no" :label="__('ui.commercial_reg')" required placeholder="445210" dir="ltr" class="tabular" />
                <x-field name="tax_number" :label="__('ui.tax_number')" required placeholder="204-881-337" dir="ltr" class="tabular" />
            </div>

            <x-field name="facility_address" :label="__('ui.facility_address')" autocomplete="street-address" />

            <div>
                <label for="activity_description" class="label">
                    {{ __('ui.activity_desc') }} <span class="font-normal text-gray-400">{{ __('ui.optional') }}</span>
                </label>
                <textarea id="activity_description" name="activity_description" rows="3" maxlength="1000"
                          @class(['input', 'input-error' => $errors->has('activity_description')])>{{ old('activity_description') }}</textarea>
                @error('activity_description')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <x-field name="payment_method" :label="__('ui.payment_method')" placeholder="CIB" />

            <x-landing.step-actions />
        </section>

        {{-- Step 3 · Categories & coverage --}}
        <section data-step="3" x-show="step === 3" @if ($initialStep !== 3) style="display: none" @endif class="flex flex-col gap-6">
            <div>
                <h1 class="text-[26px] font-bold tracking-[-0.5px] text-brand-700">{{ __('auth_ui.coverage_title') }}</h1>
                <p class="mt-1.5 text-sm text-gray-500">{{ __('auth_ui.coverage_sub') }}</p>
            </div>

            <div x-data="{ count: {{ count($oldGovernorates) }}, total: {{ $governorates->count() }},
                           sync() { this.count = this.$refs.governorates.querySelectorAll('input:checked').length },
                           setAll(checked) { this.$refs.governorates.querySelectorAll('input').forEach(input => input.checked = checked); this.sync(); this.$refs.governorates.removeAttribute('data-invalid') } }"
                 class="flex flex-col gap-2.5">
                <div class="flex items-center justify-between gap-3 text-[13px]">
                    <span class="font-semibold text-gray-700">
                        {{ __('auth_ui.coverage_label') }}
                        <span class="ms-1 font-normal text-gray-500 tabular" x-text="@js(__('auth_ui.selected_count', ['count' => '__COUNT__'])).replace('__COUNT__', count)">{{ __('auth_ui.selected_count', ['count' => count($oldGovernorates)]) }}</span>
                    </span>
                    <button type="button" class="link cursor-pointer text-[13px]" @click="setAll(count < total)"
                            x-text="count < total ? @js(__('auth_ui.select_all')) : @js(__('auth_ui.clear_all'))">{{ __('auth_ui.select_all') }}</button>
                </div>
                <div x-ref="governorates" class="peer flex flex-wrap gap-2" data-required-group @change="sync(); $el.removeAttribute('data-invalid')">
                    @foreach ($governorates as $governorate)
                        <label class="pill-choice rounded-lg">
                            <input type="checkbox" name="governorate_ids[]" value="{{ $governorate->id }}" class="sr-only"
                                   @checked(in_array((string) $governorate->id, $oldGovernorates, true))>
                            {{ $governorate->name }}
                        </label>
                    @endforeach
                </div>
                <p class="error-text hidden peer-data-invalid:block">{{ __('auth_ui.required_field') }}</p>
                @error('governorate_ids')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ count: {{ count($oldCategories) }} }" class="flex flex-col gap-2.5">
                <div class="text-[13px]">
                    <span class="font-semibold text-gray-700">{{ __('auth_ui.categories_label') }}</span>
                    <span class="font-normal text-gray-400">{{ __('ui.optional') }}</span>
                    <span class="ms-1 text-gray-500 tabular" x-text="@js(__('auth_ui.selected_count', ['count' => '__COUNT__'])).replace('__COUNT__', count)">{{ __('auth_ui.selected_count', ['count' => count($oldCategories)]) }}</span>
                </div>
                <div class="flex flex-wrap gap-2" @change="count = $el.querySelectorAll('input:checked').length">
                    @foreach ($categories as $category)
                        <label class="pill-choice h-[34px] px-[13px]">
                            <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" class="peer sr-only"
                                   @checked(in_array((string) $category->id, $oldCategories, true))>
                            <x-lucide name="check" :size="12" :stroke="3" class="hidden peer-checked:block" />
                            {{ $category->name }}
                        </label>
                    @endforeach
                </div>
                @error('category_ids')
                    <p class="error-text">{{ $message }}</p>
                @enderror
            </div>

            <x-landing.step-actions />
        </section>

        {{-- Step 4 · Documents. The file inputs stay in the DOM whichever step is visible. --}}
        <section data-step="4" x-show="step === 4" @if ($initialStep !== 4) style="display: none" @endif class="flex flex-col gap-5">
            <div>
                <h1 class="text-[26px] font-bold tracking-[-0.5px] text-brand-700">{{ __('auth_ui.documents_title') }}</h1>
                <p class="mt-1.5 text-sm leading-relaxed text-gray-500">{{ __('auth_ui.documents_sub') }}</p>
            </div>

            @if ($errors->any())
                <x-alert tone="warning">{{ __('auth_ui.documents_reselect') }}</x-alert>
            @endif

            <div class="flex flex-col gap-2.5">
                @foreach ($documents as $type => $document)
                    <div>
                        <label data-field x-data="{ file: '' }"
                               @class([
                                   'group flex cursor-pointer items-center gap-3.5 rounded-card border-[1.5px] px-4 py-3.5 transition has-focus-visible:shadow-focus data-invalid:border-danger-600',
                                   'border-danger-600' => $errors->has("documents.$type"),
                               ])
                               :class="file ? 'border-solid border-success-500 bg-success-25' : 'border-dashed border-gray-300 bg-white hover:border-brand-500'">
                            <input type="file" name="documents[{{ $type }}]" accept="{{ $document['accept'] }}" class="sr-only"
                                   @required($document['required'])
                                   @change="file = $event.target.files[0]?.name ?? ''; $el.closest('[data-field]').removeAttribute('data-invalid')">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-[9px]"
                                  :class="file ? 'bg-success-500 text-white' : 'bg-gray-100 text-gray-500'">
                                <x-lucide :name="$document['icon']" :size="18" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-900">
                                    {{ App\Enums\SupplierDocumentType::from($type)->label() }}
                                    @unless ($document['required'])
                                        <span class="rounded bg-gray-100 px-1.5 py-px text-[11px] font-semibold text-gray-500">{{ __('common.optional') }}</span>
                                    @endunless
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-gray-500" x-show="! file">{{ __("auth_ui.doc_{$type}_hint") }}</span>
                                <span class="mt-0.5 block truncate text-xs font-medium text-success-700" dir="ltr" x-show="file" x-text="file" style="display: none"></span>
                            </span>
                            <span class="shrink-0 text-[13px] font-semibold whitespace-nowrap"
                                  :class="file ? 'text-success-700' : 'text-brand-500'">
                                <span x-show="! file" class="inline-flex items-center gap-1"><x-lucide name="upload" :size="14" />{{ __('auth_ui.upload') }}</span>
                                <span x-show="file" class="inline-flex items-center gap-1" style="display: none"><x-lucide name="check" :size="14" :stroke="3" />{{ __('auth_ui.replace') }}</span>
                            </span>
                        </label>
                        @error("documents.$type")
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="flex items-start gap-2.5 rounded-lg bg-brand-100 px-3.5 py-3 text-xs leading-normal text-brand-700">
                <img src="{{ asset('images/brand/icon-verified-shield.png') }}" alt="" class="size-[22px] shrink-0">
                {{ __('auth_ui.documents_note') }}
            </div>

            <p class="text-xs text-gray-500">{{ __('ui.otp_note') }}</p>

            <x-landing.step-actions last :submit-label="__('auth_ui.submit_supplier')" />
        </section>
    </x-landing.stepper-form>
</x-layouts.auth>
