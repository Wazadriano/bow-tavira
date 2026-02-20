# PLAN V1 - BOW Book of Work
# Date: 2026-02-20
# Orchestre par: Hermes (Dispatcher BYAN)
# Pipeline: BOW-PM > BOW-ARCH > BOW-BACK > BOW-FRONT > BOW-QA > BOW-SEC > BOW-INFRA > BOW-GH

## Etat du projet (audit 2026-02-20)

| Element | Etat | Detail |
|---------|------|--------|
| Backend API | 435 tests, 46 migrations, 5 Policies | Phases 1-6 + B-D mergees, Sprint 1 DONE |
| Frontend | 50+ pages, Vite 5.4 + React Router 6.22 | Migration Next.js terminee, 2 smoke tests |
| Permissions | Tables + Models + Policies existent | AUCUN filtrage dans controllers |
| SMTP | Non configure | bow@ohadja.com (o2switch, SMTP port 465, SSL) |
| Notifications | Backend existe (4 notifications) | Timings incorrects (J-30/J-7/J-3 a retirer) |
| Dashboard Public | Non implemente | Vitrine KPI avec token a creer |
| Tests Frontend | 2 smoke tests seulement | Vitest + Testing Library configures |
| Docker | Fonctionnel | bow_api container actif |
| CI | Pint + Larastan + Pest + TSC | Tout passe |
| Stores Zustand | 12 stores (3420 LOC) | auth, workitems, risks, suppliers, governance, teams, users, settings, ui, notifications, import |

## Configuration SMTP

```
MAIL_MAILER=smtp
MAIL_HOST=ohadja.com
MAIL_PORT=465
MAIL_USERNAME=bow@ohadja.com
MAIL_PASSWORD=[a configurer]
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=bow@ohadja.com
MAIL_FROM_NAME="Book of Work"
```

Hebergeur: o2switch
IMAP: port 993 | POP3: port 995 | SMTP: port 465

---

## SPRINT 1 : SMTP + Notifications (BLOQUANT)

**Objectif**: Emails fonctionnels avec timings corrects

| # | Tache | Agent | Statut |
|---|-------|-------|--------|
| 1.1 | Configurer SMTP bow@ohadja.com dans .env | BOW-INFRA | DONE |
| 1.2 | Corriger SendTaskRemindersCommand: retirer J-30/J-7/J-3, ajouter J-14 et Jour J | BOW-BACK | DONE |
| 1.3 | Implementer logique J+1: notifier chef equipe + Ranjit si tache non validee | BOW-BACK | DONE |
| 1.4 | Implementer rapport hebdomadaire CEO (taches <=14j restants, non terminees) | BOW-BACK | DONE |
| 1.5 | Corriger logique self-assign (pas de notif si user cree sa propre tache) | BOW-BACK | DONE |
| 1.6 | Tester envoi email reel via SMTP o2switch | BOW-QA | TODO (besoin mot de passe) |
| 1.7 | PR + CI | BOW-GH | TODO |

**Regles de notification validees**:
- Attribution: si manager assigne quelqu'un (pas si self-assign)
- J-14: 14 jours avant la deadline
- Jour J: le jour meme de la deadline
- J+1: lendemain si pas validee -> chef equipe + Ranjit
- Weekly: rapport CEO consolide des taches <=14j restants

---

## SPRINT 2 : Systeme de Permissions (CRITIQUE SECURITE)

**Objectif**: Isolation des donnees par departement/theme/item

| # | Tache | Agent | Statut |
|---|-------|-------|--------|
| 2.1 | Spec: definir les regles de filtrage pour chaque controller | BOW-PM | DONE (audit montre que CRUD deja filtre) |
| 2.2 | ADR: strategie implementation (Middleware vs Policy vs Scope) | BOW-ARCH | DONE (Policies+Scopes en place) |
| 2.3 | Creer PermissionService centralise | BOW-BACK | SKIP (filtrage inline dans controllers suffisant) |
| 2.4 | Filtrage WorkItemController (scope par dept) | BOW-BACK | DEJA FAIT (existait) |
| 2.5 | Filtrage GovernanceController (dept + GovernanceItemAccess) | BOW-BACK | DEJA FAIT (existait) |
| 2.6 | Filtrage SupplierController::dashboard() (SupplierAccess) | BOW-BACK | DONE |
| 2.7 | Filtrage RiskController::dashboard() (RiskThemePermission) | BOW-BACK | DONE |
| 2.8 | CRUD endpoints UserDepartmentPermission + RiskThemePermission | BOW-BACK | DEJA FAIT (UserPermissionController + RiskThemeController) |
| 2.9 | Filtrage DashboardController stats+alerts (risk/supplier scope) | BOW-BACK | DONE |
| 2.10 | Page admin gestion permissions (front) | BOW-FRONT | TODO (Sprint 5) |
| 2.11 | Tests permissions (matrice users x modules x actions) | BOW-QA | DONE (44 tests) |
| 2.12 | Audit securite OWASP des permissions | BOW-SEC | DONE (tous dashboards filtres) |
| 2.13 | PR + CI | BOW-GH | DONE (444 tests, 0 fails) |

