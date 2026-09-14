<?php

namespace App\Filament\Resources\Ptjs\Pages;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Ptjs\PtjResource;
use App\Models\Program;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ListPtjs extends ListRecords
{
    protected static string $resource = PtjResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah PTJ'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('ALL'),
        ];

        foreach (Program::orderBy('nama_program')->get() as $program) {
            $tabs['program_'.$program->id] = Tab::make($program->nama_program)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('programs', fn (Builder $q) => $q->where('programs.id', $program->id)));
        }

        // $tabs['tiada'] = Tab::make('Tiada Program')
        //     ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('programs'));

        return $tabs;
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
