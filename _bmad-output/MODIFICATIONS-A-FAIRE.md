# Modifications a faire - Parite POC & corrections BOW

Document de suivi pour travailler sur les changements identifies (parite POC, erreurs API, milestones, governance, stores, routes, mock data).

Mis a jour le 2026-02-11 apres audit croise BOW-BACK x BOW-FRONT x BOW-PM.

---

## Legende

- FAIT = deja fait
- A FAIRE = a faire
- **Fichier** = chemin depuis la racine du repo

---

## 1. Erreurs API

### 1.1 403 sur GET /api/governance/items/{id}

- FAIT
  - **Fichier** : `tavira-bow-api/app/Http/Controllers/Api/GovernanceController.php`
  - **Modif** : `authorizeResource(GovernanceItem::class, 'item')` au lieu de `'governance'`.

### 1.2 Mismatch parametre GovernanceController

- FAIT
  - **Fichier** : `tavira-bow-api/app/Http/Controllers/Api/GovernanceController.php`
  - **Modif** : `$governance` renomme en `$item` dans `show()`, `update()`, `destroy()`.

### 1.3 422 sur POST /api/governance/items (creation)

- A FAIRE (P2)
  - **Cause** : `store()` exige `ref_no` (required, unique), `description` (required), `department` (required). Formulaire incomplet ou `ref_no` en doublon -> 422.
  - **Frontend** :
    1. Verifier que le formulaire de creation governance envoie tous les champs requis.
    2. Gerer la reponse 422 : afficher les messages de validation.
  - **Fichiers** : formulaire creation governance, store `governance.ts`.

---

## 2. Milestones - parite POC

### 2.1 Affichage des milestones sur la page detail tache

- FAIT
  - **Fichier** : `tavira-bow-frontend/src/app/(dashboard)/tasks/[id]/page.tsx`
  - **Modif** : `<MilestonesPanel>` uniquement si `item.bau_or_transformative === 'transformative'`.

### 2.2 Calendrier taches : milestones avec couleur rose

- FAIT
  - **Fichiers** :
    - `tavira-bow-frontend/src/app/(dashboard)/tasks/calendar/page.tsx`
    - `tavira-bow-frontend/src/components/calendar/calendar-view.tsx`

### 2.3 Milestones dans formulaires creation/edition tache

- FAIT
  - **Fichier** : `tavira-bow-frontend/src/components/workitems/workitem-form.tsx` (lignes 303-320)
  - **Modif** : Section Milestones conditionnelle (`bau_or_transformative === 'transformative'`). Creation: message "Please save first". Edition: MilestonesPanel inline.

---

## 3. URLs stores frontend

### 3.1 PATCH workitem status -> PUT

- FAIT
  - `PUT /workitems/{id}` avec `{current_status: ...}` (ligne 225)

### 3.2 Supplier stats URL

- FAIT
  - `GET /suppliers-dashboard` (ligne 138)

### 3.3-3.4 Contract update/delete URLs nestees

- FAIT
  - `/suppliers/${supplierId}/contracts/${contractId}` (lignes 274, 295)

### 3.5-3.6 Invoice update/delete URLs nestees

- FAIT
  - `/suppliers/${supplierId}/invoices/${invoiceId}` (lignes 344, 365)

### 3.7 Risk recalculate URL

- FAIT (front utilise `/risks/${id}/recalculate`, route per-risk backend OK)
  - Route globale `POST /risks/recalculate` manquante (voir 4.7)

### 3.8 User permission delete URL

- FAIT
  - `/users/${userId}/permissions/${permissionId}` (ligne 252)

---

## 4. Routes backend

### 4.1 GET /invoices (global)

- FAIT
  - Route ligne 138 api.php, methode `SupplierInvoiceController::all()`

### 4.2 GET /contracts (global)

- FAIT
  - Route ligne 139 api.php, methode `SupplierContractController::all()`

### 4.3 GET /risks/actions/all (global)

- FAIT
  - Route ligne 221 api.php, methode `RiskActionController::all()`

### 4.4 Routes access governance

- FAIT
  - POST/DELETE access routes enregistrees dans api.php (lignes 125-126)

