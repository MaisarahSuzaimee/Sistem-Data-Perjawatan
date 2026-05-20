<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up()
    {
        Schema::table('jawatans', function (Blueprint $table) {
            $table->string('skim')->nullable()->after('desc_jawatan');
        });
    }

    public function down()
    {
        Schema::table('jawatans', function (Blueprint $table) {
            $table->dropColumn('skim');
        });
    }
};
