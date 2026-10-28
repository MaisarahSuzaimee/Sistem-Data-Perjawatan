<?php

use App\Filament\Pages\DatabaseBackup;
use App\Models\BackupHistory;
use App\Models\GoogleDriveConnection;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.env' => 'local']);

    Schema::dropIfExists('backup_histories');
    Schema::dropIfExists('google_drive_connections');
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

    Schema::create('google_drive_connections', function (Blueprint $table): void {
        $table->id();
        $table->longText('token');
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

it('does not show a manual upload to drive button', function () {
    Storage::disk('local')->put('database-backups/mystaff_test.sql.gz', 'backup-bytes');

    BackupHistory::create([
        'filename' => 'mystaff_test.sql.gz',
        'path' => 'database-backups/mystaff_test.sql.gz',
        'size_bytes' => 12,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    Livewire::test(DatabaseBackup::class)
        ->assertSee('Download')
        ->assertSee('Not uploaded')
        ->assertDontSee('Upload to Drive');
});

it('shows a red indicator when google drive is not connected', function () {
    Livewire::test(DatabaseBackup::class)
        ->assertSee('Google Drive not connected')
        ->assertSeeHtml('bg-red-500')
        ->assertDontSeeHtml('bg-green-500');
});

it('shows a green indicator when google drive is connected', function () {
    GoogleDriveConnection::query()->create([
        'token' => [
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
        ],
    ]);

    Livewire::test(DatabaseBackup::class)
        ->assertSee('Google Drive connected')
        ->assertSeeHtml('bg-green-500')
        ->assertDontSeeHtml('bg-red-500');
});
