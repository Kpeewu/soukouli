<?php

namespace App\Http\Controllers;

use App\Exports\ElevesExport;
use App\Imports\ElevesImport;
use App\Models\AnneeScolaire;
use App\Models\Assiduite;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Promotion;
use App\Models\User;
use App\Services\AnneeScolaireGenerationService;
use App\Services\EleveTransfertService;
use App\Services\ImageService;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;

class EleveController extends Controller
{
    use FiltersByCycle;

    protected ImageService $imageService;
    protected EleveTransfertService $transfertService;
    protected AnneeScolaireGenerationService $anneeService;

    public function __construct(
        ImageService $imageService,
        EleveTransfertService $transfertService,
        AnneeScolaireGenerationService $anneeService
    ) {
        $this->imageService = $imageService;
        $this->transfertService = $transfertService;
        $this->anneeService = $anneeService;
    }

    public function create()
    {
        if (!Auth::user()->isSecretaire()) {
            abort(403, 'Seuls les secrétaires peuvent inscrire un élève.');
        }

        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        // Filtrer les promotions par annee scolaire courante et par cycle accessible
        $promotions = Promotion::with('classes')
            ->where('annee_scolaire_id', $anneeCourante->id)
            ->whereIn('cycle_id', $this->getAccessibleCycleIds())
            ->orderBy('cycle_id')
            ->orderBy('ordre')
            ->get();

        return view('eleve.create', compact('promotions'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isSecretaire()) {
            abort(403, 'Seuls les secrétaires peuvent inscrire un élève.');
        }

        $request->validate([
            'date_naissance' => 'required|date|before:'. date('Y-m-j')
        ]);

        $url = url()->previous();

        // Vérifier que la classe appartient à un cycle accessible
        $classe = Classe::with('promotion')->find($request->classe_id);
        if (!$classe) {
            return redirect()->to($url)->with('notification', ['type' => 'danger', 'message' => 'Classe non trouvée']);
        }
        $this->authorizeAccessClasse($classe);

        $annee = AnneeScolaire::getAnneeScolaireActive()->annee;

        $pere = ['nom' => $request->nom_pere, 'prenom' => $request->prenom_pere, 'telephone' => $request->contact_pere, 'adresse' => $request->adresse_pere, 'profession' => $request->profession_pere, 'situation_matrimoniale' => $request->situation_matrimoniale_pere];

        $mere = ['nom' => $request->nom_mere, 'prenom' => $request->prenom_mere, 'telephone' => $request->contact_mere, 'adresse' => $request->adresse_mere, 'profession' => $request->profession_mere, 'situation_matrimoniale' => $request->situation_matrimoniale_mere];

        $contact_tuteur = ['nom' => $request->nom_tuteur, 'prenom' => $request->prenom_tuteur, 'telephone' => $request->contact_tuteur, 'adresse' => $request->adresse_tuteur, 'profession' => $request->profession_tuteur, 'situation_matrimoniale' => $request->situation_matrimoniale_tuteur];

        $sante = ['groupe' => $request->groupe_sanguin, 'problemes' => $request->problemes, 'restrictions' => $request->restrictions, 'medicaments' => $request->medicaments];

        $niveau = substr($classe->promotion->nom, 0, 3);

        $matricule = substr($request->prenom, 0, 1) . explode(' ', $request->nom)[0] . Str::random(1) . rand(0,9) . substr($annee, 0, 4);



        // Gestion de la photo de profil avec redimensionnement
        $profil = null;
        if ($request->hasFile('profil')) {
            $profil = $this->imageService->uploadStudentPhoto(
                $request->file('profil'),
                $annee,
                $classe->nom,
                $matricule
            );
        }

        $user = User::create([
            'username' => $matricule,
            'password' => Hash::make('monavenir1234'),
        ]);


        $eleve = Eleve::create([
            'nom' => strtoupper($request->nom),
            'prenom' => $request->prenom,
            'sexe' => $request->sexe,
            'profil' => $profil,
            'date_naissance' => $request->date_naissance,
            'lieu_naissance' => $request->lieu_naissance,
            'adresse' => $request->adresse,
            'matricule' => strtolower($matricule),
            'pere' => json_encode($pere),
            'mere' => json_encode($mere),
            'contact_tuteur' => json_encode($contact_tuteur),
            'sante' => json_encode($sante),
            'user_id' => $user->id,
        ]);

        $eleve->classes()->attach($classe);

        $trimestres = $classe->promotion->trimestres;

        foreach ($trimestres as $trimestre) {
            Assiduite::create([
                'trimestre_id' => $trimestre->id,
                'eleve_id' => $eleve->id
            ]);
        }

        return redirect()->to($url)->with('notification', ['type' => 'danger', 'message' => 'Nouvel élève inscrit']);
    }

    public function edit(Eleve $eleve, Classe $classe)
    {
        if (!Auth::user()->getSecretaireCycle() || !$this->canAccessEleve($eleve)) {
            abort(403, 'Seuls les secrétaires de cycle peuvent modifier les informations d\'un élève.');
        }

        $classeActuelle = $eleve->getClasseActuelle() ?? $classe;

        $classesDisponibles = Classe::where('promotion_id', $classeActuelle->promotion_id)
            ->where('id', '!=', $classeActuelle->id)
            ->orderBy('nom')
            ->get();

        $data = [
            'eleve' => $eleve,
            'classe' => $classeActuelle,
            'classes_disponibles' => $classesDisponibles
        ];

        return view('eleve.edit', $data);
    }

    public function update(Request $request, Eleve $eleve)
    {
        if (!Auth::user()->getSecretaireCycle() || !$this->canAccessEleve($eleve)) {
            abort(403, 'Seuls les secrétaires de cycle peuvent modifier les informations d\'un élève.');
        }

        $url = url()->previous();

        $annee = AnneeScolaire::getAnneeScolaireActive()->annee;

        $pere = ['nom' => $request->nom_pere, 'prenom' => $request->prenom_pere, 'telephone' => $request->contact_pere, 'adresse' => $request->adresse_pere, 'profession' => $request->profession_pere, 'situation_matrimoniale' => $request->situation_matrimoniale_pere];

        $mere = ['nom' => $request->nom_mere, 'prenom' => $request->prenom_mere, 'telephone' => $request->contact_mere, 'adresse' => $request->adresse_mere, 'profession' => $request->profession_mere, 'situation_matrimoniale' => $request->situation_matrimoniale_mere];

        $contact_tuteur = ['nom' => $request->nom_tuteur, 'prenom' => $request->prenom_tuteur, 'telephone' => $request->contact_tuteur, 'adresse' => $request->adresse_tuteur, 'profession' => $request->profession_tuteur, 'situation_matrimoniale' => $request->situation_matrimoniale_tuteur];

        $sante = ['groupe' => $request->groupe_sanguin, 'problemes' => $request->problemes, 'restrictions' => $request->restrictions, 'medicaments' => $request->medicaments];

        // La classe actuelle en base fait foi pour determiner s'il y a un
        // changement de classe a operer, pas la valeur soumise par le client.
        $ancienneClasse = $eleve->getClasseActuelle();
        $classe = Classe::find($request->classe_id);

        if (!$classe) {
            return redirect()->to($url)->with('notification', ['type' => 'danger', 'message' => 'Classe non trouvée']);
        }

        $classe_name_array =  explode(' ', substr($classe->nom, 0, 6));

        $classe_name_for_image_path = $classe_name_array[0] . $classe_name_array[1];


        // Le matricule est attribué une seule fois à la création (voir store()) et n'est jamais
        // recalculé ici : c'est un identifiant stable, pas une valeur dérivée du nom/prénom qui
        // devrait changer à chaque modification du profil (et qui pourrait alors entrer en
        // collision avec le matricule d'un autre élève).
        if ($request->photo_change === "1") {
            $profil = $request->file('profil')->storeAs($annee . '/' . $classe_name_for_image_path . '/' . $eleve->matricule . '.png');
        } else {
            $profil = $eleve->profil;
        }

        $changementClasse = null;

        DB::transaction(function () use ($eleve, $request, $profil, $pere, $mere, $contact_tuteur, $sante, $ancienneClasse, $classe, &$changementClasse) {
            $eleve->update([
                'nom' => strtoupper($request->nom),
                'prenom' => $request->prenom,
                'sexe' => $request->sexe,
                'profil' => $profil,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'adresse' => $request->adresse,
                'pere' => json_encode($pere),
                'mere' => json_encode($mere),
                'contact_tuteur' => json_encode($contact_tuteur),
                'sante' => json_encode($sante),
            ]);

            if ($ancienneClasse && $classe->id !== $ancienneClasse->id) {
                $this->authorizeAccessClasse($ancienneClasse);
                $this->authorizeAccessClasse($classe);
                $changementClasse = $this->transfertService->changerClasse($eleve, $ancienneClasse, $classe);
            } elseif (!$ancienneClasse) {
                $this->authorizeAccessClasse($classe);
                $eleve->classes()->attach($classe->id);
            }
        });

        $message = 'Elève modifié';
        if ($changementClasse) {
            $message .= '. Changement de classe : ' . $changementClasse['ancienne_classe'] . ' -> ' . $changementClasse['nouvelle_classe']
                . ' (' . $changementClasse['notes_reportees'] . ' note(s) de devoir/composition reportée(s))';
        }

        return redirect()->to($url)->with('notification', ['type' => 'success', 'message' => $message]);
    }


    public function destroy(Eleve $eleve)
    {
        $user = Auth::user();
        if (!$user->getSecretaireCycle() || !$this->canAccessEleve($eleve)) {
            abort(403, 'Seuls les secrétaires de cycle peuvent supprimer un élève.');
        }

        $url = url()->previous();
        $eleve->delete();
        return redirect()->to($url)->with('notification', ['type' => 'success', 'message' => 'Elève supprimé']);
    }

    public function passageAnneeSup(Request $request, Classe $classe)
    {
        $user = $request->user();
        if (!$user->isDirecteur() || $user->hasRole('directeur_general')) {
            abort(403, 'Seul le directeur du cycle peut valider le passage en classe supérieure.');
        }
        $this->authorizeAccessClasse($classe);

        $url = url()->previous();

        $eleves_id = explode(',', $request->eleves);

        $promotion = $classe->promotion;
        $cycle = $promotion->cycle;
        $niveauActuel = $promotion->nom;

        $currentAnneeScolaire = AnneeScolaire::getAnneeScolaire();
        if (!$currentAnneeScolaire) {
            return redirect()->to($url)->with('notification', [
                'type' => 'warning',
                'message' => 'Aucune année scolaire courante trouvée.'
            ]);
        }

        $nextLabel = $this->anneeService->calculerLabelAnneeSuivante();
        $nextYear = AnneeScolaire::where('annee', $nextLabel)->first();

        if (!$nextYear) {
            return redirect()->to($url)->with('notification', [
                'type' => 'warning',
                'message' => "Impossible de faire passer les élèves en classe supérieure, l'année scolaire suivante ({$nextLabel}) n'existe pas encore. Veuillez d'abord la générer."
            ]);
        }

        // Determiner le niveau suivant en utilisant les methodes dynamiques du Cycle
        $niveauSuivant = $cycle->getNiveauSuivant($niveauActuel);
        $cycleCible = $cycle;

        if ($niveauSuivant === null) {
            // Dernier niveau du cycle, verifier le passage au cycle suivant
            if ($cycle->hasCycleSuivant()) {
                $cycleCible = $cycle->cycleSuivant;
                $niveauSuivant = $cycleCible->getPremierNiveau();
            } else {
                return redirect()->to($url)->with('notification', [
                    'type' => 'warning',
                    'message' => 'Le passage en classe superieure est impossible pour les eleves de ' . $niveauActuel . ' (fin de scolarite)'
                ]);
            }
        }

        // Trouver la promotion cible
        $promotionSuivante = Promotion::with(['classes', 'trimestres'])
            ->where('annee_scolaire_id', $nextYear->id)
            ->where('cycle_id', $cycleCible->id)
            ->where('nom', $niveauSuivant)
            ->first();

        if (!$promotionSuivante || $promotionSuivante->classes->isEmpty()) {
            return redirect()->to($url)->with('notification', [
                'type' => 'danger',
                'message' => 'La classe de destination n\'existe pas pour l\'annee suivante'
            ]);
        }

        $classeSuivante = $promotionSuivante->classes->first();
        $elevesTraites = 0;

        foreach ($eleves_id as $id) {
            $eleve = Eleve::find($id);
            if (!$eleve) continue;

            // Verifier si l'eleve n'est pas deja inscrit dans une classe de la nouvelle annee
            $dejaInscrit = $eleve->classes()
                ->whereHas('promotion', function ($q) use ($nextYear) {
                    $q->where('annee_scolaire_id', $nextYear->id);
                })
                ->exists();

            if ($dejaInscrit) {
                continue;
            }

            // Inscrire l'eleve dans la nouvelle classe
            $eleve->classes()->attach($classeSuivante);
            $eleve->update(['redoublant' => false]);

            // Creer les enregistrements d'assiduite
            foreach ($promotionSuivante->trimestres as $trimestre) {
                Assiduite::firstOrCreate([
                    'trimestre_id' => $trimestre->id,
                    'eleve_id' => $eleve->id
                ]);
            }

            $elevesTraites++;
        }

        if ($elevesTraites === 0) {
            return redirect()->to($url)->with('notification', [
                'type' => 'warning',
                'message' => 'Les eleves selectionnes sont deja passes en classe superieure'
            ]);
        }

        return redirect()->to($url)->with('notification', [
            'type' => 'success',
            'message' => $elevesTraites . ' eleve(s) passe(s) en ' . $niveauSuivant . ' avec succes'
        ]);
    }

    public function export(Request $request, Classe $classe)
    {
        $formats = [
            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
            'csv' => \Maatwebsite\Excel\Excel::CSV,
            'ods' => \Maatwebsite\Excel\Excel::ODS,
        ];

        $format = $request->query('format', 'xlsx');
        abort_unless(array_key_exists($format, $formats), 400);

        return Excel::download(new ElevesExport($classe), $classe->nom . '.' . $format, $formats[$format]);
    }

    public function importPage(Classe $classe)
    {
        $data = [
            'classe' => $classe
        ];

        return view('eleve.import.form', $data);
    }

    public function import(Request $request)
    {
        $url = url()->previous();
        Excel::import(new ElevesImport, $request->file('excel'));
        return redirect()->to($url)->with('notification', ['type' => 'success', 'message' => 'Élèves importés']);
    }

    public function template()
    {
        return Storage::download('templates/template_eleves.xlsx');
    }
}
