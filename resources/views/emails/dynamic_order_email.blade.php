<!DOCTYPE html>
<html>
<head>
    <title>{{ $header_title ?? 'Food App' }}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header {
            padding: 35px 25px;
            text-align: center;
            background-color: {{ $header_bg_color ?? '#ff6b6b' }};
            color: white;
        }
        .header h1 {
            margin: 10px 0 0;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .header .icon {
            font-size: 56px;
            margin-bottom: 10px;
            line-height: 1;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.95;
            font-size: 16px;
        }
        .content {
            padding: 35px 30px;
            background-color: #ffffff;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .greeting strong {
            color: {{ $accent_color ?? '#ff6b6b' }};
        }
        .message-box {
            background-color: #f8f9fa;
            padding: 20px 25px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid {{ $accent_color ?? '#ff6b6b' }};
            font-size: 16px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
            margin: 15px 0;
            background-color: {{ $status_color ?? $accent_color ?? '#28a745' }};
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
            border: 1px solid #e9ecef;
        }
        .card h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: {{ $accent_color ?? '#ff6b6b' }};
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card h3 span {
            font-size: 24px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #dee2e6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
            flex: 0 0 40%;
        }
        .detail-value {
            color: #212529;
            flex: 0 0 58%;
            text-align: right;
            font-weight: 500;
        }
        .items-list {
            margin: 15px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background-color: white;
            margin-bottom: 8px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: all 0.2s;
        }
        .item:hover {
            border-color: {{ $accent_color ?? '#ff6b6b' }};
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .item-name {
            font-weight: 600;
            color: #212529;
        }
        .item-quantity {
            color: #6c757d;
            font-size: 14px;
            margin-left: 5px;
        }
        .item-price {
            font-weight: 600;
            color: {{ $accent_color ?? '#ff6b6b' }};
        }
        .price-breakdown {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
        }
        .total-amount {
            font-size: 22px;
            font-weight: 700;
            text-align: right;
            margin-top: 20px;
            padding: 15px 0;
            border-top: 2px solid {{ $accent_color ?? '#ff6b6b' }};
            color: {{ $accent_color ?? '#ff6b6b' }};
        }
        .info-box {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            color: #004085;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            color: #856404;
        }
        .success-box {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            color: #155724;
        }
        .button {
            display: inline-block;
            padding: 14px 35px;
            background-color: {{ $button_color ?? $accent_color ?? '#ff6b6b' }};
            color: white !important;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0 10px;
            text-align: center;
            border: none;
            box-shadow: 0 4px 10px rgba({{ $button_color ?? $accent_color ?? '#ff6b6b' }}, 0.3);
            transition: all 0.3s;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba({{ $button_color ?? $accent_color ?? '#ff6b6b' }}, 0.4);
            opacity: 0.95;
        }
        .footer {
            padding: 25px;
            text-align: center;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 13px;
        }
        .social-links {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .social-links a {
            color: {{ $accent_color ?? '#ff6b6b' }};
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        .social-links a:hover {
            text-decoration: underline;
        }
        hr {
            border: none;
            border-top: 1px solid #e9ecef;
            margin: 25px 0;
        }
        @media only screen and (max-width: 600px) {
            .container { margin: 10px; }
            .content { padding: 25px 20px; }
            .header { padding: 25px 20px; }
            .header h1 { font-size: 24px; }
            .detail-row { flex-direction: column; }
            .detail-label { margin-bottom: 5px; }
            .detail-value { text-align: left; }
            .button { display: block; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="icon">{{ $header_icon ?? '🍽️' }}</div>
            <h1>{{ $header_title ?? 'Food App' }}</h1>
            @if(isset($header_subtitle))
                <p>{{ $header_subtitle }}</p>
            @endif
        </div>

        <!-- Content Section -->
        <div class="content">
            <!-- Greeting -->
            <div class="greeting">
                Hello <strong>{{ $user_name ?? 'Valued Customer' }}</strong>,
            </div>

            <!-- Main Message -->
            @if(isset($main_message))
                <div class="message-box">
                    {!! $main_message !!}
                </div>
            @endif

            <!-- Status Badge (if status is provided) -->
            @if(isset($status))
                <div style="text-align: center;">
                    <span class="status-badge">
                        {{ $status_icon ?? '' }} {{ strtoupper($status) }}
                    </span>
                </div>
            @endif

            <!-- ==================== ORDER DETAILS CARD ==================== -->
            @if(isset($order_id) || isset($complaint_id) || isset($issue_id))
                <div class="card">
                    <h3>
                        <span>📋</span> 
                        {{ $card_title ?? 'Details' }}
                    </h3>
                    
                    @if(isset($order_id))
                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        <span class="detail-value">#{{ $order_id }}</span>
                    </div>
                    @endif
                    
                    @if(isset($complaint_id) || isset($issue_id))
                    <div class="detail-row">
                        <span class="detail-label">Complaint ID:</span>
                        <span class="detail-value">#{{ $complaint_id ?? $issue_id }}</span>
                    </div>
                    @endif
                    
                    @if(isset($issue_title))
                    <div class="detail-row">
                        <span class="detail-label">Title:</span>
                        <span class="detail-value">{{ $issue_title }}</span>
                    </div>
                    @endif
                    
                    @if(isset($chef_name))
                    <div class="detail-row">
                        <span class="detail-label">Chef:</span>
                        <span class="detail-value">{{ $chef_name }}</span>
                    </div>
                    @endif
                    
                    @if(isset($user_name) && isset($show_customer))
                    <div class="detail-row">
                        <span class="detail-label">Customer:</span>
                        <span class="detail-value">{{ $user_name }}</span>
                    </div>
                    @endif
                    
                    @if(isset($order_date))
                    <div class="detail-row">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($order_date)->format('d M Y') }}</span>
                    </div>
                    @endif
                    
                    @if(isset($created_at))
                    <div class="detail-row">
                        <span class="detail-label">Created:</span>
                        <span class="detail-value">{{ $created_at }}</span>
                    </div>
                    @endif
                    
                    @if(isset($update_time))
                    <div class="detail-row">
                        <span class="detail-label">{{ $time_label ?? 'Updated' }}:</span>
                        <span class="detail-value">{{ $update_time }}</span>
                    </div>
                    @endif
                    
                    @if(isset($old_status))
                    <div class="detail-row">
                        <span class="detail-label">Previous Status:</span>
                        <span class="detail-value">{{ ucfirst($old_status) }}</span>
                    </div>
                    @endif
                    
                    @if(isset($address))
                    <div class="detail-row">
                        <span class="detail-label">Delivery Address:</span>
                        <span class="detail-value">{{ $address }}</span>
                    </div>
                    @endif
                </div>
            @endif

            <!-- Issue Description -->
            @if(isset($issue_description))
                <div class="card">
                    <h3><span>📝</span> Description</h3>
                    <p style="margin:0; color:#495057;">{{ $issue_description }}</p>
                </div>
            @endif

            <!-- ==================== ORDER ITEMS ==================== -->
            @if(isset($items) && count($items) > 0)
                <div class="card">
                    <h3><span>🛒</span> Order Items</h3>
                    <div class="items-list">
                        @foreach($items as $item)
                            <div class="item">
                                <span>
                                    <span class="item-name">{{ $item['name'] ?? 'Item' }}</span>
                                    @if(isset($item['quantity']))
                                        <span class="item-quantity">x{{ $item['quantity'] }}</span>
                                    @endif
                                </span>
                                <span class="item-price">
                                    ₹{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Price Breakdown -->
                    <div class="price-breakdown">
                        @if(isset($subtotal))
                        <div class="detail-row">
                            <span class="detail-label">Subtotal:</span>
                            <span class="detail-value">₹{{ number_format($subtotal, 2) }}</span>
                        </div>
                        @endif
                        
                        @if(isset($gst))
                        <div class="detail-row">
                            <span class="detail-label">GST:</span>
                            <span class="detail-value">₹{{ number_format($gst, 2) }}</span>
                        </div>
                        @endif
                        
                        @if(isset($discount) && $discount > 0)
                        <div class="detail-row">
                            <span class="detail-label">Discount:</span>
                            <span class="detail-value" style="color:#dc3545;">-₹{{ number_format($discount, 2) }}</span>
                        </div>
                        @endif
                    </div>
                    
                    <div class="total-amount">
                        Total: ₹{{ number_format($amount ?? 0, 2) }}
                    </div>
                </div>
            @endif

            <!-- ==================== COMPLAINT SPECIFIC ==================== -->
            @if(isset($status_message))
                <div class="card">
                    <h3><span>{{ $status_icon ?? '📢' }}</span> Status Update</h3>
                    <p style="margin:0; font-size:16px;">{{ $status_message }}</p>
                </div>
            @endif

            <!-- ==================== ADDITIONAL INFO BOXES ==================== -->
            @if(isset($additional_info))
                <div class="info-box">
                    {!! $additional_info !!}
                </div>
            @endif

            @if(isset($warning_message))
                <div class="warning-box">
                    {!! $warning_message !!}
                </div>
            @endif

            @if(isset($success_message))
                <div class="success-box">
                    {!! $success_message !!}
                </div>
            @endif

            <!-- ==================== REFUND INFO ==================== -->
            @if(isset($refund_percentage) && $refund_percentage > 0)
                <div class="warning-box">
                    <p style="margin:0;">
                        <strong>💰 Refund Status:</strong> {{ $refund_percentage }}% refund applicable.
                        @if(isset($refund_message))
                            <br>{{ $refund_message }}
                        @endif
                        <br><small>Refund will be processed within 5-7 business days.</small>
                    </p>
                </div>
            @endif

            <!-- ==================== CTA BUTTON ==================== -->
            @if(isset($button_text) && isset($button_url))
                <div style="text-align: center;">
                    <a href="{{ $button_url }}" class="button">{{ $button_text }}</a>
                </div>
            @endif

            <!-- ==================== FOOTER NOTE ==================== -->
            @if(isset($footer_note))
                <hr>
                <p style="color: #6c757d; font-size: 14px; text-align: center; margin:0;">
                    {{ $footer_note }}
                </p>
            @endif
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} Food App. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
            
            @if(isset($support_email) || isset($support_phone))
            <div class="social-links">
                @if(isset($support_email))
                    <a href="mailto:{{ $support_email }}">📧 {{ $support_email }}</a>
                @endif
                @if(isset($support_phone))
                    <a href="tel:{{ $support_phone }}">📞 {{ $support_phone }}</a>
                @endif
            </div>
            @endif
            
            <p style="margin-top:15px; font-size:11px;">
                Need help? Contact our support team.
            </p>
        </div>
    </div>
</body>
</html>