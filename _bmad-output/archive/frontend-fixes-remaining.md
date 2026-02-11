# Corrections frontend restantes - Tavira BOW

**Date** : 2026-02-10
**Priorite** : HAUTE - a faire apres ou en parallele des corrections backend

---

## 1. Corrections dans les stores Zustand

### 1.1 `src/stores/workitems.ts` - Ligne 216

**Probleme** : `api.patch('/workitems/${id}/status', { status })` - route PATCH n'existe pas
**Correction** : Utiliser PUT sur la resource complete

```typescript
// AVANT (ligne 216-218)
const response = await api.patch<{ work_item: WorkItem }>(
  `/workitems/${id}/status`,
  { status }
)

// APRES
const response = await api.put<{ work_item: WorkItem }>(
  `/workitems/${id}`,
  { current_status: status }
)
```

### 1.2 `src/stores/suppliers.ts` - Ligne 137

**Probleme** : `api.get('/suppliers/stats')` - URL incorrecte
**Correction** :

```typescript
// AVANT
const response = await api.get<SupplierDashboardStats>('/suppliers/stats')

// APRES
const response = await api.get<SupplierDashboardStats>('/suppliers-dashboard')
```

### 1.3 `src/stores/suppliers.ts` - Lignes 272-305

**Probleme** : `updateContract` et `deleteContract` utilisent `/contracts/{id}` (URL plate) mais backend attend `/suppliers/{supplier}/contracts/{contract}` (URL nestee)

**Correction** : Changer la signature des methodes pour inclure `supplierId`

```typescript
// AVANT - Interface (lignes 56-60)
updateContract: (contractId: number, data: Partial<ContractFormData>) => Promise<void>
deleteContract: (contractId: number) => Promise<void>

// APRES
updateContract: (supplierId: number, contractId: number, data: Partial<ContractFormData>) => Promise<void>
deleteContract: (supplierId: number, contractId: number) => Promise<void>

// AVANT - Implementation (lignes 272-295)
updateContract: async (contractId, data) => {
  const response = await api.put<{ contract: SupplierContract }>(
    `/contracts/${contractId}`, data
  )

deleteContract: async (contractId) => {
  await api.delete(`/contracts/${contractId}`)

// APRES
updateContract: async (supplierId, contractId, data) => {
  const response = await api.put<{ contract: SupplierContract }>(
    `/suppliers/${supplierId}/contracts/${contractId}`, data
  )

deleteContract: async (supplierId, contractId) => {
  await api.delete(`/suppliers/${supplierId}/contracts/${contractId}`)
```

**Impact** : Verifier tous les appels a `updateContract` et `deleteContract` dans les composants et ajouter le `supplierId`.

### 1.4 `src/stores/suppliers.ts` - Lignes 342-375

**Meme probleme** pour `updateInvoice` et `deleteInvoice`.

```typescript
// AVANT
updateInvoice: (invoiceId: number, data: Partial<InvoiceFormData>) => Promise<void>
deleteInvoice: (invoiceId: number) => Promise<void>

// APRES
updateInvoice: (supplierId: number, invoiceId: number, data: Partial<InvoiceFormData>) => Promise<void>
deleteInvoice: (supplierId: number, invoiceId: number) => Promise<void>

// URLs
`/suppliers/${supplierId}/invoices/${invoiceId}`
```

### 1.5 `src/stores/risks.ts` - Ligne 306

**Probleme** : `api.post('/risks/${id}/recalculate')` - route per-risk n'existe pas

**Correction temporaire** (avant ajout route backend) :
```typescript
// APRES (utiliser route globale)
const response = await api.post<{ message: string; count: number }>('/risks/recalculate')
// Puis re-fetch le risk specifique
await get().fetchById(id)
```

**Correction definitive** (apres ajout route backend) :
Garder l'URL per-risk une fois la route creee.

### 1.6 `src/stores/users.ts` - Ligne 252

**Probleme** : `api.delete('/users/permissions/${permissionId}')` - URL plate, backend attend nestee

**Correction** : Ajouter `userId` en parametre

```typescript
// AVANT - Interface
removePermission: (permissionId: number) => Promise<void>

// APRES
removePermission: (userId: number, permissionId: number) => Promise<void>

// AVANT - Implementation
await api.delete(`/users/permissions/${permissionId}`)

// APRES
await api.delete(`/users/${userId}/permissions/${permissionId}`)
```

