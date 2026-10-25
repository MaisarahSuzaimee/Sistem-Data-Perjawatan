<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waran_jawatans', function (Blueprint $table): void {
            $table->string('paparan_jawatan')->nullable()->after('butiran');
        });
    }

    public function down(): void
    {
        Schema::table('waran_jawatans', function (Blueprint $table): void {
            $table->dropColumn('paparan_jawatan');
        });
    }
};