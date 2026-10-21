<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('program_ptj', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('ptj_id');
            $table->timestamps();

            $table->unique(['program_id', 'ptj_id']);
        });

        // Migrate existing 1:N links into the pivot
        $now = now();

        DB::table('ptjs')
            ->whereNotNull('program_id')
            ->orderBy('id')
            ->get(['id', 'program_id'])
            ->each(function ($ptj) use ($now): void {
                DB::table('program_ptj')->insert([
                    'program_id' => $ptj->program_id,
                    'ptj_id' => $ptj->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        Schema::table('ptjs', function (Blueprint $table) {
            $table->dropColumn('program_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ptjs', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable()->after('id');
        });

        // Restore a single program_id per PTJ (first pivot row)
        $links = DB::table('program_ptj')
            ->orderBy('id')
            ->get(['program_id', 'ptj_id'])
            ->groupBy('ptj_id');

        foreach ($links as $ptjId => $rows) {
            DB::table('ptjs')
                ->where('id', $ptjId)
                ->update(['program_id' => $rows->first()->program_id]);
        }

        Schema::dropIfExists('program_ptj');
    }
};
