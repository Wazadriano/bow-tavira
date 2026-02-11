@extends('reports.layout')

@section('content')
<div class="summary">
    <div class="summary-item">
        <div class="summary-value">{{ $suppliers->count() }}</div>
        <div class="summary-label">Total Suppliers</div>
    </div>
    <div class="summary-item">
        <div class="summary-value">{{ $suppliers->sum(fn($s) => $s->contracts->count()) }}</div>
        <div class="summary-label">Contracts</div>
    </div>
    <div class="summary-item">
        <div class="summary-value">{{ $suppliers->sum(fn($s) => $s->invoices->count()) }}</div>
        <div class="summary-label">Invoices</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Location</th>
            <th>Status</th>
            <th>Contracts</th>
            <th>Invoices</th>
            <th>Total Spend (GBP)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($suppliers as $supplier)
        <tr>
            <td>{{ $supplier->name }}</td>
            <td>{{ $supplier->location?->value ?? '-' }}</td>
            <td>{{ $supplier->status?->value ?? '-' }}</td>
            <td>{{ $supplier->contracts->count() }}</td>
            <td>{{ $supplier->invoices->count() }}</td>
            <td>
                @php
                    $total = $supplier->invoices->sum(function($inv) use ($currencyService) {
                        return $currencyService->toGbp($inv->amount, $inv->currency ?? 'GBP');
                    });
                @endphp
                {{ number_format($total, 2) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
