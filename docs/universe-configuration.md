# Univers et créations personnelles

Le composant `resources/js/Components/UniverseSelector.vue` est partagé entre
la création de partie et Gestion → Configuration. Il présente quatre familles :
Captain Tsubasa, autres mangas/animés, football réel, créations personnelles.
Les deux groupes Captain Tsubasa et les légendes sont des choix indépendants.
L'ancien filtre global international est toujours compris pour les sauvegardes
existantes ; activer un groupe ne réactive pas les autres par accident.

Les effectifs affichés viennent du catalogue actif à la création, et des copies
de joueurs de la sauvegarde en gestion. Ils ne sont pas une promesse de joueurs
libres immédiatement : les contrats et les autres règles de la partie s'appliquent.

`legends_countries` limite uniquement les légendes du marché et du draft :
`null` signifie tous les pays, `[]` aucun, et une liste explicite choisit les pays.
Les clubs et sélections déjà constitués ne sont pas démantelés. Modifier les
options d'une sauvegarde n'importe pas les nouveaux joueurs du catalogue.

Le catalogue des créations originales conserve 26 profils illustrés, dont
Nico Perera et Gauthier Leblanc, avec leurs noms et statistiques existants.
La migration `2026_11_17_210000_archive_unillustrated_original_players` archive
13 profils génériques précis, uniquement s'ils sont encore sans photo. Une
photo manuelle ou un fichier de portrait ajouté entre-temps protège le joueur.
Aucun profil personnel ajouté à l'avenir n'est supprimé automatiquement.

Les copies `game_players`, les images et les liens des anciennes parties restent
intacts. Les joueurs `fictional` générés pour compléter les sélections nationales
ne font pas partie de ce nettoyage. Les nouveaux clins d'œil se rajoutent dans
`database/seeders/Players/Original/Roster.php` ou dans l'éditeur du catalogue.
