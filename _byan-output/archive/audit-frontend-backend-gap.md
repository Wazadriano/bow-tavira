# Audit complet : Ecarts Frontend vs Backend vs CDC

**Date** : 2026-02-10
**Auteur** : Audit automatise BOW-FRONT / BOW-BACK
**Statut** : A CORRIGER

---

## 1. Ecarts critiques : Endpoints frontend vs routes backend

Le frontend appelle des URLs qui n'existent pas ou ne matchent pas le backend Laravel.

### 1.1 URLs incorrectes dans les stores Zustand (8 fixes)

| # | Fichier | Ligne | Frontend appelle | Backend attend | Correction |
|---|---------|-------|-----------------|----------------|------------|
| 1 | `stores/workitems.ts` | 216 | `PATCH /workitems/{id}/status` | Pas de route PATCH status | Utiliser `PUT /workitems/{id}` avec `{ current_status: status }` dans le body complet |
| 2 | `stores/suppliers.ts` | 137 | `GET /suppliers/stats` | `GET /suppliers-dashboard` | Changer l'URL en `/suppliers-dashboard` |
| 3 | `stores/suppliers.ts` | 276 | `PUT /contracts/{contractId}` | `PUT /suppliers/{supplier}/contracts/{contract}` | Ajouter `supplierId` au store et utiliser URL nestee |
| 4 | `stores/suppliers.ts` | 295 | `DELETE /contracts/{contractId}` | `DELETE /suppliers/{supplier}/contracts/{contract}` | Idem, besoin du `supplierId` |
| 5 | `stores/suppliers.ts` | 346 | `PUT /invoices/{invoiceId}` | `PUT /suppliers/{supplier}/invoices/{invoice}` | Idem, besoin du `supplierId` |
| 6 | `stores/suppliers.ts` | 365 | `DELETE /invoices/{invoiceId}` | `DELETE /suppliers/{supplier}/invoices/{invoice}` | Idem, besoin du `supplierId` |
| 7 | `stores/risks.ts` | 306 | `POST /risks/{id}/recalculate` | `POST /risks/recalculate` (global) | Backend n'a PAS de per-risk recalculate. Appeler la route globale ou creer la route backend |
| 8 | `stores/users.ts` | 252 | `DELETE /users/permissions/{id}` | `DELETE /users/{user}/permissions/{permission}` | Ajouter `userId` en parametre de `removePermission` |

**Impact** : Ces 8 appels retournent tous 404/405 en production. Les fonctionnalites correspondantes sont completement cassees.

**Detail correction #3-6 (suppliers)** :
Le probleme est que `updateContract(contractId, data)` et `deleteContract(contractId)` n'ont pas acces au `supplierId`. Solutions possibles :
- **Option A (frontend)** : Changer la signature pour inclure `supplierId` : `updateContract(supplierId, contractId, data)`
- **Option B (backend)** : Ajouter des routes plates : `PUT /contracts/{contract}`, `DELETE /contracts/{contract}`, etc.
- **Recommandation** : Option A (respecte le design REST du backend)

**Detail correction #7 (recalculate)** :
Le frontend appelle per-risk mais le backend est global uniquement. Solutions :
- **Option A (backend)** : Ajouter `POST /risks/{risk}/recalculate` qui recalcule UN seul risk
- **Option B (frontend)** : Appeler la route globale (recalcule TOUS les risks - moins performant)
- **Recommandation** : Option A (plus chirurgical)

### 1.2 URLs incorrectes dans les pages (5 fixes)

| # | Fichier | Ligne | Frontend appelle | Backend attend | Correction |
|---|---------|-------|-----------------|----------------|------------|
| 9 | `risks/dashboard/page.tsx` | 44 | `GET /risks/dashboard/stats` | `GET /risks/dashboard` | Retirer `/stats` de l'URL |
| 10 | `suppliers/dashboard/page.tsx` | 44 | `GET /suppliers/dashboard/stats` | `GET /suppliers-dashboard` | Changer URL completement |
| 11 | `suppliers/invoices/page.tsx` | 52 | `GET /invoices` | N'EXISTE PAS (global) | Voir section 2.1 |
| 12 | `suppliers/contracts/page.tsx` | 52 | `GET /contracts` | N'EXISTE PAS (global) | Voir section 2.1 |
| 13 | `risks/actions/page.tsx` | 53 | `GET /risks/actions` | N'EXISTE PAS (global) | Voir section 2.1 |