---

## 2. Corrections dans les pages (URLs API directes)

### 2.1 `src/app/(dashboard)/risks/dashboard/page.tsx` - Ligne 44

```typescript
// AVANT
const response = await api.get<{ data: RiskStats }>('/risks/dashboard/stats')

// APRES
const response = await api.get<{ data: RiskStats }>('/risks/dashboard')
```

### 2.2 `src/app/(dashboard)/suppliers/dashboard/page.tsx` - Ligne 44

```typescript
// AVANT
const response = await api.get<{ data: SupplierStats }>('/suppliers/dashboard/stats')

// APRES
const response = await api.get<SupplierStats>('/suppliers-dashboard')
```

**Note** : Verifier aussi le format de reponse - le backend peut retourner directement l'objet stats, pas `{ data: stats }`.

### 2.3 `src/app/(dashboard)/suppliers/invoices/page.tsx` - Ligne 52

```typescript
// AVANT
const response = await api.get<{ data: Invoice[] }>('/invoices')

// APRES (une fois route backend creee)
const response = await api.get<{ data: Invoice[] }>('/invoices')
// OU si la route utilise la pagination Laravel standard :
const response = await api.get<PaginatedResponse<Invoice>>('/invoices')
```

### 2.4 `src/app/(dashboard)/suppliers/contracts/page.tsx` - Ligne 52

```typescript
// AVANT
const response = await api.get<{ data: Contract[] }>('/contracts')

// APRES (une fois route backend creee)
const response = await api.get<{ data: Contract[] }>('/contracts')
```

### 2.5 `src/app/(dashboard)/risks/actions/page.tsx` - Ligne 53

```typescript
// AVANT
const response = await api.get<{ data: RiskAction[] }>('/risks/actions')

// APRES (une fois route backend creee, note le /all pour eviter conflit)
const response = await api.get<{ data: RiskAction[] }>('/risks/actions/all')
```

### 2.6 `src/components/shared/access-management-panel.tsx` - Lignes 78-84

```typescript
// AVANT - attend { data: [...] }
const response = await api.get<{ data: Array<{ name: string }> }>(
  '/settings/lists?type=department'
)
setDepartments(response.data.data.map((d) => d.name))

// APRES - backend retourne { settings: { department: [...] } }
const response = await api.get<{ settings: Record<string, Array<{ value: string; label: string }>> }>(
  '/settings/lists?type=department'
)
const deptList = response.data.settings?.department || []
setDepartments(deptList.map((d) => d.label || d.value))

// OU utiliser l'endpoint specifique par type :
const response = await api.get<{ type: string; values: Array<{ value: string; label: string }> }>(
  '/settings/lists/type/department'
)
setDepartments(response.data.values.map((d) => d.label || d.value))
```

---

## 3. Suppression des mock data (catch blocks)

Pour chaque fichier, remplacer le contenu du catch par un state vide ou un log d'erreur.

| Fichier | Lignes mock | Remplacer par |
|---------|-------------|--------------|
| `components/dashboard/alerts-panel.tsx` | 66-94 | `setAlerts([])` |
| `components/dashboard/area-stats.tsx` | 55-96 | `setAreas([])` |
| `suppliers/invoices/page.tsx` | 56-117 | `setInvoices([])` |
| `risks/actions/page.tsx` | 57-114 | `setActions([])` |
| `risks/controls/page.tsx` | 55-122 | `setControls([])` |
| `risks/dashboard/page.tsx` | 48-73 | `setStats(null)` |
| `tasks/dashboard/page.tsx` | 42-69 | `setStats(null)` |
| `suppliers/dashboard/page.tsx` | 48-76 | `setStats(null)` |
| `governance/dashboard/page.tsx` | 40-63 | `setStats(null)` |
| `access-management-panel.tsx` | 89-94 | `setDepartments([])` / `setEntities([])` |

---

## 4. Ordre d'execution

1. **D'abord** : Corriger les URLs des stores (section 1) - ca debloque les pages detail
2. **Ensuite** : Corriger les URLs des pages dashboard (section 2.1-2.2)
3. **En parallele backend** : Creer les routes globales (invoices, contracts, actions)
4. **Apres backend** : Corriger les URLs des pages listings (section 2.3-2.5)
5. **Dernier** : Supprimer les mock data (section 3)
6. **Verification** : `npx tsc --noEmit` + test manuel de chaque page
