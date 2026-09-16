@extends('layouts.app')
@section('content')

<style>
    .nn-payout-page { padding: 0; }
    .nn-payout-header {
        background: linear-gradient(135deg, #FF8C00, #FFA500);
        border-radius: 0 0 20px 20px;
        padding: 20px 25px;
        margin: -20px -25px 25px -25px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .nn-payout-header h2 { font-size: 1.8rem; font-weight: 700; margin: 0; color: #fff; }
    .nn-pcard {
        background: #FFF8F0;
        border: 1px solid #FFE0B2;
        border-radius: 14px;
        padding: 22px;
    }
    .nn-pcard-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 18px;
        padding-bottom: 10px;
        border-bottom: 2px solid #FFE0B2;
    }
    .nn-chef-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .nn-chef-table th {
        background: #FFF0E0;
        padding: 10px 14px;
        font-size: 0.78rem;
        text-transform: uppercase;
        color: #888;
        font-weight: 700;
        border-bottom: 2px solid #FFE0B2;
    }
    .nn-chef-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #FFF0E0;
        font-size: 0.88rem;
        color: #333;
    }
    .nn-chef-table tr:hover td { background: #FFF5E6; }
    .nn-status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: capitalize;
    }
    .nn-status-success { background: #D4EDDA; color: #155724; }
    .nn-status-failed { background: #F8D7DA; color: #721C24; }
    .nn-status-processing { background: #FFF3CD; color: #856404; }
    .nn-status-reversed { background: #F8D7DA; color: #721C24; }
    .nn-status-queued { background: #D1ECF1; color: #0C5460; }
    .nn-btn-retry {
        background: #FF6B35;
        color: #fff;
        border: none;
        padding: 4px 14px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.78rem;
        cursor: pointer;
    }
    .nn-btn-retry:hover { background: #e55a2b; color: #fff; }
    .nn-btn-back {
        background: rgba(255,255,255,0.2);
        color: #fff;
        border: none;
        padding: 8px 18px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        text-decoration: none;
    }
    .nn-btn-back:hover { background: rgba(255,255,255,0.35); color: #fff; }
    .nn-alert {
        border-radius: 10px;
        padding: 14px 20px;
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .nn-alert-success { background: #D4EDDA; color: #155724; border: 1px solid #C3E6CB; }
    .nn-alert-error { background: #F8D7DA; color: #721C24; border: 1px solid #F5C6CB; }
    .nn-month-filter {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .nn-month-filter select {
        padding: 8px 16px;
        border: 1px solid #FFE0B2;
        border-radius: 8px;
        font-size: 0.88rem;
        background: #fff;
        font-weight: 600;
        color: #333;
    }
    .nn-month-filter .nn-filter-btn {
        background: linear-gradient(135deg, #FF8C00, #FFA500);
        color: #fff;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
    }
    .nn-month-filter .nn-filter-btn:hover {
        box-shadow: 0 4px 12px rgba(255,140,0,0.3);
    }
    .nn-summary-stat {
        text-align: center;
        padding: 14px;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #FFE0B2;
    }
    .nn-summary-stat .stat-num {
        font-size: 1.2rem;
        font-weight: 800;
        color: #FF8C00;
    }
    .nn-summary-stat .stat-lbl {
        font-size: 0.72rem;
        color: #999;
        text-transform: uppercase;
        font-weight: 600;
    }
</style>

<div class="nn-payout-page">

    <div class="nn-payout-header">
        <h2>Payout History & Reports</h2>
        <a href="{{ route('admin.payouts.create') }}" class="nn-btn-back">
            <i class="mdi mdi-arrow-left"></i> Back to Payouts
        </a>
    </div>

    @if(session('success'))
        <div class="nn-alert nn-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="nn-alert nn-alert-error">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.payouts.index') }}" class="nn-month-filter">
        <label style="font-weight: 700; color: #333;">Filter by Month:</label>
        <select name="month">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
            @endfor
        </select>
        <select name="year">
            @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        <button type="submit" class="nn-filter-btn">
            <i class="mdi mdi-filter"></i> Filter
        </button>
        <a href="{{ route('admin.payouts.index') }}" style="font-size: 0.82rem; color: #FF8C00; font-weight: 600; text-decoration: none;">Show All</a>
    </form>

    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="nn-summary-stat">
                <div class="stat-num">{{ $monthlyStats['success_count'] }}</div>
                <div class="stat-lbl">Successful</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="nn-summary-stat">
                <div class="stat-num">{{ $monthlyStats['processing_count'] }}</div>
                <div class="stat-lbl">Processing</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="nn-summary-stat">
                <div class="stat-num">{{ $monthlyStats['failed_count'] }}</div>
                <div class="stat-lbl">Failed</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="nn-summary-stat">
                <div class="stat-num">₹{{ number_format($monthlyStats['total_paid']) }}</div>
                <div class="stat-lbl">Total Paid</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="nn-summary-stat">
                <div class="stat-num">₹{{ number_format($monthlyStats['total_commission']) }}</div>
                <div class="stat-lbl">Commission</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="nn-summary-stat">
                <div class="stat-num">₹{{ number_format($monthlyStats['total_earnings']) }}</div>
                <div class="stat-lbl">Gross Earnings</div>
            </div>
        </div>
    </div>

    <div class="nn-pcard">
        <div class="nn-pcard-title">
            <i class="mdi mdi-history"></i> Payout Records
            @if(request()->has('month'))
                - {{ date('F', mktime(0,0,0,$month,1)) }} {{ $year }}
            @endif
        </div>
        @if($payouts->count() > 0)
        <div class="table-responsive">
            <table class="nn-chef-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Chef</th>
                        <th>Month</th>
                        <th>Orders</th>
                        <th>Earnings</th>
                        <th>Commission</th>
                        <th>Payout</th>
                        <th>Razorpay ID</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payouts as $payout)
                    <tr>
                        <td>#{{ $payout->id }}</td>
                        <td>{{ $payout->chef ? $payout->chef->name : 'Chef #' . $payout->chef_id }}</td>
                        <td>{{ $payout->month_name }}</td>
                        <td>{{ $payout->order_count }}</td>
                        <td>₹{{ number_format($payout->total_earning) }}</td>
                        <td>₹{{ number_format($payout->commission_amount) }}</td>
                        <td><strong>₹{{ number_format($payout->payout_amount) }}</strong></td>
                        <td style="font-size: 0.75rem;">{{ $payout->razorpay_payout_id ? Str::limit($payout->razorpay_payout_id, 18) : '-' }}</td>
                        <td>
                            <span class="nn-status-badge nn-status-{{ $payout->status }}">
                                {{ $payout->status }}
                            </span>
                            @if($payout->failure_reason)
                                <br><span style="font-size: 0.7rem; color: #dc3545;">{{ Str::limit($payout->failure_reason, 40) }}</span>
                            @endif
                        </td>
                        <td>{{ $payout->created_at ? $payout->created_at->format('M d, Y') : '-' }}</td>
                        <td>
                            @if($payout->status === 'failed')
                            <form action="{{ route('admin.payouts.retry', $payout->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Retry this payout?');">
                                @csrf
                                <button type="submit" class="nn-btn-retry">
                                    <i class="mdi mdi-refresh"></i> Retry
                                </button>
                            </form>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $payouts->appends(request()->query())->links() }}
        </div>
        @else
        <p style="text-align: center; color: #999; padding: 40px;">No payout records found for this period</p>
        @endif
    </div>

</div>

@endsection
