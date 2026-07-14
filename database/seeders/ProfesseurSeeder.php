<?php

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\Professeur;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProfesseurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Noms togolais
        $noms = [
            'AGBEKO', 'AMEGAVI', 'ATSOU', 'AYITE', 'ADODO',
            'BOEVI', 'DOGBE', 'DZIFA', 'EDEM', 'EKUE',
            'FOLLY', 'GBENOU', 'HOUNKPATIN', 'JOHNSON', 'KOFFI',
            'LAWSON', 'MENSAH', 'NUNYUI', 'OLYMPIO', 'PEDRO',
            'QUASHIE', 'SANTOS', 'SODJI', 'TETTEH', 'WILSON',
            'YACOUBOU', 'ZINSOU', 'AGBODJAN', 'ATITSO', 'BAKPE'
        ];

        $prenomsMasculins = [
            'Koffi', 'Komla', 'Kodjo', 'Kossi', 'Yaovi',
            'Edem', 'Kokou', 'Mensah', 'Yao', 'Sena'
        ];

        $prenomsFeminins = [
            'Afi', 'Akossiwa', 'Adjoa', 'Adjo', 'Amavi',
            'Abla', 'Akuvi', 'Dede', 'Efua', 'Mawusi'
        ];

        $cycles = Cycle::all();

        // Creer 5-8 professeurs par cycle
        foreach ($cycles as $cycle) {
            $nombreProfesseurs = rand(5, 8);

            for ($i = 0; $i < $nombreProfesseurs; $i++) {
                $sexe = rand(0, 1) ? 'M' : 'F';
                $nom = $noms[array_rand($noms)];
                $prenom = $sexe === 'M'
                    ? $prenomsMasculins[array_rand($prenomsMasculins)]
                    : $prenomsFeminins[array_rand($prenomsFeminins)];

                // Generer un numero de telephone togolais
                $prefixes = ['90', '91', '92', '93', '96', '97', '98', '99'];
                $telephone = $prefixes[array_rand($prefixes)] . rand(100000, 999999);

                // Verifier si ce professeur existe deja (reseed sans migrate:fresh)
                $professeur = Professeur::where('nom', $nom)
                    ->where('prenom', $prenom)
                    ->where('cycle_id', $cycle->id)
                    ->first();

                if ($professeur) {
                    // Le professeur existe deja : s'assurer que son compte a bien le role professeur
                    if ($professeur->user) {
                        $professeur->user->assignRole('professeur');
                    }

                    continue;
                }

                // Generer un username unique pour le compte utilisateur
                $baseUsername = Str::lower(substr($prenom, 0, 1) . $nom);
                $baseUsername = Str::ascii($baseUsername); // Supprimer les accents
                $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername);
                $username = $baseUsername;
                $counter = 1;
                while (User::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                // Creer le compte utilisateur avec mot de passe par defaut "professeur"
                $user = User::create([
                    'username' => $username,
                    'password' => 'professeur', // Le cast 'hashed' s'occupe du hashage
                ]);

                // Lier le compte au role professeur pour qu'il ait les permissions adequates
                $user->assignRole('professeur');

                // Creer le professeur avec le user_id
                Professeur::create([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'cycle_id' => $cycle->id,
                    'contact' => $telephone,
                    'sexe' => $sexe,
                    'user_id' => $user->id
                ]);
            }

            $this->command->info("Professeurs crees pour le cycle: {$cycle->nom}");
        }
    }
}
