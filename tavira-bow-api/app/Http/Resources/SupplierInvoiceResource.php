<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'invoice_ref' => $this->invoice_ref,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency ?? 'EUR',
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'paid_date' => $this->paid_date?->toDateString(),
            'frequency' => $this->frequency?->value,
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'is_overdue' => $this->is_overdue,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
        ];
    }
}
