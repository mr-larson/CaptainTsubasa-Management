# Base de données des univers

Golden Eleven combine des personnages de mangas et d'animés, des joueurs réels
et des créations personnelles. Le catalogue de référence rassemble ces profils ;
chaque partie dispose ensuite de ses propres copies.

Cette page décrit l'état du projet au **17 novembre 2026**. Les effectifs indiqués
peuvent évoluer avec les ajouts et les modifications du catalogue.

## Univers, nationalité et équipe

Ces trois informations répondent à des questions différentes :

| Information | Signification | Exemple |
| --- | --- | --- |
| Univers | L'origine du personnage ou du joueur | Blue Lock, Captain Tsubasa — Internationaux, Légendes internationales |
| Nationalité | Le pays associé au profil | Japon, France, Belgique |
| Équipe | Le club ou la sélection auquel le joueur appartient dans le contexte consulté | Un club sous contrat ou une sélection nationale |

Un joueur français de Captain Tsubasa reste un personnage de cet univers. Un
joueur français réel appartient aux légendes internationales. La nationalité
commune ne les fusionne pas. Un joueur actif possède une seule nationalité dans
le modèle actuel ; les sélections historiques, comme la Yougoslavie, peuvent
être conservées explicitement.

## Les familles disponibles

| Famille | Univers proposés | Contenu |
| --- | --- | --- |
| Captain Tsubasa | Clubs et joueurs japonais | Équipes du collège et joueurs japonais libres |
| Captain Tsubasa | Internationaux Captain Tsubasa | Rivaux étrangers de la série, sélectionnables séparément |
| Autres mangas et animés | École des Champions, Hungry Heart, Blue Lock, Ao Ashi | Profils et spécialités propres à chaque œuvre |
| Football réel | Légendes internationales | Joueurs réels de différentes époques, regroupés par pays |
| Créations personnelles | Créations et clins d'œil | Personnages originaux illustrés, proches et références personnelles |

L'éditeur peut également afficher des catégories techniques comme « Amateurs »
et « Fictifs ». Elles ne correspondent pas à de nouveaux univers de mangas.
Les joueurs fictifs servant à compléter des sélections sont distincts des
créations personnelles illustrées.

## Choisir les joueurs d'une partie

À la création d'une partie, puis dans **Gestion → Configuration**, le bloc
« Univers de joueurs » permet d'activer ou de désactiver les univers.

Pour le football réel, les pays se choisissent sous « Légendes internationales ».
« Tous les pays » autorise l'ensemble des pays ; « Aucun » retire les légendes
du marché et du draft ; les cases individuelles permettent une sélection précise.
Ce filtre de pays concerne uniquement les joueurs réels, pas les internationaux
de Captain Tsubasa.

Les nombres affichés sont ceux du catalogue actif lors de la création, ou ceux
des joueurs déjà copiés dans la sauvegarde en gestion. Ils ne garantissent pas
que tous ces joueurs soient libres : les contrats et les autres règles
continuent de s'appliquer. Les équipes déjà constituées ne sont pas démantelées
par un changement d'option.

Activer un univers dans une ancienne partie ne lui importe pas les profils
ajoutés ultérieurement au catalogue. La compatibilité avec les anciens réglages
de visibilité internationale est conservée.

## Consulter, créer et modifier un joueur

1. Ouvrir **Base de données → Joueurs**.
2. Combiner la recherche par nom avec les filtres de poste, d'univers, de
   nationalité et d'équipe. Le filtre de nationalité comprend « Non renseignée ».
3. Sélectionner un profil ou utiliser **Ajouter**.
4. Dans « Fiche joueur », renseigner les informations, le poste, le coût, la
   **nationalité à droite du coût**, l'univers, les statistiques et le portrait.
5. Enregistrer le joueur. Les onglets dédiés donnent accès aux coups spéciaux
   et au contrat.

