<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warans', function (Blueprint $table): void {
            if (Schema::hasColumn('warans', 'nama_waran')) {
                $table->text('nama_waran')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('warans', function (Blueprint $table): void {
            if (Schema::hasColumn('warans', 'nama_waran')) {
                $table->string('nama_waran')->nullable()->change();
            }
        });
    }
};
