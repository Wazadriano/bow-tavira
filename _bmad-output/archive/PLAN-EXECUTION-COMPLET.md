# PLAN D'EXECUTION COMPLET - BOW Refonte

Date : 2026-02-11
Version : 1.0
Auteur : Audit croise BOW-PM x BOW-BACK x BOW-FRONT x BOW-ARCH x BOW-SEC x BOW-QA

---

## Etat actuel

- CDC Fonctionnel : 85% couvert
- CDC Technique : 65% couvert
- Parite POC : 100%
- Tests : 184 passes
- Backend : 125 routes, 34 modeles, 29 controllers
- Frontend : 43 pages, 53 composants, 11 stores

---

## Regles d'execution

1. Chaque PHASE doit etre terminee avant de passer a la suivante
2. Au sein d'une phase, les taches BACK et FRONT peuvent etre parallelisees
3. Chaque tache doit passer : Pint + Larastan + Tests (BACK) ou tsc --noEmit (FRONT)
4. Un commit par tache ou groupe logique de taches
5. TDD obligatoire sur le backend (test avant implementation)
6. Pas de Co-Authored-By dans les commits

---

## PHASE 1 : SECURITE CRITIQUE (avant toute mise en prod)

### Agent : BOW-SEC + BOW-BACK

#### 1.1 Headers securite nginx
- **Fichier** : `tavira-bow-api/docker/nginx/default.conf`
- **Action** : Ajouter HSTS + CSP
  ```
  add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
  add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self' https:;" always;
  ```
- **Effort** : 15 min
- **Test** : `curl -I` sur l'URL pour verifier les headers

#### 1.2 Installer et configurer 2FA (Fortify / Google2FA)
- **Fichiers backend** :
  - `composer.json` : ajouter `pragmarx/google2fa-laravel` + `bacon/bacon-qr-code`
  - `app/Http/Controllers/Api/TwoFactorController.php` : nouveau controller
    - `POST /auth/2fa/enable` : genere secret + QR code
    - `POST /auth/2fa/verify` : verifie code TOTP et confirme activation
    - `POST /auth/2fa/disable` : desactive 2FA
    - `GET /auth/2fa/recovery-codes` : affiche codes de recuperation
  - `app/Http/Middleware/TwoFactorVerified.php` : middleware optionnel
  - `routes/api.php` : enregistrer les 4 routes
  - Tests : 8-10 tests (enable, verify valid/invalid, disable, recovery codes)
- **Fichiers frontend** :
  - `tavira-bow-frontend/src/app/(dashboard)/settings/security/page.tsx` : nouvelle page
  - UI : toggle 2FA, affichage QR code, saisie code verification, recovery codes
  - Store `auth.ts` : methodes enable2FA, verify2FA, disable2FA
- **Effort** : 3-4 jours
- **Depend de** : rien
- **Bloque** : mise en prod

#### 1.3 Configurer backup automatique
- **Fichiers** :
  - `composer.json` : ajouter `spatie/laravel-backup`
  - `config/backup.php` : configuration destinations (local + S3)
  - `routes/console.php` : scheduler `backup:run` quotidien, `backup:clean` hebdomadaire
  - `docker-compose.yml` (prod) : volume pour backups
- **Effort** : 1 jour
- **Depend de** : rien

---

## PHASE 2 : NOTIFICATIONS (core feature CDC)

### Agent : BOW-BACK + BOW-FRONT

#### 2.1 Infrastructure notifications
- **Fichiers backend** :
  - `config/mail.php` : verifier config SMTP (Mailgun/SendGrid)
  - `database/migrations/xxxx_create_notifications_table.php` : table notifications Laravel
  - `app/Models/User.php` : verifier trait `Notifiable` (deja present)
- **Effort** : 2h
- **Depend de** : rien

#### 2.2 Notification rappel taches
- **Fichiers** :
  - `app/Notifications/TaskDueReminderNotification.php` : email J-7, J-3, J-1
  - `app/Console/Commands/SendTaskRemindersCommand.php` : implementation (stub existe)
  - `routes/console.php` : scheduler quotidien
  - Tests : 5 tests (rappel envoye, pas envoye si complete, pas de doublon, J-7/J-3/J-1)
- **Effort** : 1 jour
- **Depend de** : 2.1

