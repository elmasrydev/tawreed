<?php

use App\Actions\Rfq\PublishRfq;
use App\Enums\RfqStatus;
use App\Enums\SupplyType;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Rfq;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The request wizard: three input steps and a review step. Each step validates
 * on its own so a buyer is never sent back to the start to fix one field. The
 * buyer may save a draft at any point and resume it later from My RFQs.
 */
new #[Layout('layouts::buyer', ['current' => 'requests'])] class extends Component
{
    use WithFileUploads;

    public const LAST_STEP = 4;

    /** How many files one request may carry, saved and new together. */
    public const MAX_ATTACHMENTS = 6;

    public int $step = 1;

    /** The draft being resumed, if any. Saving and publishing update this row. */
    #[Locked]
    public ?int $draftId = null;

    // Step 1 — product and specifications.
    public string $title = '';

    public ?int $category_id = null;

    public ?int $subcategory_id = null;

    public string $specs = '';

    // Step 2 — quantity and delivery.
    public string $quantity = '';

    public ?int $unit_id = null;

    public ?int $governorate_id = null;

    public string $delivery_date = '';

    public string $supply_type = 'one_time';

    public string $recurrence_note = '';

    // Step 3 — attachments, deadline and notes.
    public array $attachments = [];

    public string $quote_deadline = '';

    public string $notes = '';

    public function mount(?Rfq $rfq = null): void
    {
        $this->authorize('create', Rfq::class);

        if ($rfq?->exists) {
            $this->authorize('editDraft', $rfq);
            $this->fillFromDraft($rfq);
        }
    }

    #[Computed]
    public function mainCategories(): Collection
    {
        return Category::query()->main()->get();
    }

    #[Computed]
    public function subcategories(): Collection
    {
        return $this->category_id
            ? Category::query()->where('parent_id', $this->category_id)->where('is_active', true)->orderBy('sort')->get()
            : collect();
    }

    #[Computed]
    public function units(): Collection
    {
        return Unit::query()->orderBy('sort')->get();
    }

    #[Computed]
    public function governorates(): Collection
    {
        return Governorate::query()->orderBy('sort')->get();
    }

    /**
     * Files already saved on the draft being resumed.
     */
    #[Computed]
    public function savedAttachments(): Collection
    {
        return $this->draftId
            ? Rfq::query()->whereKey($this->draftId)->first()?->getMedia('attachments') ?? collect()
            : collect();
    }

    /**
     * Changing the main category invalidates any sub-category already picked.
     */
    public function updatedCategoryId(): void
    {
        $this->subcategory_id = null;
    }

    public function next(): void
    {
        if ($this->step < self::LAST_STEP) {
            $this->validate($this->rulesForStep($this->step));
            $this->step++;

            return;
        }

        $this->publish();
    }

    public function previous(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    /**
     * Jumps back to an earlier step, e.g. from an "Edit" link on the review.
     */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step < $this->step) {
            $this->step = $step;
        }
    }

    /**
     * Sets the quote deadline a given number of days from today.
     */
    public function pickDeadline(int $days): void
    {
        $this->quote_deadline = now()->addDays(max(1, $days))->toDateString();
    }

    /**
     * Keeps whatever the buyer has entered as a draft. Only the title is
     * required; anything else present must still be valid.
     */
    public function saveDraft(): void
    {
        $this->validate($this->draftRules());

        $this->persist();

        session()->flash('status', __('buyer.draft_saved'));

        $this->redirectRoute('buyer.rfqs.index', ['status' => RfqStatus::Draft->value], navigate: true);
    }

    public function removeSavedAttachment(int $mediaId): void
    {
        $rfq = Rfq::query()->whereKey($this->draftId)->firstOrFail();
        $this->authorize('editDraft', $rfq);

        $rfq->getMedia('attachments')->firstWhere('id', $mediaId)?->delete();

        unset($this->savedAttachments);
    }

    private function publish(): void
    {
        foreach (range(1, self::LAST_STEP - 1) as $step) {
            try {
                $this->validate($this->rulesForStep($step));
            } catch (ValidationException $exception) {
                $this->step = $step;

                throw $exception;
            }
        }

        app(PublishRfq::class)->handle($this->persist());

        session()->flash('status', __('rfq.published'));

        $this->redirectRoute('buyer.rfqs.index', navigate: true);
    }

    /**
     * Writes the wizard into the draft row, creating it on first save, and
     * attaches any newly uploaded files.
     */
    private function persist(): Rfq
    {
        $attributes = [
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'unit_id' => $this->unit_id,
            'governorate_id' => $this->governorate_id,
            'title' => $this->title,
            'specs' => $this->nullIfBlank($this->specs),
            'quantity' => $this->nullIfBlank($this->quantity),
            'delivery_date' => $this->nullIfBlank($this->delivery_date),
            'supply_type' => $this->supply_type,
            'recurrence_note' => $this->supply_type === SupplyType::Recurring->value
                ? $this->nullIfBlank($this->recurrence_note)
                : null,
            'quote_deadline' => $this->nullIfBlank($this->quote_deadline),
            'notes' => $this->nullIfBlank($this->notes),
        ];

        if ($this->draftId) {
            $rfq = Rfq::query()->whereKey($this->draftId)->firstOrFail();
            $this->authorize('editDraft', $rfq);
            $rfq->update($attributes);
        } else {
            $rfq = Rfq::create([...$attributes, 'buyer_id' => auth()->id(), 'status' => RfqStatus::Draft]);
            $this->draftId = $rfq->id;
        }

        foreach ($this->attachments as $attachment) {
            $rfq->addMedia($attachment->getRealPath())
                ->usingFileName($attachment->getClientOriginalName())
                ->toMediaCollection('attachments');
        }

        $this->attachments = [];

        return $rfq;
    }

    private function fillFromDraft(Rfq $rfq): void
    {
        $this->draftId = $rfq->id;
        $this->title = $rfq->title;
        $this->category_id = $rfq->category_id;
        $this->subcategory_id = $rfq->subcategory_id;
        $this->specs = (string) $rfq->specs;
        $this->quantity = (string) ($rfq->formattedQuantity() !== null ? str_replace(',', '', $rfq->formattedQuantity()) : '');
        $this->unit_id = $rfq->unit_id;
        $this->governorate_id = $rfq->governorate_id;
        $this->delivery_date = (string) $rfq->delivery_date?->toDateString();
        $this->supply_type = $rfq->supply_type->value;
        $this->recurrence_note = (string) $rfq->recurrence_note;
        $this->quote_deadline = (string) $rfq->quote_deadline?->toDateString();
        $this->notes = (string) $rfq->notes;
    }

    private function nullIfBlank(string $value): ?string
    {
        return trim($value) === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'title' => ['required', 'string', 'max:180'],
                'category_id' => ['required', Rule::exists('categories', 'id')->whereNull('parent_id')],
                'subcategory_id' => ['nullable', Rule::exists('categories', 'id')->where('parent_id', $this->category_id)],
                'specs' => ['required', 'string', 'min:20', 'max:4000'],
            ],
            2 => [
                'quantity' => ['required', 'numeric', 'gt:0', 'max:99999999'],
                'unit_id' => ['required', 'exists:units,id'],
                'governorate_id' => ['required', 'exists:governorates,id'],
                'delivery_date' => ['required', 'date', 'after:today'],
                'supply_type' => ['required', Rule::enum(SupplyType::class)],
                'recurrence_note' => ['nullable', 'string', 'max:180'],
            ],
            default => [
                ...$this->attachmentRules(),
                'quote_deadline' => ['required', 'date', 'after:today', 'before_or_equal:'.$this->delivery_date],
                'notes' => ['nullable', 'string', 'max:2000'],
            ],
        };
    }

    /**
     * Drafts need only a title, but anything already entered must be valid.
     *
     * @return array<string, mixed>
     */
    private function draftRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('parent_id')],
            'subcategory_id' => ['nullable', Rule::exists('categories', 'id')->where('parent_id', $this->category_id)],
            'specs' => ['nullable', 'string', 'max:4000'],
            'quantity' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'delivery_date' => ['nullable', 'date', 'after:today'],
            'supply_type' => ['required', Rule::enum(SupplyType::class)],
            'recurrence_note' => ['nullable', 'string', 'max:180'],
            ...$this->attachmentRules(),
            'quote_deadline' => array_filter([
                'nullable', 'date', 'after:today',
                filled($this->delivery_date) ? 'before_or_equal:'.$this->delivery_date : null,
            ]),
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attachmentRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:'.max(0, self::MAX_ATTACHMENTS - $this->savedAttachments->count())],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'title' => __('ui.product_name'),
            'category_id' => __('ui.main_category'),
            'subcategory_id' => __('ui.sub_category'),
            'specs' => __('ui.specs'),
            'quantity' => __('ui.quantity'),
            'unit_id' => __('ui.unit'),
            'governorate_id' => __('ui.delivery_governorate'),
            'delivery_date' => __('ui.delivery_date'),
            'quote_deadline' => __('ui.quote_deadline'),
        ];
    }

    /**
     * The values the review step and the supplier preview show.
     *
     * @return array{category: ?string, unit: ?string, governorate: ?string, deadline: ?Carbon}
     */
    public function reviewValues(): array
    {
        $category = $this->mainCategories->firstWhere('id', $this->category_id);
        $subcategory = $this->subcategories->firstWhere('id', $this->subcategory_id);

        return [
            'category' => $category ? collect([$category->name, $subcategory?->name])->filter()->implode(' › ') : null,
            'unit' => $this->units->firstWhere('id', $this->unit_id)?->name,
            'governorate' => $this->governorates->firstWhere('id', $this->governorate_id)?->name,
            'deadline' => filled($this->quote_deadline) ? Carbon::parse($this->quote_deadline) : null,
        ];
    }
};
?>

