<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name_ar';

    public static function getNavigationLabel(): string
    {
        return __('admin.categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.group_catalogue');
    }

    public static function getModelLabel(): string
    {
        return __('admin.category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.categories');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_ar')->label('الاسم بالعربية')->required()->maxLength(120),
            TextInput::make('name_en')->label('Name in English')->required()->maxLength(120),
            Select::make('parent_id')
                ->label(__('ui.main_category'))
                ->relationship('parent', 'name_ar')
                ->searchable()
                ->placeholder('—'),
            FileUpload::make('image_path')
                ->label(__('landing.admin_category_image'))
                ->helperText(__('landing.admin_category_image_hint'))
                ->image()
                ->disk(Category::IMAGE_DISK)
                ->directory('categories')
                ->visibility('public')
                ->maxSize(2048)
                ->columnSpanFull(),
            TextInput::make('sort')->label(__('admin.days'))->numeric()->default(0),
            Toggle::make('is_active')->label(__('ui.account_status'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('rfqs')->with('parent'))
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('image_path')
                    ->label(__('landing.admin_category_image'))
                    ->disk(Category::IMAGE_DISK)
                    ->imageHeight(36),
                TextColumn::make('name_ar')
                    ->label(__('admin.category'))
                    ->getStateUsing(fn (Category $record): string => $record->name)
                    ->searchable(['name_ar', 'name_en'])
                    ->weight('bold'),
                TextColumn::make('parent.name_ar')
                    ->label(__('ui.main_category'))
                    ->getStateUsing(fn (Category $record): ?string => $record->parent?->name)
                    ->placeholder('—'),
                TextColumn::make('rfqs_count')->label(__('admin.rfqs'))->numeric()->sortable(),
                IconColumn::make('is_active')->label(__('ui.account_status'))->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(function (array $data): array {
                    $data['slug'] ??= Str::slug($data['name_en']);

                    return $data;
                }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCategories::route('/')];
    }
}
