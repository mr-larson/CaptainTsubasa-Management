<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Requests\GameSaves\ActivateBonusCardRequest;
use App\Http\Requests\GameSaves\BuyBonusCardRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;
use App\Services\Cards\BonusCardActivationService;
use App\Services\Cards\BonusCardShopService;
use Illuminate\Http\RedirectResponse;

/** Cartes bonus d'une sauvegarde : achat dans la boutique hebdomadaire et activation d'une carte possédée. */
class BonusCardController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /** Injecte les services de boutique (achat) et d'activation (effet des cartes). */
    public function __construct(
        private BonusCardShopService     $shopService,
        private BonusCardActivationService $activationService,
    ) {}

    /**
     * Achète une carte depuis la boutique hebdomadaire.
     */
    public function buy(BuyBonusCardRequest $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        if (!$gameSave->getConfig('bonus_cards_enabled', true)) {
            return back()->withErrors(['card' => 'Les cartes bonus sont désactivées dans cette sauvegarde.']);
        }

        // Verrou par save : un double clic n'achète pas deux fois la carte
        // (vérification « déjà achetée » + débit du budget sous verrou).
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($request) {
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
        });
    }

    /**
     * Active une carte depuis l'inventaire.
     */
    public function activate(ActivateBonusCardRequest $request, GameSave $gameSave, GameBonusCard $gameBonusCard): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $gameBonusCard);

        abort_unless(
            (int) $gameBonusCard->game_team_id === (int) $gameSave->controlled_game_team_id,
            403
        );

        try {
            // Verrou par save : un double clic voit la carte déjà « used » et
            // n'applique pas l'effet deux fois. Une erreur de validation levée
            // par le service annule aussi les écritures partielles.
            $result = $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($request, $gameBonusCard) {
                $gameBonusCard->refresh();

                $result = $this->activationService->activate(
                    $gameBonusCard,
                    $gameSave,
                    $request->input('target_player_id'),
                    $request->input('target_team_id'),
                );
                app(\App\Services\Season\SeasonChallengeService::class)->payCompleted(
                    $gameSave, [(int) $gameBonusCard->game_team_id], ['cards_used_min']
                );

                return $result;
            });
            return back()->with('success', $result['message'] ?? 'Carte activée !');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
