<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktiviti_ptj', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aktiviti_id')->constrained('aktivitis')->cascadeOnDelete();
            $table->foreignId('ptj_id')->constrained('ptjs')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['aktiviti_id', 'ptj_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktiviti_ptj');
    }
};
