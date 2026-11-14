<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodPackageRequest;
use App\Models\PeriodPackage;
use App\Services\Game\PeriodPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Bibliothèque personnelle de packages de période (façon mod) : chaque
 * utilisateur peut en déposer, et choisir un package visible (le sien ou un
 * package partagé) au moment de créer une GameSave — cf. GameSaveController.
 */
class PeriodPackageController extends Controller
{
    /** Liste les packages de période visibles par l'utilisateur (les siens et les partagés), du plus récent au plus ancien. Affiche la page PeriodPackages/Index. */
    public function index(Request $request): Response
    {
        $packages = PeriodPackage::query()
            ->visibleTo($request->user())
            ->orderByDesc('created_at')
            ->get(['id', 'user_id', 'name', 'description', 'is_shared', 'player_count', 'team_count', 'created_at']);

        return Inertia::render('PeriodPackages/Index', [
            'packages' => $packages,
        ]);
    }

    /** Affiche le formulaire d'import d'un nouveau package de période (page PeriodPackages/Create). */
    public function create(): Response
    {
        return Inertia::render('PeriodPackages/Create');
    }

    /** Importe un package depuis le zip envoyé (nom, description, partage) via PeriodPackageService, puis redirige vers l'écran de création de partie ou vers la bibliothèque selon la cible demandée. */
    public function store(PeriodPackageRequest $request, PeriodPackageService $service): RedirectResponse
    {
        $data = $request->validated();

        $service->importFromZip($request->file('zip'), $request->user(), [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_shared'   => $data['is_shared'] ?? false,
        ]);

        // Cible de retour whitelistée (jamais une URL arbitraire fournie par le
        // client) : permet à l'écran de création de partie de lancer l'import
        // sans en sortir, en plus du point d'entrée classique (bibliothèque).
        $target = match ($data['redirect_to'] ?? null) {
            'create' => route('game-saves.create'),
            default  => route('period-packages.index'),
        };

        return redirect($target)->with('success', 'Période importée.');
    }

    /**
     * Exporte l'effectif standard (Collège) comme modèle éditable — c'est le
     * seul moyen pour un utilisateur de connaître le format de zip attendu.
     */
    public function exportTemplate(PeriodPackageService $service): BinaryFileResponse
    {
        return response()
            ->download($service->exportCanonTemplate(), 'periode-college-modele.zip')
            ->deleteFileAfterSend(true);
    }

    /** Réexporte un package déjà importé (le sien, un partagé, ou tout pour un admin). */
    public function export(PeriodPackage $periodPackage, PeriodPackageService $service): BinaryFileResponse
    {
        $this->authorize('view', $periodPackage);

        return response()
            ->download($service->exportPackageAsZip($periodPackage), Str::slug($periodPackage->name).'.zip')
            ->deleteFileAfterSend(true);
    }

    /** Met à jour les informations d'un package de période (nom, description, partage) et revient à la page précédente. */
    public function update(PeriodPackageRequest $request, PeriodPackage $periodPackage): RedirectResponse
    {
        $periodPackage->update($request->validated());

        return back()->with('success', 'Période mise à jour.');
    }

    /** Supprime un package de période (si la policy l'autorise) ainsi que ses images stockées, puis revient à la page précédente. */
    public function destroy(Request $request, PeriodPackage $periodPackage): RedirectResponse
    {
        $this->authorize('delete', $periodPackage);

        Storage::disk('public')->deleteDirectory("images/period-packages/{$periodPackage->id}");
        $periodPackage->delete();

        return back()->with('success', 'Période supprimée.');
    }
}
