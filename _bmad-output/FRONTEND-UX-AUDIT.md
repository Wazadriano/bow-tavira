# Frontend UX/UI Audit - BOW Application

## 1. DARK MODE DUPLICATION

**Probleme**: Le bouton Dark Mode est present en DOUBLE :
- Sidebar (lignes 283-301) : Switch toggle
- Header (theme-toggle.tsx) : Dropdown Light/Dark/System (toujours visible en haut a droite)

**Action**: Retirer le toggle dark mode de la sidebar. Le header suffit.

---

## 2. TEXTE FRANCAIS RESTANT (10 fichiers, ~90 strings)

### 2.1 `stores/workitems.ts` (3 strings)
| Ligne | FR | EN |
|-------|----|----|
| 159 | Reponse invalide du serveur | Invalid server response |
| 165 | Impossible de charger la tache | Failed to load work item |
| 168 | Cette tache est introuvable ou a ete supprimee. | This work item was not found or has been deleted. |

### 2.2 `tasks/[id]/page.tsx` (2 strings)
| Ligne | FR | EN |
|-------|----|----|
| 86 | Tache introuvable | Work item not found |
| 87 | Cette tache n'existe pas ou vous n'avez pas les droits... | This work item does not exist or you do not have access. |

### 2.3 `settings/page.tsx` (9 strings)
| Ligne | FR | EN |
|-------|----|----|
| 65,85 | Valeur et libelle requis | Value and label required |
| 135 | Listes | Lists |
| 139 | Systeme | System |
| 148 | Types de listes | List Types |
| 177 | Gerer les valeurs de cette liste | Manage values for this list |
| 184 | placeholder="Valeur (code)" | placeholder="Value (code)" |
| 190 | placeholder="Libelle" | placeholder="Label" |
| 248 | Actif / Inactif | Active / Inactive |

### 2.4 `suppliers/[id]/page.tsx` (~19 strings)
| Ligne | FR | EN |
|-------|----|----|
| 234 | Entites (tab) | Entities |
| 237 | Acces (tab) | Access |
| 251 | Statut | Status |
| 258 | Localisation | Location |
| 264 | Categorie SAGE | SAGE Category |
| 300 | Cree le | Created |
| 305 | Modifie le | Modified |
| 316 | Entites (title) | Entities |
| 320 | Entites associees a ce fournisseur (multi-entite). | Entities associated with this supplier (multi-entity). |
| 324 | Nom de l'entite | Entity name |
| 336,357 | Entite ajoutee | Entity added |
| 337,393 | Erreur | Error |
| 349 | Cette entite existe deja | This entity already exists |
| 359 | Erreur lors de l'ajout | Error adding entity |
| 365 | Ajouter | Add |
| 369 | Aucune entite. | No entities. |
| 391 | Entite supprimee | Entity removed |

### 2.5 `risks/themes/permissions/page.tsx` (~30 strings - PAGE ENTIERE en FR)
Tout : header, labels, badges, dialogs, toasts, placeholders.
Traduction complete necessaire.

### 2.6 `risks/dashboard/page.tsx` (3 strings)
| Ligne | FR | EN |
|-------|----|----|
| 71,109,161 | Statistiques Risk Management | Risk Management Statistics |
| 131 | Donnees indisponibles | Data unavailable |
| 132 | Impossible de charger les statistiques risques... | Unable to load risk statistics. Check the API connection. |

### 2.7 `suppliers/dashboard/page.tsx` (2 strings)
| Ligne | FR | EN |
|-------|----|----|
| 131 | Donnees indisponibles | Data unavailable |
| 132 | Impossible de charger les statistiques fournisseurs... | Unable to load supplier statistics. Check the API connection. |

### 2.8 `users/[id]/page.tsx` (~17 strings)
| Ligne | FR | EN |
|-------|----|----|
| 163 | Desactiver | Deactivate |
| 168 | Activer | Activate |
| 205 | Affiche comme: | Display name: |
| 233 | Cree le | Created |
| 241 | Modifie le | Modified |
| 286 | Niveau d'acces | Access level |
| 292 | Lecture | Read |
| 293 | Lecture/Ecriture | Read/Write |
| 340 | Ecriture | Write |
| 341 | Lecture | Read |
| 364 | Statut | Status |
| 377 | Administrateur / Membre | Administrator / Member |
| 382 | Compte | Account |
| 393 | Actif | Active |
| 398 | Inactif | Inactive |

### 2.9 `dashboard/page.tsx` (1 string)
| Ligne | FR | EN |
|-------|----|----|
| 122 | Distribution RAG | RAG Distribution |

---

## 3. UX / NAVIGATION - PROBLEMES IDENTIFIES

### 3.1 Back button toujours vers la liste (CRITIQUE)
**Probleme** : Quand on clique sur une tache depuis le Calendar/Kanban/Gantt, le bouton "Back" ramene a `/tasks` (liste), PAS a la vue d'origine.

