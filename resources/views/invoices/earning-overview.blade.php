<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Earning Overview</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ffffff;
            font-size: 13px;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h2 {
            margin: 0;
            color: #000;
            font-size: 24px;
        }

        .header p {
            color: #666;
            margin-top: 5px;
        }

        /* Grid System using Table for PDF Compatibility */
        .stats-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
            margin-bottom: 10px;
        }

        .box {
            background: #eaf6ff;
            border-radius: 12px;
            padding: 15px;
            width: 50%;
            vertical-align: top;
        }

        .box-third {
            background: #eaf6ff;
            border-radius: 12px;
            padding: 15px;
            width: 33.33%;
            vertical-align: top;
        }

        .title {
            font-size: 12px;
            color: #555;
            text-transform: uppercase;
            font-weight: bold;
        }

        .amount {
            font-size: 18px;
            font-weight: bold;
            margin-top: 8px;
            color: #000;
        }

        .wide-box {
            background: #eaf6ff;
            border-radius: 12px;
            padding: 20px;
            margin: 10px;
        }

        .payout-title {
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
        }

        .payout-amount {
            float: right;
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
        }

        .sub {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .footer-note {
            margin-top: 30px;
            font-size: 11px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Earning Overview</h2>
    <p>Generated on {{ $generated_at }}</p>
    @if($fromDate && $toDate)
        <p>Report Period: {{ $fromDate }} to {{ $toDate }}</p>
    @endif
</div>

<table class="stats-table">
    <tr>
        <td class="box">
            <div class="title">Today's Earnings</div>
            <div class="amount">{{ $today }}</div>
        </td>
        <td class="box">
            <div class="title">This Week</div>
            <div class="amount">{{ $week }}</div>
        </td>
    </tr>
</table>

<table class="stats-table">
    <tr>
        <td class="box-third">
            <div class="title">This Month</div>
            <div class="amount">{{ $month }}</div>
        </td>
        <td class="box-third">
            <div class="title">Processing Payout</div>
            <div class="amount">{{ $pending_payout }}</div>
        </td>
        <td class="box-third">
            <div class="title">Past Earnings</div>
            <div class="amount">{{ $past_earning }}</div>
        </td>
    </tr>
</table>

<table class="stats-table">
    <tr>
        <td class="box" style="width: 100%; background: #fff5f5;">
            <div class="title" style="color: #c53030;">Platform Commission (Security Deposit)</div>
            <div class="amount" style="color: #c53030;">{{ $platform_commission }}</div>
            <div class="sub">Total pending commission from unpaid orders</div>
        </td>
    </tr>
</table>

<div class="wide-box">
    <div style="width: 100%;">
        <span class="payout-title">Next Scheduled Payout</span>
        <span class="payout-amount">{{ $next_payout_amount }}</span>
    </div>
    <div style="clear: both;"></div>
    <div class="sub">Your next payout is scheduled on <strong>{{ $next_payout_date }}</strong></div>
</div>

<div class="footer-note">
    This is an automatically generated earning report. 
    Earnings are calculated as 90% of the security deposit collected.
</div>

</body>
</html>