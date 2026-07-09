<div align="center">

# 🥋 Judo Club Mormant

### Application web du Judo Club de Mormant — gestion des membres, compétitions et administration du club

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![PWA](https://img.shields.io/badge/PWA-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white)](#)
[![Status](https://img.shields.io/badge/status-en%20production-success?style=for-the-badge)](#)

</div>

---

## 📖 Sommaire

- [Présentation](#-présentation)
- [Fonctionnalités publiques](#-fonctionnalités-publiques)
  - [🏠 Accueil](#-accueil)
  - [🔐 Connexion](#-connexion)
  - [📝 Inscription](#-inscription)
  - [👤 Profil](#-profil)
  - [🏆 Compétitions](#-compétitions)
  - [🎽 Passages de ceintures](#-passages-de-ceintures)
  - [👶 Mes enfants](#-mes-enfants)
  - [📜 Règlement intérieur](#-règlement-intérieur)
  - [🐛 Signaler un problème](#-signaler-un-problème)
- [Pages système](#-pages-système)
  - [🔑 Mot de passe oublié](#-mot-de-passe-oublié)
  - [✉️ Vérification email](#️-vérification-email)
  - [🚫 Accès refusé](#-accès-refusé)
  - [🛠️ Maintenance](#️-maintenance)
- [Espace administration](#-espace-administration)
  - [📊 Tableau de bord](#-tableau-de-bord)
  - [👥 Gestion des utilisateurs](#-gestion-des-utilisateurs)
  - [📧 Envoyer un mail](#-envoyer-un-mail)
  - [🏅 Gérer les compétitions](#-gérer-les-compétitions)
  - [🔗 Gérer les liens d'accueil](#-gérer-les-liens-daccueil)
- [Vue d'ensemble des rôles](#-vue-densemble-des-rôles)
- [Contribuer](#-contribuer)

---

## 🎯 Présentation

Ce dépôt contient le code source de l'application web du **Judo Club Mormant** (JCM), une **Progressive Web App (PWA)** permettant :

- 🙋 aux **adhérents** de s'inscrire, gérer leur profil, inscrire leurs enfants aux compétitions et suivre le programme technique par ceinture ;
- 🧑‍💼 aux **administrateurs** de gérer les membres, les compétitions, les communications par email et le contenu de la page d'accueil.

---

## 🌐 Fonctionnalités publiques

### 🏠 Accueil

Page vitrine du club, point d'entrée principal de l'application.

| Fonctionnalité | Description |
|---|---|
| Présentation du club | Mise en avant du Judo Club de Mormant |
| Horaires | Affichage des créneaux par catégorie et par saison |
| Accès rapides | Boutons vers l'inscription en ligne, la boutique du club et les Craq's |
| Liens externes | Entièrement configurables depuis l'espace d'administration |

---

### 🔐 Connexion

Formulaire d'authentification classique.

- Connexion via **email** et **mot de passe**
- Lien vers la page d'**inscription**
- Lien vers la **récupération de mot de passe**

---

### 📝 Inscription

Création d'un nouveau compte utilisateur.

- Saisie du **prénom**, **nom**, **email**, **mot de passe** et **photo de profil**
- Choix d'**opt-in / opt-out** pour les emails du club
- Lien de retour vers la page de connexion

---

### 👤 Profil

Espace personnel de gestion du compte.

- ✏️ Consultation et modification des informations personnelles
- 🖼️ Changement de la photo de profil
- 📬 Activation / désactivation des emails du club
- 🔒 Changement de mot de passe
- 🏆 Affichage des compétitions auxquelles l'utilisateur est inscrit
- 🗑️ Suppression définitive du compte

---

### 🏆 Compétitions

Module central de gestion des événements sportifs.

- 📅 Calendrier **mensuel** des compétitions avec navigation entre les mois
- 📋 Liste des événements à venir
- 🔍 Fiche détaillée par compétition
- ✅ Inscription directe depuis la fiche compétition
  - Inscription **manuelle** (l'utilisateur lui-même)
  - Inscription d'un **enfant** rattaché au compte
- 👀 Visualisation des inscrits *(réservé aux administrateurs)*
- 👤 Visualisation de ses propres inscriptions *(utilisateur connecté)*

---

### 🎽 Passages de ceintures

Programme pédagogique complet du club.

- Présentation de l'ensemble des **ceintures** disponibles
- Programme technique dédié à chaque ceinture, organisé en onglets :
  - 🥋 **Technique**
  - 📚 **Culture judo**
  - 🎥 **Vidéos**
- Détail des **prises**, **retournements**, **situations de travail** et vidéos associées

---

### 👶 Mes enfants

Gestion des profils enfants pour simplifier les inscriptions.

- ➕ Ajout de profils enfants
- ✏️ Modification et 🗑️ suppression des profils
- Champs : **prénom**, **nom**, **année de naissance**, **ceinture**, **poids**
- Utilisation directe des profils lors de l'inscription aux compétitions

---

### 📜 Règlement intérieur

- Consultation du **règlement intérieur** du club
- Consultation du **règlement de l'application**
- Informations sur les conditions d'adhésion, la sécurité et l'usage du site

---

### 🐛 Signaler un problème

Formulaire de remontée de bugs ou de comportements problématiques.

- Envoi direct au **support du club**
- Limité à **3 signalements par semaine** par utilisateur

---

## ⚙️ Pages système

### 🔑 Mot de passe oublié

- Demande d'envoi d'un email de réinitialisation
- Page de réinitialisation via **token** sécurisé
- Vérification de la validité du lien avant réinitialisation

### ✉️ Vérification email

- Vérification de l'adresse email après inscription
- Message de **succès** ou d'**échec** selon l'état du lien

### 🚫 Accès refusé

- Page affichée pour tout compte **banni**
- Lien de retour vers l'accueil

### 🛠️ Maintenance

- Page affichée lorsque le site est en **mode maintenance**
- Message d'indisponibilité temporaire

---

## 🛡️ Espace administration

### 📊 Tableau de bord

Vue d'ensemble globale du club, réservée aux administrateurs.

| Statistique | Description |
|---|---|
| 👥 Membres | Nombre total d'adhérents |
| 🛡️ Admins | Nombre d'administrateurs |
| 🚫 Comptes bannis | Nombre de comptes désactivés |
| 🏆 Compétitions | Nombre de compétitions enregistrées |
| ✅ Inscriptions | Nombre total d'inscriptions |
| 🐛 Signalements | Nombre de signalements reçus |
| 🔗 Liens d'accueil | Nombre de liens configurés |

Accès rapide vers : **utilisateurs**, **mails**, **compétitions** et **liens d'accueil**.

---

### 👥 Gestion des utilisateurs

- 🛠️ Activation / désactivation du **mode maintenance**
- 🔍 Recherche des membres par **nom**, **prénom** ou **email**
- 🔐 Gestion de l'accès au site (bannissement, réactivation…)

---

### 📧 Envoyer un mail

- ✍️ Rédaction d'un email avec **sujet** et **contenu enrichi** (éditeur riche)
- 🖼️ Ajout d'images dans le corps du message
- 📤 Envoi **groupé** à l'ensemble des membres

---

### 🏅 Gérer les compétitions

- ➕ Ajout de nouvelles compétitions
- ✏️ Modification des compétitions existantes
- 🗑️ Suppression de compétitions

**Champs disponibles :** nom, lieu, catégorie cible, date, date limite d'inscription, informations complémentaires, image.

- 📋 Liste complète des compétitions enregistrées

---

### 🔗 Gérer les liens d'accueil

- Modification des **URLs** utilisées par les boutons externes de la page d'accueil
- Enregistrement des liens directement depuis l'espace d'administration

---

## 🧭 Vue d'ensemble des rôles

| Rôle | Accès |
|---|---|
| 👤 **Visiteur** | Accueil, connexion, inscription, règlement, mot de passe oublié |
| 🙋 **Membre** | Profil, compétitions, ceintures, enfants, signalement de problème |
| 🛡️ **Administrateur** | Tableau de bord, gestion des utilisateurs, envoi de mails, gestion des compétitions et des liens d'accueil |

---

## 🤝 Contribuer

Les suggestions et retours sont les bienvenus ! N'hésitez pas à ouvrir une **issue** ou une **pull request** pour proposer des améliorations.

---

<div align="center">

Fait avec ❤️ pour le **Judo Club Mormant** 🥋

</div>