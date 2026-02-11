@extends('reports.layout')

@section('content')
<div class="summary">
    <div class="summary-item">
        <div class="summary-value">{{ $risks->count() }}</div>
        <div class="summary-label">Total Risks</div>
    </div>
    <div class="summary-item">
        <div class="summary-value">{{ $risks->where('inherent_rag', \App\Enums\RAGStatus::RED)->count() }}</div>
        <div class="summary-label">High (Red)</div>
    </div>
    <div class="summary-item">
        <div class="summary-value">{{ $risks->where('appetite_status', \App\Enums\RiskAppetiteStatus::EXCEEDED)->count() }}</div>
        <div class="summary-label">Outside Appetite</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Ref</th>
            <th>Name</th>
            <th>Category</th>
            <th>Owner</th>
            <th>Inherent</th>
            <th>Residual</th>
            <th>Appetite</th>
        </tr>
    </thead>
    <tbody>
        @foreach($risks as $risk)
        <tr>
            <td>{{ $risk->ref_no }}</td>
            <td>{{ \Illuminate\Support\Str::limit($risk->name, 40) }}</td>
            <td>{{ $risk->category?->name ?? '-' }}</td>
            <td>{{ $risk->owner?->full_name ?? '-' }}</td>
            <td>
                @if($risk->inherent_rag)
                <span class="badge badge-{{ $risk->inherent_rag->value }}">{{ $risk->inherent_risk_score }}</span>
                @else
                -
                @endif
            </td>
            <td>
                @if($risk->residual_rag)
                <span class="badge badge-{{ $risk->residual_rag->value }}">{{ $risk->residual_risk_score }}</span>
                @else
                -
                @endif
            </td>
            <td>{{ $risk->appetite_status?->value ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
