<?php

use App\Actions\Quote\SubmitQuote;
use App\Enums\VatMode;
use App\Models\Rfq;
use App\Support\ContactDetailDetector;
use App\ViewModels\AnonymousRfq;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The quote form. Every field the prototype asks for, plus a check that the
 * supplier has not written contact details into the free text.
 */
new #[Layout('layouts::supplier', ['current' => 'feed'])] class extends Component
{
    public Rfq $rfq;

    /**
     * True when rendered inside the slide-over on the request page rather
     * than as the full-page deep link.
     */
    public bool $embedded = false;

    public string $unit_price = '';

    public string $total_price = '';

    public string $min_order_qty = '';

    public string $vat_mode = 'included';

    public string $vat_amount = '';

    public string $delivery_cost = '0';

    public string $expected_delivery_date = '';

    public string $validity_days = '10';

    public string $payment_terms = '';

    public string $sample_availability = '';

    public string $brand_origin = '';

    public string $extra_specs = '';

    public string $warranty_policy = '';

    public function mount(Rfq $rfq): void
    {
        Gate::authorize('quote', $rfq);

        $this->rfq = $rfq;
    }

    #[Computed]
    public function item(): AnonymousRfq
    {
        return AnonymousRfq::make($this->rfq);
    }

    /**
     * Mirrors the buyer's comparison figure so the supplier sees the same total.
     */
    #[Computed]
    public function grandTotal(): float
    {
        $vat = $this->vat_mode === VatMode::Excluded->value ? (float) $this->vat_amount : 0.0;

        return (float) $this->total_price + $vat + (float) $this->delivery_cost;
    }

    /**
     * The date the quote stops being valid if it were sent today.
     */
    #[Computed]
    public function validUntil(): ?CarbonInterface
    {
        $days = (int) $this->validity_days;

        return $days > 0 && $days <= 120 ? today()->addDays($days) : null;
    }

    #[Computed]
    public function deliveryDate(): ?CarbonInterface
    {
        return rescue(fn (): ?CarbonInterface => $this->expected_delivery_date !== '' ? Carbon::parse($this->expected_delivery_date) : null, null, false);
    }

    public function submit(SubmitQuote $submitQuote, ContactDetailDetector $detector): void
    {
        Gate::authorize('quote', $this->rfq);

        $validated = $this->validate();

        $offenders = $detector->offendingFields([
            'extra_specs' => $this->extra_specs,
            'warranty_policy' => $this->warranty_policy,
            'payment_terms' => $this->payment_terms,
            'sample_availability' => $this->sample_availability,
            'brand_origin' => $this->brand_origin,
        ]);

        if ($offenders !== []) {
            foreach ($offenders as $field) {
                $this->addError($field, __('quote.no_contact_details'));
            }

            return;
        }

        $submitQuote->handle($this->rfq, auth()->user(), $this->withBlanksAsNull($validated));

        session()->flash('status', __('quote.submitted'));

        $this->redirectRoute('supplier.quotes.index', navigate: true);
    }

    /**
     * Optional numeric fields arrive from the form as empty strings, which a
     * decimal column rejects. Blank optional values are stored as null.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withBlanksAsNull(array $data): array
    {
        foreach (['min_order_qty', 'vat_amount', 'payment_terms', 'sample_availability', 'brand_origin', 'extra_specs', 'warranty_policy'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'unit_price' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'total_price' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'min_order_qty' => ['nullable', 'numeric', 'gt:0'],
            'vat_mode' => ['required', Rule::enum(VatMode::class)],
            'vat_amount' => ['nullable', 'numeric', 'min:0', 'required_if:vat_mode,excluded'],
            'delivery_cost' => ['required', 'numeric', 'min:0'],
            'expected_delivery_date' => ['required', 'date', 'after:today'],
            'validity_days' => ['required', 'integer', 'min:1', 'max:120'],
            'payment_terms' => ['nullable', 'string', 'max:180'],
            'sample_availability' => ['nullable', 'string', 'max:180'],
            'brand_origin' => ['nullable', 'string', 'max:180'],
            'extra_specs' => ['nullable', 'string', 'max:2000'],
            'warranty_policy' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'unit_price' => __('ui.unit_price'),
            'total_price' => __('ui.total_price'),
            'min_order_qty' => __('ui.min_order_qty'),
            'vat_amount' => __('ui.vat_amount'),
            'delivery_cost' => __('ui.delivery_cost'),
            'expected_delivery_date' => __('ui.expected_delivery'),
            'validity_days' => __('ui.validity_days'),
        ];
    }
};
?>

@php
    $vatExcluded = $vat_mode === App\Enums\VatMode::Excluded->value;
    $unit = $this->item->unit;
    $egp = __('common.egp');
    $subtitle = collect([$this->item->title, $this->item->quantityWithUnit(), $this->item->buyerLabel()])->filter()->implode(' · ');
@endphp

<div @class(['flex h-full min-h-0 flex-col' => $embedded, 'shell-page' => ! $embedded])>
    @unless ($embedded)
        <a href="{{ route('supplier.rfqs.show', $rfq) }}" wire:navigate
           class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-gray-500 hover:text-brand-700">
            <x-lucide name="arrow-left" :size="14" />
            {{ __('quote.back_to_request') }}
        </a>
    @endunless

    <form wire:submit="submit" novalidate @class([
        'flex flex-col lg:grid lg:grid-cols-[minmax(0,1fr)_280px]',
        'min-h-0 flex-1' => $embedded,
        'card overflow-clip' => ! $embedded,
    ])>
        <div @class(['flex min-w-0 flex-col', 'min-h-0 flex-1 overflow-y-auto overscroll-contain' => $embedded])>
            <header @class([
                'z-10 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-4 py-4 sm:px-6 sm:py-5',
                'sticky top-0' => $embedded,
            ])>
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-brand-700">{{ __('ui.submit_quote') }}</h2>
                    <p class="text-[13px] text-gray-500 sm:truncate">{{ $subtitle }}</p>
                </div>
                @if ($embedded)
                    <button type="button" class="btn btn-secondary btn-icon shrink-0 text-gray-500"
                            x-on:click="$dispatch('close-drawer', 'submit-quote')" aria-label="{{ __('common.close') }}">
                        <x-lucide name="x" :size="16" />
                    </button>
                @endif
            </header>

            <div class="grid gap-x-5 gap-y-4 px-4 py-5 sm:grid-cols-2 sm:px-6">
                <x-supplier.quote-input name="unit_price" type="number" step="any" min="0" inputmode="decimal" required
                                        :label="__('ui.unit_price')" :suffix="$egp . ($unit !== '' ? ' / ' . $unit : '')"
                                        wire:model.live.debounce.400ms="unit_price" placeholder="185" />

                <x-supplier.quote-input name="total_price" type="number" step="any" min="0" inputmode="decimal" required
                                        :label="__('ui.total_price')" :suffix="$egp"
                                        :hint="__('quote.total_hint', ['quantity' => $this->item->quantityWithUnit()])"
                                        wire:model.live.debounce.400ms="total_price" placeholder="92500" />

                <x-supplier.quote-input name="min_order_qty" type="number" step="any" min="0" inputmode="decimal"
                                        :label="__('ui.min_order_qty')" :suffix="$unit !== '' ? $unit : null"
                                        wire:model="min_order_qty" placeholder="100" />

                <x-supplier.quote-input name="delivery_cost" type="number" step="any" min="0" inputmode="decimal" required
                                        :label="__('ui.delivery_cost')" :suffix="$egp"
                                        wire:model.live.debounce.400ms="delivery_cost" />

                <fieldset>
                    <legend class="label">{{ __('ui.vat') }}</legend>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach (App\Enums\VatMode::cases() as $mode)
                            <label class="choice">
                                <input type="radio" name="vat_mode" value="{{ $mode->value }}" wire:model.live="vat_mode" class="sr-only">
                                {{ $mode->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('vat_mode') <p class="error-text">{{ $message }}</p> @enderror
                </fieldset>

                @if ($vatExcluded)
                    <x-supplier.quote-input name="vat_amount" type="number" step="any" min="0" inputmode="decimal" required
                                            :label="__('ui.vat_amount')" :suffix="$egp"
                                            wire:model.live.debounce.400ms="vat_amount" placeholder="12950" />
                @else
                    <p class="hidden self-end pb-2.5 text-xs text-gray-500 sm:block">{{ __('quote.vat_included_hint') }}</p>
                @endif

                <x-supplier.quote-input name="expected_delivery_date" type="date" required
                                        :min="today()->addDay()->toDateString()"
                                        :label="__('ui.expected_delivery')"
                                        :hint="__('quote.buyer_needs_by', ['date' => $this->item->deliveryDate])"
                                        wire:model.live="expected_delivery_date" />

                <x-supplier.quote-input name="validity_days" type="number" min="1" max="120" inputmode="numeric" required
                                        :label="__('quote.validity')" :suffix="__('quote.days')"
                                        wire:model.live.debounce.400ms="validity_days" />

                <x-supplier.quote-input name="payment_terms" :label="__('ui.payment_terms')"
                                        wire:model="payment_terms" :placeholder="__('ui.payment_terms_placeholder')" />

                <x-supplier.quote-input name="sample_availability" :label="__('ui.sample')"
                                        wire:model="sample_availability" :placeholder="__('ui.sample_placeholder')" />

                <x-supplier.quote-input name="brand_origin" :label="__('ui.brand_origin')" class="sm:col-span-2"
                                        wire:model="brand_origin" :placeholder="__('ui.brand_origin_placeholder')" />

                <x-supplier.quote-input name="warranty_policy" type="textarea" rows="2" :label="__('ui.warranty')" class="sm:col-span-2"
                                        wire:model="warranty_policy" :placeholder="__('ui.warranty_placeholder')" />

                <x-supplier.quote-input name="extra_specs" type="textarea" rows="3" :label="__('ui.extra_specs')" class="sm:col-span-2"
                                        wire:model="extra_specs" />
            </div>

            @php
                $summary = [
                    'subtotal' => (float) $total_price,
                    'vat' => $vatExcluded ? (float) $vat_amount : null,
                    'delivery' => (float) $delivery_cost,
                    'grandTotal' => $this->grandTotal,
                    'deliveryDate' => $this->deliveryDate,
                    'validUntil' => $this->validUntil,
                ];
            @endphp

            {{-- Phones and tablets: the breakdown sits under the fields --}}
            <x-supplier.quote-summary class="mx-4 mb-5 rounded-card border border-gray-200 bg-gray-50 p-4 sm:mx-6 lg:hidden"
                                      :subtotal="$summary['subtotal']" :vat="$summary['vat']" :delivery="$summary['delivery']"
                                      :grand-total="$summary['grandTotal']" :delivery-date="$summary['deliveryDate']" :valid-until="$summary['validUntil']" />
        </div>

        {{-- Desktop: live summary column with the submit button --}}
        <aside class="hidden border-s border-gray-200 bg-gray-50 lg:block">
            <div @class(['flex flex-col gap-5 px-5 py-6', 'h-full' => $embedded, 'sticky top-[60px]' => ! $embedded])>
                <x-supplier.quote-summary
                    :subtotal="$summary['subtotal']" :vat="$summary['vat']" :delivery="$summary['delivery']"
                    :grand-total="$summary['grandTotal']" :delivery-date="$summary['deliveryDate']" :valid-until="$summary['validUntil']" />

                <button type="submit" class="btn btn-accent btn-lg mt-auto w-full" wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">{{ __('ui.send_quote') }}</span>
                    <span wire:loading wire:target="submit">{{ __('quote.sending') }}</span>
                </button>
            </div>
        </aside>

        {{-- Phones and tablets: sticky total and submit --}}
        <div @class([
            'flex items-center gap-3 border-t border-gray-200 bg-white px-4 py-3 lg:hidden',
            'pb-[max(0.75rem,env(safe-area-inset-bottom))]' => $embedded,
            'sticky bottom-[calc(58px+env(safe-area-inset-bottom))] z-10' => ! $embedded,
        ])>
            <div class="min-w-0 flex-1">
                <p class="eyebrow">{{ __('quote.grand_total') }}</p>
                <p class="truncate text-lg font-bold text-brand-700 tabular">{{ number_format($this->grandTotal, 2) }} {{ $egp }}</p>
            </div>
            <button type="submit" class="btn btn-accent h-11 shrink-0" wire:loading.attr="disabled" wire:target="submit">
                {{ __('ui.send_quote') }}
            </button>
        </div>
    </form>
</div>
