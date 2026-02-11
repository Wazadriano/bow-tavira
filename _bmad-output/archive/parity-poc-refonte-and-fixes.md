# Parité POC / Refonte + Corrections (analyse BOW)

## Contexte

Objectif : **aucune différence de fonctionnement** entre le POC et la refonte pour l’utilisateur. Les milestones doivent se comporter comme dans le POC (affichage, calendrier avec couleur distincte, ajout lors de la création/édition de tâche). Vérifier aussi la persistance en BDD et corriger les erreurs rencontrées (403 governance, 422).

---

## 1. Erreurs signalées

### 1.1 403 sur `GET /api/governance/items/{id}`

- **Cause** : Dans `GovernanceController`, `authorizeResource(GovernanceItem::class, 'governance')` utilise le nom de paramètre de route `governance`, alors que la route est `apiResource('items', ...)` donc le paramètre est **`item`**. Laravel ne trouve pas le modèle pour la policy → 403.
- **Correction** : Remplacer par `authorizeResource(GovernanceItem::class, 'item')`.

### 1.2 422 sur `GET` ou `POST /api/governance/items`

- **422 sur liste** : Peu probable sur un GET sans body ; à confirmer (filtres query ?).
- **422 sur création** : Le `store()` exige notamment `ref_no` (required, unique), `description` (required), `department` (required). Si le front envoie un formulaire incomplet ou un `ref_no` déjà existant, le serveur renvoie 422.
- **Action** : Vérifier que le formulaire de création governance envoie bien tous les champs requis et gérer l’affichage des erreurs de validation (message 422) côté front.

---

## 2. Milestones – parité avec le POC

### 2.1 Affichage des milestones (détail tâche)

- **POC** : Section milestones **uniquement pour les tâches Non-BAU** (Transformative). Cachée pour BAU.
- **Refonte** : `MilestonesPanel` affiché pour **toutes** les tâches.
- **À faire** : Afficher le bloc milestones **uniquement si** `item.bau_or_transformative === 'transformative'` (même règle que le POC).

### 2.2 Calendrier tâches avec milestones (couleur distincte)

- **POC** :
  - Calendrier charge tâches (`/api/workitems`) et milestones (`/api/milestones`).
  - Légende : Task = bleu, Milestone = rose.
  - Les milestones sont en **rose** (`bg-pink-100` / `bg-pink-800`), les tâches en bleu/RAG.
- **Refonte** :
  - La page `tasks/calendar` n’utilise que `useWorkItemsStore().items` (tâches avec `deadline`). **Aucun chargement des milestones.**
- **À faire (backend)** :
  - S’assurer que `GET /api/milestones` (avec ou sans `work_item_id`) renvoie bien la liste des task milestones (déjà le cas avec `MilestoneController::index`).
- **À faire (frontend)** :
  - Sur la page calendrier tâches : charger en plus les milestones (ex. `GET /api/milestones` ou par work_item si préféré).
  - Ajouter des événements de type `milestone` avec une **couleur dédiée** (ex. rose/pink) et un libellé du type « 🎯 [titre] ».
  - Adapter `CalendarView` pour accepter un `type: 'task' | 'milestone'` et appliquer la couleur en conséquence (bleu/RAG pour tâches, rose pour milestones).

### 2.3 Ajouter des milestones à la création / édition de tâche

- **POC** :
  - Dans le **modal de tâche** (création ou édition), pour une tâche **Non-BAU**, une section « Milestones » est visible.
  - Bouton « Add Milestone » ouvre un modal (titre, description, target date, statut, utilisateurs assignés). Création / édition / suppression sans quitter le flux tâche.
- **Refonte** :
  - Création : `tasks/new` avec `WorkItemForm` uniquement (pas de section milestones).
  - Édition : `tasks/[id]/edit` avec `WorkItemForm` uniquement. Les milestones sont sur la page **détail** `tasks/[id]` dans `MilestonesPanel`.
- **À faire** :
  - **Option A** : Intégrer dans les pages **création** et **édition** une section « Milestones » (affichée seulement si BAU = Transformative) avec liste + Add / Edit / Delete, sans changer de page (comme le POC).
  - **Option B** : Garder le flux actuel (détail pour gérer les milestones) mais s’assurer que la règle « seulement pour Transformative » est appliquée et que le lien « modifier la tâche » depuis le détail est clair.

Recommandation courte : **Option A** pour coller au POC (ajout/modification des milestones dans le même écran que la tâche, en create et edit).

### 2.4 Données et BDD

- **Task milestones** : Déjà aligné sur la table `task_milestones` (`target_date`, `status`) avec mapping API `due_date` / `is_completed`. Persistance OK après correctifs précédents.
- **Governance** : Vérifier que les policies et le paramètre de route (`item`) ne bloquent plus la consultation (correction 403 ci-dessus). Côté données, pas d’anomalie identifiée si les champs requis sont envoyés.

---

## 3. Plan des modifications

### Backend

| Priorité | Fichier / zone | Modification |
|----------|----------------|--------------|
| P0 | `GovernanceController::__construct` | Remplacer `'governance'` par `'item'` dans `authorizeResource(GovernanceItem::class, 'item')`. |
| - | `MilestoneController::index` | Déjà utilisable pour `GET /api/milestones` (liste globale ou filtrée par `work_item_id`). Rien à changer si le contrat convient au front. |

### Frontend

| Priorité | Fichier / zone | Modification |
|----------|----------------|--------------|
| P0 | `tasks/[id]/page.tsx` | N’afficher `MilestonesPanel` que si `item.bau_or_transformative === 'transformative'`. |
| P0 | `tasks/calendar/page.tsx` + store workitems | Charger les milestones (ex. endpoint unique ou par tâche), construire des événements `type: 'milestone'`, couleur rose. |
| P0 | `components/calendar/calendar-view.tsx` | Supporter un type d’événement `milestone` avec style rose (distinct des tâches). |
| P1 | `tasks/new` + `tasks/[id]/edit` + formulaire | Section « Milestones » (visible si Transformative) : liste, Add, Edit, Delete, sans quitter la page (parité POC). |
| P2 | Governance create form | S’assurer que tous les champs requis sont envoyés et que les erreurs 422 sont affichées (validation). |

---

## 4. Résumé

- **403 governance** : ✅ Corrigé en utilisant le bon nom de paramètre de route (`item`) dans `authorizeResource(GovernanceItem::class, 'item')`.
- **422 governance** : À traiter côté formulaire de création (champs requis + affichage des erreurs).
- **Milestones** : ✅ Affichage détail réservé aux tâches Transformative (condition sur `bau_or_transformative === 'transformative'`). ✅ Calendrier tâches avec chargement des milestones et couleur rose (eventKind + légende). À faire (P1) : gestion des milestones dans les écrans de création/édition de tâche comme dans le POC.
- **Données** : Persistance tâches/milestones déjà corrigée ; governance à revalider après correction 403.
