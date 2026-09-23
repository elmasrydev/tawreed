<section class="card grid gap-x-6 gap-y-5 p-4 sm:grid-cols-2 sm:p-7">
    <div>
        <label for="quote_deadline" class="label">{{ __('ui.quote_deadline') }}</label>
        <input id="quote_deadline" type="date" wire:model.live="quote_deadline" min="{{ now()->addDay()->toDateString() }}"
               @if ($delivery_date) max="{{ $delivery_date }}" @endif
               @class(['input tabular', 'input-error' => $errors->has('quote_deadline')])>
        <p class="helper">{{ __('buyer.deadline_hint') }}</p>
        @error('quote_deadline') <p class="error-text">{{ $message }}</p> @enderror
    </div>

    <div>
        <p class="label">{{ __('buyer.quick_pick') }}</p>
        <div class="flex flex-wrap gap-2">
            @foreach ([1 => __('buyer.pick_tomorrow'), 2 => __('buyer.pick_days', ['count' => 2]), 5 => __('buyer.pick_days', ['count' => 5]), 7 => __('buyer.pick_week')] as $days => $label)
                <button type="button" wire:click="pickDeadline({{ $days }})"
                        @class(['choice h-10', 'choice-selected' => $quote_deadline === now()->addDays($days)->toDateString()])>
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="sm:col-span-2">
        <p class="label">{{ __('ui.attachments') }} <span class="font-normal text-gray-400">{{ __('ui.optional') }}</span></p>
        <x-file-drop wire:model="attachments" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" :hint="__('rfq.attach_hint')" id="attachments" />
        <p wire:loading wire:target="attachments" class="helper text-brand-500">{{ __('rfq.uploading') }}</p>
        @error('attachments') <p class="error-text">{{ $message }}</p> @enderror
        @error('attachments.*') <p class="error-text">{{ $message }}</p> @enderror

        @if ($this->savedAttachments->isNotEmpty())
            <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach ($this->savedAttachments as $media)
                    <li wire:key="saved-{{ $media->id }}">
                        <x-buyer.attachment :media="$media" removable>
                            <button type="button" wire:click="removeSavedAttachment({{ $media->id }})"
                                    class="btn btn-ghost btn-icon btn-sm shrink-0 text-danger-600"
                                    aria-label="{{ __('buyer.remove_file', ['name' => $media->file_name]) }}">
                                <x-lucide name="trash" :size="15" />
                            </button>
                        </x-buyer.attachment>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="label">{{ __('ui.notes') }} <span class="font-normal text-gray-400">{{ __('ui.optional') }}</span></label>
        <textarea id="notes" wire:model="notes" rows="4" @class(['input min-h-28 py-2.5 leading-relaxed', 'input-error' => $errors->has('notes')])
                  placeholder="{{ __('rfq.notes_placeholder') }}"></textarea>
        @error('notes') <p class="error-text">{{ $message }}</p> @enderror
    </div>
</section>
