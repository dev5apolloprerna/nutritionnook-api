{{-- resources/views/reports/platform-health.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="card-title mb-0">Platform Health Analytics</h4>
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
                    <form method="GET" action="{{ route('admin.reports.platform-health') }}" class="form-inline">
                        <div class="form-group mr-2">
                            <label class="mr-2">From:</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate->format('Y-m-d') }}">
                        </div>
                        <div class="form-group mr-2">
                            <label class="mr-2">To:</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate->format('Y-m-d') }}">
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
                            <h3 class="mb-0">{{ $ordersTrend->sum('order_count') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-gradient-success text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Total Revenue</h6>
                            <h3 class="mb-0">₹{{ number_format($ordersTrend->sum('revenue'), 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-gradient-warning text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Peak Hour</h6>
                            <h3 class="mb-0">
                                @php $peakHour = $peakHours->sortByDesc('order_count')->first(); @endphp
                                {{ $peakHour ? $peakHour->hour . ':00' : 'N/A' }}
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-gradient-info text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Active Coupons</h6>
                            <h3 class="mb-0">{{ $couponUsage->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Trend Chart -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Orders Trend (Last {{ $ordersTrend->count() }} days)</h5>
                            <canvas id="ordersTrendChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Trend Table -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Daily Orders Breakdown</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="trendTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Order Count</th>
                                    <th>Revenue</th>
                                    <th>Avg Order Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ordersTrend as $trend)
                                <tr>
                                    <td>{{ date('d M Y', strtotime($trend->date)) }}</td>
                                    <td>{{ $trend->order_count }}</td>
                                    <td>₹{{ number_format($trend->revenue, 2) }}</td>
                                    <td>₹{{ number_format($trend->order_count > 0 ? $trend->revenue / $trend->order_count : 0, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Peak Ordering Time -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <h5 class="mb-3">Peak Ordering Hours</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Hour</th>
                                    <th>Order Count</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($peakHours as $hour)
                                <tr>
                                    <td>{{ sprintf('%02d:00 - %02d:00', $hour->hour, ($hour->hour + 1) % 24) }}</td>
                                    <td>{{ $hour->order_count }}</td>
                                    <td>₹{{ number_format($hour->revenue, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Coupon Usage Report -->
                <div class="col-md-6">
                    <h5 class="mb-3">Coupon Usage Report</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Coupon Code</th>
                                    <th>Type</th>
                                    <th>Value</th>
                                    <th>Usage Count</th>
                                    <th>Total Discount</th>
                                    <th>Unique Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($couponUsage as $coupon)
                                <tr>
                                    <td><span class="badge badge-primary">{{ $coupon->code }}</span></td>
                                    <td>{{ ucfirst($coupon->type) }}</td>
                                    <td>
                                        @if($coupon->type == 'percentage')
                                            {{ $coupon->value }}%
                                        @else
                                            ₹{{ number_format($coupon->value, 2) }}
                                        @endif
                                    </td>
                                    <td>{{ $coupon->usage_count }}</td>
                                    <td>₹{{ number_format($coupon->total_discount, 2) }}</td>
                                    <td>{{ $coupon->unique_users }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No coupons used in this period</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- DAU Chart (if available) -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Daily Active Users (Estimated)</h5>
                            <canvas id="dauChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        $('#trendTable').DataTable({
            "pageLength": 25,
            "order": [[0, "desc"]]
        });

        // Orders Trend Chart
        var ctx1 = document.getElementById('ordersTrendChart').getContext('2d');
        var ordersTrendChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: {!! json_encode($ordersTrend->pluck('date')->map(function($date) { return date('d M', strtotime($date)); })) !!},
                datasets: [{
                    label: 'Orders',
                    data: {!! json_encode($ordersTrend->pluck('order_count')) !!},
                    borderColor: '#4caf50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Revenue (₹)',
                    data: {!! json_encode($ordersTrend->pluck('revenue')) !!},
                    borderColor: '#ff9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Order Count'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        // DAU Chart
        var ctx2 = document.getElementById('dauChart').getContext('2d');
        var dauChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: {!! json_encode($ordersTrend->pluck('date')->map(function($date) { return date('d M', strtotime($date)); })) !!},
                datasets: [{
                    label: 'New Users',
                    data: {!! json_encode($dailyActiveUsers->pluck('new_users')) !!},
                    backgroundColor: '#4caf50',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Users'
                        }
                    }
                }
            }
        });
    });
</script>
@endpush