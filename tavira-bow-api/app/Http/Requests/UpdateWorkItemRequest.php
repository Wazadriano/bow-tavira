<?php

namespace App\Http\Requests;

use App\Enums\BAUType;
use App\Enums\CurrentStatus;
use App\Enums\ImpactLevel;
use App\Enums\RAGStatus;
use App\Enums\UpdateFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateWorkItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $workItemId = $this->route('workitem')->id;

        return [
            'ref_no' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('work_items', 'ref_no')->ignore($workItemId),
            ],
            'type' => [
                'nullable',
                'string',
                'max:50',
            ],
            'activity' => [
                'nullable',
                'string',
                'max:100',
            ],
            'department' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'description' => [
                'sometimes',
                'string',
            ],
            'bau_or_transformative' => [
                'sometimes',
                new Enum(BAUType::class),
            ],
            'impact_level' => [
                'sometimes',
                new Enum(ImpactLevel::class),
            ],
            'current_status' => [
                'sometimes',
                new Enum(CurrentStatus::class),
            ],
            'rag_status' => [
                'sometimes',
                new Enum(RAGStatus::class),
            ],
            'deadline' => [
                'nullable',
                'date',
            ],
            'completion_date' => [
                'nullable',
                'date',
            ],
            'monthly_update' => [
                'nullable',
                'string',
            ],
            'update_frequency' => [
                'sometimes',
                new Enum(UpdateFrequency::class),
            ],
            'responsible_party_id' => [
                'nullable',
                'exists:users,id',
            ],
            'tags' => [
                'nullable',
                'array',
            ],
            'tags.*' => [
                'string',
            ],
            'priority_item' => [
                'sometimes',
                'boolean',
            ],
            'file_path' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
