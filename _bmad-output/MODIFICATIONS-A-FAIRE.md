# Modifications a faire - BOW Refonte

Mis a jour le 2026-02-11 apres merge PR #8 (Phase 1) et PR #9 (Phases 2-6).

---

## Legende

- FAIT = termine et merge sur main
- A FAIRE = reste a faire
- **Fichier** = chemin depuis la racine du repo

---

## 1. Erreurs API corrigees

| # | Probleme | Statut |
|---|----------|--------|
| 1.1 | 403 sur GET /api/governance/items/{id} (param 'governance' -> 'item') | FAIT |
| 1.2 | Mismatch parametre GovernanceController ($governance -> $item) | FAIT |
| 1.3 | 422 sur POST /api/governance/items (frontend error handling) | FAIT (Phase 6) |

---

## 2. Milestones - parite POC

| # | Fonctionnalite | Statut |
|---|----------------|--------|
| 2.1 | Affichage conditionnel (Transformative only) sur page detail | FAIT |
| 2.2 | Calendrier taches avec milestones couleur rose | FAIT |
| 2.3 | Milestones dans formulaires creation/edition tache | FAIT |

---

## 3. URLs stores frontend

| # | Correction | Statut |
|---|-----------|--------|
| 3.1 | PATCH workitem status -> PUT | FAIT |
| 3.2 | Supplier stats URL | FAIT |
| 3.3-3.4 | Contract update/delete URLs nestees | FAIT |
| 3.5-3.6 | Invoice update/delete URLs nestees | FAIT |
| 3.7 | Risk recalculate URL (per-risk) | FAIT |
| 3.8 | User permission delete URL | FAIT |

---

## 4. Routes backend

| # | Route | Statut |
|---|-------|--------|
| 4.1 | GET /invoices (global) | FAIT |
| 4.2 | GET /contracts (global) | FAIT |
| 4.3 | GET /risks/actions/all (global) | FAIT |
| 4.4 | Routes access governance | FAIT |
| 4.5 | Routes access suppliers | FAIT |
| 4.6 | Routes dependencies work items | FAIT |
| 4.7 | Route globale POST /risks/recalculate | A FAIRE (P1) |

---

## 5. Dashboard URLs - FAIT

Tous les URLs dashboard corriges avec fallback resilient.

---

## 6. Mock data - FAIT

Tous les catch blocks nettoyes, aucune page n'affiche de mock data.

---

## 7. Encodage MacRoman - FAIT

44 artefacts corriges, sanitisation import ajoutee, 7 tests TDD.

---

## 8. Securite (Phase 1 - PR #8)

| # | Fonctionnalite | Statut |
|---|----------------|--------|
| 8.1 | Nginx HSTS/CSP/Permissions-Policy headers | FAIT |
| 8.2 | 2FA TOTP (TwoFactorController, 10 tests Pest) | FAIT |
| 8.3 | Backup automatise (spatie/laravel-backup) | FAIT |

---

## 9. Notifications (Phase 2 - PR #9)

| # | Fonctionnalite | Statut |
|---|----------------|--------|
| 9.1 | Infrastructure (migration, config/mail.php) | FAIT |
| 9.2 | TaskDueReminderNotification (J-7/J-3/J-1) | FAIT |
| 9.3 | ContractExpiringNotification | FAIT |
| 9.4 | RiskThresholdBreachedNotification | FAIT |
| 9.5 | TaskAssignedNotification | FAIT |
| 9.6 | Recap quotidien (DailySummaryNotification) | A FAIRE (P3) |
| 9.7 | NotificationController + UI in-app | FAIT |

---

## 10. Vues avancees (Phase 3 - PR #9)

| # | Fonctionnalite | Statut |
|---|----------------|--------|
| 10.1 | Kanban board (drag & drop HTML5) | FAIT |
| 10.2 | Gantt chart timeline | FAIT |
| 10.3 | Workload stacked bar charts | FAIT |

---

## 11. Audit trail (Phase 4 - PR #9)

| # | Fonctionnalite | Statut |
|---|----------------|--------|
| 11.1 | LogsActivity sur 4 models | FAIT |
| 11.2 | AuditController (index, forSubject, stats) | FAIT |
| 11.3 | Page audit trail frontend | FAIT |

---

## 12. Reports PDF + Currency (Phase 5 - PR #9)

| # | Fonctionnalite | Statut |
|---|----------------|--------|
| 12.1 | CurrencyConversionService | FAIT |
| 12.2 | ReportController (4 endpoints PDF) | FAIT (code OK) |
| 12.3 | 5 Blade templates PDF | FAIT |
| 12.4 | Installer barryvdh/laravel-dompdf | A FAIRE |

---

## 13. UI polish (Phase 6 - PR #9)

| # | Fonctionnalite | Statut |
|---|----------------|--------|
| 13.1 | Tags UI sur workitem-form | FAIT |
| 13.2 | Command Palette (Cmd+K) | FAIT |
| 13.3 | 422 error handling (workitem + governance) | FAIT |

---

## 14. Features UI manquantes (backend OK, frontend incomplet)

| # | Feature | Backend | Frontend | Priorite |
|---|---------|---------|----------|----------|
| 1 | Multi-assignation Work Items | Routes OK | AssignmentPanel incomplet | P1 |
| 2 | Risk File Attachments | Routes OK | Non integre page detail | P2 |
| 3 | Risk Theme Permissions admin | Routes OK | Pas d'UI admin | P2 |
| 4 | Supplier Multi-entity | Table OK | Pas d'UI | P2 |
| 5 | Bulk Invoice Import | Route existe | Pas de bouton UI | P3 |
| 6 | Supplier File Download | Route manquante | UI existe | P2 |

---

## 15. Recap - ce qui reste

### P1 - HAUTE

| # | Tache | Domaine |
|---|-------|---------|
| 1 | Route POST /risks/recalculate globale | BACK |
| 2 | Multi-assignation Work Items UI | FRONT |

### P2 - MOYENNE

| # | Tache | Domaine |
|---|-------|---------|
| 3 | Installer barryvdh/laravel-dompdf | BACK (infra) |
| 4 | Risk File Attachments integration | FRONT |
| 5 | Risk Theme Permissions admin UI | FRONT |
| 6 | Supplier Multi-entity UI | FRONT |
| 7 | Supplier File Download route | BACK |

### P3 - BASSE

| # | Tache | Domaine |
|---|-------|---------|
| 8 | Recap quotidien (DailySummaryNotification) | BACK |
| 9 | Bulk Invoice Import bouton UI | FRONT |
| 10 | Versioning documents (colonne version + historique) | BACK + FRONT |

### Infra / Optionnel

| # | Tache | Domaine |
|---|-------|---------|
| 11 | S3/MinIO pour stockage fichiers | BACK (infra) |
| 12 | Laravel Horizon (queue monitoring) | BACK (infra) |
| 13 | Sentry (error tracking) | BACK + FRONT |
| 14 | Historique connexions admin | BACK + FRONT |
| 15 | Page securite 2FA (frontend) | FRONT |
| 16 | Boutons Export PDF dans dashboards | FRONT |
| 17 | Mode sombre | FRONT |

---

## 16. References

- Audits archives : `_bmad-output/archive/`
- Agents BOW : `_bmad-output/bmb-creations/`
- Sprint import/export : `_bmad-output/import-export-sprint/`
- Contexte metier : `_bmad-output/bmb-creations/project-context-bow.yaml`
