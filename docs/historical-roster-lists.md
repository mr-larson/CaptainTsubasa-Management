# Import des quatre listes historiques

Les quatre classeurs utilisateur ont été lus sans modification : chacun contient
50 références. Les faits marquants des classeurs ne sont pas recopiés comme des
faits vérifiés dans le jeu. Les numéros regroupent les postes et ne sont pas
utilisés comme un classement de force.

## Rapprochement

Les 200 lignes correspondent à 172 identités distinctes :

- 45 joueurs actifs déjà présents ;
- 124 nouveaux joueurs ;
- 3 profils déjà archivés, conservés dans cet état : Axel Witsel, Marouane Fellaini
  et Lei Clijsters (le « Leo Clijsters » du fichier).

| Liste | Ajouts uniques à cette étape | Déjà actifs | Déjà archivés | Répétitions d’autres listes |
| --- | ---: | ---: | ---: | ---: |
| Internationaux 1994–2002 | 31 | 19 | 0 | 0 |
| Championnat belge 2000–2010 | 42 | 6 | 2 | 0 |
| Championnat belge années 1990 | 28 | 17 | 1 | 4 |
| Ligue des champions années 1990 | 23 | 12 | 0 | 15 |

Les variations d'accents, de particules et de noms d'usage sont rapprochées :
Cafu correspond au profil Marcos Cafu ; Ronaldo (Nazário) au profil Ronaldo ;
Éric/Eric Deflandre est une seule identité ; Alex Czerniatynski est enregistré
comme Alexandre Czerniatynski. Les clins d'œil et personnages de mangas restent
séparés des joueurs réels.

Le rapprochement des 200 lignes est conservé dans
`resources/data/historical-roster-import.json` : source, numéro, nom fourni,
nom retenu, nationalité et statut avant import.

## Organisation et niveaux

Les nouveaux profils sont dans `HISTORICAL_ADDITIONS` du fichier de leur pays,
sous `database/seeders/Players/InternationalLegends/Countries/`. Ils sont ajoutés
après les 150 profils existants, sans changer les valeurs ou l'ordre de ceux-ci.
La Belgique possède maintenant 78 profils actifs, dont 10 gardiens ; l'univers
réel complet compte 274 joueurs actifs.

Les postes fournis déterminent les profils de base. Des spécialités individuelles
modérées distinguent notamment Kahn, Schmeichel, Nesta, Baresi, Rivaldo, Figo,
Beckham, Henry, Van Basten et Shevchenko. Les statistiques sont des adaptations
au jeu ; elles ne constituent pas des notes historiques. Les profils existants
et leurs modifications manuelles sont conservés.

Pour les doubles nationalités du fichier, la première sélection indiquée est
retenue dans le jeu, qui stocke une seule nationalité : Belgique pour Tchité,
Strupar, Weber et Oliveira. « Nigeria » est normalisé vers « Nigéria ».
La Yougoslavie est conservée comme sélection historique avec un indicateur neutre.
Les nouveaux pays peuvent être filtrés dans le catalogue et la configuration ;
ils ne deviennent jouables en Coupe du monde que s'ils disposent d'un effectif
suffisant, dont un gardien.

## Portraits

Les images sont choisies pendant la carrière de joueur, avec priorité au style
vignette et au portrait en club/sélection. Un portrait récent d'entraîneur ou de
consultant n'est pas intégré automatiquement. Une image en pied peu lisible
comme portrait ou prise dans un contexte inadapté est écartée.

Le contrôle du 18 novembre 2026 porte sur les 357 légendes actives :
356 disposent maintenant d'un portrait de carrière, dont 77 Belges sur 78.
Regi Van Acker reste ouvert, faute de photo vérifiée de sa période de joueur.
Les vignettes Panini, Merlin et autres scans protégés sont séparés des photos
libres de Wikimedia dans les registres des crédits. Leur disponibilité publique
ne vaut pas autorisation de réemploi ; aucune licence libre n'est revendiquée.

Le suivi des 172 identités des classeurs est actualisé dans
`resources/data/historical-portrait-review.json`. Le relevé global et les sources
retenues sont dans `resources/data/legend-portrait-review.json` ; les critères et
les règles de conservation sont détaillés dans `docs/player-portraits.md`.
Les fichiers originaux restent intacts et leur cadrage est réalisé à l'affichage.

## Mise à jour

La migration `2026_11_17_220000_add_historical_roster_lists` ajoute les références
absentes sans réactiver les archives, modifier les ids ou écraser les profils
existants. `PlayerSeeder` utilise les mêmes fichiers pour les bases neuves.
Les nouvelles parties utilisent le catalogue enrichi selon leurs options ; les
anciennes copies `game_players` ne sont pas modifiées.

## Évolution après l'import initial

La synchronisation du 18 novembre ajoute les 83 nouvelles références des listes
modifiées : 357 légendes sont actives, dont 356 illustrées. Les 83 nouveaux profils
ont un portrait de carrière vérifié. L'audit complet est actualisé ; les 77 portraits belges
restent disponibles, avec sept remplacements supplémentaires de début de carrière.
