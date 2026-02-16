# CAHIER DES CHARGES FONCTIONNEL

## Refonte Application BOW

*Book of Work - Gestion des Risques et Projets*

**Version 1.0 | Janvier 2025**

---

## 1. Introduction

### 1.1 Contexte du projet

L'application BOW (Book of Work) est un outil de gestion intégré qui permet de piloter les risques, les projets et la gouvernance au sein de l'organisation. La version actuelle présente des limitations qui freinent son évolution et son adoption.

Ce projet de refonte vise à moderniser l'application pour offrir une meilleure expérience utilisateur, une performance accrue et des fonctionnalités enrichies.

### 1.2 Objectifs de la refonte

- **Améliorer la performance :** Temps de chargement réduits, navigation fluide
- **Moderniser l'interface :** Design actuel, responsive, accessible sur mobile
- **Renforcer la sécurité :** Double authentification, protection des données
- **Activer les notifications :** Alertes email automatiques, rappels de tâches
- **Préparer la croissance :** Supporter plus d'utilisateurs et de données

---

## 2. Périmètre fonctionnel

### 2.1 Modules existants à conserver

La refonte conservera l'ensemble des fonctionnalités métier existantes :

| Module | Fonctionnalités |
|--------|----------------|
| **Gestion des risques** | Hiérarchie à 3 niveaux (Thèmes > Catégories > Risques), bibliothèque de contrôles, scoring dynamique RAG (Rouge/Ambre/Vert), plans d'action et remédiation, pièces jointes |
| **Gestion des tâches** | Création et suivi des tâches (WorkItems), gestion des dépendances entre tâches, jalons et milestones, assignation aux équipes |
| **Gouvernance** | Items de gouvernance liés aux risques, suivi des décisions et actions, intégration avec les modules risques et tâches |
| **Fournisseurs** | Gestion des fournisseurs, suivi des contrats et échéances, gestion des factures, alertes de renouvellement |
| **Équipes** | Gestion des équipes et membres, assignation des responsabilités, vue d'ensemble de la charge de travail |
| **Tableaux de bord** | Dashboard principal avec indicateurs clés, synthèse RAG des risques, vue consolidée multi-modules |

### 2.2 Améliorations attendues

#### Interface utilisateur

- Design moderne et épuré avec charte graphique cohérente
- Navigation intuitive avec menu latéral rétractable
- Adaptation automatique aux écrans mobiles et tablettes
- Mode sombre (optionnel)
- Raccourcis clavier pour les actions fréquentes

#### Performance perçue

- Chargement instantané des pages (< 2 secondes)
- Mise à jour en temps réel des données (sans rechargement)
- Recherche rapide avec auto-complétion
- Pagination intelligente des listes longues

---

## 3. Exigences utilisateurs

### 3.1 Gestion des accès

| Exigence | Description |
|----------|-------------|
| **Authentification sécurisée** | Connexion par email/mot de passe avec option de double authentification (application mobile type Google Authenticator) |
| **Gestion des rôles** | 3 niveaux : Administrateur (accès total), Manager (gestion équipe), Utilisateur (consultation et actions assignées) |
| **Permissions granulaires** | Accès configurable par département et par thème de risque. Un utilisateur peut avoir accès à certains thèmes uniquement. |
| **Historique des connexions** | L'administrateur peut consulter l'historique des connexions de tous les utilisateurs (date, heure, adresse IP) |

### 3.2 Notifications et alertes

Le système de notifications permettra aux utilisateurs d'être informés automatiquement :

- **Rappels de tâches :** Notification par email X jours avant l'échéance d'une tâche
- **Alertes contrats :** Notification automatique avant expiration d'un contrat fournisseur
- **Dépassement de seuil :** Alerte quand un risque dépasse le seuil d'appétit défini
- **Récapitulatif quotidien :** Email consolidé des actions en attente (optionnel)
- **Assignation :** Notification quand une tâche ou un risque est assigné à l'utilisateur

