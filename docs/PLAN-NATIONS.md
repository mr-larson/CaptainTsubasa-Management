# Plan de migration : table `nations` (mode Coupe du Monde)

Date : 2026-11-18. Statut : **étapes 1 à 3 faites** (table `nations` remplie depuis l'enum, `game_teams.base_nation_id`, assembleur relié au catalogue). Restent : drapeaux en images (5), validation via la table et retrait de l'enum (4 partiel, 6).

## Objectif
Passer les nations de « constante d'enum + emoji » à un catalogue en base, à l'image de `teams`
(catalogue) → `game_teams` (copie par partie), **sans** toucher à `players.nationality`.

## Étapes (chacune livrable et testable seule)

### 1. Table `nations` (S)
Migration `create_nations_table` :
`id`, `name` (unique, = valeur actuelle de `Nationality`, ex. « Pays-Bas »), `code` (ISO 3166 alpha-2),
`flag_path` (nullable, `images/nations/xx.webp`), `tactical_style`, `management_philosophy`,
`default_formation` (nullable), `is_active` (bool, défaut true), timestamps.
Modèle `App\Models\Nation` (docblock FR, `getFlagUrlAttribute`, scope `active`).

### 2. Seeding depuis l'enum (S)
`NationSeeder` idempotent (`updateOrCreate` sur `name`) : une ligne par `Nationality::ALL`, style/philosophie
par défaut `balanced` / `collective`, `code` depuis une table dans le seeder. Ajouter l'appel dans `DatabaseSeeder`
et dans une migration de données unique pour la prod (Laravel Cloud n'exécute pas les seeders au deploy).

### 3. Lien partie ↔ catalogue (S)
Migration `add_base_nation_id_to_game_teams` : `base_nation_id` nullable, FK `nations` `nullOnDelete`,
à côté de `base_team_id`. Une `GameTeam` a soit un club, soit une nation, jamais les deux (garde-fou dans le modèle).

### 4. Brancher les services (M)
- `NationalTeamAssembler::assemble` : lit la `Nation`, remplit `base_nation_id`, `tactical_style`,
  `management_philosophy`, `formation` (si `default_formation`), `logo_path` = `flag_path`.
- `NewWorldCupBuilder::playableNations` : jointure avec `nations.is_active` ; garde la règle 11 joueurs + 1 gardien.
- `TournamentService::presentation` : le drapeau vient de `GameTeam->baseNation` (plus de `Nationality::flag($name)`).
  Charger `with('baseNation')` une fois (pas de N+1).
- `StartWorldCupRequest` : `Rule::exists('nations', 'name')` à la place de `Rule::in(Nationality::ALL)`.

### 5. Front (S)
`NationSelection.vue` et `TabTournament.vue` reçoivent `flag_url` (image) avec repli sur l'emoji.
Images versionnées dans `public/storage/images/nations/` (FS éphémère en prod, cf. mémoire déploiement).

### 6. Retrait progressif de l'enum (M, optionnel)
`Nationality::ALL` reste la validation des **joueurs** (`PlayerRequest`, `GamePlayerRequest`, `GameConfig`).
Ne le supprimer qu'une fois ces validations basculées sur `Nation::pluck('name')`. Pas urgent.

### Hors périmètre (volontairement)
`players.nationality` / `game_players.nationality` en clé étrangère : touche ~8 fichiers + `legends_countries`
de `GameConfig` + migrations de portraits, gain faible.

## Tests à écrire / lancer
- `NationSeederTest` : une ligne par nation de l'enum, idempotent.
- Adapter `NewWorldCupBuilderTest`, `TournamentServiceTest`, `WorldCupLegendsExpansionTest`.
- Lancer : `docker compose exec -T laravel.test php artisan test --filter="WorldCup|Tournament|Nation"`.

## Risques
- Rollback : `base_nation_id` nullable, donc l'ancien code continue de fonctionner (champ ignoré).
- Piège connu : les migrations de légendes insèrent des joueurs dans la base de test, ce qui fausse les tests qui
  comptent les nations jouables (cf. `NewWorldCupBuilderTest::test_playable_nations_are_listed_first`, déjà rouge).
