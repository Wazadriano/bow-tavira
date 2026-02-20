# BOW V1 - Release Notes

---

## FR - Notes de version

### Nouvelles fonctionnalites

- **Deduplication a l'import** : detection automatique des doublons (exact et approximatif) lors de l'import Excel
- **Conversion multi-devises** : les montants en EUR/USD sont automatiquement convertis en GBP sur les factures fournisseurs
- **Double categorie Sage** : les fournisseurs peuvent maintenant avoir une categorie Sage principale et secondaire
- **Dashboards en cache** : les statistiques des tableaux de bord se chargent plus vite (cache 5 min)

### Ameliorations

- **Responsive mobile** : le header, les formulaires dans les dialogues et le mappeur de colonnes s'adaptent aux petits ecrans
- **Validation des fichiers** : seuls les formats autorises (PDF, Word, Excel, images, CSV, TXT) sont acceptes, limite a 10 Mo
- **Performance** : chargement optimise des relations sur les listes fournisseurs et risques

### Points d'attention

- Les taux de change (EUR/USD vers GBP) sont fixes dans le code. Pour des taux dynamiques, un service externe sera necessaire
- Le cache des dashboards expire toutes les 5 minutes. Les modifications sont visibles apres ce delai
- Le SMTP pour l'envoi d'emails en production n'est pas encore configure (Mailhog en dev uniquement)
- Les tests en mode parallele ne fonctionnent pas (probleme de base de test). Utiliser `php artisan test` sans `--parallel`

### Comptes de test

Mot de passe universel : `bow2026!`

| Login | Role | Acces |
|-------|------|-------|
| `dev` | Admin | Tout |
| `mark.griffiths` | Admin | Tout |
| `simon.mason` | Admin | Tout |
| `ranjit.gursahani` | Admin | Tout |
| `andy.webster` | Member | Edit: Compliance, Finance / View: Operations, Technology |
| `will.moody` | Member | Edit: Operations / View: Finance, Technology |
| `rebecca.reffell` | Member | Edit: Finance / View: Compliance |
| `lisa.scott` | Member | Edit status: Corporate Governance / View: HR |
| `john.halliday` | Member | View: Corporate Governance |
| `olivier.dupont` | Member | Edit: Technology / View: Operations |
| `colin.bugler` | Member | View: Finance |
| `remy.alexander` | Member | Edit: Corporate Governance |

### Couverture de tests

- Backend : 423 tests (Pest)
- Frontend : 12 tests (Vitest)
- Analyse statique : 0 erreur (Larastan)

---

## EN - Release Notes

### New features

- **Import deduplication**: automatic duplicate detection (exact and fuzzy matching) during Excel import
- **Multi-currency conversion**: EUR/USD amounts are automatically converted to GBP on supplier invoices
- **Dual Sage category**: suppliers can now have a primary and secondary Sage category
- **Cached dashboards**: dashboard statistics load faster (5 min cache)

### Improvements

- **Mobile responsive**: header, dialog forms and column mapper adapt to small screens
- **File upload validation**: only allowed formats (PDF, Word, Excel, images, CSV, TXT) are accepted, limited to 10 MB
- **Performance**: optimised eager loading on supplier and risk lists

### Things to note

- Exchange rates (EUR/USD to GBP) are hardcoded. For dynamic rates, an external service will be needed
- Dashboard cache expires every 5 minutes. Changes become visible after this delay
- SMTP for production email delivery is not yet configured (Mailhog in dev only)
- Parallel test mode does not work (test database issue). Use `php artisan test` without `--parallel`

### Test accounts

Universal password: `bow2026!`

| Login | Role | Access |
|-------|------|--------|
| `dev` | Admin | Everything |
| `mark.griffiths` | Admin | Everything |
| `simon.mason` | Admin | Everything |
| `ranjit.gursahani` | Admin | Everything |
| `andy.webster` | Member | Edit: Compliance, Finance / View: Operations, Technology |
| `will.moody` | Member | Edit: Operations / View: Finance, Technology |
| `rebecca.reffell` | Member | Edit: Finance / View: Compliance |
| `lisa.scott` | Member | Edit status: Corporate Governance / View: HR |
| `john.halliday` | Member | View: Corporate Governance |
| `olivier.dupont` | Member | Edit: Technology / View: Operations |
| `colin.bugler` | Member | View: Finance |
| `remy.alexander` | Member | Edit: Corporate Governance |

### Test coverage

- Backend: 423 tests (Pest)
- Frontend: 12 tests (Vitest)
- Static analysis: 0 errors (Larastan)
