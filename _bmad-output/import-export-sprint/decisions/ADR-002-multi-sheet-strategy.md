# ADR-002: Stratégie de lecture multi-onglets Excel

**Status**: Accepted

**Date**: 2026-02-10

**Authors**: BOW Project Team

---

## Context

Le fichier XLSX "Transformation Work List Jan 2026.xlsx" contient **16 onglets**. La cible de l'import initial est l'onglet **"BOW List"**.

Situation actuelle:
- Code PHP utilise `$spreadsheet->getActiveSheet()` (onglet 1 par défaut)
- Seul l'onglet actif est lu
- Utilisateur n'a pas choix d'onglet
- L'onglet cible "BOW List" n'est pas déterminé par défaut

Problème: Si "BOW List" n'est pas le premier onglet, import échouera ou importera les mauvaises données.

Besoin:
- Énumérer tous les onglets disponibles
- Pré-sélectionner "BOW List" automatiquement
- Permettre à l'utilisateur de choisir un autre onglet si souhaité
- Passer le nom d'onglet au job de traitement

## Decision

Implémenter une **stratégie de sélection multi-onglets**:

### 1. Endpoint Preview (Phase 1)

Lors de l'upload du fichier XLSX:

```
POST /api/import-export/preview
Content-Type: multipart/form-data
- file: Transformation Work List Jan 2026.xlsx
```

**Réponse**:
```json
{
  "success": true,
  "file_id": "uuid-temp-upload",
  "sheets": [
    {
      "name": "BOW List",
      "row_count": 163,
      "is_target": true
    },
    {
      "name": "Onglet 2",
      "row_count": 45,
      "is_target": false
    },
    ...
  ],
  "selected_sheet": "BOW List"
}
```

**Logique côté Backend**:
```php
// PhpSpreadsheet
$sheetNames = $spreadsheet->getSheetNames(); // Array[16 strings]
foreach ($sheetNames as $name) {
    $sheet = $spreadsheet->getSheetByName($name);
    $rowCount = $sheet->getHighestRow();

    $isTarget = ($name === 'BOW List');
}

// Retourner au frontend avec "BOW List" pré-sélectionné
$selected = in_array('BOW List', $sheetNames) ? 'BOW List' : $sheetNames[0];
```

### 2. UI Component (Frontend)

Après upload:

1. **Afficher liste dropdown** des onglets
2. **"BOW List" sélectionné par défaut** (si présent)
3. **Allowed user to change** via Select component
4. **Show row count** pour chaque onglet (info pour user)

```tsx
<Select value={selectedSheet} onValueChange={setSelectedSheet}>
  {sheets.map(sheet => (
    <SelectItem key={sheet.name} value={sheet.name}>
      {sheet.name} ({sheet.row_count} rows)
    </SelectItem>
  ))}
</Select>
```

### 3. Endpoint Confirm Import

```
POST /api/import-export/confirm
{
  "file_id": "uuid-temp-upload",
  "selected_sheet": "BOW List"
}
```

Déclenche le job `ProcessImportFile` avec le paramètre `sheet_name`.

### 4. Job ProcessImportFile

```php
class ProcessImportFile implements ShouldQueue {
    public function handle(string $filePath, string $sheetName) {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName($sheetName);
        // Traiter l'onglet spécifique
    }
}
```

## Consequences

### Positives
- Utilisateur a **flexibilité** de choisir l'onglet
- "BOW List" **pré-sélectionné** par défaut (UX fluide)
- Code **robuste** si onglets réorganisés
- **Scalabilité** pour futures sources Excel multi-onglets
- **Transparence** user sait quels onglets disponibles

### Négatives/Mitigation
- **Deux appels API** (preview + confirm) vs un seul
  - Mitigé: Preview est rapide, améliore UX
- **Logique frontend** légèrement plus complexe
  - Mitigé: composant Select standard, cas d'usage simple
- **Stockage fichier temporaire**
  - Mitigé: UUID+cleanup après 24h

## Implementation Order

1. **Backend**: Route preview avec énumération onglets
2. **Frontend**: Composant upload → dropdown onglets
3. **Backend**: Route confirm avec job ProcessImportFile
4. **Tests**: Pest coverage multi-onglets

## Related Decisions

- ADR-001: Agent BOW-EXCEL
- ADR-003: Parsing dates
