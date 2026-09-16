<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background: #f5f5f5;
        }

        .right {
            text-align: right;
        }

        .summary-table td {
            border: none;
            padding: 6px;
        }

        .grand-total {
            font-weight: bold;
            font-size: 13px;
        }
    </style>
</head>
<body>

@php
    $grandTotal = collect($payments)->sum('amount');
@endphp

<div class="header">
    <h2>Chef Payment Report</h2>

    <p>
        <strong>Chef:</strong> {{ $chef->name ?? 'N/A' }}
    </p>

    <p>
        <strong>Period:</strong>
        {{ $fromDate ?? 'Today' }} to {{ $toDate ?? 'Today' }}
    </p>

    <p>
        <strong>Generated:</strong> {{ $generated }}
    </p>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Order ID</th>
            <th>Payment ID</th>
            <th>Status</th>
            <th>Date</th>
            <th class="right">Amount (₹)</th>
        </tr>
    </thead>

    <tbody>
        @forelse($payments as $index => $payment)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $payment->id }}</td>
                <td>{{ $payment->payment_id }}</td>
                <td>{{ ucfirst($payment->payment_status) }}</td>
                <td>{{ \Carbon\Carbon::parse($payment->paid_at)->format('d M Y') }}</td>
                <td class="right">{{ number_format($payment->amount, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;">
                    No payment records found
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- GRAND TOTAL -->
<table class="summary-table" style="width:100%; margin-top:12px;">
    <tr>
        <td colspan="4"></td>
        <td class="right grand-total">Grand Total</td>
        <td class="right grand-total">
            ₹ {{ number_format($grandTotal, 2) }}
        </td>
    </tr>
</table>

</body>
</html>
