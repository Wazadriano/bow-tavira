<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_ref' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'auto_renewal' => 'sometimes|boolean',
            'notice_period_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'entities' => 'nullable|array',
            'entities.*' => 'string',
        ];
    }
}