### 1.3 Access management - endpoints manquants

| # | Fichier | Ligne | Frontend appelle | Backend attend |
|---|---------|-------|-----------------|----------------|
| 14 | `access-management-panel.tsx` | 78-81 | `GET /settings/lists?type=department` attend `{ data: [...] }` | Backend retourne `{ settings: { department: [...] } }` |
| 15 | `access-management-panel.tsx` | 111 | `POST /governance/items/{id}/access` | Route non exposee dans api.php |
| 16 | `access-management-panel.tsx` | 112 | `POST /suppliers/{id}/access` | Route non exposee dans api.php |
| 17 | `access-management-panel.tsx` | 137 | `DELETE /governance/items/{id}/access/{accessId}` | Route non exposee dans api.php |
| 18 | `access-management-panel.tsx` | 138 | `DELETE /suppliers/{id}/access/{accessId}` | Route non exposee dans api.php |

**Note** : Le backend a des methodes `manageAccess()` dans GovernanceController et SupplierController mais elles ne sont PAS enregistrees dans routes/api.php. Il faut les exposer.

---

## 2. Routes backend manquantes

### 2.1 Routes globales (listings cross-supplier/cross-risk)

Ces 3 pages frontend ont besoin de lister TOUS les items, pas ceux d'un seul parent :

| Route manquante | Page frontend | Controller existant | Solution recommandee |
|----------------|--------------|--------------------|--------------------|
| `GET /invoices` | `suppliers/invoices/page.tsx` | `SupplierInvoiceController` | **Backend** : Ajouter methode `allInvoices()` dans un controller global, ou query sur SupplierInvoice::all() |
| `GET /contracts` | `suppliers/contracts/page.tsx` | `SupplierContractController` | **Backend** : Idem, `allContracts()` |
| `GET /risks/actions` | `risks/actions/page.tsx` | `RiskActionController` | **Backend** : Ajouter route dans le prefix `risks` (pas sous `risks/{risk}`) |

**Alternative frontend** : Charger tous les suppliers puis agreger. Mauvaise idee (N+1 requetes).

### 2.2 Routes de dependencies (work items)

| Route manquante | Frontend appelle | Solution |
|----------------|-----------------|----------|
| `POST /workitems/{workitem}/dependencies/{dependencyId}` | `stores/workitems.ts:399` | **Backend** : Creer route + methode dans WorkItemController ou nouveau DependencyController |
| `DELETE /workitems/{workitem}/dependencies/{dependencyId}` | `stores/workitems.ts:412` | **Backend** : Idem |

Le model `TaskDependency` existe en base (`task_dependencies` table) mais aucune route API n'est exposee pour le CRUD.

### 2.3 Routes d'acces (governance + suppliers)

| Route manquante | Solution |
|----------------|----------|
| `POST /governance/items/{item}/access` | **Backend** : Exposer `GovernanceController::manageAccess()` dans routes |
| `DELETE /governance/items/{item}/access/{accessId}` | **Backend** : Creer methode ou route DELETE |
| `POST /suppliers/{supplier}/access` | **Backend** : Exposer `SupplierController::manageAccess()` dans routes |
| `DELETE /suppliers/{supplier}/access/{accessId}` | **Backend** : Creer methode ou route DELETE |

### 2.4 Route recalculate per-risk

| Route manquante | Solution |
|----------------|----------|
| `POST /risks/{risk}/recalculate` | **Backend** : Ajouter dans routes sous `risks/{risk}` prefix, appeler `RiskScoringService` sur un seul risk |

---

## 3. Mock data en dur dans le frontend (catch blocks)

