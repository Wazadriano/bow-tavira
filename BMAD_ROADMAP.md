# BMAD Method - Refonte TAVIRA_BOW
## Business Model Architecture Design

> **Derniere mise a jour:** 2026-01-27
> **Sprint actuel:** Sprint 7 (Frontend)
> **Status:** EN COURS

---

## Quick Reference - Agents & Contexts

```
# Avant toute intervention:
@context contraintes    # OBLIGATOIRE - Pieges connus

# Selon la tache:
@agent backend          # Si modification Laravel/API
@agent frontend         # Si modification React/Next.js
@agent analyst          # Si nouvelle feature
@agent qa               # Si bug ou test

# Contexts additionnels:
@context auth           # Si authentification
@context database       # Si modifications DB
@context docker         # Si deploiement
@context erreurs        # Si bloque par erreur
```

**Documentation complete:** [.bmad/AGENTS-MANIFEST.md](.bmad/AGENTS-MANIFEST.md)

---

## Vue d'ensemble

| Element | Valeur |
|---------|--------|
| **Projet** | TAVIRA BOW Refonte |
| **Stack** | React + Laravel + PostgreSQL + Redis |
| **Duree estimee** | 14 semaines (3.5 mois) |
| **Sprints** | 7 sprints de 2 semaines |
| **Deploiement** | Local/On-premise |

---

## PHASE 1: BUSINESS ANALYSIS

### 1.1 Parties prenantes
- **Product Owner**: Equipe Tavira
- **Dev Team**: Claude Code
- **End Users**: Gestionnaires de risques, chefs de projet, administrateurs

### 1.2 Besoins metier identifies

| Module | Priorite | Complexite | Valeur Business |
|--------|----------|------------|-----------------|
| Auth & Users | P0 | Medium | Critique |
| Work Items (Tasks) | P0 | High | Haute |
| Dashboard | P0 | Medium | Haute |
| Governance | P1 | Medium | Moyenne |
| Suppliers | P1 | High | Moyenne |
| Risk Management | P1 | Very High | Haute |
| Import/Export Excel | P1 | High | Haute |
| Teams | P2 | Low | Basse |
| Settings | P2 | Low | Basse |

### 1.3 Contraintes techniques
- Migration donnees SQLite existantes obligatoire
- Conservation du design Shadcn/ui theme Zinc
- Deploiement on-premise (pas de cloud externe)
- 2FA differe a plus tard

---

## PHASE 2: MODEL DESIGN

### 2.1 Architecture globale

```
┌─────────────────────────────────────────────────────────────┐
│                     FRONTEND (Next.js 15)                    │
│           React 18 + TypeScript + Shadcn/ui + Zustand        │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTPS / API REST
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                     NGINX REVERSE PROXY                      │
│                  SSL + Rate Limiting + CORS                  │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────┴──────────────────────────────────┐
│                    BACKEND (Laravel 11)                      │
│          PHP 8.3 + Sanctum + Eloquent + Queue                │
└──────────────────────────┬──────────────────────────────────┘
                           │
         ┌─────────────────┼─────────────────┐
         ▼                 ▼                 ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ PostgreSQL  │    │    Redis    │    │  Filesystem │
│     16      │    │      7      │    │   Storage   │
└─────────────┘    └─────────────┘    └─────────────┘
   Database          Cache/Queue        Attachments
```

### 2.2 Modele de donnees (31 tables)

#### Domaine Core
- `users` - Utilisateurs systeme
- `user_department_permissions` - Permissions granulaires
- `work_items` - Taches principales
- `task_dependencies` - Relations entre taches

#### Domaine Collaboration
- `teams` - Equipes
- `team_members` - Membres d'equipe
- `task_assignments` - Assignations
- `task_milestones` - Jalons
- `milestone_assignments` - Assignations jalons

#### Domaine Governance
- `governance_items` - Items de gouvernance
- `governance_item_access` - Acces
- `governance_milestones` - Jalons
- `governance_attachments` - Fichiers

#### Domaine Suppliers
- `sage_categories` - Categories comptables
- `suppliers` - Fournisseurs
- `supplier_entities` - Entites
- `supplier_access` - Acces
- `supplier_contracts` - Contrats
- `contract_entities` - Entites contrats
- `supplier_invoices` - Factures
- `supplier_attachments` - Fichiers
- `supplier_contract_attachments` - Fichiers contrats

#### Domaine Settings
- `setting_lists` - Listes dynamiques
- `system_settings` - Parametres systeme

