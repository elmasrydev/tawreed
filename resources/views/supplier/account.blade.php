<x-layouts.supplier current="account" :title="__('common.nav_profile')">
    @php
        $verification = $profile->verification_status;
        $tabs = [
            ['key' => 'company', 'label' => __('supplier.tab_company'), 'url' => route('supplier.account')],
            ['key' => 'coverage', 'label' => __('supplier.tab_coverage'), 'url' => route('supplier.account', ['tab' => 'coverage'])],
            ['key' => 'documents', 'label' => __('ui.documents'), 'count' => $profile->documents->count(), 'url' => route('supplier.account', ['tab' => 'documents'])],
            ['key' => 'portfolio', 'label' => __('supplier.tab_portfolio'), 'count' => $profile->portfolioItems->count(), 'url' => route('supplier.account', ['tab' => 'portfolio'])],
        ];
        $documentsByType = $profile->documents->keyBy(fn ($document) => $document->type->value);
    @endphp

    <div class="shell-page">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                <h1 class="min-w-0 text-xl font-bold tracking-[-0.3px] break-words text-brand-700 sm:text-2xl">{{ $profile->company_name }}</h1>
                <x-status-badge :status="$verification" />
            </div>
            <p class="mt-1 flex flex-wrap items-center gap-x-1.5 text-sm text-gray-500">
                <span>{{ trans_choice('supplier.categories_count', $profile->categories->count(), ['count' => $profile->categories->count()]) }}</span>
                <span aria-hidden="true">·</span>
                <span>{{ trans_choice('supplier.governorates_count', $profile->governorates->count(), ['count' => $profile->governorates->count()]) }}</span>
                <span aria-hidden="true">·</span>
                @if ($profile->reviews_count > 0)
                    <span class="tabular"><span class="text-accent-500">★</span> {{ number_format((float) $profile->rating_avg, 1) }} ({{ $profile->reviews_count }})</span>
                @else
                    <span>{{ __('supplier.no_reviews') }}</span>
                @endif
            </p>
        </div>

        <x-tabs :items="$tabs" :current="$tab" />

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,820px)_minmax(0,1fr)]">
            <div class="flex min-w-0 flex-col gap-4">
                @if ($tab === 'company')
                    <section class="card p-5 sm:p-6">
                        <h2 class="card-title mb-4">
                            <x-lucide name="building" :size="16" class="text-brand-500" />
                            {{ __('supplier.company_information') }}
                        </h2>
                        <dl class="grid gap-x-5 gap-y-4 sm:grid-cols-2">
                            @foreach ([
                                [__('ui.factory_name'), $profile->company_name, false, false],
                                [__('ui.responsible_person'), $supplier->name, false, false],
                                [__('ui.phone'), $supplier->phone, true, false],
                                [__('ui.email'), $supplier->email, true, false],
                                [__('ui.commercial_reg'), $profile->commercial_reg_no, true, false],
                                [__('ui.tax_number'), $profile->tax_number, true, false],
                                [__('ui.facility_address'), $profile->facility_address, false, true],
                                [__('ui.activity_desc'), $profile->activity_description, false, true],
                            ] as [$label, $value, $isLtr, $isWide])
                                <div @class(['min-w-0', 'sm:col-span-2' => $isWide])>
                                    <dt class="text-xs font-semibold text-gray-500">{{ $label }}</dt>
                                    <dd @class(['mt-1 text-sm break-words whitespace-pre-line text-gray-900', 'tabular' => $isLtr])>@if ($isLtr && filled($value))<span dir="ltr">{{ $value }}</span>@else{{ filled($value) ? $value : '—' }}@endif</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @elseif ($tab === 'coverage')
                    <section class="card flex flex-col gap-3.5 p-5 sm:p-6">
                        <h2 class="card-title">
                            <x-lucide name="map-pin" :size="16" class="text-brand-500" />
                            {{ __('ui.coverage_areas') }}
                        </h2>
                        <div class="flex flex-wrap gap-1.5">
                            @forelse ($profile->governorates as $governorate)
                                <span class="inline-flex h-7 items-center rounded-md bg-brand-100 px-2.5 text-[13px] font-medium text-brand-700">{{ $governorate->name }}</span>
                            @empty
                                <span class="text-sm text-gray-500">—</span>
                            @endforelse
                        </div>
                    </section>

                    <section class="card flex flex-col gap-3.5 p-5 sm:p-6">
                        <h2 class="card-title">
                            <x-lucide name="package" :size="16" class="text-brand-500" />
                            {{ __('ui.categories_supplied') }}
                        </h2>
                        <div class="flex flex-wrap gap-1.5">
                            @forelse ($profile->categories as $category)
                                <span class="inline-flex h-7 items-center rounded-md border border-gray-200 bg-white px-2.5 text-[13px] font-medium text-gray-700">
                                    {{ $category->parent ? $category->parent->name.' › ' : '' }}{{ $category->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-500">—</span>
                            @endforelse
                        </div>
                    </section>

                    <section class="card flex flex-col gap-3 p-5 sm:p-6">
                        <h2 class="card-title">
                            <x-lucide name="credit-card" :size="16" class="text-brand-500" />
                            {{ __('ui.payment_method') }}
                        </h2>
                        <p class="text-sm text-gray-900">{{ filled($profile->payment_method) ? $profile->payment_method : '—' }}</p>
                    </section>
                @elseif ($tab === 'documents')
                    <section class="card flex flex-col gap-3 p-5 sm:p-6">
                        <h2 class="card-title">
                            <x-lucide name="shield-check" :size="16" class="text-success-500" />
                            {{ __('supplier.verification_documents') }}
                        </h2>

                        @if ($verification !== App\Enums\VerificationStatus::Verified)
                            <x-alert :tone="$verification === App\Enums\VerificationStatus::Pending ? 'neutral' : 'danger'">
                                {{ $verification->hint() }}
                                @if ($profile->verification_note)
                                    <span class="mt-1 block font-medium">{{ __('supplier.reviewer_note', ['note' => $profile->verification_note]) }}</span>
                                @endif
                            </x-alert>
                        @endif

                        <p class="text-[13px] text-gray-500">{{ __('supplier.documents_hint') }}</p>

                        @foreach (App\Enums\SupplierDocumentType::required() as $type)
                            @php($document = $documentsByType->get($type->value))
                            <div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 px-3.5 py-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
                                    <x-lucide name="file-text" :size="18" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900">{{ $type->label() }}</p>
                                    <p class="text-xs text-gray-500 tabular">
                                        {{ $document ? __('supplier.uploaded_on', ['date' => ($document->uploaded_at ?? $document->created_at)->translatedFormat('j M Y')]) : __('supplier.document_missing') }}
                                    </p>
                                </div>
                                @if ($document)
                                    <x-status-badge :status="$document->status" size="sm" />
                                    @if ($document->hasMedia('file'))
                                        <a href="{{ route('supplier-documents.show', $document) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">
                                            <x-lucide name="eye" :size="14" />
                                            {{ __('common.view') }}
                                        </a>
                                    @endif
                                @endif
                                <form method="POST" action="{{ route('supplier.documents.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type->value }}">
                                    <label class="btn btn-secondary btn-sm cursor-pointer">
                                        <x-lucide name="upload" :size="14" />
                                        {{ $document ? __('supplier.document_replace') : __('supplier.document_upload') }}
                                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" x-data x-on:change="$el.form.submit()">
                                    </label>
                                </form>
                                @if ($document?->status === App\Enums\DocumentStatus::Rejected && $document->note)
                                    <p class="basis-full text-xs text-danger-700">{{ $document->note }}</p>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @else
                    <section class="card flex flex-col gap-4 p-5 sm:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="card-title">
                                    <x-lucide name="image" :size="16" class="text-brand-500" />
                                    {{ __('supplier.portfolio_heading') }}
                                </h2>
                                <p class="mt-1 text-[13px] text-gray-500">{{ __('supplier.portfolio_hint') }}</p>
                            </div>
                            <button type="button" x-data x-on:click="$dispatch('open-modal', 'portfolio-new')" class="btn btn-primary btn-sm">
                                <x-lucide name="plus" :size="14" />
                                {{ __('supplier.portfolio_add') }}
                            </button>
                        </div>

                        @forelse ($profile->portfolioItems as $item)
                            @php($images = $item->getMedia('images'))
                            <article class="flex flex-col gap-3 rounded-lg border border-gray-200 p-3.5 sm:flex-row">
                                @if ($images->isNotEmpty())
                                    <img src="{{ $images->first()->hasGeneratedConversion('thumb') ? $images->first()->getUrl('thumb') : $images->first()->getUrl() }}"
                                         alt="" class="aspect-[4/3] w-full shrink-0 rounded-md bg-gray-100 object-cover sm:w-40" loading="lazy">
                                @endif
                                <div class="flex min-w-0 flex-1 flex-col gap-1">
                                    <p class="text-sm font-semibold break-words text-gray-900">{{ $item->title }}</p>
                                    @if ($item->client_type || $item->year)
                                        <p class="text-xs text-gray-500">{{ collect([$item->client_type, $item->year])->filter()->implode(' · ') }}</p>
                                    @endif
                                    @if ($item->description)
                                        <p class="line-clamp-3 text-[13px] leading-relaxed whitespace-pre-line text-gray-700">{{ $item->description }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400">{{ trans_choice('supplier.portfolio_images_count', $images->count(), ['count' => $images->count()]) }}</p>
                                </div>
                                <div class="flex shrink-0 items-start gap-2">
                                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'portfolio-{{ $item->id }}')" class="btn btn-secondary btn-sm">
                                        <x-lucide name="edit" :size="14" />
                                        {{ __('supplier.portfolio_edit_short') }}
                                    </button>
                                    <form method="POST" action="{{ route('supplier.portfolio.destroy', $item) }}"
                                          x-data x-on:submit="confirm(@js(__('supplier.portfolio_delete_confirm'))) || $event.preventDefault()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger-outline btn-sm" aria-label="{{ __('supplier.portfolio_delete') }}">
                                            <x-lucide name="trash" :size="14" />
                                        </button>
                                    </form>
                                </div>
                            </article>

                            <x-modal name="portfolio-{{ $item->id }}" max-width="lg" :show="old('portfolio_form') === (string) $item->id">
                                @include('supplier.partials.portfolio-form', ['item' => $item])
                            </x-modal>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('supplier.portfolio_empty') }}</p>
                        @endforelse
                    </section>

                    <x-modal name="portfolio-new" max-width="lg" :show="old('portfolio_form') === 'new'">
                        @include('supplier.partials.portfolio-form', ['item' => null])
                    </x-modal>
                @endif
            </div>

            {{-- Account: language and sign out, since there is no separate settings page --}}
            <aside class="card flex flex-col gap-4 p-5">
                <h2 class="card-title">
                    <x-lucide name="user" :size="16" class="text-brand-500" />
                    {{ __('common.nav_account') }}
                </h2>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm text-gray-700">{{ __('common.language') }}</span>
                    <x-language-toggle />
                </div>
                <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100 pt-4">
                    @csrf
                    <button type="submit" class="btn btn-danger-outline w-full">
                        <x-lucide name="log-out" :size="16" />
                        {{ __('common.log_out') }}
                    </button>
                </form>
            </aside>
        </div>
    </div>
</x-layouts.supplier>
