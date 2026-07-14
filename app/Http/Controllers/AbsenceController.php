<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Assiduite;
use App\Models\Classe;
use App\Traits\FiltersByCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbsenceController extends Controller
{
    use FiltersByCycle;

    /**
     * Seuls les secretaires et les surveillants (de cycle ou general) peuvent
     * gerer les absences d'un eleve, dans la limite de leur cycle.
     */
    private function authorizeManageAssiduite(Classe $classe): void
    {
        $user = Auth::user();
        if ((!$user->isSecretaire() && !$user->isSurveillant()) || !$this->canAccessClasse($classe)) {
            abort(403, "Seuls les secrétaires et les surveillants peuvent gérer les absences d'un élève.");
        }
    }

    public function index(Assiduite $assiduite, Classe $classe)
    {
        $data = [
            'assiduite' => $assiduite,
            'classe' => $classe,
            'canManage' => (Auth::user()->isSecretaire() || Auth::user()->isSurveillant()) && $this->canAccessClasse($classe),
        ];

        return view('absence.index', $data);
    }

    public function create(Assiduite $assiduite, Classe $classe)
    {
        $this->authorizeManageAssiduite($classe);

        $data = [
            'assiduite' => $assiduite,
            'classe' => $classe,
        ];

        return view('absence.create', $data);
    }

    public function store(Request $request, Classe $classe)
    {
        $this->authorizeManageAssiduite($classe);

        $absence = Absence::create($request->all());

        return redirect()->route('retard.index', ['assiduite' => $absence->assiduite, 'classe' => $classe])->with('notification', ['type' => 'success', 'message' => 'Absence bien enregistrée']);
    }

    public function destroy(Absence $absence)
    {
        $user = Auth::user();
        if ((!$user->isSecretaire() && !$user->isSurveillant()) || !$this->canAccessEleve($absence->assiduite->eleve)) {
            abort(403, "Seuls les secrétaires et les surveillants peuvent gérer les absences d'un élève.");
        }

        $url = url()->previous();

        $absence->delete();

        return redirect()->to($url)->with('notification', ['type' => 'error', 'message' => 'Absence supprimé']);
    }
}
