<?php

namespace App\Http\Controllers;

use App\Models\BackupHistory;
use App\Services\GoogleDriveBackupService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GoogleDriveBackupController extends Controller
{
    public function upload(
        BackupHistory $backup,
        GoogleDriveBackupService $googleDrive
    ): RedirectResponse {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if ($backup->status !== 'completed') {
            Notification::make()
                ->title('Backup unavailable')
                ->body('Only completed backups can be uploaded.')
                ->warning()
                ->send();

            return back();
        }

        if (! Storage::disk('local')->exists($backup->path)) {
            Notification::make()
                ->title('Backup file not found')
                ->danger()
                ->send();

            return back();
        }

        try {
            $result = $googleDrive->upload($backup->path);

            $backup->update([
                'google_drive_status' => 'uploaded',
                'google_drive_file_id' => $result['id'],
                'google_drive_web_view_link' => $result['web_view_link'] ?? null,
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
                ->body($exception->getMessage() ?: 'Check the application logs for details.')
                ->danger()
                ->send();
        }

        return back();
    }
}