**9 fichiers** contiennent des donnees fictives dans des blocs `catch`. En production, si l'API echoue (404, mauvaise URL, etc.), ces pages affichent des FAUSSES donnees au lieu d'une erreur.

| # | Fichier | Lignes | Donnees mock | Probleme |
|---|---------|--------|-------------|----------|
| 1 | `components/dashboard/alerts-panel.tsx` | 66-94 | 3 alertes fictives | URL OK, fallback de dev |
| 2 | `components/dashboard/area-stats.tsx` | 55-96 | 4 departements fictifs | URL OK, fallback de dev |
| 3 | `suppliers/invoices/page.tsx` | 56-117 | 5 factures fictives | **URL `/invoices` n'existe pas** -> mock TOUJOURS affiche |
| 4 | `risks/actions/page.tsx` | 57-114 | 5 actions fictives | **URL `/risks/actions` n'existe pas** -> mock TOUJOURS affiche |
| 5 | `risks/controls/page.tsx` | 55-122 | 6 controles fictifs | URL OK mais response format peut differer |
| 6 | `risks/dashboard/page.tsx` | 48-73 | Stats risques fictives | **URL incorrecte** `/risks/dashboard/stats` -> mock TOUJOURS affiche |
| 7 | `tasks/dashboard/page.tsx` | 42-69 | Stats taches fictives | URL OK |
| 8 | `suppliers/dashboard/page.tsx` | 48-76 | Stats fournisseurs fictives | **URL incorrecte** `/suppliers/dashboard/stats` -> mock TOUJOURS affiche |
| 9 | `governance/dashboard/page.tsx` | 40-63 | Stats governance fictives | URL OK |
| 10 | `access-management-panel.tsx` | 89-94 | Departements/entites fictifs | **Response format incorrect** -> mock TOUJOURS affiche |

