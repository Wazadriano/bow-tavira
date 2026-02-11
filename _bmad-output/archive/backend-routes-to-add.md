# Routes backend a ajouter - Tavira BOW

**Date** : 2026-02-10
**Priorite** : HAUTE - bloque le fonctionnement du frontend

---

## 1. Routes globales (listings cross-parent)

Ces routes sont necessaires pour les pages qui listent TOUS les items, pas ceux d'un seul parent.

### 1.1 GET /invoices (global)

**Fichier** : `SupplierInvoiceController.php`
**Methode a ajouter** : `all(Request $request)`
**Page frontend** : `suppliers/invoices/page.tsx`

```php
// Route dans api.php (dans le groupe auth:sanctum)
Route::get('/invoices', [SupplierInvoiceController::class, 'all']);

// Methode dans SupplierInvoiceController
public function all(Request $request)
{
    $query = SupplierInvoice::with('supplier:id,name');

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('invoice_ref', 'ilike', "%{$search}%")
              ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'ilike', "%{$search}%"));
        });
    }
    if ($request->filled('date_from')) {
        $query->where('invoice_date', '>=', $request->date_from);
    }
    if ($request->filled('date_to')) {
        $query->where('invoice_date', '<=', $request->date_to);
    }

    return $query->orderBy('invoice_date', 'desc')->paginate($request->per_page ?? 20);
}
```

### 1.2 GET /contracts (global)

**Fichier** : `SupplierContractController.php`
**Methode a ajouter** : `all(Request $request)`
**Page frontend** : `suppliers/contracts/page.tsx`

```php
// Route
Route::get('/contracts', [SupplierContractController::class, 'all']);

// Methode
public function all(Request $request)
{
    $query = SupplierContract::with('supplier:id,name');

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    if ($request->boolean('expiring_soon')) {
        $query->where('end_date', '<=', now()->addDays(90))
              ->where('end_date', '>=', now());
    }
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('contract_ref', 'ilike', "%{$search}%")
              ->orWhere('description', 'ilike', "%{$search}%")
              ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'ilike', "%{$search}%"));
        });
    }

    return $query->orderBy('end_date', 'asc')->paginate($request->per_page ?? 20);
}
```

### 1.3 GET /risks/actions (global)

**Fichier** : `RiskActionController.php`
**Methode a ajouter** : `all(Request $request)`
**Page frontend** : `risks/actions/page.tsx`

```php
// Route (dans le prefix 'risks', AVANT la resource risks)
Route::get('/risks/actions/all', [RiskActionController::class, 'all']);

// Methode
public function all(Request $request)
{
    $query = RiskAction::with('risk:id,ref_no,name');

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    if ($request->filled('priority')) {
        $query->where('priority', $request->priority);
    }
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('title', 'ilike', "%{$search}%")
              ->orWhereHas('risk', fn($rq) => $rq->where('name', 'ilike', "%{$search}%"));
        });
    }

    return $query->orderByRaw("CASE WHEN status = 'overdue' THEN 0 WHEN status = 'open' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END")
                 ->orderBy('due_date', 'asc')
                 ->paginate($request->per_page ?? 20);
}
```

**Note URL** : Utiliser `/risks/actions/all` et pas `/risks/actions` pour eviter conflit avec `risks/{risk}` (Laravel interpreterait "actions" comme un ID de risk).

---

## 2. Route recalculate per-risk

**Fichier** : `RiskController.php`
**Page frontend** : `risks/[id]/page.tsx` (via store `risks.ts:306`)

```php
// Route (dans le prefix risks/{risk})
Route::post('/recalculate', [RiskController::class, 'recalculateSingle']);

// Methode
public function recalculateSingle(Risk $risk)
{
    $service = app(RiskScoringService::class);
    $service->calculateScores($risk);
    $risk->refresh();

    return response()->json(['risk' => $risk->load(['controls.control', 'actions', 'category.theme', 'owner'])]);
}
```

---

## 3. Routes dependencies (work items)

**Fichier** : `WorkItemController.php`
**Page frontend** : `tasks/[id]/page.tsx` (via `DependenciesPanel`)
**Table** : `task_dependencies`

```php
// Routes (dans le prefix workitems/{workitem})
Route::post('/dependencies/{dependency}', [WorkItemController::class, 'addDependency']);
Route::delete('/dependencies/{dependency}', [WorkItemController::class, 'removeDependency']);

// Methodes
public function addDependency(Request $request, WorkItem $workitem, WorkItem $dependency)
{
    if ($workitem->id === $dependency->id) {
        return response()->json(['message' => 'Cannot depend on itself'], 422);
    }

    // Verifier pas de dependance circulaire
    $existing = TaskDependency::where('work_item_id', $workitem->id)
        ->where('depends_on_id', $dependency->id)
        ->first();

    if ($existing) {
        return response()->json(['message' => 'Dependency already exists'], 409);
    }

    $dep = TaskDependency::create([
        'work_item_id' => $workitem->id,
        'depends_on_id' => $dependency->id,
        'dependency_type' => $request->input('dependency_type', 'blocks'),
    ]);

    return response()->json(['dependency' => $dep->load('dependsOn')], 201);
}

public function removeDependency(WorkItem $workitem, WorkItem $dependency)
{
    TaskDependency::where('work_item_id', $workitem->id)
        ->where('depends_on_id', $dependency->id)
        ->delete();

    return response()->noContent();
}
```

