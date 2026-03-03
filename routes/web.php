<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\GameManagement\GameContractController;
use App\Http\Controllers\GameManagement\GamePlayerController;
use App\Http\Controllers\GameManagement\GameSaveController;
use App\Http\Controllers\GameManagement\GameTeamController;
use App\Http\Controllers\GameManagement\LineupController;
use App\Http\Controllers\GameManagement\TrainingController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public routes (non authentifiées)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'teams' => Team::select('id','name','budget', 'logo_path')
            ->orderBy('name')
            ->get(),

        'players' => Player::select('id','firstname','lastname','position','photo_path','stats')
            ->orderByRaw("JSON_EXTRACT(stats, '$.attack') DESC")
            ->take(50)
            ->get(),

        // 🔥 à rajouter :
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

    /*
    |----------------------------------------------------------------------
    | Menus principaux
    |----------------------------------------------------------------------
    */

    Route::get('/mainMenu', function () {
        return Inertia::render('MainMenu');
    })->name('mainMenu');

    Route::get('/dataBaseMenu', function () {
        return Inertia::render('DataBaseMenu');
    })->name('dataBaseMenu');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');


    /*
    |----------------------------------------------------------------------
    | Profil utilisateur
    |----------------------------------------------------------------------
    */

    Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');


    /*
    |----------------------------------------------------------------------
    | Equipes
    |----------------------------------------------------------------------
    | NB : on garde le nom historique 'teams' pour l'index
    |      car il est déjà utilisé côté contrôleurs / Vue.
    */
    Route::prefix('teams')->name('teams.')->group(function () {
        Route::get('/',        [TeamController::class, 'index'])->name('index');
        Route::get('/create',  [TeamController::class, 'create'])->name('create');
        Route::get('/edit',    [TeamController::class, 'edit'])->name('edit');
        Route::post('/',       [TeamController::class, 'store'])->name('store');
        Route::post('/{team}', [TeamController::class, 'update'])->name('update');
        Route::delete('/{team}', [TeamController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Joueurs
    |----------------------------------------------------------------------
    | Même logique : index = 'players' pour rester compatible.
    */

    Route::prefix('players')->name('players.')->group(function () {
        Route::get('/',          [PlayerController::class, 'index'])->name('index');
        Route::get('/create',    [PlayerController::class, 'create'])->name('create');
        Route::get('/edit',      [PlayerController::class, 'edit'])->name('edit');
        Route::post('/',         [PlayerController::class, 'store'])->name('store');
        Route::post('/{player}', [PlayerController::class, 'update'])->name('update');
        Route::delete('/{player}', [PlayerController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Contrats
    |----------------------------------------------------------------------
    */

    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/',            [ContractController::class, 'index'])->name('index');
        Route::get('/create',      [ContractController::class, 'create'])->name('create');
        Route::get('/edit',        [ContractController::class, 'edit'])->name('edit');
        Route::post('/',           [ContractController::class, 'store'])->name('store');
        Route::post('/{contract}', [ContractController::class, 'update'])->name('update');
        Route::delete('/{contract}', [ContractController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Game saves session
    |----------------------------------------------------------------------
    */
    Route::prefix('game-saves')->name('game-saves.')->group(function () {
        Route::get('/', [GameSaveController::class, 'index'])->name('index');
        Route::get('/create', [GameSaveController::class, 'create'])->name('create');
        Route::post('/', [GameSaveController::class, 'store'])->name('store');
        Route::post('/start', [GameSaveController::class, 'start'])->name('start');

        Route::get('/continue', [GameSaveController::class, 'continue'])->name('continue');

        Route::get('/{gameSave}', [GameSaveController::class, 'show'])->name('show');
        Route::get('/{gameSave}/play', [GameSaveController::class, 'play'])->name('play');
        Route::post('/{gameSave}/free-agents/{player}/sign', [GameSaveController::class, 'signFreeAgent'])->name('free-agents.sign');
        Route::get('/{gameSave}/match', [GameSaveController::class, 'match'])->name('match');
        Route::put('/{gameSave}', [GameSaveController::class, 'update'])->name('update');
        Route::delete('/{gameSave}', [GameSaveController::class, 'destroy'])->name('destroy');
        Route::post('/{gameSave}/matches/{match}/finish', [GameSaveController::class, 'finishMatch'])
            ->name('matches.finish');
        Route::post('/{gameSave}/simulate-week', [GameSaveController::class, 'simulateWeek'])
            ->name('simulate-week');
        Route::post('/{gameSave}/training', [TrainingController::class, 'store'])
            ->name('training.store');
        Route::post('/{gameSave}/lineup', [LineupController::class, 'update'])
            ->name('lineup.update');

        Route::get('/{gameSave}/teams',            [GameTeamController::class, 'index'])->name('teams.index');
        Route::get('/{gameSave}/teams/create',     [GameTeamController::class, 'create'])->name('teams.create');
        Route::post('/{gameSave}/teams',           [GameTeamController::class, 'store'])->name('teams.store');
        Route::get('/{gameSave}/teams/{team}/edit',[GameTeamController::class, 'edit'])->name('teams.edit');
        Route::post('/{gameSave}/teams/{team}',    [GameTeamController::class, 'update'])->name('teams.update');
        Route::delete('/{gameSave}/teams/{team}',  [GameTeamController::class, 'destroy'])->name('teams.destroy');

        Route::get('/{gameSave}/players',  [GamePlayerController::class,  'index'])->name('players.index');
        Route::get('/{gameSave}/contracts',[GameContractController::class,'index'])->name('contracts.index');

    });
    Route::patch('/game-contracts/{contract}/toggle-starter',
        [LineupController::class, 'toggleStarter'])
        ->name('game-contracts.toggle-starter');


    /*
    |----------------------------------------------------------------------
    | Gameplay
    |----------------------------------------------------------------------
    */
    Route::get('/match/demo', function () {
        return Inertia::render('Match/Engine');
    })->name('match.demo');

});

require __DIR__.'/auth.php';
