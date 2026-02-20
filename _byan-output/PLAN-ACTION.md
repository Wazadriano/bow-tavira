# PLAN D'ACTION BOW - EXECUTE

Date : 2026-02-11
Source : Audit croise CDC Fonctionnel + CDC Technique vs code reel
Statut : TOUTES LES TACHES COMPLETEES

---

## Contexte

L'application est a 100% du CDC Fonctionnel et ~98% du CDC Technique.
Les 6 taches identifiees par audit croise des CDC contre le code reel
ont toutes ete executees et validees (Pint + Larastan + 213 tests + TSC).

---

## Taches

### T1 - Clarifier le role Manager vs permissions granulaires

- **Agents** : BOW-PM (decision) + BOW-ARCH (ADR)
- **Priorite** : BLOQUANT (decision impacte T3 et T6)
- **CDC** : Fonctionnel 3.1 - "3 niveaux : Administrateur, Manager, Utilisateur"
- **Etat actuel** : 2 roles (admin, member) + permissions granulaires 3 couches (role global + departement + theme risque)
- **Question** : Le systeme de permissions actuel couvre-t-il le besoin "Manager" du CDC ?
  - Si OUI : rediger un ADR documentant cette decision architecturale
  - Si NON : specifier les droits exacts du role Manager et creer la tache d'implementation
- **Effort** : 1h (decision) ou 2-3j (implementation si nouveau role)
- **Fichiers concernes** :
  - `tavira-bow-api/app/Enums/UserRole.php` (actuellement admin + member)
  - `tavira-bow-api/app/Models/User.php` (methode isAdmin())
  - `_bmad-output/import-export-sprint/decisions/` (ADR a creer)

---

### T2 - Ajouter rate limiting sur login (5 tentatives/min)

- **Agents** : BOW-SEC (spec) + BOW-BACK (implementation)
- **Priorite** : BLOQUANT PROD (securite CDC)
- **CDC** : Technique 4.2 - "5 tentatives login/min"
- **Etat actuel** : Throttle uniquement sur password reset (config/auth.php throttle:60). Route POST /auth/login sans middleware throttle.
- **Action** :
  1. Ajouter `throttle:5,1` sur la route POST /auth/login
  2. Ajouter `throttle:5,1` sur la route POST /auth/2fa/verify
  3. Configurer RateLimiter dans bootstrap/app.php si necessaire
  4. Tests Pest : 2 tests (bloque apres 5 tentatives, deblocage apres 1 min)
- **Effort** : 1h
- **Fichiers** :
  - `tavira-bow-api/routes/api.php` (lignes 47-55)
  - `tavira-bow-api/bootstrap/app.php`
  - `tavira-bow-api/tests/Feature/` (nouveau fichier test)

---

### T3 - Implementer historique connexions admin

- **Agents** : BOW-BACK (backend TDD) + BOW-FRONT (page UI)
- **Priorite** : BLOQUANT PROD (CDC Fonctionnel)
- **CDC** : Fonctionnel 3.1 - "L'administrateur peut consulter l'historique des connexions (date, heure, adresse IP)"
- **Etat actuel** : spatie/activitylog couvre les actions metier mais PAS les login events. AuthController.login() ne log ni IP ni user-agent.
- **Action backend** :
  1. Migration `create_login_histories_table` (user_id, ip_address, user_agent, logged_in_at)
  2. Model LoginHistory
  3. Event listener sur login reussi (enregistrer IP + user-agent + timestamp)
  4. LoginHistoryController : GET /admin/login-history (admin only, paginee, filtrable)
  5. Tests Pest : 3 tests (log cree au login, endpoint admin only, filtrage par user)
- **Action frontend** :
  1. Page `src/app/(dashboard)/admin/login-history/page.tsx`
  2. Table avec colonnes : utilisateur, email, IP, navigateur, date/heure
  3. Filtres : par utilisateur, par date
  4. Lien dans sidebar section Admin
- **Effort** : 1-2 jours
- **Depend de** : T1 (si role Manager ajoute, adapter les permissions)

---

### T4 - Ameliorer responsive mobile

