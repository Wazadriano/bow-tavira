<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'contract_ref' => $this->contract_ref,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'amount' => $this->amount,
            'currency' => $this->currency ?? 'EUR',
            'auto_renewal' => $this->auto_renewal,
            'notice_period_days' => $this->notice_period_days,
            'alert_sent' => $this->alert_sent,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'days_until_expiry' => $this->days_until_expiry,
            'needs_alert' => $this->needs_alert,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'entities' => ContractEntityResource::collection($this->whenLoaded('entities')),
            'attachments' => SupplierContractAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
