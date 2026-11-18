# Organisation des joueurs de référence

```text
Players/
├── CaptainTsubasa/
│   ├── Clubs/
│   │   ├── Roster.php              # assemble les joueurs et techniques des clubs
│   │   ├── ClubRosters.php         # effectifs et capitaines pour les contrats
│   │   ├── TeamCoreSpecialMoves.php
│   │   ├── Nankatsu.php
│   │   ├── Toho.php
│   │   └── …                      # un fichier par club
│   ├── International/
│   │   ├── Roster.php
│   │   └── Countries/             # Allemagne, France, Brésil… (personnages Tsubasa)
│   └── FreePlayers.php
├── InternationalLegends/
│   ├── Roster.php
│   ├── Countries/                 # Belgique, France, Brésil… (joueurs réels)
│   └── Support/WorldLegendProfiles.php
├── BlueLock/Roster.php
├── AoAshi/Roster.php
├── EcoleDesChampions/Roster.php
├── HungryHeart/Roster.php
└── Original/Roster.php
```

Chaque `Roster.php` expose les mêmes points d'entrée que le groupe d'origine :
`players()` et, selon l'univers, `specialMoves()` et `nationalities()`.
`PlayerSeeder` assemble les groupes ; `ContractSeeder` utilise les effectifs
de clubs Captain Tsubasa. Les nationalités, photos, calculs de compétences et
notes de tête communs restent dans `database/seeders/Support/`.

Les autres univers n'ont pas de sous-dossiers supplémentaires pour le moment.
Ils pourront être découpés par club ou par pays quand leur contenu le justifiera.

L'ordre explicite des groupes, des clubs et des pays est conservé : ne pas le
remplacer par une découverte automatique ou un tri des fichiers, car l'ordre
fixe les ids lors d'une création complète du catalogue. Déplacer un fichier ou
changer son namespace ne nécessite pas de migration de données ni de reseed.

Pour retoucher une légende internationale, voir `docs/world-legends.md`.

Les créations personnelles illustrées sont conservées dans `Original/Roster.php`.
Voir `docs/universe-configuration.md` pour les filtres de partie et le nettoyage
des anciens profils génériques.
