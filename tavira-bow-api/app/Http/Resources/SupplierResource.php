<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ref_no' => $this->ref_no,
            'name' => $this->name,
            'sage_category_id' => $this->sage_category_id,
            'responsible_party_id' => $this->responsible_party_id,
            'location' => $this->location?->value,
            'is_common_provider' => $this->is_common_provider,
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'active_contracts_count' => $this->active_contracts_count,
            'total_invoices_amount' => $this->total_invoices_amount,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'sage_category' => new SageCategoryResource($this->whenLoaded('sageCategory')),
            'responsible_party' => new UserResource($this->whenLoaded('responsibleParty')),
            'entities' => SupplierEntityResource::collection($this->whenLoaded('entities')),
            'contracts' => SupplierContractResource::collection($this->whenLoaded('contracts')),
            'invoices' => SupplierInvoiceResource::collection($this->whenLoaded('invoices')),
            'attachments' => SupplierAttachmentResource::collection($this->whenLoaded('attachments')),
            'access' => SupplierAccessResource::collection($this->whenLoaded('access')),
        ];
    }
}
