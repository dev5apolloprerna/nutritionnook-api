@extends('layouts.app')

@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="card-title mb-0">Coupon List</h4>
                    <p class="card-description mb-0">List of all <code>coupons</code> with status and actions.</p>
                </div>
                @if ((isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) || \App\Helpers\CommonHelper::getPermission('Coupons', 'create'))
                    <a href="{{ route('coupons.create') }}" class="btn btn-gradient-primary btn-fw">Add Coupon</a>
                @endif
                
                <a href="{{ route('export.coupons') }}" class="btn btn-gradient-primary btn-fw">Export Excel</a>
            </div>

            <div class="table-responsive">
                <table class="table" id="myTable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Coupon Name</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Validity</th>
                            <th>Status</th>
                            @if ((isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) || 
                                 \App\Helpers\CommonHelper::getPermission('Coupons', 'edit') || 
                                 \App\Helpers\CommonHelper::getPermission('Coupons', 'delete'))
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coupons as $coupon)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $coupon->code }}</td>
                                <td>{{ ucfirst($coupon->type) }}</td>
                                <td>
                                    @if($coupon->type == 'percentage')
                                        {{ $coupon->value }}%
                                    @else
                                        ₹{{ $coupon->value }}
                                    @endif
                                </td>
                                <td>
                                    @if($coupon->starts_at && $coupon->expires_at)
                                        {{ \Carbon\Carbon::parse($coupon->starts_at)->format('d-m-Y h:i A') }} - 
                                        {{ \Carbon\Carbon::parse($coupon->expires_at)->format('d-m-Y h:i A') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @if ($coupon->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                @if ((isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) || 
                                     \App\Helpers\CommonHelper::getPermission('Coupons', 'edit') || 
                                     \App\Helpers\CommonHelper::getPermission('Coupons', 'delete'))
                                    <td>
                                        @if ((isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) || \App\Helpers\CommonHelper::getPermission('Coupons', 'edit'))
                                            <a href="{{ route('coupons.edit', $coupon->id) }}" class="btn btn-sm" title="Edit">
                                                <i class="fa fa-edit text-primary"></i>
                                            </a>
                                        @endif
                                        @if ((isset(auth()->user()->is_admin) && auth()->user()->is_admin == 1) || \App\Helpers\CommonHelper::getPermission('Coupons', 'delete'))
                                            <form action="{{ route('coupons.destroy', $coupon->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm" title="Delete" onclick="return confirm('Delete this coupon?')">
                                                    <i class="fa fa-trash-o text-danger"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection
