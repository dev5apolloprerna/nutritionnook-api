@extends('layouts.app')

@section('content')
<style>
    .verify-toggle {
        position: relative;
        width: 50px;
        height: 24px;
        -webkit-appearance: none;
        background: #c6c6c6;
        outline: none;
        border-radius: 20px;
        transition: 0.4s;
        cursor: pointer;
    }

    .verify-toggle:checked {
        background: #28a745; /* green for verified */
    }

    .verify-toggle::before {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        background: white;
        transition: 0.4s;
    }

    .verify-toggle:checked::before {
        transform: translateX(26px);
    }
</style>


    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Chef Management</h4>
                        <p class="card-description mb-0">All Registered Chefs</p>
                    </div>
                    <div class="d-flex gap-2">
                        {{-- <a href="{{ route('pdf.export', 'chefs') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-file-pdf-o"></i> Export PDF
                        </a> --}}
                        <a href="{{ route('chefs.create') }}" class="btn btn-primary">+ on board chef</a>
                        
                        <a href="{{ route('export.chefs') }}" class="btn btn-gradient-primary btn-fw">Export Excel</a>
                    </div>

                </div>

                <div class="table-responsive">
                    <table class="table" id="myTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Online Status</th>
                                <th>Verify</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($chefs as $key => $chef)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    @php
    $mainImage = null;

    if (!empty($chef->image)) {
        // Try decoding JSON
        $decoded = json_decode($chef->image, true);

        if (is_array($decoded)) {
            // Multiple images stored as JSON
            $mainImage = $decoded[0] ?? null;
        } else {
            // Single image stored as string
            $mainImage = $chef->image;
        }
    }

    // Remove "public/" if path starts with it so file_exists works correctly
    $imagePath = str_replace('public/', '', $mainImage);
@endphp

<td>
    @if (!empty($mainImage) && file_exists(public_path($imagePath)))
        <img src="{{ asset($mainImage) }}" 
             class="rounded-circle" 
             style="width: 40px; height: 40px;" 
             alt="{{ $chef->name }}">
    @else
        -
    @endif
</td>



                                    <td><strong>{{ $chef->name }}</strong></td>
                                    <td>{{ $chef->phone_number ?? 'N/A' }}</td>
                                    <td>{{ $chef->email }}</td>
                                    <td>{{ $chef->address ?? 'N/A' }}</td>
                                    <td>
                                        <input type="checkbox" class="toggle-switch" data-id="{{ $chef->id }}"
                                            {{ $chef->available ? 'checked' : '' }}>
                                    </td>
                                    <td>
    <input type="checkbox"
           class="verify-toggle"
           data-id="{{ $chef->id }}"
           {{ $chef->is_verify ? 'checked' : '' }}>
</td>
                                    <td>
                                        <a href="{{ route('chefs.edit', $chef->id) }}" class="btn btn-sm" title="Edit">
                                            <i class="fas fa-edit text-primary"></i>
                                        </a>
                                        <a href="{{ route('chefs.details', $chef->id) }}" class="btn btn-sm"
                                            title="View">
                                            <i class="fas fa-eye text-primary"></i>
                                        </a>
                                        <form action="{{ route('chefs.destroy', $chef->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm" title="Delete"
                                                onclick="return confirm('Delete this chef?')">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach

                            @if ($chefs->isEmpty())
                                <tr>
                                    <td colspan="8" class="text-center">No Chefs Found.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Toggle Switch Styling --}}
    <style>
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 24px;
            -webkit-appearance: none;
            background: #c6c6c6;
            outline: none;
            border-radius: 20px;
            transition: 0.4s;
            cursor: pointer;
        }

        .toggle-switch:checked {
            background: #2559f4;
        }

        .toggle-switch::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            background: white;
            transition: 0.4s;
        }

        .toggle-switch:checked::before {
            transform: translateX(26px);
        }
    </style>

    {{-- jQuery + SweetAlert --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.toggle-switch').change(function() {
                let chefId = $(this).data('id');
                let status = $(this).is(':checked') ? 1 : 0;

                $.ajax({
                    url: "{{ route('chefs.toggle') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: chefId,
                        available: status
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to update status!',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong!',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
        });
    </script>
    <script>
        $('.verify-toggle').change(function () {
    let chefId = $(this).data('id');
    let verifyStatus = $(this).is(':checked') ? 1 : 0;

    $.ajax({
        url: "{{ route('chefs.verify.toggle') }}",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            id: chefId,
            is_verify: verifyStatus
        },
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    text: response.message,
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Verification update failed!',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong!',
                confirmButtonText: 'OK'
            });
        }
    });
});

    </script>
@endsection