#### Domaine Risk Management
- `risk_themes` - Themes L1
- `risk_categories` - Categories L2
- `risks` - Risques L3
- `risk_work_items` - Liaison risques-taches
- `risk_governance_items` - Liaison risques-gouvernance
- `control_library` - Bibliotheque controles
- `risk_controls` - Controles appliques
- `risk_actions` - Actions remediation
- `risk_attachments` - Fichiers
- `risk_theme_permissions` - Permissions themes

#### Audit
- `activity_log` - Journal d'audit

---

## PHASE 3: ARCHITECTURE DOCUMENTATION

### 3.1 API Endpoints par domaine

| Domaine | Endpoints | Controllers |
|---------|-----------|-------------|
| Auth | 5 | AuthController |
| Users | 11 | UserController, UserPermissionController |
| WorkItems | 17 | WorkItemController, WorkItemFileController |
| Teams | 8 | TeamController, TeamMemberController |
| Milestones | 6 | MilestoneController |
| Governance | 14 | GovernanceController, GovernanceFileController |
| Suppliers | 30+ | SupplierController, ContractController, InvoiceController |
| Settings | 8 | SettingListController, SystemSettingController |
| Risks | 40+ | RiskController, ControlController, ActionController |
| Dashboard | 6 | DashboardController |
| Import/Export | 5 | ImportExportController |
| **TOTAL** | **150+** | **25+ controllers** |

### 3.2 Services metier

| Service | Responsabilite |
|---------|----------------|
| RAGCalculationService | Calcul statut RAG avec cache Redis |
| RiskScoringService | Calcul scores risques (impact x proba) |
| ImportNormalizationService | Normalisation CSV/Excel |
| ExportService | Generation fichiers Excel |
| DashboardService | Agregation statistiques |

### 3.3 Jobs asynchrones (Redis Queue)

| Job | Declencheur |
|-----|-------------|
| ProcessImportFile | Import CSV/Excel confirme |
| SendTaskReminderNotification | Scheduler quotidien |
| SendContractAlertNotification | Scheduler quotidien |
| RecalculateRAGCache | Update WorkItem |

---

## PHASE 4: DEVELOPMENT SPRINTS

---

### SPRINT 1: Infrastructure & Setup (Semaine 1-2)

**Objectif**: Environnement de developpement fonctionnel

#### Backlog Sprint 1

| # | User Story | Points | Priorite |
|---|------------|--------|----------|
| 1.1 | Setup Docker Compose (PostgreSQL, Redis, PHP) | 3 | P0 |
| 1.2 | Creer projet Laravel 11 | 2 | P0 |
| 1.3 | Configurer Sanctum pour API auth | 3 | P0 |
| 1.4 | Creer migrations Core (users, work_items) | 5 | P0 |
| 1.5 | Creer migrations Phase 1-2 (teams, governance) | 5 | P0 |
| 1.6 | Creer migrations Phase 3-4 (suppliers, settings) | 5 | P0 |
| 1.7 | Creer migrations Phase 5 (risks) | 5 | P0 |
| 1.8 | Creer tous les enums PHP | 3 | P0 |
| 1.9 | Script migration SQLite -> PostgreSQL | 5 | P1 |
| 1.10 | Seeder utilisateur admin | 2 | P0 |

**Total points**: 38
**Velocity estimee**: 40 points

#### Definition of Done Sprint 1
- [ ] Docker Compose demarre sans erreur
- [ ] `php artisan migrate` execute toutes les migrations
- [ ] Base PostgreSQL contient les 31 tables
- [ ] Utilisateur admin cree par seeder

#### Livrables Sprint 1
- `docker-compose.yml`
- `Dockerfile`
- 34 fichiers de migration
- 20 fichiers enum
- `MigrateSqliteData.php`
- `DatabaseSeeder.php`

---

### SPRINT 2: Backend Core - Auth & Users (Semaine 3-4)

**Objectif**: Authentification et gestion utilisateurs fonctionnels

#### Backlog Sprint 2

| # | User Story | Points | Priorite |
|---|------------|--------|----------|
| 2.1 | Model User avec relations | 3 | P0 |
| 2.2 | Model UserDepartmentPermission | 2 | P0 |
| 2.3 | AuthController (login, logout, me, refresh) | 5 | P0 |
| 2.4 | UserController CRUD | 5 | P0 |
| 2.5 | UserPermissionController | 3 | P0 |
| 2.6 | UserPolicy (autorisations) | 3 | P0 |
| 2.7 | Form Requests validation | 3 | P0 |
| 2.8 | API Resources (UserResource) | 2 | P0 |
| 2.9 | Middleware CheckDepartmentPermission | 3 | P1 |
| 2.10 | Tests Feature Auth | 5 | P1 |
| 2.11 | Tests Feature Users | 5 | P1 |

