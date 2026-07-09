<div align="center">

# 🥋 Judo Club Mormant App

### Portail du club, inscriptions aux compétitions & espace membre sécurisé

[![PHP](https://img.shields.io/badge/PHP-Back--end-777BB4?style=for-the-badge&logo=php&logoColor=white)](#)
[![MySQL](https://img.shields.io/badge/MySQL-Base%20de%20données-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](#)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-UI-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](#)
[![PWA](https://img.shields.io/badge/PWA-Ready-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white)](#)
[![License](https://img.shields.io/badge/Licence-À%20définir-lightgrey?style=for-the-badge)](#)

</div>

---

## 🥊 Description

**Judo Club Mormant App** est une application web PHP dédiée à la gestion de l'espace numérique du **Judo Club de Mormant**. Elle centralise trois grandes missions :

- 📣 **Présenter le club** (horaires, liens utiles, boutiques associées)
- 📝 **Gérer les inscriptions** des membres, de leurs enfants et aux compétitions
- 🛠️ **Administrer** l'ensemble via un back-office complet

Le site propose également une expérience **Progressive Web App**, avec manifeste et service worker, pour une utilisation proche d'une application mobile native.

---

## 🌐 Espace public

- 🏠 **Page d'accueil** : horaires de la saison, liens utiles, informations d'inscription, accès aux boutiques/services associés
- 🏆 **Page compétitions** : calendrier mensuel, liste des événements à venir, détails de chaque compétition
- 👤 **Création de compte**, connexion, récupération de mot de passe
- 📜 **Validation du règlement** du club
- 🚨 **Signalement** d'un problème ou d'un comportement inapproprié

---

## 👥 Espace membre

- ✏️ Gestion du **profil personnel** : modification des informations, changement de mot de passe, photo de profil
- 👶 Gestion des **profils enfants** : données personnelles, ceinture, poids — pour une inscription rapide aux compétitions
- ✉️ Choix de recevoir ou non les **emails du club**
- 📋 Consultation de la **liste de ses inscriptions** aux compétitions

---

## 🏆 Compétitions — cœur du site

- Affichage dans un **calendrier interactif**
- Pour chaque compétition : date, lieu, catégorie cible, informations complémentaires, image
- Inscription en ligne des membres tant que la **période d'inscription** est ouverte

**Côté administrateurs :**
- Ajout, modification et suppression de compétitions
- Définition de la date limite d'inscription
- Choix des catégories concernées
- Ajout d'une image illustrative

---

## 🛠️ Interface d'administration

- 📊 **Tableau de bord général** : statistiques comptes, compétitions, inscriptions, signalements, liens d'accueil, emails acceptés
- 👥 **Gestion des utilisateurs** : liste, bannir / débannir, attribuer ou retirer les droits admin
- 🚧 **Mode maintenance** activable/désactivable
- ✉️ **Envoi d'emails** à l'ensemble des membres
- 🔗 **Gestion des liens** visibles sur la page d'accueil
- 🚨 **Consultation des signalements** reçus
- 🏆 **Gestion des compétitions**

---

## ✉️ Messagerie & sécurité

Le système de messagerie repose sur **PHPMailer** et gère :

- Emails de **vérification de compte**
- Emails de **contact**
- Emails **groupés** aux membres

Sécurité applicative :
- Vérification de compte par email
- Gestion des **tokens de réinitialisation** de mot de passe
- Logique de sécurité autour des **sessions utilisateur**

---

## 🗄️ Base de données

| Table | Contenu |
|---|---|
| Comptes utilisateur | Informations de connexion et de profil |
| Profils enfants | Données personnelles, ceinture, poids |
| Compétitions | Détails des événements |
| Catégories de cible | Catégories concernées par les compétitions |
| Ceintures | Référentiel des grades |
| Inscriptions | Liens membres/enfants ↔ compétitions |
| Signalements | Problèmes remontés par les utilisateurs |
| Liens d'accueil | Boutons et cartes de la page d'accueil |
| Tokens de sécurité | Vérification email, réinitialisation mot de passe |

---

## 🧰 Stack technique

- **Back-end** : PHP, MySQL
- **Front-end** : Bootstrap, JavaScript
- **Bibliothèques** : PHPMailer (envoi d'emails), Dotenv (gestion des variables d'environnement)
- **PWA** : manifeste + service worker pour le mode application mobile

---

## ✨ Fonctionnalités clés

- 🏠 Portail d'information complet sur le club
- 📝 Inscription et gestion de compte sécurisées, avec vérification email
- 👶 Gestion des profils enfants pour des inscriptions rapides
- 🏆 Calendrier de compétitions avec inscriptions en ligne
- 🚨 Signalement de comportements inappropriés
- 🛠️ Back-office complet : utilisateurs, compétitions, liens, emails, maintenance
- ✉️ Envoi d'emails groupés via PHPMailer
- 📴 Expérience Progressive Web App

---

<div align="center">

Fait avec 🥋 pour le Judo Club de Mormant.

</div>