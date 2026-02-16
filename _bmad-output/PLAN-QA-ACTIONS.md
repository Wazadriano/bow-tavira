# PLAN QA - Actions a Realiser

Date : 2026-02-11
Source : Audit QA complet (BOW-QA) - CDC Fonctionnel + Technique vs code reel
Statut : EN ATTENTE
Prochaine session : reprendre avec `Corrige le plan QA` ou `Lance P0-1`

---

## Contexte

Audit croise complet du projet BOW :
- 218 tests Pest passed, 8 skipped, 0 failed
- Pint PASS, Larastan PASS, TSC PASS
- Couverture controllers : 15% | Models : 0% | Routes : 24% | Frontend : 0%
- 12/13 regles de gestion implementees (RG-BOW-008 en TDD RED)

---

## Tableau Recapitulatif

| # | Tache | Agent(s) | Type | Depend | Effort | Statut |
|---|-------|----------|------|--------|--------|--------|
| **P0-1** | Corriger store frontend export (polling async) | **BOW-FRONT** | Fix bug | - | 1h | A FAIRE |
| **P0-2** | Implementer detectDuplicates() RG-BOW-008 | **BOW-BACK** | TDD impl | - | 2h | A FAIRE |
| **P0-3** | Fixer RAGGovernanceTest (5 tests skipped) | **BOW-BACK** | Fix bug | - | 30min | A FAIRE |
| **P1-1** | Tests matrice permissions 3 couches | **BOW-QA** + **BOW-BACK** | Tests | P0-3 | 1j | A FAIRE |
| **P1-2** | Tests CurrencyConversionService | **BOW-BACK** | Tests | - | 2h | A FAIRE |
| **P1-3** | Tests 15 Form Requests | **BOW-QA** + **BOW-BACK** | Tests | - | 1j | A FAIRE |
| **P1-4** | Tests 4 commands Artisan | **BOW-BACK** | Tests | - | 3h | A FAIRE |
| **P2-1** | Tests CRUD 10 controllers principaux | **BOW-QA** + **BOW-BACK** | Tests | P1-1, P1-3 | 2-3j | A FAIRE |
| **P2-2** | Validation file size 10MB (4 controllers) | **BOW-BACK** + **BOW-SEC** | Fix CDC | - | 30min | A FAIRE |
| **P2-3** | Setup Vitest + premiers tests frontend | **BOW-FRONT** + **BOW-QA** | Infra + Tests | P0-1 | 2j | A FAIRE |
| **P2-4** | Tests 3 notifications manquantes | **BOW-BACK** | Tests | - | 2h | A FAIRE |

### Agents concernes et leur role

| Agent | Taches | Role |
|-------|--------|------|
| **BOW-FRONT** | P0-1, P2-3 | Correction store export async, setup Vitest, tests composants |
| **BOW-BACK** | P0-2, P0-3, P1-1, P1-2, P1-3, P1-4, P2-1, P2-2, P2-4 | Implementation TDD, correction services, ecriture tests Pest |
| **BOW-QA** | P1-1, P1-3, P2-1, P2-3 | Definition scenarios, edge cases, matrice permissions, review |
| **BOW-SEC** | P2-2 | Validation limites fichiers (OWASP file upload) |

### Graphe de dependances

```
P0-1 (frontend export) ─────────────────────> P2-3 (setup Vitest)
P0-2 (detectDuplicates) ──> independant
P0-3 (fix RAG governance) ──> P1-1 (permissions matrix)
P1-2 (currency tests) ──> independant
P1-3 (form request tests) ──┐
P1-4 (commands tests) ──────┤
                            └──> P2-1 (CRUD controller tests)
P2-2 (file size) ──> independant
P2-4 (notif tests) ──> independant
```

---

## P0 - BLOQUANT PRODUCTION

### P0-1 : Corriger le store frontend export (polling async)

