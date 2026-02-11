# Ecarts CDC vs Code implemente - Tavira BOW

**Date** : 2026-02-10
**Source CDC** : `_bmad-output/bmb-creations/project-context-bow.yaml` + agents BOW-*

---

## 1. Regles metier (RG-BOW) - Statut implementation

### Implementees et connectees

| Regle | Description | Backend | Frontend | Statut |
|-------|-----------|---------|----------|--------|
| RG-BOW-001 | RAG calcule automatiquement | `RAGCalculationService` | Affiche RAGBadge | OK - verifier observers |
| RG-BOW-002 | Work Item multi-assignation | `task_assignments` table, routes assign/unassign | UI pas visible dans detail | **PARTIAL** - UI d'assignation a creer |
| RG-BOW-010 | 5 Risk Themes fixes | `risk_themes` table | Affiche dans risk detail | OK |

### Implementees backend, non connectees frontend

| Regle | Description | Backend | Frontend | Gap |
|-------|-----------|---------|----------|-----|
| RG-BOW-003 | Score inherent = max(impacts) x prob | `RiskScoringService` | Calcul fait en JS dans la page | **Frontend devrait lire le score du backend, pas le recalculer** |
| RG-BOW-004 | Cap controles 70% | `RiskScoringService` | Pas affiche / non verifie | A verifier dans le service |
| RG-BOW-005 | Tier: A(>=9), B(4-8), C(<4) | `RiskScoringService` | Affiche tier dans sidebar | OK si backend calcule |
| RG-BOW-006 | Appetite: OK/OUTSIDE | `RiskScoringService` | Affiche appetite_status | OK si backend calcule |
| RG-BOW-008 | Deduplication import | `ImportNormalizationService` | Preview montre erreurs | OK |
| RG-BOW-009 | Encodage UTF-8 normalise | `ImportNormalizationService` | Transparent | OK |
| RG-BOW-011 | Permissions dept + theme | `user_department_permissions` + `risk_theme_permissions` | Gestion permissions dans users | **PARTIAL** - UI risk theme permissions manquante |

### Non implementees ou a verifier

| Regle | Description | Backend | Frontend | Gap |
|-------|-----------|---------|----------|-----|
| RG-BOW-007 | Alerte contrat 90j | Pas de scheduler visible | Pas d'UI alertes contrats | **NON IMPLEMENTE** - besoin d'un scheduler + notifications |
| RG-BOW-012 | Double categorie Sage | `sage_categories` table, relation exists | UI montre 1 seule categorie | **PARTIAL** - UI n'affiche qu'une categorie |
| RG-BOW-013 | Conversion multi-devises GBP | Pas de service de taux de change | Pas d'affichage conversion | **NON IMPLEMENTE** - majeur pour la finance |

---

## 2. Features CDC - Statut par module

### Module 1 : Work Items

| Feature | Backend | Frontend | Gap |
|---------|---------|----------|-----|
| CRUD work items | OK | OK (store + pages) | URLs OK |
| Multi-assignation Owner/Member | Routes OK | **UI incomplete** - pas de panel d'assignation dans detail | Creer AssignmentPanel |
| Dependencies | Model + table OK | UI DependenciesPanel OK | **Routes API manquantes** |
| Milestones | Routes OK | UI MilestonesPanel OK | OK |
| RAG automatique | Service OK | Affichage OK | Verifier observers |
| Tags | Champ existe | Pas d'UI de gestion tags | **UI manquante** |
| File attachments | Routes OK | UI FileAttachmentsPanel OK | OK |
| Import/Export | Routes OK | UI ImportExport OK | OK |

### Module 2 : Governance

| Feature | Backend | Frontend | Gap |
|---------|---------|----------|-----|
| CRUD governance items | OK | OK | URLs OK |
| Frequency | Champ existe | Affiche dans detail | OK |
| 7 Locations | Enum/values | Affiche dans detail | OK |
| Milestones | Routes OK | UI OK | OK |
| Documents/Files | Routes OK | UI OK | OK |
| Access management | Methode existe, **route non exposee** | UI AccessManagementPanel | **Routes a exposer** |
| Auto RAG | Service OK | Affichage OK | OK |
| Dashboard stats | Route OK | **URL incorrecte** | Fix URL |

### Module 3 : Suppliers

| Feature | Backend | Frontend | Gap |
|---------|---------|----------|-----|
| CRUD suppliers | OK | OK | OK |
| Multi-entity | Table `supplier_entities` | Pas d'UI entites | **UI manquante** |
| Contracts CRUD | Routes OK | UI OK (dialog) | **URLs update/delete incorrectes** |
| Invoices CRUD | Routes OK | UI OK (dialog) | **URLs update/delete incorrectes** |
| Bulk invoice import | Route OK | Pas de bouton | **UI manquante** |
| Sage categories | Route + model OK | Affiche dans detail | OK |
| Dashboard | Route OK | **URL incorrecte** | Fix URL |
| Access management | Methode existe, **route non exposee** | UI existe | **Routes a exposer** |
| File attachments | Routes partielles (pas de download) | UI existe | **Route download manquante** |
| Contrat alerte 90j (RG-BOW-007) | Pas implemente | Pas implemente | **NON IMPLEMENTE** |
| Conversion multi-devises (RG-BOW-013) | Pas implemente | Pas implemente | **NON IMPLEMENTE** |
| Global invoices listing | **Route manquante** | Page existe avec mock | **Route a creer** |
| Global contracts listing | **Route manquante** | Page existe avec mock | **Route a creer** |

### Module 4 : Risk Management

| Feature | Backend | Frontend | Gap |
|---------|---------|----------|-----|
| 3 niveaux (Theme/Category/Risk) | OK | OK | OK |
| CRUD risks | OK | OK | OK |
| Scoring (impact x prob) | `RiskScoringService` | **Calcul en JS** | Frontend devrait lire le backend |
| Controls avec library | Routes OK | UI dialog OK | OK |
| Residual score | Service OK | Affiche OK | OK |
| Tier classification | Service OK | Affiche OK | OK |
| Appetite check | Service OK | Affiche OK | OK |
| Heatmap 5x5 | Route OK | Page existe | A verifier connexion |
| Actions CRUD | Routes OK | UI dialog OK | OK |
| Risk theme permissions | Routes OK | **Pas d'UI admin** | **UI manquante** |
| Global actions listing | **Route manquante** | Page existe avec mock | **Route a creer** |
| Recalculate per-risk | **Route manquante** | Store appelle | **Route a creer** |
| Dashboard | Route OK | **URL incorrecte** | Fix URL |
| File attachments | Routes OK | **Pas integre dans page detail** | **Ajouter FileAttachmentsPanel** |

### Module 5 : Settings

| Feature | Backend | Frontend | Gap |
|---------|---------|----------|-----|
| Lists CRUD | Routes OK | UI OK | OK |
| System settings | Routes OK | UI OK | OK |
| Bulk lists | Route OK | Pas d'UI | Non prioritaire |

---

## 3. Donnees : BDD vs Mock

### Pages qui utilisent la BDD (via API)
- Toutes les pages de listing (tasks, governance, suppliers, risks) via stores Zustand
- Toutes les pages de detail via stores
- Import/Export via API
- Settings via store

### Pages qui tombent sur le mock (API echoue = URL incorrecte)
- `suppliers/invoices/page.tsx` - **TOUJOURS mock** (route globale n'existe pas)
- `suppliers/contracts/page.tsx` - **TOUJOURS mock** (route globale n'existe pas)
- `risks/actions/page.tsx` - **TOUJOURS mock** (route globale n'existe pas)
- `risks/dashboard/page.tsx` - **TOUJOURS mock** (URL incorrecte)
- `suppliers/dashboard/page.tsx` - **TOUJOURS mock** (URL incorrecte)
- `access-management-panel.tsx` - **TOUJOURS mock** (format reponse incorrect)

### Pages avec mock en fallback (API fonctionne normalement)
- `alerts-panel.tsx` - Mock uniquement si backend down
- `area-stats.tsx` - Idem
- `tasks/dashboard/page.tsx` - Idem
- `governance/dashboard/page.tsx` - Idem
- `risks/controls/page.tsx` - A verifier le format reponse

---

## 4. Priorites de correction

### P0 - Critique (donnees fausses en production)
1. Corriger les 8 URLs incorrectes dans les stores
2. Corriger les 5 URLs incorrectes dans les pages
3. Supprimer TOUS les mock data des catch blocks
4. Creer les 3 routes backend globales (invoices, contracts, risk actions)

### P1 - Haute (fonctionnalites cassees)
5. Exposer les routes access management (governance + suppliers)
6. Creer les routes dependencies work items
7. Creer la route recalculate per-risk
8. Corriger le format de reponse settings dans access-management-panel

### P2 - Moyenne (features incompletes)
9. UI multi-assignation work items (AssignmentPanel)
10. UI gestion tags work items
11. UI multi-entite suppliers
12. UI risk theme permissions (admin)
13. Integrer FileAttachmentsPanel dans risk detail
14. Route download fichiers suppliers
15. Route export invoices

### P3 - Basse (features CDC non implementees)
16. Alertes contrat 90j (RG-BOW-007) - scheduler + notifications
17. Conversion multi-devises GBP (RG-BOW-013) - service taux de change
18. Double categorie Sage (RG-BOW-012) - UI
19. Bulk invoice import UI
20. Heatmap interactive verification

---

## 5. Estimation effort

| Priorite | Nombre items | Effort estime |
|----------|-------------|--------------|
| P0 | 4 items | 2-3h (frontend URLs + backend routes) |
| P1 | 4 items | 3-4h (backend routes + frontend format) |
| P2 | 7 items | 8-12h (nouveaux composants + routes) |
| P3 | 5 items | 15-20h (services + scheduler + UI) |
