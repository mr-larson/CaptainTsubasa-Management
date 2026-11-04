# Audit du projet — Golden Eleven (main @ 7bdd68a, 2026-11-04)

Audit fait en lecture seule : rien n'a été modifié ni commité.

- **Périmètre** : sécurité et autorisations, backend (correction, base de données, performance, architecture), suivi de `revue-front.md`, outillage et dépendances.
- **Statuts** : ✔ = vérifié dans le code ; « probable » = le mécanisme est confirmé, mais l'effet n'a pas été observé en prod.

## Vue d'ensemble

| Indicateur | Valeur |
|---|---|
| Stack | Laravel 12.71, PHP 8.2, Inertia 1.3, Vue 3.5, Vite 4, Tailwind 3 |
| Taille | 126 fichiers PHP (~16 500 l.), 148 fichiers front (~23 800 l.) |
| Tests PHP (Pest) | **221 réussis** (727 assertions, 12,7 s), aucun échec |
| Tests JS (Vitest) | 9 réussis (2 fichiers : `matchStats`, `shootout`) |
| ESLint | 0 erreur, 63 avertissements (surtout des variables inutilisées) |
| Pint | 182 fichiers non conformes au style |
| `composer audit` | aucune vulnérabilité |
| `npm audit` | 31 vulnérabilités (2 critiques, 10 élevées), toutes dans les dépendances de dev ou de build |
| Revue front du 03/11 | 52 constats : 43 corrigés, 8 partiels, 1 toujours présent |

**En résumé** : le projet est sain, bien testé côté backend, et les autorisations sont solides. Je n'ai trouvé aucun IDOR entre utilisateurs. Deux vrais bugs sont à corriger tout de suite (🔴). Pour le reste, la dette porte surtout sur la taille de certains fichiers, l'état JSON `state` sans schéma et la fin de semaine synchrone.

---

## 🔴 À corriger tout de suite

| # | Où | Problème | Correctif |
|---|---|---|---|
| A1 ✔ (effet en prod probable) | `GameMatchController.php:23,296,438` | Le code importe `App\Services\AILineupService`, alors que la classe et le fichier s'appellent `AiLineUpService`. La classmap Composer est sensible à la casse, et PSR-4 cherche le fichier `AILineupService.php`. Sous macOS (le bind-mount de Sail), le système de fichiers ignore la casse, donc les tests passent. Sur Linux (Laravel Cloud), le fichier est introuvable, ce qui donne une **« Class not found » dans `finishMatch` et `simulateWeek`**, c'est-à-dire à chaque fin de semaine. | Corriger la casse de l'import. Harmoniser ensuite le nommage `Ai*`/`AI*` des services. Vérifier les logs de prod. |
| A2 ✔ | `GameSeasonController.php:24-29` (revue front F16) | La méthode `show()` est typée `: Inertia\Response` mais renvoie `redirect()`. Résultat : **TypeError 500** sur `/season-end` dès que la phase n'est plus `season_end` (par exemple après « Démarrer la saison 2 » puis Retour ou F5). | Typer `Response\|RedirectResponse`. |

## 🟠 Élevé

| # | Où | Problème | Correctif |
|---|---|---|---|
| A3 ✔ | `GameSaveController.php:100,312,624,645` ; `MatchSimulator.php:134,213` | `game_config` est validé uniquement comme `nullable\|array`, puis copié tel quel dans `state.config` à la création (ligue et Coupe du Monde). Par exemple, `match_max_turns=100000000` fait tourner la boucle de simulation IA pendant des heures, sous verrou de save, ce qui **sature le worker (DoS CPU)**. Autre exemple : `training_cost` négatif fait que l'entraînement *rapporte* du budget, IA comprise. | Réutiliser les règles de `updateConfig()` (l.1235-1255) pour `game_config.*` dans `store`, `start` et `startWorldCup`, et ne garder que les clés connues. |
| A4 ✔ | `GameSaveController.php:335` vs `SeasonService.php:23` | `start()` calcule la durée de saison avec `(n-1)*2`. Or avec un nombre impair d'équipes (packages de période), la saison dure `n*2`. Les contrats expirent alors 2 semaines avant la fin et les effectifs se vident. | Appeler partout `SeasonService::getSeasonLength()`. Cette méthode est copiée dans 4 services : il faut la centraliser. |
| A5 | `GameMatchController.php:300-325` vs `440-470` | Le pipeline de fin de semaine est dupliqué, et l'ordre diffère : `finishMatch` incrémente la semaine **avant** Stamina, Moral et Promesses. `StaminaService` (l.82) filtre les contrats sur `week`, donc il traite la semaine N+1. | Créer un `WeekClosingService::close($save, $week)` unique pour les deux chemins. |
| A6 | `GameSaveController.php:976-978` | La prop `matches` envoie à `Play.vue` **tous les `match_stats`** de la saison (événements des 45 tours et snapshot des compositions). Le payload grossit à chaque semaine. | Faire un `select` sans `match_stats`, et charger le détail à la demande (prop lazy ou endpoint). |
| A7 | `GameMatchController.php:392-505` | Toute la semaine est simulée de façon synchrone, dans **une seule transaction `lockForUpdate`** : ~8 matchs, l'IA (composition, entraînement, transferts, cartes), la stamina et le moral de tous les joueurs, avec un `save()` par joueur. Cela risque de dépasser le timeout HTTP. | Court terme : mises à jour en lot (`upsert`). Moyen terme : un job en file d'attente avec un écran d'attente. |

