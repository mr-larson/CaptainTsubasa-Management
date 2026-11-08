<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Controllers\Controller;
use App\Models\GameSaves\GameSave;
use App\Services\PlayerChemistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GamePlayerChemistryController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    public function store(Request $request, GameSave $gameSave, PlayerChemistryService $chemistry): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);
        $data = $request->validate([
            'player_id' => ['required', 'integer', 'min:1'],
            'partner_id' => ['required', 'integer', 'min:1', 'different:player_id'],
        ]);

        return $this->withSaveLock($gameSave, function (GameSave $save) use ($data, $chemistry) {
            abort_unless($save->isInMatchPhase() && $save->controlled_game_team_id, 403);
            $confirmed = $chemistry->attempt($save, (int) $save->controlled_game_team_id, $data['player_id'], $data['partner_id']);

            return $confirmed
                ? back()->with('success', 'Duo confirmé. Le une-deux est disponible lorsque les deux joueurs occupent des lignes compatibles.')
                : back()->with('info', 'Le duo n’a pas encore trouvé ses automatismes. Il pourra réessayer après trois nouveaux matchs ensemble.');
        });
    }
}
