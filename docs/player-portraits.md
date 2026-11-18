# Portraits des joueurs réels

## État du catalogue au 18 novembre 2026

Le catalogue local comprend 394 légendes actives : 393 ont un portrait,
dont 77 des 78 joueurs belges. Les 83 nouveaux profils ajoutés le 18 novembre
sont tous illustrés. Regi Van Acker reste sans portrait : les résultats
vérifiés le montrent entraîneur, pas joueur. Ce cas reste ouvert.
Le relevé complet est dans `resources/data/legend-portrait-review.json`.

Les portraits privilégient la carrière de joueur, en tenue de club ou de sélection.
Pour les Belges, les débuts en club sont préférés lorsqu'une source vérifiable
existe : Mbo et Émile Mpenza à Mouscron (1997), Preud'homme au Standard (1980),
Nilis et Oliveira à Anderlecht (1991), puis Courtois, De Bruyne et Trossard à Genk
et Lukaku à Anderlecht (album 2011). Les années désignent l'édition de la
collection ou la période représentée ; elles ne certifient pas toujours la date
exacte du cliché. Certaines cartes historiques sont des rétrospectives.
Ce relevé ne garantit pas le tout premier portrait de carrière de chaque joueur.

## Fichiers, sources et droits

Les originaux téléchargés sont conservés sans retouche dans
`public/storage/players/`, avec leur empreinte SHA-256 et un nom daté distinct.
Le cadrage de certains scans de cartes ou de pages d'album se fait uniquement à
l'affichage, via `portrait_crop`, `image_width` et `image_height` dans
`PlayerPhoto.vue`. Les miniatures et l'agrandissement isolent le même joueur.

Deux registres distincts évitent de confondre les droits :

- `resources/data/player-photo-credits.json` conserve les photographies libres
  de Wikimedia Commons, leurs fichiers, auteurs et licences. La page publique
  `/credits-photos` reste disponible, y compris pour les anciens imports.
- `resources/data/player-illustration-credits.json` décrit les visuels protégés
  Panini, Merlin et autres éditeurs : source de collection, période, éditeur
  identifié et cadrage. Aucune licence libre n'est revendiquée pour ces scans.
  Leur présence publique ne constitue pas une autorisation de réemploi.
  L'agrandissement renvoie à la source du visuel concerné.

Une recherche d'images n'est pas une validation : les homonymes, faux visuels et
photos modernes d'entraîneur/commentateur sont écartés après contrôle visuel.
Les anciennes photos libres restent conservées avec leurs crédits ; la sélection
préférée utilise un autre chemin pour éviter les anciennes images en cache.

## Bases existantes, bases neuves et parties

`PlayerPhotos::pathFor()` choisit le portrait `preferred_for_catalog` disponible
puis les fichiers habituels en repli. `PlayerSeeder` l'utilise pour une base neuve.
Une nouvelle partie copie ensuite le portrait du catalogue local.

La migration `2026_11_18_010000_attach_reviewed_legend_portraits` applique le choix
validé aux catalogues existants. Son instantané est figé dans
`database/data/legend-portraits-2026-11-18.json`. Seuls les chemins absents ou les
anciens chemins d'import connus sont modifiés. Les photos personnelles, joueurs
archivés, autres univers, statistiques et copies des parties restent conservés.
La migration est appliquée en local. En production, il faut déployer les fichiers
versionnés et exécuter les migrations ; aucune mise à jour distante n'a été faite.

Les migrations précédentes d'ajout et d'archivage restent en place. Les profils
archivés (dont Lei Clijsters, Witsel et Fellaini) ne sont pas réactivés.

## Ajouter ou remplacer un portrait

Vérifier l'identité, la période et le rôle du joueur sur l'image réelle.
Conserver le fichier original, inscrire sa source et ses droits dans le registre
approprié et n'attribuer qu'un seul portrait préféré par joueur. Les coordonnées
de cadrage doivent rester dans les dimensions du fichier. Pour une base existante,
prévoir une mise à jour ciblée qui préserve les images personnelles.