#### 2.3 Notification alertes contrats expirants (RG-BOW-007)
- **Fichiers** :
  - `app/Notifications/ContractExpiringNotification.php` : email a J-90, J-30, J-7
  - `app/Console/Commands/ContractExpiryAlertsCommand.php` : completer (logic partielle existe)
  - Tests : 5 tests
- **Effort** : 1 jour
- **Depend de** : 2.1

#### 2.4 Notification depassement seuil risque
- **Fichiers** :
  - `app/Notifications/RiskThresholdBreachedNotification.php` : email quand appetite = OUTSIDE
  - Integrer dans `RiskScoringService` apres recalcul
  - Tests : 3 tests
- **Effort** : 0.5 jour
- **Depend de** : 2.1

#### 2.5 Notification assignation
- **Fichiers** :
  - `app/Notifications/TaskAssignedNotification.php` : email quand assigne a une tache
  - Integrer dans `WorkItemController::assign()`
  - Tests : 3 tests
- **Effort** : 0.5 jour
- **Depend de** : 2.1

#### 2.6 Recapitulatif quotidien (optionnel)
- **Fichiers** :
  - `app/Notifications/DailySummaryNotification.php` : resume taches dues, risques eleves
  - Command + scheduler
  - Tests : 3 tests
- **Effort** : 1 jour
- **Depend de** : 2.2, 2.3, 2.4

#### 2.7 UI notifications in-app (frontend)
- **Fichiers frontend** :
  - `src/components/layout/notification-bell.tsx` : cloche dans le header avec badge count
  - `src/stores/notifications.ts` : store pour fetch/mark-as-read
  - `src/app/(dashboard)/notifications/page.tsx` : page liste notifications
  - Routes backend : `GET /notifications`, `PUT /notifications/{id}/read`, `PUT /notifications/read-all`
- **Effort** : 2 jours
- **Depend de** : 2.1

---

## PHASE 3 : VUES AVANCEES FRONTEND (parite CDC)

### Agent : BOW-FRONT

#### 3.1 Vue Kanban taches
- **Fichiers** :
  - `package.json` : ajouter `@dnd-kit/core` + `@dnd-kit/sortable`
  - `src/app/(dashboard)/tasks/kanban/page.tsx` : nouvelle page
  - `src/components/workitems/kanban-board.tsx` : composant drag-drop
  - `src/components/workitems/kanban-card.tsx` : carte tache
  - 4 colonnes : Not Started, In Progress, On Hold, Completed
  - Drag-drop change le statut via `PUT /workitems/{id}` avec `current_status`
  - Navigation : ajouter lien dans sidebar sous Tasks
- **Effort** : 3-4 jours
- **Depend de** : rien

#### 3.2 Diagramme Gantt
- **Fichiers** :
  - `package.json` : ajouter `frappe-gantt` ou `dhtmlx-gantt`
  - `src/app/(dashboard)/tasks/gantt/page.tsx` : nouvelle page
  - `src/components/workitems/gantt-chart.tsx` : composant wrapper
  - Afficher : deadline, dependencies entre taches, progression par statut
  - Navigation : ajouter lien dans sidebar sous Tasks
- **Effort** : 3-4 jours
- **Depend de** : rien

#### 3.3 Vue charge travail equipes
- **Fichiers** :
  - `src/app/(dashboard)/teams/workload/page.tsx` : nouvelle page
  - `src/components/teams/workload-chart.tsx` : stacked bar chart (par user/team)
  - Backend : `GET /teams/{team}/workload` ou agreger cote front via assignments
  - Navigation : ajouter lien dans sidebar sous Teams
- **Effort** : 2-3 jours
- **Depend de** : rien

---

## PHASE 4 : INFRASTRUCTURE PRODUCTION

### Agent : BOW-ARCH + BOW-SEC

#### 4.1 Configurer S3/MinIO pour stockage fichiers
- **Fichiers** :
  - `config/filesystems.php` : ajouter disque `s3`
  - `.env.example` : variables AWS_*/MINIO_*
  - `docker-compose.yml` (prod) : service MinIO optionnel
  - Migrer les controllers fichiers pour utiliser le disque configure
- **Effort** : 1-2 jours
- **Depend de** : rien

