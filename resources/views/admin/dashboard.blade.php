@extends('layouts.app')
@section('content')


@php

use Carbon\Carbon;
use App\Models\Chef;

@endphp

<style>
    .nn-dashboard {
        padding: 0;
    }
    .nn-header {
        background: linear-gradient(135deg, #FF8C00, #FFA500);
        border-radius: 0 0 20px 20px;
        padding: 20px 25px;
        margin: -20px -25px 25px -25px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .nn-header h2 {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
        color: #fff;
    }
    .nn-header .admin-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255,255,255,0.2);
        border-radius: 30px;
        padding: 6px 16px 6px 6px;
    }
    .nn-header .admin-badge img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 2px solid #fff;
    }
    .nn-header .admin-badge span {
        font-weight: 600;
        color: #fff;
    }

    .nn-stat-card {
        background: #FFF8F0;
        border: 1px solid #FFE0B2;
        border-radius: 14px;
        padding: 18px 20px;
        text-align: center;
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }
    .nn-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(255,140,0,0.15);
    }
    .nn-stat-card .stat-label {
        font-size: 0.78rem;
        color: #888;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    .nn-stat-card .stat-value {
        font-size: 1.6rem;
        font-weight: 800;
        color: #333;
    }

    .nn-card {
        background: #FFF8F0;
        border: 1px solid #FFE0B2;
        border-radius: 14px;
        padding: 20px;
        height: 100%;
    }
    .nn-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 2px solid #FFE0B2;
    }

    .nn-chef-row {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #FFF0E0;
    }
    .nn-chef-row:last-child { border-bottom: none; }
    .nn-chef-row img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 12px;
        border: 2px solid #FFE0B2;
    }
    .nn-chef-row .chef-info { flex: 1; }
    .nn-chef-row .chef-info h6 {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
    }
    .nn-chef-row .chef-stats {
        display: flex;
        gap: 16px;
        font-size: 0.82rem;
        color: #666;
    }
    .nn-chef-row .chef-stats strong {
        color: #FF8C00;
    }

    .nn-menu-item {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #FFF0E0;
    }
    .nn-menu-item:last-child { border-bottom: none; }
    .nn-menu-item img {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        object-fit: cover;
        margin-right: 12px;
        border: 1px solid #FFE0B2;
    }
    .nn-menu-item .item-name {
        flex: 1;
        font-weight: 600;
        font-size: 0.9rem;
        color: #333;
    }
    .nn-menu-item .item-count {
        font-weight: 700;
        color: #FF8C00;
        font-size: 0.85rem;
    }

    .nn-customer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #FFF0E0;
    }
    .nn-customer-row:last-child { border-bottom: none; }
    .nn-customer-row .cust-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: #333;
    }
    .nn-customer-row .cust-orders {
        font-weight: 700;
        color: #FF8C00;
        font-size: 0.9rem;
    }

    .nn-retention-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #FFF0E0;
    }
    .nn-retention-row:last-child { border-bottom: none; }
    .nn-retention-row .ret-label {
        font-size: 0.78rem;
        color: #999;
        text-transform: uppercase;
        font-weight: 600;
    }
    .nn-retention-row .ret-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: #333;
    }
    .nn-retention-row .ret-count {
        font-weight: 700;
        color: #FF8C00;
    }

    .nn-order-card {
        background: #fff;
        border: 1px solid #FFE0B2;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .nn-order-card .order-info h6 {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 700;
        color: #333;
    }
    .nn-order-card .order-info small {
        color: #999;
        font-size: 0.78rem;
    }
    .nn-order-card .order-meta {
        text-align: right;
    }
    .nn-order-card .order-meta .nn-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .nn-badge-new { background: #FFF3CD; color: #856404; }
    .nn-badge-pending { background: #FFF3CD; color: #856404; }
    .nn-badge-accepted { background: #D4EDDA; color: #155724; }
    .nn-badge-preparing { background: #D1ECF1; color: #0C5460; }
    .nn-badge-ready { background: #CCE5FF; color: #004085; }
    .nn-badge-delivered { background: #D4EDDA; color: #155724; }
    .nn-badge-rejected { background: #F8D7DA; color: #721C24; }
    .nn-badge-canceled { background: #F8D7DA; color: #721C24; }

    .nn-unresolved-box {
        display: flex;
        align-items: center;
        gap: 15px;
        background: #fff;
        border: 1px solid #FFE0B2;
        border-radius: 10px;
        padding: 16px 20px;
    }
    .nn-unresolved-box .unresolved-num {
        font-size: 2rem;
        font-weight: 800;
        color: #FF6B35;
        line-height: 1;
    }
    .nn-unresolved-box .unresolved-label {
        font-size: 0.85rem;
        color: #888;
        font-weight: 500;
    }

    .nn-chef-header {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        color: #999;
        text-transform: uppercase;
        font-weight: 600;
        padding-bottom: 8px;
        border-bottom: 2px solid #FFE0B2;
        margin-bottom: 4px;
    }

    .nn-placeholder-img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FF8C00, #FFA500);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 0.9rem;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .nn-placeholder-sq {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: linear-gradient(135deg, #FF8C00, #FFA500);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        margin-right: 12px;
        flex-shrink: 0;
    }
</style>

<div class="nn-dashboard">

   
    {{-- Summary Boxes --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="nn-stat-card">
                <div class="stat-label">Today's Orders</div>
                <div class="stat-value">{{ number_format($todaysOrders) }}</div>
            </div>
        </div>
       <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
    <div class="nn-stat-card">
        <div class="stat-label">
            Today's Revenue
            <i class="fa fa-info-circle" style="color: #999; font-size: 11px;" 
               title="Calculated as total Dish Base Price (Chef's 90% + Platform's 10%)."></i>
        </div>
        <div class="stat-value">₹ {{ number_format($todaysRevenue, 2) }}</div>
        <div style="font-size: 10px; color: #6c757d; margin-top: 5px;">
            (Calculated by removing GST and Platform Fees from the total order amount)
        </div>
    </div>
</div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
    <div class="nn-stat-card">
        <div class="stat-label">
            Security Deposite 
            <i class="fa fa-info-circle" 
               style="cursor: pointer; color: #007bff; font-size: 12px;" 
               title="Calculated as 10% Security Deposit on the base price of delivered orders.">
            </i>
        </div>
        <div class="stat-value">₹ {{ number_format($todaysCommission, 2) }}</div>
        
        <div style="font-size: 10px; color: #6c757d; margin-top: 5px; line-height: 1.2;">
            (10% Security Deposit on Base Price)
        </div>
    </div>
</div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="nn-stat-card">
                <div class="stat-label">Avg. Prep Time</div>
                <div class="stat-value">{{ $avgPrepTime }} min</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="nn-stat-card">
                <div class="stat-label">Pending Applications</div>
                <div class="stat-value">{{ \App\Models\Chef::where('is_verify','0')->count() }}</div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row mb-4">
        <div class="col-lg-7 mb-3">
            <div class="nn-card">
                <div class="nn-card-title">Orders & Revenue</div>
                <div style="height: 280px;">
                    <canvas id="ordersRevenueChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-3">
            <div class="nn-card">
                <div class="nn-card-title">Popular Cuisine Types</div>
                <div style="height: 280px;">
                    <canvas id="cuisinePieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Listings Row 1 --}}
    <div class="row mb-4">
        {{-- Top Chefs --}}
        <div class="col-lg-4 mb-3">
            <div class="nn-card">
                <div class="nn-card-title">Top Chefs</div>
                <div class="nn-chef-header">
                    <span>Chef</span>
                    <div style="display:flex; gap:20px;">
                        <span>Orders</span>
                        <span>Earnings</span>
                    </div>
                </div>
                @forelse($topChefs as $chef)
                <div class="nn-chef-row">
                    @if($chef->profile_image)
                        <img src="{{ asset($chef->profile_image) }}" alt="{{ $chef->name }}">
                    @else
                        <div class="nn-placeholder-img">{{ strtoupper(substr($chef->name, 0, 1)) }}</div>
                    @endif
                    <div class="chef-info">
                        <h6>{{ $chef->name }}</h6>
                    </div>
                    <div class="chef-stats">
                        <span><strong>{{ $chef->total_orders }}</strong></span>
                        <span><strong>₹{{ number_format($chef->total_earnings) }}</strong></span>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-3">No data available</p>
                @endforelse
            </div>
        </div>

        {{-- Popular Menu Items --}}
        <div class="col-lg-4 mb-3">
            <div class="nn-card">
                <div class="nn-card-title">Popular Menu Items</div>
                @forelse($popularDishes as $dish)
                <div class="nn-menu-item">
                    @if(!empty($dish['image']))
                        <img src="{{ asset($dish['image']) }}" alt="{{ $dish['name'] }}">
                    @else
                        <div class="nn-placeholder-sq">
                            <i class="mdi mdi-food"></i>
                        </div>
                    @endif
                    <span class="item-name">{{ $dish['name'] }}</span>
                    <span class="item-count">{{ $dish['total_quantity'] }}</span>
                </div>
                @empty
                <p class="text-muted text-center py-3">No data available</p>
                @endforelse
            </div>
        </div>

        {{-- Customer Retention --}}
        <div class="col-lg-4 mb-3">
            <div class="nn-card">
                <div class="nn-card-title">Retention</div>

                @if($topCustomer)
                <div class="nn-retention-row">
                    <div>
                        <div class="ret-label">Top Customer</div>
                        <div class="ret-name">{{ $topCustomer->name }}</div>
                    </div>
                    <div class="ret-count">{{ $topCustomer->total_orders }}</div>
                </div>
                @endif

                @if($regularCustomer)
                <div class="nn-retention-row">
                    <div>
                        <div class="ret-label">Regular Customer</div>
                        <div class="ret-name">{{ $regularCustomer->name }}</div>
                    </div>
                    <div class="ret-count">{{ $regularCustomer->total_orders }}</div>
                </div>
                @endif

                @if($newCustomer)
                <div class="nn-retention-row">
                    <div>
                        <div class="ret-label">New Customer</div>
                        <div class="ret-name">{{ $newCustomer->name }}</div>
                    </div>
                    <div class="ret-count">New</div>
                </div>
                @endif

                @if(!$topCustomer && !$regularCustomer && !$newCustomer)
                <p class="text-muted text-center py-3">No data available</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Listings Row 2 --}}
    <div class="row mb-4">
        {{-- Top Customers --}}
        <div class="col-lg-4 mb-3">
            <div class="nn-card">
                <div class="nn-card-title">Top Customers</div>
                @forelse($topCustomers as $customer)
                <div class="nn-customer-row">
                    <span class="cust-name">{{ $customer->name }}</span>
                    <span class="cust-orders">{{ $customer->total_orders }}</span>
                </div>
                @empty
                <p class="text-muted text-center py-3">No data available</p>
                @endforelse
            </div>
        </div>

        {{-- New Orders --}}
        <div class="col-lg-4 mb-3">
            <div class="nn-card">
                <div class="nn-card-title">New Orders</div>
                <div class="nn-unresolved-box mb-3">
                    <div class="unresolved-num">{{ $unresolvedOrders }}</div>
                    <div class="unresolved-label">Unresolved</div>
                </div>
                @forelse($latestOrders as $order)
                <div class="nn-order-card">
                    <div class="order-info">
                        <h6>Order #{{ $order->id }}</h6>
                        <small>{{ \Carbon\Carbon::parse($order->created_at)->format('h:i A') }}</small>
                    </div>
                    <div class="order-meta">
                        <div class="nn-badge nn-badge-{{ $order->status }}">{{ $order->status }}</div>
                        <div style="font-weight:700; color:#333; font-size:0.85rem; margin-top:4px;">₹{{ number_format($order->amount) }}</div>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-3">No orders yet</p>
                @endforelse
            </div>
        </div>

        {{-- Pre Orders / Additional Info --}}
        <div class="col-lg-4 mb-3">
            <div class="nn-card">
                <div class="nn-card-title">Issues / Complaints</div>
                <div class="nn-unresolved-box">
                    <div class="unresolved-num">{{ $issues }}</div>
                    <div>
                        <div class="unresolved-label">Unresolved Orders</div>
                        <div style="font-size:0.75rem; color:#aaa;">Needs attention</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Orders & Revenue Chart
        const ordersCtx = document.getElementById('ordersRevenueChart').getContext('2d');
        const ordersData = @json($chartOrders);
        const revenueData = @json($chartRevenue);

        const gradient1 = ordersCtx.createLinearGradient(0, 0, 0, 280);
        gradient1.addColorStop(0, 'rgba(255, 140, 0, 0.3)');
        gradient1.addColorStop(1, 'rgba(255, 140, 0, 0.01)');

        const gradient2 = ordersCtx.createLinearGradient(0, 0, 0, 280);
        gradient2.addColorStop(0, 'rgba(255, 165, 0, 0.2)');
        gradient2.addColorStop(1, 'rgba(255, 165, 0, 0.01)');

        new Chart(ordersCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [
                    {
                        label: 'Orders',
                        data: ordersData,
                        borderColor: '#FF8C00',
                        backgroundColor: gradient1,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#FF8C00',
                    },
                    {
                        label: 'Revenue',
                        data: revenueData,
                        borderColor: '#FFA500',
                        backgroundColor: gradient2,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#FFA500',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { size: 12, weight: '600' }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { font: { size: 11 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '600' } }
                    }
                }
            }
        });

        // Cuisine Pie Chart
        const cuisineCtx = document.getElementById('cuisinePieChart').getContext('2d');
        const cuisineLabels = @json($cuisineLabels);
        const cuisineData = @json($cuisineData);
        const cuisineColors = @json(array_slice($cuisineColors, 0, count($cuisineLabels)));

        new Chart(cuisineCtx, {
            type: 'doughnut',
            data: {
                labels: cuisineLabels.length > 0 ? cuisineLabels : ['No Data'],
                datasets: [{
                    data: cuisineData.length > 0 ? cuisineData : [100],
                    backgroundColor: cuisineColors.length > 0 ? cuisineColors : ['#E0E0E0'],
                    borderWidth: 2,
                    borderColor: '#FFF8F0'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 15,
                            font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed}%`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>

@endsection
