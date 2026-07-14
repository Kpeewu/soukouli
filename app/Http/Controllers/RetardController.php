<?php

namespace App\Http\Controllers;

use App\Models\Assiduite;
use App\Models\Classe;
use App\Models\Retard;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RetardController extends Controller
{
    use FiltersByCycle;

    /**
     * Seuls les secretaires et les surveillants (de cycle ou general) peuvent
     * gerer les retards d'un eleve, dans la limite de leur cycle.
     */
    private function authorizeManageAssiduite(Classe $classe): void
    {
        $user = Auth::user();
        if ((!$user->isSecretaire() && !$user->isSurveillant()) || !$this->canAccessClasse($classe)) {
            abort(403, "Seuls les secrétaires et les surveillants peuvent gérer les retards d'un élève.");
        }
    }

    public function index(Assiduite $assiduite, Classe $classe)
    {
        $data = [
            'assiduite' => $assiduite,
            'classe' => $classe,
            'canManage' => (Auth::user()->isSecretaire() || Auth::user()->isSurveillant()) && $this->canAccessClasse($classe),
        ];

        return view('retard.index', $data);
    }

    public function create(Assiduite $assiduite, Classe $classe)
    {
        $this->authorizeManageAssiduite($classe);

        $data = [
            'assiduite' => $assiduite,
            'classe' => $classe,
        ];

        return view('retard.create', $data);
    }

    public function store(Request $request, Classe $classe)
    {
        $this->authorizeManageAssiduite($classe);

        $retard = Retard::create($request->all());

        return redirect()->route('retard.index', ['assiduite' => $retard->assiduite, 'classe' => $classe])->with('notification', ['type' => 'success', 'message' => 'Retard bien enregistré']);
    }

    public function destroy(Retard $retard)
    {
        $user = Auth::user();
        if ((!$user->isSecretaire() && !$user->isSurveillant()) || !$this->canAccessEleve($retard->assiduite->eleve)) {
            abort(403, "Seuls les secrétaires et les surveillants peuvent gérer les retards d'un élève.");
        }

        $url = url()->previous();

        $retard->delete();

        return redirect()->to($url)->with('notification', ['type' => 'error', 'message' => 'Retard supprimé']);
    }
}
