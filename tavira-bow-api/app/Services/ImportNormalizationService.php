<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ImportNormalizationService
{
    private array $errors = [];
    private array $warnings = [];

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
            $data[] = array_map(fn($cell) => $this->normalizeCell($cell), $row);
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

            if (str_contains($rule, 'date') && $value && !$this->isValidDate($value)) {
                $errors[] = "Row {$rowNum}: Field '{$field}' must be a valid date";
            }

            if (str_contains($rule, 'numeric') && $value && !is_numeric($value)) {
                $errors[] = "Row {$rowNum}: Field '{$field}' must be numeric";
            }

            if (str_contains($rule, 'email') && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNum}: Field '{$field}' must be a valid email";
            }
        }

        return $errors;
    }

    /**
     * Check if value is valid date
     */
    private function isValidDate(string $value): bool
    {
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];

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
            'float', 'decimal' => (float) str_replace(',', '.', $value),
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'date' => $this->parseDate($value),
            'datetime' => $this->parseDateTime($value),
            'array', 'json' => json_decode($value, true) ?? [$value],
            default => $value,
        };
    }

    /**
     * Parse date string
     */
    private function parseDate(string $value): ?string
    {
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    /**
     * Parse datetime string
     */
    private function parseDateTime(string $value): ?string
    {
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
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
