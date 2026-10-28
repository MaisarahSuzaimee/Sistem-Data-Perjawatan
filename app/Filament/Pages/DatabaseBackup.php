<?php

namespace App\Filament\Pages;

use App\Models\BackupHistory;
use App\Models\GoogleDriveConnection;
use App\Services\DatabaseBackupManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;

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
                        $backup = app(DatabaseBackupManager::class)->createAndUpload(
                            createdBy: auth()->id(),
                        );

                        $driveMessage = match ($backup->google_drive_status) {
                            'uploaded' => ' Uploaded to Google Drive.',
                            'failed' => ' Google Drive upload failed.',
                            default => '',
                        };

                        $notification = Notification::make()
                            ->title($backup->google_drive_status === 'failed'
                                ? 'Backup created, Drive upload failed'
                                : 'Backup created successfully')
                            ->body(
                                $backup->filename.
                                ' ('.
                                number_format($backup->size_bytes / 1024, 2).
                                ' KB).'.
                                $driveMessage
                            );

                        if ($backup->google_drive_status === 'failed') {
                            $notification->warning();
                        } else {
                            $notification->success();
                        }

                        $notification->send();

                        $this->dispatch('$refresh');
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Backup failed')
                            ->body(
                                $exception->getMessage() ?: 'The backup could not be completed. Check the application logs for details.'
                            )
                            ->danger()
                            ->send();
                    }
                }),
            // Action::make('connectGoogleDrive')
            //     ->label('Connect Google Drive')
            //     ->icon(Heroicon::OutlinedCloud)
            //     ->color('gray')
            //     ->url(url('/google-drive/connect'))
            //     ->openUrlInNewTab(false),
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
            'googleDriveConnected' => GoogleDriveConnection::isConnected(),
        ];
    }
}
