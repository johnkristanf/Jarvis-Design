<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Important: Set charset to utf-8 for correct symbol support -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif; /* Use DejaVu Sans for better UTF-8 PDF support */
            padding: 40px;
            color: #333;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .logo-section {
            display: table-cell;
            width: 100px;
            vertical-align: middle;
        }

        .logo {
            width: 80px;
            height: 80px;
            border: 2px solid #333;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            color: #666;
        }

        .title-section {
            display: table-cell;
            vertical-align: middle;
            padding-left: 20px;
        }

        .title-section h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .jarvis-designs {
            font-size: 28px;
            font-weight: bold;
            color: #7d6724;
        }

        .title-section p {
            font-size: 16px;
            color: #666;
        }

        .report-info {
            margin-bottom: 30px;
            font-size: 12px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        thead {
            background-color: #f5f5f5;
        }

        th {
            padding: 12px 8px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
            border-bottom: 2px solid #333;
            border-top: 2px solid #333;
        }

        td {
            padding: 10px 8px;
            font-size: 11px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        tr:hover {
            background-color: #fafafa;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .prepared-by {
            font-size: 12px;
            margin-bottom: 5px;
        }

        .owner-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .owner-title {
            font-size: 11px;
            color: #666;
            margin-bottom: 15px;
        }

        .report-meta {
            font-size: 10px;
            color: #999;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }

        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .summary-label {
            display: table-cell;
            font-weight: bold;
            font-size: 12px;
        }

        .summary-value {
            display: table-cell;
            text-align: right;
            font-size: 12px;
        }

        .size-list {
            font-size: 10px;
            color: #fff;
        }
        .size-badge {
            display: inline-block;
            background-color: #007bff;
            color: #fff;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 6px; /* rounded-md */
            margin-right: 4px;
            margin-bottom: 2px;
            font-weight: bold;
            vertical-align: middle;
        }


        .total-order-badge-green {
            display: inline-block;
            background-color: #28a745;
            color: #fff;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 6px; /* rounded-md */
            margin-right: 4px;
            font-weight: bold;
        }
        .total-order-badge-red {
            display: inline-block;
            background-color: #dc3545;
            color: #fff;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 6px; /* rounded-md */
            margin-right: 4px;
            font-weight: bold;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-section">
            <img src="{{ public_path('jarvis-logo-circle.png') }}" alt="Logo" class="logo-img">
        </div>
        <div class="title-section">
            <h1>
                Jarvis <span class="jarvis-designs">Designs</span>
            </h1>
            <p>Sales Report</p>
        </div>
    </div>

    <div class="report-info">
        <strong>Report Period:</strong> {{ $startDate->format('F d, Y') }} - {{ $endDate->format('F d, Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Date of Order</th>
                <th style="width: 10%;">Product Ordered</th>
                <th style="width: 15%;">Product Name</th>
                <th style="width: 18%;">Sizes</th>
                <th style="width: 12%;" class="text-right">Total Payment</th>
                <th style="width: 15%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalCompleted = 0;
                $totalCancelled = 0;
                $completedCount = 0;
                $cancelledCount = 0;
                // Use UTF-8 Peso sign entity
                $peso = '&#8369;';
            @endphp

            @foreach($orders as $order)
                <tr>
                    <td>{{ date('M d, Y', strtotime($order->created_at)) }}</td>
                    <td>{{ $order->total_quantity }} pcs</td>
                    <td>{{ $order->product->name }}</td>
                    <td>
                        <div class="size-list">
                            @foreach($order->sizes as $size)
                                <span class="size-badge">{{ $size->name }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="text-right">{!! $peso !!}{{ number_format($order->total_payment, 2) }}</td>
                    <td class="text-center">
                        @if($order->status === 'completed')
                            <span class="status-badge status-completed">Completed</span>
                            @php
                                $totalCompleted += $order->total_payment;
                                $completedCount++;
                            @endphp
                        @else
                            <span class="status-badge status-cancelled">Cancelled</span>
                            @php
                                $totalCancelled += $order->total_payment;
                                $cancelledCount++;
                            @endphp
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row">
            <div class="summary-label">Total Orders:</div>
            <div class="summary-value">{{ count($orders) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label" >Completed Orders <span style="color: green;">({{ $completedCount }})</span>:</div>
            <div class="summary-value"> {!! $peso !!}{{ number_format($totalCompleted, 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label" >Cancelled Orders <span style="color: red;">({{ $cancelledCount }})</span>:</div>
            <div class="summary-value"> {!! $peso !!}{{ number_format($totalCancelled, 2) }}</div>
        </div>
        <div class="summary-row" style="border-top: 2px solid #333; padding-top: 8px; margin-top: 8px;">
            <div class="summary-label" style="font-size: 14px;">Total Revenue:</div>
            <div class="summary-value" style="font-size: 14px;">{!! $peso !!}{{ number_format($totalCompleted, 2) }}</div>
        </div>
    </div>

    <div class="footer">
        <p class="prepared-by">Prepared by:</p>
        <p class="owner-name">Jason Santillan</p>
        <p class="owner-title">Owner</p>
        <p class="report-meta">This is a system generated report | Generated on {{ date('F d, Y g:i A') }}</p>
    </div>
</body>
</html>