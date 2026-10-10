<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les colonnes JSON des élèves (pere, mere, contact_tuteur, sante) ont été enregistrées
 * deux fois encodées (json_encode() manuel + cast 'json' du modèle), ce qui les faisait
 * relire comme une simple chaîne. On les ré-encode une seule fois.
 */
return new class extends Migration
{
    private const COLONNES = ['pere', 'mere', 'contact_tuteur', 'sante'];

    public function up(): void
    {
        DB::table('eleves')->orderBy('id')->chunkById(200, function ($eleves) {
            foreach ($eleves as $eleve) {
                $updates = [];

                foreach (self::COLONNES as $colonne) {
                    $valeur = $eleve->{$colonne};
                    if ($valeur === null) {
                        continue;
                    }

                    $decode = json_decode($valeur, true);
                    if (is_string($decode)) {
                        $interne = json_decode($decode, true);
                        if (is_array($interne)) {
                            $updates[$colonne] = json_encode($interne);
                        }
                    }
                }

                if ($updates) {
                    DB::table('eleves')->where('id', $eleve->id)->update($updates);
                }
            }
        });
    }

    public function down(): void
    {
        // Pas de retour arrière : l'ancien format était erroné.
    }
};
