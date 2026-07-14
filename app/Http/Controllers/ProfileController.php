<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:30',
            'civilite' => 'nullable|in:M,Mme',
        ]);

        $user->update($request->only(['nom', 'prenom', 'telephone', 'civilite']));

        if ($request->filled('password')) {
            $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
            }

            $user->update(['password' => $request->password]);
        }

        return redirect()->route('profile.edit')
            ->with('notification', ['type' => 'success', 'message' => 'Profil mis à jour avec succès']);
    }
}