**Total points**: 39
**Velocity estimee**: 40 points

#### Definition of Done Sprint 2
- [ ] Login retourne JWT valide
- [ ] CRUD users fonctionnel (admin only)
- [ ] Permissions departement appliquees
- [ ] Tests passent a 80%+

#### Livrables Sprint 2
- `app/Models/User.php`
- `app/Models/UserDepartmentPermission.php`
- `app/Http/Controllers/Api/Auth/AuthController.php`
- `app/Http/Controllers/Api/UserController.php`
- `app/Policies/UserPolicy.php`
- Tests dans `tests/Feature/Auth/`

---

### SPRINT 3: Backend Core - WorkItems (Semaine 5-6)

**Objectif**: Gestion complete des taches

#### Backlog Sprint 3

| # | User Story | Points | Priorite |
|---|------------|--------|----------|
| 3.1 | Model WorkItem avec RAG calcule | 5 | P0 |
| 3.2 | Model TaskDependency | 2 | P0 |
| 3.3 | Model Team, TeamMember | 3 | P0 |
| 3.4 | Model TaskAssignment | 2 | P0 |
| 3.5 | Model TaskMilestone, MilestoneAssignment | 3 | P0 |
| 3.6 | WorkItemController CRUD | 5 | P0 |
| 3.7 | WorkItemFileController (upload/download) | 5 | P0 |
| 3.8 | TeamController CRUD | 3 | P1 |
| 3.9 | MilestoneController | 3 | P1 |
| 3.10 | WorkItemPolicy | 3 | P0 |
| 3.11 | RAGCalculationService avec cache Redis | 5 | P0 |
| 3.12 | Tests Feature WorkItems | 5 | P1 |

**Total points**: 44
**Velocity estimee**: 45 points

#### Definition of Done Sprint 3
- [ ] CRUD WorkItems fonctionnel
- [ ] Upload/download fichiers ok
- [ ] RAG calcule automatiquement
- [ ] Cache Redis invalide correctement
- [ ] Dependencies entre taches gerees

#### Livrables Sprint 3
- `app/Models/WorkItem.php`
- `app/Models/Task*.php` (5 modeles)
- `app/Services/RAGCalculationService.php`
- `app/Http/Controllers/Api/WorkItemController.php`
- Tests dans `tests/Feature/WorkItem/`

---

### SPRINT 4: Backend Modules - Governance & Suppliers (Semaine 7-8)

**Objectif**: Modules Governance et Suppliers complets

#### Backlog Sprint 4

| # | User Story | Points | Priorite |
|---|------------|--------|----------|
| 4.1 | Models Governance (4 modeles) | 5 | P0 |
| 4.2 | GovernanceController CRUD | 5 | P0 |
| 4.3 | GovernanceFileController | 3 | P0 |
| 4.4 | GovernanceMilestoneController | 3 | P0 |
| 4.5 | GovernancePolicy | 2 | P0 |
| 4.6 | Models Suppliers (8 modeles) | 8 | P0 |
| 4.7 | SupplierController CRUD | 5 | P0 |
| 4.8 | SupplierContractController | 5 | P0 |
| 4.9 | SupplierInvoiceController + bulk import | 5 | P0 |
| 4.10 | SageCategoryController | 2 | P1 |
| 4.11 | SupplierPolicy | 2 | P0 |
| 4.12 | Tests | 5 | P1 |

**Total points**: 50
**Velocity estimee**: 50 points

#### Definition of Done Sprint 4
- [ ] CRUD Governance complet
- [ ] CRUD Suppliers + contracts + invoices
- [ ] Bulk import factures CSV fonctionne
- [ ] Alertes expiration contrats

#### Livrables Sprint 4
- `app/Models/Governance*.php` (4 modeles)
- `app/Models/Supplier*.php` (8 modeles)
- Controllers correspondants
- Policies

---

### SPRINT 5: Backend Modules - Risk Management (Semaine 9-10)

**Objectif**: Module Risk Management complet

#### Backlog Sprint 5

