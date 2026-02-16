<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ref_no' => 'required|string|max:50|unique:suppliers,ref_no',
            'name' => 'required|string|max:200',
            'sage_category_id' => 'nullable|exists:sage_categories,id',
            'responsible_party_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string',
            'is_common_provider' => 'sometimes|boolean',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
            'entities' => 'nullable|array',
            'entities.*' => 'string',
        ];
    }
}
