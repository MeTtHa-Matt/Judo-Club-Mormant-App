# 🥋 Judo Club Mormant App

Application web PHP/MySQL destinée à la gestion du site du Judo Club de Mormant : espace public, espace membre, compétitions, gestion administrative et PWA mobile.

## Sommaire

- [Présentation](#présentation)
- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration de l’environnement](#configuration-de-lenvironnement)
- [Base de données](#base-de-données)
- [Lancement](#lancement)
- [Dépendances et scripts utiles](#dépendances-et-scripts-utiles)
- [Structure du projet](#structure-du-projet)

## Présentation

Le projet centralise les éléments numériques du club :

- page d’accueil et informations publiques,
- inscription et gestion des comptes,
- profils enfants et familles,
- calendrier des compétitions et inscriptions en ligne,
- espace d’administration pour gérer les utilisateurs, mails, liens, signalements et maintenance,
- expérience installable comme application mobile via PWA.

## Fonctionnalités

### Espace public

- accueil du club avec informations, liens utiles et contenus promotionnels,
- page des compétitions avec détails et dates limites,
- création de compte, connexion et réinitialisation de mot de passe,
- validation du règlement du club,
- formulaire de signalement et vérification par email,
- installation sur iPhone/iPad/Android via le tutoriel PWA.

### Espace membre

- profil utilisateur, photo et mot de passe,
- gestion des enfants et profils familiaux,
- gestion des ceintures et des informations sportives,
- liste des inscriptions aux compétitions,
- choix d’acceptation des emails du club,
- historique de présence / activités de compte selon le back-office.

### Administration

- gestion des comptes et droits admin,
- gestion des compétitions et des catégories ciblées,
- envoi d’emails à l’ensemble des abonnés,
- gestion des signalements,
- activation du mode maintenance,
- gestion des liens d’accueil,
- contrôle des accès et sécurité des sessions.

### Outils complémentaires

- intégration d’IndexNow pour le soumission des pages publiques,
- script de chat IA via variable d’environnement `IA_API_KEY`,
- mini-jeu `fruit_ninja.php` avec score enregistré pour les comptes,
- support de notifications et d’outils d’administration JS.

## Stack technique

- PHP 8.x
- MySQL / MariaDB
- Composer
- Bootstrap 5
- JavaScript vanilla
- PHPMailer
- vlucas/phpdotenv
- PWA (manifest + service worker)

## Prérequis

Avant de lancer le projet, vérifiez que vous avez :

- PHP 8.1 ou plus,
- MySQL 8 ou MariaDB,
- Composer,
- un serveur web local (Apache / Nginx) ou l’option `php -S`.

## Installation

```bash
git clone <url-du-depot>
cd Judo-Club-Mormant-App
composer install
```

Ensuite, créez la base de données MySQL puis importez le schéma fourni dans le dépôt :

```bash
mysql -u root -p
CREATE DATABASE judo_club_mormant;
EXIT;

mysql -u root -p judo_club_mormant < db.sql
```

## Configuration de l’environnement

Le projet utilise un fichier `.env` à la racine. Il n’existe pas de fichier `.env.example` dans le dépôt, donc il faut le créer manuellement.

Exemple :

```env
DB_HOST=127.0.0.1
DB_NAME=judo_club_mormant
DB_USER=root
DB_PASSWORD=

APP_BASE_URL=http://localhost:8000

MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=contact@example.com
MAIL_PASSWORD=your_smtp_password
MAIL_FROM=no-reply@example.com
MAIL_FROM_NAME="Judo Club Mormant"
MAIL_ENCRYPTION=tls
MAIL_REPORT_TO=contact@example.com
MAIL_REPORT_NAME="Signalements JCM"

INDEXNOW_SITE_URL=https://www.exemple.fr
LIEN_WHATSAPP=https://wa.me/33000000000
IA_API_KEY=votre_cle_api
```

> Les variables SMTP sont nécessaires pour les vérifications de compte, les rappels et les envois de mail.

## Base de données

Le fichier `db.sql` contient le schéma et les données de base du projet. Il inclut notamment :

- `account` : comptes utilisateurs et administrateurs,
- `child_profiles` : profils enfants associés aux familles,
- `families` et `family_members` : gestion des familles,
- `competitions` : événements et dates limites,
- `competition_cibles` : catégories par compétition,
- `ceintures` : référentiel des grades,
- `signalements_jcm` : signalements utilisateurs,
- `index_links_jcm` : liens affichés sur l’accueil.

## Lancement

### Option 1 : serveur PHP intégré

```bash
php -S 127.0.0.1:8000
```

Puis ouvre :

```text
http://127.0.0.1:8000/
```

### Option 2 : sous Apache / Nginx

Configurez le dossier du projet comme racine du site web et vérifiez que le document root pointe bien vers le dépôt.

## Dépendances et scripts utiles

### Composer

Le dépôt utilise :

```bash
composer install
```

### IndexNow

Le script `indexnow_watch.php` détecte les modifications de fichiers publics et peut soumettre les URLs à IndexNow.

Exemple :

```bash
INDEXNOW_SITE_URL=https://www.exemple.fr php indexnow_watch.php
```

Pour vérifier sans envoi réel :

```bash
INDEXNOW_SITE_URL=https://www.exemple.fr php indexnow_watch.php --dry-run
```

L’option `--force` resoumet toutes les pages publiques.

### PWA

Le site intègre :

- `manifest.json`,
- `sw.js`,
- `installer.php`,
- tutoriels d’installation pour iOS et Android.

## Structure du projet

```text
.
├── index.php
├── competitions.php
├── login.php
├── register.php
├── profile.php
├── mailing.php
├── chat.php
├── fruit_ninja.php
├── db.sql
├── composer.json
├── manifest.json
├── sw.js
├── includes/
│   ├── account/
│   ├── ceintures/
│   ├── competitions/
│   └── general/
├── js/
├── css/
├── img/
├── vendor/
└── storage/
```

## Notes

- Les pages administrateur sont activées selon les droits de l’utilisateur connecté.
- Le mode maintenance est géré côté application et peut être déclenché depuis l’interface d’administration.
- Les comptes de test peuvent être créés en local et les emails de vérification sont envoyés via le SMTP configuré.

---

Fait avec 🥋 pour le Judo Club de Mormant.