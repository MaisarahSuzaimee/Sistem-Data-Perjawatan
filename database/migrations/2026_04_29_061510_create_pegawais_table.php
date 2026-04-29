<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->integer('ptj_id');
            $table->integer('bahagian_id');
            $table->integer('unit_id');
            $table->integer('subunit_id');
            $table->integer('jawatan_gred_id');
            $table->integer('opsyen_pencen_id');
            $table->string('nama');
            $table->string('nokp');
            $table->string('jantina');
            $table->date('tarikh_lantikan');
            $table->date('tarikh_sah_jawatan');
            $table->date('tarikh_pencen');
            $table->boolean('is_kontrak');
            $table->boolean('is_kup');
            $table->boolean('is_kupj');
            $table->boolean('is_jtw');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
