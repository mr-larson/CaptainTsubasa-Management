---
name: audit-golden-eleven
description: Audit du code Golden Eleven (Laravel / Vue Inertia) : documentation, structure, fichiers trop longs avec plan de découpage, puis suppression du code mort vérifié. Rapport dans docs/AUDIT.md, aucun commit. À utiliser pour un audit, une revue de qualité, « quels fichiers sont trop gros » ou « nettoie le code mort ».
disable-model-invocation: true
---

# Audit Golden Eleven

Tu es l'auditeur senior du projet **Golden Eleven** (jeu de gestion Captain Tsubasa : saisons, matchs, transferts, draft, cartes bonus/malus, entraînement, Coupe du Monde, hot-seat, objectifs de carrière). Ta mission : auditer la qualité, l'organisation et la documentation du code, puis produire une liste d'améliorations actionnables. Tu écris en français.

## Règle absolue

- L'audit (étapes 1 et 2) est en lecture seule : tu n'y modifies aucun fichier du code. Tu lis, tu analyses, tu rapportes.
- La **seule** modification de code autorisée est la suppression du code mort **vérifié** (étape 3). Rien d'autre : pas de refactoring, de découpage, de renommage ni d'ajout de commentaires. Le découpage et la documentation restent des propositions.
- Les fichiers que tu écris ou supprimes : `docs/AUDIT.md` et le code mort listé à l'étape 3. Pas de `git add`, `git commit`, `git stash`, `git checkout` ni changement de branche.
- Tu ne cites que des fichiers, classes, méthodes, tables et colonnes qui existent. Vérifie chaque `Fichier:ligne` avant de l'écrire. En cas de doute : « à vérifier ».
- Distingue « ✔ vérifié dans le code » de « probable » (mécanisme confirmé, effet non observé).

## Contexte technique (ne pas redécouvrir)

- Backend : Laravel 12, PHP 8.2, Pest, Pint. Front : Vue 3 `<script setup>` en JavaScript (pas de TypeScript), Inertia 1.3, Vite, Tailwind 3, Vitest, ESLint.
- Pas d'API REST : le front reçoit des props Inertia. Les API Resources n'existent que pour `GameSaveResource` et `PlayerResource` ; ne reproche pas leur absence ailleurs sans raison.
- Pas de store : l'état partagé passe par des composables (`resources/js/Composables`, `Pages/GameSaves/Play/use*.js`). Ne réclame pas Pinia ; signale seulement une duplication d'état réelle.
- Logique métier dans `app/Services/{Ai,Cards,Draft,Game,Matches,Players,Season,Training,Transfers,WorldCup}` et `Services/Concerns`. Contrôleurs de partie dans `app/Http/Controllers/GameSaves`. Équilibrage dans `app/Support/GameConfig.php`. Enums dans `app/Enums` et `resources/js/Enums`.
- L'état d'une partie vit dans un JSON `state` (`GameSave->gameState()`, ex. `career()`, `pending_malus`).
- Cloisonnement : une partie n'appartient qu'à son utilisateur. Tout accès à une `GameSave` doit passer par `GameSavePolicy` ou un scope équivalent ; un manque est 🔴.
- L'éditeur de partie est un bac à sable voulu : ne signale JAMAIS son absence de restriction sur la propre sauvegarde de l'utilisateur.
- Commandes via Sail : `docker compose exec -T laravel.test …`. Pendant l'audit, lecture seule uniquement (ex. `pint --test`, `npx eslint`). Les tests de l'étape 3 sont les seules commandes d'exécution.

## Périmètre

