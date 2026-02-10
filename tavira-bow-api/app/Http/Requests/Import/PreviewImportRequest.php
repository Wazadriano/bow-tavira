<?php

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;

class PreviewImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:csv,txt,xlsx,xls',
            ],
            'type' => 'required|in:workitems,suppliers,invoices,risks,governance',
            'sheet_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Un fichier est requis.',
            'file.max' => 'Le fichier ne doit pas depasser 10 Mo.',
            'file.mimes' => 'Format accepte : CSV, XLS, XLSX.',
            'type.required' => 'Le type de donnees est requis.',
            'type.in' => 'Type invalide.',
        ];
    }
}
