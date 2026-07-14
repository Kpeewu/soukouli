<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Cycle;
use App\Models\ExamenOfficiel;
use App\Models\Promotion;
use Illuminate\Http\Request;

class ExamenOfficielController extends Controller
{
    public function index()
    {
        $examens = ExamenOfficiel::with('cycle')->orderBy('cycle_id')->get();

        return view('admin.examens-officiels.index', compact('examens'));
    }

    public function create()
    {
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        $cycles = Cycle::with(['promotions' => function($query) use ($anneeCourante) {
            $query->where('annee_scolaire_id', $anneeCourante->id)->orderBy('ordre');
        }])->orderBy('ordre')->get();

        return view('admin.examens-officiels.create', compact('cycles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:examens_officiels,code',
            'cycle_id' => 'required|exists:cycles,id',
            'niveau_requis' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        $examen = ExamenOfficiel::create($request->all());

        // Lier les promotions correspondantes a cet examen
        $promotionsLiees = Promotion::where('cycle_id', $request->cycle_id)
            ->where('nom', $request->niveau_requis)
            ->update([
                'a_examen_officiel' => true,
                'examen_officiel_id' => $examen->id
            ]);

        $message = 'Examen officiel cree avec succes';
        if ($promotionsLiees > 0) {
            $message .= " ($promotionsLiees promotion(s) liee(s))";
        }

        return redirect()->route('examens-officiels.index')
            ->with('notification', ['type' => 'success', 'message' => $message]);
    }

    public function edit(ExamenOfficiel $examens_officiel)
    {
        $anneeCourante = AnneeScolaire::getAnneeScolaireActive();

        $cycles = Cycle::with(['promotions' => function($query) use ($anneeCourante) {
            $query->where('annee_scolaire_id', $anneeCourante->id)->orderBy('ordre');
        }])->orderBy('ordre')->get();

        $examen = $examens_officiel;

        return view('admin.examens-officiels.edit', compact('examen', 'cycles'));
    }

    public function update(Request $request, ExamenOfficiel $examens_officiel)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:examens_officiels,code,' . $examens_officiel->id,
            'cycle_id' => 'required|exists:cycles,id',
            'niveau_requis' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);

        // Delier les anciennes promotions si le cycle ou niveau change
        if ($examens_officiel->cycle_id != $request->cycle_id ||
            $examens_officiel->niveau_requis != $request->niveau_requis) {

            Promotion::where('examen_officiel_id', $examens_officiel->id)
                ->update([
                    'a_examen_officiel' => false,
                    'examen_officiel_id' => null
                ]);
        }

        $examens_officiel->update($request->all());

        // Lier les nouvelles promotions correspondantes
        Promotion::where('cycle_id', $request->cycle_id)
            ->where('nom', $request->niveau_requis)
            ->update([
                'a_examen_officiel' => true,
                'examen_officiel_id' => $examens_officiel->id
            ]);

        return redirect()->route('examens-officiels.index')
            ->with('notification', ['type' => 'success', 'message' => 'Examen officiel mis a jour']);
    }

    public function destroy(ExamenOfficiel $examens_officiel)
    {
        // Delier les promotions avant suppression
        Promotion::where('examen_officiel_id', $examens_officiel->id)
            ->update([
                'a_examen_officiel' => false,
                'examen_officiel_id' => null
            ]);

        $examens_officiel->delete();

        return redirect()->route('examens-officiels.index')
            ->with('notification', ['type' => 'success', 'message' => 'Examen officiel supprime']);
    }
}
