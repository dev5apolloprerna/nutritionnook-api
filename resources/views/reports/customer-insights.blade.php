{{-- resources/views/reports/customer-insights.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="card-title mb-0">Customer Insights Report</h4>
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
                    <form method="GET" action="{{ route('admin.reports.customer-insights') }}" class="form-inline">
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
                            <h6 class="mb-0">Total Customers</h6>
                            <h3 class="mb-0">{{ $summary['total_customers'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-gradient-success text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">New Customers</h6>
                            <h3 class="mb-0">{{ $summary['new_customers'] }}</h3>
                            <small>{{ $summary['total_customers'] > 0 ? round(($summary['new_customers'] / $summary['total_customers']) * 100, 1) : 0 }}%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-gradient-warning text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Returning Customers</h6>
                            <h3 class="mb-0">{{ $summary['returning_customers'] }}</h3>
                            <small>{{ $summary['total_customers'] > 0 ? round(($summary['returning_customers'] / $summary['total_customers']) * 100, 1) : 0 }}%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-gradient-info text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Total Revenue</h6>
                            <h3 class="mb-0">₹{{ number_format($summary['total_revenue'], 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Acquisition Chart -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Customer Acquisition</h5>
                            <div style="display: flex; justify-content: center;">
                                <div style="width: 300px; height: 300px;">
                                    <canvas id="customerChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Top Customers (by spending)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="topCustomersTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Avg Order</th>
                                    <th>Last Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCustomers as $customer)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($customer->image)
                                                <img src="{{ asset($customer->image) }}" alt="profile" class="mr-2" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                                            @endif
                                            {{ $customer->name }}
                                        </div>
                                    </td>
                                    <td>{{ $customer->email }}</td>
                                    <td>{{ $customer->phone_number }}</td>
                                    <td><span class="badge badge-primary">{{ $customer->order_count }}</span></td>
                                    <td><strong>₹{{ number_format($customer->total_spent, 2) }}</strong></td>
                                    <td>₹{{ number_format($customer->avg_order_value, 2) }}</td>
                                    <td>{{ $customer->last_order_date }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No customer data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer Rating Summary -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Customer Rating Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Customer</th>
                                    <th>Total Ratings</th>
                                    <th>Avg Rating</th>
                                    <th>5★</th>
                                    <th>4★</th>
                                    <th>3★</th>
                                    <th>2★</th>
                                    <th>1★</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customerRatings as $rating)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $rating->name }}</td>
                                    <td>{{ $rating->total_ratings_given }}</td>
                                    <td>
                                        <span class="badge badge-{{ $rating->avg_rating_given >= 4 ? 'success' : ($rating->avg_rating_given >= 3 ? 'warning' : 'danger') }}">
                                            {{ number_format($rating->avg_rating_given, 1) }} ★
                                        </span>
                                    </td>
                                    <td>{{ $rating->five_star_ratings }}</td>
                                    <td>{{ $rating->four_star_ratings }}</td>
                                    <td>{{ $rating->three_star_ratings }}</td>
                                    <td>{{ $rating->two_star_ratings }}</td>
                                    <td>{{ $rating->one_star_ratings }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No ratings from customers</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Order Distribution -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Distribution</h5>
                            <canvas id="orderDistributionChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Customer Summary</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Total Customers</th>
                                    <td>{{ $summary['total_customers'] }}</td>
                                </tr>
                                <tr>
                                    <th>Customers with Orders</th>
                                    <td>{{ $summary['customers_with_orders'] }}</td>
                                </tr>
                                <tr>
                                    <th>Inactive Customers</th>
                                    <td>{{ $summary['inactive_customers'] }}</td>
                                </tr>
                                <tr>
                                    <th>New vs Returning Ratio</th>
                                    <td>{{ $summary['new_customers'] }} : {{ $summary['returning_customers'] }}</td>
                                </tr>
                                <tr>
                                    <th>Avg Revenue per Customer</th>
                                    <td>₹{{ number_format($summary['average_revenue_per_customer'], 2) }}</td>
                                </tr>
                            </table>
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
        $('#topCustomersTable').DataTable({
            "pageLength": 25,
            "order": [[5, "desc"]]
        });

        // Customer Chart (Doughnut)
        var ctx1 = document.getElementById('customerChart').getContext('2d');
        var customerChart = new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['New Customers ({{ $summary['new_customers'] }})', 'Returning Customers ({{ $summary['returning_customers'] }})'],
                datasets: [{
                    data: [{{ $summary['new_customers'] }}, {{ $summary['returning_customers'] }}],
                    backgroundColor: ['#4caf50', '#ff9800'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        fontColor: '#333',
                        fontSize: 12
                    }
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var dataset = data.datasets[tooltipItem.datasetIndex];
                            var total = dataset.data.reduce(function(previousValue, currentValue) {
                                return previousValue + currentValue;
                            });
                            var currentValue = dataset.data[tooltipItem.index];
                            var percentage = Math.floor(((currentValue / total) * 100) + 0.5);
                            return percentage + '%';
                        }
                    }
                }
            }
        });

        // Order Distribution Chart
        var ctx2 = document.getElementById('orderDistributionChart').getContext('2d');
        var orderChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['1-2 Orders', '3-5 Orders', '6-10 Orders', '10+ Orders'],
                datasets: [{
                    label: 'Number of Customers',
                    data: [
                        {{ $orderDistribution['1-2 orders'] }},
                        {{ $orderDistribution['3-5 orders'] }},
                        {{ $orderDistribution['6-10 orders'] }},
                        {{ $orderDistribution['10+ orders'] }}
                    ],
                    backgroundColor: ['#4caf50', '#2196f3', '#ff9800', '#f44336'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }]
                },
                legend: {
                    display: false
                }
            }
        });
    });
</script>
@endpush