| # | User Story | Points | Priorite |
|---|------------|--------|----------|
| 5.1 | Models Risk (8 modeles) | 8 | P0 |
| 5.2 | RiskThemeController | 3 | P0 |
| 5.3 | RiskCategoryController | 3 | P0 |
| 5.4 | RiskController CRUD | 5 | P0 |
| 5.5 | ControlLibraryController | 3 | P0 |
| 5.6 | RiskControlController | 3 | P0 |
| 5.7 | RiskActionController | 3 | P0 |
| 5.8 | RiskFileController | 3 | P0 |
| 5.9 | RiskScoringService | 5 | P0 |
| 5.10 | RiskPolicy + RiskThemePolicy | 3 | P0 |
| 5.11 | Dashboard risques + heatmap | 5 | P1 |
| 5.12 | Tests | 5 | P1 |

**Total points**: 49
**Velocity estimee**: 50 points

#### Definition of Done Sprint 5
- [ ] Hierarchie Theme > Category > Risk fonctionnelle
- [ ] Scoring (impact x proba) calcule
- [ ] Controles et actions CRUD ok
- [ ] Heatmap 5x5 genere

#### Livrables Sprint 5
- `app/Models/Risk*.php` (8 modeles)
- `app/Services/RiskScoringService.php`
- Controllers Risk (7 controllers)
- Policies

---

### SPRINT 6: Backend - Dashboard, Import/Export & Settings (Semaine 11-12)

**Objectif**: Fonctionnalites transverses

#### Backlog Sprint 6

| # | User Story | Points | Priorite |
|---|------------|--------|----------|
| 6.1 | DashboardController (stats, alerts) | 5 | P0 |
| 6.2 | DashboardService avec cache | 3 | P0 |
| 6.3 | SearchController | 3 | P1 |
| 6.4 | ImportNormalizationService | 8 | P0 |
| 6.5 | ImportExportController (preview, confirm) | 5 | P0 |
| 6.6 | ExportService (Excel) | 5 | P0 |
| 6.7 | Templates Excel (4 types) | 3 | P1 |
| 6.8 | ProcessImportFile Job | 3 | P0 |
| 6.9 | SettingListController | 3 | P1 |
| 6.10 | SystemSettingController | 2 | P1 |
| 6.11 | Notifications (TaskReminder, ContractAlert) | 5 | P1 |
| 6.12 | Task Scheduler (rappels quotidiens) | 3 | P1 |

**Total points**: 48
**Velocity estimee**: 50 points

#### Definition of Done Sprint 6
- [ ] Dashboard stats fonctionnel
- [ ] Import CSV avec preview et validation
- [ ] Export Excel fonctionne
- [ ] Notifications email configurees
- [ ] Settings dynamiques ok

#### Livrables Sprint 6
- `app/Services/ImportNormalizationService.php`
- `app/Services/ExportService.php`
- `app/Http/Controllers/Api/DashboardController.php`
- `app/Http/Controllers/Api/ImportExportController.php`
- `app/Jobs/ProcessImportFile.php`
- `app/Notifications/*.php`

---

### SPRINT 7: Frontend React Complete (Semaine 13-14)

**Objectif**: Interface utilisateur complete

#### Backlog Sprint 7

| # | User Story | Points | Priorite |
|---|------------|--------|----------|
| 7.1 | Setup Next.js 15 + TypeScript | 3 | P0 |
| 7.2 | Configurer Shadcn/ui theme Zinc | 3 | P0 |
| 7.3 | Layout (Sidebar, Header, navigation) | 5 | P0 |
| 7.4 | Pages Auth (login, logout) | 3 | P0 |
| 7.5 | Module Tasks (liste, detail, form) | 8 | P0 |
| 7.6 | Composant RAG Badge | 2 | P0 |
| 7.7 | Composant Data Table | 5 | P0 |
| 7.8 | Module Dashboard + charts | 5 | P0 |
| 7.9 | Module Governance | 5 | P1 |
| 7.10 | Module Suppliers | 5 | P1 |
| 7.11 | Module Risks + Heatmap | 8 | P1 |
| 7.12 | Module Import/Export | 5 | P1 |
| 7.13 | Module Settings + Users admin | 5 | P1 |
| 7.14 | Calendriers | 3 | P2 |
| 7.15 | Tests E2E Playwright | 5 | P1 |

**Total points**: 70
**Note**: Sprint plus charge, peut etre divise en 2 sprints si necessaire

