<?php

namespace App\Filament\Pages;

use App\Services\DatabaseBackupService;
use App\Models\BackupHistory;
use App\Services\GoogleDriveBackupService;
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
            // Action::make('connectGoogleDrive')
            //     ->label('Connect Google Drive')
            //     ->icon(Heroicon::OutlinedCloud)
            //     ->color('gray')
            //     ->url(url('/google-drive/connect'))
            //     ->openUrlInNewTab(false),
        ];
    }


    public function uploadToGoogleDrive(int $backupId): void
    {
        // Only superadmins may upload database backups.
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $backup = BackupHistory::query()->findOrFail($backupId);

        if ($backup->status !== 'completed') {
            Notification::make()
                ->title('Backup unavailable')
                ->body('Only completed backups can be uploaded.')
                ->warning()
                ->send();

            return;
        }

        $disk = Storage::disk('local');

        if (!$disk->exists($backup->path)) {
            Notification::make()
                ->title('Backup file not found')
                ->body('The local backup file does not exist.')
                ->danger()
                ->send();

            return;
        }

        try {
            $result = app(GoogleDriveBackupService::class)->upload(
                $backup->path,
            );

            $backup->update([
                'google_drive_status' => 'uploaded',
                'google_drive_file_id' => $result['id'],
                'google_drive_web_view_link' => $result['webViewLink'] ?? null,
                'google_drive_uploaded_at' => now('Asia/Kuala_Lumpur'),
            ]);

            Notification::make()
                ->title('Uploaded to Google Drive')
                ->body($backup->filename)
                ->success()
                ->send();

        } catch (Throwable $exception) {
            report($exception);

            $backup->update([
                'google_drive_status' => 'failed',
            ]);

            Notification::make()
                ->title('Google Drive upload failed')
                ->body('Check the application logs for details.')
                ->danger()
                ->send();
        }
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
