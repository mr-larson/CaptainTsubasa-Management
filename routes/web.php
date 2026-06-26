<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\GameSaves\BonusCardController;
use App\Http\Controllers\GameSaves\GameContractController;
use App\Http\Controllers\GameSaves\GameDraftController;
use App\Http\Controllers\GameSaves\GameSeasonController;
use App\Http\Controllers\GameSaves\GameMatchController;
use App\Http\Controllers\GameSaves\GamePlayerController;
use App\Http\Controllers\GameSaves\GameDeclarationController;
use App\Http\Controllers\GameSaves\GamePromiseController;
use App\Http\Controllers\GameSaves\GameSaveController;
use App\Http\Controllers\GameSaves\GameTeamController;
use App\Http\Controllers\GameSaves\LineupController;
use App\Http\Controllers\GameSaves\TrainingController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'teams' => Team::select('id','name','budget','logo_path')->orderBy('name')->get(),
        'players' => Player::select('id','firstname','lastname','position','photo_path','stats')
            ->orderByRaw("JSON_EXTRACT(stats, '$.attack') DESC")
            ->take(50)
            ->get(),
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('welcome');

/*
|--------------------------------------------------------------------------
| Routes protégées (auth)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Menus
    Route::get('/mainMenu',    fn() => Inertia::render('MainMenu'))->name('mainMenu');
    Route::get('/dataBaseMenu',fn() => Inertia::render('DataBaseMenu'))->name('dataBaseMenu');
    Route::get('/dashboard',   fn() => Inertia::render('Dashboard'))->name('dashboard');

    // Profil
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Équipes (base) — données de référence : gestion réservée aux admins
    Route::prefix('teams')->name('teams.')->middleware('admin')->group(function () {
        Route::get('/',          [TeamController::class, 'index'])->name('index');
        Route::get('/create',    [TeamController::class, 'create'])->name('create');
        Route::get('/edit',      [TeamController::class, 'edit'])->name('edit');
        Route::post('/',         [TeamController::class, 'store'])->name('store');
        Route::post('/{team}',   [TeamController::class, 'update'])->name('update');
        Route::delete('/{team}', [TeamController::class, 'destroy'])->name('destroy');
    });

    // Joueurs (base) — données de référence : gestion réservée aux admins
    Route::prefix('players')->name('players.')->middleware('admin')->group(function () {
        Route::get('/',            [PlayerController::class, 'index'])->name('index');
        Route::get('/create',      [PlayerController::class, 'create'])->name('create');
        Route::get('/edit',        [PlayerController::class, 'edit'])->name('edit');
        Route::post('/',           [PlayerController::class, 'store'])->name('store');
        Route::post('/{player}',   [PlayerController::class, 'update'])->name('update');
        Route::delete('/{player}', [PlayerController::class, 'destroy'])->name('destroy');
    });

    // Contrats (base) — données de référence : gestion réservée aux admins
    Route::prefix('contracts')->name('contracts.')->middleware('admin')->group(function () {
        Route::get('/',              [ContractController::class, 'index'])->name('index');
        Route::get('/create',        [ContractController::class, 'create'])->name('create');
        Route::get('/edit',          [ContractController::class, 'edit'])->name('edit');
        Route::post('/',             [ContractController::class, 'store'])->name('store');
        Route::post('/{contract}',   [ContractController::class, 'update'])->name('update');
        Route::delete('/{contract}', [ContractController::class, 'destroy'])->name('destroy');
    });

    // Game saves
    Route::prefix('game-saves')->name('game-saves.')->group(function () {

        // CRUD saves
        Route::get('/',        [GameSaveController::class, 'index'])->name('index');
        Route::get('/create',  [GameSaveController::class, 'create'])->name('create');
        Route::post('/',       [GameSaveController::class, 'store'])->name('store');
        Route::post('/start',  [GameSaveController::class, 'start'])->name('start');
        Route::post('/world-cup/start', [GameSaveController::class, 'startWorldCup'])->name('start-world-cup');
        Route::get('/continue',[GameSaveController::class, 'continue'])->name('continue');
        Route::get('/{gameSave}/draft', [GameDraftController::class, 'show'])->name('draft');
        Route::post('/{gameSave}/draft/ai-pick', [GameDraftController::class, 'aiPick'])->name('draft.ai-pick');
        Route::post('/{gameSave}/draft/pick', [GameDraftController::class, 'pick'])->name('draft.pick');
        Route::post('/{gameSave}/draft/finish', [GameDraftController::class, 'finish'])->name('draft.finish');
        Route::get('/{gameSave}/season-end',  [GameSeasonController::class, 'show'])->name('season-end');
        Route::post('/{gameSave}/season-end/continue', [GameSeasonController::class, 'continue'])->name('season-end.continue');
        Route::get('/{gameSave}',      [GameSaveController::class, 'show'])->name('show');
        Route::get('/{gameSave}/Play', [GameSaveController::class, 'play'])->name('Play');
        Route::put('/{gameSave}',      [GameSaveController::class, 'update'])->name('update');
        Route::put('/{gameSave}/config', [GameSaveController::class, 'updateConfig'])->name('config.update');
        Route::delete('/{gameSave}',   [GameSaveController::class, 'destroy'])->name('destroy');

        // Agents libres
        Route::post('/{gameSave}/free-agents/{player}/sign',
            [GameSaveController::class, 'signFreeAgent'])->name('free-agents.sign');

        // ── Matchs → GameMatchController ──────────────────────────
        Route::get('/{gameSave}/match',
            [GameMatchController::class, 'match'])->name('match');
        Route::post('/{gameSave}/matches/{match}/finish',
            [GameMatchController::class, 'finishMatch'])->name('matches.finish');
        Route::post('/{gameSave}/simulate-week',
            [GameMatchController::class, 'simulateWeek'])->name('simulate-week');

        // Entraînement
        Route::post('/{gameSave}/training',
            [TrainingController::class, 'store'])->name('training.store');

        // Lineup & formation
        Route::post('/{gameSave}/lineup',
            [LineupController::class, 'update'])->name('lineup.update');
        Route::post('/{gameSave}/lineup/formation',
            [LineupController::class, 'updateFormation'])->name('lineup.formation');
        Route::post('/{gameSave}/lineup/substitute', [LineupController::class, 'substitute'])
            ->name('lineup.substitute');

        // Équipes de partie
        Route::get('/{gameSave}/teams',             [GameTeamController::class, 'index'])->name('teams.index');
        Route::get('/{gameSave}/teams/create',      [GameTeamController::class, 'create'])->name('teams.create');
        Route::post('/{gameSave}/teams',            [GameTeamController::class, 'store'])->name('teams.store');
        Route::get('/{gameSave}/teams/{team}/edit', [GameTeamController::class, 'edit'])->name('teams.edit');
        Route::post('/{gameSave}/teams/{team}',     [GameTeamController::class, 'update'])->name('teams.update');
        Route::delete('/{gameSave}/teams/{team}',   [GameTeamController::class, 'destroy'])->name('teams.destroy');

        // Joueurs de partie
        Route::get('/{gameSave}/players',                   [GamePlayerController::class, 'index'])->name('players.index');
        Route::get('/{gameSave}/players/create',            [GamePlayerController::class, 'create'])->name('players.create');
        Route::post('/{gameSave}/players',                  [GamePlayerController::class, 'store'])->name('players.store');
        Route::get('/{gameSave}/players/{player}/edit',     [GamePlayerController::class, 'edit'])->name('players.edit');
        Route::put('/{gameSave}/players/{player}',          [GamePlayerController::class, 'update'])->name('players.update');
        Route::delete('/{gameSave}/players/{player}',       [GamePlayerController::class, 'destroy'])->name('players.destroy');
        Route::patch('/{gameSave}/players/{player}/number', [GamePlayerController::class, 'updateNumber'])
            ->name('players.update-number');

        // Promesses (relation coach)
        Route::post('/{gameSave}/players/{player}/promises',
            [GamePromiseController::class, 'store'])->name('players.promises.store');

        // Déclarations publiques (relation coach)
        Route::post('/{gameSave}/players/{player}/declarations',
            [GameDeclarationController::class, 'store'])->name('players.declarations.store');

        // Contrats de partie
        Route::post('/{gameSave}/players/{player}/contracts',
            [GameContractController::class, 'store'])->name('contracts.store');
        Route::put('/{gameSave}/contracts/{contract}',
            [GameContractController::class, 'update'])->name('contracts.update');
        Route::delete('/{gameSave}/contracts/{contract}',
            [GameContractController::class, 'destroy'])->name('contracts.destroy');
        Route::delete('/{gameSave}/contracts/{contract}/release',
            [GameContractController::class, 'release'])->name('contracts.release');

        // Routes JSON pour le match engine
        Route::post('/{gameSave}/captain-reroll/{contract}',
            [GameMatchController::class, 'useCaptainReroll'])->name('captain-reroll.use');

        Route::post('/{gameSave}/captain-reroll/{contract}/reset-action-flag',
            [GameMatchController::class, 'resetCaptainRerollActionFlag'])->name('captain-reroll.reset-flag');

        // Bonus Card
        Route::post('/{gameSave}/bonus-cards/buy',
            [BonusCardController::class, 'buy'])
            ->name('bonus-cards.buy');

        Route::post('/{gameSave}/bonus-cards/{gameBonusCard}/activate',
            [BonusCardController::class, 'activate'])
            ->name('bonus-cards.activate');
    });

    // Toggle titulaire
    Route::patch('/game-contracts/{contract}/toggle-starter',
        [LineupController::class, 'toggleStarter'])->name('game-contracts.toggle-starter');
    Route::patch('/game-contracts/{contract}/toggle-captain',
        [LineupController::class, 'toggleCaptain'])->name('game-contracts.toggle-captain');

    // Match démo
    Route::get('/match/demo', fn() => Inertia::render('Match/Engine'))->name('match.demo');

    //Test — réservé aux admins (route de debug, expose des données de partie)
    Route::get('/debug/styles/{gameSave}', function (\App\Models\GameSaves\GameSave $gameSave) {
        return \App\Models\GameSaves\GameTeam::where('game_save_id', $gameSave->id)
            ->get(['name', 'tactical_style', 'management_philosophy']);
    })->middleware('admin');
});

require __DIR__.'/auth.php';
