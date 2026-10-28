<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupManager;
use Illuminate\Console\Command;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'database:backup {--no-drive : Skip uploading the backup to Google Drive}';

    protected $description = 'Create a compressed MySTAFF database backup and upload it to Google Drive';

    public function handle(DatabaseBackupManager $manager): int
    {
        $this->info('Creating database backup...');

        try {
            $history = $manager->createAndUpload(
                createdBy: null,
                uploadToDrive: ! $this->option('no-drive'),
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage() ?: 'The database backup could not be completed.');

            return self::FAILURE;
        }

        $this->info('Backup created: '.$history->filename);

        if ($this->option('no-drive')) {
            return self::SUCCESS;
        }

        if ($history->google_drive_status === 'uploaded') {
            $this->info('Uploaded to Google Drive.');

            return self::SUCCESS;
        }

        $this->error('Backup was saved locally, but the Google Drive upload failed.');

        return self::FAILURE;
    }
}