### 3.3 Gestion des fichiers

- Upload de pièces jointes (PDF, Word, Excel, images) jusqu'à 10 Mo par fichier
- Prévisualisation des documents sans téléchargement
- Organisation par dossiers au niveau des risques et des tâches
- Historique des versions des documents

---

## 4. Parcours utilisateurs types

### 4.1 Parcours Risk Manager

**Persona :** Marie, Responsable des risques opérationnels

1. Connexion avec double authentification
2. Consultation du dashboard : vue synthétique des risques RAG par thème
3. Drill-down sur les risques en rouge : identification des actions urgentes
4. Mise à jour du scoring d'un risque après revue
5. Création d'une action de remédiation avec assignation et échéance
6. Export PDF du rapport mensuel pour le comité de direction

### 4.2 Parcours Chef de projet

**Persona :** Thomas, Chef de projet IT

1. Connexion et accès au module Tâches
2. Vue Kanban des tâches de son équipe par statut
3. Création d'une nouvelle tâche avec dépendances
4. Assignation aux membres de l'équipe
5. Suivi de l'avancement via le diagramme de Gantt
6. Validation des tâches terminées

### 4.3 Parcours Administrateur

**Persona :** Sophie, Administratrice système

1. Gestion des utilisateurs : création, modification, désactivation
2. Configuration des permissions par département
3. Paramétrage des seuils d'alerte (risk appetite)
4. Consultation des logs d'audit
5. Configuration des templates de notification

---

## 5. Contraintes et exigences non-fonctionnelles

### 5.1 Disponibilité

- Application accessible 24h/24, 7j/7
- Objectif de disponibilité : 99,5% (environ 1h45 d'indisponibilité mensuelle maximum)
- Maintenance planifiée en dehors des heures de bureau (20h-6h)

### 5.2 Performance

- Temps de réponse moyen < 2 secondes pour les pages standards
- Dashboard chargé en < 3 secondes
- Support de 100 utilisateurs simultanés minimum
- Exports de données volumineux : traitement asynchrone avec notification

### 5.3 Compatibilité navigateurs

- Chrome (dernières 2 versions)
- Firefox (dernières 2 versions)
- Safari (dernières 2 versions)
- Edge (dernières 2 versions)

### 5.4 Sauvegarde des données

- Sauvegarde automatique quotidienne
- Rétention des sauvegardes : 30 jours
- Test de restauration mensuel
- Objectif de perte de données (RPO) : < 1 heure

---

## 6. Planning prévisionnel

| Phase | Durée | Livrables clés |
|-------|-------|----------------|
| 1. Préparation et configuration | 2 semaines | Environnements de dev/test prêts |
| 2. Développement backend | 6 semaines | APIs fonctionnelles, base de données migrée |
| 3. Développement interface | 8 semaines | Nouvelle interface complète |
| 4. Notifications et alertes | 2 semaines | Système d'emails opérationnel |
| 5. Tests et corrections | 4 semaines | Rapport de tests, corrections |
| 6. Migration et mise en production | 2 semaines | Application en production |
| **TOTAL** | **24 semaines** | **(environ 6 mois)** |

---

## 7. Critères d'acceptation

La recette finale sera validée si les conditions suivantes sont remplies :

1. Toutes les fonctionnalités existantes sont opérationnelles dans la nouvelle version
2. Les temps de réponse respectent les objectifs définis
3. La double authentification fonctionne correctement
4. Les notifications email sont envoyées et reçues
5. Les données ont été migrées sans perte ni corruption
6. L'application est accessible sur mobile (responsive)
7. La documentation utilisateur est disponible
8. Aucun bug bloquant n'est présent

---

**Document validé par :**

Sponsor projet : _________________________ Date : ___/___/______

Représentant métier : _________________________ Date : ___/___/______

Chef de projet : _________________________ Date : ___/___/______
