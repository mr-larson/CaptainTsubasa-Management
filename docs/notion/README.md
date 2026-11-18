# Mise à jour de la documentation Notion — 17 novembre 2026

**État : publié dans Notion le 17 novembre 2026.** La page d'accueil existante
a été consultée, puis complétée par des modifications ciblées. Ses archives et
ses autres contenus sont conservés.

Documentation cible :
[Golden Eleven / Captain Tsubasa Management](https://gautd8.notion.site/Captain-Tsubasa-Management-28c47313c8ca4fb5b0e3652491118849).

## Contenu prêt à intégrer

| Page | Destination prévue | Contenu |
| --- | --- | --- |
| [Nouveautés](nouveautes.md) | Journal existant de la page d'accueil, entrée du 14–17 novembre ; synthèse publiée de cette version locale détaillée | Gestion humaine, cartes, matchs, interface, éditeur et choix des univers |
| [Base de données des univers](base-de-donnees-des-univers.md) | [Sous-page publiée dans Notion](https://app.notion.com/p/41bc39bf98db8145b9f6cf2b1471cbe0), liée depuis l'accueil et sa configuration | Familles, filtres, effectifs, édition, portraits, organisation des données et maintenance |

Les contenus décrivent le projet local au 17 novembre 2026. Ils ne constituent
pas une confirmation de déploiement en production. Les effectifs sont un
instantané ; les compteurs de l'interface reflètent les données disponibles.

## Publication effectuée

1. Accueil et arborescence lus : un changelog existant et une sous-page d'archives.
2. Quinze modifications ciblées enregistrées dans l'accueil : présentation,
   boucle de jeu, configuration, modèles, services, édition, composants,
   présentation visuelle et nouvelle entrée du changelog. Aucun remplacement
   global ni suppression d'archives.
3. Sous-page « Base de données des univers » créée et relue : familles, choix des
   pays, édition, effectifs, portraits et maintenance présents, sans bloc inconnu.
4. Liens natifs ajoutés depuis le gameplay, la configuration, l'édition et le
   changelog ; lien de retour vers la documentation principale dans la sous-page.
5. Page des univers ouverte dans le panneau Notion du chat. Les écritures ont
   réussi sans avertissement ; la publication documentaire ne déploie pas le jeu.

## Sources de vérification

- Guides du projet : `docs/universe-configuration.md`, `docs/world-legends.md`,
  `docs/historical-roster-lists.md`, `docs/player-portraits.md` et
  `database/seeders/Players/README.md`.
- Configuration et édition : `UniverseSelector.vue`, `PlayerOrigin.php`,
  `UniverseOptions.php`, `PlayerFormFields.vue`, `PlayerEditorFilters.vue`.
- Fonctionnalités récentes : `PlayerRequestService.php`, `BonusCardShopService.php`,
  `ComebackCards.php`, moteur de match et composants d'affichage des statistiques.
- Historique des changements : commits `0c376b7`, `e00c20b`, `590afe2`,
  `5394cdf`, `ea8c19f`, `0021c8f`, `5d7bfd6`, `bbe7b1a`, `e0f4657`, `351a33e`.

Les chemins de fichiers des pages ci-dessous sont des références au dépôt,
pas des liens vers des pièces jointes hébergées dans Notion.
