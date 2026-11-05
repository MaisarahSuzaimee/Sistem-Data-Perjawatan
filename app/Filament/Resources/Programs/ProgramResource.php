<?php

namespace App\Filament\Resources\Programs;

use App\Filament\Resources\Programs\Pages\CreateProgram;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\Pages\ListPrograms;
use App\Filament\Resources\Programs\Pages\ViewProgram;
use App\Filament\Resources\Programs\Schemas\ProgramForm;
use App\Filament\Resources\Programs\Tables\ProgramsTable;
use App\Models\Program;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $recordTitleAttribute = 'nama_program';

    protected static ?string $navigationLabel = 'Program & Aktiviti';

    protected static ?string $modelLabel = 'Program & Aktiviti';

    protected static ?string $pluralModelLabel = 'Program & Aktiviti';

    protected static string|\UnitEnum|null $navigationGroup = 'Kawalan';

    protected static ?int $navigationSort = 19;

    public static function form(Schema $schema): Schema
    {
        return ProgramForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return static::canManage();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManage();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrograms::route('/'),
            'create' => CreateProgram::route('/create'),
            'view' => ViewProgram::route('/{record}'),
            'edit' => EditProgram::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $ptjId = $user?->isUser() && filled($user->ptj_id)
            ? (int) $user->ptj_id
            : null;

        $query = parent::getEloquentQuery()
            ->with([
                'aktiviti' => function ($query) use ($ptjId): void {
                    if ($ptjId !== null) {
                        $query->assignedToPtj($ptjId);
                    }
                },
                'aktiviti.ptjs' => function ($query) use ($ptjId): void {
                    if ($ptjId !== null) {
                        $query->where('ptjs.id', $ptjId);
                    }
                },
                'aktiviti.butiran',
                'ptjs.aktivitis',
            ]);

        if ($user?->isUser()) {
            if ($ptjId === null) {
                return $query->whereRaw('0 = 1');
            }

            $query->assignedToPtj($ptjId);
        }

        return $query;
    }

    protected static function canManage(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isAdmin();
    }
}