## Complément de début de carrière du 18 novembre

Sept anciens imports belges sont remplacés : Eden Hazard à Lille (2009–2010),
Youri Tielemans à Anderlecht (2014–2015), Jérémy Doku à Anderlecht (2019–2020),
Thomas Buffel à Feyenoord (2000–2001), Thomas Vermaelen à l'Ajax (2006–2007),
Jan Vertonghen à l'Ajax (2010–2011) et Stijn Stijnen à Bruges (2007).
Les autres anciens portraits belges ont été revus ; les photos déjà adaptées à
leur période de joueur restent conservées. Les originaux et les crédits restent
intacts. La migration `2026_11_18_071000_refresh_early_belgian_career_portraits`
utilise son propre instantané et préserve les photos personnelles et sauvegardes.

## Complément international et homonymes du 18 novembre

Les 83 nouveaux portraits couvrent le Portugal (8), l'Angleterre (7), l'Espagne
(6), la Croatie (8), le Danemark (10), le Maroc (11), la Suède (11), la Norvège
(11) et le Nigéria (11). Le Portugal possède désormais ses 12 portraits.
Les cartes de carrière sont privilégiées ; quelques photographies de presse
complètent les sources lorsque le portrait de collection n'est pas adapté.
Les dates incertaines et cartes rétrospectives sont signalées dans les crédits,
notamment pour Daniel Agger et Larbi Benbarek.

La migration `2026_11_18_090000_attach_expanded_legend_portraits` utilise
`database/data/new-legend-portraits-2026-11-18.json`. Elle cible le nom, l'univers
et la nationalité, ne remplace que les chemins absents ou connus, et préserve
les photos personnelles, archives, statistiques, contrats et copies de parties.
Les 83 changements sont appliqués en local et contrôlés. Les bases neuves
utilisent les mêmes portraits via le seeder ; les nouvelles parties reprennent
les images du catalogue. La production nécessite le déploiement et les migrations.

Les homonymes entre univers sont isolés à l'initialisation. Le Pepe portugais
utilise `players/pepe-portugal-2008.jpg` et celui de Captain Tsubasa conserve
`players/pepe.webp`. La liste d'édition affiche l'univers et la nationalité
des homonymes ; la recherche accepte aussi ces informations. Les deux identités
et leurs identifiants restent distincts.

## Quatre portraits belges remplacés à la demande du 18 novembre

Weber utilise un scan du Cercle Brugge (Football 1992), Sonck un portrait de
Genk (Football 2002), Van Buyten celui du Standard (Football 2000) et Gerets
celui du Standard (Football 1981). Les sources et cadrages sont enregistrés dans
les crédits ; le filigrane présent sur la source de Van Buyten reste conservé.
Les anciens fichiers restent disponibles pour les sauvegardes existantes.
La migration `2026_11_18_100000_refresh_four_belgian_portraits` et son instantané
`database/data/belgian-four-portraits-2026-11-18.json` actualisent uniquement
les imports connus du catalogue, en préservant les photos personnelles.

## Ajouts brésiliens du 18 novembre

Ronaldinho et Neymar sont ajoutés aux légendes internationales du Brésil avec
des portraits Panini de carrière : Ronaldinho en sélection (2002), Neymar à
Santos (2010, année lisible sur la vignette). Les originaux, crédits et cadrages
sont enregistrés dans le registre. Le seeder les ajoute après les anciens profils
pour conserver leur ordre. La migration `2026_11_18_110000_add_ronaldinho_and_neymar`
utilise `database/data/brazil-legends-2026-11-18.json` ; elle ne modifie aucun
profil existant ni joueur archivé. Le catalogue local compte 712 joueurs,
dont 359 légendes et 358 portraits de légendes.

