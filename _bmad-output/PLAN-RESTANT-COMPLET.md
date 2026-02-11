# PLAN RESTANT COMPLET - BOW Refonte

Date : 2026-02-11
Apres merge PR #8 (Phase 1 Securite) et PR #9 (Phases 2-6)

---

## Etat recalibre apres audit approfondi

Plusieurs taches listees comme "A FAIRE" sont en realite deja implementees :

| Tache | Statut reel | Preuve |
|-------|-------------|--------|
| Route POST /risks/recalculate globale | DEJA FAIT | api.php:240, RiskController:335 |
| Risk File Attachments frontend | DEJA FAIT | risks/[id]/page.tsx:594 |
| Risk Theme Permissions admin UI | DEJA FAIT | risks/themes/permissions/page.tsx complet |
| Supplier Multi-entity UI | DEJA FAIT | suppliers/[id]/page.tsx:313, onglet Entites |
| Supplier File Download route | DEJA FAIT | api.php:180, SupplierFileController::download |
| Bulk Invoice Import bouton UI | DEJA FAIT | suppliers/invoices/page.tsx:116 |
| Health endpoint | DEJA FAIT | /up (Laravel built-in, bootstrap/app.php) |

---

## CE QUI RESTE REELLEMENT

### Inventaire final : 11 taches en 4 phases

---

## PHASE A : CORRECTIONS BACKEND (1 tache)

### A.1 Verifier contrat API multi-assignation (assignmentType vs type)

- **Probleme** : Le frontend AssignmentPanel envoie `assignmentType`, le backend attend `type`
- **Fichiers** :
  - `tavira-bow-frontend/src/components/workitems/assignment-panel.tsx` - verifier le payload envoye
  - `tavira-bow-api/app/Http/Controllers/Api/WorkItemController.php:291` - parametre attendu
- **Action** : Aligner le nom du champ entre front et back
- **Test** : Tester assign/unassign via l'UI
- **Effort** : 15 min
- **Domaine** : BACK + FRONT

---

## PHASE B : INFRASTRUCTURE MANQUANTE (2 taches)

### B.1 Installer barryvdh/laravel-dompdf

- **Commande** : `docker exec bow_api composer require barryvdh/laravel-dompdf`
- **Verification** : Le ReportController (4 endpoints PDF) est deja code et merge
- **Fichiers concernes** :
  - `tavira-bow-api/app/Http/Controllers/Api/ReportController.php` - utilise deja Pdf::loadView
  - `tavira-bow-api/resources/views/reports/*.blade.php` - 5 templates deja crees
- **Test** : `docker exec bow_api php artisan test` (Larastan doit passer sans @phpstan-ignore-line)
- **Effort** : 10 min
- **Domaine** : BACK (infra)

### B.2 Page securite 2FA dans le frontend

- **Contexte** : Le backend 2FA est complet (TwoFactorController, 10 tests). Il manque l'UI utilisateur.
- **Fichiers a creer** :
  - `tavira-bow-frontend/src/app/(dashboard)/settings/security/page.tsx`
    - Toggle activer/desactiver 2FA
    - Affichage QR code (via endpoint POST /auth/2fa/enable)
    - Champ saisie code verification (POST /auth/2fa/confirm)
    - Affichage codes de recuperation (POST /auth/2fa/recovery-codes)
    - Bouton desactiver (POST /auth/2fa/disable)
- **Fichiers a modifier** :
  - `tavira-bow-frontend/src/components/layout/sidebar.tsx` - ajouter lien Settings > Security
  - `tavira-bow-frontend/src/stores/auth.ts` - ajouter methodes enable2FA, confirm2FA, disable2FA, getRecoveryCodes
- **Routes backend existantes** :
  - POST /auth/2fa/enable
  - POST /auth/2fa/confirm
  - POST /auth/2fa/disable
  - POST /auth/2fa/recovery-codes
- **Effort** : 2-3h
- **Domaine** : FRONT

---

## PHASE C : FEATURES CDC MANQUANTES (5 taches)

### C.1 Boutons Export dans les dashboards