### 4.5 Routes access suppliers

- FAIT
  - POST/DELETE access routes enregistrees dans api.php (lignes 167-168)

### 4.6 Routes dependencies work items

- FAIT
  - POST/DELETE dependency routes enregistrees dans api.php (lignes 87-88)

### 4.7 Route globale POST /risks/recalculate

- A FAIRE (P1)
  - **Impact** : recalcul global de tous les scores de risques non accessible
  - **Fichier** : `tavira-bow-api/routes/api.php`
  - **Modif** : Ajouter `Route::post('/recalculate', [RiskController::class, 'recalculate']);` dans le prefix `risks` (hors groupe `{risk}`)
  - La methode `recalculate()` existe deja dans RiskController (ligne 335)

---

## 5. URLs dashboard

### 5.1 Risk dashboard URL

- FAIT
  - `/risks/dashboard` avec fallback resilient (ligne 45)

### 5.2 Supplier dashboard URL

- FAIT
  - `/suppliers-dashboard` avec fallback resilient (ligne 45)

---

## 6. Mock data

- FAIT
  - Tous les catch blocks nettoyes : `setInvoices([])`, `setContracts([])`, `setActions([])`, `setStats(null)`, `setDepartments([])`, `setEntities([])`
  - Aucune page n'affiche de mock data en dur

---

## 7. Features UI manquantes (backend OK)

| # | Feature | Backend | Frontend | Priorite |
|---|---------|---------|----------|----------|
| 1 | Multi-assignation Work Items | Routes OK | AssignmentPanel incomplet | P1 |
| 2 | Risk File Attachments | Routes OK | Non integre page detail | P2 |
| 3 | Risk Theme Permissions admin | Routes OK | Pas d'UI admin | P2 |
| 4 | Tags Work Items | Champ existe | Pas d'UI | P2 |
| 5 | Supplier Multi-entity | Table OK | Pas d'UI | P2 |
| 6 | Bulk Invoice Import | Route existe | Pas de bouton UI | P2 |
| 7 | Supplier File Download | Route manquante | UI existe | P2 |

---

## 8. Corrections donnees

### 8.1 Encodage MacRoman legacy data

- FAIT
  - 44 artefacts corriges dans `legacy_data.json`
  - ~41 rows corrigees en base PostgreSQL (work_items.description, monthly_update)
  - Sanitisation import ajoutee (CSV + Excel) dans `ImportNormalizationService`
  - 7 tests TDD couvrent la sanitisation

---

## 9. Recap global - checklist par priorite

### P1 - HAUTE (1 tache restante backend + 1 feature UI)

| # | Tache | Domaine | Fichiers principaux |
|---|-------|---------|---------------------|
| 1 | Enregistrer route POST /risks/recalculate globale (4.7) | BACK | `routes/api.php` |
| 2 | Multi-assignation Work Items UI (7.1) | FRONT | `AssignmentPanel` |

### P2 - MOYENNE (6 taches)

| # | Tache | Domaine | Fichiers principaux |
|---|-------|---------|---------------------|
| 3 | Gerer 422 creation governance (1.3) | FRONT | Formulaire governance, store |
| 4 | Risk File Attachments integration (7.2) | FRONT | Page detail risque |
| 5 | Risk Theme Permissions admin UI (7.3) | FRONT | Nouvelle page admin |
| 6 | Tags Work Items UI (7.4) | FRONT | Formulaire workitem |
| 7 | Supplier Multi-entity UI (7.5) | FRONT | Page detail supplier |
| 8 | Bulk Invoice Import bouton UI (7.6) | FRONT | Page invoices |

---

## 10. References

- Analyse parite POC : `_bmad-output/parity-poc-refonte-and-fixes.md`
- Fixes frontend : `_bmad-output/frontend-fixes-remaining.md`
- Gaps CDC vs code : `_bmad-output/cdc-vs-code-gaps.md`
- Routes manquantes : `_bmad-output/backend-routes-to-add.md`
- Audit frontend-backend : `_bmad-output/audit-frontend-backend-gap.md`
- Contexte metier : `_bmad-output/bmb-creations/project-context-bow.yaml`
