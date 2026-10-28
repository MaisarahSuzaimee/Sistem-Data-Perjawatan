<?php

namespace App\Services;

use App\Models\BackupHistory;
use Throwable;

class DatabaseBackupManager
{
    public const DRIVE_RETENTION_DAYS = 30;

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
            $history = $this->uploadToDrive($history);
        } catch (Throwable) {
            return $history->refresh();
        }

        $this->purgeExpiredDriveBackups($history->id);

        return $history->refresh();
    }

    public function purgeExpiredDriveBackups(?int $exceptId = null): int
    {
        $cutoff = now('Asia/Kuala_Lumpur')->subDays(self::DRIVE_RETENTION_DAYS);

        $expired = BackupHistory::query()
            ->where('google_drive_status', 'uploaded')
            ->whereNotNull('google_drive_file_id')
            ->where('google_drive_uploaded_at', '<=', $cutoff)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->get();

        $purged = 0;

        foreach ($expired as $history) {
            try {
                $this->googleDrive->delete((string) $history->google_drive_file_id);

                $history->update([
                    'google_drive_status' => 'expired',
                    'google_drive_web_view_link' => null,
                ]);

                $purged++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $purged;
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