#### 4.2 Installer Horizon (supervision queues)
- **Fichiers** :
  - `composer.json` : ajouter `laravel/horizon`
  - `config/horizon.php` : configuration workers
  - `docker-compose.yml` : remplacer ou completer le worker queue par Horizon
  - Route : `/horizon` (dashboard Horizon protege par middleware)
- **Effort** : 1 jour
- **Depend de** : rien

#### 4.3 Monitoring erreurs (Sentry)
- **Fichiers** :
  - `composer.json` : ajouter `sentry/sentry-laravel`
  - `config/sentry.php` : DSN + config
  - `.env.example` : variable SENTRY_DSN
  - Frontend : `package.json` ajouter `@sentry/nextjs`, `sentry.client.config.ts`
- **Effort** : 0.5 jour
- **Depend de** : rien

#### 4.4 Historique connexions admin
- **Fichiers** :
  - `app/Http/Controllers/Api/AuditController.php` : nouveau controller
  - Routes : `GET /admin/login-history` (sessions), `GET /admin/activity-log` (spatie)
  - `src/app/(dashboard)/admin/audit/page.tsx` : nouvelle page
  - Backend : lire table `sessions` + `activity_log`
- **Effort** : 2 jours
- **Depend de** : rien

---

## PHASE 5 : REGLES METIER MANQUANTES

### Agent : BOW-BACK + BOW-PM

#### 5.1 Conversion multi-devises GBP (RG-BOW-013)
- **Fichiers** :
  - `app/Services/CurrencyConversionService.php` : nouveau service
  - Integration avec API taux de change (ex: exchangerate-api.com) ou table statique
  - Utiliser dans les calculs financiers (invoices, cost_savings, expected_cost)
  - `CurrencyController` existe deja : completer avec logique conversion
  - Tests : 5 tests (conversion, devise inconnue, taux 0, arrondi)
- **Effort** : 3-4 jours
- **Depend de** : rien

#### 5.2 Export PDF rapports mensuels
- **Fichiers** :
  - `composer.json` : ajouter `barryvdh/laravel-dompdf`
  - `app/Http/Controllers/Api/ReportController.php` : nouveau controller
  - Routes : `GET /reports/risks/pdf`, `GET /reports/tasks/pdf`
  - Templates Blade pour mise en page PDF
  - Frontend : boutons "Export PDF" dans les dashboards
  - Tests : 3 tests (generation, contenu, format)
- **Effort** : 3-4 jours
- **Depend de** : rien

---

## PHASE 6 : POLISH UI

### Agent : BOW-FRONT

#### 6.1 Tags Work Items UI
- **Fichiers** :
  - `src/components/workitems/workitem-form.tsx` : ajouter champ tags (multi-select ou input libre)
  - `src/components/shared/tag-input.tsx` : composant reutilisable
  - Backend : champ `tags` (JSON array) existe deja dans WorkItem
- **Effort** : 1 jour
- **Depend de** : rien

#### 6.2 Supplier Multi-entity UI
- **Fichiers** :
  - `src/components/suppliers/supplier-form.tsx` : section entites dans formulaire
  - Backend : table `supplier_entities` + routes existent
- **Effort** : 1 jour
- **Depend de** : rien

#### 6.3 Bulk Invoice Import bouton UI
- **Fichiers** :
  - `src/app/(dashboard)/suppliers/invoices/page.tsx` : ajouter bouton "Import"
  - Reutiliser le flow import existant (`/import-export`)
  - Backend : route `POST /suppliers/{supplier}/invoices/bulk` existe
- **Effort** : 0.5 jour
- **Depend de** : rien

#### 6.4 Raccourcis clavier
- **Fichiers** :
  - `src/hooks/useKeyboardShortcuts.ts` : hook global
  - `src/components/shared/command-palette.tsx` : palette Cmd+K
  - Raccourcis : Cmd+K (search), Cmd+N (new), Escape (close)
- **Effort** : 2 jours
- **Depend de** : rien

#### 6.5 Gerer 422 creation governance
- **Fichiers** :
  - `src/app/(dashboard)/governance/new/page.tsx` : afficher erreurs validation
  - `src/stores/governance.ts` : capturer et retourner erreurs 422
- **Effort** : 0.5 jour
- **Depend de** : rien

