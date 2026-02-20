# Sprint: Import/Export Feature - BOW Project

## Objectif

Rendre fonctionnelle la feature Import/Export pour le fichier **"Transformation Work List Jan 2026.xlsx"** contenant:
- **162 work items**
- **16 onglets**
- **31 colonnes**
- Formats de données hétérogènes (dates, noms, enums, formules Excel)

## Critères d'Acceptation

1. **Page /import-export accessible** depuis la sidebar (lien MANAGEMENT)
2. **Upload XLSX affiche les 16 onglets**, "BOW List" pré-sélectionné par défaut
3. **Import "BOW List" mappe >= 20/25 colonnes** automatiquement
4. **Dates "Jul 2026" parsées correctement** en 2026-07-01
5. **162 work items créés/mis à jour** avec tous champs mappés
6. **Colonnes formule (V, X) ignorées** automatiquement
7. **Noms de personnes résolus** quand correspondance exacte ou email
8. **Tests Pest verts** pour toutes les fonctionnalités import
9. **Pipeline CI inclut les tests import**

## Architecture et Phases

### Phase 0: Setup
- Création du dossier sprint et documentation
- Création de l'agent BOW-EXCEL (specs)
- Architecture décisionnelle (ADRs)

### Phase 1: Accès & Infrastructure
- Route `/import-export` accessible depuis MANAGEMENT
- Upload endpoint avec parsing XLSX
- Liste des onglets retournée au frontend

### Phase 2: Schéma & Mapping
- Mapping automatique colonnes Excel -> champs database
- Parser dates supportant formats "M Y" et "F Y"
- Résolution noms de personnes
- Normalisation données

### Phase 3: Robustesse & Qualité
- Tests Pest exhaustifs (upload, parsing, mapping, validation)
- Gestion erreurs et edge cases
- Pipeline CI validant les tests
- Documentation utilisateur

## Agents Assignés

| Agent | Rôle | Responsabilités |
|-------|------|-----------------|
| **BOW-EXCEL** | Spécialiste Excel | Conçoit specs mapping, normalisation, formats |
| **BOW-BACK** | Implémentation Backend | Code PHP/Laravel, routes, jobs, models |
| **BOW-FRONT** | UI/UX Frontend | Composant upload, dropdown onglets, preview |
| **BOW-QA** | Tests & Qualité | Tests Pest, edge cases, validation |
| **BOW-SEC** | Sécurité | Validation fichiers XLSX, sanitisation données |
| **BOW-GH** | CI/CD | Pipeline GitHub Actions, tests import |

## Décisions Architecturales (ADRs)

- **ADR-001**: Création de l'agent BOW-EXCEL
- **ADR-002**: Stratégie de lecture multi-onglets Excel
- **ADR-003**: Stratégie de parsing des dates

## Livrables

```
_bmad-output/import-export-sprint/
├── README.md (ce fichier)
├── decisions/
│   ├── ADR-001-excel-agent.md
│   ├── ADR-002-multi-sheet-strategy.md
│   └── ADR-003-date-parsing-strategy.md
├── specifications/ (à remplir par BOW-EXCEL)
│   ├── column-mapping.yaml
│   ├── data-normalization.md
│   └── person-resolution.md
├── implementation/ (à remplir par BOW-BACK)
│   ├── routes.php
│   ├── ImportController.php
│   ├── ProcessImportFileJob.php
│   └── ExcelParser.php
└── tests/ (à remplir par BOW-QA)
    └── Feature/ImportExportTest.php
```

## Convention de Dates

Lors du parsing de dates au format "Mon YYYY" (ex: "Jul 2026"), la date est interprétée comme le **1er du mois** de l'année spécifiée:
- "Jul 2026" → 2026-07-01
- "Jan 2026" → 2026-01-01
- "Dec 2026" → 2026-12-01

Cela permet une normalisation cohérente sans perte d'information.

## Méthodologie

- **Merise Agile + TDD**: Tests d'abord, implémentation ensuite
- **Challenge Before Confirm**: Valider les specs avant implémentation
- **Zero Trust**: Valider toutes les données d'import
- **Clean Code**: Code auto-documenté, pas de commentaires superflus
