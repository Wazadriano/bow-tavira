# Tests Unitaires — BOW Tavira

## Structure des tests

Tous les tests se trouvent dans `tavira-bow-api/tests/Unit/` et utilisent **Pest** (wrapper PHP au-dessus de PHPUnit).

```
tavira-bow-api/tests/Unit/
├── Enums/
│   └── RiskTierTest.php            # Classification Tier A/B/C
├── Services/
│   ├── RAGCalculationServiceTest.php   # RAG Work Items (existait avant)
│   ├── RiskScoringServiceTest.php      # Score inherent (existait avant)
│   ├── RAGFromScoreTest.php            # Score -> couleur RAG
│   ├── RAGGovernanceTest.php           # RAG Governance Items
│   ├── ResidualScoreTest.php           # Score residuel + plafond 70%
│   ├── AppetiteStatusTest.php          # Statut appetence risque
│   ├── ImportTransformValueTest.php    # Conversion de valeurs a l'import
│   └── ImportDeduplicationTest.php     # TDD: deduplication (pas encore implemente)
```

---

## Resultats par fichier

### 1. RiskTierTest.php — Classification Tier (RG-BOW-005)

**Chemin** : `tavira-bow-api/tests/Unit/Enums/RiskTierTest.php`
**Methode testee** : `RiskTier::fromScore(int $score)`
**Resultat** : 10 PASS / 0 FAIL

Verifie que le score residuel d'un risque est correctement classifie :
- Score >= 9 → Tier A (Risque Eleve)
- Score 4-8 → Tier B (Risque Moyen)
- Score < 4 → Tier C (Risque Faible)

| Test | Score | Attendu | Resultat |
|------|-------|---------|----------|
| Cas normal haut | 15 | TIER_A | PASS |
| Cas normal moyen | 6 | TIER_B | PASS |
| Cas normal bas | 2 | TIER_C | PASS |
| Borne basse Tier A | 9 | TIER_A | PASS |
| Juste sous Tier A | 8 | TIER_B | PASS |
| Borne basse Tier B | 4 | TIER_B | PASS |
| Juste sous Tier B | 3 | TIER_C | PASS |
| Maximum (25) | 25 | TIER_A | PASS |
| Minimum (1) | 1 | TIER_C | PASS |
| Zero | 0 | TIER_C | PASS |

---

### 2. RAGFromScoreTest.php — Couleur RAG d'un score (RG-BOW-003)

**Chemin** : `tavira-bow-api/tests/Unit/Services/RAGFromScoreTest.php`
**Methode testee** : `RiskScoringService::getRAGFromScore(int $score)`
**Resultat** : 10 PASS / 0 FAIL

Verifie la correspondance entre un score de risque et sa couleur RAG :
- Score 1-4 → GREEN (Risque faible)
- Score 5-12 → AMBER (Risque moyen)
- Score 13-25 → RED (Risque eleve)

| Test | Score | Attendu | Resultat |
|------|-------|---------|----------|
| Cas normal vert | 2 | GREEN | PASS |
| Cas normal orange | 8 | AMBER | PASS |
| Cas normal rouge | 20 | RED | PASS |
| Borne haute green | 4 | GREEN | PASS |
| Borne basse amber | 5 | AMBER | PASS |
| Borne haute amber | 12 | AMBER | PASS |
| Borne basse red | 13 | RED | PASS |
| Minimum (1) | 1 | GREEN | PASS |
| Maximum (25) | 25 | RED | PASS |
| Zero | 0 | GREEN | PASS |

---

### 3. ImportTransformValueTest.php — Conversion de valeurs a l'import

**Chemin** : `tavira-bow-api/tests/Unit/Services/ImportTransformValueTest.php`
**Methode testee** : `ImportNormalizationService::transformValue($value, string $type)`
**Resultat** : 17 PASS / 0 FAIL

Verifie que les valeurs importees depuis un fichier Excel/CSV sont correctement converties :

| Test | Entree | Type | Attendu | Resultat |
|------|--------|------|---------|----------|
| null → string | null | string | null | PASS |
| null → int | null | int | null | PASS |
| Entier → string | 123 | string | "123" | PASS |
| String → integer | "42" | integer | 42 | PASS |
| String → int | "42" | int | 42 | PASS |
| Virgule europeenne → float | "3,14" | float | 3.14 | PASS |
| Virgule europeenne → decimal | "3,14" | decimal | 3.14 | PASS |
| Point → float | "3.14" | float | 3.14 | PASS |
| "yes" → bool | "yes" | bool | true | PASS |
| "true" → boolean | "true" | boolean | true | PASS |
| "no" → bool | "no" | bool | false | PASS |
| "0" → bool | "0" | bool | false | PASS |
| Date naturelle | "15 January 2025" | date | "2025-01-15" | PASS |
| Date ISO | "2025-01-15" | date | "2025-01-15" | PASS |
| Date invalide | "not-a-date" | date | null | PASS |
| JSON | '["a","b"]' | json | ["a","b"] | PASS |
| Type inconnu | "hello" | unknown | "hello" | PASS |

