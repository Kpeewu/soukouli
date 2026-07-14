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
        Schema::table('promotions', function (Blueprint $table) {
            $table->foreignId('cycle_id')->nullable()->after('annee_scolaire_id')
                  ->constrained('cycles')->onDelete('cascade');
            $table->enum('type_periode', ['trimestre', 'semestre'])->default('trimestre')->after('cycle_id');
            $table->boolean('a_examen_officiel')->default(false)->after('type_periode');
            $table->foreignId('examen_officiel_id')->nullable()->after('a_examen_officiel')
                  ->constrained('examens_officiels')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeign(['examen_officiel_id']);
            $table->dropColumn('examen_officiel_id');
            $table->dropColumn('a_examen_officiel');
            $table->dropColumn('type_periode');
            $table->dropForeign(['cycle_id']);
            $table->dropColumn('cycle_id');
        });
    }
};
