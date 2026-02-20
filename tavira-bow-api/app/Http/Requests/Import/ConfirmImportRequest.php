<?php

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'temp_file' => [
                'required',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (str_contains($value, '..')) {
                        $fail('Chemin de fichier invalide.');
                    }
                    if (! str_starts_with($value, 'imports/temp/')) {
                        $fail('Chemin de fichier invalide.');
                    }
                },
            ],
            'type' => 'required|in:workitems,suppliers,invoices,risks,governance',
            'column_mapping' => 'required|array',
            'column_mapping.*' => 'string|max:100',
            'sheet_name' => 'nullable|string|max:255',
            'sheet_names' => 'nullable|array',
            'sheet_names.*' => 'string|max:255',
            'user_overrides' => 'nullable|array',
            'user_overrides.*' => 'integer',
        ];
    }

    public function messages(): array
    {
        return [
            'temp_file.required' => 'Le fichier temporaire est requis.',
            'type.required' => 'Le type de donnees est requis.',
            'column_mapping.required' => 'Le mapping des colonnes est requis.',
        ];
    }
}
