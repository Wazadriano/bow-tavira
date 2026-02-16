# CAHIER DES CHARGES TECHNIQUE

## Application BOW

*Book of Work — Gestion des Risques*

---

## 1. Stack technique

| Couche | Technologie | Justification |
|--------|-------------|---------------|
| Frontend | **React 18** TypeScript | Composants réutilisables, typage fort, écosystème mature, facilité de recrutement |
| Backend | **Laravel 11** PHP 8.3 | Framework complet : auth, ORM, queues, notifications, validation intégrés nativement |
| Base de données | **PostgreSQL 16** | Accès concurrents, JSONB pour données flexibles, full-text search, backup PITR |
| Cache | **Redis 7** | Cache applicatif, sessions révocables, driver de queue pour jobs asynchrones |
| Stockage fichiers | **S3 / MinIO** | Abstraction Laravel Storage, URLs signées, migration cloud transparente |

---

## 2. Architecture

### 2.1 Vue d'ensemble

L'application suit une architecture classique API REST avec séparation frontend/backend :

```
┌─────────────────────────────────────────────┐
│  CLIENTS                                    │
│  Navigateur Web • Application Mobile        │
└──────────────────────┬──────────────────────┘
                       │ HTTPS
┌──────────────────────▼──────────────────────┐
│  NGINX                                      │
│  Reverse Proxy • SSL/TLS • Rate Limiting    │
│  Gzip                                       │
└──────────────────────┬──────────────────────┘
                       │
        ┌──────────────┴──────────────┐
        ▼                             ▼
┌───────────────────┐   ┌───────────────────┐
│  FRONTEND         │   │  BACKEND API      │
│  React+TypeScript │   │  Laravel 11       │
│  Vite             │   │  Sanctum          │
│  TailwindCSS      │   │  Eloquent         │
│  Shadcn/ui        │   │  Queues           │
└───────────────────┘   └────────┬──────────┘
                                 │
        ┌────────────────────────┼────────────────┐
        ▼                        ▼                ▼
┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│ PostgreSQL   │   │ Redis        │   │ S3 / MinIO   │
│ Données      │   │ Cache        │   │ Fichiers     │
│ métier       │   │ Sessions     │   │ attachés     │
│              │   │ Jobs         │   │              │
└──────────────┘   └──────────────┘   └──────────────┘
```

### 2.2 Structure du projet Laravel

| Dossier | Contenu |
|---------|---------|
| `app/Http/Controllers/` | Contrôleurs API : RiskController, TaskController, AuthController… |
| `app/Models/` | Modèles Eloquent : Risk, Theme, Category, Control, Task, User… |
| `app/Policies/` | Autorisations : RiskPolicy (accès par département/thème) |
| `app/Http/Requests/` | Validation des entrées : StoreRiskRequest, UpdateTaskRequest… |
| `app/Http/Resources/` | Transformation JSON : RiskResource, TaskCollection… |
| `app/Services/` | Logique métier : RiskScoringService (calcul RAG), ReportService… |
| `app/Jobs/` | Tâches asynchrones : SendReminderEmail, CalculateDashboard… |
| `app/Notifications/` | Templates : TaskDueNotification, ContractExpiringNotification… |

### 2.3 Modèle de données

#### Hiérarchie principale des risques

```
THEME                    CATEGORY                 RISK
Domaine de risque        Sous-domaine             Risque unitaire
ex: Cybersécurité        ex: Accès non autorisé   ex: Vol de credentials
       1 ──────────── N        1 ──────────── N
```

#### Entités liées à un risque

| CONTROL | ACTION | ATTACHMENT |
|---------|--------|------------|
| Mesure de mitigation | Plan de remédiation | Pièces jointes |

#### Autres entités

| Task | Supplier | Contract | Team |
|------|----------|----------|------|

---

## 3. Fonctionnalités Laravel utilisées

Chaque besoin métier est couvert par une fonctionnalité native de Laravel :

| Besoin | Composant Laravel | Détail |
|--------|-------------------|--------|
| Authentification API | **Sanctum** | Tokens API, authentification SPA, révocation |
| Double authentification | **Fortify** | TOTP intégré (Google Authenticator) |
| Permissions granulaires | **Policies + Gates** | Autorisation par ressource et par condition métier |
| Relations complexes | **Eloquent ORM** | hasMany, belongsToMany, eager loading, scopes |
| Envoi d'emails | **Notifications** | Multi-canal (email, SMS, Slack), templates Blade |
| Tâches asynchrones | **Queues + Jobs** | Envoi différé, retry automatique, driver Redis |
| Rappels automatiques | **Task Scheduling** | Cron en PHP, rappels quotidiens, alertes |
| Upload fichiers | **Storage** | Abstraction local/S3, même code partout |
| Validation données | **Form Requests** | Validation déclarative, messages personnalisés |
| Cache | **Cache facade** | `Cache::remember()`, invalidation, tags |
| Rate limiting | **Throttle middleware** | Protection brute force, limites configurables |
| Audit logs | **spatie/activitylog** | Qui a fait quoi, quand, sur quelle ressource |
| Tests | **Pest / PHPUnit** | Tests unitaires, feature tests, factories |

---

## 4. Sécurité

### 4.1 Authentification et autorisation

| Mesure | Implémentation |
|--------|----------------|
| Tokens API | Sanctum avec expiration configurable et révocation possible |
| 2FA | TOTP via Fortify, compatible Google Authenticator |
| Permissions | Policies par ressource : vérification département et thème |
| Sessions | Stockage Redis, révocation instantanée possible |

### 4.2 Protection des données

| Mesure | Implémentation |
|--------|----------------|
| HTTPS | TLS 1.3 obligatoire via Nginx, certificat Let's Encrypt |
| CSRF | Protection native Laravel sur toutes les requêtes POST/PUT/DELETE |
| Rate limiting | 60 req/min par défaut, 5 tentatives login/min |
| Secrets | Fichier `.env` hors Git, `APP_KEY` chiffré |
| Headers sécurité | HSTS, CSP, X-Frame-Options, X-Content-Type-Options via Nginx |
| SQL Injection | Protection native Eloquent (requêtes préparées) |
| XSS | Échappement automatique Blade + sanitization React |

---

## 5. Infrastructure

### 5.1 Environnements

| Environnement | Usage |
|---------------|-------|
| **Local** | Développement avec Laravel Sail (Docker) |
| **Staging** | Tests et validation avant mise en production |
| **Production** | Environnement live, accès utilisateurs |

### 5.2 Déploiement

- **Conteneurisation :** Docker Compose (Nginx + PHP-FPM + PostgreSQL + Redis)
- **CI/CD :** GitHub Actions — tests automatiques + déploiement sur push
- **Migrations :** Exécution automatique via `php artisan migrate`

### 5.3 Backup

- **Base de données :** `pg_dump` quotidien + WAL archiving (point-in-time recovery)
- **Fichiers :** Sync S3/MinIO vers stockage externe
- **Automatisation :** Package `spatie/laravel-backup`

### 5.4 Monitoring

- **Logs :** Laravel Log centralisé, rotation quotidienne
- **Queues :** Laravel Horizon pour supervision des jobs Redis
- **Erreurs :** Intégration Sentry possible pour alertes temps réel
- **Health check :** Endpoint `/health` pour monitoring externe

---

**Validations**

Tech Lead : _________________________________________

Chef de projet : _________________________________________
