# ADR-001: Création de l'agent BOW-EXCEL

**Status**: Accepted

**Date**: 2026-02-10

**Authors**: BOW Project Team

---

## Context

Le fichier XLSX "Transformation Work List Jan 2026.xlsx" contient 31 colonnes hétérogènes à mapper vers la structure database BOW. Les défis incluent:

- **25 colonnes** à mapper automatiquement
- **Formats de dates** variés ("Jul 2026", format Excel serial)
- **Types de données** mixtes (string, date, enum, numeric, formula)
- **Colonnes formule** (V, X) à ignorer intelligemment
- **Résolution de noms** de personnes (correspondance exacte, email fallback)
- **Normalisation** des valeurs (trimming, case, enums valides)

Les agents existants (BOW-BACK, BOW-FRONT, BOW-QA) couvrent implémentation, UI, tests. Aucun ne détient l'expertise Excel spécifique requise pour concevoir le **mapping** et **normalisation**.

## Decision

**Créer un agent spécialisé BOW-EXCEL** responsable de:

1. **Conception des specs de mapping**: Déterminer quelles colonnes Excel mapent à quels champs database
2. **Stratégie de normalisation**: Format des données après parse
3. **Formats de dates**: Parsing et convention de représentation
4. **Résolution de personnes**: Logique de matching avec users/contacts
5. **Validation**: Règles de validation avant import
6. **Documentation**: Specs détaillées pour implémentation par BOW-BACK

**Separation of Concerns**:
- **BOW-EXCEL** = Conception, specs, architecture métier
- **BOW-BACK** = Implémentation PHP/Laravel, routes, jobs, models

### Artefacts BOW-EXCEL

BOW-EXCEL produit dans `_bmad-output/import-export-sprint/specifications/`:
- `column-mapping.yaml`: Mappage Excel → DB (type, validation, conversion)
- `data-normalization.md`: Règles de normalisation par type
- `person-resolution.md`: Logique résolution noms/emails
- `validation-rules.md`: Contraintes et validations
- `edge-cases.md`: Cas limites et comment les gérer

## Consequences

### Positives
- Expertise Excel centralisée, réutilisable
- Specs claires pour BOW-BACK (moins d'allers-retours)
- Scalabilité: Ajouter d'autres sources Excel futurement
- Documentation métier exhaustive
- Réduction risque d'erreur mapping/normalisation

### Négatives/Mitigation
- Création nouveau rôle d'agent (bonne pratique)
- BOW-BACK dépend specs BOW-EXCEL (mitigé par ADRs)
- Nécessite coordination BOW-EXCEL ↔ BOW-BACK

## Implementation Notes

1. BOW-EXCEL charge `project-context-bow.yaml`
2. BOW-EXCEL analyse le fichier XLSX réel pour proposer mappage
3. Specs BOW-EXCEL versionnées dans git
4. BOW-BACK implémente exactement selon specs
5. Modifications specs = nouvelle décision (ADR ou Change Request)

## Related Decisions

- ADR-002: Stratégie multi-onglets Excel
- ADR-003: Stratégie parsing dates
