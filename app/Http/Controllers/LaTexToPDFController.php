<?php

namespace App\Http\Controllers;

use App\Models\BulletinMatiereOrdre;
use App\Models\Classe;
use App\Models\Cycle;
use App\Models\Eleve;
use App\Models\Setting;
use App\Models\Trimestre;
use App\Models\User;
use App\Traits\FiltersByCycle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ismaelw\LaraTeX\LaraTeX;
use Rmunate\Utilities\SpellNumber;

class LaTexToPDFController extends Controller
{
    use FiltersByCycle;

    /**
     * Seuls les secretaires (de cycle ou secretaire general) peuvent generer les bulletins et
     * les cartes etudiantes.
     */
    private function authorizeSecretaire(Classe $classe): void
    {
        if (!Auth::user()->isSecretaire() || !$this->canAccessClasse($classe)) {
            abort(403, 'Seuls les secrétaires peuvent générer ce document.');
        }
    }

    /**
     * Génère la liste des élèves d'une classe en PDF
     */
    public function listesDesEleves(Classe $classe)
    {
        // Eager loading pour éviter les requêtes N+1
        $classe->load(['eleves', 'promotion.anneeScolaire']);

        $data = [
            'eleves' => $classe->eleves->sortBy('nom'),
            'classe' => $classe,
            'annee' => $classe->promotion->anneeScolaire,
            'logo' => Setting::getLogoPath(),
            'school' => $this->getSchoolSettings(),
        ];

        return (new LaraTeX('latex.liste-eleves'))
            ->with($data)
            ->inline('liste_des_eleves_' . substr($classe->nom, 0, 6) . '.pdf');
    }

    /**
     * Génère la fiche d'information d'un élève en PDF
     */
    public function informationsEleve(Eleve $eleve)
    {
        $data = [
            'eleve' => $eleve,
            'logo' => Setting::getLogoPath(),
            'photo_passeport' => public_path('assets/media/avatars/avatar1.jpg'),
            'school' => $this->getSchoolSettings(),
        ];

        return (new LaraTeX('latex.informationEleve'))
            ->with($data)
            ->inline('fiche_information_' . $eleve->nom . '_' . $eleve->prenom);
    }

    /**
     * Génère les fiches d'informations de tous les élèves d'une classe
     */
    public function informationsEleveAll(Classe $classe)
    {
        $classe->load('eleves');

        $data = [
            'eleves' => $classe->eleves->sortBy('nom'),
            'logo' => Setting::getLogoPath(),
            'school' => $this->getSchoolSettings(),
        ];

        return (new LaraTeX('latex.informationEleveClasse'))
            ->with($data)
            ->inline('fiche_informations_' . $classe->nom);
    }

    /**
     * Génère les cartes étudiantes de tous les élèves d'une classe
     */
    public function cartesEtudiantesClasse(Classe $classe)
    {
        $this->authorizeSecretaire($classe);

        $classe->load(['eleves', 'promotion.anneeScolaire']);

        $data = [
            'eleves' => $classe->eleves->sortBy('nom'),
            'classe' => $classe,
            'annee' => $classe->promotion->anneeScolaire,
            'logo' => Setting::getLogoPath(),
            'photo_passeport' => public_path('assets/media/avatars/avatar1.jpg'),
            'school' => $this->getSchoolSettings(),
        ];

        return (new LaraTeX('latex.carteEtudiante'))
            ->with($data)
            ->inline('cartes_etudiantes_' . substr($classe->nom, 0, 6));
    }

    /**
     * Génère le bulletin d'un élève pour un trimestre
     * VERSION OPTIMISÉE - Eager loading + calculs centralisés
     */
    public function bulletinTrimestre(Eleve $eleve, Classe $classe, Trimestre $trimestre)
    {
        $this->authorizeSecretaire($classe);

        $data = $this->buildBulletinData($eleve, $classe, $trimestre);

        return Pdf::loadView('pdf.bulletin', $data)
            ->setPaper('a4')
            ->stream('bulletin' . $classe->nom . '_' . $eleve->nom . '_' . $eleve->prenom . '.pdf');
    }

