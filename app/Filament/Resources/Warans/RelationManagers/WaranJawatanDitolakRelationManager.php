<?php

namespace App\Filament\Resources\Warans\RelationManagers;

class WaranJawatanDitolakRelationManager extends WaranJawatansRelationManager
{
    protected static string $relationship = 'waranTolakJawatan';

    protected static ?string $title = 'Ditolak';

    public string $panelMode = 'ditolak';

    public string $viewMode = 'catalog';
}
