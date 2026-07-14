<?php

namespace App\Http\Controllers;

use App\Models\TypeFrais;
use Illuminate\Http\Request;

class TypeFraisController extends Controller
{
    public function index()
    {
        $typesFrais = TypeFrais::withCount('configurations')->orderBy('nom')->get();

        return view('comptabilite.admin.types-frais.index', compact('typesFrais'));
    }

    public function create()
    {
        return view('comptabilite.admin.types-frais.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:types_frais,code',
            'description' => 'nullable|string',
            'obligatoire' => 'boolean',
        ]);

        TypeFrais::create([
            'nom' => $request->nom,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'obligatoire' => $request->boolean('obligatoire', true),
            'actif' => true,
        ]);

        return redirect()->route('types-frais.index')
            ->with('notification', ['type' => 'success', 'message' => 'Type de frais cree avec succes']);
    }

    public function edit(TypeFrais $types_frai)
    {
        return view('comptabilite.admin.types-frais.edit', ['typeFrais' => $types_frai]);
    }

    public function update(Request $request, TypeFrais $types_frai)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:types_frais,code,' . $types_frai->id,
            'description' => 'nullable|string',
            'obligatoire' => 'boolean',
            'actif' => 'boolean',
        ]);

        $types_frai->update([
            'nom' => $request->nom,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'obligatoire' => $request->boolean('obligatoire', true),
            'actif' => $request->boolean('actif', true),
        ]);

        return redirect()->route('types-frais.index')
            ->with('notification', ['type' => 'success', 'message' => 'Type de frais mis a jour']);
    }

    public function destroy(TypeFrais $types_frai)
    {
        if ($types_frai->configurations()->exists()) {
            return redirect()->route('types-frais.index')
                ->with('notification', ['type' => 'error', 'message' => 'Ce type de frais est utilise dans des configurations']);
        }

        $types_frai->delete();

        return redirect()->route('types-frais.index')
            ->with('notification', ['type' => 'success', 'message' => 'Type de frais supprime']);
    }
}
