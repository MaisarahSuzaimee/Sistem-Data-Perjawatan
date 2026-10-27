<?php

namespace App\Filament\Pages;

use App\Services\DatabaseBackupService;
use App\Models\BackupHistory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Page
{
    protected string $view = 'filament.pages.database-backup';

    protected static BackedEnum|string|null $navigationIcon =
        Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Database Backup';

    protected static ?string $title = 'Database Backup';

    protected static string|\UnitEnum|null $navigationGroup = 'Kawalan';

    protected static ?int $navigationSort = 34;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBackup')
                ->label('Create Backup')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Create Database Backup')
                ->modalDescription(
                    'Create a compressed backup of the current MySTAFF database?'
                )
                ->modalSubmitActionLabel('Create Backup')
                ->action(function (): void {
                    try {
                        $backup = app(DatabaseBackupService::class)->create();

                        BackupHistory::create([
                            'filename' => $backup['filename'],
                            'path' => $backup['path'],
                            'size_bytes' => $backup['size'],
                            'status' => 'completed',
                            'created_by' => auth()->id(),
                            'completed_at' => $backup['created_at'],
                        ]);

                        Notification::make()
                            ->title('Backup created successfully')
                            ->body(
                                $backup['filename'] .
                                ' (' .
                                number_format($backup['size'] / 1024, 2) .
                                ' KB)'
                            )
                            ->success()
                            ->send();

                        $this->dispatch('$refresh');
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Backup failed')
                            ->body(
                                'The backup could not be completed. Check the application logs for details.'
                            )
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('connectGoogleDrive')
                ->label('Connect Google Drive')
                ->icon(Heroicon::OutlinedCloud)
                ->color('gray')
                ->url(route('google-drive.connect')),
        ];
    }


    protected function getViewData(): array
    {
        $backups = BackupHistory::query()
            ->latest('completed_at')
            ->latest('id')
            ->get();

        $latestBackup = $backups->first();

        $totalSize = $backups->sum('size_bytes');

        return [
            'backups' => $backups,
            'latestBackup' => $latestBackup,
            'backupCount' => $backups->count(),
            'totalSize' => $totalSize,
        ];
    }

}
