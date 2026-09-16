{{-- resources/views/reports/order-revenue.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="card-title mb-0">Order & Revenue Reports</h4>
                    <p class="card-description mb-0">
                        {{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}
                    </p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-gradient-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fa fa-download"></i> Export
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">Excel</a>
                        <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}">PDF</a>
                    </div>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <form method="GET" action="{{ route('admin.reports.order-revenue') }}" class="form-inline">
                        <div class="form-group mr-2">
                            <label class="mr-2">From:</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate->format('Y-m-d') }}">
                        </div>
                        <div class="form-group mr-2">
                            <label class="mr-2">To:</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate->format('Y-m-d') }}">
                        </div>
                        <div class="form-group mr-2">
                            <select name="report_type" class="form-control form-control-sm">
                                <option value="daily" {{ $reportType == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ $reportType == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ $reportType == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-gradient-primary btn-sm">Generate</button>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card bg-gradient-primary text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Total Orders</h6>
                            <h3 class="mb-0">{{ $totalOrders }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-gradient-success text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Total Revenue</h6>
                            <h3 class="mb-0">₹{{ number_format($totalRevenue, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-gradient-warning text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Commission</h6>
                            <h3 class="mb-0">₹{{ number_format($totalCommission, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-gradient-danger text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Refunds</h6>
                            <h3 class="mb-0">₹{{ number_format($totalRefunds, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Status Report -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Order Status Breakdown</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Order Count</th>
                                    <th>Percentage</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalOrdersCount = $orderStatusData->sum('count'); @endphp
                                @foreach($orderStatusData as $status)
                                <tr>
                                    <td>
                                        <label class="badge 
                                            @if($status->status == 'delivered') badge-success
                                            @elseif($status->status == 'cancelled' || $status->status == 'rejected') badge-danger
                                            @elseif($status->status == 'accepted') badge-primary
                                            @elseif($status->status == 'preparing') badge-warning
                                            @else badge-secondary @endif">
                                            {{ ucfirst($status->status) }}
                                        </label>
                                    </td>
                                    <td>{{ $status->count }}</td>
                                    <td>{{ $totalOrdersCount > 0 ? round(($status->count / $totalOrdersCount) * 100, 2) : 0 }}%</td>
                                    <td>₹{{ number_format($status->revenue ?? 0, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Order Trends -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Order Trends ({{ ucfirst($reportType) }})</h5>
                    <div class="table-responsive">
                        <table class="table" id="myTable">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Total Orders</th>
                                    <th>Revenue</th>
                                    <th>Cancelled</th>
                                    <th>Avg Order Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orderTrends as $trend)
                                <tr>
                                    <td>{{ $trend->period }}</td>
                                    <td>{{ $trend->total_orders }}</td>
                                    <td>₹{{ number_format($trend->total_revenue, 2) }}</td>
                                    <td>{{ $trend->cancelled_orders }}</td>
                                    <td>₹{{ number_format($trend->total_orders > 0 ? $trend->total_revenue / $trend->total_orders : 0, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Refunded & Cancelled Orders -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <h5 class="mb-3">Refunded Orders</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Chef</th>
                                    <th>Amount</th>
                                    <th>Refunded On</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($refundedOrders as $order)
                                <tr>
                                    <td>#{{ $order->id }}</td>
                                    <td>{{ $order->user->name ?? 'N/A' }}</td>
                                    <td>{{ $order->chef->name ?? 'N/A' }}</td>
                                    <td>₹{{ number_format($order->amount, 2) }}</td>
                                    <td>{{ $order->refunded_at ? date('d M Y', strtotime($order->refunded_at)) : 'N/A' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No refunded orders</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Cancelled Orders</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Chef</th>
                                    <th>Amount</th>
                                    <th>Cancelled On</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cancelledOrders as $order)
                                <tr>
                                    <td>#{{ $order->id }}</td>
                                    <td>{{ $order->user->name ?? 'N/A' }}</td>
                                    <td>{{ $order->chef->name ?? 'N/A' }}</td>
                                    <td>₹{{ number_format($order->amount, 2) }}</td>
                                    <td>{{ $order->updated_at ? date('d M Y', strtotime($order->updated_at)) : 'N/A' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No cancelled orders</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#myTable').DataTable({
            "pageLength": 25,
            "order": [[0, "desc"]]
        });
    });
</script>
@endpush