---

## 4. Routes access management

### 4.1 Governance access

**Fichier** : `GovernanceController.php`
**Table** : `governance_item_access`

```php
// Routes (dans le prefix governance/items/{item})
Route::post('/access', [GovernanceController::class, 'addAccess']);
Route::delete('/access/{access}', [GovernanceController::class, 'removeAccess']);

// Methodes
public function addAccess(Request $request, GovernanceItem $item)
{
    $validated = $request->validate([
        'department' => 'required|string',
        'access_level' => 'required|in:read,write,admin',
    ]);

    $access = $item->access()->create($validated);

    return response()->json(['access' => $access], 201);
}

public function removeAccess(GovernanceItem $item, GovernanceItemAccess $access)
{
    if ($access->governance_item_id !== $item->id) {
        return response()->json(['message' => 'Access does not belong to this item'], 403);
    }

    $access->delete();
    return response()->noContent();
}
```

### 4.2 Supplier access

**Fichier** : `SupplierController.php`
**Table** : `supplier_access`

```php
// Routes (dans le prefix suppliers/{supplier})
Route::post('/access', [SupplierController::class, 'addAccess']);
Route::delete('/access/{access}', [SupplierController::class, 'removeAccess']);

// Methodes
public function addAccess(Request $request, Supplier $supplier)
{
    $validated = $request->validate([
        'entity' => 'required|string',
        'access_level' => 'required|in:read,write,admin',
    ]);

    $access = $supplier->access()->create($validated);

    return response()->json(['access' => $access], 201);
}

public function removeAccess(Supplier $supplier, SupplierAccess $access)
{
    if ($access->supplier_id !== $supplier->id) {
        return response()->json(['message' => 'Access does not belong to this supplier'], 403);
    }

    $access->delete();
    return response()->noContent();
}
```

---

## 5. Route export invoices (manquante)

**Fichier** : `ImportExportController.php`

Le backend exporte workitems, governance, suppliers, risks mais PAS les invoices.

```php
// Route
Route::get('/export/invoices', [ImportExportController::class, 'exportInvoices']);

// Methode a creer
public function exportInvoices()
{
    return Excel::download(new InvoicesExport(), 'invoices.xlsx');
}
```

Necessite aussi de creer `app/Exports/InvoicesExport.php`.

---

## 6. Supplier file download (manquant)

Le backend n'a pas de route de download pour les fichiers suppliers :

```php
// Routes existantes pour suppliers (manque le show/download)
Route::get('/files', [SupplierFileController::class, 'index']);     // OK
Route::post('/files', [SupplierFileController::class, 'store']);    // OK
Route::delete('/files/{file}', [SupplierFileController::class, 'destroy']); // OK
// MANQUE : Route::get('/files/{file}/download', [SupplierFileController::class, 'download']);
```

---

## 7. Resume des modifications dans routes/api.php

```php
// === AJOUTS A FAIRE ===

// Dans le groupe auth:sanctum, AVANT les resources :

// Routes globales listings
Route::get('/invoices', [SupplierInvoiceController::class, 'all']);
Route::get('/contracts', [SupplierContractController::class, 'all']);

// Dans le prefix 'risks' (ligne ~174), AVANT la resource risks :
Route::get('/risks/actions/all', [RiskActionController::class, 'all']);

// Dans le prefix workitems/{workitem} (ligne ~77) :
Route::post('/dependencies/{dependency}', [WorkItemController::class, 'addDependency']);
Route::delete('/dependencies/{dependency}', [WorkItemController::class, 'removeDependency']);

// Dans le prefix risks/{risk} (ligne ~205) :
Route::post('/recalculate', [RiskController::class, 'recalculateSingle']);

// Dans le prefix governance/items/{item} (ligne ~115) :
Route::post('/access', [GovernanceController::class, 'addAccess']);
Route::delete('/access/{access}', [GovernanceController::class, 'removeAccess']);

// Dans le prefix suppliers/{supplier} (ligne ~133) :
Route::post('/access', [SupplierController::class, 'addAccess']);
Route::delete('/access/{access}', [SupplierController::class, 'removeAccess']);

// Supplier file download
Route::get('/files/{file}/download', [SupplierFileController::class, 'download']);

// Export invoices (ligne ~261)
Route::get('/invoices', [ImportExportController::class, 'exportInvoices']);
```

---

## 8. Tests Pest a ecrire

Pour chaque nouvelle route, un test minimum :

| Test | Verifie |
|------|---------|
| `test_can_list_all_invoices` | GET /invoices retourne 200 + paginated |
| `test_can_list_all_contracts` | GET /contracts retourne 200 + paginated |
| `test_can_list_all_risk_actions` | GET /risks/actions/all retourne 200 + paginated |
| `test_can_recalculate_single_risk` | POST /risks/{id}/recalculate retourne 200 + risk |
| `test_can_add_dependency` | POST /workitems/{id}/dependencies/{id} retourne 201 |
| `test_cannot_self_dependency` | POST /workitems/{id}/dependencies/{same_id} retourne 422 |
| `test_can_remove_dependency` | DELETE retourne 204 |
| `test_can_add_governance_access` | POST retourne 201 |
| `test_can_remove_governance_access` | DELETE retourne 204 |
| `test_can_add_supplier_access` | POST retourne 201 |
| `test_can_remove_supplier_access` | DELETE retourne 204 |
