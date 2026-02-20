<?php

namespace App\Services;

use App\Enums\BAUType;
use App\Enums\CurrentStatus;
use App\Enums\ImpactLevel;
use App\Enums\RAGStatus;
use App\Enums\UpdateFrequency;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportNormalizationService
{
    private array $errors = [];

    private array $warnings = [];

    private array $userCache = [];

    /**
     * Get actual column count by scanning header row for last non-null cell.
     * Prevents PhpSpreadsheet Table object inflation (e.g. 16383 cols on "Operations Will").
     */
    public static function getActualColumnCount(Worksheet $sheet): int
    {
        $maxCol = 500;
        $lastNonNull = 0;

        for ($col = 1; $col <= $maxCol; $col++) {
            $value = $sheet->getCellByColumnAndRow($col, 1)->getValue();
            if ($value !== null && trim((string) $value) !== '') {
                $lastNonNull = $col;
            }
        }

        return $lastNonNull;
    }

    /**
     * Convert worksheet to array using only the actual data range.
     * Replaces toArray() which may return 16383 columns due to Table objects.
     */
    public static function sheetToLimitedArray(Worksheet $sheet): array
    {
        $colCount = self::getActualColumnCount($sheet);
        if ($colCount === 0) {
            return [];
        }

        $highestRow = $sheet->getHighestDataRow();
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
        $range = "A1:{$colLetter}{$highestRow}";

        return $sheet->rangeToArray($range, null, true, true, false);
    }

    /**
     * Parse CSV file content
     */
    public function parseCSV(string $content, ?string $delimiter = null): array
    {
        $this->errors = [];
        $this->warnings = [];

        // Detect encoding and convert to UTF-8
        $content = $this->normalizeEncoding($content);

        // Detect delimiter if not provided
        $delimiter = $delimiter ?? $this->detectDelimiter($content);

        // Parse lines
        $lines = str_getcsv($content, "\n");
        $data = [];

        foreach ($lines as $lineNum => $line) {
            if (empty(trim($line))) {
                continue;
            }

            $row = str_getcsv($line, $delimiter);
            $data[] = array_map(fn ($cell) => $this->normalizeCell($cell), $row);
        }

        return $data;
    }

    /**
     * Normalize encoding to UTF-8
     */
    private function normalizeEncoding(string $content): string
    {
        // Try to detect encoding (MacRoman added to handle Mac-origin files)
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ASCII', 'Windows-1252', 'ISO-8859-1'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            $this->warnings[] = "Encoding converted from {$encoding} to UTF-8";
        }

        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Fix MacRoman artifacts that survive encoding detection
        $content = $this->fixMacRomanArtifacts($content);

        return $content;
    }

    /**
     * MacRoman byte values misinterpreted as Latin-1/ISO-8859-1 produce
     * wrong Unicode codepoints. Only unambiguous replacements are applied:
     * - Ò, Ó, Õ, Ð are extremely rare in English/French business text
     * - Ô, É, Ñ are excluded: they are valid French/Spanish characters
     */
    private const MACROMAN_ARTIFACT_MAP = [
        "\u{00D2}" => "\u{201C}", // Ò -> left double quote (not used in French/English)
        "\u{00D3}" => "\u{201D}", // Ó -> right double quote (not used in French/English)
        "\u{00D5}" => "\u{2019}", // Õ -> right single quote (Portuguese only, not FR/EN)
        "\u{00D0}" => "\u{2013}", // Ð -> en-dash (Icelandic only, not FR/EN)
    ];

    public function fixMacRomanArtifacts(string $content): string
    {
        return strtr($content, self::MACROMAN_ARTIFACT_MAP);
    }

    /**
     * Sanitize an array of rows from Excel import (toArray output).
     * Applies MacRoman artifact fix and cell normalization to each cell.
     */
    public function sanitizeExcelData(array $data): array
    {
        return array_map(function (array $row) {
            return array_map(function ($cell) {
                if (! is_string($cell) || $cell === '') {
                    return $cell;
                }

                return $this->fixMacRomanArtifacts($cell);
            }, $row);
        }, $data);
    }

    /**
     * Detect CSV delimiter
     */
    private function detectDelimiter(string $content): string
    {
        $firstLine = strtok($content, "\n");

        $delimiters = [
            ',' => substr_count($firstLine, ','),
            ';' => substr_count($firstLine, ';'),
            "\t" => substr_count($firstLine, "\t"),
            '|' => substr_count($firstLine, '|'),
        ];

        $delimiter = array_search(max($delimiters), $delimiters);
        Log::info("Detected delimiter: '{$delimiter}'");

        return $delimiter;
    }

    /**
     * Normalize cell value
     */
    private function normalizeCell(string $value): ?string
    {
        $value = trim($value);

        // Handle empty values
        if ($value === '' || strtolower($value) === 'null' || strtolower($value) === 'n/a') {
            return null;
        }

        // Clean special characters
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);

        // Remove extra whitespace
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }

    /**
     * Map CSV columns to database fields
     */
    public function mapColumns(array $headers, array $mapping): array
    {
        $result = [];

        foreach ($headers as $index => $header) {
            if ($header === null) {
                continue;
            }

            $normalizedHeader = $this->normalizeHeader($header);

            // Check direct mapping
            if (isset($mapping[$normalizedHeader])) {
                $result[$index] = $mapping[$normalizedHeader];
            }
            // Check fuzzy matching
            else {
                $match = $this->fuzzyMatchColumn($normalizedHeader, array_keys($mapping));
                if ($match) {
                    $result[$index] = $mapping[$match];
                    $this->warnings[] = "Column '{$header}' matched to '{$match}'";
                }
            }
        }

        return $result;
    }

    /**
     * Normalize header name
     */
    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = trim($header, '_');

        return $header;
    }

    /**
     * Fuzzy match column name
     */
    private function fuzzyMatchColumn(string $header, array $candidates): ?string
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $similarity = 0;
            similar_text($header, $this->normalizeHeader($candidate), $similarity);

            if ($similarity > 80 && $similarity > $bestScore) {
                $bestScore = $similarity;
                $bestMatch = $candidate;
            }
        }

        return $bestMatch;
    }

    /**
     * Validate row data
     */
    public function validateRow(array $row, array $rules, int $rowNum): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $row[$field] ?? null;

            if (str_contains($rule, 'required') && empty($value)) {
                $errors[] = "Row {$rowNum}: Field '{$field}' is required";
            }

            if (str_contains($rule, 'date') && $value && ! $this->isValidDate($value)) {
                $errors[] = "Row {$rowNum}: Field '{$field}' must be a valid date";
            }

            if (str_contains($rule, 'numeric') && $value && ! is_numeric($value)) {
                $errors[] = "Row {$rowNum}: Field '{$field}' must be numeric";
            }

            if (str_contains($rule, 'email') && $value && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNum}: Field '{$field}' must be a valid email";
            }
        }

        return $errors;
    }

    /**
     * Check if value is valid date
     */
    private function isValidDate($value): bool
    {
        if (is_numeric($value) && (float) $value > 25569) {
            return true;
        }

        $value = (string) $value;

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d', 'M Y', 'F Y', 'M y', 'F y'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return true;
            }
        }

        return strtotime($value) !== false;
    }

    /**
     * Transform value to target type
     */
    public function transformValue($value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'string' => (string) $value,
            'integer', 'int' => (int) $value,
            'float', 'decimal' => (float) str_replace(',', '.', (string) $value),
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'date' => $this->parseDate($value),
            'datetime' => $this->parseDateTime($value),
            'array', 'json' => json_decode((string) $value, true) ?? [$value],
            default => $value,
        };
    }

    /**
     * Parse date string with extended format support
     */
    public function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Handle Excel serial date (float > 25569 which is 1970-01-01)
        if (is_numeric($value) && (float) $value > 25569) {
            try {
                $dateTime = Date::excelToDateTimeObject((float) $value);

                return $dateTime->format('Y-m-d');
            } catch (\Exception $e) {
                $this->warnings[] = "Could not parse Excel serial date: {$value}";

                return null;
            }
        }

        $value = (string) $value;

        // Standard formats first
        $standardFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($standardFormats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                // Validate that the parsed date is a real calendar date (no silent overflow)
                $year = (int) $date->format('Y');
                $month = (int) $date->format('m');
                $day = (int) $date->format('d');
                if (! checkdate($month, $day, $year)) {
                    return null;
                }

                return $date->format('Y-m-d');
            }
        }

        // Handle m-d-Y format with dashes (US format: month-day-year)
        // Distinguish from d-m-Y by checking if the second number > 12
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $value, $matches)) {
            $first = (int) $matches[1];
            $second = (int) $matches[2];
            $year = (int) $matches[3];

            if ($second > 12 && $first <= 12) {
                // Must be m-d-Y (second number cannot be a month)
                if (checkdate($first, $second, $year)) {
                    $date = \DateTime::createFromFormat('m-d-Y', $value);
                    if ($date) {
                        return $date->format('Y-m-d');
                    }
                }
            }
        }

        // "Mon YY" format (e.g. "Jan 26", "Dec 25") -> 1st of month, prefix century
        if (preg_match('/^([A-Za-z]{3}) (\d{2})$/', $value, $matches)) {
            $monthAbbr = $matches[1];
            $yearFull = '20'.$matches[2];
            $expanded = $monthAbbr.' '.$yearFull;
            $date = \DateTime::createFromFormat('M Y', $expanded);
            if ($date) {
                $date->setDate((int) $date->format('Y'), (int) $date->format('m'), 1);

                return $date->format('Y-m-d');
            }
        }

        // "Mon YYYY" formats (e.g. "Jul 2026", "July 2026") -> 1st of month
        $monthYearFormats = ['M Y', 'F Y', 'F y'];
        foreach ($monthYearFormats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) {
                $date->setDate((int) $date->format('Y'), (int) $date->format('m'), 1);

                return $date->format('Y-m-d');
            }
        }

        // Fallback to strtotime
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        // Validate that strtotime did not silently overflow an invalid date
        // If the input looks like a date format (Y-m-d, d-m-Y, etc.), verify the result matches
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];
            if (! checkdate($month, $day, $year)) {
                return null;
            }
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * Parse datetime string
     */
    private function parseDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Handle Excel serial date
        if (is_numeric($value) && (float) $value > 25569) {
            try {
                $dateTime = Date::excelToDateTimeObject((float) $value);

                return $dateTime->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    /**
     * Resolve a user ID from name or email
     */
    public function resolveUserId(?string $nameOrEmail, ?array $userOverrides = null): ?int
    {
        if (empty($nameOrEmail)) {
            return null;
        }

        $nameOrEmail = trim($nameOrEmail);

        if ($nameOrEmail === '') {
            return null;
        }

        // Check user_overrides first (from frontend validation)
        if ($userOverrides && isset($userOverrides[$nameOrEmail])) {
            return (int) $userOverrides[$nameOrEmail];
        }

        // Apply known name aliases
        if (isset(self::USER_NAME_ALIASES[$nameOrEmail])) {
            $nameOrEmail = self::USER_NAME_ALIASES[$nameOrEmail];
        }

        // Check cache first
        $cacheKey = strtolower($nameOrEmail);
        if (array_key_exists($cacheKey, $this->userCache)) {
            return $this->userCache[$cacheKey];
        }

        // Exact match on full_name
        $user = User::where('full_name', $nameOrEmail)->first();

        // Exact match on email
        if (! $user) {
            $user = User::where('email', $nameOrEmail)->first();
        }

        // Fuzzy LIKE match on full_name (ordered by most recent for deterministic results)
        if (! $user) {
            $user = User::where('full_name', 'ILIKE', '%'.$nameOrEmail.'%')
                ->orderBy('id', 'desc')
                ->first();
        }

        $userId = $user?->id;

        if (! $userId) {
            $this->warnings[] = "User not found: '{$nameOrEmail}'";
        }

        // Cache result
        $this->userCache[$cacheKey] = $userId;

        return $userId;
    }

    /**
     * Suggest matching users for a given name/email string.
     * Returns an array of suggestions with confidence scores.
     */
    public function suggestUsers(string $nameOrEmail, int $limit = 3): array
    {
        $nameOrEmail = trim($nameOrEmail);
        if ($nameOrEmail === '') {
            return [];
        }

        $suggestions = [];

        // Apply known aliases first
        $corrected = self::USER_NAME_ALIASES[$nameOrEmail] ?? null;

        // 1. Exact match on full_name
        $user = User::where('full_name', $corrected ?? $nameOrEmail)->first();
        if ($user) {
            return [[
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'confidence' => 100,
                'match_type' => $corrected ? 'alias' : 'exact',
            ]];
        }

        // 2. Exact match on email
        $user = User::where('email', $nameOrEmail)->first();
        if ($user) {
            return [[
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'confidence' => 100,
                'match_type' => 'exact',
            ]];
        }

        // 3. ILIKE substring match
        $matches = User::where('full_name', 'ILIKE', '%'.$nameOrEmail.'%')
            ->limit($limit)
            ->get();
        foreach ($matches as $match) {
            $suggestions[] = [
                'user_id' => $match->id,
                'full_name' => $match->full_name,
                'email' => $match->email,
                'confidence' => 85,
                'match_type' => 'substring',
            ];
        }

        // 4. Initial expansion: "W.Moody" -> W% Moody
        if (preg_match('/^([A-Z])[\.\s]?\s*(\S+)$/i', $nameOrEmail, $m)) {
            $initial = $m[1];
            $surname = $m[2];
            $expanded = User::where('full_name', 'ILIKE', $initial.'% '.$surname)
                ->limit($limit)
                ->get();
            foreach ($expanded as $match) {
                if (! $this->hasSuggestion($suggestions, $match->id)) {
                    $suggestions[] = [
                        'user_id' => $match->id,
                        'full_name' => $match->full_name,
                        'email' => $match->email,
                        'confidence' => 80,
                        'match_type' => 'initial_expansion',
                    ];
                }
            }
        }

        // 5. Token matching: split words and compare
        $inputTokens = preg_split('/[\s\.]+/', strtolower($nameOrEmail));
        $inputTokens = array_filter($inputTokens, fn ($t) => strlen($t) > 1);

        if (count($inputTokens) > 0 && empty($suggestions)) {
            $query = User::query();
            foreach ($inputTokens as $token) {
                $query->orWhere('full_name', 'ILIKE', '%'.$token.'%');
            }
            $tokenMatches = $query->limit($limit * 2)->get();
            foreach ($tokenMatches as $match) {
                if (! $this->hasSuggestion($suggestions, $match->id)) {
                    $matchTokens = preg_split('/\s+/', strtolower($match->full_name));
                    $commonTokens = count(array_intersect($inputTokens, $matchTokens));
                    $totalTokens = max(count($inputTokens), count($matchTokens));
                    $confidence = (int) round(($commonTokens / $totalTokens) * 80);
                    if ($confidence >= 40) {
                        $suggestions[] = [
                            'user_id' => $match->id,
                            'full_name' => $match->full_name,
                            'email' => $match->email,
                            'confidence' => $confidence,
                            'match_type' => 'token_match',
                        ];
                    }
                }
            }
        }

        // 6. Levenshtein on all users if still no matches
        if (empty($suggestions)) {
            $allUsers = User::where('is_active', true)->get();
            $inputLower = strtolower($nameOrEmail);
            foreach ($allUsers as $user) {
                $userFullNameLower = strtolower($user->full_name);
                $distance = levenshtein($inputLower, $userFullNameLower);
                $maxLen = max(strlen($inputLower), strlen($userFullNameLower));
                if ($distance <= 4) {
                    $confidence = (int) round((1 - $distance / $maxLen) * 100);
                    $suggestions[] = [
                        'user_id' => $user->id,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'confidence' => $confidence,
                        'match_type' => 'levenshtein',
                    ];
                }
            }
        }

        // Sort by confidence descending and limit
        usort($suggestions, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);

        return array_slice($suggestions, 0, $limit);
    }

    private function hasSuggestion(array $suggestions, int $userId): bool
    {
        foreach ($suggestions as $s) {
            if ($s['user_id'] === $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Known user name aliases (typos/variations => correct name)
     */
    private const USER_NAME_ALIASES = [
        'Rebecca Refell' => 'Rebecca Reffell',
        'rebecca refell' => 'Rebecca Reffell',
    ];

    /**
     * Enum alias mappings: normalized input => enum case value
     */
    private const ENUM_ALIASES = [
        BAUType::class => [
            'bau' => 'BAU',
            'business as usual' => 'BAU',
            'non bau' => 'Non BAU',
            'non-bau' => 'Non BAU',
            'growth' => 'Non BAU',
            'transformative' => 'Non BAU',
            'transformation' => 'Non BAU',
            'project' => 'Non BAU',
        ],
        CurrentStatus::class => [
            'not started' => 'Not Started',
            'not_started' => 'Not Started',
            'in progress' => 'In Progress',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'done' => 'Completed',
            'complete' => 'Completed',
            'on hold' => 'On Hold',
            'on_hold' => 'On Hold',
            'pending' => 'On Hold',
            'hold' => 'On Hold',
        ],
        ImpactLevel::class => [
            'high' => 'High',
            'h' => 'High',
            '3' => 'High',
            'medium' => 'Medium',
            'med' => 'Medium',
            'm' => 'Medium',
            '2' => 'Medium',
            'low' => 'Low',
            'l' => 'Low',
            '1' => 'Low',
        ],
        RAGStatus::class => [
            'blue' => 'Blue',
            'b' => 'Blue',
            'green' => 'Green',
            'g' => 'Green',
            'amber' => 'Amber',
            'a' => 'Amber',
            'orange' => 'Amber',
            'red' => 'Red',
            'r' => 'Red',
        ],
        UpdateFrequency::class => [
            'annually' => 'Annually',
            'annual' => 'Annually',
            'yearly' => 'Annually',
            'semi annually' => 'Semi Annually',
            'semi_annually' => 'Semi Annually',
            'bi-annual' => 'Semi Annually',
            'bi-annually' => 'Semi Annually',
            'quarterly' => 'Quarterly',
            'quarter' => 'Quarterly',
            'monthly' => 'Monthly',
            'month' => 'Monthly',
            'weekly' => 'Weekly',
            'week' => 'Weekly',
            'one-off' => 'One-off',
            'one off' => 'One-off',
            'one_off' => 'One-off',
            'once' => 'One-off',
            'ad hoc' => 'One-off',
        ],
    ];

    /**
     * Normalize a value to a valid enum case value
     */
    public function normalizeEnumValue(mixed $value, string $enumClass): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        // Exact match via tryFrom
        $case = $enumClass::tryFrom($value);
        if ($case !== null) {
            return $case->value;
        }

        // Case-insensitive match on enum case values
        $lower = strtolower($value);
        foreach ($enumClass::cases() as $case) {
            if (strtolower($case->value) === $lower) {
                return $case->value;
            }
        }

        // Lookup in alias map
        $aliases = self::ENUM_ALIASES[$enumClass] ?? [];
        if (isset($aliases[$lower])) {
            return $aliases[$lower];
        }

        $this->warnings[] = "Unknown enum value '{$value}' for {$enumClass}";

        return null;
    }

    /**
     * Get expected column mappings for import type (header alias => db field)
     */
    public function getExpectedColumns(string $type): array
    {
        return match ($type) {
            'workitems' => [
                // Core fields
                'ref_no' => 'ref_no',
                'reference' => 'ref_no',
                'ref' => 'ref_no',
                'reference_number' => 'ref_no',
                'number' => 'ref_no',
                'type' => 'type',
                'work_type' => 'type',
                'activity' => 'activity',
                'activity_type' => 'type',
                'department' => 'department',
                'area' => 'department',
                'dept' => 'department',
                'impacted_area' => 'department',
                'description' => 'description',
                'desc' => 'description',
                'workstream_deliverable' => 'description',
                'goal' => 'goal',
                'objective' => 'goal',
                'goal_objective' => 'goal',
                // Classification
                'bau_or_transformative' => 'bau_or_transformative',
                'bau_transformative' => 'bau_or_transformative',
                'bau' => 'bau_or_transformative',
                'bau_non_bau' => 'bau_or_transformative',
                'bau_or_growth_transformative' => 'bau_or_transformative',
                'impact_level' => 'impact_level',
                'impact' => 'impact_level',
                'impact_urgency_level' => 'impact_level',
                'current_status' => 'current_status',
                'status' => 'current_status',
                'rag_status' => 'rag_status',
                'rag' => 'rag_status',
                // Dates
                'deadline' => 'deadline',
                'due_date' => 'deadline',
                'target_date' => 'deadline',
                'target_completion_date' => 'deadline',
                'expected_completion' => 'deadline',
                'completion_date' => 'completion_date',
                'actual_completion_date' => 'completion_date',
                'expected_completion_date' => 'completion_date',
                // Updates
                'monthly_update' => 'monthly_update',
                'update' => 'monthly_update',
                'key_milestones_steps' => 'monthly_update',
                'comments' => 'comments',
                'comment' => 'comments',
                'notes' => 'comments',
                'update_frequency' => 'update_frequency',
                'frequency' => 'update_frequency',
                'update_refresh_frequency' => 'update_frequency',
                // People
                'responsible_party' => 'responsible_party_id',
                'responsible_person' => 'responsible_party_id',
                'owner' => 'responsible_party_id',
                'assigned_to' => 'responsible_party_id',
                'responsible' => 'responsible_party_id',
                'department_head' => 'department_head_id',
                'dept_head' => 'department_head_id',
                'head_of_department' => 'department_head_id',
                // Meta
                'tags' => 'tags',
                'priority_item' => 'priority_item',
                'priority' => 'priority_item',
                'priority_item_currently' => 'priority_item',
                // Financial
                'cost_savings' => 'cost_savings',
                'savings' => 'cost_savings',
                'cost_savings_mm' => 'cost_savings',
                'cost_efficiency_fte' => 'cost_efficiency_fte',
                'fte' => 'cost_efficiency_fte',
                'fte_savings' => 'cost_efficiency_fte',
                'cost_efficiency_fte_time_saving' => 'cost_efficiency_fte',
                'expected_cost' => 'expected_cost',
                'cost' => 'expected_cost',
                'budget' => 'expected_cost',
                'expected_cost_to_implement_mm' => 'expected_cost',
                'revenue_potential' => 'revenue_potential',
                'revenue' => 'revenue_potential',
                'revenue_potential_first_fy_mm' => 'revenue_potential',
                // Backup person
                'back_up_person' => 'back_up_person_id',
                'backup_person' => 'back_up_person_id',
                'backup' => 'back_up_person_id',
                // Dependencies
                'other_item_completion_dependences' => 'other_item_completion_dependences',
                'dependencies' => 'other_item_completion_dependences',
                'dependences' => 'other_item_completion_dependences',
                // Issues/Risks
                'issues_risks' => 'issues_risks',
                'issues' => 'issues_risks',
                'issues___risks' => 'issues_risks',
                // Initial provider
                'initial_item_provider_editor' => 'initial_item_provider_editor',
                'initial_item_provider' => 'initial_item_provider_editor',
                'initial_item_provider_editor_1' => 'initial_item_provider_editor',
            ],
            'suppliers' => [
                'ref_no' => 'ref_no',
                'reference' => 'ref_no',
                'name' => 'name',
                'supplier_name' => 'name',
                'sage_category' => 'sage_category_id',
                'category' => 'sage_category_id',
                'location' => 'location',
                'is_common_provider' => 'is_common_provider',
                'common_provider' => 'is_common_provider',
                'status' => 'status',
                'entities' => 'entities',
                'notes' => 'notes',
            ],
            'invoices' => [
                'supplier_ref' => 'supplier_ref',
                'supplier' => 'supplier_ref',
                'invoice_ref' => 'invoice_ref',
                'invoice_number' => 'invoice_ref',
                'description' => 'description',
                'amount' => 'amount',
                'currency' => 'currency',
                'invoice_date' => 'invoice_date',
                'date' => 'invoice_date',
                'due_date' => 'due_date',
                'frequency' => 'frequency',
                'status' => 'status',
            ],
            'risks' => [
                'ref_no' => 'ref_no',
                'reference' => 'ref_no',
                'theme_code' => 'theme_code',
                'theme' => 'theme_code',
                'category_code' => 'category_code',
                'category' => 'category_code',
                'name' => 'name',
                'description' => 'description',
                'tier' => 'tier',
                'owner' => 'owner_id',
                'responsible_party' => 'responsible_party_id',
                'financial_impact' => 'financial_impact',
                'regulatory_impact' => 'regulatory_impact',
                'reputational_impact' => 'reputational_impact',
                'inherent_probability' => 'inherent_probability',
                'probability' => 'inherent_probability',
            ],
            'governance' => [
                'ref_no' => 'ref_no',
                'reference' => 'ref_no',
                'activity' => 'activity',
                'description' => 'description',
                'department' => 'department',
                'frequency' => 'frequency',
                'location' => 'location',
                'current_status' => 'current_status',
                'status' => 'current_status',
                'deadline' => 'deadline',
                'responsible_party' => 'responsible_party_id',
                'owner' => 'responsible_party_id',
                'tags' => 'tags',
            ],
            default => [],
        };
    }

    /**
     * Detect duplicate rows based on a key field.
     * In exact mode, groups rows by the key field value and returns one representative per group where count > 1.
     * In fuzzy mode, uses similar_text() to find rows with similar values (threshold >= 80%).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function detectDuplicates(array $rows, string $keyField, bool $fuzzy = false): array
    {
        if ($fuzzy) {
            return $this->detectFuzzyDuplicates($rows, $keyField);
        }

        return $this->detectExactDuplicates($rows, $keyField);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function detectExactDuplicates(array $rows, string $keyField): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = $row[$keyField] ?? null;
            if ($key === null) {
                continue;
            }

            $groups[$key][] = $row;
        }

        $duplicates = [];
        foreach ($groups as $grouped) {
            if (count($grouped) > 1) {
                $duplicates[] = $grouped[0];
            }
        }

        return $duplicates;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function detectFuzzyDuplicates(array $rows, string $keyField): array
    {
        $count = count($rows);
        $matched = [];
        $duplicates = [];

        for ($i = 0; $i < $count; $i++) {
            if (isset($matched[$i])) {
                continue;
            }

            $valueA = $rows[$i][$keyField] ?? null;
            if ($valueA === null) {
                continue;
            }

            $hasDuplicate = false;

            for ($j = $i + 1; $j < $count; $j++) {
                if (isset($matched[$j])) {
                    continue;
                }

                $valueB = $rows[$j][$keyField] ?? null;
                if ($valueB === null) {
                    continue;
                }

                if ($this->isFuzzyMatch((string) $valueA, (string) $valueB)) {
                    $matched[$j] = true;
                    $hasDuplicate = true;
                }
            }

            if ($hasDuplicate) {
                $duplicates[] = $rows[$i];
            }
        }

        return $duplicates;
    }

    private function isFuzzyMatch(string $a, string $b): bool
    {
        $lowerA = strtolower($a);
        $lowerB = strtolower($b);

        $similarity = 0.0;
        similar_text($lowerA, $lowerB, $similarity);
        if ($similarity >= 80) {
            return true;
        }

        $maxLen = max(strlen($lowerA), strlen($lowerB));
        if ($maxLen > 0 && levenshtein($lowerA, $lowerB) <= (int) ceil($maxLen * 0.3)) {
            return true;
        }

        return $this->tokensMatchWithInitials($lowerA, $lowerB);
    }

    private function tokensMatchWithInitials(string $a, string $b): bool
    {
        $tokensA = preg_split('/[\s.\-]+/', $a, -1, PREG_SPLIT_NO_EMPTY);
        $tokensB = preg_split('/[\s.\-]+/', $b, -1, PREG_SPLIT_NO_EMPTY);

        if ($tokensA === false || $tokensB === false || count($tokensA) < 2 || count($tokensB) < 2) {
            return false;
        }

        $surnameA = end($tokensA);
        $surnameB = end($tokensB);

        if ($surnameA !== $surnameB) {
            return false;
        }

        $firstA = $tokensA[0];
        $firstB = $tokensB[0];

        if (strlen($firstA) === 1 || strlen($firstB) === 1) {
            return $firstA[0] === $firstB[0];
        }

        return false;
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Add error
     */
    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }
}