    /**
     * Génère les bulletins de tous les élèves d'une classe pour un trimestre
     * VERSION OPTIMISÉE - Une seule passe sur les données
     */
    public function bulletinsTrimestreClasse(Classe $classe, Trimestre $trimestre)
    {
        $this->authorizeSecretaire($classe);

        // 1. EAGER LOADING - Charger TOUTES les données en UNE SEULE requête
        $classe->load([
            'cours.matiere',
            'cours.professeur',
            'promotion.trimestres',
            'promotion.cycle',
            'promotion.classes.cours.evaluations.notes',
            'eleves.assiduites' => fn($q) => $q->where('trimestre_id', $trimestre->id),
            'professeur'
        ]);

        $this->ordonnerCoursSelonConfig($classe);

        // 2. PRÉ-INDEXER les notes (une seule passe)
        $notesIndex = $this->indexerNotes($classe);

        // 3. CALCULER les moyennes pour TOUS les trimestres
        $moyennesParTrimestre = [];
        foreach ($classe->promotion->trimestres as $trim) {
            $moyennesParTrimestre[$trim->id] = $this->calculerMoyennesClasse($classe, $trim->id, $notesIndex);
        }

        // 4. CONSTRUIRE tous les bulletins en une seule passe
        $bulletins = [];
        $nombreTrimestres = $classe->promotion->trimestres->count();
        $effectif = count($classe->eleves);

        foreach ($classe->eleves as $eleve) {
            $bulletin = [
                'eleve' => $eleve,
                'eleve_id' => $eleve->id,
                $eleve->id => $this->construireLignesBulletin($eleve, $classe, $trimestre, $notesIndex),
            ];

            // Moyennes et rangs
            $bulletin['moyennes'] = $this->extraireMoyennesEleve($eleve->id, $moyennesParTrimestre, $effectif);

            // Moyenne annuelle
            $bulletin['moyenne_annuelle'] = $this->calculerMoyenneAnnuelle($bulletin['moyennes'], $nombreTrimestres);

            // Moyenne en lettres
            $moyenneActuelle = $bulletin['moyennes'][$trimestre->id]['moyenne'] ?? 0;
            $bulletin['moyenne_lettres'] = $this->nombreEnLettres($moyenneActuelle);

            // Assiduité (déjà chargée via eager loading)
            $bulletin['assiduite'] = $eleve->assiduites->first();

            $bulletins[] = $bulletin;
        }

        $data = [
            'bulletins' => $bulletins,
            'classe' => $classe,
            'trimestre' => $trimestre,
            'logo' => Setting::getLogoPath(),
            'school' => $this->getSchoolSettings(),
            'directeur' => $this->resolveDirecteurPourCycle($classe->promotion->cycle ?? null),
            'layout' => BulletinHeaderConfigController::getLayout(),
        ];

        // Un bulletin par eleve (logo + mise en page repetes sur chaque page) peut
        // depasser la limite memoire par defaut de PHP pour une classe chargee.
        ini_set('memory_limit', '512M');

        return Pdf::loadView('pdf.bulletins', $data)
            ->setPaper('a4')
            ->stream('bulletins_' . substr($classe->nom, 0, 6) . '_' . substr($trimestre->intitule, 0, 11) . '.pdf');
    }

    /**
     * Resout le compte directeur associe au cycle donne (role "directeur_{code}"), si existant.
     * A defaut de directeur specifique au cycle, se replie sur le directeur general (tous cycles).
     */
    private function resolveDirecteurPourCycle(?Cycle $cycle): ?User
    {
        if ($cycle) {
            $directeur = User::role('directeur_' . strtolower($cycle->code))->first();
            if ($directeur) {
                return $directeur;
            }
        }

        return User::role('directeur_general')->first();
    }

