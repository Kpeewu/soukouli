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
        Schema::create('configurations_frais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_frais_id')->constrained('types_frais')->onDelete('cascade');
            $table->foreignId('cycle_id')->constrained('cycles')->onDelete('cascade');
            $table->string('niveau')->nullable();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->onDelete('cascade');
            $table->decimal('montant', 10, 2);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['type_frais_id', 'cycle_id', 'niveau', 'annee_scolaire_id'], 'config_frais_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations_frais');
    }
};
