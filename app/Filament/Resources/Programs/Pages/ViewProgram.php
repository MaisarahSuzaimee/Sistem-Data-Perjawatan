<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ViewProgram extends ViewRecord
{
    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Kemaskini'),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Paparan';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getHeading(): string|Htmlable
    {
        return new HtmlString(
            '<a href="'.e(ProgramResource::getUrl('index')).'" class="mystaff-back-btn" aria-label="Kembali">'
            .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>'
            .'</a>'
            .'<span>'.e($this->getTitle()).'</span>'
        );
    }
}
