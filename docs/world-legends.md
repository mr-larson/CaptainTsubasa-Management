# Légendes internationales par pays

Les sélections réelles sont dans `database/seeders/Players/InternationalLegends/Countries/` :
`France.php`, `Germany.php`, `Argentina.php`, `Brazil.php`, `Italy.php`,
`Netherlands.php`, `Uruguay.php`, `Mexico.php`, `England.php`, `Spain.php`,
`Portugal.php` et `Croatia.php`.
La Belgique est regroupée avec les autres pays dans `database/seeders/Players/InternationalLegends/Countries/Belgium.php`.
Les personnages internationaux de Captain Tsubasa restent dans leur univers séparé.

## Modifier une sélection

Chaque fichier contient `ROSTER` (les profils initiaux) et `ADDITIONS`
(les compléments). Les deux listes alimentent la même sélection et le même
univers `world_legends` ; cette séparation préserve l'ordre du catalogue initial.

Chaque entrée expose `firstname`, `lastname`, `profile` et, au besoin :

- `world_cup_rank` : rang éditorial fourni, sans recalcul après suppression ;
- `position` et `secondary` : postes détaillés (`GB`, `DC`, `LIB`, `DD`, `DG`,
  `MDC`, `MC`, `MOC`, `MD`, `MG`, `AD`, `AG`, `AT`, `BU`) ;
- `stats` : valeurs individuelles qui remplacent celles du profil partagé ;
- `note` : complément de description.

Les profils de base (`keeper`, `libero`, `playmaker`, `striker`…) sont dans
`database/seeders/Players/InternationalLegends/Support/WorldLegendProfiles.php`. Modifier un profil partagé
change tous les joueurs qui l'utilisent au prochain seed : utiliser `stats`
pour une retouche individuelle. Les statistiques sont adaptées au jeu et ne
représentent pas une évaluation historique des carrières. Un rang de notoriété
ne devient pas automatiquement une note de tir ou de défense.

Pour les noms uniques (Pelé, Didi…), `firstname` peut être vide et `lastname`
contient le nom d'usage. Xavi est enregistré comme « Xavi Hernández » et le
Cafu du tableau correspond au profil existant « Marcos Cafu ».

## Comparaison du premier tableau fourni

Le fichier `50_joueurs_marquants_coupe_du_monde.xlsx` contient 44 lignes
renseignées, avec des rangs manquants. Les 44 références sont conservées dans
`tests/Fixtures/world_cup_legends.csv`, avec leurs postes, leur rang, leur statut
avant extension et le nom retenu dans le catalogue. Le fichier utilisateur n'est
pas modifié. Les six références absentes n'ont pas été inventées.

8 profils existaient déjà ; 36 sont ajoutés. Éric Cantona est un 37e ajout,
à la demande de l'utilisateur, hors classement des Coupes du monde.
Les commentaires historiques et les liens du classeur ne sont pas recopiés
comme faits vérifiés dans les descriptions des joueurs.

| Pays | Après le premier import | Ajouts |
| --- | ---: | ---: |
| Belgique | 41 | 0 |
| Allemagne | 17 | 5 |
| France | 18 | 4 |
| Argentine | 18 | 3 |
| Brésil | 12 | 8 |
| Italie | 9 | 6 |
| Pays-Bas | 11 | 1 |
| Uruguay | 9 | 2 |
| Mexique | 7 | 0 |
| Angleterre | 2 | 2 |
| Espagne | 4 | 4 |
| Portugal | 1 | 1 |
| Croatie | 1 | 1 |
| **Total** | **150** | **37** |

Le Portugal et la Croatie sont ajoutés aux nationalités sélectionnables pour
l'édition et les filtres. Ces profils seuls ne suffisent pas à rendre une nation
jouable : le jeu exige toujours un effectif suffisant, dont un gardien.

## Bases existantes et nouvelles parties

### Calibration des cinq onze proposés

Les 55 joueurs des onze anglais, croate, danois, espagnol et marocain ont
des surcharges individuelles dans `stats`. Les qualités de carrière sont
traduites en choix de gameplay, et non en notes objectives ou en statistiques
de la saison actuelle : vitesse, création, finition, récupération et jeu aérien
sont différenciés, avec des faiblesses conservées.

La vitesse et l'endurance utilisent l'échelle physique existante (environ
55–82 ici), distincte des compétences techniques. Les spécialités techniques
culminent à 30 ; l'attaque culmine à 36 et le tir à 29, contre 42 et 31 pour
Hyuga et Schneider. Ces bornes ne garantissent pas à elles seules l'équilibre
des matchs, qui dépend aussi des techniques et de la progression.
Les autres joueurs de ces sélections conservent leurs profils antérieurs.
Ces retouches concernent le seeder ; elles ne modifient pas une base déjà remplie.

La migration `2026_11_17_200000_add_missing_world_cup_legends` complète le
catalogue sans changer les ids, statistiques, coûts, portraits ou modifications
manuelles des joueurs présents, y compris ceux déjà archivés. Les parties
existantes ne sont pas modifiées ; les nouvelles parties utilisent le catalogue
actualisé, selon leurs filtres d'univers.

Sur une base neuve, `PlayerSeeder` compose directement la liste complète.
Ne pas lancer un reseed général pour appliquer une simple retouche à une base
existante : prévoir une migration ciblée pour les ajouts, suppressions ou
ajustements de statistiques que l'on souhaite réellement y appliquer.

## Extension suivante

Les quatre listes historiques supplémentaires portent ensuite le catalogue actif
à 274 légendes, dont 78 Belges. Le détail des 124 ajouts et du suivi des images
est décrit dans `docs/historical-roster-lists.md`.

## Portraits de carrière — 18 novembre 2026

393 des 394 légendes actives possèdent un portrait local, dont 77 Belges sur 78.
Les débuts en club sont privilégiés pour les Belges lorsque la source est vérifiée.
Regi Van Acker reste sans photo de joueur confirmée. Voir `player-portraits.md`
et `resources/data/legend-portrait-review.json` pour les sources, limites et droits.
La migration ciblée du 18 novembre met à jour les anciens imports du catalogue
sans remplacer les photos personnelles ni les copies des parties existantes.

## Synchronisation locale des listes — 18 novembre 2026

Les modifications actuelles des listes ont été rapprochées du catalogue local :
83 légendes ajoutées et 94 profils actualisés (tous univers confondus).
Les 5 joueurs retirés de l'École des champions sont archivés, sans suppression
physique. Le catalogue actif compte 710 joueurs, dont 357 légendes internationales.
Les 83 nouvelles légendes possèdent toutes un portrait de carrière ; les 273
portraits existants restent disponibles, dont sept nouveaux choix de début de
carrière belges. La migration `2026_11_18_090000_attach_expanded_legend_portraits`
applique les portraits manquants au catalogue local. Le Pepe portugais et son
homonyme de Captain Tsubasa ont chacun leur fichier et leur origine ; la liste
d'édition affiche leur univers et leur nationalité pour les distinguer.

La migration `2026_11_18_070000_update_reference_catalogue_from_reviewed_rosters`
utilise `database/data/reference-catalogue-update-2026-11-18.json`, instantané figé
des ajouts, anciennes valeurs, valeurs demandées et retraits. Elle ne réexécute
pas les migrations déjà appliquées, ne tronque aucune table, préserve les photos
personnelles et ignore les profils archivés lors des ajouts. Une retouche faite
après l'instantané est conservée. Aucun contrat ni contenu de partie n'est modifié.
La base locale a été contrôlée après application : aucun écart avec les listes
actuelles, hors photos personnelles conservées. Une sauvegarde préalable est
conservée localement dans `storage/app/private/catalogue-backups/`.

## Complément Brésil — Ronaldinho et Neymar

