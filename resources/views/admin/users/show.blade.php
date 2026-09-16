@extends('layouts.app')

@section('content')
<div class="container">
    <a href="{{ route('users.index') }}" class="text-primary mb-3 d-inline-block">
        <i class="fa fa-arrow-left"></i> Back to Users
    </a>

    {{-- User Detail Card --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                {{-- Left Side: User Image --}}
                <div class="col-md-3 text-center">
                    @if($user->image)
                        <img src="{{ asset('/images/' . $user->image) }}" 
                             alt="{{ $user->name }}" 
                             class="img-fluid rounded shadow-sm"
                             style="width: 200px; height: 200px; object-fit: cover;">
                    @else
                        <img src="{{ asset('/images/default.png') }}" 
                             alt="Default User" 
                             class="img-fluid rounded shadow-sm"
                             style="width: 200px; height: 200px; object-fit: cover;">
                    @endif
                </div>

                {{-- Right Side: User Details --}}
                <div class="col-md-9">
                    <h3 class="mb-2">{{ $user->name }}</h3>
                    
                    <p>
                        <span class="badge {{ $user->status == 'active' ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </p>

                    <p><i class="fa fa-envelope text-primary me-2"></i> {{ $user->email }}</p>
                    <p><i class="fa fa-phone text-primary me-2"></i> {{ $user->phone_number }}</p>
                    
                    <p><i class="fa fa-calendar text-primary me-2"></i> {{ $user->dob }}</p>
                    <p><i class="fa fa-user text-primary me-2"></i> {{ $user->gender }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- User Addresses Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-map-marker me-2"></i> User Addresses</h5>
        </div>
        <div class="card-body">
            @if($addresses->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-stripped table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 40%">Full Address</th>
                                <th style="width: 15%">Pincode</th>
                                <th style="width: 15%">Tag</th>
                                <th style="width: 10%">Selected</th>
                                <th style="width: 15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($addresses as $index => $address)
                                <tr>
                                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                    <td>{{ $address->full_address }}</td>
                                    <td>{{ $address->pincode ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-info text-dark px-3 py-2 rounded-pill">
                                            {{ $address->tag ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($address->is_selected == 1)
                                            <span class="badge bg-success px-3 py-2 rounded-pill">
                                                <i class="fa fa-check me-1"></i> Yes
                                            </span>
                                        @else
                                            <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                                No
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-danger btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal{{ $address->id }}"
                                                title="Delete Address">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                                {{-- Delete Confirmation Modal --}}
                                <div class="modal fade" id="deleteModal{{ $address->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title text-danger">
                                                    <i class="fa fa-exclamation-triangle me-2"></i> Confirm Delete
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete this address?</p>
                                                <div class="alert alert-light">
                                                    <small><strong>Address:</strong> {{ Str::limit($address->full_address, 100) }}</small><br>
                                                    @if($address->tag)
                                                        <small><strong>Tag:</strong> {{ $address->tag }}</small>
                                                    @endif
                                                </div>
                                                <p class="text-danger mb-0">
                                                    <small><i class="fa fa-info-circle me-1"></i> This action cannot be undone.</small>
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fa fa-times me-1"></i> Cancel
                                                </button>
                                                <form action="{{ route('addresses.destroy', $address->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fa fa-trash me-1"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No addresses found for this user.</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Optional: Add some JavaScript for better UX
    document.addEventListener('DOMContentLoaded', function() {
        // You can add confirmation before delete without modal as alternative
        // document.querySelectorAll('.delete-address').forEach(button => {
        //     button.addEventListener('click', function(e) {
        //         if (!confirm('Are you sure you want to delete this address?')) {
        //             e.preventDefault();
        //         }
        //     });
        // });
    });
</script>
@endpush