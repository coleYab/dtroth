<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .divider { border-top: 1px solid #e5e7eb; margin: 16px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
        .total { font-weight: bold; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; }
        .badge.paid { background: #dcfce7; color: #166534; }
        .badge.pending { background: #fef9c3; color: #854d0e; }
        .badge.failed { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="row">
        <div>
            <h1>{{ config('app.name') }}</h1>
            <div class="muted">Age verification platform</div>
        </div>
        <div style="text-align: right">
            <div class="badge {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</div>
            <div class="muted">{{ $invoice->number }}</div>
        </div>
    </div>

    <div class="divider"></div>

    <div class="row">
        <div>
            <strong>Billed to</strong><br>
            {{ $invoice->user->name }}<br>
            {{ $invoice->user->email }}
        </div>
        <div style="text-align: right">
            <strong>Issued</strong><br>
            {{ $invoice->created_at?->format('M d, Y') ?? '—' }}<br>
            <strong>Paid</strong><br>
            {{ $invoice->paid_at?->format('M d, Y') ?? '—' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Subscription period {{ $invoice->period_start?->format('M d, Y') ?? '—' }} – {{ $invoice->period_end?->format('M d, Y') ?? '—' }}</td>
                <td style="text-align: right">{{ $invoice->formattedAmount() }}</td>
            </tr>
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="row">
        <strong>Total due</strong>
        <strong class="total">{{ $invoice->formattedAmount() }}</strong>
    </div>

    <div class="divider"></div>

    <div class="muted">Payment channel: {{ $invoice->channel ? ucfirst(str_replace('_', ' ', $invoice->channel)) : '—' }}</div>
</body>
</html>