#### 6.6 Versioning documents
- **Fichiers backend** :
  - Ajouter colonne `version` aux tables attachments
  - Migration : `add_version_to_*_attachments_tables`
  - Modifier upload pour incrementer version au lieu d'ecraser
  - `GET /risks/{risk}/files/{file}/versions` : lister versions
- **Fichiers frontend** :
  - `src/components/shared/file-attachments-panel.tsx` : afficher historique versions
- **Effort** : 2-3 jours
- **Depend de** : rien

---

## RESUME ORDONNANCEMENT

```
PHASE 1 (Securite)     [Semaine 1]
  |-- 1.1 Headers nginx .............. 15 min   BOW-SEC
  |-- 1.2 2FA ........................ 3-4 jours BOW-BACK + BOW-FRONT
  |-- 1.3 Backup .................... 1 jour    BOW-ARCH
  (toutes en parallele)

PHASE 2 (Notifications) [Semaine 2-3]
  |-- 2.1 Infrastructure ............. 2h       BOW-BACK
  |   |-- 2.2 Rappel taches ......... 1 jour   BOW-BACK
  |   |-- 2.3 Alertes contrats ...... 1 jour   BOW-BACK
  |   |-- 2.4 Seuil risques ......... 0.5 jour BOW-BACK
  |   |-- 2.5 Assignation ........... 0.5 jour BOW-BACK
  |   |-- 2.6 Recap quotidien ....... 1 jour   BOW-BACK (apres 2.2-2.4)
  |   |-- 2.7 UI notifications ...... 2 jours  BOW-FRONT (en parallele)

PHASE 3 (Vues avancees) [Semaine 3-4]
  |-- 3.1 Kanban ..................... 3-4 jours BOW-FRONT
  |-- 3.2 Gantt ...................... 3-4 jours BOW-FRONT
  |-- 3.3 Charge travail ............ 2-3 jours BOW-FRONT
  (toutes en parallele)

PHASE 4 (Infrastructure) [Semaine 4]
  |-- 4.1 S3/MinIO .................. 1-2 jours BOW-ARCH
  |-- 4.2 Horizon ................... 1 jour    BOW-ARCH
  |-- 4.3 Sentry .................... 0.5 jour  BOW-ARCH
  |-- 4.4 Historique connexions ..... 2 jours   BOW-BACK + BOW-FRONT
  (toutes en parallele)

PHASE 5 (Regles metier) [Semaine 5]
  |-- 5.1 Multi-devises ............. 3-4 jours BOW-BACK
  |-- 5.2 Export PDF ................ 3-4 jours BOW-BACK
  (en parallele)

PHASE 6 (Polish UI)     [Semaine 6]
  |-- 6.1 Tags UI ................... 1 jour    BOW-FRONT
  |-- 6.2 Multi-entity UI ........... 1 jour    BOW-FRONT
  |-- 6.3 Bulk import bouton ........ 0.5 jour  BOW-FRONT
  |-- 6.4 Raccourcis clavier ........ 2 jours   BOW-FRONT
  |-- 6.5 Gerer 422 governance ...... 0.5 jour  BOW-FRONT
  |-- 6.6 Versioning documents ...... 2-3 jours BOW-BACK + BOW-FRONT
  (toutes en parallele)
```

---

## CRITERES DE COMPLETION

A la fin des 6 phases :
- CDC Fonctionnel : 100%
- CDC Technique : 95%+ (hors tests de charge)
- Tous les tests passent (objectif : 250+ tests)
- Pint + Larastan + tsc clean
- Pipeline CI/CD vert
- Zero mock data en frontend
- Application prete pour production

---

## ASSIGNATION AGENTS

| Agent | Phases | Responsabilites |
|-------|--------|-----------------|
| BOW-SEC | 1 | Headers, audit securite final |
| BOW-BACK | 1, 2, 5, 6 | 2FA, notifications, regles metier, versioning |
| BOW-FRONT | 1, 2, 3, 6 | 2FA UI, notifications UI, Kanban, Gantt, polish |
| BOW-ARCH | 1, 4 | Backup, S3, Horizon, Sentry |
| BOW-QA | transversal | Tests TDD, validation chaque phase |
| BOW-PM | transversal | Validation regles metier, acceptation fonctionnelle |
