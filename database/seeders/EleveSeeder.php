<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Assiduite;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Promotion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EleveSeeder extends Seeder
{
    /**
     * Noms togolais courants
     */
    private array $noms = [
        'AGBEKO', 'AYEVA', 'LARE', 'AYITE', 'ADODO', 'AKAKPO',
        'PAKOU', 'ISSAKA', 'DZIFA', 'TCHANTCHAN', 'EKUE', 'ENYO',
        'LATA', 'GBENOU', 'HOUNKPATIN', 'LANDOSS', 'KOFFI', 'KLUTSE',
        'OURO BANGNA', 'KPATCHA', 'TCHANGANI', 'OLYMPIO', 'PEDRO', 'PAKU',
        'IROUDJI', 'MOUMOUNI', 'SODJI', 'TETTEH', 'WILSON', 'WOANYAH',
        'YACOUBOU', 'ZINSOU', 'LABALE', 'ATITSO', 'BAKPE', 'BOKO',
        'AHOEFA', 'YENDOUBE', 'ZENDJINA', 'ASSOGBA', 'BANINI', 'TRAORE'
    ];

    private array $prenomsMasculins = [
        'Ruben', 'Komla', 'Kodjo', 'Jean', 'Yaovi', 'Edem',
        'Gnato', 'Razak', 'Yao', 'Sena', 'Moussa', 'Ahmed',
        'Esso', 'Mohamed', 'Komi', 'Dodji', 'Zerou', 'Atsu',
        'Ilyace', 'Senyo', 'Essozina', 'Fiifi', 'Kodjovi', 'Mawuli'
    ];

    private array $prenomsFeminins = [
        'Rahmat', 'Djamila', 'Adjoa', 'Adjo', 'Mariam', 'Abla',
        'Nouria', 'Dede', 'Efua', 'Selima', 'Ama', 'Akpene',
        'Ayoko', 'Dzidzor', 'Kafui', 'Kekeli', 'Roukeya', 'Akua',
        'Karima', 'Noura', 'Yawa', 'Assana', 'Senam', 'Dela'
    ];

    private array $villesTogo = [
        'Lome', 'Sokode', 'Kara', 'Kpalime', 'Atakpame',
        'Dapaong', 'Tsevie', 'Aneho', 'Mango', 'Bassar',
        'Notse', 'Vogan', 'Tabligbo', 'Badou', 'Sotouboua'
    ];

    private array $professions = [
        'Commercant', 'Enseignant', 'Fonctionnaire', 'Agriculteur',
        'Artisan', 'Chauffeur', 'Infirmier', 'Medecin',
        'Comptable', 'Menuisier', 'Couturier', 'Electricien',
        'Mecanicien', 'Vendeur', 'Entrepreneur', 'Retraite'
    ];

    private array $situationsMatrimoniales = [
        'Marie(e)', 'Celibataire', 'Divorce(e)', 'Veuf(ve)', 'Union libre'
    ];

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

        // Recuperer toutes les classes
        $classes = Classe::with('promotion.cycle')->get();

        $elevesCount = 0;

        foreach ($classes as $classe) {
            // 20-25 eleves par classe
            $nombreEleves = rand(20, 25);

            for ($i = 0; $i < $nombreEleves; $i++) {
                $eleve = $this->createEleve($classe);
                $elevesCount++;
            }

            $this->command->info("Eleves crees pour la classe: {$classe->nom}");
        }

        $this->command->info("Total eleves crees: {$elevesCount}");
    }

    /**
     * Creer un eleve et l'attacher a une classe
     */
    private function createEleve(Classe $classe): Eleve
    {
        $sexe = rand(0, 1) ? 'M' : 'F';
        $nom = $this->noms[array_rand($this->noms)];
        $prenom = $sexe === 'M'
            ? $this->prenomsMasculins[array_rand($this->prenomsMasculins)]
            : $this->prenomsFeminins[array_rand($this->prenomsFeminins)];

        // Age adapte au niveau
        $ageRange = $this->getAgeRangeForPromotion($classe->promotion);
        $age = rand($ageRange[0], $ageRange[1]);
        $dateNaissance = now()->subYears($age)->subDays(rand(0, 365))->format('Y-m-d');

        $lieuNaissance = $this->villesTogo[array_rand($this->villesTogo)];

        // Generer matricule unique
        $cycleCode = $classe->promotion->cycle ? substr($classe->promotion->cycle->code, 0, 3) : 'XXX';
        $matricule = $cycleCode . date('Y') . Str::upper(Str::random(8));

        
        // Contacts tuteur
        $prefixes = ['90', '91', '92', '93', '96', '97', '98', '99'];
        $telephoneTuteur = $prefixes[array_rand($prefixes)] . rand(100000, 999999);

        $eleve = Eleve::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'date_naissance' => $dateNaissance,
            'lieu_naissance' => $lieuNaissance,
            'sexe' => $sexe,
            'matricule' => $matricule,
            'adresse' => $lieuNaissance . ', Togo',
            'redoublant' => rand(0, 10) < 2, // 20% de redoublants
            'contact_tuteur' => json_encode([
                'nom' => $this->noms[array_rand($this->noms)],
                'prenom' => $this->prenomsMasculins[array_rand($this->prenomsMasculins)],
                'telephone' => $telephoneTuteur,
                'adresse' => $lieuNaissance,
                'profession' => $this->professions[array_rand($this->professions)],
                'situation_matrimoniale' => $this->situationsMatrimoniales[array_rand($this->situationsMatrimoniales)],
                'lien' => ['Pere', 'Mere', 'Oncle', 'Tante', 'Tuteur'][rand(0, 4)]
            ]),
            'pere' => json_encode([
                'nom' => $nom,
                'prenom' => $this->prenomsMasculins[array_rand($this->prenomsMasculins)],
                'telephone' => $prefixes[array_rand($prefixes)] . rand(100000, 999999),
                'profession' => $this->professions[array_rand($this->professions)],
                'adresse' => $lieuNaissance,
                'situation_matrimoniale' => $this->situationsMatrimoniales[array_rand($this->situationsMatrimoniales)]
            ]),
            'mere' => json_encode([
                'nom' => $this->noms[array_rand($this->noms)],
                'prenom' => $this->prenomsFeminins[array_rand($this->prenomsFeminins)],
                'telephone' => $prefixes[array_rand($prefixes)] . rand(100000, 999999),
                'profession' => $this->professions[array_rand($this->professions)],
                'adresse' => $lieuNaissance,
                'situation_matrimoniale' => $this->situationsMatrimoniales[array_rand($this->situationsMatrimoniales)]
            ]),
            'sante' => json_encode([
                'groupe' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'][rand(0, 7)],
                'problemes' => rand(0, 5) < 1 ? 'Aucun probleme connu' : 'Aucun',
                'restrictions' => 'Aucune',
                'medicaments' => 'Aucun'
            ])
        ]);

        // Attacher l'eleve a la classe
        $classe->eleves()->attach($eleve->id);

        // Creer l'assiduite pour chaque trimestre
        foreach ($classe->promotion->trimestres as $trimestre) {
            Assiduite::firstOrCreate([
                'eleve_id' => $eleve->id,
                'trimestre_id' => $trimestre->id
            ], [
                'comportement' => json_encode([
                    'appreciation' => ['Excellent', 'Tres bien', 'Bien', 'Assez bien', 'Passable'][rand(0, 4)],
                    'avertissement' => [
                        'Travail' => false,
                        'Discipline' => false
                    ],
                    'blame' => [
                        'Travail' => false,
                        'Discipline' => false
                    ]
                ])
            ]);
        }

        return $eleve;
    }

    /**
     * Retourne la fourchette d'age selon le niveau
     */
    private function getAgeRangeForPromotion(Promotion $promotion): array
    {
        if (!$promotion->cycle) {
            return [10, 15];
        }

        return match($promotion->cycle->code) {
            'MATERNELLE' => [3, 6],
            'PRIMAIRE' => [6, 12],
            'COLLEGE' => [11, 17],
            'LYCEE' => [15, 20],
            default => [10, 15]
        };
    }
}
