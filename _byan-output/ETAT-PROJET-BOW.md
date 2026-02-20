# ETAT DU PROJET BOW - Reference Unique

Date : 2026-02-11
Verifie par : Audit croise CDC Fonctionnel + CDC Technique vs code reel (backend + frontend)

Voir aussi :
- **PLAN-QA-ACTIONS.md** : plan QA complet (11 taches, agents assignes, ordonnancement)
- **PLAN-ACTION.md** : taches fonctionnelles T1-T6 (toutes completees)
- **PLAN-CONFIG-PROD.md** : checklist configuration production
- **README.md** : index de navigation du dossier _bmad-output/
- **archive/** : anciens plans et audits (historique)

---

## 1. RESUME EXECUTIF

| Metrique | Valeur |
|----------|--------|
| CDC Fonctionnel | 97% (reste RG-BOW-008 dedup, responsive partiel) |
| CDC Technique | ~94% (couverture tests incomplete, file size validation) |
| Tests Pest | 218 passed, 8 skipped, 0 echecs |
| Pint | PASS |
| Larastan | PASS (0 erreurs) |
| TSC | PASS (0 erreurs) |
| Backend | 35 controllers, 43 migrations, 15 Form Requests, 370+ lignes routes |
| Frontend | 48 pages, 56 composants, 12 stores Zustand |
| PRs mergees | #8 (securite), #9 (phases 2-6), #10 (phases B-D) |

---

## 2. TOUT CE QUI EST FAIT

### 2.1 Corrections et parite POC (PR initiales)

- Erreurs API governance (403, 422, params) - FAIT
- Milestones parite POC (calendrier, formulaires, affichage conditionnel) - FAIT
- URLs stores frontend (8 corrections PATCH/PUT/nested) - FAIT
- Routes backend manquantes (invoices, contracts, risks/actions, governance, suppliers, dependencies) - FAIT
- Dashboard URLs avec fallback resilient - FAIT
- Mock data supprimee de toutes les pages - FAIT
- Encodage MacRoman (44 artefacts + sanitisation import + 7 tests) - FAIT

### 2.2 Phase 1 : Securite (PR #8)

- Nginx HSTS/CSP/Permissions-Policy headers - FAIT
- 2FA TOTP complet (TwoFactorController, 4 routes, 10 tests Pest) - FAIT
- Backup automatise (spatie/laravel-backup, scheduler quotidien + hebdo) - FAIT

### 2.3 Phase 2 : Notifications (PR #9)

- Infrastructure notifications (migration, config/mail.php) - FAIT
- TaskDueReminderNotification (J-7, J-3, J-1) - FAIT
- ContractExpiringNotification (J-90, J-30, J-7) - FAIT
- RiskThresholdBreachedNotification - FAIT
- TaskAssignedNotification - FAIT
- DailySummaryNotification + command + scheduler 07:00 - FAIT
- NotificationController + UI in-app (bell, badge, page) - FAIT
- ImportCompletedNotification (bonus) - FAIT

### 2.4 Phase 3 : Vues avancees (PR #9)

- Kanban board drag & drop (HTML5 natif, 4 colonnes) - FAIT
- Gantt chart timeline (implementation custom) - FAIT
- Workload stacked bar charts (Recharts) - FAIT

### 2.5 Phase 4 : Infrastructure + Audit (PR #9 + #10)

- LogsActivity sur 4 models (spatie/activitylog) - FAIT
- AuditController (index, forSubject, stats) + page frontend - FAIT
- S3/MinIO storage (4 controllers vers Storage::disk() configurable) - FAIT
- Laravel Horizon v5.44 (3 supervisors, auth admin) - FAIT
- Sentry backend v4.20 + frontend @sentry/nextjs - FAIT

### 2.6 Phase 5 : Regles metier (PR #9)

- CurrencyConversionService (GBP, multi-devises) - FAIT
- ReportController (4 endpoints PDF) + 5 Blade templates - FAIT
- barryvdh/laravel-dompdf v3.1 installe - FAIT
- Route globale POST /risks/recalculate - FAIT

### 2.7 Phase 6 : UI polish (PR #9 + #10)

- Tags UI sur workitem-form - FAIT
- Command Palette Cmd+K - FAIT
- 422 error handling (workitem + governance) - FAIT
- Boutons Export Excel + PDF sur 4 dashboards - FAIT
- Mode sombre (ThemeToggle Light/Dark/System) - FAIT
- Versioning documents (migration + 3 controllers + 3 models + badge) - FAIT
- File preview dialog inline - FAIT
- Page securite 2FA frontend (settings/security) - FAIT

### 2.8 Features UI frontend (anciennement "gaps")

Toutes verifiees par audit code le 2026-02-11 :

- Multi-assignation Work Items : AssignmentPanel complet (owner/member, dialog ajout) - FAIT
- Risk File Attachments : integre page detail risque (upload, delete, download) - FAIT
- Risk Theme Permissions admin : page complete avec CRUD 4 permissions - FAIT
- Supplier Multi-entity : onglet Entites sur page detail (ajout/suppression) - FAIT
- Bulk Invoice Import : bouton Import CSV sur page invoices -> /import-export - FAIT
- Supplier File Download : route GET + SupplierFileController::download() - FAIT

---

## 3. TACHES COMPLETEES (session 2026-02-11)

| # | Tache | Agent | Resultat |
|---|-------|-------|----------|
| T1 | Clarifier role Manager vs permissions | BOW-PM + BOW-ARCH | ADR-004 : permissions granulaires conservees |
| T2 | Rate limiting login 5 req/min | BOW-SEC + BOW-BACK | throttle:5,1 sur login + 2fa/verify, 2 tests |
| T3 | Historique connexions admin | BOW-BACK + BOW-FRONT | Migration + LoginHistoryController + page frontend, 4 tests |
| T4 | Responsive mobile | BOW-FRONT | Sidebar mobile (hamburger, overlay, slide-in), breakpoints md |
| T5 | Endpoint /api/health | BOW-BACK | GET /api/health (DB + Redis check), 1 test |
| T6 | Form Requests (5 controllers) | BOW-QA + BOW-BACK | 10 Form Requests, 5 controllers refactorises |

---

## 4. CE QUI RESTE

### 4.1 Technique (~2% - hors scope dev)

| # | Item | Type |
|---|------|------|
| 1 | Tests de charge (k6, Artillery) | Performance |
| 2 | Staging environment | Infra ops |
| 3 | Documentation utilisateur | Non-dev |

### 4.2 Configuration production (pre-deploiement)

Voir detail complet dans `PLAN-CONFIG-PROD.md`. Resume :

| # | Action | Responsable | Critique |
|---|--------|-------------|----------|
| 1 | Creer 3 fichiers .env (root, backend, frontend) | BOW-ARCH | OUI |
| 2 | Configurer SENTRY_LARAVEL_DSN + NEXT_PUBLIC_SENTRY_DSN | BOW-ARCH | OUI |
| 3 | Configurer S3/MinIO credentials + FILESYSTEM_DISK=s3 | BOW-ARCH | NON (local OK) |
| 4 | Configurer Redis + lancer `php artisan horizon` | BOW-ARCH | OUI |
| 5 | Generer recovery codes 2FA pour comptes admin | BOW-SEC | OUI |
| 6 | Retirer localhost des CORS (`config/cors.php`) | BOW-SEC | OUI |
| 7 | Configurer SMTP (Mailgun/SendGrid) | BOW-ARCH | OUI |
| 8 | `php artisan key:generate` | BOW-ARCH | OUI |
| 9 | `php artisan config:cache && route:cache && view:cache` | BOW-ARCH | OUI |
| 10 | Verifier permissions storage (775, www-data) | BOW-SEC | OUI |

---

## 4. INVENTAIRE TECHNIQUE

### 4.1 Backend (tavira-bow-api/)

| Categorie | Nombre |
|-----------|--------|
| Controllers (app/Http/Controllers/Api/) | 34 |
| Models | 34 |
| Migrations | 42 |
| Notifications | 6 |
| Services | 4 |
| Commands | 4+ |
| Tests (tests/) | 23 fichiers |
| Routes API (routes/api.php) | 345 lignes, 150+ endpoints |

Packages cles : sanctum, horizon, sentry, backup, activitylog, google2fa, dompdf, excel, permission

### 4.2 Frontend (tavira-bow-frontend/)

| Categorie | Nombre |
|-----------|--------|
| Pages (src/app/(dashboard)/) | 47 |
| Composants (src/components/) | 56 |
| Stores Zustand (src/stores/) | 12 (3264 lignes) |

Packages cles : next-themes, cmdk, recharts, @sentry/nextjs, zustand, react-hook-form, zod, @tanstack/react-query

### 4.3 Infrastructure (docker-compose.yml)

| Service | Image | Container |
|---------|-------|-----------|
| database | postgres:16-alpine | bow_database |
| redis | redis:7-alpine | bow_redis |
| api | php:8.3-fpm-alpine | bow_api |
| queue | (meme image api) | bow_queue |
| scheduler | (meme image api) | bow_scheduler |
| frontend | node:20-alpine | bow_frontend |
| webdb | webdb/app | bow_webdb |

Reverse proxy : Traefik (reseau admin_proxy)

---

## 5. TACHES PLANIFIEES (Scheduler)

| Commande | Frequence | Heure |
|----------|-----------|-------|
| bow:send-daily-summary | Quotidien | 07:00 |
| bow:send-task-reminders | Quotidien | 08:00 |
| bow:send-contract-alerts | Quotidien | 09:00 |
| bow:recalculate-dashboard | Horaire | - |
| backup:run --only-db | Quotidien | 02:00 |
| backup:run | Hebdo | Dim 03:00 |
| backup:clean | Hebdo | Dim 04:00 |
| backup:monitor | Quotidien | 06:00 |
| activitylog:clean | Mensuel | 1er |

---

## 6. URLS PRODUCTION

| Service | URL |
|---------|-----|
| Frontend | https://bow.ohadja.com |
| API | https://api-bow.ohadja.com |
| Horizon | https://api-bow.ohadja.com/horizon |
| WebDB | https://webdb-bow.ohadja.com |

---

## 7. REGLES CI/CD

Tous les checks doivent passer avant merge :

```
docker exec bow_api vendor/bin/pint --test
docker exec bow_api vendor/bin/phpstan analyse
docker exec bow_api php artisan test
cd tavira-bow-frontend && npx tsc --noEmit
```

---

## 8. STRUCTURE _bmad-output/

```
_bmad-output/
  ETAT-PROJET-BOW.md      <- CE FICHIER (reference unique etat projet)
  PLAN-ACTION.md           <- Taches restantes + agents assignes
  PLAN-CONFIG-PROD.md      <- Checklist config production
  README.md                <- Index de navigation
  bmb-creations/           <- 8 agents BOW + contexte projet
  import-export-sprint/    <- Sprint import/export + 3 ADR
  archive/                 <- Plans et audits historiques (9 fichiers)
```

Voir `README.md` pour le detail de chaque fichier et dossier.
