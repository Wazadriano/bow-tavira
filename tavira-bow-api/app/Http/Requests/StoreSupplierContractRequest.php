<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_ref' => 'required|string|max:100',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
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
