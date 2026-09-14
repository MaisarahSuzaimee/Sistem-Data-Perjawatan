<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subunits', function (Blueprint $table): void {
            $table->foreignId('aktiviti_id')
                ->nullable()
                ->after('parlimen_id')
                ->constrained('aktivitis')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subunits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('aktiviti_id');
        });
    }
};
