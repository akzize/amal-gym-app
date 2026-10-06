<?php

namespace App\Filament\Pages;

use App\Support\DashboardMetrics;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends Page
{
    // Date range shared by every widget (as $this->pageFilters), kept in the URL and session
    use HasFiltersForm;

    protected static string $routePath = '/';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    DatePicker::make('from')
                        ->label(__('resources.dashboard.filter_from'))
                        ->placeholder(DashboardMetrics::defaultFrom()->toDateString())
                        ->maxDate(fn(Get $get) => $get('to')),
                    DatePicker::make('to')
                        ->label(__('resources.dashboard.filter_to'))
                        ->placeholder(DashboardMetrics::defaultTo()->toDateString())
                        ->minDate(fn(Get $get) => $get('from')),
                ])
                ->columnSpanFull(),
        ]);
    }

    protected static ?int $navigationSort = -2;

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ??
            static::$title ??
            __('filament-panels::pages/dashboard.title');
    }

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? (Filament::hasTopNavigation() ? Heroicon::Home : Heroicon::OutlinedHome);
    }

    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return Filament::getWidgets();
    }

    /**
     * @deprecated Use `getWidgetsSchemaComponents($this->getWidgets())` to transform widgets into schema components instead, which also filters their visibility.
     *
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int | array
    {
        return 2;
    }

    public function getTitle(): string | Htmlable
    {
        return static::$title ?? __('filament-panels::pages/dashboard.title');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...(method_exists($this, 'getFiltersForm') ? [$this->getFiltersFormContentComponent()] : []),
                $this->getWidgetsContentComponent(),
            ]);
    }

    public function getFiltersFormContentComponent(): Component
    {
        return EmbeddedSchema::make('filtersForm');
    }

    public function getWidgetsContentComponent(): Component
    {
        return Grid::make($this->getColumns())
            ->schema(fn(): array => $this->getWidgetsSchemaComponents($this->getWidgets()));
    }
}