---

### 4. RAGGovernanceTest.php — RAG Governance Items (RG-BOW-001)

**Chemin** : `tavira-bow-api/tests/Unit/Services/RAGGovernanceTest.php`
**Methode testee** : `RAGCalculationService::calculateGovernanceRAG(GovernanceItem $item)`
**Resultat** : 3 PASS / 5 FAIL

**BUG DETECTE** : Le service utilise les mauvais noms de champs.

| Test | Attendu | Resultat | Cause |
|------|---------|----------|-------|
| Item complete → BLUE | BLUE | FAIL | `$item->status` devrait etre `$item->current_status` |
| Complete + due date passee → BLUE | BLUE | FAIL | Meme cause |
| Pas de due date → GREEN | GREEN | PASS | Passe "par accident" (null = pas de date) |
| Due date dans 20j → GREEN | GREEN | PASS | Passe "par accident" |
| Due date dans 5j → AMBER | AMBER | FAIL | `$item->due_date` devrait etre `$item->deadline` |
| Due date exactement 7j → AMBER | AMBER | FAIL | Meme cause |
| Due date hier → RED | RED | FAIL | Meme cause |
| Due date dans 8j → GREEN | GREEN | PASS | Passe "par accident" |

**Diagnostic** : Dans `calculateGovernanceRAG()`, le code accede a `$item->status` et `$item->due_date` alors que le modele `GovernanceItem` utilise `current_status` et `deadline`. Le service lit toujours `null` pour ces champs, ce qui fait que le RAG des items de gouvernance est toujours GREEN, quel que soit leur statut ou leur deadline.

**Impact metier** : Les items de gouvernance en retard n'affichent jamais RED. Les items termines n'affichent jamais BLUE. Le tableau de bord de gouvernance donne une fausse impression que tout va bien.

**Correction requise** : Modifier `RAGCalculationService::calculateGovernanceRAG()` pour utiliser `current_status` et `deadline`.

---

### 5. ResidualScoreTest.php — Score residuel + plafond 70% (RG-BOW-004)

**Chemin** : `tavira-bow-api/tests/Unit/Services/ResidualScoreTest.php`
**Methode testee** : `RiskScoringService::calculateResidualScore(Risk $risk)`
**Resultat** : 7 PASS / 0 FAIL

Verifie le calcul du score residuel apres application des controles, avec le plafond reglementaire de 70% :

| Test | Score inherent | Controles | Reduction | Residuel attendu | Resultat |
|------|---------------|-----------|-----------|-----------------|----------|
| Sans controles | 20 | aucun | 0% | 20 | PASS |
| 1 effective | 20 | effective (30%) | 30% | 14 | PASS |
| 1 partially_effective | 20 | partially (15%) | 15% | 17 | PASS |
| 1 ineffective | 20 | ineffective (0%) | 0% | 20 | PASS |
| 2 effective | 20 | 2x effective | 60% | 8 | PASS |
| 3 effective (plafond) | 20 | 3x effective (90%→70%) | 70% | 6 | PASS |
| Score minimum | 1 | effective (30%) | 30% | 1 (minimum) | PASS |

**Le plafond de 70% fonctionne correctement** : 3 controles "effective" representent theoriquement 90% de reduction, mais le systeme plafonne a 70%. Le score ne descend jamais en dessous de 1.

---

### 6. AppetiteStatusTest.php — Statut d'appetence (RG-BOW-006)

**Chemin** : `tavira-bow-api/tests/Unit/Services/AppetiteStatusTest.php`
**Methode testee** : `RiskScoringService::calculateAppetiteStatus(Risk $risk)`
**Resultat** : 8 PASS / 0 FAIL

Verifie la classification de l'appetence au risque en 3 niveaux :
- WITHIN : score residuel <= seuil du theme
- APPROACHING : score residuel <= seuil x 1.5
- EXCEEDED : score residuel > seuil x 1.5

| Test | Score residuel | Seuil | Attendu | Resultat |
|------|---------------|-------|---------|----------|
| Sous le seuil | 5 | 8 | WITHIN | PASS |
| Egal au seuil | 8 | 8 | WITHIN | PASS |
| Au-dessus du seuil | 9 | 8 | APPROACHING | PASS |
| Borne exacte 1.5x | 12 | 8 (x1.5=12) | APPROACHING | PASS |
| Au-dessus de 1.5x | 13 | 8 | EXCEEDED | PASS |
| Score max, seuil bas | 25 | 2 | EXCEEDED | PASS |
| Seuil par defaut (null) WITHIN | 7 | defaut (8) | WITHIN | PASS |
| Seuil par defaut (null) EXCEEDED | 15 | defaut (8) | EXCEEDED | PASS |

