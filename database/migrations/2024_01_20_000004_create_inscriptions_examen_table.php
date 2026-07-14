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
        Schema::create('inscriptions_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_examen_id')->constrained('sessions_examen')->onDelete('cascade');
            $table->foreignId('eleve_id')->constrained('eleves')->onDelete('cascade');
            $table->string('numero_inscription')->nullable();
            $table->string('centre_examen')->nullable();
            $table->enum('statut', ['inscrit', 'admis', 'ajourne', 'absent'])->default('inscrit');
            $table->float('moyenne_obtenue')->nullable();
            $table->string('mention')->nullable();
            $table->timestamps();

            $table->unique(['session_examen_id', 'eleve_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions_examen');
    }
};