- **Agents** : BOW-FRONT (implementation) + BOW-QA (tests visuels)
- **Priorite** : P1 (critere acceptation CDC)
- **CDC** : Fonctionnel 2.2 + 7.6 - "Adaptation automatique aux ecrans mobiles et tablettes"
- **Etat actuel** : Sidebar collapse (w-16/w-64) fonctionne mais pas de breakpoints mobile. Pas de hamburger menu. Pas de classes `hidden md:block` ou `sm:hidden`.
- **Action** :
  1. `sidebar.tsx` : hidden par defaut sur mobile (< md), hamburger menu pour ouvrir en overlay
  2. `header.tsx` : hamburger button visible sur mobile
  3. Verifier les pages dashboard et tables pour overflow horizontal
  4. Ajouter viewport meta explicite dans layout.tsx si absent
  5. Tester sur Chrome DevTools (iPhone SE, iPad, Desktop)
- **Effort** : 1-2 jours
- **Fichiers** :
  - `tavira-bow-frontend/src/components/layout/sidebar.tsx`
  - `tavira-bow-frontend/src/components/layout/header.tsx`
  - `tavira-bow-frontend/src/app/layout.tsx`

---

### T5 - Ajouter endpoint /api/health dedie

- **Agents** : BOW-BACK (implementation)
- **Priorite** : P2 (monitoring production)
- **CDC** : Technique 5.4 - "Endpoint /health pour monitoring externe"
- **Etat actuel** : /up existe (Laravel 11 default) mais pas d'endpoint /api/health avec checks DB + Redis.
- **Action** :
  1. Route GET /api/health (publique, sans auth)
  2. Retourne JSON : `{status, database, redis, version, timestamp}`
  3. Verifie connexion DB (SELECT 1) + Redis (PING)
  4. Test Pest : 1 test
- **Effort** : 30 min
- **Fichiers** :
  - `tavira-bow-api/routes/api.php`
  - `tavira-bow-api/tests/Feature/` (nouveau fichier test)

---

### T6 - Audit et creation Form Requests manquants

- **Agents** : BOW-QA (audit) + BOW-BACK (implementation)
- **Priorite** : P2 (qualite technique)
- **CDC** : Technique 2.2 - "Validation declarative via Form Requests"
- **Etat actuel** : 5 Form Requests sur 34 controllers. Les autres font de la validation inline (`$request->validate()`).
- **Action** :
  1. Auditer les 10 controllers principaux qui font store/update
  2. Extraire la validation inline vers des Form Requests dedies
  3. Priorite : RiskController, SupplierController, GovernanceController, SupplierContractController, SupplierInvoiceController, TeamController, RiskActionController, RiskControlController, ControlLibraryController, SystemSettingController
  4. Chaque Form Request doit avoir : rules(), authorize(), messages()
- **Effort** : 2-3 jours
- **Fichiers** :
  - `tavira-bow-api/app/Http/Requests/` (10-20 nouveaux fichiers)
  - Les 10 controllers concernes (remplacement $request->validate() par type-hint Form Request)

---

## Ordonnancement

```
T1 (Decision PM)     T2 (Rate limit)     T5 (Health)
     |                    |                   |
     v                    |                   |
T3 (Login history)       done               done
     |
     v
T4 (Responsive)     T6 (Form Requests)
     |                    |
     v                    v
          T7 (MAJ docs)
```

T2 + T5 peuvent demarrer immediatement (independants).
T1 doit etre tranchee avant T3 (impacts permissions).
T4 et T6 sont independants et parallelisables.
T7 (mise a jour ETAT-PROJET-BOW.md) quand tout est termine.

---

## Estimation globale

| Tache | Effort | Agents |
|-------|--------|--------|
| T1 | 1h (decision) | BOW-PM + BOW-ARCH |
| T2 | 1h | BOW-SEC + BOW-BACK |
| T3 | 1-2j | BOW-BACK + BOW-FRONT |
| T4 | 1-2j | BOW-FRONT + BOW-QA |
| T5 | 30min | BOW-BACK |
| T6 | 2-3j | BOW-QA + BOW-BACK |
| **Total** | **5-8 jours** | |

---

## Validation CI apres chaque tache

```bash
docker exec bow_api vendor/bin/pint --test
docker exec bow_api vendor/bin/phpstan analyse
docker exec bow_api php artisan test
cd tavira-bow-frontend && npx tsc --noEmit
```
