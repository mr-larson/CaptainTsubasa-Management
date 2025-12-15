# CaptainTsubasa-Management ⚽️🎮

![Version](https://img.shields.io/badge/version-0.2.0-blue.svg)
![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)
![Laravel](https://img.shields.io/badge/Laravel-12-red)
![Vue](https://img.shields.io/badge/Vue-3-green)

> Jeu de gestion d’équipe de football inspiré de l’univers **Captain Tsubasa**.  
> Gère ton club, construis ton effectif et vis les matchs via un **moteur de jeu interactif**.

---

## 📸 Aperçu du jeu

> *Captures issues de la version actuelle du moteur de match.*

![Match Engine](docs/screenshots/match-engine.png)
![Player Cards](docs/screenshots/player-cards.png)
![Season Dashboard](docs/screenshots/season-dashboard.png)

---

## 🧠 Vision du projet

**CaptainTsubasa-Management** est un jeu de type **GM Mode / Management Football**, combinant :

- Gestion d’équipe (budget, effectif, contrats)
- Simulation de saison (calendrier, semaines, résultats)
- Matchs jouables via un moteur **tour par tour**
- IA offensive et défensive
- Système de stamina et de statistiques

Le projet vise un **MVP solide**, extensible vers :
- formations d’équipe,
- tactiques,
- progression des joueurs,
- modes de jeu avancés.

---

## 📚 Documentation
🏠 [Documentation](https://gautd8.notion.site/Captain-Tsubasa-Management-28c47313c8ca4fb5b0e3652491118849?pvs=4)
https://gautd8.notion.site/Captain-Tsubasa-Management-28c47313c8ca4fb5b0e3652491118849?source=copy_link

---

## ⚙️ Fonctionnalités actuelles

### 🏟️ Gestion & Saison
- Création de partie (GameSave)
- Duplication des équipes, joueurs et contrats
- Calendrier automatique (aller / retour)
- Simulation des matchs non joués
- Classement (victoires / nuls / défaites)

### ⚽️ Moteur de match
- Match **jouable** (home / away)
- Tours limités (30)
- Actions : Passe, Dribble, Tir, Spécial
- Duels basés sur :(Stat joueur × coef) × stamina + dé
- IA offensive et défensive
- Gestion de la stamina (attaque / défense / gardien)
- Ballon libre en cas de duel à égalité
- Logs détaillés des actions

### 🧩 Interface
- Cartes joueurs HOME / AWAY dynamiques
- Stats adaptées (joueur de champ / gardien)
- Barre d’énergie visuelle
- Action bar contextuelle
- Historique des actions du match

---

## 🧪 Stack technique

- **Backend**
- Laravel 12
- Eloquent ORM
- Services (MatchSimulator)
- **Frontend**
- Vue 3
- Inertia.js
- Tailwind CSS
- **Dev**
- Laravel Sail (Docker)
- Vite
- Ziggy

---

## 🚀 Installation

### Pré-requis
- Docker
- Composer
- Node.js

```bash
git clone https://github.com/mr-larson/CaptainTsubasa-Management.git
cd CaptainTsubasa-Management

composer install
./vendor/bin/sail composer install

npm install
npm run dev

cp .env.example .env
./vendor/bin/sail php artisan key:generate

./vendor/bin/sail up




