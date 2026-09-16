{{-- resources/views/reports/menu-cuisine.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="card-title mb-0">Menu & Cuisine Insights</h4>
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
                    <form method="GET" action="{{ route('admin.reports.menu-cuisine') }}" class="form-inline">
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
                <div class="col-md-4 mb-3">
                    <div class="card bg-gradient-primary text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Total Cuisines</h6>
                            <h3 class="mb-0">{{ $topCuisines->count() }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-gradient-success text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Total Dishes Sold</h6>
                            <h3 class="mb-0">{{ $mostOrderedDishes->sum('order_count') }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-gradient-danger text-white">
                        <div class="card-body p-3">
                            <h6 class="mb-0">Out of Stock Items</h6>
                            <h3 class="mb-0">{{ $outOfStockItems->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Most Ordered Cuisine Types -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Most Ordered Cuisine Types</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="cuisineTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Cuisine Type</th>
                                    <th>Order Count</th>
                                    <th>Revenue</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalCuisineOrders = $topCuisines->sum('order_count'); @endphp
                                @forelse($topCuisines as $cuisine)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $cuisine->title }}</td>
                                    <td>{{ $cuisine->order_count }}</td>
                                    <td>₹{{ number_format($cuisine->revenue, 2) }}</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                style="width: {{ $totalCuisineOrders > 0 ? ($cuisine->order_count / $totalCuisineOrders) * 100 : 0 }}%" 
                                                aria-valuenow="{{ $totalCuisineOrders > 0 ? ($cuisine->order_count / $totalCuisineOrders) * 100 : 0 }}" 
                                                aria-valuemin="0" aria-valuemax="100">
                                                {{ $totalCuisineOrders > 0 ? round(($cuisine->order_count / $totalCuisineOrders) * 100, 1) : 0 }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No cuisine data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Most Ordered Dishes -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Most Ordered Dishes</h5>
                    <div class="table-responsive">
                        <table class="table table-hover" id="dishesTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Dish Name</th>
                                    <th>Chef</th>
                                    <th>Cuisine</th>
                                    <th>Price</th>
                                    <th>Order Count</th>
                                    <th>Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mostOrderedDishes as $dish)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $dish->name }}</td>
                                    <td>{{ $dish->chef_name }}</td>
                                    <td>{{ $dish->cuisine }}</td>
                                    <td>₹{{ number_format($dish->price, 2) }}</td>
                                    <td><span class="badge badge-success">{{ $dish->order_count }}</span></td>
                                    <td>₹{{ number_format($dish->total_revenue, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No dish sales data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Out of Stock / Disabled Items -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Out of Stock / Disabled Items</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Dish Name</th>
                                    <th>Chef</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($outOfStockItems as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->chef_name }}</td>
                                    <td>₹{{ number_format($item->price, 2) }}</td>
                                    <td>
                                        @if($item->in_stock == 0)
                                            <label class="badge badge-danger">Out of Stock</label>
                                        @elseif($item->is_active == 0)
                                            <label class="badge badge-warning">Disabled</label>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->in_stock == 0)
                                            No stock available
                                        @elseif($item->is_active == 0)
                                            Manually disabled
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No out of stock or disabled items</td>
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
        $('#cuisineTable').DataTable({
            "pageLength": 25,
            "order": [[2, "desc"]]
        });
        
        $('#dishesTable').DataTable({
            "pageLength": 25,
            "order": [[5, "desc"]]
        });
    });
</script>
@endpush