Deux profils complètent le catalogue : Ronaldinho (milieu créatif, secondaire
attaquant) et Neymar (attaquant, secondaire milieu). Leur dribble et leur création
sont privilégiés ; leur tir reste à 28, sous les 30 de Ronaldo et Pelé. Les
portraits de carrière sont intégrés : sélection brésilienne 2002 pour Ronaldinho,
Santos 2010 pour Neymar. Les entrées éditables sont dans `Brazil::EXTRA_ADDITIONS`,
assemblées en fin de catalogue pour préserver l'ordre des joueurs précédents.
La migration dédiée ajoute uniquement les profils absents, sans réactiver les
archives ni remplacer les retouches personnelles. Le catalogue local compte
désormais 712 joueurs, dont 359 légendes internationales.

## Corée du Sud, Arabie saoudite et Alin Stoica

Les sélections proposées par l'utilisateur sont définies dans les nouveaux
fichiers `Countries/SouthKorea.php` (11 joueurs) et `Countries/SaudiArabia.php`
(12 joueurs, dont Sami Al-Jaber en complément du onze). Alin Stoica rejoint
`Romania::EXTRA_ADDITIONS`. Les profils s'assemblent en fin de catalogue ; les
anciens identifiants locaux restent conservés.

Les statistiques sont une adaptation au jeu, sans reprendre les chiffres de
sélections fournis comme des données certifiées. Son et Cha privilégient vitesse
et finition, Park l'endurance et le pressing, Hong et Kim la défense. Majed
Abdullah privilégie la finition, Al-Thunayan et Al-Dawsari la technique. Les tirs
restent sous les références offensives mondiales du catalogue.

La migration d'ajout utilise l'instantané figé
`database/data/anderlecht-asian-legends-2026-11-18.json` ; une seconde remplace le
portrait importé de Zetterberg par sa vignette Anderlecht. Les photos personnelles,
archives et contenus des parties existantes sont conservés. Le catalogue local
compte 736 joueurs actifs, dont 383 légendes. Les nouveaux profils sont disponibles
pour les nouvelles parties qui incluent cet univers.

## Chine historique et compléments Captain Tsubasa World Youth

Le fichier `InternationalLegends/Countries/China.php` définit les onze joueurs
proposés : Zhang Huikang, Sun Jihai, Fan Zhiyi, Li Weifeng, Wu Chengying, Li Tie,
Zheng Zhi, Rong Zhixing, Wu Lei, Hao Haidong et Lee Wai Tong. La nationalité
`Chine` et son drapeau sont ajoutés à `Nationality`. Les valeurs sont adaptées au
jeu : Fan et Sun sont les cadres défensifs, Zheng et Rong les organisateurs,
Hao, Wu Lei et Lee les références offensives. Les records de buts et de sélections
du tableau de départ ne sont pas repris comme données certifiées. Lee est
positionné à gauche pour ce onze, avec l'avant-centre comme rôle alternatif.

Les fichiers `CaptainTsubasa/International/Countries/China.php`, `SouthKorea.php`
et `SaudiArabia.php` regroupent sept personnages World Youth : Xiao, Wang, Fei,
Lee Yong-un, Cha In-cheon, Owairan et Vulcan. Xiao privilégie blocage et
interception ; Owairan organisation défensive et relance. Cha et Lee sont de
bons attaquants complémentaires, Fei et Vulcan des spécialistes aériens avec
une mobilité limitée, Wang un créateur de couloir. Chaque personnage a une
technique adaptée aux actions existantes du jeu ; le canon de renvoi de Xiao
est une technique de blocage, sans introduire une nouvelle mécanique de renvoi
automatique. Les finitions aériennes nommées restent des techniques de tir.

La migration `2026_11_18_140000_add_china_and_world_youth_players` utilise
l'instantané figé `database/data/china-world-youth-2026-11-18.json` : onze profils
réels et sept personnages, chacun dans son univers. Aucun joueur existant,
contrat ni contenu des parties n'est modifié. Catalogue local : 754 joueurs,
dont 394 légendes. Les règles d'éligibilité et de composition des sélections
restent traitées dans le travail séparé sur le mode Coupe du monde.
