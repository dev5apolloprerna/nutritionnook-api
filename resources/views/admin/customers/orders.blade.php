@extends('layouts.app')

@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="card-title mb-1">Order Details</h4>
                    <p class="card-description mb-0">
                        All orders placed by <strong>{{ $user->name }}</strong>
                        ({{ $user->email }})
                    </p>
                </div>
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Customers
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Order ID</th>
                            <th>Chef</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Placed At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                        @php
                        $items = is_array($order->items)
                        ? $order->items
                        : (json_decode($order->items, true) ?: []);
                        $itemSummary = collect($items)->map(function ($item) {
                        $name = $item['name'] ?? 'Unknown item';
                        $quantity = $item['quantity'] ?? 1;

                        return $name . ' × ' . $quantity;
                        })->implode(', ');
                        $paymentStatus = $order->payment_status
                        ?? data_get($order->payment, 'status')
                        ?? 'N/A';
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>#{{ $order->id }}</strong></td>
                            <td>{{ $order->chef->name ?? 'N/A' }}</td>
                            <td>
                                <span title="{{ $itemSummary }}">
                                    {{ \Illuminate\Support\Str::limit($itemSummary ?: 'No items', 55) }}
                                </span>
                            </td>
                            <td>₹{{ number_format((float) $order->amount, 2) }}</td>
                            <td>{{ ucfirst($paymentStatus) }}</td>
                            <td>
                                <span class="badge badge-{{ $order->status === 'delivered' ? 'success' : ($order->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>{{ $order->date ? \Carbon\Carbon::parse($order->date)->format('d M Y, h:i A') : 'N/A' }}</td>
                            <td>{{ $order->created_at ? $order->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('orders.show', $order->id) }}" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                No orders found for this customer.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection