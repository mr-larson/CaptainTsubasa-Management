# CaptainTsubasa-Management 🚀

![Version](https://img.shields.io/badge/version-0.1.0-blue.svg?cacheSeconds=2592000)
![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)

> Une plateforme de gestion d'équipe inspirée de Captain Tsubasa. Gérez votre équipe, entraînez vos joueurs et menez-les à la victoire!

## 🏠 [Documentation](https://gautd8.notion.site/Captain-Tsubasa-Management-28c47313c8ca4fb5b0e3652491118849?pvs=4)

## Fonctionnalités 🌱

- Création et gestion d'équipe.
- Recrutement de joueurs avec des compétences uniques.
- Système de match avec des cartes bonus/malus.
- Tournois et compétitions.
- Et bien plus encore!

## Installation 🔧

**Pré-requis**:  Avoir Docker installé et en cours d'exécution, [Composer](https://getcomposer.org/) et [Node.js](https://nodejs.org/) installés.

```bash
# Cloner le répertoire
git clone https://github.com/mr-larson/CaptainTsubasa-Management.git

# Se déplacer dans le dossier
cd CaptainTsubasa-Management

# Installer les dépendances PHP avec Sail
./vendor/bin/sail composer install

# Installer les dépendances JavaScript
npm install

# Construire les assets
npm run dev

# Configurer l'environnement
cp .env.example .env
# Générer une clé d'application
./vendor/bin/sail php artisan key:generate

# Lancer l'environnement de développement Sail
./vendor/bin/sail up

cp .env.example .env