Le portrait de Solskjær est ensuite remplacé par la vignette Panini Champions
League 1999–2000 de Manchester United (nº 136), à la demande de l'utilisateur.
La page originale reste intacte ; seul son portrait est cadré à l'affichage.
La migration `2026_11_18_120000_refresh_solskjaer_1999_portrait` préserve les
photos personnelles et les anciennes copies des parties.

## Anderlecht et sélections asiatiques — 18 novembre 2026

24 profils ajoutés : Alin Stoica (Roumanie), 11 Sud-Coréens et 12 Saoudiens.
La sélection saoudienne comprend le onze proposé et Sami Al-Jaber, cité parmi
les figures majeures. Tous ont une photo de carrière locale. Les albums Panini
1986, 1994, 1998, 2002, 2014, 2018 et 2022 sont privilégiés. Deux exceptions de
provenance : Saleh Al-Nu'eimeh dans Al-Saqr (1986), Mohammed Noor sur une photo
sportive d'archive Arriyadiyah (date exacte non confirmée).

Stoica est représenté dans l'album belge 2002 avec Anderlecht. Le portrait de
Pär Zetterberg reprend exactement la vignette Anderlecht fournie par le lien de
l'utilisateur, publiée sur le site du Sporting Charleroi ; son année précise
n'est pas confirmée. Les sources originales restent intactes ; le cadrage est
une métadonnée d'affichage. Les crédits et droits sont dans le registre habituel.

Les migrations 130000 et 131000 ajoutent les profils absents et remplacent
uniquement l'ancienne photo importée de Zetterberg. Elles conservent les archives,
photos personnelles, contrats et joueurs de parties existantes. Après application
locale : 736 joueurs, dont 383 légendes et 382 portraits de légendes.

## Chine et personnages World Youth — 18 novembre 2026

Onze légendes chinoises ont un portrait de carrière : six dans l'album Panini
2002 (Sun, Fan, Li Weifeng, Wu Chengying, Li Tie et Hao), Wu Lei dans une carte
Panini Espanyol 2018–2019, Zheng Zhi à Charlton, et trois portraits d'archives
pour Zhang Huikang, Rong Zhixing et Lee Wai Tong. Les années exactes des archives
ne sont pas certifiées. Zhang est le joueur à droite sur la photo avec Kong
Guoxian, identifié par la légende de l'article Sina ; le cadrage le distingue.

Sept personnages de Captain Tsubasa World Youth possèdent une illustration du
site officiel Dream Team / KLab : Xiao Junguang, Wang Zhongming, Fei Xiang,
Lee Yong-un, Cha In-cheon, Mark Owairan et Vulcan. Ils sont rattachés à
`captain_tsubasa_international`, séparément des joueurs réels `world_legends`.
Leurs fichiers originaux sont conservés sans retouche ; seul le cadrage
vectoriel d'affichage est enregistré dans les crédits.

La migration 140000 ajoute uniquement les joueurs absents ; les archives et
modifications personnelles sont conservées. Après application locale : 754
joueurs actifs, dont 394 légendes internationales et 393 portraits de légendes.

## Catalogue Captain Tsubasa resserré et Zangiev

Les 40 internationaux Captain Tsubasa actifs ont désormais tous un portrait.
Hernandez, Gentile et Klisman disposent d'illustrations officielles Dream Team ;
Zangiev possède le portrait officiel World Fighters fourni par Bandai Namco.
Les 33 seconds rôles sans portrait ont été archivés sur demande, avec conservation
des parties et des données originales. La liste et les sources sont détaillées
dans `docs/captain-tsubasa-internationals.md`. Catalogue local après application :
722 joueurs actifs ; le nombre de légendes réelles reste 394 (393 illustrées).

La migration 160000 remplace ensuite 24 portraits Captain Tsubasa par les visuels
World Fighters de Bandai Namco. Les personnages, nationalités et statistiques
restent identiques ; les deux images inversées de Hernandez et Gentile sur la
page officielle ont été réattribuées après contrôle visuel. Les autres portraits
et les anciens fichiers sont conservés.
