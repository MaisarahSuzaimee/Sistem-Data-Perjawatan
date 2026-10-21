<?php

namespace App\Filament\Resources\Parlimens\Pages;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Parlimens\ParlimenResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ListParlimens extends ListRecords
{
    protected static string $resource = ParlimenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Parlimen'),
            Action::make('exportFasiliti')
                ->label('Export Fasiliti')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('export')
                ->url(route('export.fasilitiByParlimen'))
                ->openUrlInNewTab(),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Senarai';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getHeading(): string|Htmlable
    {
        return new HtmlString(
            '<a href="'.e(Dashboard::getUrl()).'" class="mystaff-back-btn" aria-label="Kembali ke Dashboard">'.
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>'.
            '</a>'.
            '<span>'.e($this->getTitle()).'</span>'
        );
    }
}
