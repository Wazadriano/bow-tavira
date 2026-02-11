<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Report' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 20px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin: 0;
        }
        .header .subtitle {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            color: #475569;
        }
        td {
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
        .summary {
            background: #f1f5f9;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 20px;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
        }
        .summary-label {
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title ?? 'Report' }}</h1>
        <div class="subtitle">Generated: {{ $generated_at->format('d/m/Y H:i') }} | Tavira BOW</div>
    </div>

    @yield('content')

    <div class="footer">
        Tavira BOW - Confidential | Page {PAGE_NUM} of {PAGE_COUNT}
    </div>
</body>
</html>
