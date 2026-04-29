<?php

namespace App\Filament\Resources\ButiranJawatans;

use App\Filament\Resources\ButiranJawatans\Pages\CreateButiranJawatan;
use App\Filament\Resources\ButiranJawatans\Pages\EditButiranJawatan;
use App\Filament\Resources\ButiranJawatans\Pages\ListButiranJawatans;
use App\Filament\Resources\ButiranJawatans\Schemas\ButiranJawatanForm;
use App\Filament\Resources\ButiranJawatans\Tables\ButiranJawatansTable;
use App\Models\Butiran_Jawatan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ButiranJawatanResource extends Resource
{
    protected static ?string $model = Butiran_Jawatan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ButiranJawatanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ButiranJawatansTable::configure($table);
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
            'index' => ListButiranJawatans::route('/'),
            'create' => CreateButiranJawatan::route('/create'),
            'edit' => EditButiranJawatan::route('/{record}/edit'),
        ];
    }
}
