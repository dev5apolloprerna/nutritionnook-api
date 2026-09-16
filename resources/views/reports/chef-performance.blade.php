{{-- resources/views/reports/chef-performance.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="card-title mb-0">Chef Performance Report</h4>
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
                    <form method="GET" action="{{ route('admin.reports.chef-performance') }}" class="form-inline">
                        <div class="form-group mr-2">
                            <label class="mr-2">From:</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate->format('Y-m-d') }}">
                        </div>
                        <div class="form-group mr-2">
                            <label class="mr-2">To:</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate->format('Y-m-d') }}">
                        </div>
                        <div class="form-group mr-2">
                            <select name="chef_id" class="form-control form-control-sm">
                                <option value="">All Chefs</option>
                                @foreach(App\Models\Chef::where('is_verify', 1)->get() as $chef)
                                    <option value="{{ $chef->id }}" {{ request('chef_id') == $chef->id ? 'selected' : '' }}>
                                        {{ $chef->name }} ({{ $chef->business_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-gradient-primary btn-sm">Generate</button>
                    </form>
                </div>
            </div>

            <!-- Chef Earnings Report -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Chef Earnings Report</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="earningsTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Chef Name</th>
                                    <th>Business Name</th>
                                    <th>Phone</th>
                                    <th>Total Orders</th>
                                    <th>Gross Earnings</th>
                                    <th>Commission ({{ $chefEarnings->first()->commission ?? 10 }}%)</th>
                                    <th>Net Earnings</th>
                                    <th>Avg Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chefEarnings as $index => $chef)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $chef->name }}</td>
                                    <td>{{ $chef->business_name ?? 'N/A' }}</td>
                                    <td>{{ $chef->phone_number }}</td>
                                    <td>{{ $chef->total_orders ?? 0 }}</td>
                                    <td>₹{{ number_format($chef->gross_earnings ?? 0, 2) }}</td>
                                    <td>₹{{ number_format($chef->commission_amount ?? 0, 2) }}</td>
                                    <td>₹{{ number_format($chef->net_earnings ?? 0, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $chef->avg_rating >= 4 ? 'success' : ($chef->avg_rating >= 3 ? 'warning' : 'danger') }}">
                                            {{ number_format($chef->avg_rating ?? 0, 1) }} ★
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="4" class="text-right">Total:</td>
                                    <td>{{ $chefEarnings->sum('total_orders') }}</td>
                                    <td>₹{{ number_format($chefEarnings->sum('gross_earnings'), 2) }}</td>
                                    <td>₹{{ number_format($chefEarnings->sum('commission_amount'), 2) }}</td>
                                    <td>₹{{ number_format($chefEarnings->sum('net_earnings'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Chef Acceptance Rate -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Chef Acceptance Rate</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="acceptanceTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Chef Name</th>
                                    <th>Total Orders</th>
                                    <th>Accepted</th>
                                    <th>Rejected</th>
                                    <th>Acceptance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($chefAcceptance as $chef)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $chef->name }}</td>
                                    <td>{{ $chef->total_orders }}</td>
                                    <td class="text-success">{{ $chef->accepted_orders }}</td>
                                    <td class="text-danger">{{ $chef->rejected_orders }}</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                style="width: {{ $chef->acceptance_rate }}%" 
                                                aria-valuenow="{{ $chef->acceptance_rate }}" 
                                                aria-valuemin="0" aria-valuemax="100">
                                                {{ $chef->acceptance_rate }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Selling Items -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Top Selling Items</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Dish Name</th>
                                    <th>Chef</th>
                                    <th>Price</th>
                                    <th>Order Count</th>
                                    <th>Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topItems as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->chef_name }}</td>
                                    <td>₹{{ number_format($item->price, 2) }}</td>
                                    <td>{{ $item->order_count }}</td>
                                    <td>₹{{ number_format($item->total_revenue, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No items sold in this period</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Chef Rating Summary -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Chef Rating Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Chef Name</th>
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
                                @forelse($chefRatings as $rating)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $rating->name }}</td>
                                    <td>{{ $rating->total_ratings }}</td>
                                    <td>
                                        <span class="badge badge-{{ $rating->avg_rating >= 4 ? 'success' : ($rating->avg_rating >= 3 ? 'warning' : 'danger') }}">
                                            {{ $rating->avg_rating }} ★
                                        </span>
                                    </td>
                                    <td>{{ $rating->five_star }}</td>
                                    <td>{{ $rating->four_star }}</td>
                                    <td>{{ $rating->three_star }}</td>
                                    <td>{{ $rating->two_star }}</td>
                                    <td>{{ $rating->one_star }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No ratings available</td>
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
        $('#earningsTable').DataTable({
            "pageLength": 25,
            "order": [[4, "desc"]]
        });
        
        $('#acceptanceTable').DataTable({
            "pageLength": 25,
            "order": [[5, "desc"]]
        });
    });
</script>
@endpush