- **Probleme** : Les 5 routes `/api/export/{type}` retournent maintenant 202 + JSON (async) au lieu d'un BinaryFileResponse (sync). Le store frontend attend toujours un blob.
- **Agent** : BOW-FRONT
- **Fichier** : `tavira-bow-frontend/src/stores/import.ts`
- **Action** :
  1. Modifier la methode `exportData()` du store pour recevoir le `job_id` du 202
  2. Ajouter une methode `pollExportStatus(jobId)` qui interroge `GET /api/export/status/{jobId}`
  3. Quand `status === 'completed'`, declencher le download via `GET /api/export/download/{jobId}`
  4. Gerer les etats : queued -> processing -> completed/failed
  5. Afficher une notification toast pendant le traitement
- **Routes backend** (deja en place) :
  - `GET /api/export/status/{jobId}` -> retourne `{status, type, rows, file, filename}`
  - `GET /api/export/download/{jobId}` -> retourne le fichier Excel
- **Effort** : 1h
- **Verification** : Tester manuellement chaque bouton export (workitems, governance, suppliers, risks, invoices)

---

### P0-2 : Implementer detectDuplicates() (RG-BOW-008)

- **Probleme** : 3 tests TDD RED dans `ImportDeduplicationTest.php` - la methode `detectDuplicates()` n'existe pas encore dans `ImportNormalizationService`
- **Agent** : BOW-BACK
- **Fichiers** :
  - `tavira-bow-api/app/Services/ImportNormalizationService.php` (ajouter la methode)
  - `tavira-bow-api/tests/Unit/Services/ImportDeduplicationTest.php` (deja ecrit, 3 tests skip)
- **Action** :
  1. Lire les 3 tests existants pour comprendre le contrat attendu
  2. Implementer `detectDuplicates(array $rows, string $type): array`
  3. Logique : normaliser les noms (lowercase, trim), fuzzy match pour "J. Smith" vs "John Smith"
  4. Retourner les indices des lignes dupliquees
  5. Activer les 3 tests (retirer le skip)
- **Effort** : 2h
- **Verification** : `docker exec bow_api php artisan test --filter=ImportDeduplication`

---

### P0-3 : Fixer RAGGovernanceTest (5 tests skipped)

- **Probleme** : Le service `RAGCalculationService` utilise `status`/`due_date` au lieu de `current_status`/`deadline` (noms reels des champs GovernanceItem)
- **Agent** : BOW-BACK
- **Fichiers** :
  - `tavira-bow-api/app/Services/RAGCalculationService.php` (methode `calculateGovernanceRAG`)
  - `tavira-bow-api/tests/Unit/Services/RAGCalculationServiceTest.php` (5 tests skipped)
- **Action** :
  1. Verifier les noms de champs dans le model `GovernanceItem.php`
  2. Corriger le service pour utiliser les bons noms de champs
  3. Reactiver les 5 tests
- **Effort** : 30min
- **Verification** : `docker exec bow_api php artisan test --filter=RAGCalculation`

---

## P1 - CRITIQUE QUALITE

### P1-1 : Tests matrice permissions (admin vs member x dept x theme)

- **Probleme** : La matrice 3 couches (role global + dept + theme risque) n'a AUCUN test dedie. C'est critique pour la securite.
- **Agent** : BOW-QA + BOW-BACK
- **Fichier a creer** : `tavira-bow-api/tests/Feature/PermissionMatrixTest.php`
- **Scenarios a tester** :
  1. Admin accede a tout (workitems, risks, governance, suppliers) -> 200
  2. Member sans permission sur dept A -> 403 sur workitems dept A
  3. Member avec `can_view` sur dept A -> peut lister, pas editer
  4. Member avec `can_edit_status` sur dept A -> peut update status, pas create
  5. Member avec `can_create_tasks` sur dept A -> peut creer, pas editer les autres
  6. Member avec `can_edit_all` sur dept A -> full access dept A, 403 dept B
  7. Member avec permission theme risque REG -> voit risques REG, pas les autres
  8. Cross-check : permission dept vs permission theme (conflit)
- **Tables concernees** : `user_department_permissions`, `risk_theme_permissions`
- **Policies** : `RiskPolicy.php`, `WorkItemPolicy.php`, `GovernanceItemPolicy.php`, `SupplierPolicy.php`
- **Effort** : 1 jour
- **Verification** : `docker exec bow_api php artisan test --filter=PermissionMatrix`

---

### P1-2 : Tests CurrencyConversionService

- **Probleme** : Service de conversion multi-devises sans aucun test
- **Agent** : BOW-BACK
- **Fichier a creer** : `tavira-bow-api/tests/Unit/Services/CurrencyConversionServiceTest.php`
- **Fichier source** : `tavira-bow-api/app/Services/CurrencyConversionService.php`
- **Scenarios** :
  1. Conversion EUR -> GBP avec taux connu
  2. Conversion GBP -> EUR
  3. Conversion devise identique (EUR -> EUR) -> meme montant
  4. Conversion avec montant 0
  5. Conversion avec devise inconnue -> exception ou fallback
  6. Verification des taux par defaut
- **Effort** : 2h
- **Verification** : `docker exec bow_api php artisan test --filter=CurrencyConversion`

---

### P1-3 : Tests Form Requests (15 restants)

- **Probleme** : 15 Form Requests sur 17 n'ont aucun test de validation
- **Agent** : BOW-QA + BOW-BACK
- **Fichier a creer** : `tavira-bow-api/tests/Feature/FormRequestValidationTest.php`
- **Form Requests a tester** (par priorite) :
  1. `StoreRiskRequest` / `UpdateRiskRequest` - regles complexes (enums, scores)
  2. `StoreGovernanceItemRequest` / `UpdateGovernanceItemRequest`
  3. `StoreSupplierRequest` / `UpdateSupplierRequest`
  4. `StoreSupplierContractRequest` / `UpdateSupplierContractRequest`
  5. `StoreSupplierInvoiceRequest` / `UpdateSupplierInvoiceRequest`
  6. `StoreWorkItemRequest` / `UpdateWorkItemRequest`
  7. `StoreUserRequest` / `UpdateUserRequest`
  8. `LoginRequest`
