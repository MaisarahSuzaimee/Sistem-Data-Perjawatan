<?php

use App\Filament\Pages\DatabaseBackup;
use App\Models\BackupHistory;
use App\Models\User;
use App\Services\DatabaseBackupService;
use App\Services\GoogleDriveBackupService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
});

it('schedules the database backup every day at 6 pm malaysia time', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(function ($event): bool {
            return str_contains((string) ($event->command ?? ''), 'database:backup')
                || $event->description === 'mystaff-daily-database-backup';
        });

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 18 * * *');

    $timezone = $event->timezone;

    if ($timezone instanceof DateTimeZone) {
        $timezone = $timezone->getName();
    }

    expect((string) $timezone)->toBe('Asia/Kuala_Lumpur');
});

it('creates a backup and uploads it to google drive', function () {
    $this->mock(DatabaseBackupService::class, function ($mock): void {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'filename' => 'mystaff_auto.sql.gz',
                'path' => 'database-backups/mystaff_auto.sql.gz',
                'size' => 2048,
                'created_at' => now('Asia/Kuala_Lumpur'),
            ]);
    });

    $this->mock(GoogleDriveBackupService::class, function ($mock): void {
        $mock->shouldReceive('upload')
            ->once()
            ->with('database-backups/mystaff_auto.sql.gz')
            ->andReturn([
                'id' => 'drive-auto-1',
                'name' => 'mystaff_auto.sql.gz',
                'mime_type' => 'application/gzip',
                'size' => 2048,
                'web_view_link' => 'https://drive.google.com/file/d/drive-auto-1/view',
            ]);
    });

    $this->artisan('database:backup')
        ->expectsOutputToContain('Backup created: mystaff_auto.sql.gz')
        ->expectsOutputToContain('Uploaded to Google Drive.')
        ->assertSuccessful();

    $history = BackupHistory::query()->first();

    expect($history)->not->toBeNull()
        ->and($history->filename)->toBe('mystaff_auto.sql.gz')
        ->and($history->status)->toBe('completed')
        ->and($history->google_drive_status)->toBe('uploaded')
        ->and($history->google_drive_file_id)->toBe('drive-auto-1')
        ->and($history->google_drive_web_view_link)->toBe('https://drive.google.com/file/d/drive-auto-1/view');
});

it('keeps the local backup when google drive upload fails', function () {
    $this->mock(DatabaseBackupService::class, function ($mock): void {
        $mock->shouldReceive('create')
            ->once()
            ->andReturn([
                'filename' => 'mystaff_auto.sql.gz',
                'path' => 'database-backups/mystaff_auto.sql.gz',
                'size' => 2048,
                'created_at' => now('Asia/Kuala_Lumpur'),
            ]);
    });

    $this->mock(GoogleDriveBackupService::class, function ($mock): void {
        $mock->shouldReceive('upload')
            ->once()
            ->andThrow(new \RuntimeException('Google Drive is not connected.'));
    });

    $this->artisan('database:backup')
        ->expectsOutputToContain('Backup created: mystaff_auto.sql.gz')
        ->expectsOutputToContain('Google Drive upload failed')
        ->assertFailed();

    $history = BackupHistory::query()->first();

    expect($history)->not->toBeNull()
        ->and($history->status)->toBe('completed')
        ->and($history->google_drive_status)->toBe('failed')
        ->and($history->google_drive_file_id)->toBeNull();
});

it('shows the automatic google drive backup schedule on the backup page', function () {
    $this->actingAs(User::create([
        'name' => 'SUPERADMIN BACKUP SCHEDULE',
        'email' => 'superadmin-backup-schedule@moh.gov.my',
        'password' => bcrypt('password'),
        'nokp' => '110101015502',
        'status' => true,
        'role' => 1,
    ]));

    Livewire::test(DatabaseBackup::class)
        ->assertSee('Daily auto backup at 6:00 PM')
        ->assertSee('Malaysia time (Asia/Kuala_Lumpur)')
        ->assertSee('Uploaded automatically after each backup');
});
