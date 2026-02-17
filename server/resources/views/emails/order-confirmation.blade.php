<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
        }
        .logo {
            width: 80px;
            height: 80px;
            border: 2px solid #333;
            border-radius: 50%;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .brand-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        .brand-accent {
            color: #7d6724;
        }
        .order-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .order-info p {
            margin: 5px 0;
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .product-table th {
            background-color: #333;
            color: #ffffff;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }
        .product-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eeeeee;
            vertical-align: top;
            font-size: 14px;
        }
        .product-name {
            font-weight: bold;
            color: #333;
        }
        .product-detail {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .total-label {
            font-weight: bold;
            color: #666;
            margin-right: 10px;
        }
        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #7d6724;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
        .btn {
            display: inline-block;
            background-color: #333;
            color: #ffffff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Use absolute path for logo if needed, or embed via cid if attachments are used. 
                 For now sticking to standard img src assuming public access or updated linkage. 
                 Blade's asset() helper might point to localhost, so typically message->embed() is verified. 
                 But based on user prompt, I will use a simple img tag for now referencing the public path conceptually 
                 or a placeholder if it's local only. 
                 Actually, the PDF example used file path. For emails, we usually need a reachable URL or CID.
                 I'll use the 'message->embed' method if possible for local files, but safe fallback is asset().
            -->
            <img src="{{ $message->embed(public_path('jarvis-logo-circle.png')) }}" alt="Jarvis Designs" class="logo">
            <h1 class="brand-name">Jarvis <span class="brand-accent">Designs</span></h1>
            <p style="color: #666; font-size: 14px; margin-top: 5px;">Order Confirmation</p>
        </div>

        <div class="order-info">
            <p><strong>Order Number:</strong> #{{ $orders->order_number }}</p>
            <p><strong>Date:</strong> {{ $orders->created_at->format('F d, Y h:i A') }}</p>
            <p><strong>Status:</strong> <span class="status-badge">{{ ucfirst($orders->status) }}</span></p>
        </div>

        <p>Dear Customer,</p>
        <p>Thank you for your order! We are pleased to confirm that we have received your order and it is now being processed.</p>

        <table class="product-table">
            <thead>
                <tr>
                    <th width="50%">Product</th>
                    <th width="15%" style="text-align: center;">Qty</th>
                    <th width="20%" style="text-align: right;">Price</th>
                    <th width="15%" style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="product-name">{{ $orders->product->name ?? 'Custom Item' }}</div>
                        <div class="product-detail">Color: {{ ucfirst($orders->color) }}</div>
                        @if($orders->sizes && $orders->sizes->count() > 0)
                            <div class="product-detail">
                                Sizes: 
                                @foreach($orders->sizes as $size)
                                    {{ $size->name }} ({{ $size->pivot->quantity }}), 
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td style="text-align: center;">{{ $orders->total_quantity }}</td>
                    <td style="text-align: right;">&#8369;{{ number_format($orders->product_unit_price, 2) }}</td>
                    <td style="text-align: right;"><strong>&#8369;{{ number_format($orders->total_price, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <span class="total-label">Grand Total:</span>
            <span class="total-amount">&#8369;{{ number_format($orders->total_price, 2) }}</span>
        </div>

        <div class="footer">
            <p>If you have any questions, please reply to this email or contact our support team.</p>
            <p>&copy; {{ date('Y') }} Jarvis Designs. All rights reserved.</p>
            <p style="font-size: 10px; color: #bbb;">This is a system-generated email. Please do not reply directly to this message if it was sent from a noreply address.</p>
        </div>
    </div>
</body>
</html>