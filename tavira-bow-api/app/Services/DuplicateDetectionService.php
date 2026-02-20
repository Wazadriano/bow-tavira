<?php

namespace App\Services;

use App\Models\GovernanceItem;
use App\Models\Risk;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\WorkItem;

class DuplicateDetectionService
{
    /**
     * Detect duplicates for a set of preview rows against existing DB records.
     *
     * @param  array  $previewRows  Rows from the file (raw arrays)
     * @param  array  $columnMapping  Column index => field name mapping
     * @param  string  $type  Import type (workitems, suppliers, risks, governance, invoices)
     * @return array Duplicate detection results
     */
    public function detect(array $previewRows, array $columnMapping, string $type): array
    {
        $duplicates = [];

        foreach ($previewRows as $index => $row) {
            $mappedRow = $this->mapRow($row, $columnMapping);
            $matches = $this->findMatches($mappedRow, $type);

            if (! empty($matches)) {
                $duplicates[] = [
                    'row_number' => $index + 2,
                    'imported_ref' => $mappedRow['ref_no'] ?? null,
                    'imported_name' => $this->extractName($mappedRow, $type),
                    'matches' => $matches,
                ];
            }
        }

        return $duplicates;
    }

    private function mapRow(array $row, array $columnMapping): array
    {
        $result = [];
        foreach ($columnMapping as $colIndex => $fieldName) {
            $result[$fieldName] = $row[$colIndex] ?? null;
        }

        return $result;
    }

    private function extractName(array $data, string $type): ?string
    {
        return match ($type) {
            'workitems' => $data['description'] ?? null,
            'suppliers' => $data['name'] ?? null,
            'risks' => $data['name'] ?? null,
            'governance' => $data['description'] ?? null,
            'invoices' => $data['invoice_ref'] ?? null,
            default => null,
        };
    }

    private function findMatches(array $data, string $type): array
    {
        return match ($type) {
            'workitems' => $this->findWorkItemMatches($data),
            'suppliers' => $this->findSupplierMatches($data),
            'risks' => $this->findRiskMatches($data),
            'governance' => $this->findGovernanceMatches($data),
            'invoices' => $this->findInvoiceMatches($data),
            default => [],
        };
    }