    /**
     * Indexe toutes les notes de la promotion pour un accès O(1) au lieu de O(n)
     * Structure: [matiere_id][eleve_id][trimestre_id] => tableau de notes {valeur, type}
     *
     * Indexé par matière (et non par cours d'une seule classe) pour qu'un élève transféré
     * dans une autre classe de la promotion en cours d'année conserve, dans sa moyenne, les
     * notes prises dans le cours de son ancienne classe pour la même matière.
     */
    private function indexerNotes(Classe $classe): array
    {
        $index = [];

        foreach ($classe->promotion->classes as $classePromo) {
            foreach ($classePromo->cours as $cours) {
                foreach ($cours->evaluations as $evaluation) {
                    foreach ($evaluation->notes as $note) {
                        $index[$cours->matiere_id][$note->eleve_id][$note->trimestre_id][] = [
                            'id' => $note->id,
                            'valeur' => $note->valeur,
                            'type' => $evaluation->type,
                            'note_source_id' => $note->note_source_id,
                        ];
                    }
                }
            }
        }

        return $index;
    }

    /**
     * Calcule les moyennes de tous les élèves pour un trimestre
     * Retourne un tableau trié par moyenne décroissante avec les rangs
     */
    private function calculerMoyennesClasse(Classe $classe, int $trimestreId, array $notesIndex): array
    {
        $moyennes = [];

        foreach ($classe->eleves as $eleve) {
            $totalMoyenne = 0;
            $totalCoefficients = 0;

            foreach ($classe->cours as $cours) {
                $notesCours = $this->calculerNotesCours($eleve->id, $cours, $trimestreId, $notesIndex);
                $moyenneCours = ($notesCours['moyenne_classe'] + $notesCours['compo']) / 2;
                $totalMoyenne += $moyenneCours * $cours->coefficient;
                $totalCoefficients += $cours->coefficient;
            }

            $moyenne = $totalCoefficients > 0 ? round($totalMoyenne / $totalCoefficients, 2) : 0;

            $moyennes[] = [
                'eleve_id' => $eleve->id,
                'moyenne' => $moyenne,
            ];
        }

        // Trier par moyenne décroissante
        usort($moyennes, fn($a, $b) => $b['moyenne'] <=> $a['moyenne']);

        // Ajouter les rangs
        foreach ($moyennes as $index => &$item) {
            $item['rang'] = $index + 1;
        }

        return $moyennes;
    }

    /**
     * Calcule les notes d'un élève pour un cours (moyenne classe + compo)
     */
    private function calculerNotesCours(int $eleveId, $cours, int $trimestreId, array $notesIndex): array
    {
        $notesClasse = [];
        $noteCompo = 0;

        $notes = $notesIndex[$cours->matiere_id][$eleveId][$trimestreId] ?? [];

        // Une note reportée suite à un changement de classe et sa note d'origine représentent
        // le même devoir/composition : on exclut l'originale dès qu'une copie existe dans le
        // lot, pour ne pas la compter deux fois dans la moyenne.
        $idsSources = array_filter(array_column($notes, 'note_source_id'));
        $notes = array_filter($notes, fn ($note) => !in_array($note['id'], $idsSources));

        foreach ($notes as $note) {
            if ($note['type'] === 'composition') {
                $noteCompo = $note['valeur'];
            } else {
                $notesClasse[] = $note['valeur'];
            }
        }

        $moyenneClasse = count($notesClasse) > 0 ? round(array_sum($notesClasse) / count($notesClasse), 2) : 0;

        return [
            'moyenne_classe' => $moyenneClasse,
            'compo' => $noteCompo,
            'cours' => $cours,
        ];
    }

