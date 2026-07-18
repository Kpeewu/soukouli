<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Promotion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClasseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param int $nombreClasses Groupes a creer par promotion. La valeur par
     *                           defaut preserve le comportement historique ;
     *                           DemoSeeder passe une valeur reduite via
     *                           callWith().
     */
    public function run(int $nombreClasses = 2): void
    {
        $anneeScolaire = AnneeScolaire::where('courant', true)->first();

        if (!$anneeScolaire) {
            $this->command->error('Aucune annee scolaire courante trouvee!');
            return;
        }

        // Recuperer toutes les promotions de l'annee scolaire courante
        $promotions = Promotion::where('annee_scolaire_id', $anneeScolaire->id)
            ->with('cycle')
            ->get();

        $groupes = array_slice(['A', 'B', 'C', 'D'], 0, max(1, min(4, $nombreClasses)));

        foreach ($promotions as $promotion) {
            // Creer les groupes de la promotion (A, B, ...)
            foreach ($groupes as $groupe) {
                $nomClasse = $promotion->nom . ' ' . $groupe;

                Classe::firstOrCreate(
                    [
                        'nom' => $nomClasse,
                        'promotion_id' => $promotion->id
                    ]
                );
            }
        }

        $this->command->info('Classes creees pour toutes les promotions');
    }
}
