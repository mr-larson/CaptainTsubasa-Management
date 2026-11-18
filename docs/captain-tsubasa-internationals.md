# Internationaux Captain Tsubasa : catalogue resserré

## État au 18 novembre 2026

47 personnages actifs, tous avec un portrait local. 28 seconds rôles sans
portrait sont archivés sur demande de l'utilisateur, sans suppression physique
ni modification des joueurs déjà copiés dans des parties. Le catalogue complet
local compte désormais 772 joueurs actifs, dont 437 légendes internationales.

Les personnages connus sans image ont été conservés : Gino Hernandez,
Salvatore Gentile et Ruud Klisman. Trois illustrations officielles Dream Team
ont été ajoutées ; les variantes Gino/Zino et Klisman/Klismann désignent les
mêmes personnages, sans créer de doublons ni modifier leurs identifiants.

Zangiev rejoint l'univers `captain_tsubasa_international`, avec la nationalité
Ouzbékistan, son drapeau et un portrait Bandai Namco. Son poste d'attaquant,
son rôle de capitaine, son profil physique et la technique Great Rock Blaster
sont confirmés sur la fiche officielle. Les notes restent adaptées à l'échelle
du jeu : attaque 34, tir 27, tête 30, vitesse 63, passe 19. Il reste en dessous
des meilleurs finisseurs du catalogue.

## Références officielles et images

- Base de référence : https://fr.bandainamcoent.eu/captain-tsubasa/captain-tsubasa-2-world-fighters/characters
- Zangiev : même page, ancre `#zangiev` ; portrait original `Zangiev_ICON.png`.
- Hernandez : https://www.tsubasa-dreamteam.com/en/character/180/
- Gentile : https://www.tsubasa-dreamteam.com/en/character/265/
- Klisman : https://www.tsubasa-dreamteam.com/en/character/415/

La page Bandai Namco sert de référence éditoriale pour identifier les personnages
et trouver les portraits officiels. Elle n'importe pas automatiquement tous ses
personnages dans le jeu. Les sources et crédits des quatre nouvelles images
figurent dans `resources/data/player-illustration-credits.json`. Les fichiers
sources sont conservés intacts ; seul un cadrage vectoriel d'affichage est ajouté.

## Profils retirés du catalogue actif

Ces personnages sont des seconds rôles identifiés ; leur archivage correspond
au choix de resserrer le catalogue, pas à une affirmation qu'ils sont inventés.

| Pays | Nombre | Joueurs archivés |
| --- | ---: | --- |
| Brésil | 1 | Blanco |
| Argentine | 1 | Juan Babington |
| Uruguay | 9 | Conzales, Amerigo, Pazu, Olivares, Filippo, Dionisi, Enrico, Pedro, Fengas |
| Pays-Bas | 1 | Haan |
| Mexique | 5 | Medina, Carvajal, Gomez, Espino, Rivera |
| Italie | 11 | Amoruso, Tacchinardi, Fresi, Galante, Pagotto, Totti, Branca, Delvecchio, Panucci, Lentini, Bianchi |

## Application et préservation des données

La migration 150000 utilise deux listes figées :
`secondary-internationals-retirement-2026-11-18.json` pour les 33 identités et
`key-international-portraits-2026-11-18.json` pour les trois portraits manquants.
Elle archive uniquement ces identités exactes, dans l'univers et le pays attendus,
si aucun portrait n'a été ajouté entre-temps. Un portrait personnel conserve le
joueur dans le catalogue actif. Les statistiques et identifiants restent stockés.
Les personnages importants déjà archivés ne sont pas réactivés.

Les 33 entrées sont également retirées des fichiers de pays des seeders, pour
les installations neuves. La migration 151000 utilise `zangiev-2026-11-18.json`
pour ajouter Zangiev uniquement s'il n'existe pas déjà, archives comprises.

Une sauvegarde locale précède l'application. Le contrôle après migration
confirme : 33 archives, 3 portraits complétés, 1 nouveau joueur ; aucun autre
champ existant modifié, aucune suppression physique, joueurs de parties et
contrats inchangés. Les migrations et les seeders restent distincts : pas de
réensemencement de la base locale existante.

## Mise à jour des portraits World Fighters

