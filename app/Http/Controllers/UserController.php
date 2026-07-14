<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cycle;
use App\Models\Professeur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private function isProfesseurRole(?string $roleName): bool
    {
        return $roleName === 'professeur' || str_starts_with((string) $roleName, 'professeur_');
    }

    public function index()
    {
        $users = User::with('roles', 'professeur')->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        // Les comptes professeur sont crees automatiquement depuis la gestion des enseignants
        $roles = Role::all()->reject(fn ($role) => $this->isProfesseurRole($role->name))->values();
        $cycles = Cycle::orderBy('ordre')->get();
        return view('admin.users.create', compact('roles', 'cycles'));
    }

    public function store(Request $request)
    {
        $validationRules = [
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:30',
            'civilite' => 'nullable|in:M,Mme',
        ];

        // Si le rôle est professeur, le professeur_id est requis
        if ($this->isProfesseurRole($request->role)) {
            $validationRules['professeur_id'] = 'required|exists:professeurs,id';
        }

        $request->validate($validationRules);

        $user = User::create([
            'username' => $request->username,
            'password' => $request->password,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'telephone' => $request->telephone,
            'civilite' => $request->civilite,
        ]);

        $user->assignRole($request->role);

        // Si c'est un professeur, lier le compte utilisateur au professeur
        if ($this->isProfesseurRole($request->role) && $request->professeur_id) {
            $professeur = Professeur::find($request->professeur_id);
            $professeur->update(['user_id' => $user->id]);
        }

        return redirect()->route('users.index')
            ->with('notification', ['type' => 'success', 'message' => 'Utilisateur créé avec succès']);
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $cycles = Cycle::orderBy('ordre')->get();
        // Récupérer les professeurs disponibles (sans user_id ou avec l'user_id actuel)
        $professeursDisponibles = Professeur::where(function ($query) use ($user) {
            $query->whereNull('user_id')
                  ->orWhere('user_id', $user->id);
        })->get();
        return view('admin.users.edit', compact('user', 'roles', 'cycles', 'professeursDisponibles'));
    }

    public function update(Request $request, User $user)
    {
        $validationRules = [
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'role' => 'required|exists:roles,name',
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:30',
            'civilite' => 'nullable|in:M,Mme',
        ];

        // Si le rôle est professeur, le professeur_id est requis
        if ($this->isProfesseurRole($request->role)) {
            $validationRules['professeur_id'] = 'required|exists:professeurs,id';
        }

        $request->validate($validationRules);

        $user->update([
            'username' => $request->username,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'telephone' => $request->telephone,
            'civilite' => $request->civilite,
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8|confirmed']);
            $user->update(['password' => $request->password]);
        }

        // Gestion du changement de rôle
        $oldRole = $user->roles->first()?->name;
        $newRole = $request->role;

        // Si l'ancien rôle était professeur, délier le professeur
        if ($this->isProfesseurRole($oldRole) && !$this->isProfesseurRole($newRole)) {
            if ($user->professeur) {
                $user->professeur->update(['user_id' => null]);
            }
        }

        $user->syncRoles([$newRole]);

        // Si le nouveau rôle est professeur, lier le professeur
        if ($this->isProfesseurRole($newRole) && $request->professeur_id) {
            // D'abord, délier l'ancien professeur si différent
            if ($user->professeur && $user->professeur->id != $request->professeur_id) {
                $user->professeur->update(['user_id' => null]);
            }
            // Lier le nouveau professeur
            $professeur = Professeur::find($request->professeur_id);
            $professeur->update(['user_id' => $user->id]);
        }

        return redirect()->route('users.index')
            ->with('notification', ['type' => 'success', 'message' => 'Utilisateur modifié avec succès']);
    }

    public function destroy(User $user)
    {
        // Empêcher la suppression de l'admin principal
        if ($user->username === 'monavenir') {
            return redirect()->route('users.index')
                ->with('notification', ['type' => 'danger', 'message' => 'Impossible de supprimer l\'administrateur principal']);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('notification', ['type' => 'success', 'message' => 'Utilisateur supprimé avec succès']);
    }
}
