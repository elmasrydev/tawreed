<?php

namespace App\Filament\Resources\SupplierProfiles\Schemas;

use App\Models\Category;
use App\Models\Governorate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;

/**
 * The supplier company fields the admin fills in, shared by the "new user"
 * form and the supplier profile edit page.
 */
class SupplierProfileFields
{
    /**
     * @param  bool  $usesRelationships  true when editing an existing profile, so
     *                                   coverage and categories sync through the model
     * @return array<int, Component>
     */
    public static function make(bool $usesRelationships = false): array
    {
        return [
            TextInput::make('company_name')->label(__('ui.company_name'))->required()->maxLength(180),
            TextInput::make('commercial_reg_no')->label(__('ui.commercial_reg'))->required()->maxLength(60),
            TextInput::make('tax_number')->label(__('ui.tax_number'))->required()->maxLength(60),
            TextInput::make('years_active')->label(__('buyer.stat_years_active'))->integer()->minValue(0)->maxValue(200),
            TextInput::make('payment_method')->label(__('ui.payment_method'))->maxLength(120),
            TextInput::make('facility_address')->label(__('ui.facility_address'))->maxLength(255),
            Textarea::make('activity_description')->label(__('ui.activity_desc'))->maxLength(1000)->rows(3)->columnSpanFull(),
            self::multiSelect('governorate_ids', 'governorates', __('ui.coverage_areas'), Governorate::class, $usesRelationships)
                ->required(),
            self::multiSelect('category_ids', 'categories', __('ui.categories'), Category::class, $usesRelationships),
        ];
    }

    /**
     * @param  class-string<Model>  $model
     */
    private static function multiSelect(string $field, string $relationship, string $label, string $model, bool $usesRelationships): Select
    {
        $select = Select::make($usesRelationships ? $relationship : $field)
            ->label($label)
            ->multiple()
            ->searchable()
            ->preload();

        if ($usesRelationships) {
            return $select->relationship($relationship, 'name_ar', fn ($query) => $query->orderBy('sort'))
                ->getOptionLabelFromRecordUsing(fn (Model $record): string => $record->name);
        }

        return $select->options(fn (): array => $model::query()->orderBy('sort')->get()->pluck('name', 'id')->all());
    }
}
