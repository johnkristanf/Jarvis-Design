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
        .amount {
            text-align: right;
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
                <th class="amount">Total Used</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row['fabric'] }}</td>
                <td class="amount">{{ $row['total_used'] }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>Total</td>
                <td class="amount">{{ $totalUsed }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>

