<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fabric Used Report</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .company-header-content {
            display: inline-block;
        }
        .company-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }
        .company-name .gold {
            color: #b8860b;
        }
        .header h1 {
            margin: 10px 0 5px 0;
            font-size: 20px;
            color: #333;
            font-weight: normal;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #1f2937;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #1f2937; /* gray-900 */
            padding-top: 15px;
        }
        /* Make the 'Total' row's top border span the full width */
        .total-row td {
            border-bottom: none;
        }
        .total-row td:first-child {
            border-right: none;
        }
        .total-row td {
            /* Ensuring border-top goes fully across all cells */
        }
        .amount {
            text-align: right;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="company-header">
            <div class="company-header-content">
                <img src="{{ $logoPath }}" alt="Jarvis Designs Logo" class="company-logo" style="display: inline-block; vertical-align: middle; margin-right: 15px;" />
                <h2 class="company-name" style="display: inline-block; vertical-align: middle; margin: 0;">
                    Jarvis <span class="gold">Designs</span>
                </h2>
            </div>
        </div>
        <h1>Fabric Used Summary Report</h1>
        <p>Period: {{ $startDate }} to {{ $endDate }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fabric Name</th>
                <th>Unit</th>
                <th class="amount">Total Used</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row['fabric'] }}</td>
                <td>{{ $row['unit'] }}</td>
                <td class="amount">{{ $row['total_used'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-align:left; border-top:2px solid #1f2937; border-bottom: none;">Total</td>
                <td class="amount" style="font-weight:bold; border-top:2px solid #1f2937; border-bottom: none;">{{ $totalUsed }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p class="prepared-by">Prepared by:</p>
        <p class="owner-name">Jason Santillan</p>
        <p class="owner-title">Owner</p>
        <p class="report-meta">This is a system generated report | Generated on {{ date('F d, Y g:i A') }}</p>
    </div>
</body>
</html>