Les filtres peuvent être réinitialisés ; les résultats se trient notamment par
nom, coût, note globale, profil au poste ou statistique individuelle. Les noms
uniques sont possibles : le prénom peut rester vide, tandis que le nom d'usage
reste renseigné.

Modifier le catalogue prépare les prochaines créations de partie. Modifier le
joueur d'une sauvegarde concerne cette copie, sans réécrire le catalogue commun.

## Effectifs et équilibre

| Ensemble | État au 17 novembre 2026 |
| --- | ---: |
| Légendes internationales actives | 274 joueurs |
| Dont profils de nationalité belge | 78 joueurs, dont 10 gardiens |
| Dernier enrichissement historique | 124 nouveaux joueurs |
| Créations personnelles illustrées conservées | 26 profils |
| Anciens originaux génériques archivés lors du nettoyage | 13 profils |

Les quatre derniers classeurs contiennent 200 références pour 172 identités
distinctes : 124 nouveaux joueurs, 45 joueurs actifs déjà présents et trois
profils déjà archivés. Witsel, Fellaini et Lei Clijsters restent archivés.

Les noms d'usage, accents et variantes sont rapprochés avant ajout. Par exemple,
« Ronaldo (Nazário) » correspond à Ronaldo et Cafu au profil Marcos Cafu.
Un classement de notoriété n'est pas transformé automatiquement en classement
de puissance.

Les statistiques sont des choix d'équilibrage pour Golden Eleven, pas des notes
historiques officielles. Les rôles et spécialités individuelles servent à
différencier les joueurs. Les retouches des univers Ao Ashi, Hungry Heart,
Blue Lock et Captain Tsubasa international s'inscrivent dans cette logique.

Ajouter une nationalité ou quelques profils ne suffit pas à rendre un pays
jouable en Coupe du monde : il doit disposer de l'effectif requis, dont un gardien.

## Portraits : privilégier la carrière du joueur

L'objectif est un portrait en tenue de club ou de sélection pendant sa carrière,
idéalement proche de son apogée, dans un esprit vintage. Une photo récente
d'entraîneur ou de commentateur n'est pas un remplacement satisfaisant par défaut.

Chaque image intégrée doit correspondre à la bonne personne, à une période
adaptée et à un cadrage lisible. Sa source, son auteur et sa licence sont
consignés. Les catalogues de vignettes peuvent aider à identifier les bonnes
époques ; une image visible en ligne n'est pas automatiquement réutilisable.

Le dernier enrichissement inclut 13 portraits supplémentaires contrôlés,
notamment Bergkamp (1996), Papin (1997), De Wilde (1991), De Vlieger (1997),
Zetterberg (1997) et Radzinski (2004). Certaines photos sont proches de la
période souhaitée sans être des vignettes Panini ni des portraits de la saison
exacte. Les portraits encore manquants ou trop récents restent à améliorer,
dont celui de Philippe Albert. Les images personnelles ne sont pas remplacées
automatiquement par des résultats de recherche non validés.

Les crédits des images documentées sont accessibles à **`/credits-photos`**.

## Maintenance du catalogue : organisation des fichiers

Cette partie concerne les contributeurs qui modifient les données du projet.

```text
database/seeders/Players/
├── CaptainTsubasa/
│   ├── Clubs/                 un fichier par club, plus l'assemblage
│   ├── International/
│   │   ├── Roster.php
│   │   └── Countries/         personnages internationaux de Captain Tsubasa
│   └── FreePlayers.php
├── InternationalLegends/
│   ├── Roster.php
│   ├── Countries/             joueurs réels, un fichier par pays
│   └── Support/WorldLegendProfiles.php
├── AoAshi/Roster.php
├── BlueLock/Roster.php
├── EcoleDesChampions/Roster.php
├── HungryHeart/Roster.php
└── Original/Roster.php
```

