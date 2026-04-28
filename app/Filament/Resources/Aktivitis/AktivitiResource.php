<?php

namespace App\Filament\Resources\Aktivitis;

use App\Filament\Resources\Aktivitis\Pages\CreateAktiviti;
use App\Filament\Resources\Aktivitis\Pages\EditAktiviti;
use App\Filament\Resources\Aktivitis\Pages\ListAktivitis;
use App\Filament\Resources\Aktivitis\Schemas\AktivitiForm;
use App\Filament\Resources\Aktivitis\Tables\AktivitisTable;
use App\Models\Aktiviti;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AktivitiResource extends Resource
{
    protected static ?string $model = Aktiviti::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nama_aktiviti';

    public static function form(Schema $schema): Schema
    {
        return AktivitiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AktivitisTable::configure($table);
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
            'index' => ListAktivitis::route('/'),
            'create' => CreateAktiviti::route('/create'),
            'edit' => EditAktiviti::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
{
    return Aktiviti::query()
        ->with(['program', 'butiran']);
}
}