@php
    $stepLabels = [__('buyer.step_product'), __('buyer.step_quantity'), __('buyer.step_terms'), __('buyer.step_review')];
@endphp

<div class="shell-page max-w-[1040px]">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('buyer.rfqs.index') }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-[13px] font-medium text-gray-500 hover:text-brand-700">
                <x-lucide name="arrow-left" :size="14" />
                {{ __('common.nav_my_rfqs') }}
            </a>
            <h1 class="mt-1 text-xl font-bold tracking-[-0.3px] text-brand-700 sm:text-2xl">
                {{ $draftId ? __('buyer.edit_draft_title') : __('buyer.new_rfq_title') }}
            </h1>
        </div>
        <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft,attachments" class="btn btn-secondary">
            <x-lucide name="save" :size="15" />
            {{ __('buyer.save_draft') }}
        </button>
    </div>

    <x-stepper :steps="$stepLabels" :current="$step" />

    <form wire:submit="next" class="flex flex-col gap-5">
        @include('livewire.buyer.partials.rfq-step-'.$step)

        <div class="flex items-center justify-between gap-3">
            <button type="button" wire:click="previous" @class(['btn btn-secondary', 'invisible' => $step === 1])>
                <x-lucide name="arrow-left" :size="14" />
                {{ __('ui.previous') }}
            </button>

            @if ($step === 4)
                <button type="submit" class="btn btn-accent" wire:loading.attr="disabled" wire:target="next,attachments">
                    <x-lucide name="send" :size="15" />
                    {{ __('buyer.post_rfq') }}
                </button>
            @else
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="next,attachments">
                    {{ __('buyer.continue') }}
                    <x-lucide name="arrow-right" :size="14" />
                </button>
            @endif
        </div>
    </form>
</div>