- **Contexte** : Le backend export existe (ImportExportController::exportWorkItems, exportRisks, etc.) mais les dashboards n'ont pas de bouton.
- **Fichiers a modifier** :
  - `tavira-bow-frontend/src/app/(dashboard)/risks/dashboard/page.tsx` - ajouter bouton Export Excel + Export PDF
  - `tavira-bow-frontend/src/app/(dashboard)/tasks/dashboard/page.tsx` - ajouter bouton Export Excel + Export PDF
  - `tavira-bow-frontend/src/app/(dashboard)/suppliers/dashboard/page.tsx` - ajouter bouton Export Excel + Export PDF
  - `tavira-bow-frontend/src/app/(dashboard)/governance/dashboard/page.tsx` - ajouter bouton Export Excel + Export PDF
- **URLs backend existantes** :
  - GET /export/workitems (Excel)
  - GET /export/risks (Excel)
  - GET /export/suppliers (Excel)
  - GET /export/governance (Excel)
  - GET /reports/work-items (PDF)
  - GET /reports/risks (PDF)
  - GET /reports/suppliers (PDF)
  - GET /reports/governance (PDF)
- **Action** : Ajouter un DropdownMenu avec "Export Excel" et "Export PDF" sur chaque dashboard
- **Effort** : 1h
- **Domaine** : FRONT

### C.2 DailySummaryNotification (recap quotidien)

- **CDC** : "Recapitulatif quotidien : Email consolide des actions en attente (optionnel)"
- **Fichiers a creer** :
  - `tavira-bow-api/app/Notifications/DailySummaryNotification.php`
    - Canal : mail uniquement
    - Contenu : taches dues aujourd'hui, risques RED, contrats expirant sous 30j, actions overdue
    - Envoye aux users avec role admin ou manager
  - `tavira-bow-api/app/Console/Commands/SendDailySummaryCommand.php`
    - Signature : `bow:send-daily-summary`
    - Collecte stats, dispatch notification aux admins/managers
- **Fichiers a modifier** :
  - `tavira-bow-api/routes/console.php` - scheduler `->dailyAt('07:00')`
- **Tests a creer** :
  - `tavira-bow-api/tests/Feature/DailySummaryTest.php` (3 tests : envoye, contenu correct, pas envoye si rien a signaler)
- **Effort** : 2-3h
- **Domaine** : BACK

### C.3 Previsualisation documents inline

- **CDC** : "Previsualisation des documents sans telechargement"
- **Fichier a modifier** :
  - `tavira-bow-frontend/src/components/shared/file-attachments-panel.tsx`
    - Ajouter bouton Eye (preview) a cote du bouton Download
    - Pour images : Dialog avec `<img>` tag
    - Pour PDF : `<iframe>` ou `<object>` embed
    - Pour autres types : message "Preview non disponible, telecharger le fichier"
    - Conditionnel sur le mime type (deja detecte par getMimeIcon)
- **Fichier a creer** :
  - `tavira-bow-frontend/src/components/shared/file-preview-dialog.tsx`
    - Dialog Shadcn/ui avec preview du fichier
    - Props : url, filename, mimeType, open, onClose
- **Effort** : 2h
- **Domaine** : FRONT

### C.4 Mode sombre (toggle UI)

- **CDC** : "Mode sombre (optionnel)"
- **Contexte** : next-themes est installe et ThemeProvider est configure dans providers.tsx. Il manque le toggle.
- **Fichier a creer** :
  - `tavira-bow-frontend/src/components/layout/theme-toggle.tsx`
    - Bouton Moon/Sun avec useTheme() de next-themes
    - 3 options : Light, Dark, System
    - DropdownMenu avec les 3 choix
- **Fichiers a modifier** :
  - `tavira-bow-frontend/src/components/layout/header.tsx` - integrer ThemeToggle dans le header
  - `tavira-bow-frontend/src/app/globals.css` - verifier que les CSS variables dark existent (Shadcn/ui genere normalement les deux palettes)
- **Effort** : 1h
- **Domaine** : FRONT

### C.5 Versioning documents

- **CDC** : "Historique des versions des documents"
- **Contexte** : Colonne `version` existe deja dans risk_attachments, governance_attachments, supplier_contract_attachments. Manque dans supplier_attachments.
- **Fichiers a creer** :
  - `tavira-bow-api/database/migrations/2026_02_11_000002_add_version_to_supplier_attachments.php`
    - Ajouter colonne `version` (integer, default 1) a supplier_attachments
