<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Controllers\Controller;
use App\Http\Requests\GameSaves\SignFreeAgentRequest;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\FreeAgentService;
use App\Support\ActionOutcome;
use Illuminate\Http\RedirectResponse;

/**
 * AGENTS LIBRES.
 *
 * Gère une seule action : le recrutement d'un joueur libre par une équipe
 * (onglet « Mercato » de la page de jeu).
 *
 * Les règles (budget, durée, moral, numéro de maillot…) ne sont PAS ici : elles
 * sont dans App\Services\FreeAgentService. Ce contrôleur vérifie les droits,
 * pose le verrou de la sauvegarde, appelle le service puis traduit son résultat
 * en redirection.
 */
class FreeAgentController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Signe l'agent libre $player dans l'équipe choisie.
     *
     * Les données du formulaire (équipe, durée en semaines, motif) sont validées
     * par SignFreeAgentRequest. Le verrou de la sauvegarde évite qu'un double clic
     * crée deux contrats ou débite deux fois le budget : le joueur et l'équipe sont
     * relus sous verrou avant la décision.
     */
    public function signFreeAgent(SignFreeAgentRequest $request, GameSave $gameSave, GamePlayer $player, FreeAgentService $freeAgents): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $player);

        $data = $request->validated();

        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data, $player, $freeAgents) {
            $player->refresh();
            $team = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($data['team_id']);

            $outcome = $freeAgents->sign(
                $gameSave,
                $player,
                $team,
                (int) $data['matches_total'],
                $data['reason'] ?? null,
            );

            return $this->redirectFor($outcome);
        });
    }

    /**
     * Traduit le résultat du service en retour vers la page précédente :
     * message de succès, message d'information, ou erreur sur un champ du formulaire.
     */
    private function redirectFor(ActionOutcome $outcome): RedirectResponse
    {
        return match ($outcome->level) {
            ActionOutcome::ERROR => back()->withErrors([$outcome->field => $outcome->message]),
            ActionOutcome::INFO  => back()->with('info', $outcome->message),
            default              => back()->with('success', $outcome->message),
        };
    }
}
