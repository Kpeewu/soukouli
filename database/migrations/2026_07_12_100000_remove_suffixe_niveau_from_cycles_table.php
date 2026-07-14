<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Materialiser le suffixe (ex: "eme" pour le college) directement dans les
        // noms de niveaux/promotions/classes/trimestres avant de supprimer la colonne,
        // pour que l'affichage reste identique une fois le mecanisme de suffixe retire.
        // Requetes SQL brutes (pas les modeles Eloquent) car Cycle/Promotion perdent
        // suffixe_niveau/getPromotionSuffix() dans ce meme deploiement.
        $cycles = DB::table('cycles')->whereNotNull('suffixe_niveau')->where('suffixe_niveau', '!=', '')->get();

        foreach ($cycles as $cycle) {
            $anciensNiveaux = json_decode($cycle->niveaux, true) ?? [];

            if (empty($anciensNiveaux)) {
                continue;
            }

            // Le suffixe ASCII stocke (ex: "eme") est remplace par sa forme
            // accentuee finale (ex: "eme" -> "ème") pour matcher la convention deja
            // utilisee ailleurs dans l'app (ex: ExamenOfficielSeeder: niveau_requis = "3ème").
            $suffixeAffiche = $cycle->suffixe_niveau === 'eme' ? 'ème' : $cycle->suffixe_niveau;

            $nouveauxNiveaux = [];

            foreach ($anciensNiveaux as $ancienNiveau) {
                $ancienToken = $ancienNiveau . $cycle->suffixe_niveau; // ex: "6eme"
                $nouveauToken = $ancienNiveau . $suffixeAffiche;       // ex: "6ème"
                $nouveauxNiveaux[] = $nouveauToken;

                // Promotions dont le nom est encore l'ancien niveau brut (ex: "6")
                DB::table('promotions')
                    ->where('cycle_id', $cycle->id)
                    ->where('nom', $ancienNiveau)
                    ->update(['nom' => $nouveauToken]);

                $promotionIds = DB::table('promotions')
                    ->where('cycle_id', $cycle->id)
                    ->where('nom', $nouveauToken)
                    ->pluck('id');

                if ($promotionIds->isEmpty()) {
                    continue;
                }

                // Classes/trimestres dont le nom contient encore l'ancien token (ex: "6eme A")
                foreach (DB::table('classes')->whereIn('promotion_id', $promotionIds)->get(['id', 'nom']) as $classe) {
                    if (str_contains($classe->nom, $ancienToken)) {
                        DB::table('classes')->where('id', $classe->id)->update([
                            'nom' => str_replace($ancienToken, $nouveauToken, $classe->nom),
                        ]);
                    }
                }

                foreach (DB::table('trimestres')->whereIn('promotion_id', $promotionIds)->get(['id', 'intitule']) as $trimestre) {
                    if (str_contains($trimestre->intitule, $ancienToken)) {
                        DB::table('trimestres')->where('id', $trimestre->id)->update([
                            'intitule' => str_replace($ancienToken, $nouveauToken, $trimestre->intitule),
                        ]);
                    }
                }
            }

            DB::table('cycles')->where('id', $cycle->id)->update([
                'niveaux' => json_encode($nouveauxNiveaux),
            ]);

            // Recree le lien vers l'examen officiel correspondant, devenu possible
            // maintenant que promotions.nom correspond exactement a niveau_requis.
            DB::statement('
                UPDATE promotions
                SET examen_officiel_id = (
                    SELECT id FROM examens_officiels
                    WHERE examens_officiels.niveau_requis = promotions.nom
                    AND examens_officiels.cycle_id = promotions.cycle_id
                )
                WHERE promotions.cycle_id = ?
                AND promotions.a_examen_officiel = true
                AND promotions.examen_officiel_id IS NULL
            ', [$cycle->id]);
        }

        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('suffixe_niveau');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Recree uniquement la colonne : le renommage des donnees (promotions,
     * classes, trimestres, niveaux JSON) n'est pas annule.
     */
    public function down(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->string('suffixe_niveau')->nullable()->after('cycle_suivant_id');
        });
    }
};
