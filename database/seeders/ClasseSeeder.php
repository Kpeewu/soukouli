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
     */
    public function run(): void
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

        $groupes = ['A', 'B'];

        foreach ($promotions as $promotion) {
            // Creer 2 classes par promotion (A et B)
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
