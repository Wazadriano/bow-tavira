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
    private const STATUS_MAP = [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
    ];

    private const BAU_MAP = [
        'bau' => 'BAU',
        'transformative' => 'Non BAU',
    ];

    private const IMPACT_MAP = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    private const RAG_MAP = [
        'blue' => 'Blue',
        'green' => 'Green',
        'amber' => 'Amber',
        'red' => 'Red',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('current_status') && isset(self::STATUS_MAP[$this->current_status])) {
            $merge['current_status'] = self::STATUS_MAP[$this->current_status];
        }

        if ($this->has('bau_or_transformative') && isset(self::BAU_MAP[$this->bau_or_transformative])) {
            $merge['bau_or_transformative'] = self::BAU_MAP[$this->bau_or_transformative];
        }

        if ($this->has('impact_level') && isset(self::IMPACT_MAP[$this->impact_level])) {
            $merge['impact_level'] = self::IMPACT_MAP[$this->impact_level];
        }

        if ($this->has('rag_status') && isset(self::RAG_MAP[$this->rag_status])) {
            $merge['rag_status'] = self::RAG_MAP[$this->rag_status];
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
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