**Note** : Quand une categorie de risque n'a pas de seuil defini, le systeme utilise 8 par defaut.

---

### 7. ImportDeduplicationTest.php — TDD : Deduplication a l'import (RG-BOW-008)

**Chemin** : `tavira-bow-api/tests/Unit/Services/ImportDeduplicationTest.php`
**Methode testee** : `ImportNormalizationService::detectDuplicates()` — **N'EXISTE PAS ENCORE**
**Resultat** : 0 PASS / 3 FAIL (attendu)

Ce fichier illustre le principe du **TDD (Test-Driven Development)** :

1. **RED** (etat actuel) — On ecrit les tests AVANT le code. Les 3 tests echouent car la methode `detectDuplicates()` n'existe pas encore.
2. **GREEN** (prochaine etape) — Un developpeur implementera la methode pour faire passer les tests.
3. **REFACTOR** — Une fois les tests verts, on optimise le code.

| Test | Attendu | Resultat | Erreur |
|------|---------|----------|--------|
| Detecter les doublons exacts par ref_no | 1 doublon | FAIL | `Call to undefined method detectDuplicates()` |
| Pas de doublons → tableau vide | [] | FAIL | `Call to undefined method detectDuplicates()` |
| Doublons flous ("John Smith" / "J. Smith") | non-vide | FAIL | `Call to undefined method detectDuplicates()` |

**Pourquoi ce test est important** : Il prouve que notre suite de tests ne produit pas de faux positifs. Quand quelque chose ne marche pas, les tests le detectent. Les 3 FAIL confirment que le framework de test fonctionne correctement.

---

## Synthese globale

| Fichier | Tests | PASS | FAIL | Regle metier |
|---------|-------|------|------|-------------|
| RiskTierTest.php | 10 | 10 | 0 | RG-BOW-005 |
| RAGFromScoreTest.php | 10 | 10 | 0 | RG-BOW-003 |
| ImportTransformValueTest.php | 17 | 17 | 0 | RG-BOW-009 |
| RAGGovernanceTest.php | 8 | 3 | 5 | RG-BOW-001 (bug) |
| ResidualScoreTest.php | 7 | 7 | 0 | RG-BOW-004 |
| AppetiteStatusTest.php | 8 | 8 | 0 | RG-BOW-006 |
| ImportDeduplicationTest.php | 3 | 0 | 3 | RG-BOW-008 (TDD) |
| RAGCalculationServiceTest.php | 8 | 8 | 0 | RG-BOW-001 |
| RiskScoringServiceTest.php | 10 | 10 | 0 | RG-BOW-003 |
| **TOTAL** | **81** | **73** | **8** | |

### Bugs detectes

1. **RAGCalculationService::calculateGovernanceRAG()** utilise les mauvais noms de champs (`status`/`due_date` au lieu de `current_status`/`deadline`). A corriger par le Backend Expert.

### Fonctionnalites a implementer (TDD)

1. **ImportNormalizationService::detectDuplicates()** — Deduplication des donnees importees (RG-BOW-008). Les tests sont prets, il reste a coder la methode.

---

## Comment lancer les tests

Les tests se lancent depuis le container Docker `bow_api` ou le code est monte dans `/var/www/html`.

```bash
# Tous les tests unitaires
docker exec bow_api php /var/www/html/vendor/bin/pest /var/www/html/tests/Unit/

# Un fichier specifique
docker exec bow_api php /var/www/html/vendor/bin/pest /var/www/html/tests/Unit/Enums/RiskTierTest.php
docker exec bow_api php /var/www/html/vendor/bin/pest /var/www/html/tests/Unit/Services/RAGFromScoreTest.php
docker exec bow_api php /var/www/html/vendor/bin/pest /var/www/html/tests/Unit/Services/ResidualScoreTest.php
docker exec bow_api php /var/www/html/vendor/bin/pest /var/www/html/tests/Unit/Services/AppetiteStatusTest.php
docker exec bow_api php /var/www/html/vendor/bin/pest /var/www/html/tests/Unit/Services/ImportTransformValueTest.php
docker exec bow_api php /var/www/html/vendor/bin/pest /var/www/html/tests/Unit/Services/RAGGovernanceTest.php
docker exec bow_api php /var/www/html/vendor/bin/pest /var/www/html/tests/Unit/Services/ImportDeduplicationTest.php

```