## 🟡 Moyen

**Sécurité / triche**

- **Quota d'entraînement contournable** (`TrainingService.php:44-50`, `StoreTrainingRequest.php`) : `season`/`week` viennent du client et ne sont jamais comparés à la save. En envoyant `week=1000`, puis 1001, etc., le quota hebdomadaire est remis à zéro à chaque fois. → Utiliser `$gameSave->season/week` côté serveur.
- **Aucun rate limiting** hors login : `register`, `forgot-password`, `game-saves/start`, `world-cup/start`, l'upload de packages ZIP de 20 Mo et `simulate-week`. Chaque `start` duplique équipes, joueurs, contrats et calendrier. → Ajouter `throttle:` sur ces routes et un plafond de saves par utilisateur.

**Intégrité des données**

- **Écritures sans verrou** : `LineupController` (l.104-138, 187-216), `GameContractController`, les promesses et déclarations, et `updateNumber` lisent puis réécrivent le blob `state` complet sans `withSaveLock`. Une requête concurrente peut donc écraser une mise à jour. De plus, `release()` ne retire pas le joueur de `state['lineup']`.
- **`start()` sans transaction** (`GameSaveController.php:283-613`) : il fait des centaines d'INSERT, et une erreur au milieu laisse une save à moitié créée.
- **Le GET `play()` écrit en base** (`ensureCalendar`, `ensureSeasonMandate`) sans verrou. Deux onglets ouverts en même temps peuvent créer un calendrier en double (plausible).

**Performance et base de données**

- **Requêtes lourdes** :
  - `PlayerStatsService::aggregateForSave` décode le JSON de tous les matchs à chaque affichage de Play ;
  - `AITransferService.php:84` relance la requête des joueurs libres après chaque équipe IA ;
  - N+1 dans `applyWeeklyIncome` (`GameMatchController.php:530`).
- **Index composites manquants** : `game_matches(game_save_id, week, status)` et `game_contracts(game_team_id, start_week, end_week)` (scope `activeAt`).

**Front (constats restants de la revue)**

- **F10** : `getKeeperId` ne filtre pas les gardiens indisponibles.
- **F11** : un match peut être rejoué, car il n'est jamais marqué `in_progress` côté serveur.
- **F29** : le chevauchement des jetons au coup d'envoi sur mobile n'a pas été vérifié.

**Front (nouveaux constats)**

- **`useOtherTeam.js`** (l.67-260) contient environ 150 lignes de mutations mortes. `updatePlayerNumber` de TabMyTeam vient de ce fichier, et non de `useTeam`.
- **« Terminer mon draft »** (`Draft.vue:333`) : l'erreur est avalée par un `console.error`, sans aucun retour à l'utilisateur.

## ⚪ Faible / info

**Sécurité et configuration**

- La prop Inertia `ziggy` (`HandleInertiaRequests.php:46`) est générée sans groupe : les routes admin sont listées pour tous les utilisateurs. → `new Ziggy($user?->isAdmin() ? null : 'player')`.
- La route `game-saves.show` pointe vers une méthode inexistante, ce qui donne une 500.
- Aucun en-tête de sécurité (CSP, `X-Frame-Options`).
- La vérification e-mail n'est jamais exigée.
- À vérifier en prod : `TrustProxies` et `SESSION_SECURE_COOKIE` derrière le proxy Laravel Cloud.
- Les scores et `match_stats` viennent du client. C'est assumé, sans impact tant qu'il n'y a pas de classement global.

**Logique**

