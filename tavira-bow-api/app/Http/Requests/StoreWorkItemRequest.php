<?php

namespace App\Http\Requests;

use App\Enums\BAUType;
use App\Enums\CurrentStatus;
use App\Enums\ImpactLevel;
use App\Enums\RAGStatus;
use App\Enums\UpdateFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreWorkItemRequest extends FormRequest
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
        return [
            'ref_no' => [
                'required',
                'string',
                'max:50',
                'unique:work_items,ref_no',
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
                'required',
                'string',
                'max:100',
            ],
            'description' => [
                'required',
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

    public function messages(): array
    {
        return [
            'ref_no.unique' => 'This reference number is already in use.',
            'responsible_party_id.exists' => 'The selected responsible party does not exist.',
        ];
    }
}
