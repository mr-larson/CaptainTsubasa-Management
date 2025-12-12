<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\GameSaveController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public routes (non authentifiées)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
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
        Route::post('{gameSave}/matches/{match}/finish', [GameSaveController::class, 'finishMatch'])
            ->name('matches.finish');

    });

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
