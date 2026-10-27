<?php

use App\Filament\Pages\DatabaseBackup;
use App\Models\BackupHistory;
use App\Models\User;
use App\Services\GoogleDriveBackupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.env' => 'local']);

    Schema::dropIfExists('backup_histories');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('ptj_id')->nullable();
        $table->string('email')->unique();
        $table->string('avatar')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->string('nokp')->unique();
        $table->string('phone_number')->nullable();
        $table->boolean('status')->default(true);
        $table->integer('role')->default(3);
        $table->timestamps();
    });

    Schema::create('backup_histories', function (Blueprint $table): void {
        $table->id();
        $table->string('filename');
        $table->string('path');
        $table->unsignedBigInteger('size_bytes')->default(0);
        $table->string('status')->default('completed');
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->string('google_drive_status')->nullable();
        $table->string('google_drive_file_id')->nullable();
        $table->text('google_drive_web_view_link')->nullable();
        $table->timestamp('google_drive_uploaded_at')->nullable();
        $table->timestamps();
    });

    Storage::fake('local');

    $this->actingAs(User::create([
        'name' => 'SUPERADMIN BACKUP',
        'email' => 'superadmin-backup@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '110101015501',
        'status' => true,
        'role' => 1,
    ]));
});

it('uploads a completed backup to google drive from the backup page', function () {
    Storage::disk('local')->put('database-backups/mystaff_test.sql.gz', 'backup-bytes');

    $backup = BackupHistory::create([
        'filename' => 'mystaff_test.sql.gz',
        'path' => 'database-backups/mystaff_test.sql.gz',
        'size_bytes' => 12,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    $this->mock(GoogleDriveBackupService::class, function ($mock): void {
        $mock->shouldReceive('upload')
            ->once()
            ->with('database-backups/mystaff_test.sql.gz')
            ->andReturn([
                'id' => 'drive-file-1',
                'name' => 'mystaff_test.sql.gz',
                'mime_type' => 'application/gzip',
                'size' => 12,
                'web_view_link' => 'https://drive.google.com/file/d/drive-file-1/view',
            ]);
    });

    Livewire::test(DatabaseBackup::class)
        ->call('uploadToGoogleDrive', $backup->id)
        ->assertSee('Uploaded')
        ->assertSee('View in Drive');

    $backup->refresh();

    expect($backup->google_drive_status)->toBe('uploaded')
        ->and($backup->google_drive_file_id)->toBe('drive-file-1')
        ->and($backup->google_drive_web_view_link)->toBe('https://drive.google.com/file/d/drive-file-1/view');
});
