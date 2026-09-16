@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h4 class="mb-0 text-primary">
                            <i class="fas fa-clipboard-list mr-2"></i> Order Details - {{ $order->id }}
                        </h4>
                        <p class="text-muted mb-0 small">Order placed on {{ $order->created_at->format('d M Y') }}</p>
                    </div>
                    <div>
                        <a href="{{ route('orders.index') }}" class="btn btn-primary btn-sm" style="color: white;">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Orders
                        </a>

                        <!--<button class="btn btn-primary btn-sm ml-2">-->
                        <!--    <i class="fas fa-print mr-1"></i> Print Invoice-->
                        <!--</button>-->
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-6">
                            <!-- Customer Details -->
                            <div class="card mb-4 border-0 shadow-sm">
                                <div class="card-header bg-light d-flex align-items-center py-3">
                                    <i class="fas fa-user-circle text-primary mr-2"></i>
                                    <h5 class="mb-0 font-weight-bold">Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-4">
                                        <div class="position-relative">
                                            <img src="{{ $customer['avatar'] }}" 
                                                 class="rounded-circle border mr-3" 
                                                 width="70" height="70"
                                                 alt="{{ $customer['name'] }}">
                                            <span class="badge badge-success badge-dot position-absolute" style="bottom: 5px; right: 5px;"></span>
                                        </div>
                                        <div class="ml-3">
                                            <h5 class="mb-2 font-weight-bold">{{ $customer['name'] }}</h5>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-phone-alt text-muted mr-2"></i>
                                                <span class="text-muted">{{ $customer['phone'] }}</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-envelope text-muted mr-2"></i>
                                                <span class="text-muted small">{{ $customer['email'] }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-top pt-3 mt-3">
                                        <h6 class="font-weight-bold d-flex align-items-center mb-2">
                                            <i class="fas fa-map-marker-alt text-danger mr-2"></i> Delivery Address
                                        </h6>
                                        <div class="bg-light p-3 rounded">
                                            <p class="mb-2">{{ $customer['address'] }}</p>
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-map-pin mr-1"></i> Pincode: {{ $customer['pincode'] }}
                                            </p>
                                            <p class="mb-0 text-muted small">
                                                <i class="fas fa-tag mr-1"></i> {{ $customer['tag'] }}
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Order Summary -->
                            <div class="card border-0 shadow-sm">
    <div class="card-header bg-light d-flex align-items-center py-3">
        <i class="fas fa-receipt text-primary mr-2"></i>
        <h5 class="mb-0 font-weight-bold">Order Summary</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="border-top-0">Item</th>
                        <th class="border-top-0 text-center">Qty</th>
                        <th class="border-top-0 text-right">Price</th>
                        <th class="border-top-0 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-1 mr-2" style="width:40px; height:40px;">
                                    <img src="{{ $item['image'] }}" class="rounded" width="38" height="38" alt="{{ $item['name'] }}">
                                </div>
                                <div>
                                    <h6 class="mb-1 font-weight-bold">{{ $item['name'] }}</h6>
                                    <small class="text-muted">Food Item</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center align-middle">{{ $item['qty'] }}</td>
                        <td class="text-right align-middle">₹{{ number_format($item['price'], 2) }}</td>
                        <td class="text-right align-middle font-weight-bold">₹{{ number_format($item['total'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            <!-- Subtotal -->
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Subtotal:</span>
                <span class="font-weight-bold">₹{{ number_format($subtotal, 2) }}</span>
            </div>
            
            <!-- Platform Fee -->
            
            @if(isset($platformFee) && $platformFee > 0)
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Platform Fee:</span>
                <span class="font-weight-bold">₹{{ number_format($platformFee, 2) }}</span>
            </div>
            @endif

            <!-- GST -->
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">GST ({{ $gstSetting }}):</span>
                <span class="font-weight-bold">₹{{ number_format($gstAmount, 2) }}</span>
            </div>

            <!-- Discount (If available) -->
            @if(isset($order->discount_amount) && $order->discount_amount > 0)
            <div class="d-flex justify-content-between mb-2">
                <span class="text-success font-weight-bold">Discount:</span>
                <span class="text-success font-weight-bold">- ₹{{ number_format($order->discount_amount, 2) }}</span>
            </div>
            @endif
            
            <!-- Final Total -->
            <div class="d-flex justify-content-between mt-3 pt-2 border-top">
                <span class="font-weight-bold" style="font-size: 1.1rem;">Total Amount:</span>
                <span class="font-weight-bold text-primary" style="font-size: 1.1rem;">₹{{ number_format($total, 2) }}</span>
            </div>
        </div>

        <div class="p-3 bg-light rounded-bottom">
            <h6 class="font-weight-bold d-flex align-items-center mb-2">
                <i class="fas fa-credit-card text-primary mr-2"></i> Payment Details
            </h6>
            <p class="mb-2"><b>Method:</b> {{ $payment['method'] ?? 'N/A' }}</p>
            <p class="mb-2"><b>Status:</b> 
                <span class="badge {{ ($payment['status'] == 'received' || $payment['status'] == 'paid') ? 'badge-success' : 'badge-warning' }}">
                    {{ strtoupper($payment['status'] ?? 'N/A') }}
                </span>
            </p>
            <p class="mb-0"><b>Paid At:</b> 
                {{ isset($payment['paidAt']) ? \Carbon\Carbon::parse($payment['paidAt'])->format('d M Y, h:i A') : 'N/A' }}
            </p>
        </div>
    </div>
</div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-6">
                            <!-- Delivery Info -->
                            <!--<div class="card mb-4 border-0 shadow-sm">-->
                            <!--    <div class="card-header bg-light d-flex align-items-center py-3">-->
                            <!--        <i class="fas fa-truck text-primary mr-2"></i>-->
                            <!--        <h5 class="mb-0 font-weight-bold">Delivery Information</h5>-->
                            <!--    </div>-->
                            <!--    <div class="card-body">-->
                            <!--        <div class="d-flex align-items-start mb-4">-->
                            <!--            <div class="position-relative mr-3">-->
                            <!--                <img src="{{ $deliveryBoy['avatar'] }}" -->
                            <!--                     class="rounded-circle border mr-3" -->
                            <!--                     width="70" height="70"-->
                            <!--                     alt="{{ $deliveryBoy['name'] }}">-->
                            <!--                <span class="badge badge-success badge-dot position-absolute" style="bottom: 5px; right: 5px;"></span>-->
                            <!--            </div>-->
                            <!--            <div>-->
                            <!--                <h5 class="mb-2 font-weight-bold">{{ $deliveryBoy['name'] }}</h5>-->
                            <!--                <div class="d-flex align-items-center mb-2">-->
                            <!--                    <div class="text-warning mr-2">-->
                            <!--                        @for($i = 1; $i <= 5; $i++)-->
                            <!--                            @if($i <= floor($deliveryBoy['rating']))-->
                            <!--                                <i class="fas fa-star"></i>-->
                            <!--                            @elseif($i == ceil($deliveryBoy['rating']) && $deliveryBoy['rating'] - floor($deliveryBoy['rating']) > 0)-->
                            <!--                                <i class="fas fa-star-half-alt"></i>-->
                            <!--                            @else-->
                            <!--                                <i class="far fa-star"></i>-->
                            <!--                            @endif-->
                            <!--                        @endfor-->
                            <!--                    </div>-->
                            <!--                    <span class="badge badge-dark">{{ $deliveryBoy['rating'] }}/5</span>-->
                            <!--                </div>-->
                            <!--                <div class="d-flex align-items-center mb-2">-->
                            <!--                    <i class="fas fa-phone-alt text-muted mr-2"></i>-->
                            <!--                    <span class="text-muted">{{ $deliveryBoy['phone'] }}</span>-->
                            <!--                </div>-->
                            <!--                <div class="d-flex align-items-center mb-2">-->
                            <!--                    <i class="fas fa-biking text-muted mr-2"></i>-->
                            <!--                    <span class="text-muted small">{{ $deliveryBoy['vehicle'] }}</span>-->
                            <!--                </div>-->
                            <!--            </div>-->
                            <!--        </div>-->
                            <!--    </div>-->
                            <!--</div>-->

                            <!-- Order Tracking -->
                            <!--<div class="card border-0 shadow-sm">-->
                            <!--    <div class="card-header bg-light d-flex align-items-center py-3">-->
                            <!--        <i class="fas fa-map-marked-alt text-primary mr-2"></i>-->
                            <!--        <h5 class="mb-0 font-weight-bold">Order Tracking</h5>-->
                            <!--    </div>-->
                            <!--    <div class="card-body">-->
                            <!--        <div class="timeline-steps">-->
                            <!--            @foreach($tracking as $track)-->
                            <!--            <div class="timeline-step {{ $track['active'] ? 'active' : '' }}">-->
                            <!--                <div class="timeline-icon">-->
                            <!--                    @if($track['active'])-->
                            <!--                        <i class="fas fa-check"></i>-->
                            <!--                    @else-->
                            <!--                        <i class="fas {{ $loop->last ? 'fa-home' : 'fa-motorcycle' }}"></i>-->
                            <!--                    @endif-->
                            <!--                </div>-->
                            <!--                <div class="timeline-content">-->
                            <!--                    <h6 class="font-weight-bold">{{ $track['status'] }}</h6>-->
                            <!--                    <p class="text-muted small mb-0">{{ $track['time'] }}</p>-->
                            <!--                </div>-->
                            <!--            </div>-->
                            <!--            @endforeach-->
                            <!--        </div>-->
                            <!--    </div>-->
                            <!--</div>-->
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light d-flex align-items-center py-3">
                                    <i class="fas fa-map-marked-alt text-primary mr-2"></i>
                                    <h5 class="mb-0 font-weight-bold">Order Tracking</h5>
                                </div>
                                <div class="card-body">
                                    <div class="timeline-steps">
                                        @php $limit = 3; @endphp {{-- pehle 3 hi dikhayenge --}}
                                        @foreach($tracking as $index => $track)
                                            <div class="timeline-step {{ $track['active'] ? 'active' : '' }} {{ $index >= $limit ? 'd-none more-history' : '' }}">
                                                <div class="timeline-icon">
                                                    @if($track['active'])
                                                        <i class="fas fa-check text-success"></i>
                                                    @else
                                                        <i class="fas fa-clock text-secondary"></i>
                                                    @endif
                                                </div>
                                                <div class="timeline-content">
                                                    <h6 class="font-weight-bold">{{ $track['status'] }}</h6>
                                                    <p class="text-muted small mb-0">{{ $track['time'] }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                            
                                    {{-- View More Button --}}
                                    @if(count($tracking) > $limit)
                                        <div class="text-center mt-3">
                                            <button class="btn btn-outline-primary btn-sm" id="viewMoreBtn">View More</button>
                                        </div>
                                    @endif
                                </div>
                            </div>



                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .badge-dot {
        width: 10px;
        height: 10px;
        padding: 0;
        border-radius: 50%;
        border: 2px solid white;
    }
    .timeline-steps {
        position: relative;
        padding-left: 2rem;
    }
    .timeline-step {
        position: relative;
        padding-bottom: 1.5rem;
    }
    .timeline-step:last-child { padding-bottom: 0; }
    .timeline-icon {
        position: absolute;
        left: -2rem;
        width: 36px; height: 36px;
        border-radius: 50%;
        background: #e9ecef;
        display: flex; align-items: center; justify-content: center;
        color: #6c757d; z-index: 2;
    }
    .timeline-step.active .timeline-icon {
        background: #4e73df; color: white;
    }
    .timeline-content { padding-left: 1rem; }
    .timeline-step::before {
        content: ''; position: absolute; left: -0.7rem; top: 36px; bottom: 0;
        width: 2px; background: #e9ecef;
    }
    .timeline-step.active::before {
        background: #4e73df; opacity: 0.3;
    }
    .timeline-step:last-child::before { display: none; }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const btn = document.getElementById("viewMoreBtn");
    if (btn) {
        btn.addEventListener("click", function() {
            document.querySelectorAll(".more-history").forEach(el => el.classList.remove("d-none"));
            btn.style.display = "none"; // button hide after click
        });
    }
});
</script>
@endsection
