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
        Schema::table('paiements', function (Blueprint $table) {
            $table->foreignId('tranche_paiement_id')->nullable()->after('eleve_id')
                  ->constrained('tranches_paiement')->nullOnDelete();
            $table->foreignId('configuration_frais_id')->nullable()->after('tranche_paiement_id')
                  ->constrained('configurations_frais')->nullOnDelete();
            $table->foreignId('comptable_id')->nullable()->after('configuration_frais_id')
                  ->constrained('users')->nullOnDelete();
            $table->string('mode_paiement')->default('especes')->after('montant');
            $table->string('reference')->nullable()->after('mode_paiement');
            $table->text('notes')->nullable()->after('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropForeign(['tranche_paiement_id']);
            $table->dropForeign(['configuration_frais_id']);
            $table->dropForeign(['comptable_id']);
            $table->dropColumn([
                'tranche_paiement_id',
                'configuration_frais_id',
                'comptable_id',
                'mode_paiement',
                'reference',
                'notes'
            ]);
        });
    }
};
