<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waran_jawatans', function (Blueprint $table): void {
            $table->unsignedBigInteger('menggantikan_waran_id')->nullable()->after('waran_tolak_id');
            $table->index('menggantikan_waran_id');
        });
    }

    public function down(): void
    {
        Schema::table('waran_jawatans', function (Blueprint $table): void {
            $table->dropIndex(['menggantikan_waran_id']);
            $table->dropColumn('menggantikan_waran_id');
        });
    }
};