- **Fichiers a modifier** :
  - `tavira-bow-api/app/Http/Controllers/Api/RiskFileController.php` - lors de l'upload, si meme filename existe, incrementer version
  - `tavira-bow-api/app/Http/Controllers/Api/GovernanceFileController.php` - idem
  - `tavira-bow-api/app/Http/Controllers/Api/SupplierFileController.php` - idem
  - `tavira-bow-api/app/Http/Controllers/Api/WorkItemFileController.php` - idem (si applicable)
  - `tavira-bow-frontend/src/components/shared/file-attachments-panel.tsx` - afficher badge version, lister versions
- **Effort** : 3-4h
- **Domaine** : BACK + FRONT

---

## PHASE D : OPTIONNEL / INFRA (3 taches)

### D.1 S3/MinIO pour stockage fichiers

- **CDC Technique** : "S3/MinIO : Laravel Storage abstraction, signed URLs"
- **Fichiers a modifier** :
  - `tavira-bow-api/config/filesystems.php` - ajouter disque s3
  - `tavira-bow-api/.env.example` - variables AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET
  - Tous les file controllers : remplacer `Storage::disk('local')` par `Storage::disk(config('filesystems.default'))`
- **Effort** : 2-3h
- **Domaine** : BACK (infra)

### D.2 Laravel Horizon (queue monitoring)

- **CDC Technique** : "Laravel Horizon pour Redis job supervision"
- **Commande** : `docker exec bow_api composer require laravel/horizon`
- **Fichiers** :
  - `tavira-bow-api/config/horizon.php` - configuration workers
  - `tavira-bow-api/routes/web.php` - route /horizon protegee
- **Effort** : 1-2h
- **Domaine** : BACK (infra)

### D.3 Sentry (error tracking)

- **CDC Technique** : "Sentry integration possible pour alertes temps reel"
- **Commandes** :
  - `docker exec bow_api composer require sentry/sentry-laravel`
  - `cd tavira-bow-frontend && npm install @sentry/nextjs`
- **Fichiers** :
  - `tavira-bow-api/config/sentry.php`
  - `tavira-bow-api/.env.example` - SENTRY_LARAVEL_DSN
  - `tavira-bow-frontend/sentry.client.config.ts`
  - `tavira-bow-frontend/sentry.server.config.ts`
- **Effort** : 1-2h
- **Domaine** : BACK + FRONT

---

## ORDONNANCEMENT

```
PHASE A [15 min]
  A.1 Fix contrat API assignation ............ BACK + FRONT

PHASE B [3-4h]
  B.1 Installer dompdf ....................... BACK (10 min)
  B.2 Page 2FA frontend ...................... FRONT (2-3h)
  (en parallele)

PHASE C [10-12h]
  C.1 Boutons export dashboards .............. FRONT (1h)
  C.2 DailySummaryNotification ............... BACK (2-3h)
  C.3 Preview documents inline ............... FRONT (2h)
  C.4 Mode sombre toggle ..................... FRONT (1h)
  C.5 Versioning documents ................... BACK + FRONT (3-4h)
  (C.1 + C.4 en parallele, puis C.3, puis C.2 + C.5 en parallele)

PHASE D [5-7h] (optionnel)
  D.1 S3/MinIO ............................... BACK (2-3h)
  D.2 Horizon ................................ BACK (1-2h)
  D.3 Sentry ................................. BACK + FRONT (1-2h)
  (toutes en parallele)
```

---

## COUVERTURE CDC APRES COMPLETION

| Metrique | Avant | Apres Phase C | Apres Phase D |
|----------|-------|---------------|---------------|
| CDC Fonctionnel | ~90% | ~98% | ~99% |
| CDC Technique | ~80% | ~85% | ~95% |
| Tests | 203 | ~215 | ~215 |

Les 2% restants du CDC Fonctionnel apres Phase C :
- Historique connexions specifique (login IP/date) - non critique
- Documentation utilisateur - hors scope dev

Les 5% restants du CDC Technique apres Phase D :
- Tests de charge (hors scope dev)
- Staging environment (infra ops)

---

## CRITERES DE VALIDATION

Avant commit final :
1. `docker exec bow_api php artisan test` : 0 echecs
2. `cd tavira-bow-frontend && npx tsc --noEmit` : 0 erreurs
3. `docker exec bow_api vendor/bin/pint --test` : 0 issues
4. `docker exec bow_api vendor/bin/phpstan analyse` : 0 erreurs
