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
        Schema::create('recus', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('paiement_id')->constrained('paiements')->onDelete('cascade');
            $table->foreignId('comptable_id')->constrained('users')->onDelete('cascade');
            $table->datetime('date_emission');
            $table->boolean('annule')->default(false);
            $table->text('motif_annulation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recus');
    }
};
