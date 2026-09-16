{{-- resources/views/reports/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Reports Dashboard</h4>
                <p class="card-description">Select a report type to view analytics</p>
                
                <div class="row mt-4">
                    <!-- Order & Revenue Reports -->
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card bg-gradient-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-shopping-cart fa-3x mr-3"></i>
                                    <div>
                                        <h5 class="mb-1">Order & Revenue</h5>
                                        <p class="mb-0 small">Track orders, revenue, commissions</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.reports.order-revenue') }}" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chef Performance Reports -->
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card bg-gradient-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-users fa-3x mr-3"></i>
                                    <div>
                                        <h5 class="mb-1">Chef Performance</h5>
                                        <p class="mb-0 small">Earnings, acceptance rate, ratings</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.reports.chef-performance') }}" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Insights -->
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card bg-gradient-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-user fa-3x mr-3"></i>
                                    <div>
                                        <h5 class="mb-1">Customer Insights</h5>
                                        <p class="mb-0 small">New vs returning, top customers</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.reports.customer-insights') }}" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Menu & Cuisine Insights -->
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card bg-gradient-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-cutlery fa-3x mr-3"></i>
                                    <div>
                                        <h5 class="mb-1">Menu & Cuisine</h5>
                                        <p class="mb-0 small">Top dishes, out of stock items</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.reports.menu-cuisine') }}" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Platform Health -->
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card bg-gradient-danger text-white h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-line-chart fa-3x mr-3"></i>
                                    <div>
                                        <h5 class="mb-1">Platform Health</h5>
                                        <p class="mb-0 small">DAU, trends, peak hours</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.reports.platform-health') }}" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection