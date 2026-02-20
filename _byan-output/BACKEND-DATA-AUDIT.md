# Backend Data Audit - Plan de correction

## PROBLEMES IDENTIFIES

### WorkItems (132 items)
| Champ | Probleme | Target |
|-------|----------|--------|
| bau_or_transformative | 100% BAU (132/132) | ~60% BAU, ~40% Transformative |
| current_status | 86% Not Started (114/132) | ~20% Not Started, ~35% In Progress, ~15% On Hold, ~30% Completed |
| rag_status | 66% Red (87/132) | ~20% Blue, ~30% Green, ~30% Amber, ~20% Red |
| impact_level | 100% Medium (132/132) | ~25% High, ~50% Medium, ~25% Low |
| task_assignments | 0 assignments | ~200 assignments (1-3 par task) |
| cost_savings | 0 remplis | ~40 avec valeurs GBP |
| expected_cost | 0 remplis | ~50 avec valeurs GBP |
| revenue_potential | 0 remplis | ~30 avec valeurs GBP |
| tags | 0 tags | ~80 tasks avec 1-3 tags |
| deadlines | 87 overdue / 2 ce mois / 43 futurs | Distribution plus equilibree sur 2025-2026 |
| completion_date | Probablement vide pour les Completed | Rempli pour Completed |

### Distribution dates cible
- Passe (overdue) : ~20% (taches en retard realiste)
- Mois en cours : ~15%
- Prochains 3 mois : ~30%
- 3-6 mois : ~20%
- 6-12 mois : ~15%

### Users existants (a garder tels quels)
27 users (IDs 1-27), dont 2 admins (1: Administrator, 18: James Thompson)

---

## PLAN D'EXECUTION

### Seeder : `WorkItemDataEnhancerSeeder.php`

Ce seeder met a jour les 132 work items existants pour les rendre realistes.
Il ne cree PAS de nouveaux work items, il UPDATE ceux existants.

#### Logique de mise a jour

```
Pour chaque work item (par lot de ID) :
1. Varier bau_or_transformative : ~40% transformative (surtout Technology, Compliance, Finance)
2. Varier current_status selon une distribution realiste
3. Recalculer rag_status pour etre coherent avec le status :
   - Completed -> Blue
   - In Progress + deadline OK -> Green
   - In Progress + deadline proche -> Amber
   - Overdue ou en retard -> Red
   - Not Started -> Green ou Amber selon deadline
4. Varier impact_level
5. Redistribuer les deadlines sur 2025-2026
6. Ajouter completion_date pour les Completed
7. Remplir cost_savings, expected_cost, revenue_potential pour ~40%
8. Ajouter tags (compliance, regulatory, digital, etc.)
9. Creer TaskAssignments (1-3 users par task, owner + members)
10. Ajouter monthly_update pour les In Progress et On Hold
```

#### Tags pool realiste
```
compliance, regulatory, digital-transformation, cost-reduction,
automation, risk-mitigation, client-facing, internal,
priority-q1, priority-q2, infrastructure, reporting,
audit, governance, vendor-management, training,
process-improvement, data-migration, cybersecurity, ESG
```

#### Assignments logique
- Chaque task a 1 owner (souvent le responsible_party ou du meme departement)
- ~50% des tasks ont 1-2 members supplementaires
- Distribuer equitablement entre les users actifs

---

## FICHIERS A CREER/MODIFIER

| Action | Fichier |
|--------|---------|
| CREER | `database/seeders/WorkItemDataEnhancerSeeder.php` |
| MODIFIER | `database/seeders/DatabaseSeeder.php` (ajouter le seeder) |

## VERIFICATION

```bash
docker exec bow_api php artisan db:seed --class=WorkItemDataEnhancerSeeder
docker exec bow_api php artisan tinker --execute="
echo 'Status: '; App\Models\WorkItem::selectRaw('current_status, count(*)')->groupBy('current_status')->get();
echo 'RAG: '; App\Models\WorkItem::selectRaw('rag_status, count(*)')->groupBy('rag_status')->get();
echo 'Type: '; App\Models\WorkItem::selectRaw('bau_or_transformative, count(*)')->groupBy('bau_or_transformative')->get();
echo 'Impact: '; App\Models\WorkItem::selectRaw('impact_level, count(*)')->groupBy('impact_level')->get();
echo 'Assignments: ' . App\Models\TaskAssignment::count();
"
```
