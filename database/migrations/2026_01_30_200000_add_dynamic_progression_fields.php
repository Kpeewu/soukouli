<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter ordre de progression aux promotions
        Schema::table('promotions', function (Blueprint $table) {
            $table->integer('ordre')->default(0)->after('nom');
        });

        // Ajouter les champs dynamiques aux cycles
        Schema::table('cycles', function (Blueprint $table) {
            // Niveaux par defaut (JSON) pour la creation automatique
            $table->json('niveaux')->nullable()->after('supports_semestre');
            // Cycle suivant pour le passage entre cycles
            $table->foreignId('cycle_suivant_id')->nullable()->after('niveaux')
                  ->constrained('cycles')->nullOnDelete();
            // Suffixe pour l'affichage (ex: "eme" pour college)
            $table->string('suffixe_niveau')->nullable()->after('cycle_suivant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropForeign(['cycle_suivant_id']);
            $table->dropColumn(['niveaux', 'cycle_suivant_id', 'suffixe_niveau']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('ordre');
        });
    }
};
