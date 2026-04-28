<?php

namespace App\Filament\Resources\Butirans;

use App\Filament\Resources\Butirans\Pages\CreateButiran;
use App\Filament\Resources\Butirans\Pages\EditButiran;
use App\Filament\Resources\Butirans\Pages\ListButirans;
use App\Filament\Resources\Butirans\Schemas\ButiranForm;
use App\Filament\Resources\Butirans\Tables\ButiransTable;
use App\Models\Butiran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ButiranResource extends Resource
{
    protected static ?string $model = Butiran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'butiran';

    public static function form(Schema $schema): Schema
    {
        return ButiranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ButiransTable::configure($table);
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
            'index' => ListButirans::route('/'),
            'create' => CreateButiran::route('/create'),
            'edit' => EditButiran::route('/{record}/edit'),
        ];
    }
}