`PlayerSeeder` assemble ces groupes. Les éléments communs de nationalité, de
photo et de compétences restent dans `database/seeders/Support/`. L'ordre des
groupes et des joueurs est explicite : le préserver lors d'une réorganisation,
car il détermine les identifiants d'un catalogue créé entièrement.

Les légendes réelles sont réparties dans les listes initiales, les compléments
`ADDITIONS` et les derniers ajouts `HISTORICAL_ADDITIONS`. Les profils communs
définissent les valeurs de base ; le champ `stats` permet une retouche
individuelle. Modifier un profil partagé peut affecter tous ses utilisateurs
lors de la prochaine création complète du catalogue. La Belgique conserve aussi
les rangs explicites de son tableau initial pour stabiliser ses paliers.

Le simple déplacement d'un fichier ne nécessite pas une migration de données.

## Maintenance : bases existantes, nouvelles parties et production

| Changement | Catalogue existant | Nouvelle partie | Partie déjà créée |
| --- | --- | --- | --- |
| Modifier seulement un fichier de données du seeder | Pas de mise à jour automatique | Utilise le catalogue effectivement installé, pas le fichier PHP directement | Copies conservées |
| Initialiser une base neuve avec les seeders | Crée le catalogue à partir des fichiers actuels | Utilise ce nouveau catalogue selon la configuration | Sans objet pour une base neuve |
| Appliquer une migration ciblée d'ajout | Ajoute les profils manquants selon ses règles | Peut utiliser les nouveaux profils | Aucun import automatique de joueurs |
| Retoucher un joueur dans l'éditeur du catalogue | Enregistre cette référence | Reprend cette valeur à la création | Copies conservées |
| Modifier un joueur dans une sauvegarde | Catalogue commun inchangé | Sans effet sur les autres parties | Modifie la copie concernée |

Les migrations récentes d'enrichissement préservent les profils déjà présents,
leurs modifications manuelles et les archives. Les anciens originaux génériques
visés par le nettoyage sont archivés uniquement s'ils restent sans portrait ;
les nouvelles créations personnelles ne subissent pas de nettoyage automatique.

En production, publier les fichiers ne lance pas les migrations. Le déploiement
doit appliquer les migrations prévues et inclure les fichiers des portraits.
Pour une simple mise à jour, utiliser les migrations ciblées plutôt qu'un reseed
général. Les opérations décrites ici ont été vérifiées localement ; leur
déploiement en production doit être confirmé séparément.

Les copies des anciennes parties ne sont pas réécrites. Un remplacement du
fichier image au même chemin peut cependant changer le visuel partout où ce
chemin est utilisé, y compris dans une sauvegarde existante.

## Maintenance : ajouter un joueur ou un portrait

1. Rechercher l'identité dans les profils actifs et archivés, avec ses variantes
   de nom, avant tout ajout. Ne pas réactiver un profil archivé par simple import.
2. Choisir l'univers, le pays, le poste et des statistiques cohérentes avec le
   niveau des autres joueurs. Renseigner la seule nationalité retenue.
3. Ajouter le profil au fichier du bon univers ou du bon pays.
4. Pour une image, vérifier identité, période et réutilisation ; conserver le
   fichier sous le nom attendu dans `public/storage/players/` et ajouter ses
   informations dans `resources/data/player-photo-credits.json`.
5. Prévoir une migration ciblée si la modification doit aussi atteindre un
   catalogue déjà installé. Préserver les portraits et retouches manuels.
6. Vérifier les identités, statistiques, portraits et filtres concernés, puis
   actualiser les effectifs de la documentation.

Les rapprochements des quatre classeurs et le suivi des portraits sont conservés
dans `resources/data/historical-roster-import.json` et
`resources/data/historical-portrait-review.json`. Les guides détaillés du dépôt
sont `docs/historical-roster-lists.md`, `docs/world-legends.md`,
`docs/player-portraits.md`, `docs/universe-configuration.md` et
`database/seeders/Players/README.md`.