**Pages qui affichent TOUJOURS du mock** (4 pages critiques) :
- `suppliers/invoices/page.tsx` (route globale n'existe pas)
- `risks/actions/page.tsx` (route globale n'existe pas)
- `risks/dashboard/page.tsx` (URL incorrecte)
- `suppliers/dashboard/page.tsx` (URL incorrecte)

**Action** : Supprimer TOUS les mock data des catch blocks. Remplacer par `setData([])` ou afficher un ErrorState.

---

## 4. Ecarts format de reponse API

Le backend et le frontend ne s'attendent pas au meme format JSON dans certains cas.

| Endpoint | Frontend attend | Backend retourne | Fichier frontend |
|----------|----------------|-----------------|-----------------|
| `GET /settings/lists` | `{ data: [...] }` | `{ settings: { type: [...] } }` (grouped) | `access-management-panel.tsx:78` |
| `GET /settings/lists?type=X` | `{ data: [{name}] }` | `{ settings: { type: [...] } }` | `access-management-panel.tsx:78-84` |
| `GET /risks/controls/library` | Page attend `{ data: [...] }` | Backend retourne paginated ou raw array | `risks/controls/page.tsx:51` |

**Note** : Les stores Zustand ont deja ete corriges dans la Phase 1 precedente pour les format `response.data.data` -> `response.data.xxx`. Mais les pages qui appellent l'API directement (pas via store) n'ont PAS ete corrigees.

---

## 5. Ecarts CDC vs Code

### 5.1 Features CDC implementees mais pas connectees

| Feature CDC | Status Backend | Status Frontend | Gap |
|------------|---------------|----------------|-----|
| Dependencies work items | Table `task_dependencies` existe, model existe | UI existe (DependenciesPanel) | **Routes API manquantes** |
| Access management governance | Methode `manageAccess()` existe | UI existe (AccessManagementPanel) | **Routes non exposees** |
| Access management suppliers | Methode `manageAccess()` existe | UI existe | **Routes non exposees** |
| Recalculate risk scores | `RiskScoringService` existe | Bouton dans risk detail appelle store | **Route per-risk manquante** |
| Global invoices listing | Model `SupplierInvoice` existe | Page invoices existe | **Route globale manquante** |
| Global contracts listing | Model `SupplierContract` existe | Page contracts existe | **Route globale manquante** |
| Global risk actions listing | Model `RiskAction` existe | Page actions existe | **Route globale manquante** |

### 5.2 Features CDC a verifier

| Feature CDC | Regle | Status |
|------------|-------|--------|
| RAG automatique (RG-BOW-001) | Jamais manuel, calcule par deadline | A verifier dans `RAGCalculationService` |
| Cumul controles max 70% (RG-BOW-004) | Cap de reduction | A verifier dans `RiskScoringService` |
| Alerte contrat 90j (RG-BOW-007) | Notification avant expiration | A verifier - scheduler Laravel |
| Import deduplication (RG-BOW-008) | Pas de doublons users/suppliers | A verifier dans `ImportNormalizationService` |
| Encodage UTF-8 (RG-BOW-009) | Normalisation import | A verifier |
| Conversion multi-devises GBP (RG-BOW-013) | Taux de change | Pas visible dans le code frontend |
| Permissions par departement + theme (RG-BOW-011) | 3 couches | Backend existe, frontend a verifier |

### 5.3 Features CDC non implementees (frontend)

| Feature | Description | Priorite |
|---------|-----------|----------|
| Heatmap 5x5 interactive | Matrice risques cliquable | Existe (page heatmap) mais a connecter |
| Export invoices | `GET /export/invoices` | Route n'existe PAS dans backend (seulement workitems, governance, suppliers, risks) |
| Supplier file attachments download | `GET /suppliers/{id}/files/{filename}` | Route de download manquante (seul index + store + destroy existent) |
| Risk theme reorder | `POST /risks/themes/reorder` | Route existe backend mais pas de UI |
| Risk category CRUD | Routes existent | Pas de UI d'admin pour categories |
| Control library CRUD (admin) | Routes existent | Page affiche la liste mais pas de creation (bouton disabled) |

---

## 6. Fichiers backend a creer/modifier

### 6.1 Nouvelles routes a ajouter dans `routes/api.php`

```php
// Dans le prefix 'risks'
Route::get('/actions', [RiskActionController::class, 'all']);  // Global listing

// Sous risks/{risk}
Route::post('/recalculate', [RiskController::class, 'recalculateSingle']);

// Routes globales
Route::get('/contracts', [SupplierContractController::class, 'all']);
Route::get('/invoices', [SupplierInvoiceController::class, 'all']);

// Work item dependencies
Route::prefix('workitems/{workitem}')->group(function () {
    Route::post('/dependencies/{dependency}', [WorkItemController::class, 'addDependency']);
    Route::delete('/dependencies/{dependency}', [WorkItemController::class, 'removeDependency']);
});

// Governance access
Route::prefix('governance/items/{item}')->group(function () {
    Route::post('/access', [GovernanceController::class, 'addAccess']);
    Route::delete('/access/{access}', [GovernanceController::class, 'removeAccess']);
});

// Supplier access
Route::prefix('suppliers/{supplier}')->group(function () {
    Route::post('/access', [SupplierController::class, 'addAccess']);
    Route::delete('/access/{access}', [SupplierController::class, 'removeAccess']);
});
```

### 6.2 Methodes backend a ajouter

| Controller | Methode | Description |
|-----------|---------|------------|
| `RiskActionController` | `all()` | Lister toutes les actions de tous les risks (avec filtres status, priority) |
| `RiskController` | `recalculateSingle(Risk $risk)` | Recalculer les scores d'un seul risk |
| `SupplierContractController` | `all()` | Lister tous les contrats de tous les suppliers (avec join supplier name) |
| `SupplierInvoiceController` | `all()` | Lister toutes les factures de tous les suppliers |
| `WorkItemController` | `addDependency(WorkItem, WorkItem)` | Creer un TaskDependency |
| `WorkItemController` | `removeDependency(WorkItem, WorkItem)` | Supprimer un TaskDependency |
| `GovernanceController` | `addAccess(GovernanceItem)` | Ajouter un acces |
| `GovernanceController` | `removeAccess(GovernanceItem, GovernanceItemAccess)` | Supprimer un acces |
| `SupplierController` | `addAccess(Supplier)` | Ajouter un acces |
| `SupplierController` | `removeAccess(Supplier, SupplierAccess)` | Supprimer un acces |

---

## 7. Fichiers frontend a modifier

### 7.1 Stores (corrections URLs)

| Fichier | Corrections |
|---------|------------|
| `stores/workitems.ts` | L216: `api.patch('/workitems/{id}/status')` -> `api.put('/workitems/{id}', { current_status: status })` |
| `stores/suppliers.ts` | L137: `/suppliers/stats` -> `/suppliers-dashboard` |
| `stores/suppliers.ts` | L276: `/contracts/{id}` -> `/suppliers/{supplierId}/contracts/{id}` (changer signature) |
| `stores/suppliers.ts` | L295: `/contracts/{id}` -> `/suppliers/{supplierId}/contracts/{id}` (changer signature) |
| `stores/suppliers.ts` | L346: `/invoices/{id}` -> `/suppliers/{supplierId}/invoices/{id}` (changer signature) |
| `stores/suppliers.ts` | L365: `/invoices/{id}` -> `/suppliers/{supplierId}/invoices/{id}` (changer signature) |
| `stores/risks.ts` | L306: `/risks/{id}/recalculate` -> `/risks/recalculate` (ou attendre route backend) |
| `stores/users.ts` | L252: `/users/permissions/{id}` -> `/users/{userId}/permissions/{id}` (changer signature) |

### 7.2 Pages (corrections URLs + suppression mock data)

| Fichier | Corrections |
|---------|------------|
| `risks/dashboard/page.tsx` | L44: `/risks/dashboard/stats` -> `/risks/dashboard`. Supprimer mock L48-73 |
| `suppliers/dashboard/page.tsx` | L44: `/suppliers/dashboard/stats` -> `/suppliers-dashboard`. Supprimer mock L48-76 |
| `suppliers/invoices/page.tsx` | L52: `/invoices` -> attendre route backend globale. Supprimer mock L56-117 |
| `suppliers/contracts/page.tsx` | L52: `/contracts` -> attendre route backend globale. Supprimer mock |
| `risks/actions/page.tsx` | L53: `/risks/actions` -> attendre route backend globale. Supprimer mock L57-114 |
| `risks/controls/page.tsx` | L51: verifier format reponse. Supprimer mock L55-122 |
| `alerts-panel.tsx` | Supprimer mock L66-94 |
| `area-stats.tsx` | Supprimer mock L55-96 |
| `tasks/dashboard/page.tsx` | Supprimer mock L42-69 |
| `governance/dashboard/page.tsx` | Supprimer mock L40-63 |
| `access-management-panel.tsx` | L78-84: format reponse settings. Supprimer mock L89-94 |

---

## 8. Plan d'execution recommande

### Phase 1 : Backend (creer les routes manquantes)
1. Ajouter les 6 nouvelles routes dans `routes/api.php`
2. Creer les methodes `all()` dans RiskActionController, SupplierContractController, SupplierInvoiceController
3. Creer `recalculateSingle()` dans RiskController
4. Creer les methodes dependency dans WorkItemController
5. Exposer les routes access pour governance et suppliers
6. Ecrire les tests Pest pour chaque nouvelle route

### Phase 2 : Frontend (corriger les URLs)
1. Corriger les 8 URLs dans les stores
2. Corriger les 5 URLs dans les pages
3. Corriger le format de reponse dans access-management-panel
4. Supprimer TOUS les mock data des catch blocks (remplacer par `setData([])`)

### Phase 3 : Verification
1. `npx tsc --noEmit` (0 erreurs)
2. `php artisan route:list` (verifier toutes les routes)
3. `php artisan test` (tous les tests passent)
4. Test integration : chaque page charge des vraies donnees
