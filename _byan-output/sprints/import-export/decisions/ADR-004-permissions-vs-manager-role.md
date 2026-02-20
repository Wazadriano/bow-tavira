# ADR-004 : Permissions granulaires vs role Manager

Date : 2026-02-11
Statut : ACCEPTE
Agents : BOW-PM (decision metier) + BOW-ARCH (validation architecture)

## Contexte

Le CDC Fonctionnel 3.1 specifie 3 niveaux de roles :
- Administrateur (acces total)
- Manager (gestion equipe)
- Utilisateur (consultation et actions assignees)

L'implementation actuelle utilise 2 roles globaux (admin, member) combines a un systeme de permissions granulaires sur 4 axes.

## Decision

Conserver le systeme de permissions granulaires actuel. Ne PAS ajouter un role "Manager" rigide.

## Justification

Le systeme actuel est PLUS flexible que la hierarchie 3 roles du CDC :

| CDC Role | Equivalent actuel |
|----------|-------------------|
| Administrateur | role = admin (acces total) |
| Manager | role = member + can_edit_all + can_create_tasks sur son departement |
| Utilisateur | role = member + can_view + can_edit_status uniquement |

Avantages du systeme actuel :
1. Un utilisateur peut etre "Manager" sur le departement IT et "Utilisateur" sur le departement Finance
2. Les permissions risques sont independantes des permissions departement
3. L'acces governance et fournisseurs est configurable par item
4. Pas de migration de donnees necessaire

Tables de permissions :
- user_department_permissions : can_view, can_edit_status, can_create_tasks, can_edit_all (par departement)
- risk_theme_permissions : can_view, can_edit, can_create, can_delete, can_edit_all (par theme)
- governance_item_access : can_view, can_edit (par item)
- supplier_access : can_view, can_edit (par fournisseur)

## Consequences

- Le front doit documenter clairement dans l'UI admin que les permissions par departement remplacent le concept de "Manager"
- La documentation utilisateur (future) doit expliquer les 4 niveaux de permissions
- Aucun changement de code necessaire