24 portraits de personnages conservés sont remplacés par les visuels officiels
Bandai Namco, cadrés sur les visages. Zangiev possédait déjà ce visuel ; les
personnages non présents dans la galerie et Natureza gardent leur image actuelle.
Les variantes Gino/Zino et Cruyfford/Kluivoort sont reliées aux fiches existantes,
sans changement de nom ni création de doublon.

La page officielle inverse les images de Hernandez et Gentile dans ses liens et
ses métadonnées. L'attribution est corrigée après contrôle visuel et vérification
des dossiers officiels `ZinoHernandez` et `SalvatoreGentile`. Ce constat figure
dans les crédits de ces deux images. Les anciens fichiers et crédits restent
conservés pour les parties et pour l'historique.

La migration 160000 utilise `bandai-international-portraits-2026-11-18.json` et
remplace uniquement les chemins importés connus ou les portraits absents. Les
portraits personnels, joueurs archivés, statistiques et copies des parties ne
sont pas modifiés. Le contrôle local confirme 24 changements de portrait et
aucun autre changement des fiches de référence. Une activité de jeu a modifié
les sauvegardes entre les relevés globaux ; le rejeu immédiat de la migration
confirme son idempotence et la préservation des joueurs de parties et contrats.

## Fonds transparents et cadrages du 18 novembre

Onze icônes World Fighters ont un fond noir intégré au fichier fourni : les cinq
Allemands, les deux Français, les deux Italiens et les deux Coréens. Le catalogue
préfère de nouveau leurs visuels transparents déjà présents (Dream Team pour
Gentile, Hernandez, Lee et Cha ; portraits historiques du projet pour les autres).
Les fichiers Bandai et leurs crédits restent conservés. La provenance externe des
portraits historiques n'étant pas documentée, aucune nouvelle attribution n'est
inventée.

La migration `2026_11_18_170000_use_transparent_international_portraits` remplace
uniquement ces chemins connus dans le catalogue actif et ses copies en partie.
Elle ne modifie pas les photos personnelles, statistiques ou contrats. Les bases
neuves utilisent les mêmes choix via `PlayerPhotos`.

Les cadrages de Taira Nakamura, Lucas Rondi, Fei Xiang, Ruud Klisman et Wang
Zhongming isolent le visage et le haut du buste sans retoucher les originaux.
Le module virtuel des crédits est désormais invalidé quand le registre change en
mode développement : les nouveaux cadrages ne restent plus cachés par un index
chargé avant leur ajout. Un déploiement nécessite la compilation et la migration.


## Mexique : réintégration du noyau aztèque

Zaragoza, Garcia, Suarez, Alvez et Lopez rejoignent Espadas dans le catalogue
actif : trois milieux et deux attaquants. Ils font partie des cinq guerriers du
Soleil aztèque présentés dans le manga World Youth et sur la page officielle
[World Fighters](https://en.bandainamcoent.eu/captain-tsubasa/captain-tsubasa-2-world-fighters/characters).
Le nom Alvez est conservé pour éviter un doublon avec son alias officiel Arbez.
Medina, Carvajal, Gomez, Espino et Rivera restent archivés.

Les cinq icônes officielles sont transparentes, conservées sans retouche et
créditées dans le registre des illustrations. Le cadrage se fait à l'affichage.
Les statistiques reprennent les profils de soutien archivés : ils restent sous
les grands meneurs et buteurs du catalogue. Aucun nouveau pouvoir de combinaison
n'est inventé pour le moteur de match.

La migration `2026_11_18_210000_restore_mexico_aztec_warriors` réutilise les ids des
profils archivés sans photo, ou ajoute les profils absents. Les portraits manuels,
archives avec photo personnelle, statistiques éditées et copies des parties ne
sont pas modifiés. Le seeder fournit les mêmes cinq personnages aux bases neuves.

## Sénégal

Ismail Senghor (meneur et capitaine) et Moussa Malick Diallo (attaquant rapide et puissant) sont ajoutés dans le fichier pays `Senegal.php`. Introduits dans Rise of New Champions et confirmés sur le [site officiel World Fighters](https://fr.bandainamcoent.eu/captain-tsubasa/captain-tsubasa-2-world-fighters/characters), ils disposent chacun d’une illustration officielle transparente Rise of New Champions. Aucun joueur secondaire de remplissage n’est ajouté.
