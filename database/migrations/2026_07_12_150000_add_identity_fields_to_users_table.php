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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nom')->nullable()->after('username');
            $table->string('prenom')->nullable()->after('nom');
            $table->string('telephone')->nullable()->after('prenom');
            $table->enum('civilite', ['M', 'Mme'])->nullable()->after('telephone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['civilite', 'telephone', 'prenom', 'nom']);
        });
    }
};
