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
        Schema::table('professeurs', function (Blueprint $table) {
            $table->foreignId('cycle_id')->nullable()->after('user_id')
                  ->constrained('cycles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('professeurs', function (Blueprint $table) {
            $table->dropForeign(['cycle_id']);
            $table->dropColumn('cycle_id');
        });
    }
};
