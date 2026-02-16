# _bmad-output/ - Index de navigation

Organisation des livrables BMAD/BYAN pour le projet BOW.

---

## Fichiers actifs (racine)

| Fichier | Role | Quand le consulter |
|---------|------|--------------------|
| **ETAT-PROJET-BOW.md** | Reference unique de l'etat du projet | Pour savoir ou on en est globalement |
| **PLAN-QA-ACTIONS.md** | Plan QA complet : 11 taches, agents, ordonnancement 6 jours | **PROCHAINE SESSION** - reprendre ici |
| **PLAN-ACTION.md** | Taches fonctionnelles T1-T6 (toutes completees) | Historique des taches deja faites |
| **PLAN-CONFIG-PROD.md** | Checklist configuration production | Avant deploiement en prod |
| **README.md** | Ce fichier - index de navigation | Pour s'orienter dans le dossier |

---

## Dossiers

### bmb-creations/

Agents BOW specialises et contexte projet. Crees par BYAN.

| Fichier | Agent | Domaine |
|---------|-------|---------|
| project-context-bow.yaml | - | Contexte complet du projet (charge par tous les agents) |
| bow-product-manager.md | BOW-PM | Specs, regles metier, glossaire, 5 modules |
| bow-architect.md | BOW-ARCH | Architecture, Docker/Traefik, ADR |
| bow-backend-expert.md | BOW-BACK | Laravel 11, PostgreSQL, 150+ endpoints, TDD |
| bow-frontend-expert.md | BOW-FRONT | Next.js 15, Shadcn/ui, Zustand |
| bow-qa-tester.md | BOW-QA | Tests TDD, edge cases, permissions |
| bow-devsecops.md | BOW-SEC | Securite OWASP, hardening, secrets |
| bow-github-expert.md | BOW-GH | CI/CD, GitHub Actions, PRs |
| bow-excel-specialist.md | BOW-EXCEL | Import/export Excel, multi-sheet |

### archive/

Documents de planification et audits historiques. Conserves pour reference.

| Fichier | Contenu | Date |
|---------|---------|------|
| PLAN-EXECUTION-COMPLET.md | Plan initial 6 phases (avant implementation) | 11 fev |
| PLAN-RESTANT-COMPLET.md | Dernier suivi phases A-D (remplace par ETAT-PROJET-BOW.md) | 11 fev |
| MODIFICATIONS-A-FAIRE.md | Liste modifications (perime, pre-PR #10) | 11 fev |
| audit-frontend-backend-gap.md | Audit ecarts front/back | 11 fev |
| cdc-vs-code-gaps.md | Audit CDC vs code | 11 fev |
| backend-routes-to-add.md | Routes backend manquantes (toutes ajoutees) | 11 fev |
| frontend-fixes-remaining.md | Fixes frontend restants (tous faits) | 11 fev |
| milestones-analysis.md | Analyse milestones parite POC | 11 fev |
| parity-poc-refonte-and-fixes.md | Parite POC et corrections | 11 fev |

### import-export-sprint/

Documentation du sprint import/export Excel.

| Fichier | Contenu |
|---------|---------|
| README.md | Vue d'ensemble du sprint import/export |
| TEST_PLAN.md | Plan de tests import/export |
| decisions/ADR-001-excel-agent.md | Decision : agent specialise Excel |
| decisions/ADR-002-multi-sheet-strategy.md | Decision : strategie multi-feuilles |
| decisions/ADR-003-date-parsing-strategy.md | Decision : parsing des dates |
| decisions/ADR-004-permissions-vs-manager-role.md | Decision : permissions granulaires vs role Manager |

---

## Routing agents

| Question sur... | Agent a utiliser |
|-----------------|------------------|
| Specs, regles metier, modules | BOW-PM |
| Architecture, infra, Docker | BOW-ARCH |
| Laravel, API, backend | BOW-BACK |
| Next.js, UI, frontend | BOW-FRONT |
| Tests, qualite | BOW-QA |
| Securite, OWASP | BOW-SEC |
| CI/CD, GitHub | BOW-GH |
| Import/export Excel | BOW-EXCEL |
