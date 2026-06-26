<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;
use App\Services\BonusCardActivationService;
use App\Services\BonusCardShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BonusCardController extends Controller
{
    use AuthorizesGameSave;

    public function __construct(
        private BonusCardShopService     $shopService,
        private BonusCardActivationService $activationService,
    ) {}

    /**
     * Achète une carte depuis la boutique hebdomadaire.
     */
    public function buy(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        if (!$gameSave->getConfig('bonus_cards_enabled', true)) {
            return back()->withErrors(['card' => 'Les cartes bonus sont désactivées dans cette sauvegarde.']);
        }

        $request->validate([
            'bonus_card_id' => 'required|integer|exists:bonus_cards,id',
            'tier'          => 'required|in:bronze,silver,gold',
        ]);

        $teamId = $gameSave->controlled_game_team_id;
        $team   = $gameSave->controlledGameTeam;

        if (!$team) abort(422, 'Équipe contrôlée introuvable.');

        // Vérifier que l'offre est bien dans la boutique de cette semaine
        $offers = $this->shopService->getOffersForTeam($gameSave, $teamId);
        $offer  = collect($offers)->firstWhere('bonus_card_id', (int) $request->bonus_card_id);

        if (!$offer) {
            return back()->withErrors(['card' => 'Cette carte n\'est pas disponible cette semaine.']);
        }

        $cost = (int) ($offer['cost'] ?? 0);

        if ($team->budget < $cost) {
            return back()->withErrors(['card' => 'Budget insuffisant.']);
        }

        // Vérifier que la carte n'a pas déjà été achetée cette semaine
        $alreadyBought = GameBonusCard::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $teamId)
            ->where('bonus_card_id', $offer['bonus_card_id'])
            ->where('purchased_season', $gameSave->season)
            ->where('purchased_week', $gameSave->week)
            ->exists();

        if ($alreadyBought) {
            return back()->withErrors(['card' => 'Vous avez déjà acheté cette carte cette semaine.']);
        }

        // Déduire le coût
        $team->budget -= $cost;
        $team->save();

        // Créer la carte en inventaire
        GameBonusCard::create([
            'game_save_id'     => $gameSave->id,
            'bonus_card_id'    => $offer['bonus_card_id'],
            'game_team_id'     => $teamId,
            'tier'             => $offer['tier'],
            'cost_paid'        => $cost,
            'status'           => 'available',
            'purchased_season' => $gameSave->season,
            'purchased_week'   => $gameSave->week,
        ]);

        return back()->with('success', "Carte \"{$offer['name']}\" achetée !");
    }

    /**
     * Active une carte depuis l'inventaire.
     */
    public function activate(Request $request, GameSave $gameSave, GameBonusCard $gameBonusCard): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $gameBonusCard);

        abort_unless(
            (int) $gameBonusCard->game_team_id === (int) $gameSave->controlled_game_team_id,
            403
        );

        $request->validate([
            'target_player_id' => 'nullable|integer|exists:game_players,id',
            'target_team_id'   => 'nullable|integer|exists:game_teams,id',
        ]);

        try {
            $result = $this->activationService->activate(
                $gameBonusCard,
                $gameSave,
                $request->input('target_player_id'),
                $request->input('target_team_id'),
            );
            return back()->with('success', $result['message'] ?? 'Carte activée !');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
