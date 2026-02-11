@extends('reports.layout')

@section('content')
<div class="summary">
    <div class="summary-item">
        <div class="summary-value">{{ $items->count() }}</div>
        <div class="summary-label">Total Items</div>
    </div>
    <div class="summary-item">
        <div class="summary-value">{{ $items->where('current_status', \App\Enums\CurrentStatus::COMPLETED)->count() }}</div>
        <div class="summary-label">Completed</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Ref</th>
            <th>Description</th>
            <th>Department</th>
            <th>Frequency</th>
            <th>Owner</th>
            <th>Status</th>
            <th>RAG</th>
            <th>Deadline</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->ref_no }}</td>
            <td>{{ \Illuminate\Support\Str::limit($item->description, 50) }}</td>
            <td>{{ $item->department }}</td>
            <td>{{ $item->frequency?->value ?? '-' }}</td>
            <td>{{ $item->responsibleParty?->full_name ?? '-' }}</td>
            <td>{{ $item->current_status?->value ?? '-' }}</td>
            <td>
                @if($item->rag_status)
                <span class="badge badge-{{ $item->rag_status->value }}">{{ $item->rag_status->value }}</span>
                @endif
            </td>
            <td>{{ $item->deadline?->format('d/m/Y') ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
