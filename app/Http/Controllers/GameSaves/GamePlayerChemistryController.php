<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Requests\GameSaves\StoreChemistryRequest;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Controllers\Controller;
use App\Models\GameSaves\GameSave;
use App\Services\PlayerChemistryService;
use Illuminate\Http\RedirectResponse;

/** Alchimie entre joueurs : tentative de confirmer un duo (une-deux) entre deux joueurs de l'équipe contrôlée. */
class GamePlayerChemistryController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /** Tente de confirmer un duo entre deux joueurs de l'équipe contrôlée. Vérifie le droit de modifier la sauvegarde, prend le verrou et refuse (403) hors phase de match ou sans équipe contrôlée. Redirige en arrière avec un message de succès (duo confirmé) ou d'information (pas encore d'automatismes, nouvel essai après trois matchs). */
    public function store(StoreChemistryRequest $request, GameSave $gameSave, PlayerChemistryService $chemistry): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);
        $data = $request->validated();

        return $this->withSaveLock($gameSave, function (GameSave $save) use ($data, $chemistry) {
            abort_unless($save->isInMatchPhase() && $save->controlled_game_team_id, 403);
            $confirmed = $chemistry->attempt($save, (int) $save->controlled_game_team_id, $data['player_id'], $data['partner_id']);

            return $confirmed
                ? back()->with('success', 'Duo confirmé. Le une-deux est disponible lorsque les deux joueurs occupent des lignes compatibles.')
                : back()->with('info', 'Le duo n’a pas encore trouvé ses automatismes. Il pourra réessayer après trois nouveaux matchs ensemble.');
        });
    }
}
