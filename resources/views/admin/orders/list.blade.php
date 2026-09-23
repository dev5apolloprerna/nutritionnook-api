@extends('layouts.app')

@section('content')
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Order Management</h4>
                        <!-- <p class="card-description mb-0">All Customer Orders</p> -->
                        <p class="card-description mb-0">Customer orders grouped by delivery date and status</p>
                    </div>
                   
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('pdf.export', ['model' => 'chef_orders']) }}" 
                            class="btn btn-sm btn-primary d-flex align-items-center">
                                <i class="fa fa-file-pdf-o me-2"></i> Export PDF
                        </a>
                        <a href="{{ route('export.orders') }}" class="btn btn-gradient-primary btn-fw">Export Excel</a>
                        <form method="GET" action="{{ route('orders.index') }}" class="d-flex">
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                                <option value="all" {{ ($status ?? '') == 'all' ? 'selected' : '' }}>All Status</option>
                                <option value="new" {{ ($status ?? '') == 'new' ? 'selected' : '' }}>New</option>
                                <option value="accepted" {{ ($status ?? '') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                                <option value="preparing" {{ ($status ?? '') == 'preparing' ? 'selected' : '' }}>Preparing</option>
                                <option value="ready" {{ ($status ?? '') == 'ready' ? 'selected' : '' }}>Ready</option>
                                <option value="delivered" {{ ($status ?? '') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="rejected" {{ ($status ?? '') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </form>
                    </div>

                    <!--<div class="d-flex">-->
                    <!--    <select class="form-control form-control-sm mr-2" style="width: 120px;">-->
                    <!--        <option>All Status</option>-->
                    <!--        <option>Pending</option>-->
                    <!--        <option>Preparing</option>-->
                    <!--        <option>On Delivery</option>-->
                    <!--        <option>Completed</option>-->
                    <!--        <option>Cancelled</option>-->
                    <!--    </select>-->
                    <!--    <input type="date" class="form-control form-control-sm" style="width: 150px;">-->
                    <!--</div>-->
                </div>
                
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $tab === 'today' ? 'active' : '' }}"
                            href="{{ route('orders.index', ['tab' => 'today']) }}">
                            Today's Orders
                            <span class="badge badge-pill badge-light ml-1">{{ $tabCounts['today'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $tab === 'pre_orders' ? 'active' : '' }}"
                            href="{{ route('orders.index', ['tab' => 'pre_orders']) }}">
                            Pre-orders
                            <span class="badge badge-pill badge-light ml-1">{{ $tabCounts['pre_orders'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $tab === 'completed' ? 'active' : '' }}"
                            href="{{ route('orders.index', ['tab' => 'completed']) }}">
                            Completed / Rejected
                            <span class="badge badge-pill badge-light ml-1">{{ $tabCounts['completed'] }}</span>
                        </a>
                    </li>
                </ul>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Order ID</th>
                                <th>Chef</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Order Time</th>
                                <th>Get Now/Later</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $index => $order)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $order->id }}</td>
                            
                                    {{-- Chef --}}
                                    <td>
                                        <div class="d-flex align-items-center">
                                           
                                            <div>
                                                <strong>{{ $order->chef->name ?? '-'}}</strong>
                                               
                                            </div>
                                        </div>
                                    </td>
                            
                                    {{-- Customer --}}
                                    <td>
                                        <div class="d-flex align-items-center">
                                           
                                                <img src="{{ $order->user && $order->user->image ? asset('/images/'.$order->user->image) : asset('/images/user-image.png') }}"
                                 class="rounded-circle mr-2"
                                 width="40" height="40"
                                 alt="{{ $order->user->name ?? 'User' }}">
                            
                                            <div>
                                                <strong>{{ $order->user->name ?? '-' }}</strong>
                                                
                                            </div>
                                        </div>
                                    </td>
                            
                                    {{-- Items --}}
                                    <td>
                                        <div class="text-truncate" style="max-width: 150px;">
                                            {{ $order->items_text }}
                                        </div>
                                    </td>
                            
                                    {{-- Amount --}}
                                    <td>₹{{ number_format($order->total_amount, 2) }}</td>
                            
                                    {{-- Status --}}
                                    <!--<td>-->
                                    <!--    @if ($order->status == 'accepted')-->
                                    <!--        <span class="badge badge-success">Accepted</span>-->
                                    <!--    @elseif($order->status == 'pending')-->
                                    <!--        <span class="badge badge-warning">Pending</span>-->
                                    <!--    @elseif($order->status == 'on_delivery')-->
                                    <!--        <span class="badge badge-info"><i class="fas fa-motorcycle"></i> On Delivery</span>-->
                                    <!--    @endif-->
                                    <!--</td>-->
                                    {{-- Status --}}
                            <td>
                                @php
                                    $statusClasses = [
                                        'new'       => 'btn-secondary',
                                        'accept'  => 'btn-primary',
                                        'preparing' => 'btn-warning',
                                        'ready'     => 'btn-info',
                                        'delivered' => 'btn-success',
                                        'reject'  => 'btn-danger',
                                    ];
                                @endphp
                            
                                <div class="btn-group">
                                    @php
    $displayStatus = $order->status;

    if ($order->status === 'rejected') {
        if (!empty($order->rejected_by)) {
            $displayStatus = 'rejected';
        } else {
            $displayStatus = 'cancelled';
        }
    }
@endphp
                                    <button type="button"
                                        class="btn btn-sm {{ $statusClasses[$order->status] ?? 'btn-secondary' }} dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        
                                        {{ ucfirst($displayStatus) }}
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="new">New</a></li>
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="accepted">Accepted</a></li>
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="preparing">Preparing</a></li>
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="ready">Ready</a></li>
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="delivered">Delivered</a></li>
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="rejected">Rejected</a></li>
                                    </ul>
                                </div>
                            </td>


        {{-- Date --}}
        <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}</td>
        
        <td>
            {{ \Carbon\Carbon::parse($order->date)->format('d M Y') }}
        </td>

        {{-- Actions --}}
        <td>
            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-primary">
                <i class="fas fa-eye"></i> View
            </a>
            
             @if ($order->status === 'delivered')
        <button 
            class="btn btn-sm btn-danger refund-order"
            data-order-id="{{ $order->id }}">
            <i class="fas fa-undo"></i> Refund
        </button>
    @endif
        </td>
    </tr>
@endforeach

                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('change-status')) {
            let orderId = e.target.getAttribute('data-order-id');
            let status = e.target.getAttribute('data-status');

            fetch(`{{ route('orders.updateStatusManagement') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: status
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // ✅ Map Laravel status => Bootstrap btn class
                        const statusClasses = {
                            new: 'btn-secondary',
                            accepted: 'btn-primary',
                            preparing: 'btn-warning',
                            ready: 'btn-info',
                            delivered: 'btn-success',
                            rejected: 'btn-danger'
                        };

                        // ✅ Update button text & color instantly
                        let btn = e.target.closest('.btn-group').querySelector('button');
                        btn.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        btn.className = `btn btn-sm dropdown-toggle ${statusClasses[status] ?? 'btn-secondary'}`;

                        // ✅ Show SweetAlert success popup
                        Swal.fire({
                            title: 'Success!',
                            text: `Order status updated to "${status.charAt(0).toUpperCase() + status.slice(1)}".`,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    }
                });
        }
    });
</script>

<script>
    document.addEventListener('click', function (e) {
    const btn = e.target.closest('.refund-order');
    if (!btn) return;

    const orderId = btn.dataset.orderId;

    Swal.fire({
        title: 'Confirm Refund',
        text: 'The customer will receive a 100% refund. Do you want to proceed?.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Refund',
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("{{ route('orders.refund') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(res => res.json())
            .then(data => {
                Swal.fire(
                    data.success ? 'Success' : 'Error',
                    data.message,
                    data.success ? 'success' : 'error'
                );
            });
        }
    });
});

</script>


@endsection
