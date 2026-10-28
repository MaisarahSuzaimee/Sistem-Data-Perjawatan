<?php

namespace App\Services;

use App\Models\BackupHistory;
use Throwable;

class DatabaseBackupManager
{
    public function __construct(
        private DatabaseBackupService $databaseBackups,
        private GoogleDriveBackupService $googleDrive,
    ) {}

    public function createAndUpload(?int $createdBy = null, bool $uploadToDrive = true): BackupHistory
    {
        $file = $this->databaseBackups->create();

        $history = BackupHistory::query()->create([
            'filename' => $file['filename'],
            'path' => $file['path'],
            'size_bytes' => $file['size'],
            'status' => 'completed',
            'created_by' => $createdBy,
            'completed_at' => $file['created_at'],
            'google_drive_status' => $uploadToDrive ? 'pending' : null,
        ]);

        if (! $uploadToDrive) {
            return $history;
        }

        try {
            return $this->uploadToDrive($history);
        } catch (Throwable) {
            return $history->refresh();
        }
    }

    public function uploadToDrive(BackupHistory $history): BackupHistory
    {
        try {
            $result = $this->googleDrive->upload($history->path);

            $history->update([
                'google_drive_status' => 'uploaded',
                'google_drive_file_id' => $result['id'],
                'google_drive_web_view_link' => $result['web_view_link'] ?? null,
                'google_drive_uploaded_at' => now('Asia/Kuala_Lumpur'),
            ]);

            return $history->refresh();
        } catch (Throwable $exception) {
            $history->update([
                'google_drive_status' => 'failed',
            ]);

            throw $exception;
        }
    }
}
