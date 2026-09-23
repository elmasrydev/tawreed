<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\MarketplaceStats;
use App\Filament\Widgets\SubscriptionRevenueChart;
use App\Filament\Widgets\VerificationQueue;
use App\Http\Middleware\SetLocale;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * TawreedHub palettes, hex-exact to the design system. Filament paints
     * primary buttons with shade 600 and their hover with 500, so navy sits at
     * 600 and the brand blue at 500.
     *
     * @var array<string, array<int, string>>
     */
    public const COLORS = [
        'primary' => [
            50 => '#F0F7FD', 100 => '#E3F0FA', 200 => '#BFDBFE', 300 => '#8DBDE4', 400 => '#4B8EC4',
            500 => '#1565A0', 600 => '#0B3D5C', 700 => '#093350', 800 => '#072940', 900 => '#051E2F', 950 => '#03131E',
        ],
        'info' => [
            50 => '#F0F7FD', 100 => '#E3F0FA', 200 => '#BFDBFE', 300 => '#93C2EA', 400 => '#4A90C8',
            500 => '#1565A0', 600 => '#135A8F', 700 => '#0F4A76', 800 => '#0B3D5C', 900 => '#08304A', 950 => '#051F30',
        ],
        'success' => [
            50 => '#F0FAF9', 100 => '#D9F2EF', 200 => '#A7E0DA', 300 => '#6FCCC2', 400 => '#3DB8AC',
            500 => '#17A398', 600 => '#128A80', 700 => '#0F7A72', 800 => '#0C625C', 900 => '#0A4F4A', 950 => '#06302D',
        ],
        'warning' => [
            50 => '#FFFBEB', 100 => '#FEF3C7', 200 => '#FDE68A', 300 => '#FCD34D', 400 => '#FBBF24',
            500 => '#F59E0B', 600 => '#D97706', 700 => '#B45309', 800 => '#92400E', 900 => '#78350F', 950 => '#451A03',
        ],
        'danger' => [
            50 => '#FEF2F2', 100 => '#FEE2E2', 200 => '#FECACA', 300 => '#FCA5A5', 400 => '#F87171',
            500 => '#EF4444', 600 => '#DC2626', 700 => '#B91C1C', 800 => '#991B1B', 900 => '#7F1D1D', 950 => '#450A0A',
        ],
        'gray' => [
            50 => '#F9FAFB', 100 => '#F3F4F6', 200 => '#E5E7EB', 300 => '#D1D5DB', 400 => '#9CA3AF',
            500 => '#6B7280', 600 => '#4B5563', 700 => '#374151', 800 => '#1F2937', 900 => '#111827', 950 => '#030712',
        ],
    ];

    public function register(): void
    {
        parent::register();

        // Phones get card-like rows instead of a sideways-scrolling table.
        Table::configureUsing(fn (Table $table): Table => $table->stackedOnMobile());
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('TawreedHub')
            ->brandLogo(fn (): View => view('filament.admin.brand-logo'))
            ->brandLogoHeight('2.125rem')
            ->favicon(asset('images/brand/mark-full-color.png'))
            ->colors(self::COLORS)
            ->darkMode(false)
            ->font('Inter', provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarWidth('14.5rem')
            ->maxContentWidth(Width::Full)
            ->collapsibleNavigationGroups(false)
            ->navigationGroups([
                NavigationGroup::make(fn (): string => __('admin.group_marketplace')),
                NavigationGroup::make(fn (): string => __('admin.group_billing')),
                NavigationGroup::make(fn (): string => __('admin.group_catalogue')),
                NavigationGroup::make(fn (): string => __('admin.group_moderation')),
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): Htmlable => app(Vite::class)->fonts())
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): View => view('filament.admin.language-toggle'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                MarketplaceStats::class,
                SubscriptionRevenueChart::class,
                VerificationQueue::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                SetLocale::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