    /**
     * Construit le tableau de donnees complet necessaire au rendu d'un bulletin
     * individuel (lignes de notes, moyennes, assiduite...). Reutilise par
     * bulletinTrimestre() et par l'apercu de configuration d'en-tete
     * (BulletinHeaderConfigController), pour garantir que l'apercu reflete
     * exactement les memes donnees que le vrai PDF.
     */
    public function buildBulletinData(Eleve $eleve, Classe $classe, Trimestre $trimestre): array
    {
        // 1. EAGER LOADING - Charger TOUTES les données en une seule requête
        $classe->load([
            'cours.matiere',
            'cours.professeur',
            'promotion.trimestres',
            'promotion.cycle',
            'promotion.classes.cours.evaluations.notes',
            'eleves',
            'professeur'
        ]);

        $this->ordonnerCoursSelonConfig($classe);

        $eleve->load(['assiduites' => fn($q) => $q->where('trimestre_id', $trimestre->id)]);

        // 2. PRÉ-INDEXER les notes pour un accès O(1)
        $notesIndex = $this->indexerNotes($classe);

        // 3. CALCULER les moyennes de tous les élèves (nécessaire pour les rangs)
        $moyennesParTrimestre = [];
        foreach ($classe->promotion->trimestres as $trim) {
            $moyennesParTrimestre[$trim->id] = $this->calculerMoyennesClasse($classe, $trim->id, $notesIndex);
        }

        // 4. CONSTRUIRE les lignes du bulletin
        $lignesBulletin = $this->construireLignesBulletin($eleve, $classe, $trimestre, $notesIndex);

        // 5. EXTRAIRE les moyennes de l'élève
        $moyennes = $this->extraireMoyennesEleve($eleve->id, $moyennesParTrimestre, count($classe->eleves));

        // 6. CALCULER moyenne en lettres et annuelle
        $moyenneActuelle = $moyennes[$trimestre->id]['moyenne'] ?? 0;
        $moyenneLettre = $this->nombreEnLettres($moyenneActuelle);
        $moyenneAnnuelle = $this->calculerMoyenneAnnuelle($moyennes);

        return [
            'lignes' => $lignesBulletin,
            'moyennes_trimestres' => $moyennes,
            'moyenne_lettre' => $moyenneLettre,
            'eleve' => $eleve,
            'classe' => $classe,
            'trimestre' => $trimestre,
            'logo' => Setting::getLogoPath(),
            'moyenne_annuelle' => $moyenneAnnuelle,
            'assiduite' => $eleve->assiduites->first(),
            'school' => $this->getSchoolSettings(),
            'directeur' => $this->resolveDirecteurPourCycle($classe->promotion->cycle ?? null),
            'layout' => BulletinHeaderConfigController::getLayout(),
        ];
    }

    /**
     * Reordonne $classe->cours selon la configuration d'affichage des matieres
     * definie pour ce niveau (cycle + nom de promotion). Les matieres non
     * configurees sont placees a la fin, dans un ordre stable (sortBy de
     * Laravel preserve l'ordre relatif des elements a egalite de cle).
     */
    private function ordonnerCoursSelonConfig(Classe $classe): void
    {
        $promotion = $classe->promotion;
        if (!$promotion) {
            return;
        }

        $ordreMap = BulletinMatiereOrdre::where('cycle_id', $promotion->cycle_id)
            ->where('niveau', $promotion->nom)
            ->pluck('ordre', 'matiere_id');

        if ($ordreMap->isEmpty()) {
            return;
        }

        $classe->setRelation(
            'cours',
            $classe->cours->sortBy(fn ($cours) => $ordreMap->get($cours->matiere_id, PHP_INT_MAX))->values()
        );
    }

    /**
     * Construit les lignes du bulletin (notes par matière)
     */
    private function construireLignesBulletin(Eleve $eleve, Classe $classe, Trimestre $trimestre, array $notesIndex): array
    {
        $lignes = [];

        foreach ($classe->cours as $cours) {
            $lignes[] = [
                'notes_cours' => $this->calculerNotesCours($eleve->id, $cours, $trimestre->id, $notesIndex),
            ];
        }

        return $lignes;
    }

    /**
     * Extrait les moyennes et rangs d'un élève pour tous les trimestres
     */
    private function extraireMoyennesEleve(int $eleveId, array $moyennesParTrimestre, int $effectif): array
    {
        $result = [];

        foreach ($moyennesParTrimestre as $trimestreId => $moyennes) {
            $eleveData = collect($moyennes)->firstWhere('eleve_id', $eleveId);

            $result[$trimestreId] = [
                'rang' => $eleveData['rang'] ?? $effectif,
                'moyenne' => $eleveData['moyenne'] ?? 0,
                'eleve_id' => $eleveId,
            ];
        }

        return $result;
    }

    /**
     * Calcule la moyenne annuelle à partir des moyennes trimestrielles
     */
    private function calculerMoyenneAnnuelle(array $moyennes, ?int $nombreTrimestres = null): float
    {
        $somme = 0;
        foreach ($moyennes as $moy) {
            $somme += $moy['moyenne'];
        }

        $diviseur = $nombreTrimestres ?? count($moyennes);
        return $diviseur > 0 ? round($somme / $diviseur, 2) : 0;
    }

