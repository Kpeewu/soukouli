<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Récupérer l'ID du cycle Collège
        $collegeCycle = DB::table('cycles')->where('code', 'COLLEGE')->first();

        if ($collegeCycle) {
            // Assigner toutes les promotions existantes au cycle Collège
            DB::table('promotions')
                ->whereNull('cycle_id')
                ->update(['cycle_id' => $collegeCycle->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre cycle_id à null pour toutes les promotions
        DB::table('promotions')->update(['cycle_id' => null]);
    }
};
