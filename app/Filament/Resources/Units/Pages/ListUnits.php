<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use illuminate\Support\HtmlString;
use App\Models\Ptj;
use App\Models\Unit;
use Filament\Actions;

class ListUnits extends ListRecords
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Jabatan / KK / KP'),
            // // ->modal()
            // // ->createAnother(false),
            // ->disabled(fn () => $this->cannotAddUnit())
            // ->tooltip(fn () => $this->cannotAddUnit()
            //     ? 'PTJ anda sudah mempunyai unit. Sila kemaskini senarai sedia ada.'
            //     : null),
        ];
    }

    protected function cannotAddUnit(): bool
    {
        $user = auth()->user();

        // Admin / superadmin: disabled only when every PTJ already has units
        if (in_array($user->role, ['admin', 'superadmin'])) {
            return ! Ptj::whereNotIn('id', Unit::query()->select('ptj_id'))->exists();
        }

        // Normal user: disabled when their own PTJ already has units
        return Unit::where('ptj_id', $user->ptj_id)->exists();
    }

    public function getBreadcrumb(): string
    {
        return 'Senarai';
    }

    public function getBreadcrumbs(): array
    {
        // The back button (see getHeading()) replaces the need for a
        // breadcrumb trail on this page.
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
