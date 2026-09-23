<?php

namespace App\Filament\Resources\Testimonials;

use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/**
 * Landing-page testimonials. Only published rows are shown publicly, so the
 * section stays hidden until an admin adds a real one.
 */
class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'author_name';

    public static function getNavigationLabel(): string
    {
        return __('testimonials.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('testimonials.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('testimonials.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('testimonials.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('author_name')
                ->label(__('testimonials.author_name'))
                ->required()
                ->maxLength(120)
                ->columnSpanFull(),
            TextInput::make('author_role_ar')->label(__('testimonials.author_role_ar'))->maxLength(160),
            TextInput::make('author_role_en')->label(__('testimonials.author_role_en'))->maxLength(160),
            TextInput::make('location_ar')->label(__('testimonials.location_ar'))->maxLength(120),
            TextInput::make('location_en')->label(__('testimonials.location_en'))->maxLength(120),
            Textarea::make('quote_ar')->label(__('testimonials.quote_ar'))->required()->rows(4)->maxLength(600),
            Textarea::make('quote_en')->label(__('testimonials.quote_en'))->required()->rows(4)->maxLength(600),
            Select::make('rating')
                ->label(__('testimonials.rating'))
                ->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★'])
                ->placeholder('—'),
            TextInput::make('sort')->label(__('testimonials.sort'))->numeric()->minValue(0)->default(0),
            Toggle::make('is_published')
                ->label(__('testimonials.is_published'))
                ->helperText(__('testimonials.is_published_hint'))
                ->default(false)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('author_name')
                    ->label(__('testimonials.author_name'))
                    ->description(fn (Testimonial $record): ?string => $record->author_role)
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('quote_ar')
                    ->label(__('testimonials.quote'))
                    ->getStateUsing(fn (Testimonial $record): string => $record->quote)
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('rating')
                    ->label(__('testimonials.rating'))
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('★', $state) : '—')
                    ->placeholder('—'),
                TextColumn::make('sort')->label(__('testimonials.sort'))->numeric()->sortable(),
                ToggleColumn::make('is_published')->label(__('testimonials.is_published')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->headerActions([CreateAction::make()])
            ->emptyStateHeading(__('testimonials.empty_heading'))
            ->emptyStateDescription(__('testimonials.empty_body'));
    }

    public static function getPages(): array
    {
        return ['index' => ListTestimonials::route('/')];
    }
}
