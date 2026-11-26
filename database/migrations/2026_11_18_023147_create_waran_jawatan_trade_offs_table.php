<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waran_jawatan_trade_offs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('waran_jawatan_id')
                ->constrained('waran_jawatans')
                ->cascadeOnDelete();
            $table->foreignId('trade_off_waran_jawatan_id')
                ->constrained('waran_jawatans')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['waran_jawatan_id', 'trade_off_waran_jawatan_id'],
                'waran_jawatan_trade_offs_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waran_jawatan_trade_offs');
    }
};
