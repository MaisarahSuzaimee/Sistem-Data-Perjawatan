<?php

namespace App\Http\Controllers;

use App\Models\BackupHistory;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupDownloadController extends Controller
{
    public function __invoke(
        BackupHistory $backupHistory
    ): StreamedResponse {
        abort_unless(
            auth()->user()?->isSuperAdmin(),
            403
        );

        abort_unless(
            $backupHistory->status === 'completed',
            404
        );

        $disk = Storage::disk('local');

        abort_unless(
            $disk->exists($backupHistory->path),
            404,
            'Backup file not found.'
        );

        return $disk->download(
            $backupHistory->path,
            $backupHistory->filename
        );
    }
}
