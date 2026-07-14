<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_matiere_ordres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('cycles')->cascadeOnDelete();
            // Pas de FK vers promotions : l'identite d'un niveau a travers les annees
            // scolaires est (cycle_id, nom), pas un promotion_id precis qui est recree
            // chaque annee (cf. AnneeScolaireGenerationService).
            $table->string('niveau');
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();

            $table->unique(['cycle_id', 'niveau', 'matiere_id'], 'bulletin_matiere_ordres_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_matiere_ordres');
    }
};
