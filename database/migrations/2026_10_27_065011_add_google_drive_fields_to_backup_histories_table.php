
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_histories', function (Blueprint $table) {
            $table->string('google_drive_status')
                ->nullable()
                ->after('status');

            $table->string('google_drive_file_id')
                ->nullable()
                ->after('google_drive_status');

            $table->text('google_drive_web_view_link')
                ->nullable()
                ->after('google_drive_file_id');

            $table->timestamp('google_drive_uploaded_at')
                ->nullable()
                ->after('google_drive_web_view_link');
        });
    }

    public function down(): void
    {
        Schema::table('backup_histories', function (Blueprint $table) {
            $table->dropColumn([
                'google_drive_status',
                'google_drive_file_id',
                'google_drive_web_view_link',
                'google_drive_uploaded_at',
            ]);
        });
    }
};
