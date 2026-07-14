<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Cours;
use App\Models\Professeur;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoursController extends Controller
{
    use FiltersByCycle;

    /**
     * Seuls les secretaires de cycle (pas le secretaire general, ni les directeurs) peuvent
     * modifier les cours. Directeur general, secretaire general et directeurs de cycle n'ont
     * qu'un droit de consultation.
     */
    private function authorizeManageCours(): void
    {
        if (!Auth::user()->getSecretaireCycle()) {
            abort(403, 'Seuls les secrétaires de cycle peuvent modifier les cours.');
        }
    }

    /**
     * Afficher les cours d'une classe
     *
     * @param Classe $classe
     * @return void
     */
    public function index(Classe $classe)
    {
        $this->authorizeAccessClasse($classe);

        $data = [
            'classe' => $classe,
            'cours' => $classe->cours,
            'professeurs' => Professeur::all(),
            'canManage' => (bool) Auth::user()->getSecretaireCycle(),
        ];

        return view('cours.index', $data);
    }

    /**
     * Afficher les détails d'un cours
     *
     * @param Cours $cours
     * @return void
     */
    public function show(Cours $cours)
    {
        $this->authorizeAccessClasse($cours->classe);

        $data = [
            'cours' => $cours,
            'professeurs' => Professeur::all(),
            'canManage' => (bool) Auth::user()->getSecretaireCycle(),
        ];
        return view('cours.show', $data);
    }

    /**
     * Modifier les informations d'un cours
     *
     * @param Cours $cours
     * @param Request $request
     * @return void
     */
    public function update(Cours $cours, Request $request)
    {
        $this->authorizeAccessClasse($cours->classe);
        $this->authorizeManageCours();

        $cours->update([
            'professeur_id' => $request->professeur_id,
            'coefficient' => $request->coefficient,
        ]);
        return redirect()->route('cours.index', $cours->classe)->with('notification', ['type' => 'success', 'message' => 'Le cours à été mis à jour']);
    }
}