**Gap identifie (POC vs Refonte)**:
- POC: fonctions can_view_department(), can_edit_task_status() dans auth.py
- Refonte: User model a canViewDepartment()/canEditInDepartment() mais PAS appeles dans controllers
- 5 Policies existent (RiskPolicy, GovernanceItemPolicy, UserPolicy, SupplierPolicy, WorkItemPolicy) mais pas de filtrage scope

---

## SPRINT 3 : Dashboard Vitrine Public

**Objectif**: Dashboard read-only pour clients/board via token

| # | Tache | Agent | Statut |
|---|-------|-------|--------|
| 3.1 | Spec: KPIs a afficher (WI, Gov, Risk, Supplier) | BOW-PM | DONE |
| 3.2 | Backend: endpoint /api/public/dashboard + PublicDashboardTokenController CRUD | BOW-BACK | DONE |
| 3.3 | Backend: VerifyPublicDashboardToken middleware (bearer/query) | BOW-BACK | DONE |
| 3.4 | Backend: EnsureUserIsAdmin middleware pour token CRUD | BOW-BACK | DONE |
| 3.5 | Frontend: page /public/dashboard (read-only, KPIs tous modules) | BOW-FRONT | TODO |
| 3.6 | Frontend: export PNG graphiques (html2canvas ou dom-to-image) | BOW-FRONT | TODO |
| 3.7 | Tests (13 tests: access, auth, CRUD, expiry) | BOW-QA | DONE |

---

## SPRINT 4 : Fix Notification Timings + Light Mode

| # | Tache | Agent | Statut |
|---|-------|-------|--------|
| 4.1 | Fix RG-BOW-014 dans project-context-bow.yaml (J-14/Jour J/J+1/Weekly) | BOW-PM | DONE |
| 4.2 | Fix light mode: heatmap text contrast (yellow cells text-gray-900) | BOW-FRONT | DONE |
| 4.3 | Audit light mode: CSS variables, sidebar, header, layout OK | BOW-FRONT | DONE |
| 4.4 | TSC clean, Pint clean, Larastan 0 erreurs | BOW-GH | DONE |

---

## SPRINT 5 : Tests Frontend + Qualite

**Objectif**: Couverture frontend >60%

| # | Tache | Agent | Statut |
|---|-------|-------|--------|
| 5.1 | Tests Zod validations (54 tests: login, user, workitem, gov, supplier, risk, team, settings, search) | BOW-QA | DONE |
| 5.2 | Tests auth store (9 tests: login, logout, fetchUser, error handling) | BOW-QA | DONE |
| 5.3 | Tests UI store (16 tests: sidebar, modal, confirm, theme, notifications) | BOW-QA | DONE |
| 5.4 | Tests API client + smoke (12 tests) | BOW-QA | DEJA FAIT |
| 5.5 | Total: 91 tests frontend, TSC clean | BOW-GH | DONE |

---

## SPRINT 6 : Pre-Production + Deploiement

| # | Tache | Agent | Statut |
|---|-------|-------|--------|
| 6.1 | Setup staging OVH (domaine, SSL, DNS) | BOW-INFRA | TODO (besoin acces infra) |
| 6.2 | Configuration Sentry DSN reel | BOW-INFRA | TODO (besoin DSN) |
| 6.3 | Seed data realiste (20 users, permissions, WI, gov, suppliers, risks) | BOW-BACK | DEJA FAIT (RealUsersSeeder+ComprehensiveDataSeeder) |
| 6.4 | Security headers (X-Content-Type-Options, X-Frame-Options, XSS, Referrer-Policy, Permissions-Policy) | BOW-SEC | DONE |
| 6.5 | Rate limiting auth (5/min login, 5/min 2FA, throttle:api) | BOW-SEC | DEJA FAIT |
| 6.6 | CORS (bow.ohadja.com + localhost:3000, credentials) | BOW-SEC | DEJA FAIT |
| 6.7 | Tests smoke en staging | BOW-QA | TODO (besoin staging) |
| 6.8 | Go/No-Go V1 | BOW-PM | TODO |

---

## Ordre de priorite

1. Sprint 1 (SMTP) - BLOQUANT pour tout envoi d'email
2. Sprint 2 (Permissions) - CRITIQUE securite, fuite de donnees actuelle
3. Sprint 4 (Fix timings) - Quick win, correction de logique existante
4. Sprint 3 (Dashboard Public) - Feature demandee par le client
5. Sprint 5 (Tests Frontend) - Qualite avant production
6. Sprint 6 (Deploy) - Mise en production

## Diagramme

Excalidraw: https://excalidraw.com/#json=1G6fOcmRl1-pkS96SrlMB,5mHQh2LKdb0kPMAPHabpWw
