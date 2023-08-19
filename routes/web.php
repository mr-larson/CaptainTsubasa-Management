<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mainMenu', function () {
        return Inertia::render('MainMenu');
    })->name('mainMenu');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/teams', function () {
        return Inertia::render('Teams/Index');
    })->name('teams');

    Route::get('/teams/create', function () {
        return Inertia::render('Teams/Create');
    })->name('teams.create');

    Route::get('/teams/{team}/edit', function ($team) {
        $team = App\Models\Team::find($team);  // Trouvez l'équipe par son ID
        $allTeams = App\Models\Team::all();    // Récupérez toutes les équipes pour la sidebar

        return Inertia::render('Teams/Edit', [
            'team' => $team,
            'allTeams' => $allTeams
        ]);
    })->name('teams.edit');

    Route::get('/players', function () {
        return Inertia::render('Players/Index');
    })->name('players');

    Route::get('/players/create', function () {
        return Inertia::render('Players/Create');
    })->name('players.create');

    Route::get('/players/{player}/edit', function () {
        return Inertia::render('Players/Edit');
    })->name('players.edit');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