- En hot-seat, les `transfer_requests` ne sont calculées que pour le siège 1 (`SeasonService.php:73`).
- `captainReroll.js:81` : la relance est appliquée côté client avant la réponse du serveur, avec un `.catch(() => {})` silencieux.
- `neutralizeMarkup` est une protection anti-XSS partielle. → Passer à `escapeHTML` ou `textContent`.

**Code mort et dette**

- `app/Helpers/SanctionHelper.php` n'est utilisé nulle part, mais il est chargé à chaque requête via `autoload.files`.
- `TeamSelection.vue:438` contient `isMultiMode = computed(() => true)`.
- `GamePlayers/Edit.vue` est encore routé mais n'est plus lié nulle part.
- La requête « joueurs indisponibles » est copiée **20 fois**.
- `positionGroup` existe en 3 versions.
- La liste des formations est codée en dur dans la validation.
- `startNewSeason` supprime définitivement (hard-delete) matchs et contrats : l'historique détaillé des saisons passées est perdu.

## Architecture et maintenabilité

- **Fichiers géants** :
  - `GameSaveController` (1269 l.) : `start()` fait ~330 l. et duplique la création des équipes ; `play()` fait ~330 l. pour 25 props ;
  - `MatchSimulator` (1297 l.) ;
  - `BonusCardActivationService` (1074 l., avec un switch d'environ 15 effets) ;
  - côté front : `Draft.vue` (944 l.), `engine.js` (864 l.) et `TabCalendar.vue` (843 l.).
- **`state` JSON sans schéma** : une vingtaine de clés (`draft`, `lineup`, `training`, `tournament`, `pending_malus`, `career`…) manipulées comme tableaux bruts dans une dizaine de services. Chaque `save()` réécrit le blob entier, sans version.
- **Double moteur de match** : `MatchSimulator.php` est un miroir PHP du moteur JS, donc chaque règle doit être maintenue deux fois.

## Outillage et dépendances

- **Tests JS** : seulement 2 fichiers pour un moteur de ~5 000 lignes (`engine/`). Les résolveurs et les dés mériteraient des tests unitaires.
- **Pas de CI** visible : rien ne garantit que Pest, ESLint et Pint passent avant un push sur `main`, alors que ce push déploie automatiquement. Une CI Linux aurait détecté A1.
- **Pint** : 182 fichiers à reformater. C'est à faire dans un commit dédié, sans autre changement.
- **npm** : Vite 4 et Vitest 1 sont en fin de vie. Les alertes critiques (`tinypool` via Vitest) et élevées (`vite`, `postcss`, `axios`) sont pour la plupart en dev ou build. `axios` et `vue` sont livrés au navigateur : `npm audit fix` suffit pour eux. Montée de version conseillée : Vite 5+ et Vitest 2+.
- **Composer** : versions majeures disponibles pour inertia-laravel (3.x), ziggy (2.x) et guzzle (8.x). Rien d'urgent.

## Points forts

- **Autorisations** : `authorizeGameSave()` vérifie la policy et rattache chaque modèle enfant à la save. Les éditeurs de la base sont protégés par le middleware `admin`. `is_admin` n'est pas mass-assignable.
- **Entrées** : aucun `$request->all()`, uniquement `validated()`/`safe()`. Aucun `v-html`. Aucune entrée utilisateur dans du SQL brut. Le CSRF est actif.
- **Import ZIP robuste** : protection contre le zip-slip, plafonds d'entrées et de taille, images vérifiées par leur MIME réel.
- **`withSaveLock`** (transaction, `lockForUpdate`, relecture) protège les actions financières, avec des gardes d'idempotence (semaine, match déjà joué, phase).
- **Revue front très bien traitée** : les 4 critiques sont corrigés et 43 constats sur 52 sont réglés en 26 commits.
- **Bonne couverture fonctionnelle** côté backend (221 tests). Les commentaires métier expliquent le « pourquoi ».

## Plan d'action conseillé

1. **Aujourd'hui** : A1 (casse `AiLineUpService`) et A2 (type de retour de `/season-end`). Quelques lignes chacun.
2. **Cette semaine** :
   - A3 (valider `game_config`) ;
   - le quota d'entraînement (`week` côté serveur) ;
   - `throttle` sur les routes sensibles ;
   - A4 (une seule `getSeasonLength`).
3. **Ensuite** :
   - A5 (`WeekClosingService`) ;
   - A6 (retirer `match_stats` des props) ;
   - une CI GitHub Actions (Pest, ESLint, Pint sous Linux) ;
   - `npm audit fix` et la montée de Vite.
4. **Refactoring de fond** : encapsuler `state` (accesseur typé, puis tables pour `lineup` et les effets en attente), centraliser les « indisponibles » et passer la fin de semaine en job.
