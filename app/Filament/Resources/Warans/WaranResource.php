<?php

namespace App\Filament\Resources\Warans;

use App\Filament\Resources\Warans\Pages\CreateWaran;
use App\Filament\Resources\Warans\Pages\EditWaran;
use App\Filament\Resources\Warans\Pages\ListWarans;
use App\Filament\Resources\Warans\Schemas\WaranForm;
use App\Filament\Resources\Warans\Tables\WaransTable;
use App\Models\Waran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WaranResource extends Resource
{
    protected static ?string $model = Waran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'no_waran';

    public static function form(Schema $schema): Schema
    {
        return WaranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WaransTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarans::route('/'),
            'create' => CreateWaran::route('/create'),
            'edit' => EditWaran::route('/{record}/edit'),
        ];
    }
}