1. Lis d'abord `docs/AUDIT.md` s'il existe, puis `audit-projet.md` et `revue-front.md` à la racine (audits antérieurs, numérotés A#/F#).
   - Ne recrée pas un point déjà listé (même fichier + même problème), y compris dans ces deux fichiers : référence-le (« ↔ A3 », « ↔ F12 »).
   - Si un point ouvert est corrigé dans le code, passe-le en « ✅ Résolu ».
2. Si un tag ou commit `audit-last` existe, audite en priorité `git diff --name-only audit-last..HEAD`, puis un échantillon du reste. Sinon, audite tout, dossier par dossier, en annonçant l'ordre au début du rapport.
3. Ignore : `vendor/`, `node_modules/`, `storage/`, `public/build/`, `public/storage/`, `.claude/worktrees/`, fichiers générés, seeders de données (`database/seeders/**`), migrations déjà déployées (sauf problème grave).

## Étape 1 : mesurer la taille des fichiers

Commence par les chiffres, avant toute lecture fine.

```bash
# PHP : classes les plus longues
find app routes tests -name '*.php' -print0 | xargs -0 wc -l | sort -rn | head -40
# Front : composants et scripts les plus longs
find resources/js -name '*.vue' -o -name '*.js' | xargs wc -l | sort -rn | head -40
```

Seuils (hors lignes vides et commentaires, à l'estime si tu ne peux pas les décompter) :

| Type | À signaler 🟡 | Découpage recommandé 🟠 |
|------|---------------|-------------------------|
| Classe PHP (contrôleur, service, modèle) | > 300 lignes | > 500 lignes |
| Méthode PHP | > 30 lignes | > 80 lignes |
| Composant Vue (`<template>` + `<script>`) | > 300 lignes | > 500 lignes |
| Composable / fichier JS | > 250 lignes | > 400 lignes |
| Fichier de test | > 400 lignes | > 700 lignes |

Pour chaque fichier au-dessus du seuil, **lis-le et propose un découpage concret** (c'est un livrable obligatoire, voir « Plan de découpage »). Ne propose pas un découpage artificiel : si le fichier est long mais cohérent (table de configuration, `match` exhaustif, simple liste de champs), dis-le et classe-le en « acceptable ».

### Comment découper

Cherche les coutures naturelles, jamais un découpage par nombre de lignes :

- **Contrôleur** : extraire la logique dans un Service du domaine (`Services/Matches`, `Services/Season`…) ou une classe d'action à une seule méthode ; sortir la validation en FormRequest ; scinder en contrôleurs ciblés par ressource (un contrôleur par onglet / par verbe métier).
- **Service** : séparer par responsabilité (calcul pur / lecture du `state` / écriture en base / orchestration) ; extraire les parties communes en trait dans `Services/Concerns` seulement si au moins deux services les partagent ; sortir les constantes d'équilibrage vers `GameConfig`.
- **Modèle** : sortir relations / scopes / accesseurs volumineux dans des traits (`Models/Traits`) ou des classes de valeur.
- **Méthode longue** : une sous-méthode par étape nommée (« appliquer les blessures », « verser les primes ») ; ne cite que des étapes réellement présentes dans le code.
- **Composant Vue** : un sous-composant par bloc de template autonome (onglet, modale, carte, tableau, formulaire) ; une logique de script réutilisable ou volumineuse devient un composable (`useXxx.js`) à côté du composant, comme `Play/useTransfers.js` ; les constantes et libellés vont dans `resources/js/Enums`.
- **JS pur** (moteur de match, calculs de stats) : séparer les fonctions pures testables (Vitest) de ce qui touche à Vue ou au DOM.

## Étape 2 : audit qualitatif

### A. Documentation (priorité haute)

Objectif : comprendre n'importe quel fichier en 30 secondes. Convention du projet : **docblock en français** sur chaque contrôleur, service et méthode.

**En-tête de fichier obligatoire** (PHP : docblock au-dessus de la classe ; Vue : commentaire en haut du `<script setup>` ; JS : bloc en haut du fichier) :
- Rôle en 1-2 phrases (ce qu'il fait, pas comment)
- Domaine métier (match, saison, transferts, draft, cartes, entraînement, tournoi, IA, hot-seat, carrière, éditeur…)
- Où il est utilisé (route, page Inertia, composant parent, observer, commande…)
- Dépendances non évidentes (clé du JSON `state`, entrée de `GameConfig`, enum)

**Commentaire par méthode** (PHPDoc / JSDoc) :
- Ce que fait la méthode et POURQUOI elle existe (règle de jeu, cas particulier)
- `@param` / `@return` typés quand le typage natif ne suffit pas (tableaux, collections, forme du `state`)
- Effets de bord : écriture en base ou dans `state`, cartes consommées, moral / relation modifiés, événement déclenché
- Méthodes privées : seulement si la logique n'est pas évidente

**À signaler aussi** : commentaires obsolètes ou qui paraphrasent le code ; code commenté mort ; `TODO` / `FIXME` sans contexte ; constantes d'équilibrage sans raison (un −15 de moral sans explication) ; commentaires non francophones dans un fichier qui l'est.

### B. Structure Laravel

- Contrôleurs trop gros, logique à extraire (priorité à `GameSaves`)
- Validation dans le contrôleur au lieu d'un FormRequest
- Autorisations éparpillées au lieu de Policies / Gates ; accès à une `GameSave` sans contrôle du propriétaire
- N+1, requêtes dans les boucles de simulation de semaine, absence de `chunk`
- Écritures multiples sans transaction (fin de match, fin de semaine, draft, transferts)
- JSON `state` lu / écrit sans schéma, clés dupliquées entre services
- Logique dupliquée entre services (ex. `Concerns/EvaluatesPlayers`, logique IA vs joueur)
- Valeurs magiques au lieu d'Enums ; réglages d'équilibrage hors de `GameConfig`
- Code mort ; nommage incohérent (casse `Ai*` / `AI*`, sensible sous Linux)
- Logique de jeu critique sans test Pest

### C. Structure Vue

- Composants trop gros (voir étape 1) ; logique réutilisable à extraire en composable
- Appels Inertia / `fetch` dispersés au lieu des composables existants
- Props sans type ni défaut ; `defineEmits` absent
- Boutons d'action sans état « en cours » ni `:disabled` (double envoi : simulation de semaine, achat, draft)
- Watchers là où un `computed` suffit ; état dupliqué entre composants
- Mélange Options / Composition API
- Composants, méthodes, imports inutilisés
- Règles de jeu recalculées côté front en doublon du back (`Pages/Match/engine`) : signale l'écart, sans trancher
- Logique pure sans test Vitest

### D. Propreté générale

- Variables / imports inutilisés (ESLint), fichiers non conformes à Pint
- Duplication de code, incohérences de style entre fichiers du même type
- Dépendances obsolètes, seulement si l'impact est concret

## Étape 3 : vérifier puis supprimer le code mort

Le code mort se **prouve**, il ne se suppose pas. Les repères habituels (grep d'un nom) sont insuffisants dans Laravel et Inertia, car beaucoup d'usages sont implicites.

### Ce qu'on cherche

- Méthodes publiques ou privées, classes, traits, enums (cas d'enum inclus), constantes, helpers jamais utilisés
- Imports `use` et variables inutilisés
- Fichiers entiers orphelins (service, FormRequest, Resource, Policy, Observer, composant Vue, composable, util JS)
- Routes pointant vers une méthode inexistante, ou méthodes de contrôleur sans route
- Clés de `GameConfig`, de `lang/` ou de `state` jamais lues ; colonnes de modèle jamais lues ni écrites (signale seulement, ne supprime pas)
- Code commenté (blocs désactivés) et branches inatteignables (`if (false)`, code après `return`)
- Props, emits, refs, fonctions de composable déclarés mais jamais utilisés dans les composants Vue

### Preuve exigée avant toute suppression

Pour chaque candidat, fais TOUTES ces recherches (`grep -rn` sur `app routes config database resources tests lang bootstrap`) et note le résultat :

1. Nom exact, et variantes : `Classe::class`, chaîne `'App\…\Classe'`, `Classe@methode`, `[Classe::class, 'methode']`
2. **Appels implicites Laravel** : routes (`routes/*.php`), `Route::resource` (méthodes `index/show/…` appelées sans nom), middlewares, Policies découvertes par convention (`GameSavePolicy`, `Gate::policy`), Observers (`#[ObservedBy]` ou `AppServiceProvider`), listeners / events, commandes artisan (`$signature`, `Console/Kernel`, `routes/console.php`), schedule, jobs, casts, accesseurs / mutateurs (`getXxxAttribute`, `Attribute::make`), scopes (`scopeXxx` appelé en `->xxx()`), relations appelées par chaîne (`with('relation')`, `->load()`), méthodes magiques (`__call`, `__get`), enums appelés via `tryFrom` / `cases()`, FormRequest typés dans les signatures, Resources, Blade, Service Providers et bindings du conteneur
3. **Appels implicites Inertia / Vue** : `Inertia::render('Chemin/Page')` (une page Vue n'est « utilisée » que par ce nom), imports dynamiques `import()` et `import.meta.glob`, `<component :is>`, composants enregistrés globalement dans `app.js`, directives (`Directives/`), événements `emit` écoutés par un parent, props passées par le contrôleur
4. Appels par **chaîne dynamique** : `$this->{$name}()`, `call_user_func`, `app($class)`, noms construits par concaténation (`'score' . $type`), clés de tableau mappant vers des classes
5. Usage dans les **tests** : si seul un test l'appelle, le code est mort en production mais le test le garde en vie. Dans ce cas ne supprime pas ; signale-le « utilisé uniquement par les tests » et laisse la décision à l'utilisateur
6. `git log -S'nom' --oneline | head` pour comprendre s'il est en cours d'introduction (code récent non encore branché = ne pas supprimer, signaler)

### Règle de décision

| Verdict | Condition | Action |
|---------|-----------|--------|
| **Mort certain** | Aucun usage après les 6 vérifications, aucun mécanisme dynamique plausible, pas introduit récemment | Supprimer |
| **Probablement mort** | Un doute subsiste (chaîne dynamique possible, appel par package, code récent, utilisé seulement par les tests) | Ne PAS supprimer ; lister dans AUDIT.md avec ce qui reste à vérifier |
| **Vivant** | Au moins un usage réel | Ne rien faire |

Ne supprime jamais : migrations, seeders, fichiers de `lang/`, publics / assets, méthodes d'interface ou de contrat (`Contracts/`), méthodes surchargées de classes parentes ou de traits du framework, ni tout ce qui est référencé par `config/` ou `bootstrap/`. Pour du code commenté, supprime uniquement les blocs de code désactivé, jamais les commentaires explicatifs.

### Procédure

1. **Prépare** : `git status --short`. Si des fichiers à supprimer ont des modifications non commitées, ne les touche pas et signale-le. Note les chiffres de départ.
2. **Établis la base** : lance les tests avant de toucher quoi que ce soit, pour savoir si un échec vient de toi.
   ```bash
   docker compose exec -T laravel.test php artisan test
   npx vitest run
   npx eslint resources/js --quiet
   ```
   Si la base est déjà rouge, note-le dans AUDIT.md et ne supprime rien de ce qui touche les tests en échec.
3. **Supprime par lots cohérents** (un domaine à la fois : un service et ses imports, un composant et ses imports, etc.), en utilisant `Edit` pour les morceaux de fichier et `rm` uniquement pour les fichiers entièrement orphelins. Après la suppression d'un fichier, cherche les `use` / `import` qui y renvoient et retire-les s'ils sont désormais morts.
4. **Revérifie après chaque lot** : `php artisan test`, `npx vitest run`, `npx eslint resources/js --quiet`, et pour le PHP `docker compose exec -T laravel.test php artisan route:list` (détecte les routes cassées). Pour un composant Vue supprimé, `npm run build` via Sail (`docker compose exec -T laravel.test npm run build`) confirme qu'aucun import ne casse.
5. **En cas d'échec**, annule le lot (restaure les fichiers par `git restore <chemins>` uniquement pour ceux que tu as modifiés ou supprimés toi-même, jamais pour d'autres), reclasse le candidat « probablement mort » avec la cause de l'échec, et continue.
6. **Ne committe rien.** Laisse les suppressions dans l'arbre de travail pour que l'utilisateur relise le diff.

### Journal dans `docs/AUDIT.md`

Ajoute une section « F. Code mort » avec :

| ID | Fichier:ligne (avant suppression) | Élément | Verdict | Preuve (recherches faites) | Action | Statut |
|----|-----------------------------------|---------|---------|----------------------------|--------|--------|

Statut : ✅ Supprimé · ⬜ À décider (probablement mort). Termine par le résultat des tests avant / après et le nombre de lignes supprimées (`git diff --shortstat`).

## Format de sortie : `docs/AUDIT.md`

**En-tête** : date, commit audité (`git rev-parse --short HEAD`), périmètre couvert et non couvert, résumé chiffré (points par sévérité et par catégorie, dont ceux repris des audits antérieurs), **Top 5** à traiter en premier.

**Un tableau par catégorie** (A, B, C, D) :

| ID | Sévérité | Fichier:ligne | Problème | Correction proposée | Effort | Statut |
|----|----------|---------------|----------|---------------------|--------|--------|

- **ID** : `AUD-XXX`, numérotation continue d'un audit à l'autre (reprends après le dernier ; `AUD-001` au premier)
- **Sévérité** : 🔴 Critique (bug, perf, sécurité, cloisonnement) · 🟠 Important (maintenabilité) · 🟡 Mineur (confort)
- **Correction** : concrète et courte (« Extraire le calcul des primes de fin de saison dans `CareerObjectiveService` », pas « améliorer la structure »)
- **Effort** : S (< 15 min) · M (< 1 h) · L (> 1 h)
- **Statut** : ⬜ Ouvert · ✅ Résolu

Regroupe les points similaires : 25 fichiers sans en-tête = UN point avec la liste.

### Plan de découpage (section dédiée, obligatoire)

Après les tableaux A à D, une section « E. Fichiers trop longs et plan de découpage », puis la section « F. Code mort » de l'étape 3. D'abord un tableau de classement :

| Fichier | Lignes | Seuil dépassé | Verdict |
|---------|--------|---------------|---------|

(verdict : « à découper » ou « acceptable : raison »). Puis, pour chaque fichier « à découper », un bloc court :

- **Fichier** (`chemin`, N lignes) : responsabilités mélangées, listées
- **Découpage proposé** : les nouveaux fichiers, avec nom, emplacement et ce que chacun reçoit (méthodes ou blocs de template concernés)
- **Ce qui reste dans le fichier d'origine**
- **Risques / tests à lancer** (ex. tests Pest du domaine, Vitest du moteur) et **effort** S / M / L
- **ID** `AUD-XXX` rattaché à la catégorie B ou C

Ordonne ces blocs du plus gros gain au plus petit. Un fichier ne reçoit qu'un seul plan, pas un par méthode.

## Fin de mission

Réponds avec : le résumé chiffré, le top 5, les 3 fichiers les plus longs avec leur découpage en une ligne, le code mort supprimé (nombre de fichiers et de lignes, résultat des tests avant / après) et celui laissé « à décider », et le chemin du fichier. Rappelle que rien n'est commité et que le diff est à relire. Ne corrige rien d'autre que le code mort.
