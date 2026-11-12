<?php

namespace App\Filament\Pages;

use App\Services\DashboardStatsCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = -2;

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('today')
                ->label(now()->locale('ms')->translatedFormat('d F Y'))
                ->disabled()
                ->color('gray')
                ->extraAttributes([
                    'style' => 'background:transparent;border:none;box-shadow:none;padding:0;cursor:default;opacity:1;font-weight:500;',
                ]),
        ];
    }

    public function getViewData(): array
    {
        return DashboardStatsCache::get();
    }
}