    /**
     * Convertit un nombre en lettres (français)
     * Exemple: 10.02 → "dix virgule zéro deux"
     * Exemple: 11.23 → "onze virgule vingt-trois"
     */
    private function nombreEnLettres(float $nombre): string
    {
        if ($nombre === 0.0) {
            return 'Zéro';
        }

        try {
            // Séparer partie entière et décimale
            $nombreStr = number_format($nombre, 2, '.', '');
            $parts = explode('.', $nombreStr);
            $partieEntiere = (int) $parts[0];
            $partieDecimale = $parts[1] ?? '00';

            // Convertir la partie entière
            $entierEnLettres = $this->convertirEntierEnLettres($partieEntiere);

            // Si pas de décimales significatives
            if ((int) $partieDecimale === 0) {
                return ucfirst($entierEnLettres);
            }

            // Convertir la partie décimale
            $decimalEnLettres = $this->convertirDecimaleEnLettres($partieDecimale);

            return ucfirst($entierEnLettres) . ' virgule ' . $decimalEnLettres;
        } catch (\Exception $e) {
            return strval($nombre);
        }
    }

    /**
     * Convertit un entier en lettres
     */
    private function convertirEntierEnLettres(int $nombre): string
    {
        $unites = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
                   'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $dizaines = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

        if ($nombre === 0) {
            return 'zéro';
        }

        if ($nombre < 20) {
            return $unites[$nombre];
        }

        if ($nombre < 100) {
            $dizaine = (int) ($nombre / 10);
            $unite = $nombre % 10;

            // Cas spéciaux pour 70-79 et 90-99
            if ($dizaine === 7 || $dizaine === 9) {
                $unite += 10;
            }

            $result = $dizaines[$dizaine];

            if ($unite === 1 && $dizaine !== 8 && $dizaine !== 9) {
                $result .= '-et-un';
            } elseif ($unite > 0) {
                $result .= '-' . $unites[$unite];
            } elseif ($dizaine === 8) {
                $result .= 's'; // quatre-vingts
            }

            return $result;
        }

        // Pour les nombres >= 100
        try {
            return SpellNumber::integer($nombre)->locale('fr')->toLetters();
        } catch (\Exception $e) {
            return strval($nombre);
        }
    }

    /**
     * Convertit la partie décimale en lettres
     * Si commence par 0: chiffre par chiffre (02 → "zéro deux")
     * Sinon: comme un nombre (23 → "vingt-trois")
     */
    private function convertirDecimaleEnLettres(string $decimale): string
    {
        $chiffres = ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];

        // Supprimer les zéros à droite pour l'affichage
        $decimale = rtrim($decimale, '0');

        if (empty($decimale)) {
            return '';
        }

        // Si commence par 0, convertir chiffre par chiffre
        if (str_starts_with($decimale, '0')) {
            $result = [];
            for ($i = 0; $i < strlen($decimale); $i++) {
                $result[] = $chiffres[(int) $decimale[$i]];
            }
            return implode(' ', $result);
        }

        // Sinon, convertir comme un nombre
        return $this->convertirEntierEnLettres((int) $decimale);
    }

    /**
     * Recupere les parametres de l'etablissement pour les templates LaTeX
     */
    private function getSchoolSettings(): array
    {
        return [
            'name' => Setting::get('school_name', 'Mon Avenir'),
            'full_name' => Setting::get('school_full_name', 'Complexe Prive Laique Mon Avenir'),
            'type' => Setting::get('school_type', 'COMPLEXE SCOLAIRE'),
            'motto' => Setting::get('school_motto', 'Travail - Discipline - Succes'),
            'bp' => Setting::get('school_bp', 'BP: 68'),
            'city' => Setting::get('school_city', 'SOKODE'),
            'country' => Setting::get('school_country', 'TOGO'),
            'phone' => Setting::get('school_phone', ''),
            'email' => Setting::get('school_email', ''),
            'address' => Setting::getFullAddress(),
        ];
    }
}
