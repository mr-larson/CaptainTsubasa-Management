# Catalogue Ao Ashi

Le catalogue comporte neuf profils après l'ajout de Taira Nakamura, le
17 novembre 2026. Les références sont dans
`database/seeders/Players/AoAshi/Roster.php`.

## Taira Nakamura

Le [site officiel de l'anime](https://aoashi-pr.com/season1/character/) le
présente comme un milieu de l'équipe B d'Esperion, promu depuis les catégories
de jeunes, un an au-dessus d'Ashito et attentif à ses coéquipiers. Le
[wiki francophone](https://ao-ashi.fandom.com/fr/wiki/Tokyo_City_Esperion_FC)
est une référence complémentaire pour repérer les personnages ; la fiche
[anglophone](https://ao-ashi.fandom.com/wiki/Taira_Nakamura) précise aussi son
profil défensif et sa polyvalence.

Dans Golden Eleven, il est milieu avec défenseur comme poste secondaire,
orienté récupération et relance : défense 30, passe 28, interception 27,
tacle 25, tir 19. Sa frappe reste sous celle des attaquants Ao Ashi.
Les statistiques et « Relance maîtrisée » sont des adaptations au jeu, pas des
notes officielles ni le nom d'une technique canonique. Son âge est harmonisé
à 13 ans comme les huit autres profils de cet univers dans le catalogue,
plutôt qu'une reproduction de son âge dans l'œuvre.

Le visuel officiel est conservé sans retouche dans
`public/storage/players/taira-nakamura.png`. Son origine, le crédit, l'empreinte
et le cadrage d'affichage sont enregistrés dans
`resources/data/player-illustration-credits.json`. Les miniatures cadrent le
haut de l'image ; l'agrandissement conserve l'illustration complète et propose
un lien vers la source officielle. Ce registre d'illustrations protégées est
distinct des photographies Wikimedia sous licence libre ; aucune licence libre
n'est revendiquée pour ce visuel.

La migration `2026_11_17_230000_add_taira_nakamura` complète uniquement le
catalogue installé s'il manque. Elle ne modifie pas les autres profils,
ne réactive pas une archive et ne réécrit pas les copies des parties existantes.
Ses valeurs sont figées pour éviter de charger un futur roster à la place de
l'instantané de cet ajout. Sur une base neuve, le seeder fournit le même profil.
