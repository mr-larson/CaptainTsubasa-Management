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

<img width="900" alt="Capture d’écran 2025-12-14 à 20 06 20" src="https://github.com/user-attachments/assets/a7d11ace-545c-471a-ae22-be13bf60ca1e" />
<img width="900" alt="Capture d’écran 2025-12-14 à 20 05 59" src="https://github.com/user-attachments/assets/ef03088b-a3ce-445d-a08d-4457f58efa4b" />
<img width="900" alt="Capture d’écran 2025-12-13 à 05 14 08" src="https://github.com/user-attachments/assets/73d071dc-e222-48a5-ad0f-5acf5cb27229" />
<img width="900" alt="Capture d’écran 2025-12-07 à 17 30 31" src="https://github.com/user-attachments/assets/04c3bb5f-d49e-4311-b34a-f9e0fa330d8f" />



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




