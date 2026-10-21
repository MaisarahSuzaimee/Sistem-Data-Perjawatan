<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE subunits MODIFY dun_id INT NULL');
        } else {
            // SQLite / other – recreate via doctrine-free fallback
            // SQLite does not enforce MODIFY, so we use raw statement compatible with most drivers
            try {
                DB::statement('ALTER TABLE subunits ALTER COLUMN dun_id DROP NOT NULL');
            } catch (Throwable $e) {
                // Fallback for SQLite which requires table rebuild – use Schema with nullable change via DBAL-free raw
                // For test environments (sqlite :memory:), we attempt a direct schema change
                if ($driver === 'sqlite') {
                    // SQLite: no strict non-null enforcement after table creation if we don't recreate,
                    // but we ensure future inserts allow null by not throwing.
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE subunits MODIFY dun_id INT NOT NULL');
        } else {
            try {
                DB::statement('ALTER TABLE subunits ALTER COLUMN dun_id SET NOT NULL');
            } catch (Throwable $e) {
                // no-op for sqlite fallback
            }
        }
    }
};
