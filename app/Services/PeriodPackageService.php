<?php

namespace App\Services;

use App\Enums\Nationality;
use App\Enums\PlayerPosition;
use App\Enums\TeamStyle;
use App\Models\Contract;
use App\Models\PeriodPackage;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * Import d'un package de période (zip : manifest + players/teams/contracts.json
 * + images) déposé par un utilisateur. Toute la validation a lieu AVANT toute
 * écriture en base ou sur le disque — un package rejeté ne laisse aucune trace.
 *
 * Le contenu validé est stocké tel quel en JSON sur `period_packages` : il
 * n'est relu qu'une fois, au moment où GameSaveController::start() hydrate
 * game_teams/game_players/game_contracts pour la save qui a choisi ce package.
 */
class PeriodPackageService
{
    private const MAX_ENTRIES      = 500;
    private const MAX_ENTRY_BYTES  = 8 * 1024 * 1024;  // zip-bomb guard
    private const MAX_IMAGE_BYTES  = 4 * 1024 * 1024;  // cohérent avec GamePlayerRequest

    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param array{name: string, description?: ?string, is_shared?: bool} $meta
     */
    public function importFromZip(UploadedFile $zip, User $user, array $meta): PeriodPackage
    {
        $za = new ZipArchive();
        if ($za->open($zip->getRealPath()) !== true) {
            throw ValidationException::withMessages(['zip' => 'Fichier zip illisible ou corrompu.']);
        }

        try {
            $entries = $this->readEntries($za);

            $manifest = $this->decodeJsonEntry($za, $entries, 'manifest.json');
            if ((int) ($manifest['format_version'] ?? 0) !== 1) {
                throw ValidationException::withMessages(['zip' => "Version de format de package non prise en charge."]);
            }

            $teams     = $this->decodeJsonEntry($za, $entries, 'teams.json');
            $players   = $this->decodeJsonEntry($za, $entries, 'players.json');
            $contracts = $this->decodeJsonEntry($za, $entries, 'contracts.json');

            if (!array_is_list($teams) || !array_is_list($players) || !array_is_list($contracts)) {
                throw ValidationException::withMessages(['zip' => 'players.json, teams.json et contracts.json doivent être des tableaux JSON.']);
            }

            $this->validatePayload($teams, $players, $contracts);
            $this->validateContractReferences($teams, $players, $contracts);

            $teamImages   = $this->extractTeamImages($za, $entries, $teams);
            $playerImages = $this->extractPlayerImages($za, $entries, $players);
        } finally {
            $za->close();
        }

        $package = DB::transaction(fn () => PeriodPackage::create([
            'user_id'        => $user->id,
            'name'           => $meta['name'],
            'description'    => $meta['description'] ?? null,
            'is_shared'      => $meta['is_shared'] ?? false,
            'player_count'   => count($players),
            'team_count'     => count($teams),
            'teams_json'     => $teams,
            'players_json'   => $players,
            'contracts_json' => $contracts,
        ]));

        try {
            $package->update([
                'teams_json'   => $this->storeTeamImages($package, $teams, $teamImages),
                'players_json' => $this->storePlayerImages($package, $players, $playerImages),
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->deleteDirectory("images/period-packages/{$package->id}");
            $package->delete();
            throw $e;
        }

        return $package;
    }

    /**
     * Liste + garde-fous anti-abus : whitelist des noms d'entrée (protection
     * zip-slip), plafond du nombre de fichiers et de la taille décompressée
     * de chacun (protection zip-bomb) — avant toute extraction de contenu.
     *
     * @return array<string, array> entrées autorisées, indexées par nom
     */
    private function readEntries(ZipArchive $zip): array
    {
        $count = $zip->numFiles;
        if ($count > self::MAX_ENTRIES) {
            throw ValidationException::withMessages([
                'zip' => 'Le package contient trop de fichiers ('.$count.', maximum '.self::MAX_ENTRIES.').',
            ]);
        }

        $entries = [];
        for ($i = 0; $i < $count; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];

            if (str_ends_with($name, '/')) {
                continue; // entrée de dossier
            }

            if (!$this->isAllowedEntryName($name)) {
                throw ValidationException::withMessages(['zip' => "Fichier non autorisé dans le package : {$name}"]);
            }

            if ($stat['size'] > self::MAX_ENTRY_BYTES) {
                throw ValidationException::withMessages(['zip' => "Fichier trop volumineux dans le package : {$name}"]);
            }

            $entries[$name] = $stat;
        }

        return $entries;
    }

    private function isAllowedEntryName(string $name): bool
    {
        if (str_contains($name, '..') || str_starts_with($name, '/')) {
            return false;
        }

        if (in_array($name, ['manifest.json', 'players.json', 'teams.json', 'contracts.json'], true)) {
            return true;
        }

        return (bool) preg_match('#^images/(players|teams)/[A-Za-z0-9_.-]+\.(jpe?g|png|webp)$#i', $name);
    }

    private function decodeJsonEntry(ZipArchive $zip, array $entries, string $name): array
    {
        if (!isset($entries[$name])) {
            throw ValidationException::withMessages(['zip' => "Fichier manquant dans le package : {$name}"]);
        }

        $raw = $zip->getFromName($name);
        $decoded = $raw !== false ? json_decode($raw, true) : null;

        if (!is_array($decoded)) {
            throw ValidationException::withMessages(['zip' => "JSON invalide : {$name}"]);
        }

        return $decoded;
    }

    private function validatePayload(array $teams, array $players, array $contracts): void
    {
        $validator = Validator::make(
            ['teams' => $teams, 'players' => $players, 'contracts' => $contracts],
            [
                'teams'                          => ['required', 'array', 'min:1'],
                'teams.*.team_key'               => ['required', 'string', 'max:100', 'distinct'],
                'teams.*.name'                    => ['required', 'string', 'max:255'],
                'teams.*.description'             => ['nullable', 'string', 'max:2000'],
                'teams.*.budget'                   => ['nullable', 'integer', 'min:0'],
                'teams.*.formation'                => ['nullable', 'string', 'max:20'],
                'teams.*.tactical_style'           => ['nullable', Rule::in(TeamStyle::TACTICAL_STYLES)],
                'teams.*.management_philosophy'    => ['nullable', Rule::in(TeamStyle::PHILOSOPHIES)],
                'teams.*.logo_image'               => ['nullable', 'string'],

                'players'                         => ['required', 'array', 'min:1'],
                'players.*.player_key'            => ['required', 'string', 'max:100', 'distinct'],
                // 'nullable' plutôt que 'required' : certains joueurs canon n'ont
                // qu'un mononyme (ex. "Alberto"), firstname vide dans ce cas.
                'players.*.firstname'             => ['nullable', 'string', 'max:255'],
                'players.*.lastname'              => ['required', 'string', 'max:255'],
                'players.*.position'              => ['required', new Enum(PlayerPosition::class)],
                'players.*.secondary_positions'   => ['nullable', 'array'],
                'players.*.secondary_positions.*' => [new Enum(PlayerPosition::class)],
                'players.*.origin'                => ['nullable', 'string', 'max:100'],
                'players.*.nationality'           => ['nullable', Rule::in(Nationality::ALL)],
                'players.*.description'           => ['nullable', 'string', 'max:1000'],
                'players.*.cost'                  => ['nullable', 'integer', 'min:0'],
                'players.*.speed'                 => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.stamina'               => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.attack'                => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.defense'               => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.shot'                  => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.pass'                  => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.dribble'               => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.block'                 => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.intercept'             => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.tackle'                => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.heading'               => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.hand_save'             => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.punch_save'            => ['nullable', 'integer', 'min:0', 'max:100'],
                'players.*.special_moves'                => ['nullable', 'array'],
                'players.*.special_moves.*.key'          => ['required', 'string', 'max:255'],
                'players.*.special_moves.*.label'        => ['required', 'string', 'max:255'],
                'players.*.special_moves.*.mode'         => ['required', 'string', 'in:attack,defense'],
                'players.*.special_moves.*.base_action'  => ['required', 'string', 'max:50'],
                'players.*.special_moves.*.description'  => ['nullable', 'string', 'max:1000'],
                'players.*.photo_image'           => ['nullable', 'string'],

                'contracts'                => ['nullable', 'array'],
                'contracts.*.team_key'     => ['required', 'string'],
                'contracts.*.player_key'   => ['required', 'string'],
                'contracts.*.salary'       => ['nullable', 'integer', 'min:0'],
                'contracts.*.is_captain'   => ['nullable', 'boolean'],
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function validateContractReferences(array $teams, array $players, array $contracts): void
    {
        $teamKeys   = array_column($teams, 'team_key');
        $playerKeys = array_column($players, 'player_key');

        $badTeamKeys   = array_diff(array_column($contracts, 'team_key'), $teamKeys);
        $badPlayerKeys = array_diff(array_column($contracts, 'player_key'), $playerKeys);

        if ($badTeamKeys || $badPlayerKeys) {
            throw ValidationException::withMessages([
                'zip' => 'contracts.json référence des clés inconnues : '
                    .implode(', ', array_unique(array_merge($badTeamKeys, $badPlayerKeys))),
            ]);
        }
    }

    /** @return array<string, array{0: string, 1: string}> bytes+extension par team_key */
    private function extractTeamImages(ZipArchive $zip, array $entries, array $teams): array
    {
        $images = [];
        foreach ($teams as $t) {
            if (!empty($t['logo_image'])) {
                $images[$t['team_key']] = $this->extractAndValidateImage($zip, $entries, $t['logo_image']);
            }
        }
        return $images;
    }

    /** @return array<string, array{0: string, 1: string}> bytes+extension par player_key */
    private function extractPlayerImages(ZipArchive $zip, array $entries, array $players): array
    {
        $images = [];
        foreach ($players as $p) {
            if (!empty($p['photo_image'])) {
                $images[$p['player_key']] = $this->extractAndValidateImage($zip, $entries, $p['photo_image']);
            }
        }
        return $images;
    }

    /**
     * Vérifie que le chemin d'image référencé existe bien dans la whitelist
     * du zip, puis sniffe le contenu réel (pas l'extension du nom de
     * fichier) pour confirmer qu'il s'agit bien d'une image du type attendu.
     *
     * @return array{0: string, 1: string} [octets bruts, extension finale]
     */
    private function extractAndValidateImage(ZipArchive $zip, array $entries, string $path): array
    {
        if (!isset($entries[$path])) {
            throw ValidationException::withMessages(['zip' => "Image référencée absente du package : {$path}"]);
        }

        $bytes = $zip->getFromName($path);
        if ($bytes === false || strlen($bytes) > self::MAX_IMAGE_BYTES) {
            throw ValidationException::withMessages(['zip' => "Image invalide ou trop volumineuse : {$path}"]);
        }

        $info = @getimagesizefromstring($bytes);
        $mime = $info['mime'] ?? null;
        if ($info === false || !isset(self::ALLOWED_IMAGE_MIMES[$mime])) {
            throw ValidationException::withMessages(['zip' => "Fichier image invalide : {$path}"]);
        }

        return [$bytes, self::ALLOWED_IMAGE_MIMES[$mime]];
    }

    private function storeTeamImages(PeriodPackage $package, array $teams, array $images): array
    {
        $dir = "images/period-packages/{$package->id}/teams";

        foreach ($teams as &$t) {
            unset($t['logo_image']);
            $t['logo_path'] = null;

            if (isset($images[$t['team_key']])) {
                [$bytes, $ext] = $images[$t['team_key']];
                $filename = Str::slug($t['name']).'-'.Str::random(6).'.'.$ext;
                Storage::disk('public')->put("{$dir}/{$filename}", $bytes);
                $t['logo_path'] = "{$dir}/{$filename}";
            }
        }

        return $teams;
    }

    private function storePlayerImages(PeriodPackage $package, array $players, array $images): array
    {
        $dir = "images/period-packages/{$package->id}/players";

        foreach ($players as &$p) {
            unset($p['photo_image']);
            $p['photo_path'] = null;

            if (isset($images[$p['player_key']])) {
                [$bytes, $ext] = $images[$p['player_key']];
                $filename = Str::slug($p['firstname'].' '.$p['lastname']).'-'.Str::random(6).'.'.$ext;
                Storage::disk('public')->put("{$dir}/{$filename}", $bytes);
                $p['photo_path'] = "{$dir}/{$filename}";
            }
        }

        return $players;
    }

    /**
     * Exporte l'effectif standard (Collège) au format zip attendu par
     * importFromZip() — sert de modèle de référence à éditer puis réimporter,
     * puisqu'un utilisateur n'a sinon aucun moyen de connaître ce format.
     *
     * Les images ne sont PAS embarquées : les portraits/logos réels pèsent à
     * eux seuls ~200 Mo (certains fichiers sources dépassent 2 Mo), largement
     * au-delà de la limite d'upload d'un package (20 Mo) — un zip qui les
     * embarquerait serait donc impossible à réimporter tel quel. Le JSON
     * reste complet (toutes les équipes/joueurs/contrats réels) ; seuls les
     * champs `logo_image`/`photo_image` sont laissés à null, à renseigner par
     * l'utilisateur s'il ajoute ses propres images.
     *
     * @return string chemin d'un fichier zip temporaire (à supprimer après envoi)
     */
    public function exportCanonTemplate(): string
    {
        $usedTeamKeys = [];
        $teamKeyById  = [];
        $teamsJson    = [];

        foreach (Team::orderBy('name')->get() as $team) {
            $key = $this->uniqueKey($usedTeamKeys, Str::slug($team->name));
            $teamKeyById[$team->id] = $key;

            $teamsJson[] = [
                'team_key'               => $key,
                'name'                   => $team->name,
                'description'            => $team->description,
                'budget'                 => $team->budget,
                'formation'              => $team->default_formation,
                'tactical_style'         => $team->tactical_style,
                'management_philosophy'  => $team->management_philosophy,
                'logo_image'             => null,
            ];
        }

        $usedPlayerKeys = [];
        $playerKeyById  = [];
        $playersJson    = [];

        foreach (Player::orderBy('lastname')->get() as $player) {
            $key = $this->uniqueKey($usedPlayerKeys, Str::slug($player->firstname.' '.$player->lastname));
            $playerKeyById[$player->id] = $key;

            $s = $player->stats;

            $playersJson[] = [
                'player_key'           => $key,
                'firstname'            => $player->firstname,
                'lastname'             => $player->lastname,
                'position'             => $player->position?->value,
                'secondary_positions'  => $player->secondary_positions ?? [],
                'origin'               => $player->origin,
                'nationality'          => $player->nationality,
                'description'          => $player->description,
                'cost'                 => $player->cost ?? 0,
                'speed'                => $s['speed'],
                'stamina'              => $s['stamina'],
                'attack'               => $s['attack'],
                'defense'              => $s['defense'],
                'shot'                 => $s['shot'],
                'pass'                 => $s['pass'],
                'dribble'              => $s['dribble'],
                'block'                => $s['block'],
                'intercept'            => $s['intercept'],
                'tackle'               => $s['tackle'],
                'heading'              => $s['heading'],
                'hand_save'            => $s['hand_save'],
                'punch_save'           => $s['punch_save'],
                'special_moves'        => $player->special_moves ?? [],
                'photo_image'          => null,
            ];
        }

        $contractsJson = [];
        $contractsByTeam = Contract::orderBy('id')->get()->groupBy('team_id');
        foreach ($contractsByTeam as $teamId => $contracts) {
            if (!isset($teamKeyById[$teamId])) continue;
            foreach ($contracts as $contract) {
                if (!isset($playerKeyById[$contract->player_id])) continue;
                $contractsJson[] = [
                    'team_key'   => $teamKeyById[$teamId],
                    'player_key' => $playerKeyById[$contract->player_id],
                    'salary'     => $contract->salary ?? 0,
                    'is_captain' => (bool) ($contract->is_captain ?? false),
                ];
            }
        }

        return $this->buildZip(
            [
                'format_version' => 1,
                'name'           => 'Collège (modèle)',
                'description'    => "Export de l'effectif standard (sans les images, trop volumineuses) — "
                    ."modifie ce fichier puis réimporte-le comme période personnalisée. Pour ajouter des photos, "
                    ."dépose-les dans images/players/ ou images/teams/ et renseigne photo_image/logo_image en conséquence.",
            ],
            $teamsJson,
            $playersJson,
            $contractsJson,
            [],
            [],
        );
    }

    /**
     * Réexporte un package déjà importé dans le même format, pour permettre
     * de l'ajuster puis de le réimporter comme nouvelle période.
     */
    public function exportPackageAsZip(PeriodPackage $package): string
    {
        $teamsJson  = [];
        $teamImages = [];
        foreach ($package->teams_json as $t) {
            if (!empty($t['logo_path'])) {
                $teamImages[$t['team_key']] = $t['logo_path'];
            }
            $teamsJson[] = [
                'team_key'               => $t['team_key'],
                'name'                   => $t['name'],
                'description'            => $t['description'] ?? null,
                'budget'                 => $t['budget'] ?? null,
                'formation'              => $t['formation'] ?? null,
                'tactical_style'         => $t['tactical_style'] ?? null,
                'management_philosophy'  => $t['management_philosophy'] ?? null,
                'logo_image'             => !empty($t['logo_path']) ? "images/teams/{$t['team_key']}.".$this->extensionOf($t['logo_path']) : null,
            ];
        }

        $playersJson  = [];
        $playerImages = [];
        foreach ($package->players_json as $p) {
            if (!empty($p['photo_path'])) {
                $playerImages[$p['player_key']] = $p['photo_path'];
            }
            $playersJson[] = [
                'player_key'          => $p['player_key'],
                'firstname'           => $p['firstname'],
                'lastname'            => $p['lastname'],
                'position'            => $p['position'],
                'secondary_positions' => $p['secondary_positions'] ?? [],
                'origin'              => $p['origin'] ?? null,
                'nationality'         => $p['nationality'] ?? null,
                'description'         => $p['description'] ?? null,
                'cost'                => $p['cost'] ?? 0,
                'speed'               => $p['speed'] ?? 50,
                'stamina'             => $p['stamina'] ?? 50,
                'attack'              => $p['attack'] ?? 50,
                'defense'             => $p['defense'] ?? 50,
                'shot'                => $p['shot'] ?? 50,
                'pass'                => $p['pass'] ?? 50,
                'dribble'             => $p['dribble'] ?? 50,
                'block'               => $p['block'] ?? 50,
                'intercept'           => $p['intercept'] ?? 50,
                'tackle'              => $p['tackle'] ?? 50,
                'heading'             => $p['heading'] ?? 15,
                'hand_save'           => $p['hand_save'] ?? 0,
                'punch_save'          => $p['punch_save'] ?? 0,
                'special_moves'       => $p['special_moves'] ?? [],
                'photo_image'         => !empty($p['photo_path']) ? "images/players/{$p['player_key']}.".$this->extensionOf($p['photo_path']) : null,
            ];
        }

        return $this->buildZip(
            [
                'format_version' => 1,
                'name'           => $package->name,
                'description'    => $package->description,
            ],
            $teamsJson,
            $playersJson,
            $package->contracts_json,
            $teamImages,
            $playerImages,
        );
    }

    private function uniqueKey(array &$seen, string $base): string
    {
        $slug = $base !== '' ? $base : 'x';
        $key  = $slug;
        $i    = 2;
        while (isset($seen[$key])) {
            $key = $slug.'-'.$i++;
        }
        $seen[$key] = true;

        return $key;
    }

    private function extensionOf(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION) ?: 'png';
    }

    /**
     * @param array<string, string> $teamImages   team_key => chemin sur le disque public
     * @param array<string, string> $playerImages player_key => chemin sur le disque public
     * @return string chemin du zip temporaire créé
     */
    private function buildZip(array $manifest, array $teams, array $players, array $contracts, array $teamImages, array $playerImages): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ppkg-export').'.zip';
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $json = fn ($data) => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $zip->addFromString('manifest.json', $json($manifest));
        $zip->addFromString('teams.json', $json($teams));
        $zip->addFromString('players.json', $json($players));
        $zip->addFromString('contracts.json', $json($contracts));

        foreach ($teamImages as $key => $sourcePath) {
            if (Storage::disk('public')->exists($sourcePath)) {
                $zip->addFromString('images/teams/'.$key.'.'.$this->extensionOf($sourcePath), Storage::disk('public')->get($sourcePath));
            }
        }
        foreach ($playerImages as $key => $sourcePath) {
            if (Storage::disk('public')->exists($sourcePath)) {
                $zip->addFromString('images/players/'.$key.'.'.$this->extensionOf($sourcePath), Storage::disk('public')->get($sourcePath));
            }
        }

        $zip->close();

        return $path;
    }
}