**Fichiers concernes** :
- `tasks/[id]/page.tsx` L106 : `<Link href="/tasks">`
- `governance/[id]/page.tsx` L104 : `<Link href="/governance">`
- `risks/[id]/page.tsx` L204 : `<Link href="/risks">`
- `suppliers/[id]/page.tsx` L156 : `<Link href="/suppliers">`

**Solution** : Utiliser `router.back()` au lieu d'un lien fixe.
Cela respecte le parcours utilisateur : Calendar -> Detail -> Back = Calendar.

### 3.2 Pages detail/edit sans back button
Les pages edit utilisent correctement le back vers le detail. Pas de probleme ici.

### 3.3 Navigation coherente
- Toutes les pages de liste : OK (pas de back, c'est correct)
- Toutes les pages de detail : back vers la page d'origine (a corriger)
- Toutes les pages d'edit : back vers le detail (OK)

---

## 4. PLAN D'EXECUTION

### Phase 1 : Retirer dark mode sidebar
- Fichier : `components/layout/sidebar.tsx`
- Retirer le bloc lignes 283-301 + imports Moon/Sun/Switch/useTheme

### Phase 2 : Traduire les 10 fichiers FR -> EN
1. `stores/workitems.ts`
2. `tasks/[id]/page.tsx`
3. `settings/page.tsx`
4. `suppliers/[id]/page.tsx`
5. `risks/themes/permissions/page.tsx`
6. `risks/dashboard/page.tsx`
7. `suppliers/dashboard/page.tsx`
8. `users/[id]/page.tsx`
9. `dashboard/page.tsx`

### Phase 3 : Fix navigation back buttons
- Remplacer `<Link href="/xxx">Back</Link>` par `<button onClick={() => router.back()}>Back</button>` dans :
  - `tasks/[id]/page.tsx`
  - `governance/[id]/page.tsx`
  - `risks/[id]/page.tsx`
  - `suppliers/[id]/page.tsx`
  - `users/[id]/page.tsx`

---

## 5. PAGES INVENTAIRE COMPLET

| Module | Page | Status | Back | FR |
|--------|------|--------|------|-----|
| Auth | /login | OK | N/A | OK |
| Home | /dashboard | OK | N/A | 1 FR |
| Tasks | /tasks | OK | N/A | OK |
| Tasks | /tasks/[id] | OK | FIX back | 2 FR |
| Tasks | /tasks/[id]/edit | OK | OK | OK |
| Tasks | /tasks/new | OK | OK | OK |
| Tasks | /tasks/kanban | OK | N/A | OK |
| Tasks | /tasks/gantt | OK | N/A | OK |
| Tasks | /tasks/workload | OK | N/A | OK |
| Tasks | /tasks/dashboard | OK | N/A | OK |
| Tasks | /tasks/calendar | OK | N/A | OK |
| Governance | /governance | OK | N/A | OK |
| Governance | /governance/[id] | OK | FIX back | OK |
| Governance | /governance/[id]/edit | OK | OK | OK |
| Governance | /governance/new | OK | OK | OK |
| Governance | /governance/dashboard | Placeholder | N/A | OK |
| Governance | /governance/calendar | Placeholder | N/A | OK |
| Suppliers | /suppliers | OK | N/A | OK |
| Suppliers | /suppliers/[id] | OK | FIX back | 19 FR |
| Suppliers | /suppliers/[id]/edit | OK | OK | OK |
| Suppliers | /suppliers/new | OK | OK | OK |
| Suppliers | /suppliers/contracts | Placeholder | N/A | OK |
| Suppliers | /suppliers/invoices | Placeholder | N/A | OK |
| Suppliers | /suppliers/dashboard | OK | N/A | 2 FR |
| Suppliers | /suppliers/calendar | Placeholder | N/A | OK |
| Risks | /risks | OK | N/A | OK |
| Risks | /risks/[id] | OK | FIX back | OK |
| Risks | /risks/[id]/edit | OK | OK | OK |
| Risks | /risks/new | OK | OK | OK |
| Risks | /risks/dashboard | OK | N/A | 3 FR |
| Risks | /risks/heatmap | Tab in /risks | N/A | OK |
| Risks | /risks/actions | Placeholder | N/A | OK |
| Risks | /risks/controls | Placeholder | N/A | OK |
| Risks | /risks/themes/permissions | OK | N/A | 30 FR |
| Users | /users | OK | N/A | OK |
| Users | /users/[id] | OK | FIX back | 17 FR |
| Users | /users/[id]/edit | Placeholder | - | - |
| Users | /users/new | Placeholder | - | - |
| Teams | /teams | OK | N/A | OK |
| Teams | /teams/[id] | Placeholder | - | - |
| Import | /import-export | OK | N/A | OK |
| Settings | /settings | OK | N/A | 9 FR |
| Settings | /settings/security | Placeholder | N/A | - |
| Audit | /audit | OK | N/A | OK |
| Notifs | /notifications | OK | N/A | OK |
| Admin | /admin/login-history | Placeholder | N/A | - |

**Total FR restant** : ~90 strings dans 10 fichiers
**Back buttons a corriger** : 5 pages detail
**Dark mode sidebar** : 1 suppression
