<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resourceType === 'debt' ? 'Debt' : 'Lending' }} Record Shared</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .info-box {
            background-color: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .info-box .label {
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 5px;
        }
        .info-box .value {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        .details-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .detail-row {
            display: table-row;
        }
        .detail-label, .detail-value {
            display: table-cell;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-label {
            color: #64748b;
            width: 40%;
        }
        .detail-value {
            font-weight: 500;
        }
        .cta-button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .cta-button:hover {
            background-color: #1d4ed8;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 14px;
            text-align: center;
        }
        .link-fallback {
            word-break: break-all;
            color: #64748b;
            font-size: 12px;
        }
        .amount-highlight {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SmartBucket</h1>
        </div>

        <div class="content">
            <p>Hello,</p>

            <p>
                <strong>{{ $sender->name }}</strong> has shared a {{ $resourceType }} record with you.
            </p>

            <div class="info-box">
                <div class="label">{{ $resourceType === 'debt' ? 'Debtor' : 'Borrower' }}</div>
                <div class="value">{{ $personName }}</div>
            </div>

            <div class="details-grid">
                <div class="detail-row">
                    <div class="detail-label">Total Amount</div>
                    <div class="detail-value">${{ number_format($amount, 2) }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Remaining Amount</div>
                    <div class="detail-value amount-highlight">${{ number_format($remainingAmount, 2) }}</div>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ $shareUrl }}" class="cta-button">View Full Details</a>
            </div>

            <p class="link-fallback">
                Or copy and paste this link in your browser:<br>
                {{ $shareUrl }}
            </p>

            @if($shareToken->expires_at)
            <p style="color: #f59e0b; font-size: 14px;">
                This link will expire on {{ $shareToken->expires_at->format('F j, Y \a\t g:i A') }}.
            </p>
            @endif
        </div>

        <div class="footer">
            <p>
                This email was sent from SmartBucket - Your Personal Finance Tracker.<br>
                If you didn't expect this email, you can safely ignore it.
            </p>
        </div>
    </div>
</body>
</html>