    private function findWorkItemMatches(array $data): array
    {
        $matches = [];
        $refNo = trim((string) ($data['ref_no'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $department = trim((string) ($data['department'] ?? ''));

        // Exact ref_no match
        if ($refNo !== '') {
            $existing = WorkItem::where('ref_no', $refNo)->first();
            if ($existing) {
                $matches[] = [
                    'id' => $existing->id,
                    'ref_no' => $existing->ref_no,
                    'name' => $existing->description,
                    'match_type' => 'exact_ref',
                    'confidence' => 100,
                    'action' => 'update',
                ];

                return $matches;
            }
        }

        // Fuzzy match: same department + similar description
        if ($description !== '' && $department !== '') {
            $keywords = $this->extractKeywords($description);
            if (count($keywords) >= 2) {
                $query = WorkItem::where('department', $department);
                foreach (array_slice($keywords, 0, 3) as $keyword) {
                    $query->where('description', 'ILIKE', "%{$keyword}%");
                }
                $candidates = $query->limit(3)->get();

                foreach ($candidates as $candidate) {
                    $similarity = $this->textSimilarity($description, $candidate->description ?? '');
                    if ($similarity >= 60) {
                        $matches[] = [
                            'id' => $candidate->id,
                            'ref_no' => $candidate->ref_no,
                            'name' => $candidate->description,
                            'match_type' => 'similar_description',
                            'confidence' => $similarity,
                            'action' => 'review',
                        ];
                    }
                }
            }
        }

        return $matches;
    }

    private function findSupplierMatches(array $data): array
    {
        $matches = [];
        $refNo = trim((string) ($data['ref_no'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));

        if ($refNo !== '') {
            $existing = Supplier::where('ref_no', $refNo)->first();
            if ($existing) {
                $matches[] = [
                    'id' => $existing->id,
                    'ref_no' => $existing->ref_no,
                    'name' => $existing->name,
                    'match_type' => 'exact_ref',
                    'confidence' => 100,
                    'action' => 'update',
                ];

                return $matches;
            }
        }

        if ($name !== '') {
            $candidates = Supplier::where('name', 'ILIKE', "%{$name}%")
                ->orWhere('name', 'ILIKE', $name.'%')
                ->limit(3)
                ->get();

            foreach ($candidates as $candidate) {
                $similarity = $this->textSimilarity($name, $candidate->name ?? '');
                if ($similarity >= 70) {
                    $matches[] = [
                        'id' => $candidate->id,
                        'ref_no' => $candidate->ref_no,
                        'name' => $candidate->name,
                        'match_type' => 'similar_name',
                        'confidence' => $similarity,
                        'action' => 'review',
                    ];
                }
            }
        }

        return $matches;
    }

    private function findRiskMatches(array $data): array
    {
        $matches = [];
        $refNo = trim((string) ($data['ref_no'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));

        if ($refNo !== '') {
            $existing = Risk::where('ref_no', $refNo)->first();
            if ($existing) {
                $matches[] = [
                    'id' => $existing->id,
                    'ref_no' => $existing->ref_no,
                    'name' => $existing->name,
                    'match_type' => 'exact_ref',
                    'confidence' => 100,
                    'action' => 'update',
                ];

                return $matches;
            }
        }

        if ($name !== '') {
            $keywords = $this->extractKeywords($name);
            if (count($keywords) >= 2) {
                $query = Risk::query();
                foreach (array_slice($keywords, 0, 3) as $keyword) {
                    $query->where('name', 'ILIKE', "%{$keyword}%");
                }
                $candidates = $query->limit(3)->get();

                foreach ($candidates as $candidate) {
                    $similarity = $this->textSimilarity($name, $candidate->name ?? '');
                    if ($similarity >= 65) {
                        $matches[] = [
                            'id' => $candidate->id,
                            'ref_no' => $candidate->ref_no,
                            'name' => $candidate->name,
                            'match_type' => 'similar_name',
                            'confidence' => $similarity,
                            'action' => 'review',
                        ];
                    }
                }
            }
        }

        return $matches;
    }

    private function findGovernanceMatches(array $data): array
    {
        $matches = [];
        $refNo = trim((string) ($data['ref_no'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $department = trim((string) ($data['department'] ?? ''));

        if ($refNo !== '') {
            $existing = GovernanceItem::where('ref_no', $refNo)->first();
            if ($existing) {
                $matches[] = [
                    'id' => $existing->id,
                    'ref_no' => $existing->ref_no,
                    'name' => $existing->description,
                    'match_type' => 'exact_ref',
                    'confidence' => 100,
                    'action' => 'update',
                ];

                return $matches;
            }
        }

        if ($description !== '' && $department !== '') {
            $keywords = $this->extractKeywords($description);
            if (count($keywords) >= 2) {
                $query = GovernanceItem::where('department', $department);
                foreach (array_slice($keywords, 0, 3) as $keyword) {
                    $query->where('description', 'ILIKE', "%{$keyword}%");
                }
                $candidates = $query->limit(3)->get();

                foreach ($candidates as $candidate) {
                    $similarity = $this->textSimilarity($description, $candidate->description ?? '');
                    if ($similarity >= 60) {
                        $matches[] = [
                            'id' => $candidate->id,
                            'ref_no' => $candidate->ref_no,
                            'name' => $candidate->description,
                            'match_type' => 'similar_description',
                            'confidence' => $similarity,
                            'action' => 'review',
                        ];
                    }
                }
            }
        }

        return $matches;
    }

    private function findInvoiceMatches(array $data): array
    {
        $matches = [];
        $supplierRef = trim((string) ($data['supplier_ref'] ?? ''));
        $invoiceRef = trim((string) ($data['invoice_ref'] ?? ''));

        if ($supplierRef === '' || $invoiceRef === '') {
            return $matches;
        }

        $supplier = Supplier::where('ref_no', $supplierRef)->first();
        if (! $supplier) {
            return $matches;
        }

        $existing = SupplierInvoice::where('supplier_id', $supplier->id)
            ->where('invoice_ref', $invoiceRef)
            ->first();

        if ($existing) {
            $matches[] = [
                'id' => $existing->id,
                'ref_no' => $invoiceRef,
                'name' => $existing->description,
                'match_type' => 'exact_ref',
                'confidence' => 100,
                'action' => 'update',
            ];
        }

        return $matches;
    }

    /**
     * Calculate text similarity percentage between two strings.
     */
    private function textSimilarity(string $a, string $b): int
    {
        $a = mb_strtolower(trim($a));
        $b = mb_strtolower(trim($b));

        if ($a === $b) {
            return 100;
        }

        if ($a === '' || $b === '') {
            return 0;
        }

        similar_text($a, $b, $percent);

        return (int) round($percent);
    }

    /**
     * Extract significant keywords from a text (ignore short/common words).
     */
    private function extractKeywords(string $text): array
    {
        $stopWords = ['the', 'and', 'for', 'with', 'from', 'that', 'this', 'into', 'over',
            'les', 'des', 'une', 'pour', 'dans', 'avec', 'sur', 'par', 'est', 'sont',
            'de', 'la', 'le', 'du', 'au', 'en', 'et', 'ou', 'un', 'a', 'to', 'of', 'in', 'on', 'at', 'is'];

        $words = preg_split('/[\s\-_\/,;.()]+/', mb_strtolower(trim($text)));
        $keywords = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) >= 3 && ! in_array($word, $stopWords, true)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }
}
