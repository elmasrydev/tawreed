<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The admin overview, titled and iconed as in the design's sidebar.
 */
class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function getNavigationLabel(): string
    {
        return __('admin.overview');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.overview');
    }

    public function getSubheading(): ?string
    {
        return __('admin.overview_subheading');
    }
}
