# Plan de Tests - Import Excel End-to-End

## Commandes de test

### 1. Tests unitaires enum normalisation
```bash
cd tavira-bow-api && vendor/bin/pest tests/Unit/Services/ImportEnumNormalizationTest.php --no-coverage
```

**Fichier** : `tavira-bow-api/tests/Unit/Services/ImportEnumNormalizationTest.php`

**Ce qui est teste** :
- `normalizeEnumValue()` avec null, empty string -> null
- BAUType : exact match ("BAU", "Non BAU"), case-insensitive ("bau"), aliases ("growth" -> "Non BAU", "transformative" -> "Non BAU", "non-bau" -> "Non BAU", "Business As Usual" -> "BAU")
- CurrentStatus : exact match, aliases ("not started" -> "Not Started", "not_started", "in progress", "in_progress", "done" -> "Completed", "pending" -> "On Hold")
- ImpactLevel : exact match, numeriques ("3" -> "High", "2" -> "Medium", "1" -> "Low"), lettres ("h", "m", "l")
- RAGStatus : exact match, aliases ("b" -> "Blue", "g" -> "Green", "a" -> "Amber", "orange" -> "Amber", "r" -> "Red")
- UpdateFrequency : exact match, aliases ("annual" -> "Annually", "yearly", "bi-annual" -> "Semi Annually", "quarter" -> "Quarterly", "month" -> "Monthly", "week" -> "Weekly")
- Valeur inconnue -> null + warning
- Whitespace trimming

---

### 2. Tests multi-sheet et mapping
```bash
cd tavira-bow-api && vendor/bin/pest tests/Unit/Jobs/ProcessImportMultiSheetTest.php --no-coverage
```

**Fichier** : `tavira-bow-api/tests/Unit/Jobs/ProcessImportMultiSheetTest.php`

**Ce qui est teste** :
- `getExpectedColumns('workitems')` contient bien les alias Excel (number -> ref_no, impacted_area -> department, etc.)
- `mapColumns()` auto-mappe correctement les 25 headers Excel reels vers les champs DB
- `mapColumns()` mappe un sous-ensemble 13 colonnes (style Will Rebecca) correctement
- BOW List est trie en premier dans la liste des sheets
- `isRowEmpty()` detecte les lignes vides (null, "", espaces) et non-vides

---

### 3. Tests existants a relancer (regression)
```bash
cd tavira-bow-api && vendor/bin/pest tests/Unit/Services/ImportDateParsingTest.php --no-coverage
cd tavira-bow-api && vendor/bin/pest tests/Unit/Services/ImportUserLookupTest.php --no-coverage
cd tavira-bow-api && vendor/bin/pest tests/Unit/Services/ImportTransformValueTest.php --no-coverage
cd tavira-bow-api && vendor/bin/pest tests/Unit/Services/ImportDeduplicationTest.php --no-coverage
```

---

### 4. Tous les tests Import en une commande
```bash
cd tavira-bow-api && vendor/bin/pest --filter=Import --no-coverage
```

---

### 5. Analyse statique Larastan
```bash
cd tavira-bow-api && vendor/bin/phpstan analyse --memory-limit=512M
```

**Points a verifier** :
- Les imports d'enums dans `ProcessImportFile.php` et `ImportNormalizationService.php`
- Le type `array|string|null` pour `$sheetNames` dans le constructeur du Job
- L'utilisation de `\PhpOffice\PhpSpreadsheet\Cell\Coordinate` dans le controller

---

### 6. Linting Pint
```bash
cd tavira-bow-api && vendor/bin/pint --test
```

Si des erreurs, corriger avec :
```bash
cd tavira-bow-api && vendor/bin/pint
```

---

### 7. Verification Frontend TypeScript
```bash
cd tavira-bow-frontend && npx tsc --noEmit
```

**Points a verifier** :
- Interface `SheetInfo` exportee depuis `stores/import.ts`
- Props `sheetInfo`, `selectedSheets`, `toggleAllImportable` dans le store
- Import de `Checkbox` et `Badge` dans `import-export/page.tsx`

---

### 8. Test End-to-End Manuel

**Scenario 1 : Import single sheet**
1. Uploader "Transformation Work List Jan 2026.xlsx"
2. Verifier que les 16 onglets apparaissent avec leur info (lignes, colonnes, importable/non)
3. Decocher tout sauf "BOW List"
4. Verifier l'auto-mapping des 25 colonnes
5. Lancer l'import
6. Verifier que la barre de progression avance (polling)
7. Verifier le message final "X crees, Y mis a jour, Z ignores"

**Scenario 2 : Import multi-sheet**
1. Cocher "Importer tous les onglets compatibles" (9 onglets)
2. Lancer l'import
3. Verifier que BOW List est traite en premier
4. Verifier la dedup par ref_no (les doublons dans les sheets departement skippes)
5. Verifier le total : ~200-250 items uniques

**Scenario 3 : Enum normalisation dans les donnees**
- Verifier que "BAU" dans le Excel -> BAUType::BAU en base
- Verifier que "not started" -> CurrentStatus::NOT_STARTED
- Verifier que "3" (impact) -> ImpactLevel::HIGH
- Verifier que les lignes vides en fin de sheet sont skippees

---

## Resume des fichiers de tests crees/modifies

| Fichier | Statut | Tests |
|---------|--------|-------|
| `tests/Unit/Services/ImportEnumNormalizationTest.php` | NOUVEAU | 13 tests |
| `tests/Unit/Jobs/ProcessImportMultiSheetTest.php` | NOUVEAU | 5 tests |

## Resume des fichiers modifies (code)

| Fichier | Phase | Changements |
|---------|-------|-------------|
| `app/Services/ImportNormalizationService.php` | T1.1, T1.2 | +normalizeEnumValue(), +getExpectedColumns(), +ENUM_ALIASES |
| `app/Http/Controllers/Api/ImportExportController.php` | T1.2, T3.1 | sheet_info dans preview, sheet_names dans confirm, getExpectedColumns via service |
| `app/Jobs/ProcessImportFile.php` | T1.3, T3.2 | Enum normalization, multi-sheet loop, dedup, isRowEmpty, per-sheet remapping |
| `app/Http/Requests/Import/ConfirmImportRequest.php` | T3.1 | +sheet_names validation |
| `tavira-bow-frontend/src/stores/import.ts` | T2.1, T3.3, T4.1 | Polling async, SheetInfo, selectedSheets, toggleAllImportable, rag_status |
| `tavira-bow-frontend/src/components/import-export/import-progress.tsx` | T2.2 | Progress bar utilise percentage backend |
| `tavira-bow-frontend/src/app/(dashboard)/import-export/page.tsx` | T3.3 | Checkboxes multi-sheet avec Checkbox/Badge |
