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

class ImportNormalizationService
{
    private array $errors = [];

    private array $warnings = [];

    private array $userCache = [];

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
        // Try to detect encoding
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            $this->warnings[] = "Encoding converted from {$encoding} to UTF-8";
        }

        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        return $content;
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
    public function resolveUserId(?string $nameOrEmail): ?int
    {
        if (empty($nameOrEmail)) {
            return null;
        }

        $nameOrEmail = trim($nameOrEmail);

        if ($nameOrEmail === '') {
            return null;
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