#### Definition of Done Sprint 7
- [ ] Login/logout fonctionnel
- [ ] Tous les modules accessibles
- [ ] Design Shadcn identique a l'existant
- [ ] Mode sombre fonctionne
- [ ] Import/Export fonctionnel depuis UI
- [ ] Tests E2E passent

#### Livrables Sprint 7
- Projet `tavira-bow-frontend/` complet
- Composants Shadcn/ui configures
- Pages pour tous les modules
- API clients TypeScript
- Tests Playwright

---

## ROADMAP VISUELLE

```
SEMAINE    1    2    3    4    5    6    7    8    9   10   11   12   13   14
           |----|----|----|----|----|----|----|----|----|----|----|----|----|

SPRINT 1   ████████████
           Infrastructure & Setup

SPRINT 2             ████████████
                     Auth & Users

SPRINT 3                       ████████████
                               WorkItems

SPRINT 4                                 ████████████
                                         Governance & Suppliers

SPRINT 5                                           ████████████
                                                   Risk Management

SPRINT 6                                                     ████████████
                                                             Dashboard, Import/Export

SPRINT 7                                                               ████████████
                                                                       Frontend React

MILESTONES:
           ▼           ▼           ▼           ▼           ▼           ▼
           DB Ready    Auth OK     Tasks OK    Modules OK  API Complete Frontend OK
```

---

## METRIQUES & KPIs

### Suivi du projet

| Metrique | Cible | Mesure |
|----------|-------|--------|
| Velocity | 40-50 pts/sprint | Points termines |
| Couverture tests | > 70% | PHPUnit + Pest |
| Bugs critiques | 0 | A chaque sprint |
| Code review | 100% | PR obligatoires |
| Documentation API | 100% | Swagger auto |

### Definition of Done globale
- [ ] Code review approuve
- [ ] Tests passent
- [ ] Documentation a jour
- [ ] Pas de regression
- [ ] Deploye sur environnement de test

---

## RISQUES & MITIGATIONS

| Risque | Probabilite | Impact | Mitigation |
|--------|-------------|--------|------------|
| Migration donnees echoue | Medium | High | Tests migration sur copie |
| Performance Redis | Low | Medium | Monitoring des lors Sprint 3 |
| Complexite Risk Management | High | Medium | Sprint dedie, POC d'abord |
| Design Shadcn different | Medium | Low | Capture ecrans existants |
| Depassement delais | Medium | Medium | Sprints flexibles, priorisation |

---

## STATUS ACTUEL - SPRINT 7

### Completed (Sprints 1-6)
- [x] Infrastructure Docker complete
- [x] 31 Models Eloquent
- [x] 34 Migrations PostgreSQL
- [x] 20 Enums PHP
- [x] 15+ Controllers API
- [x] Services (RAG, Risk Scoring, Import)
- [x] Policies et Form Requests
- [x] Deploiement Docker/Traefik

### Sprint 7 - En cours
- [x] Setup Next.js 15 + TypeScript
- [x] Shadcn/ui theme Zinc
- [x] Page Login fonctionnelle
- [x] Auth store Zustand
- [ ] Dashboard page
- [ ] Work Items list/detail
- [ ] Risk Register + Heatmap
- [ ] Import/Export UI

### Sprint 8 - A venir
- [ ] Tests E2E Playwright
- [ ] Documentation utilisateur
- [ ] Performance optimization
- [ ] Polish final

---

## ANNEXES

### A. Commandes utiles

```bash
# Demarrer environnement
docker-compose up -d

# Migrations
docker-compose exec api php artisan migrate

# Migration SQLite
docker-compose exec api php artisan migrate:sqlite /path/to/book_of_work.db

# Tests
docker-compose exec api php artisan test

# Cache clear
docker-compose exec api php artisan cache:clear
docker-compose exec api php artisan config:clear
```

### B. Fichiers de reference

| Fichier source | Usage |
|----------------|-------|
| `/home/ohadja/TAVIRA_BOW/models.py` | Structure modeles |
| `/home/ohadja/TAVIRA_BOW/main.py` | Endpoints API |
| `/home/ohadja/TAVIRA_BOW/schemas.py` | Validation |
| `/home/ohadja/TAVIRA_BOW/static/index.html` | Design UI |
| `/home/ohadja/TAVIRA_BOW/data/book_of_work.db` | Donnees a migrer |

---

*Document genere le: 2026-01-23*
*Version: 1.0*
*Methode: BMAD (Business Model Architecture Design)*
