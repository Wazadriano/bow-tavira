# Analyse : Milestones (task_milestones)

## Ce que sont les milestones

- **Définition** : Sous-étapes ou jalons rattachés à une **tâche (Work Item)**. Chaque jalon a un titre, une date cible, un statut (Non démarré / Complété) et peut avoir des assignations.
- **Contexte** : Ils vivent **uniquement** dans le périmètre des **Work Items (Tasks)**. Il existe un autre type de milestones pour la **Governance** (`governance_milestones`), géré par un autre contrôleur.

## Où ils doivent être

| Côté | Emplacement |
|------|-------------|
| **API** | `GET /api/workitems/{workitem}/milestones` (liste pour une tâche), `POST /api/milestones` (création avec `work_item_id`), `GET/PUT/DELETE /api/milestones/{id}`. |
| **Frontend** | Page détail tâche `tasks/[id]` → composant `MilestonesPanel` avec `workItemId={item.id}`. Le store `workitems` appelle `fetchMilestones(workItemId)` et `createMilestone(workItemId, data)`. |
| **BDD** | Table `task_milestones` : `id`, `work_item_id`, `title`, `description`, `target_date`, `status`, `order`, `timestamps`. Table de liaison `milestone_assignments` pour les utilisateurs assignés. |

## Comment ils doivent interagir

1. **Ouverture d’une tâche** : le front appelle `GET /workitems/{id}/milestones` → affiche la liste des jalons.
2. **Création** : l’utilisateur clique « Add », saisit titre / description / due date → front envoie `POST /api/milestones` avec `{ work_item_id, title, description?, due_date? }`.
3. **Cocher « complété »** : front envoie `PUT /api/milestones/{id}` avec `{ is_completed: true }` → le jalon doit être marqué complété (côté BDD : `status = 'Completed'` ou `completion_date` selon le schéma).
4. **Suppression** : `DELETE /api/milestones/{id}`.

## Cause des 500

- **Schéma BDD réel** (migration `2024_01_02_000007_create_task_milestones_table`) : colonnes **`target_date`** et **`status`** (pas de `due_date`, `completion_date`, `rag_status`).
- **Modèle et contrôleur** : utilisent **`due_date`**, **`completion_date`**, **`is_completed`**, **`rag_status`**.
- **Effet** : à la création, Laravel tente d’insérer dans une colonne `due_date` qui n’existe pas → erreur SQL → **500**. Idem en lecture si le modèle s’attend à des colonnes absentes.

## Correction appliquée

- **Modèle** : aligné sur la BDD (`target_date`, `status`). Accesseurs `due_date` et `is_completed` exposés pour le front (lecture/écriture mappées).
- **Contrôleur** : en création, mapping `due_date` → `target_date` ; en mise à jour, mapping `is_completed` → `status` (`Completed` / `Not Started`).
