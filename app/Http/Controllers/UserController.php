<?php

namespace App\Http\Controllers;

use App\Models\GameSaves\GameSave;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/** Administration des comptes utilisateurs et de leurs parties : liste, modification, suppression (réservé aux admins via le middleware de route). */
class UserController extends Controller
{
    /** Affiche tous les utilisateurs, chacun avec la liste (résumée) de ses parties. */
    public function index(): Response
    {
        $users = User::with(['gameSaves' => fn ($q) => $q->select(
                'id', 'user_id', 'label', 'season', 'week', 'phase', 'game_mode', 'competition_type', 'updated_at'
            )->orderByDesc('updated_at')])
            ->orderBy('created_at')
            ->get()
            ->map(fn (User $u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'is_admin'   => $u->isAdmin(),
                'created_at' => $u->created_at?->toIso8601String(),
                'game_saves' => $u->gameSaves->map(fn (GameSave $s) => [
                    'id'               => $s->id,
                    'label'            => $s->label,
                    'season'           => $s->season,
                    'week'             => $s->week,
                    'phase'            => $s->phase?->value,
                    'game_mode'        => $s->game_mode,
                    'competition_type' => $s->competition_type,
                    'updated_at'       => $s->updated_at?->toIso8601String(),
                ])->values(),
            ]);

        return Inertia::render('Users/Index', ['users' => $users]);
    }

    /** Modifie nom, e-mail, droit admin et (facultatif) mot de passe d'un utilisateur. */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'is_admin' => ['required', 'boolean'],
            'password' => ['nullable', 'string', Password::defaults()],
        ]);

        // Garde-fou : un admin ne peut pas se retirer lui-même ses droits (plus aucun admin possible).
        if ($user->is($request->user()) && !$data['is_admin']) {
            return back()->withErrors(['is_admin' => 'Tu ne peux pas retirer tes propres droits admin.']);
        }

        if ($data['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        $user->is_admin = $data['is_admin'];
        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    /** Supprime un utilisateur (ses parties et packages partent en cascade). Pas de suppression de soi-même. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Tu ne peux pas supprimer ton propre compte depuis cette page.');
        }

        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }

    /** Renomme une partie (le libellé uniquement : l'état de jeu n'est pas touché). */
    public function updateGameSave(Request $request, GameSave $gameSave): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $gameSave->update(['label' => $data['label']]);

        return back()->with('success', 'Partie mise à jour.');
    }

    /** Supprime une partie de n'importe quel utilisateur. */
    public function destroyGameSave(GameSave $gameSave): RedirectResponse
    {
        $gameSave->delete();

        return back()->with('success', 'Partie supprimée.');
    }
}
