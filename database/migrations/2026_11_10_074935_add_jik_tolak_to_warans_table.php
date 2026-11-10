<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warans', function (Blueprint $table): void {
            $table->integer('jik_tolak')->nullable()->after('jik');
        });
    }

    public function down(): void
    {
        Schema::table('warans', function (Blueprint $table): void {
            $table->dropColumn('jik_tolak');
        });
    }
};
