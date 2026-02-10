# ADR-003: Stratégie de parsing des dates

**Status**: Accepted

**Date**: 2026-02-10

**Authors**: BOW Project Team

---

## Context

Le fichier XLSX "Transformation Work List Jan 2026.xlsx" contient des dates en plusieurs formats:

1. **Format "Mon YYYY"** (ex: "Jul 2026")
   - 3-letter month abbreviation + 4-digit year
   - Non parsé par le parser actuel (strtotime, Carbon)
   - Représente une période (le mois entier)

2. **Format "Full Month YYYY"** (ex: "January 2026")
   - Texte complet du mois + year
   - Pareillement non parsé

3. **Format Excel Serial** (ex: 45500)
   - Nombre décimal > 40000
   - Représente un jour depuis 1900-01-01 (convention Excel)
   - Doit être converti via PhpSpreadsheet `Date::excelToDateTimeObject()`

4. **Format standard ISO** (ex: "2026-07-01")
   - Déjà supporté par Carbon/strtotime
   - No conversion needed

**Problème actuel**: Le parser ne reconnaît que format standard, autres formats échouent silencieusement ou sont traités comme NULL.

**Décision précédente**: Interpréter "Jul 2026" et "January 2026" comme le **1er du mois** (2026-07-01, 2026-01-01), car:
- Représente la période sans perte d'information critique
- Normalise à un jour fixe pour comparaisons/tri
- Convention documentée et cohérente

## Decision

Implémenter une **stratégie multi-format de parsing de dates** avec **détection automatique du type**:

### 1. Ordre de Priorité

Tenter les formats dans cet ordre:

1. **Format ISO standard** (Y-m-d): `strtotime()` ou `Carbon::parse()`
2. **Format "M Y"** (3-letter month + year): Regex + matching
3. **Format "F Y"** (Full month + year): Regex + matching
4. **Format Excel Serial** (float > 40000): `Date::excelToDateTimeObject()`
5. **Fallback strtotime**: Laisser PHP/strtotime essayer
6. **Null si échec**: Marquer comme non-parsable

### 2. Détection Type

```php
$value = (mixed) cellValue; // Can be string or float

// Type detection
if (is_float($value) && $value > 40000) {
    // Excel serial format
    $date = DateFactory::excelToDateTimeObject($value);
} elseif (is_string($value)) {
    // Text formats
    if (preg_match('/^([A-Za-z]{3})\s+(\d{4})$/', $value, $m)) {
        // Format "Jul 2026"
        $month = DateTime::createFromFormat('M', $m[1])->format('m');
        $date = Carbon::createFromDate($m[2], $month, 1);
    } elseif (preg_match('/^([A-Za-z]+)\s+(\d{4})$/', $value, $m)) {
        // Format "January 2026"
        $month = DateTime::createFromFormat('F', $m[1])->format('m');
        $date = Carbon::createFromDate($m[2], $month, 1);
    } else {
        // Try ISO or strtotime
        $date = Carbon::parse($value);
    }
}
```

### 3. Convention de Représentation

Toute date parsée à partir d'une **périodes (M Y, F Y)** est normalisée au **1er du mois**:

| Input | Parsed | SQL |
|-------|--------|-----|
| "Jul 2026" | 2026-07-01 | `2026-07-01 00:00:00` |
| "January 2026" | 2026-01-01 | `2026-01-01 00:00:00` |
| "45500" (Excel) | 2026-07-01 | `2026-07-01 00:00:00` |
| "2026-07-01" | 2026-07-01 | `2026-07-01 00:00:00` |
| "01/07/2026" | 2026-07-01 | `2026-07-01 00:00:00` |
| Invalid | NULL | `NULL` |

### 4. Exemple Implémentation

```php
// app/Services/ExcelParser.php
class DateFormatter {
    public static function parse($value): ?Carbon {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Excel serial (float > 40000)
        if (is_float($value) && $value > 40000) {
            try {
                return Date::excelToDateTimeObject($value);
            } catch (Exception $e) {
                return null;
            }
        }

        if (is_string($value)) {
            $value = trim($value);

            // Format "Jul 2026" (3-letter month)
            if (preg_match('/^([A-Za-z]{3})\s+(\d{4})$/', $value, $m)) {
                try {
                    $month = DateTime::createFromFormat('M', $m[1])->format('m');
                    return Carbon::createFromDate($m[2], $month, 1);
                } catch (Exception $e) {
                    return null;
                }
            }

            // Format "January 2026" (full month)
            if (preg_match('/^([A-Za-z]+)\s+(\d{4})$/', $value, $m)) {
                try {
                    $month = DateTime::createFromFormat('F', $m[1])->format('m');
                    return Carbon::createFromDate($m[2], $month, 1);
                } catch (Exception $e) {
                    return null;
                }
            }

            // Try ISO/strtotime
            try {
                return Carbon::parse($value);
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }
}
```

### 5. Validation & Error Handling

- **Parse success**: Date stored in database
- **Parse failure**: Log warning avec valeur originale et column name
- **User feedback**: Import report show parsing errors (ligne X, colonne Y, valeur "???")
- **Non-blocking**: Import continue même si quelques dates invalides

## Consequences

### Positives
- **Flexibilité**: Supporte 4+ formats de dates différents
- **User-friendly**: Pas d'erreur pour "Jul 2026" ou "January 2026"
- **Robustesse**: Fallback strtotime en cas d'autres formats
- **Normalisé**: Toutes dates au format ISO 8601 en DB
- **Transparent**: Convention "1er du mois" documentée
- **Testable**: Chaque format a cas de test dedicated

### Négatives/Mitigation
- **Ambiguité parsing**: "Jul" pourrait être juillet (EN) ou autre langue
  - Mitigé: Documentation clé, locale future improvement
- **Perte info**: "Jul 2026" → "2026-07-01" (jour spécifique perdu)
  - Mitigé: C'était le but (période → date fixe)
- **Performance**: Regex + multiple try/catch
  - Mitigé: Parsing une fois au upload, négligeable

## Related Decisions

- ADR-001: Agent BOW-EXCEL (specs détaillées format dates)
- ADR-002: Multi-sheet strategy
