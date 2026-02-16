<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_ref' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'amount' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'nullable|date',
            'paid_date' => 'nullable|date',
            'frequency' => 'nullable|string',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