- **Approche** : Pour chaque Form Request, tester :
  - Requete valide -> 200/201
  - Champ requis manquant -> 422 + message
  - Champ avec format invalide -> 422
  - Champ unique en violation -> 422 (pour update, exclure l'item courant)
- **Effort** : 1 jour
- **Verification** : `docker exec bow_api php artisan test --filter=FormRequestValidation`

---

### P1-4 : Tests 4 commands non couverts

- **Probleme** : 4 commandes Artisan sans tests
- **Agent** : BOW-BACK
- **Fichier a creer** : `tavira-bow-api/tests/Feature/CommandsTest.php`
- **Commands a tester** :
  1. `ContractExpiryAlertsCommand` (`bow:send-contract-alerts`)
     - Creer un contrat expirant dans 30j -> verifie notification envoyee
     - Contrat expirant dans 100j -> pas de notification
  2. `RecalculateDashboardCommand` (`bow:recalculate-dashboard`)
     - Executer -> pas d'exception, retourne 0
  3. `UpdateRAGStatuses`
     - Creer un workitem overdue -> verifie RAG = Red apres execution
  4. `MigrateSqliteData` (optionnel, specifique migration)
- **Effort** : 3h
- **Verification** : `docker exec bow_api php artisan test --filter=CommandsTest`

---

## P2 - AMELIORATION

### P2-1 : Tests CRUD controllers (10 principaux)

- **Probleme** : 0% de couverture sur les controllers
- **Agent** : BOW-QA + BOW-BACK
- **Controllers prioritaires** :
  1. RiskController (index, store, show, update, destroy, dashboard, heatmap, recalculate)
  2. WorkItemController (index, store, show, update, destroy, assign, unassign)
  3. GovernanceController (index, store, show, update, destroy, dashboard)
  4. SupplierController (index, store, show, update, destroy, dashboard)
  5. UserController (index, store, show, update, destroy, resetPassword)
  6. TeamController (index, store, show, update, destroy)
  7. SupplierContractController (index, store, update, destroy, all)
  8. SupplierInvoiceController (index, store, update, destroy, all, bulkStore)
  9. DashboardController (stats, byArea, byRag, alerts, upcoming, calendar)
  10. SearchController (search, tags, departments, activities)
- **Approche par controller** :
  - Test index -> retourne liste paginee
  - Test store -> cree un item, retourne 201
  - Test show -> retourne item avec relations
  - Test update -> modifie item, retourne 200
  - Test destroy -> supprime, retourne 204
  - Test auth -> 401 sans token
  - Test permissions -> 403 si pas le droit
- **Effort** : 2-3 jours
- **Fichiers a creer** : 1 fichier test par controller dans `tests/Feature/`

---

### P2-2 : Validation file size 10MB

- **Probleme** : CDC exige max 10MB par fichier, non verifie dans les Form Requests
- **Agent** : BOW-BACK
- **Fichiers a verifier/modifier** :
  - `RiskFileController.php` -> ajouter `'file' => 'required|file|max:10240'`
  - `WorkItemFileController.php` -> idem
  - `GovernanceFileController.php` -> idem
  - `SupplierFileController.php` -> idem
- **Effort** : 30min
- **Verification** : Tester upload > 10MB -> 422

---

### P2-3 : Setup tests frontend (Vitest)

- **Probleme** : 0 test frontend, aucun framework de test installe
- **Agent** : BOW-FRONT + BOW-QA
- **Actions** :
  1. `npm install -D vitest @testing-library/react @testing-library/jest-dom @testing-library/user-event jsdom`
  2. Configurer `vitest.config.ts` avec jsdom
  3. Ajouter script `"test": "vitest"` dans package.json
  4. Ecrire les premiers tests :
     - `src/lib/__tests__/utils.test.ts` (formatDate, formatCurrency, getRAGColor)
     - `src/stores/__tests__/auth.test.ts` (login, logout, token refresh)
     - `src/components/shared/__tests__/rag-badge.test.tsx` (rendu couleur)
- **Effort** : 2 jours
- **Verification** : `cd tavira-bow-frontend && npm test`

---

### P2-4 : Tests notifications manquantes

- **Probleme** : 3 notifications sans test direct
- **Agent** : BOW-BACK
- **Fichier a creer** : `tavira-bow-api/tests/Feature/NotificationTypesTest.php`
- **Notifications a tester** :
  1. `ContractExpiringNotification` - contenu mail, destinataire, delais
  2. `ImportCompletedNotification` - contenu avec stats import
  3. `RiskThresholdBreachedNotification` - contenu avec score et seuil
- **Effort** : 2h

---

## Ordonnancement

```
Jour 1 matin :
  P0-1 (store frontend export)    -> 1h
  P0-3 (fix RAGGovernanceTest)    -> 30min
  P0-2 (detectDuplicates)         -> 2h

Jour 1 aprem :
  P1-2 (CurrencyConversion tests) -> 2h
  P1-4 (commands tests)           -> 3h

Jour 2 :
  P1-1 (permission matrix tests)  -> 1j

Jour 3 :
  P1-3 (Form Request tests)       -> 1j

Jour 4-5 :
  P2-1 (CRUD controller tests)    -> 2-3j

Jour 6 :
  P2-2 (file size validation)     -> 30min
  P2-3 (setup Vitest frontend)    -> reste du jour
  P2-4 (notifications tests)      -> 2h
```

---

## Commandes CI (a lancer apres chaque bloc)

```bash
docker exec bow_api vendor/bin/pint --test
docker exec bow_api vendor/bin/phpstan analyse
docker exec bow_api php artisan test
cd tavira-bow-frontend && npx tsc --noEmit
```

---

## Objectif Final

| Metrique | Avant | Apres |
|----------|-------|-------|
| Tests Pest | 218 | ~350+ |
| Tests skipped | 8 | 0 |
| Couverture controllers | 15% | 80%+ |
| Couverture Form Requests | 12% | 100% |
| Couverture permissions | 0% | 100% |
| RG-BOW testees | 10/13 | 13/13 |
| Tests frontend | 0 | 20+ |
